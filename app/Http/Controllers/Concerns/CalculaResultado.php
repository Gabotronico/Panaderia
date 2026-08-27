<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CorteCaja;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Las piezas del resultado que Finanzas y el dashboard TIENEN que calcular
 * igual.
 *
 * Los dos tableros arman la utilidad por su cuenta y ya se desincronizaron una
 * vez: cada uno tenía su propia consulta y mostraban números distintos para el
 * mismo mes. Todo lo que ambos necesitan vive acá, en un solo lugar, para que
 * no puedan volver a divergir sin que alguien lo haga a propósito.
 */
trait CalculaResultado
{
    /**
     * Costo de lo vendido en el período: cantidad × costo congelado al vender.
     *
     * Se lee el costo del DETALLE, nunca el actual del producto. El producto
     * recalcula su costo promedio en cada corrida de producción; si el estado
     * de resultados leyera de ahí, una corrida cara de noviembre cambiaría el
     * margen de agosto y ningún mes quedaría nunca cerrado.
     *
     * Las ventas directas no aportan nada acá: no tienen detalle. Ver
     * ventasSinCostoConocido().
     */
    protected function costoDeVentas(Carbon $desdeHora, Carbon $hastaHora): float
    {
        return round((float) DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.created_at', [$desdeHora, $hastaHora])
            ->sum(DB::raw('detalle_ventas.cantidad * detalle_ventas.costo_unitario')), 2);
    }

    /**
     * Cuánta venta del período entró al resultado SIN costo que la acompañe.
     *
     * Son dos casos distintos y los dos inflan el margen de la misma forma:
     *
     * 1. Ventas directas: se registran por monto total, sin decir qué se
     *    vendió. No hay producto del cual copiar un costo y no se inventa uno.
     * 2. Detalles con costo 0: ventas anteriores a que existiera la columna, o
     *    productos que todavía no pasaron por ninguna corrida de producción y
     *    por eso siguen con costo 0.
     *
     * Sin este dato el margen bruto puede verse falsamente enorme y nadie se
     * entera. Se devuelve para mostrarlo al lado del margen, no para restarlo:
     * restar un costo estimado sería peor que no tenerlo.
     */
    protected function ventasSinCostoConocido(Carbon $desdeHora, Carbon $hastaHora): float
    {
        $directas = (float) Venta::where('estado', 'completada')
            ->where('es_directa', true)
            ->whereBetween('created_at', [$desdeHora, $hastaHora])
            ->sum('total');

        // Se suma el subtotal de la línea, no el total de la venta: en una
        // venta mixta —parte con costo, parte sin— solo la parte sin costo
        // está sin respaldo. El descuento de cabecera queda fuera del reparto;
        // es una aproximación consciente y por exceso de prudencia, porque
        // sobreestima levemente lo no cubierto en vez de esconderlo.
        $sinCosto = (float) DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.created_at', [$desdeHora, $hastaHora])
            ->where('detalle_ventas.costo_unitario', '<=', 0)
            ->sum('detalle_ventas.subtotal');

        return round($directas + $sinCosto, 2);
    }

    /**
     * Descuadre de caja del período: sobrante (+) o faltante (−).
     *
     * `diferencia` sale de total_efectivo − (monto_inicial + ventas_efectivo) y
     * `diferencia_qr` de total_qr − ventas_qr, así que ya vienen con el signo
     * correcto: negativo es plata que falta y resta del resultado, positivo es
     * plata de más y suma. No hace falta invertir nada.
     *
     * SE INCLUYE diferencia_qr. Es cierto que el QR no entra en el arqueo de
     * EFECTIVO —esa distinción existe para que el cajero no tenga que tener en
     * el cajón plata que nunca pasó por ahí— pero eso es una regla del arqueo,
     * no del resultado. Si lo verificado por QR no coincide con lo registrado
     * en ventas, hay plata que no cuadra igual que si faltara del cajón, y para
     * el estado de resultados un descuadre es un descuadre venga por donde
     * venga. Dejarlo afuera escondería justamente los errores de cobro por QR.
     *
     * Solo cuentan los cortes CERRADOS: uno abierto todavía no fue arqueado y
     * su `diferencia` es 0 por defecto, no un cero medido.
     *
     * Los límites van como 'Y-m-d': fecha_corte guarda el día sin hora y
     * comparar contra un Carbon completo deja fuera el último día del rango.
     */
    protected function diferenciasDeCaja(string $desde, string $hasta): float
    {
        return round((float) CorteCaja::where('estado', 'cerrado')
            ->whereBetween('fecha_corte', [$desde, $hasta])
            ->sum(DB::raw('diferencia + diferencia_qr')), 2);
    }
}
