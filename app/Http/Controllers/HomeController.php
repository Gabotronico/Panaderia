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
            'isCajero'
        ));
    }
}