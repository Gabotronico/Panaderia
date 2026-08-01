<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Insumo;
use App\Models\Venta;
use App\Models\CorteCaja;
use App\Models\CompraInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-dashboard');
    }

    public function index(Request $request)
    {
        // Si es cajero, mostrar solo su información
        $user = Auth::user();
        $isCajero = $user->hasRole('Cajero');

        $periodo = $this->resolverPeriodo($request);
        $inicio  = $periodo['inicio'];
        $fin     = $periodo['fin'];

        // Las ventas guardan created_at (fecha y hora), así que el rango se
        // abre al inicio del primer día y se cierra al final del último.
        $desdeHora = $inicio->copy()->startOfDay();
        $hastaHora = $fin->copy()->endOfDay();

        $ventasDelPeriodo = fn () => Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$desdeHora, $hastaHora])
            ->when($isCajero, fn ($q) => $q->where('user_id', $user->id));

        $ventasPeriodo   = (float) $ventasDelPeriodo()->sum('total');
        $cantidadVentas  = $ventasDelPeriodo()->count();
        $ticketPromedio  = $cantidadVentas > 0 ? round($ventasPeriodo / $cantidadVentas, 2) : 0.0;

        // Hoy se sigue mostrando aparte: es el dato que se mira de reojo
        // durante la jornada, sin importar qué período esté filtrado.
        $ventasHoy = (float) Venta::where('estado', 'completada')
            ->whereDate('created_at', Carbon::today())
            ->when($isCajero, fn ($q) => $q->where('user_id', $user->id))
            ->sum('total');

        // Desglose por medio de pago. El efectivo es lo que entra al cajón y
        // se arquea en el cierre; el QR va directo a la cuenta. Verlos juntos
        // en el dashboard evita tener que abrir cada corte para saber cuánto
        // de la venta es dinero físico.
        $porPagoPeriodo = $this->ventasPorTipoPago(
            fn ($q) => $q->whereBetween('created_at', [$desdeHora, $hastaHora]),
            $isCajero ? $user->id : null
        );

        $porPagoHoy = $this->ventasPorTipoPago(
            fn ($q) => $q->whereDate('created_at', Carbon::today()),
            $isCajero ? $user->id : null
        );

        // Productos con stock bajo
        $productosStockBajo = Producto::whereColumn('stock', '<=', 'stock_minimo')
            ->where('activo', true)
            ->count();

        // Insumos con stock bajo
        $insumosStockBajo = Insumo::whereColumn('cantidad_stock', '<=', 'stock_minimo')
            ->where('activo', true)
            ->count();

        // Últimas ventas del período elegido
        $ultimasVentas = $ventasDelPeriodo()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Productos más vendidos del período
        $productosMasVendidos = DB::table('productos')
            ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.created_at', [$desdeHora, $hastaHora])
            ->when($isCajero, fn ($q) => $q->where('ventas.user_id', $user->id))
            ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->take(5)
            ->get();

        // Serie de ventas del período. En rangos largos se agrupa por mes: una
        // barra por día en un año entero no se lee.
        $serieVentas        = $this->serieDeVentas($inicio, $fin, $periodo['agrupacion'], $isCajero ? $user->id : null);
        $fechasUltimos7Dias = $serieVentas['etiquetas'];
        $ventasUltimos7Dias = $serieVentas['valores'];

        // Gasto en compras de insumos dentro del período (solo admin)
        $gastosCompras     = [];
        $fechasCompras     = [];
        $totalGastoMes     = 0;
        $ultimasCompras    = collect();

        if (!$isCajero) {
            // Los límites van como 'Y-m-d': la columna guarda el día sin hora y
            // comparar contra un Carbon completo dejaría fuera las compras
            // hechas justo en los bordes del rango.
            $serieCompras  = $this->serieDeCompras($inicio, $fin, $periodo['agrupacion']);
            $fechasCompras = $serieCompras['etiquetas'];
            $gastosCompras = $serieCompras['valores'];

            $totalGastoMes = (float) DB::table('compras_insumo')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('total');

            $ultimasCompras = CompraInsumo::with('insumo', 'user')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->take(5)
                ->get();
        }

        // ── Panel de operación diaria (solo administrador) ──────────────
        $operacion = null;

        if ($user->esAdministrador()) {
            // Este panel siempre habla del día de hoy y del mes en curso: es
            // operativo, no analítico, así que no sigue al filtro de arriba.
            $hoy        = Carbon::today();
            $periodoMes = $hoy->format('Y-m');

            $empleadosActivos = \App\Models\Empleado::activos()->count();
            $asistenciasHoy   = \App\Models\Asistencia::whereDate('fecha', $hoy)->get();

            $gastosDelMes = \App\Models\GastoPago::where('periodo', $periodoMes)->get();

            // Utilidad del mes en curso: ventas − insumos − sueldos − gastos pagados.
            // Mismo criterio que Finanzas: solo las planillas pagadas cuentan
            // como salida de plata, y se imputan al mes en que arranca su
            // período. Los límites van como 'Y-m-d' porque la columna guarda
            // el día sin hora.
            $planillasMes = (float) \App\Models\Planilla::where('estado', 'pagada')
                ->whereBetween('periodo_inicio', [
                    $hoy->copy()->startOfMonth()->toDateString(),
                    $hoy->copy()->endOfMonth()->toDateString(),
                ])->sum('total_general');

            $operacion = [
                'empleados_activos'  => $empleadosActivos,
                'asistencia_tomada'  => $asistenciasHoy->isNotEmpty(),
                'presentes_hoy'      => $asistenciasHoy->whereIn('estado', ['presente', 'tardanza'])->count(),
                'ausentes_hoy'       => $asistenciasHoy->where('estado', 'ausente')->count(),
                'es_domingo'         => $hoy->dayOfWeek === Carbon::SUNDAY,

                'gastos_vencidos'    => $gastosDelMes->where('estado', 'vencido')->count(),
                'gastos_pendientes'  => $gastosDelMes->whereIn('estado', ['pendiente', 'vencido'])->count(),
                'monto_por_pagar'    => (float) $gastosDelMes->whereIn('estado', ['pendiente', 'vencido'])->sum('monto_estimado'),
                'gastos_pagados'     => (float) $gastosDelMes->where('estado', 'pagado')->sum('monto_real'),

                'planillas_borrador' => \App\Models\Planilla::where('estado', 'borrador')->count(),
                'adelantos_pend'     => (float) \App\Models\Adelanto::whereNull('planilla_id')->sum('monto'),

                // La utilidad se calcula sobre el período filtrado, para que
                // acompañe a lo que muestran las tarjetas de arriba.
                'utilidad_mes'       => round(
                    $ventasPeriodo - $totalGastoMes - $planillasMes
                    - (float) $gastosDelMes->where('estado', 'pagado')->sum('monto_real'),
                    2
                ),
            ];
        }

        return view('home', compact(
            'periodo',
            'ventasPeriodo',
            'cantidadVentas',
            'ticketPromedio',
            'ventasHoy',
            'productosStockBajo',
            'insumosStockBajo',
            'ultimasVentas',
            'productosMasVendidos',
            'ventasUltimos7Dias',
            'fechasUltimos7Dias',
            'gastosCompras',
            'fechasCompras',
            'totalGastoMes',
            'ultimasCompras',
            'isCajero',
            'operacion',
            'porPagoHoy',
            'porPagoPeriodo'
        ));
    }

    /**
     * Interpreta el filtro de la barra superior.
     *
     * Acepta dos formas: un mes concreto (mes + anio) o un rango libre
     * (desde + hasta). Si no viene nada, o si lo que viene no se entiende, se
     * cae al mes en curso en lugar de romper la pantalla.
     *
     * @return array{inicio: Carbon, fin: Carbon, modo: string, etiqueta: string, agrupacion: string, mes: int, anio: int}
     */
    private function resolverPeriodo(Request $request): array
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        if ($desde && $hasta) {
            try {
                $inicio = Carbon::parse($desde)->startOfDay();
                $fin    = Carbon::parse($hasta)->startOfDay();

                // Si las invirtieron, se ordenan solas en vez de devolver vacío.
                if ($fin->lt($inicio)) {
                    [$inicio, $fin] = [$fin, $inicio];
                }

                return [
                    'inicio'     => $inicio,
                    'fin'        => $fin,
                    'modo'       => 'rango',
                    'etiqueta'   => $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y'),
                    'agrupacion' => $this->agrupacionSegunLargo($inicio, $fin),
                    'mes'        => $inicio->month,
                    'anio'       => $inicio->year,
                ];
            } catch (\Throwable) {
                // Fecha ilegible: sigue al mes en curso.
            }
        }

        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }
        if ($anio < 2000 || $anio > 2999) {
            $anio = now()->year;
        }

        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth()->startOfDay();

        return [
            'inicio'     => $inicio,
            'fin'        => $fin,
            'modo'       => 'mes',
            'etiqueta'   => ucfirst($inicio->locale('es')->isoFormat('MMMM [de] YYYY')),
            'agrupacion' => 'dia',
            'mes'        => $mes,
            'anio'       => $anio,
        ];
    }

    /** Un punto por día es ilegible más allá de un par de meses. */
    private function agrupacionSegunLargo(Carbon $inicio, Carbon $fin): string
    {
        return $inicio->diffInDays($fin) > 62 ? 'mes' : 'dia';
    }

    /**
     * Serie de ventas del período, lista para el gráfico.
     *
     * Se trae todo agrupado en una sola consulta y después se rellenan los
     * huecos: así los días sin ventas aparecen en cero en vez de desaparecer,
     * que es lo que hace que una línea de tiempo se lea mal.
     *
     * @return array{etiquetas: array<string>, valores: array<float>}
     */
    private function serieDeVentas(Carbon $inicio, Carbon $fin, string $agrupacion, ?int $userId): array
    {
        $tramo = $this->expresionDeTramo('created_at', $agrupacion);

        $totales = Venta::where('estado', 'completada')
            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->selectRaw("{$tramo} as tramo, SUM(total) as total")
            ->groupBy('tramo')
            ->pluck('total', 'tramo');

        return $this->rellenarSerie($inicio, $fin, $agrupacion, $totales);
    }

    /**
     * Expresión SQL que recorta una fecha al día o al mes, según el motor.
     *
     * strftime() es de SQLite y DATE_FORMAT() de MySQL: usar una sola rompía
     * la pantalla al cambiar de base, como ya pasó con DAYOFWEEK().
     */
    private function expresionDeTramo(string $columna, string $agrupacion): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => $agrupacion === 'mes'
                ? "strftime('%Y-%m', {$columna})"
                : "strftime('%Y-%m-%d', {$columna})",
            'pgsql'  => $agrupacion === 'mes'
                ? "to_char({$columna}, 'YYYY-MM')"
                : "to_char({$columna}, 'YYYY-MM-DD')",
            default  => $agrupacion === 'mes'
                ? "DATE_FORMAT({$columna}, '%Y-%m')"
                : "DATE_FORMAT({$columna}, '%Y-%m-%d')",
        };
    }

    /**
     * Serie de compras de insumos del período.
     *
     * @return array{etiquetas: array<string>, valores: array<float>}
     */
    private function serieDeCompras(Carbon $inicio, Carbon $fin, string $agrupacion): array
    {
        $tramo = $this->expresionDeTramo('fecha', $agrupacion);

        $totales = DB::table('compras_insumo')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw("{$tramo} as tramo, SUM(total) as total")
            ->groupBy('tramo')
            ->pluck('total', 'tramo');

        return $this->rellenarSerie($inicio, $fin, $agrupacion, $totales);
    }

    /**
     * Completa los tramos sin movimiento con cero y arma las etiquetas.
     *
     * @param  \Illuminate\Support\Collection<string, mixed>  $totales
     * @return array{etiquetas: array<string>, valores: array<float>}
     */
    private function rellenarSerie(Carbon $inicio, Carbon $fin, string $agrupacion, $totales): array
    {
        $etiquetas = [];
        $valores   = [];
        $cursor    = $inicio->copy();

        if ($agrupacion === 'mes') {
            $cursor = $cursor->startOfMonth();

            while ($cursor->lte($fin)) {
                $etiquetas[] = ucfirst($cursor->locale('es')->isoFormat('MMM YY'));
                $valores[]   = (float) ($totales[$cursor->format('Y-m')] ?? 0);
                $cursor->addMonth();
            }

            return ['etiquetas' => $etiquetas, 'valores' => $valores];
        }

        while ($cursor->lte($fin)) {
            $etiquetas[] = $cursor->format('d/m');
            $valores[]   = (float) ($totales[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        return ['etiquetas' => $etiquetas, 'valores' => $valores];
    }

    /**
     * Suma las ventas completadas del periodo separadas por medio de pago.
     *
     * @param  callable  $periodo  Aplica el filtro de fechas sobre la query.
     * @param  int|null  $userId   Limita a un cajero; null suma toda la panadería.
     * @return array{efectivo: float, qr: float, total: float}
     */
    private function ventasPorTipoPago(callable $periodo, ?int $userId = null): array
    {
        $query = Venta::where('estado', 'completada');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $periodo($query);

        $porTipo = $query->selectRaw('tipo_pago, SUM(total) as monto')
            ->groupBy('tipo_pago')
            ->pluck('monto', 'tipo_pago');

        $efectivo = (float) ($porTipo['efectivo'] ?? 0);
        $qr       = (float) ($porTipo['qr'] ?? 0);

        return [
            'efectivo' => $efectivo,
            'qr'       => $qr,
            'total'    => $efectivo + $qr,
        ];
    }
}