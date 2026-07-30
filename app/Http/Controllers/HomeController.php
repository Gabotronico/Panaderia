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

    public function index()
    {
        // Si es cajero, mostrar solo su información
        $user = Auth::user();
        $isCajero = $user->hasRole('Cajero');

        // Ventas de hoy
        if ($isCajero) {
            $ventasHoy = Venta::where('user_id', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->where('estado', 'completada')
                ->sum('total');

            $ventasMes = Venta::where('user_id', $user->id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->where('estado', 'completada')
                ->sum('total');
            
            $ventasSemana = Venta::where('user_id', $user->id)
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->where('estado', 'completada')
                ->sum('total');
        } else {
            $ventasHoy = Venta::whereDate('created_at', Carbon::today())
                ->where('estado', 'completada')
                ->sum('total');

            $ventasMes = Venta::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->where('estado', 'completada')
                ->sum('total');
            
            $ventasSemana = Venta::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->where('estado', 'completada')
                ->sum('total');
        }

        // Desglose por medio de pago. El efectivo es lo que entra al cajón y
        // se arquea en el cierre; el QR va directo a la cuenta. Verlos juntos
        // en el dashboard evita tener que abrir cada corte para saber cuánto
        // de la venta del día es dinero físico.
        $porPagoHoy = $this->ventasPorTipoPago(
            fn ($q) => $q->whereDate('created_at', Carbon::today()),
            $isCajero ? $user->id : null
        );

        $porPagoMes = $this->ventasPorTipoPago(
            fn ($q) => $q->whereMonth('created_at', Carbon::now()->month)
                         ->whereYear('created_at', Carbon::now()->year),
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

        // Últimas ventas
        if ($isCajero) {
            $ultimasVentas = Venta::with('user')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } else {
            $ultimasVentas = Venta::with('user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        // Productos más vendidos (últimos 30 días)
        if ($isCajero) {
            $productosMasVendidos = DB::table('productos')
                ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.user_id', $user->id)
                ->where('ventas.estado', 'completada')
                ->where('ventas.created_at', '>=', Carbon::now()->subDays(30))
                ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
                ->groupBy('productos.id', 'productos.nombre')
                ->orderByDesc('total_vendido')
                ->take(5)
                ->get();
        } else {
            $productosMasVendidos = DB::table('productos')
                ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'completada')
                ->where('ventas.created_at', '>=', Carbon::now()->subDays(30))
                ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
                ->groupBy('productos.id', 'productos.nombre')
                ->orderByDesc('total_vendido')
                ->take(5)
                ->get();
        }

        // Datos para gráfico de ventas (últimos 7 días)
        $ventasUltimos7Dias = [];
        $fechasUltimos7Dias = [];

        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $fechasUltimos7Dias[] = $fecha->format('d/m');

            if ($isCajero) {
                $ventasUltimos7Dias[] = Venta::where('user_id', $user->id)
                    ->whereDate('created_at', $fecha)
                    ->where('estado', 'completada')
                    ->sum('total');
            } else {
                $ventasUltimos7Dias[] = Venta::whereDate('created_at', $fecha)
                    ->where('estado', 'completada')
                    ->sum('total');
            }
        }

        // Gasto en compras de insumos — últimos 30 días (solo admin)
        $gastosCompras     = [];
        $fechasCompras     = [];
        $totalGastoMes     = 0;
        $ultimasCompras    = collect();

        if (!$isCajero) {
            $inicio = Carbon::today()->subDays(29);

            $comprasPorDia = DB::table('compras_insumo')
                ->selectRaw('DATE(fecha) as dia, SUM(total) as total_dia')
                ->where('fecha', '>=', $inicio)
                ->groupBy('dia')
                ->orderBy('dia')
                ->pluck('total_dia', 'dia');

            for ($i = 29; $i >= 0; $i--) {
                $fecha           = Carbon::today()->subDays($i);
                $fechasCompras[] = $fecha->format('d/m');
                $gastosCompras[] = (float) ($comprasPorDia[$fecha->toDateString()] ?? 0);
            }

            $totalGastoMes = DB::table('compras_insumo')
                ->whereMonth('fecha', Carbon::now()->month)
                ->whereYear('fecha', Carbon::now()->year)
                ->sum('total');

            $ultimasCompras = CompraInsumo::with('insumo', 'user')
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->take(5)
                ->get();
        }

        // ── Panel de operación diaria (solo administrador) ──────────────
        $operacion = null;

        if ($user->esAdministrador()) {
            $hoy     = Carbon::today();
            $periodo = $hoy->format('Y-m');

            $empleadosActivos = \App\Models\Empleado::activos()->count();
            $asistenciasHoy   = \App\Models\Asistencia::whereDate('fecha', $hoy)->get();

            $gastosDelMes = \App\Models\GastoPago::where('periodo', $periodo)->get();

            // Utilidad del mes en curso: ventas − insumos − sueldos − gastos pagados
            $planillasMes = (float) \App\Models\Planilla::whereBetween('periodo_fin', [
                    $hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth(),
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

                'utilidad_mes'       => round(
                    $ventasMes - $totalGastoMes - $planillasMes
                    - (float) $gastosDelMes->where('estado', 'pagado')->sum('monto_real'),
                    2
                ),
            ];
        }

        return view('home', compact(
            'ventasHoy',
            'ventasMes',
            'ventasSemana',
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
            'porPagoMes'
        ));
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