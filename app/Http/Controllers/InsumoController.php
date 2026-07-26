<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\CompraInsumo;
use App\Models\MermaInsumo;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InsumoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-insumos')->only(['index', 'show']);
        $this->middleware('permission:crear-insumos')->only(['create', 'store']);
        $this->middleware('permission:editar-insumos')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-insumos')->only(['destroy']);
        $this->middleware('permission:crear-insumos')->only(['comprar']);
    }

    public function index(Request $request)
    {
        $buscar  = $request->input('buscar');
        $insumos = Insumo::when($buscar, fn($q) => $q->where('nombre', 'like', "%{$buscar}%"))
                         ->orderBy('nombre')
                         ->paginate(10)
                         ->withQueryString();

        return view('insumos.index', compact('insumos', 'buscar'));
    }

    public function create()
    {
        return view('insumos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'         => 'required|string|max:150|unique:insumos,nombre',
            'descripcion'    => 'nullable|string',
            'unidad_medida'  => 'required|string|max:50',
            'cantidad_stock' => 'required|numeric|min:0',
            'stock_minimo'   => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'activo'         => 'boolean',
        ], [
            'nombre.unique' => 'Ya existe un insumo con ese nombre. Usa el botón "Registrar Compra" (carrito) para agregar stock al existente.',
        ]);

        Insumo::create($request->all());

        return redirect()->route('insumos.index')
            ->with('success', 'Insumo creado exitosamente.');
    }

    public function show(Insumo $insumo)
    {
        $compras = $insumo->compras()
            ->with('user')
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $mermas = $insumo->mermas()
            ->with('user')
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Datos para gráfica de evolución de precio
        $preciosHistorial = $insumo->compras()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get(['fecha', 'precio_unitario', 'cantidad']);

        $productos = Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'stock']);

        return view('insumos.show', compact('insumo', 'compras', 'mermas', 'preciosHistorial', 'productos'));
    }

    public function edit(Insumo $insumo)
    {
        return view('insumos.edit', compact('insumo'));
    }

    public function update(Request $request, Insumo $insumo)
    {
        $request->validate([
            'nombre'         => 'required|string|max:150|unique:insumos,nombre,' . $insumo->id,
            'descripcion'    => 'nullable|string',
            'unidad_medida'  => 'required|string|max:50',
            'cantidad_stock' => 'required|numeric|min:0',
            'stock_minimo'   => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'activo'         => 'boolean',
        ]);

        $insumo->update($request->all());

        return redirect()->route('insumos.index')
            ->with('success', 'Insumo actualizado exitosamente.');
    }

    public function destroy(Insumo $insumo)
    {
        try {
            $insumo->delete();
            return redirect()->route('insumos.index')
                ->with('success', 'Insumo eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('insumos.index')
                ->with('error', 'No se puede eliminar el insumo porque tiene productos asociados.');
        }
    }

    // Registrar compra de insumo (incrementa stock y guarda historial)
    public function comprar(Request $request, Insumo $insumo)
    {
        $request->validate([
            'cantidad'        => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'fecha'           => 'required|date',
            'observaciones'   => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $insumo) {
            $total = $request->cantidad * $request->precio_unitario;

            CompraInsumo::create([
                'insumo_id'       => $insumo->id,
                'user_id'         => Auth::id(),
                'cantidad'        => $request->cantidad,
                'precio_unitario' => $request->precio_unitario,
                'total'           => $total,
                'fecha'           => $request->fecha,
                'observaciones'   => $request->observaciones,
            ]);

            // Actualizar stock y costo unitario (precio más reciente)
            $insumo->increment('cantidad_stock', $request->cantidad);
            $insumo->update(['costo_unitario' => $request->precio_unitario]);
        });

        return redirect()->route('insumos.show', $insumo)
            ->with('success', "Compra registrada: {$request->cantidad} {$insumo->unidad_medida} de \"{$insumo->nombre}\" agregados al stock.");
    }

    // Registrar merma/descuento manual de stock (solo Administrador)
    public function merma(Request $request, Insumo $insumo)
    {
        if (!Auth::user()->esAdministrador()) {
            abort(403);
        }

        $request->validate([
            'cantidad'      => 'required|numeric|min:0.01',
            'motivo'        => 'required|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'fecha'         => 'required|date',
        ]);

        if ($request->cantidad > $insumo->cantidad_stock) {
            return redirect()->back()
                ->with('error', "No se puede descontar {$request->cantidad} {$insumo->unidad_medida}: el stock actual es {$insumo->cantidad_stock}.");
        }

        DB::transaction(function () use ($request, $insumo) {
            MermaInsumo::create([
                'insumo_id'     => $insumo->id,
                'user_id'       => Auth::id(),
                'cantidad'      => $request->cantidad,
                'motivo'        => $request->motivo,
                'observaciones' => $request->observaciones,
                'fecha'         => $request->fecha,
            ]);

            $insumo->decrement('cantidad_stock', $request->cantidad);
        });

        return redirect()->route('insumos.show', $insumo)
            ->with('success', "Merma registrada: se descontaron {$request->cantidad} {$insumo->unidad_medida} de \"{$insumo->nombre}\".");
    }

    // Mover stock de insumo directo a un producto (sin receta)
    public function moverAProducto(Request $request, Insumo $insumo)
    {
        if (!Auth::user()->esAdministrador()) {
            abort(403);
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad'    => 'required|numeric|min:0.00001',
        ]);

        if ($request->cantidad > $insumo->cantidad_stock) {
            return redirect()->back()
                ->with('error', "Stock insuficiente. Disponible: {$insumo->cantidad_stock} {$insumo->unidad_medida}.");
        }

        $producto = Producto::findOrFail($request->producto_id);

        DB::transaction(function () use ($request, $insumo, $producto) {
            $insumo->decrement('cantidad_stock', $request->cantidad);
            $producto->increment('stock', (int) $request->cantidad);
        });

        $cant = number_format($request->cantidad, 2);
        return redirect()->route('insumos.show', $insumo)
            ->with('success', "{$cant} {$insumo->unidad_medida} de \"{$insumo->nombre}\" movidas al stock de \"{$producto->nombre}\".");
    }
}