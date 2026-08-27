<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CalculaResultado;
use App\Models\CompraInsumo;
use App\Models\GastoPago;
use App\Models\GastoVariable;
use App\Models\MermaInsumo;
use App\Models\Planilla;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanzaController extends Controller
{
    // Costo de ventas, venta sin costo conocido y diferencias de caja se
    // calculan igual acá y en el dashboard. Ver el trait.
    use CalculaResultado;

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
     * Estado de resultados del mes en PDF.
     *
     * El tablero de Finanzas se mira en pantalla; esto es lo que se le entrega
     * al contador o al socio, así que sale como documento con el mismo formato
     * que la planilla: A4, membrete y firmas.
     */
    public function estadoResultados(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $inicio = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $previo    = $inicio->copy()->subMonthNoOverflow();
        $resultado = $this->calcularPeriodo($inicio, $fin);
        $anterior  = $this->calcularPeriodo($previo->copy()->startOfMonth(), $previo->copy()->endOfMonth());

        // La vista nombra al porcentaje 'pct_sin_costo'. El cálculo lo devuelve
        // como 'venta_sin_costo_pct', que es el nombre que ya usan el tablero y
        // el dashboard: se traduce acá en lugar de renombrarlo en tres lugares.
        $r = $resultado + ['pct_sin_costo' => $resultado['venta_sin_costo_pct']];

        // Se pasan todas las líneas comparables, no solo las de resumen: la
        // vista arma la columna de variación por clave, así que cada renglón
        // que exista acá gana su comparación con el mes anterior.
        $comparativo = array_intersect_key($anterior, array_flip([
            'ingresos', 'costo_ventas', 'gastos_variables', 'utilidad_bruta',
            'planillas', 'gastos_fijos', 'diferencias_caja', 'gastos_operativos',
            'utilidad_neta',
        ]));

        $empresa = [
            'nombre'    => config('app.name', 'Obrador'),
            'subtitulo' => 'Sistema de gestión',
        ];

        $periodo = [
            'etiqueta' => ucfirst($inicio->locale('es')->isoFormat('MMMM [de] YYYY')),
            'desde'    => $inicio,
            'hasta'    => $fin,
        ];

        $pdf = Pdf::loadView('finanzas.estado-resultados', [
            'empresa'     => $empresa,
            'periodo'     => $periodo,
            'r'           => $r,
            'comparativo' => $comparativo,
            'usuario'     => Auth::user()?->name,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("estado-resultados-{$inicio->format('Y-m')}.pdf");
    }

    /**
     * Consolida ingresos y egresos de todos los módulos para un rango de fechas.
     */
    private function calcularPeriodo(Carbon $inicio, Carbon $fin): array
    {
        // ── INGRESOS ────────────────────────────────────────────────
        // Las ventas guardan created_at con hora, así que el rango se abre al
        // inicio del primer día y se cierra al final del último.
        $desdeHora = $inicio->copy()->startOfDay();
        $hastaHora = $fin->copy()->endOfDay();

        $ingresos = (float) Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$desdeHora, $hastaHora])
            ->sum('total');

        $numVentas = Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$desdeHora, $hastaHora])
            ->count();

        // ── COSTOS DIRECTOS ─────────────────────────────────────────
        // Las columnas de fecha guardan el día sin hora, así que los límites
        // van como 'Y-m-d'. Si se pasara el Carbon completo, el motor compara
        // '2026-08-01' contra '2026-08-01 00:00:00' como texto y deja fuera
        // justamente los registros del último día del rango.
        $desde = $inicio->toDateString();
        $hasta = $fin->toDateString();

        // COSTO DE VENTAS: lo que costó producir lo que efectivamente se
        // vendió este mes, con el costo congelado al momento de cada venta.
        // Este es el costo que se enfrenta al ingreso; ver el trait.
        $costoVentas = $this->costoDeVentas($desdeHora, $hastaHora);

        // Cuánta venta entró al resultado sin costo detrás (ventas directas y
        // detalles con costo 0). Sin este dato, el margen puede verse enorme
        // solo porque falta la mitad de la ecuación.
        $ventaSinCosto = $this->ventasSinCostoConocido($desdeHora, $hastaHora);

        // LAS COMPRAS YA NO RESTAN DEL RESULTADO. Comprar insumo no es un
        // gasto: mueve plata de la caja al inventario, y el valor sigue en la
        // panadería hasta que se consume y se vende. Restarlas era lo que
        // hundía el mes en que se compra para varios meses y regalaba los
        // siguientes. Se sigue calculando y devolviendo como DATO INFORMATIVO
        // —para ver el esfuerzo de compra y el flujo de caja— pero fuera del
        // cálculo de la utilidad.
        $compras = (float) CompraInsumo::whereBetween('fecha', [$desde, $hasta])->sum('total');

        // La merma guarda cantidad, no monto: se valoriza al costo del insumo
        $mermas = (float) MermaInsumo::query()
            ->join('insumos', 'mermas_insumos.insumo_id', '=', 'insumos.id')
            ->whereBetween('mermas_insumos.fecha', [$desde, $hasta])
            ->sum(DB::raw('mermas_insumos.cantidad * insumos.costo_unitario'));

        // Gastos ocasionales de operar: transporte, fletes, una reparación
        // puntual. Van acá arriba, junto a las compras, porque pesan sobre la
        // utilidad bruta: son costo de mover el negocio, no gastos de
        // estructura como el alquiler o la luz.
        $gastosVariables = (float) GastoVariable::whereBetween('fecha', [$desde, $hasta])->sum('monto');

        // La merma NO se suma al costo: el insumo perdido ya se pagó cuando se
        // compró y su valor sale del inventario, no del resultado del mes. Se
        // mantiene calculada porque sirve como indicador de cuánto se está
        // perdiendo por vencimiento o mal manejo, pero se muestra aparte.
        $costosDirectos = $costoVentas + $gastosVariables;

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

        // ── DESCUADRE DE CAJA ───────────────────────────────────────
        // El faltante de caja es plata que entró como venta y no aparece: sin
        // esta línea el resultado la contaba como si estuviera. Viene con signo
        // (negativo = faltante, resta; positivo = sobrante, suma) e incluye el
        // descuadre de QR. Ver el trait para el porqué.
        $diferenciasCaja = $this->diferenciasDeCaja($desde, $hasta);

        // ── RESULTADOS ──────────────────────────────────────────────
        $utilidadBruta = $ingresos - $costoVentas - $gastosVariables;
        $utilidadNeta  = $utilidadBruta - $gastosOperativos + $diferenciasCaja;

        // El faltante suma a los egresos y el sobrante los baja, de modo que
        // ingresos − egresos_total siga dando exactamente la utilidad neta.
        $egresosTotal = $costosDirectos + $gastosOperativos - $diferenciasCaja;

        return [
            'ingresos'          => round($ingresos, 2),
            'num_ventas'        => $numVentas,
            'ticket_promedio'   => $numVentas > 0 ? round($ingresos / $numVentas, 2) : 0.0,
            'costo_ventas'      => round($costoVentas, 2),
            // Informativo: mueve plata a inventario, no resta del resultado.
            'compras'           => round($compras, 2),
            'gastos_variables'  => round($gastosVariables, 2),
            'mermas'            => round($mermas, 2),
            'costos_directos'   => round($costosDirectos, 2),
            'planillas'         => round($planillas, 2),
            'gastos_fijos'      => round($gastosFijos, 2),
            'gastos_operativos' => round($gastosOperativos, 2),
            'diferencias_caja'  => round($diferenciasCaja, 2),
            'egresos_total'     => round($egresosTotal, 2),
            'utilidad_bruta'    => round($utilidadBruta, 2),
            'utilidad_neta'     => round($utilidadNeta, 2),
            'margen_bruto'      => $ingresos > 0 ? round(($utilidadBruta / $ingresos) * 100, 1) : 0.0,
            'margen_neto'       => $ingresos > 0 ? round(($utilidadNeta / $ingresos) * 100, 1) : 0.0,

            // Señal de confiabilidad del margen: cuánta venta del período entró
            // sin costo detrás. Si esto es alto, el margen bruto está inflado y
            // hay que leerlo como referencia, no como medición.
            'venta_sin_costo'     => round($ventaSinCosto, 2),
            'venta_sin_costo_pct' => $ingresos > 0
                ? round(($ventaSinCosto / $ingresos) * 100, 1)
                : 0.0,
        ];
    }
}
