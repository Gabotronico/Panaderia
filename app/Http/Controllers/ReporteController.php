<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Insumo;
use App\Models\CorteCaja;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-reportes');
    }

    public function index()
    {
        return view('reportes.index');
    }

    // Reporte de Ventas
    public function ventas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $ventas = Venta::with('user', 'detalles.producto')
            ->whereBetween('created_at', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ])
            ->where('estado', 'completada')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalVentas = $ventas->sum('total');
        $totalDescuentos = $ventas->sum('descuento');
        $cantidadVentas = $ventas->count();

        if ($request->tipo === 'pdf') {
            $pdf = Pdf::loadView('reportes.ventas-pdf', compact(
                'ventas',
                'totalVentas',
                'totalDescuentos',
                'cantidadVentas',
                'request'
            ));
            return $pdf->download('reporte-ventas.pdf');
        }

        return view('reportes.ventas', compact(
            'ventas',
            'totalVentas',
            'totalDescuentos',
            'cantidadVentas',
            'request'
        ));
    }

    // Reporte de Productos Más Vendidos
    public function productosMasVendidos(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $productos = DB::table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.created_at', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ])
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'),
                DB::raw('SUM(detalle_ventas.subtotal) as ingresos')
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderBy('total_vendido', 'desc')
            ->get();

        if ($request->tipo === 'pdf') {
            $pdf = Pdf::loadView('reportes.productos-mas-vendidos-pdf', compact('productos', 'request'));
            return $pdf->download('reporte-productos-mas-vendidos.pdf');
        }

        return view('reportes.productos-mas-vendidos', compact('productos', 'request'));
    }

    // Reporte de Inventario
    public function inventario()
    {
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $insumos = Insumo::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $productosStockBajo = $productos->filter(function ($producto) {
            return $producto->stock <= $producto->stock_minimo;
        });

        $insumosStockBajo = $insumos->filter(function ($insumo) {
            return $insumo->cantidad_stock <= $insumo->stock_minimo;
        });

        return view('reportes.inventario', compact(
            'productos',
            'insumos',
            'productosStockBajo',
            'insumosStockBajo'
        ));
    }

    // Reporte de Cortes de Caja
    public function cortesCaja(Request $request)
    {
       $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
          ]);

    // Obtener cortes en el rango de fechas
    $cortes = CorteCaja::with('user')
        ->whereBetween('fecha_corte', [$request->fecha_inicio, $request->fecha_fin])
        ->orderBy('fecha_corte', 'desc')
        ->get();

    // Calcular totales
    $totalInicial = $cortes->sum('monto_inicial');
    $totalVentas = $cortes->where('estado', 'cerrado')->sum('total_ventas');
    $totalEfectivo = $cortes->where('estado', 'cerrado')->sum('total_efectivo');
    $totalDiferencia = $cortes->where('estado', 'cerrado')->sum('diferencia');

    // Calcular estados de cortes cerrados
    $cortesCuadrados = 0;
    $cortesConSobrante = 0;
    $cortesConFaltante = 0;

    foreach ($cortes->where('estado', 'cerrado') as $corte) {
        // Calcular el sobrante real (lo que quedó después de las ventas)
        $sobranteReal = $corte->monto_final - $corte->total_ventas;
        
        // Comparar el sobrante con el monto inicial
        $diferenciaMontoInicial = $sobranteReal - $corte->monto_inicial;
        
        if (abs($diferenciaMontoInicial) < 0.01) {
            // Cuadra perfecto
            $cortesCuadrados++;
        } elseif ($diferenciaMontoInicial > 0) {
            // Hay sobrante
            $cortesConSobrante++;
        } else {
            // Hay faltante
            $cortesConFaltante++;
        }
    }

    // Si es PDF
    if ($request->tipo === 'pdf') {
        $pdf = PDF::loadView('reportes.cortes-caja-pdf', compact(
            'cortes',
            'request',
            'totalInicial',
            'totalVentas',
            'totalEfectivo',
            'totalDiferencia'
        ));

        return $pdf->download('reporte-cortes-caja-' . date('Y-m-d') . '.pdf');
    }

    // Vista web
    return view('reportes.cortes-caja', compact(
        'cortes',
        'request',
        'totalInicial',
        'totalVentas',
        'totalEfectivo',
        'totalDiferencia',
        'cortesCuadrados',
        'cortesConSobrante',
        'cortesConFaltante'
    ));
    }
}