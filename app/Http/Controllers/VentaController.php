<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Almacen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-ventas')->only(['index', 'show']);
        $this->middleware('permission:crear-ventas')->only(['create', 'store']);
        $this->middleware('permission:editar-ventas')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-ventas')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Venta::with('user', 'almacen');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Cajero solo ve sus ventas de su almacén
        $user = Auth::user();
        if (!$user->esAdministrador() && $user->almacen_id) {
            $query->where('almacen_id', $user->almacen_id);
        }

        $ventas = $query->orderBy('created_at', 'desc')->paginate(15);
        $ventas->appends($request->all());

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $user    = Auth::user();
        $almacen = $user->almacen;

        if (!$user->esAdministrador() && !$almacen) {
            return redirect()->route('ventas.index')
                ->with('error', 'No tienes un almacén asignado. Contacta al administrador.');
        }

        // Admin sin almacén propio: puede elegir uno
        $almacenes = null;
        if ($user->esAdministrador() && !$almacen) {
            $almacenes = Almacen::where('activo', true)->orderBy('nombre')->get();
            $almacenId = request('almacen_id');
            if ($almacenId) {
                $almacen = Almacen::find($almacenId);
            }
        }

        if ($almacen) {
            $productos = $almacen->productos()
                ->where('activo', true)
                ->wherePivot('stock', '>', 0)
                ->orderBy('nombre')
                ->get()
                ->each(fn($p) => $p->stock_almacen = (int) $p->pivot->stock);
        } else {
            $productos = collect();
        }

        $ultimaVenta = Venta::latest('id')->first();
        $numeroVenta = 'V-' . str_pad(($ultimaVenta ? $ultimaVenta->id + 1 : 1), 6, '0', STR_PAD_LEFT);

        return view('ventas.create', compact('productos', 'numeroVenta', 'almacen', 'almacenes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha'                => 'nullable|date|before_or_equal:today',
            'productos'            => 'required|array|min:1',
            'productos.*.id'       => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'tipo_pago'            => 'required|in:efectivo,qr',
            'monto_recibido'       => 'required|numeric|min:0',
            'descuento'            => 'nullable|numeric|min:0|max:9999',
            'observaciones'        => 'nullable|string|max:500',
        ], [
            'tipo_pago.required'         => 'Seleccione el tipo de pago.',
            'monto_recibido.required_if' => 'Ingrese el monto recibido del cliente.',
            'fecha.before_or_equal'      => 'No se pueden registrar ventas con fecha futura.',
        ]);

        $user    = Auth::user();
        $almacen = $user->almacen;

        // Admin puede vender desde un almacén elegido en la vista
        if ($user->esAdministrador() && !$almacen && $request->almacen_id) {
            $almacen = Almacen::find($request->almacen_id);
        }

        if (!$user->esAdministrador() && !$almacen) {
            return redirect()->back()->with('error', 'No tienes un almacén asignado.');
        }

        try {
            DB::beginTransaction();

            $ultimaVenta = Venta::latest('id')->first();
            $numeroVenta = 'V-' . str_pad(($ultimaVenta ? $ultimaVenta->id + 1 : 1), 6, '0', STR_PAD_LEFT);

            // Pre-cargar productos desde BD para evitar manipulación de precios
            $idsProductos = collect($request->productos)->pluck('id');
            $productosDB  = Producto::whereIn('id', $idsProductos)->get()->keyBy('id');

            $subtotal = 0;
            foreach ($request->productos as $prod) {
                $precio    = (float) $productosDB[$prod['id']]->precio_venta; // precio real de BD
                $subtotal += $prod['cantidad'] * $precio;
            }

            $descuento = $request->descuento ?? 0;
            $total     = $subtotal - $descuento;

            // Calcular cambio solo para efectivo
            $tipoPago      = $request->tipo_pago;
            $montoRecibido = $tipoPago === 'efectivo' ? (float) $request->monto_recibido : $total;
            $cambio        = $tipoPago === 'efectivo' ? max(0, $montoRecibido - $total) : 0;

            if ($tipoPago === 'efectivo' && $montoRecibido < $total) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'El monto recibido (Bs ' . number_format($montoRecibido, 2) . ') es menor al total (Bs ' . number_format($total, 2) . ').')
                    ->withInput();
            }

            $venta = Venta::create([
                'user_id'        => Auth::id(),
                'almacen_id'     => $almacen?->id,
                'numero_venta'   => $numeroVenta,
                'subtotal'       => $subtotal,
                'descuento'      => $descuento,
                'total'          => $total,
                'tipo_pago'      => $tipoPago,
                'monto_recibido' => $montoRecibido,
                'cambio'         => $cambio,
                'estado'         => 'completada',
                'observaciones'  => $request->observaciones,
            ]);

            // Ventas cargadas en diferido —vendieron el domingo y las ingresan
            // el lunes— llevan la fecha real. created_at es lo que miran cortes,
            // finanzas y reportes, por eso se ajusta aparte del create().
            if ($request->filled('fecha')) {
                $venta->forceFill([
                    'created_at' => Carbon::parse($request->fecha)->setTimeFrom(now()),
                ])->save();
            }

            foreach ($request->productos as $prod) {
                $producto = $productosDB[$prod['id']];
                $precio   = (float) $producto->precio_venta; // precio real de BD

                // Verificar stock según contexto
                $stockDisponible = $almacen
                    ? $almacen->stockProducto($producto->id)
                    : $producto->stock;

                if ($stockDisponible < $prod['cantidad']) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', "Stock insuficiente para: {$producto->nombre}")
                        ->withInput();
                }

                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $producto->id,
                    'cantidad'        => $prod['cantidad'],
                    'precio_unitario' => $precio,
                    'subtotal'        => $prod['cantidad'] * $precio,
                ]);

                // Descontar solo del almacén si la venta es desde un almacén,
                // o del stock global (bodega) si es una venta directa sin almacén.
                if ($almacen) {
                    $stockAlmacen = $almacen->stockProducto($producto->id);
                    $almacen->productos()->syncWithoutDetaching([
                        $producto->id => ['stock' => max(0, $stockAlmacen - $prod['cantidad'])],
                    ]);
                } else {
                    $producto->decrement('stock', $prod['cantidad']);
                }
            }

            DB::commit();

            return redirect()->route('ventas.show', $venta->id)
                ->with('success', 'Venta registrada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar la venta. Intente nuevamente.')
                ->withInput();
        }
    }

    public function show(Venta $venta)
    {
        $user = Auth::user();
        // Cajero solo puede ver ventas de su almacén
        if (!$user->esAdministrador() && $user->almacen_id && $venta->almacen_id !== $user->almacen_id) {
            abort(403);
        }

        $venta->load('detalles.producto', 'user', 'almacen');
        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        $user = Auth::user();
        if (!$user->esAdministrador() && $user->almacen_id && $venta->almacen_id !== $user->almacen_id) {
            abort(403);
        }

        if ($venta->estado === 'cancelada') {
            return redirect()->route('ventas.index')
                ->with('error', 'No se puede editar una venta cancelada.');
        }

        $venta->load('detalles.producto');
        return view('ventas.edit', compact('venta'));
    }

    public function update(Request $request, Venta $venta)
    {
        $request->validate([
            'observaciones' => 'nullable|string',
            'estado'        => 'required|in:completada,cancelada',
        ]);

        if ($request->estado === 'cancelada' && $venta->estado !== 'cancelada') {
            $almacen = $venta->almacen;

            foreach ($venta->detalles as $detalle) {
                if ($almacen) {
                    // Restaurar stock del almacén de origen
                    $stockActual = $almacen->stockProducto($detalle->producto_id);
                    $almacen->productos()->syncWithoutDetaching([
                        $detalle->producto_id => ['stock' => $stockActual + $detalle->cantidad],
                    ]);
                } else {
                    // Restaurar stock de bodega global
                    $detalle->producto->increment('stock', $detalle->cantidad);
                }
            }
        }

        $venta->update([
            'observaciones' => $request->observaciones,
            'estado'        => $request->estado,
        ]);

        return redirect()->route('ventas.show', $venta->id)
            ->with('success', 'Venta actualizada exitosamente.');
    }

    public function destroy(Venta $venta)
    {
        try {
            foreach ($venta->detalles as $detalle) {
                $detalle->producto->increment('stock', $detalle->cantidad);
            }
            $venta->delete();
            return redirect()->route('ventas.index')->with('success', 'Venta eliminada.');
        } catch (\Exception $e) {
            return redirect()->route('ventas.index')->with('error', 'Error al eliminar la venta.');
        }
    }

    /** Formulario para registrar lo vendido por monto, sin detallar productos. */
    public function crearDirecta()
    {
        $user    = Auth::user();
        $almacen = $user->almacen;

        // El admin sin almacén propio elige a cuál imputar la venta, igual que
        // en la venta normal.
        $almacenes = ($user->esAdministrador() && !$almacen)
            ? Almacen::where('activo', true)->orderBy('nombre')->get()
            : null;

        if (!$user->esAdministrador() && !$almacen) {
            return redirect()->route('ventas.index')
                ->with('error', 'No tienes un almacén asignado. Contacta al administrador.');
        }

        return view('ventas.directa', compact('almacen', 'almacenes'));
    }

    /**
     * Registra una venta por monto total, sin detalle de productos.
     *
     * Se guarda una venta por medio de cobro: el arqueo del corte de caja
     * separa efectivo de QR, así que meter los dos montos en una sola fila
     * dejaría el cuadre sin saber cuánto entró al cajón.
     *
     * No mueve stock a propósito: no se sabe qué se vendió. Si además se
     * cargan las ventas producto por producto, esto duplicaría los ingresos.
     */
    public function guardarDirecta(Request $request)
    {
        $datos = $request->validate([
            'fecha'         => 'nullable|date|before_or_equal:today',
            'efectivo'      => 'nullable|numeric|min:0|max:9999999',
            'qr'            => 'nullable|numeric|min:0|max:9999999',
            'almacen_id'    => 'nullable|exists:almacenes,id',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'fecha.before_or_equal' => 'No se pueden registrar ventas con fecha futura.',
        ]);

        $efectivo = round((float) ($datos['efectivo'] ?? 0), 2);
        $qr       = round((float) ($datos['qr'] ?? 0), 2);

        if ($efectivo <= 0 && $qr <= 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ingrese al menos un monto: efectivo o QR.');
        }

        $user    = Auth::user();
        $almacen = $user->almacen
            ?? (isset($datos['almacen_id']) ? Almacen::find($datos['almacen_id']) : null);

        if (!$user->esAdministrador() && !$almacen) {
            return redirect()->route('ventas.index')
                ->with('error', 'No tienes un almacén asignado. Contacta al administrador.');
        }

        $montos = array_filter(
            ['efectivo' => $efectivo, 'qr' => $qr],
            fn ($monto) => $monto > 0
        );

        // Si se registra una venta de días pasados —vendieron el domingo y lo
        // cargan el lunes— la venta lleva esa fecha, no la de hoy. Se conserva
        // la hora actual para que el orden dentro del día sea el de carga.
        $momento = isset($datos['fecha'])
            ? Carbon::parse($datos['fecha'])->setTimeFrom(now())
            : now();

        $creadas = DB::transaction(function () use ($montos, $almacen, $datos, $user, $momento) {
            $creadas = [];

            foreach ($montos as $tipoPago => $monto) {
                // El número se pide dentro del bucle: la fila anterior ya está
                // insertada, así que la segunda venta no repite el número.
                $ultima = Venta::latest('id')->first();
                $numero = 'VD-' . str_pad(($ultima ? $ultima->id + 1 : 1), 6, '0', STR_PAD_LEFT);

                $venta = Venta::create([
                    'user_id'        => $user->id,
                    'almacen_id'     => $almacen?->id,
                    'numero_venta'   => $numero,
                    'subtotal'       => $monto,
                    'descuento'      => 0,
                    'total'          => $monto,
                    'tipo_pago'      => $tipoPago,
                    'monto_recibido' => $monto,
                    'cambio'         => 0,
                    'estado'         => 'completada',
                    'es_directa'     => true,
                    'observaciones'  => $datos['observaciones'] ?? null,
                ]);

                // created_at es la fecha con la que la venta cuenta en cortes,
                // finanzas y reportes, así que se ajusta aparte del create().
                $venta->forceFill(['created_at' => $momento])->save();

                $creadas[] = $venta;
            }

            return $creadas;
        });

        $detalle = collect($creadas)
            ->map(fn ($v) => ($v->tipo_pago === 'qr' ? 'QR' : 'Efectivo') . ' Bs ' . number_format($v->total, 2))
            ->implode(' · ');

        $total = number_format($efectivo + $qr, 2);

        return redirect()->route('ventas.index')
            ->with('success', "Venta directa registrada por Bs {$total} ({$detalle}). No se descontó stock.");
    }
}
