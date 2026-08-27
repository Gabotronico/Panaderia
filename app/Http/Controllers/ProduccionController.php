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

            // Lo que costó la corrida, a precio congelado del día. Es la plata
            // que se convierte en producto terminado: de acá sale el costo que
            // después va a llevar cada unidad.
            $costoCorrida = 0.0;

            foreach ($insumos as $id => $cantidad) {
                $insumo = $registros[$id];

                $produccion->insumos()->attach($id, [
                    'cantidad'       => $cantidad,
                    // Se congela el costo del día: si mañana sube la harina,
                    // esta corrida sigue reflejando lo que costó realmente.
                    'costo_unitario' => $insumo->costo_unitario,
                ]);

                $costoCorrida += $cantidad * (float) $insumo->costo_unitario;

                $insumo->decrement('cantidad_stock', $cantidad);
            }

            // Criterio de reparto: si la corrida saca varios productos
            // distintos, el costo de los insumos se reparte PROPORCIONALMENTE
            // A LAS UNIDADES de cada uno. No es exacto —una empanada lleva más
            // relleno que un pan— pero el sistema no guarda receta por
            // producto, así que no hay dato para hacer algo mejor. Es simple,
            // reproducible y defendible ante el contador; el día que exista
            // receta por producto se puede refinar sin tocar nada más, porque
            // el costo ya queda congelado en la venta.
            $unidadesTotales = array_sum($productos);
            $costoPorUnidad  = $unidadesTotales > 0 ? $costoCorrida / $unidadesTotales : 0.0;

            foreach ($productos as $id => $cantidad) {
                $produccion->productos()->attach($id, ['cantidad' => $cantidad]);

                // Se lee el producto dentro de la transacción para promediar
                // contra el stock real de este instante, no contra uno leído
                // antes de validar.
                $producto    = Producto::whereKey($id)->first();
                $stockPrevio = (int) $producto->stock;
                $costoPrevio = (float) $producto->costo_unitario;

                // Promedio ponderado: el pan de ayer y el de hoy se mezclan en
                // el mostrador y no hay forma de saber cuál se vende primero,
                // así que el costo también se mezcla.
                //
                //   nuevo = (stock_previo × costo_previo + unidades × costo_corrida)
                //           / (stock_previo + unidades)
                //
                // Con stock previo en 0 —o en negativo por un descuadre— no hay
                // nada que promediar: manda el costo de esta corrida. Promediar
                // contra un stock negativo daría un costo sin sentido, incluso
                // negativo.
                $nuevoCosto = $stockPrevio > 0
                    ? ($stockPrevio * $costoPrevio + $cantidad * $costoPorUnidad) / ($stockPrevio + $cantidad)
                    : $costoPorUnidad;

                // Un solo save() para stock y costo: si se hiciera increment()
                // por un lado y update() por el otro, un fallo entre medio
                // dejaría el stock nuevo con el costo viejo.
                $producto->forceFill([
                    'stock'          => $stockPrevio + (int) $cantidad,
                    'costo_unitario' => round($nuevoCosto, 4),
                ])->save();
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
     *
     * EL COSTO DEL PRODUCTO NO SE REVIERTE, a propósito. Deshacer el promedio
     * ponderado hacia atrás es incorrecto: entre esta corrida y la anulación
     * pudo haber otras corridas que ya promediaron sobre este costo, y sobre
     * todo pudo haber VENTAS que congelaron este costo en su detalle. Volver
     * atrás el costo del producto no cambiaría esas ventas —está bien que no
     * las cambie— pero dejaría el producto con un costo que no corresponde ni
     * al de antes ni al de ahora.
     *
     * Lo que queda es un costo levemente contaminado por una corrida que no
     * existió. Se corrige solo: la próxima producción vuelve a promediar y el
     * efecto se diluye. Y como el stock producido no se vendió (si se hubiera
     * vendido, la anulación estaría bloqueada), la corrida anulada no llegó a
     * ensuciar ningún costo de ventas.
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
