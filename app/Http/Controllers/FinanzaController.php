<?php

namespace App\Http\Controllers;

use App\Models\CompraInsumo;
use App\Models\GastoPago;
use App\Models\MermaInsumo;
use App\Models\Planilla;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanzaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $year   = (int) $request->input('year', now()->year);
        $month  = (int) $request->input('month', now()->month);

        $inicio = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $actual   = $this->calcularPeriodo($inicio, $fin);
        $previo   = $inicio->copy()->subMonthNoOverflow();
        $anterior = $this->calcularPeriodo($previo->copy()->startOfMonth(), $previo->copy()->endOfMonth());

        // Tendencia de los últimos 12 meses
        $tendencia = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $inicio->copy()->subMonthsNoOverflow($i);
            $p = $this->calcularPeriodo($m->copy()->startOfMonth(), $m->copy()->endOfMonth());
            $tendencia[] = [
                'etiqueta' => $m->locale('es')->isoFormat('MMM'),
                'anio'     => $m->year,
                'mes'      => $m->month,
                'ingresos' => $p['ingresos'],
                'egresos'  => $p['egresos_total'],
                'utilidad' => $p['utilidad_neta'],
            ];
        }

        $periodo = $inicio->format('Y-m');

        // Desglose de gastos fijos del mes por categoría
        $gastosPorCategoria = GastoPago::query()
            ->join('gastos_fijos', 'gastos_pagos.gasto_fijo_id', '=', 'gastos_fijos.id')
            ->where('gastos_pagos.estado', 'pagado')
            ->where('gastos_pagos.periodo', $periodo)
            ->groupBy('gastos_fijos.categoria')
            ->select('gastos_fijos.categoria', DB::raw('SUM(gastos_pagos.monto_real) as total'))
            ->orderByDesc('total')
            ->get();

        // Compromisos del período que aún no se han pagado
        $sinPagar = GastoPago::whereIn('estado', ['pendiente', 'vencido'])
            ->where('periodo', $periodo)
            ->get();

        $pendientes = [
            'gastos'     => (float) $sinPagar->sum('monto_estimado'),
            'cantidad'   => $sinPagar->count(),
            'vencidos'   => $sinPagar->where('estado', 'vencido')->count(),
            // Mismo criterio que el gasto: la planilla pertenece al mes en que
            // arranca su período.
            'borradores' => Planilla::where('estado', 'borrador')
                                ->whereBetween('periodo_inicio', [$inicio->toDateString(), $fin->toDateString()])
                                ->count(),
        ];

        return view('finanzas.index', compact(
            'actual', 'anterior', 'tendencia', 'gastosPorCategoria', 'pendientes',
            'year', 'month', 'inicio', 'fin'
        ));
    }

    /**
     * Consolida ingresos y egresos de todos los módulos para un rango de fechas.
     */
    private function calcularPeriodo(Carbon $inicio, Carbon $fin): array
    {
        // ── INGRESOS ────────────────────────────────────────────────
        $ingresos = (float) Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->sum('total');

        $numVentas = Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->count();

        // ── COSTOS DIRECTOS ─────────────────────────────────────────
        // Las columnas de fecha guardan el día sin hora, así que los límites
        // van como 'Y-m-d'. Si se pasara el Carbon completo, el motor compara
        // '2026-08-01' contra '2026-08-01 00:00:00' como texto y deja fuera
        // justamente los registros del último día del rango.
        $desde = $inicio->toDateString();
        $hasta = $fin->toDateString();

        $compras = (float) CompraInsumo::whereBetween('fecha', [$desde, $hasta])->sum('total');

        // La merma guarda cantidad, no monto: se valoriza al costo del insumo
        $mermas = (float) MermaInsumo::query()
            ->join('insumos', 'mermas_insumos.insumo_id', '=', 'insumos.id')
            ->whereBetween('mermas_insumos.fecha', [$desde, $hasta])
            ->sum(DB::raw('mermas_insumos.cantidad * insumos.costo_unitario'));

        // La merma NO se suma al costo: el insumo perdido ya se pagó cuando se
        // compró, y las compras se cuentan enteras acá arriba. Sumarla otra vez
        // cobraría dos veces la misma plata e inflaría la pérdida. Se mantiene
        // calculada porque sirve como indicador de cuánto se está perdiendo por
        // vencimiento o mal manejo, pero se muestra aparte del resultado.
        $costosDirectos = $compras;

        // ── GASTOS OPERATIVOS ───────────────────────────────────────
        // La planilla se imputa al mes en que ARRANCA su período: una semana
        // que va del 27/07 al 01/08 se trabajó casi toda en julio, así que
        // cargarla a agosto por terminar ahí distorsionaba los dos meses.
        // Y solo pesa como gasto cuando ya se pagó: mientras está en borrador
        // o cerrada es un cálculo, no plata que salió de la caja.
        $planillas = (float) Planilla::where('estado', 'pagada')
            ->whereBetween('periodo_inicio', [$desde, $hasta])
            ->sum('total_general');

        // Se imputa al período al que corresponde el gasto, no a la fecha en que
        // se pagó: la luz de enero es costo de enero aunque se pague en marzo.
        $gastosFijos = (float) GastoPago::where('estado', 'pagado')
            ->where('periodo', $inicio->format('Y-m'))
            ->sum('monto_real');

        $gastosOperativos = $planillas + $gastosFijos;

        // ── RESULTADOS ──────────────────────────────────────────────
        $utilidadBruta = $ingresos - $costosDirectos;
        $utilidadNeta  = $utilidadBruta - $gastosOperativos;
        $egresosTotal  = $costosDirectos + $gastosOperativos;

        return [
            'ingresos'          => round($ingresos, 2),
            'num_ventas'        => $numVentas,
            'ticket_promedio'   => $numVentas > 0 ? round($ingresos / $numVentas, 2) : 0.0,
            'compras'           => round($compras, 2),
            'mermas'            => round($mermas, 2),
            'costos_directos'   => round($costosDirectos, 2),
            'planillas'         => round($planillas, 2),
            'gastos_fijos'      => round($gastosFijos, 2),
            'gastos_operativos' => round($gastosOperativos, 2),
            'egresos_total'     => round($egresosTotal, 2),
            'utilidad_bruta'    => round($utilidadBruta, 2),
            'utilidad_neta'     => round($utilidadNeta, 2),
            'margen_bruto'      => $ingresos > 0 ? round(($utilidadBruta / $ingresos) * 100, 1) : 0.0,
            'margen_neto'       => $ingresos > 0 ? round(($utilidadNeta / $ingresos) * 100, 1) : 0.0,
        ];
    }
}
