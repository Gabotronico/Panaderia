<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\Producto;
use App\Models\Produccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProduccionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-produccion')->only(['index', 'show']);
        $this->middleware('permission:crear-produccion')->only(['create', 'store']);
        $this->middleware('permission:eliminar-produccion')->only(['destroy']);
    }

    public function index()
    {
        $producciones = Produccion::with(['user', 'insumos', 'productos'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        return view('produccion.index', compact('producciones'));
    }

    public function create()
    {
        $insumos = Insumo::where('activo', true)->orderBy('nombre')->get();
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();

        return view('produccion.create', compact('insumos', 'productos'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha'                  => 'required|date',
            'observaciones'          => 'nullable|string|max:500',
            'insumos'                => 'required|array|min:1',
            'insumos.*.insumo_id'    => 'required|exists:insumos,id',
            'insumos.*.cantidad'     => 'required|numeric|min:0.001',
            'productos'              => 'required|array|min:1',
            'productos.*.producto_id'=> 'required|exists:productos,id',
            'productos.*.cantidad'   => 'required|integer|min:1',
        ], [
            'insumos.required'   => 'Agregue al menos un insumo consumido.',
            'productos.required' => 'Agregue al menos un producto obtenido.',
        ]);

        // Si el mismo insumo (o producto) viene repetido en varias filas, se
        // acumulan: de otro modo el segundo pivot pisaría al primero y el stock
        // quedaría descuadrado respecto de lo que muestra el detalle.
        $insumos   = $this->agrupar($datos['insumos'], 'insumo_id');
        $productos = $this->agrupar($datos['productos'], 'producto_id');

        $registros = Insumo::whereIn('id', array_keys($insumos))->get()->keyBy('id');

        // Se valida todo antes de tocar nada: es preferible rechazar la carga
        // entera a dejar el depósito a medio descontar.
        $faltantes = [];
        foreach ($insumos as $id => $cantidad) {
            $insumo = $registros[$id];
            if ((float) $insumo->cantidad_stock < $cantidad) {
                $faltantes[] = "{$insumo->nombre}: necesita "
                    . $this->formatoCantidad($cantidad) . " {$insumo->unidad_medida}, "
                    . "hay " . $this->formatoCantidad($insumo->cantidad_stock);
            }
        }

        if ($faltantes) {
            throw ValidationException::withMessages([
                'insumos' => 'Stock insuficiente — ' . implode(' · ', $faltantes),
            ]);
        }

        $produccion = DB::transaction(function () use ($datos, $insumos, $productos, $registros) {
            $produccion = Produccion::create([
                'fecha'         => $datos['fecha'],
                'observaciones' => $datos['observaciones'] ?? null,
                'user_id'       => Auth::id(),
            ]);

            foreach ($insumos as $id => $cantidad) {
                $insumo = $registros[$id];

                $produccion->insumos()->attach($id, [
                    'cantidad'       => $cantidad,
                    // Se congela el costo del día: si mañana sube la harina,
                    // esta corrida sigue reflejando lo que costó realmente.
                    'costo_unitario' => $insumo->costo_unitario,
                ]);

                $insumo->decrement('cantidad_stock', $cantidad);
            }

            foreach ($productos as $id => $cantidad) {
                $produccion->productos()->attach($id, ['cantidad' => $cantidad]);
                Producto::whereKey($id)->increment('stock', $cantidad);
            }

            return $produccion;
        });

        return redirect()->route('produccion.show', $produccion)
            ->with('success', 'Producción registrada: se descontaron los insumos y el stock de productos quedó actualizado.');
    }

    public function show(Produccion $produccion)
    {
        $produccion->load(['user', 'insumos', 'productos']);

        return view('produccion.show', compact('produccion'));
    }

    /**
     * Anula una producción y devuelve el stock a como estaba.
     *
     * Se bloquea si los productos ya se vendieron: descontarlos dejaría el
     * stock en negativo y el depósito diría algo que no es cierto.
     */
    public function destroy(Produccion $produccion)
    {
        $produccion->load(['insumos', 'productos']);

        $vendidos = [];
        foreach ($produccion->productos as $producto) {
            if ((int) $producto->stock < (int) $producto->pivot->cantidad) {
                $vendidos[] = "{$producto->nombre}: se produjeron {$producto->pivot->cantidad} "
                    . "y quedan {$producto->stock} en stock";
            }
        }

        if ($vendidos) {
            return redirect()->route('produccion.show', $produccion)->with('error',
                'No se puede anular: ya se vendió parte de lo producido — ' . implode(' · ', $vendidos)
                . '. Registre el faltante como merma si corresponde.'
            );
        }

        $id = $produccion->id;

        DB::transaction(function () use ($produccion) {
            foreach ($produccion->insumos as $insumo) {
                $insumo->increment('cantidad_stock', $insumo->pivot->cantidad);
            }

            foreach ($produccion->productos as $producto) {
                $producto->decrement('stock', $producto->pivot->cantidad);
            }

            // Los detalles se van en cascada con la producción.
            $produccion->delete();
        });

        return redirect()->route('produccion.index')
            ->with('success', "Se anuló la producción #{$id}: los insumos volvieron al stock y los productos se descontaron.");
    }

    /**
     * Suma las cantidades de las filas que repiten el mismo id.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, float>
     */
    private function agrupar(array $filas, string $clave): array
    {
        $totales = [];

        foreach ($filas as $fila) {
            $id = (int) $fila[$clave];
            $totales[$id] = ($totales[$id] ?? 0) + (float) $fila['cantidad'];
        }

        return $totales;
    }

    /** Muestra 2.5 en vez de 2.500 y 3 en vez de 3.000. */
    private function formatoCantidad(float|string $cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 3, '.', ''), '0'), '.');
    }
}
