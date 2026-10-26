<?php

namespace App\Support\Reportes;

use App\Models\CorteCaja;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Versión en Excel de los reportes del módulo Reportes. Recibe los mismos
 * datos que ya arma ReporteController para la vista y el PDF, así los tres
 * formatos muestran exactamente los mismos números.
 */
final class ReporteExcel
{
    private const MEDIOS = ['efectivo' => 'Efectivo', 'qr' => 'QR'];

    public static function ventas(array $d): StreamedResponse
    {
        $libro = new LibroExcel('Reporte de ventas', self::periodo($d['request']));

        [$hoja, $fila] = $libro->hoja('Resumen');
        $fila = $libro->indicadores($hoja, $fila, 'Totales del período', [
            'Ventas completadas' => [$d['cantidadVentas'], 'entero'],
            'Total vendido' => [$d['totalVentas'], 'moneda'],
            'Subtotal' => [$d['totalSubtotal'], 'moneda'],
            'Descuentos' => [$d['totalDescuentos'], 'moneda'],
            'Ticket promedio' => [$d['ticketPromedio'], 'moneda'],
            'Cobrado en efectivo' => [$d['porPago']['efectivo'], 'moneda'],
            'Cobrado por QR' => [$d['porPago']['qr'], 'moneda'],
            'Mejor día' => [$d['mejorDia'] ? Carbon::parse($d['mejorDia']) : null, 'fecha'],
        ]);
        $fila = $libro->tabla($hoja, $fila, [
            ['titulo' => 'Cajero'], ['titulo' => 'Ventas', 'tipo' => 'entero'],
            ['titulo' => 'Efectivo', 'tipo' => 'moneda'], ['titulo' => 'QR', 'tipo' => 'moneda'], ['titulo' => 'Total', 'tipo' => 'moneda'],
        ], collect($d['porCajero'])->map(fn ($c, $nombre) => [$nombre, $c['cantidad'], $c['efectivo'], $c['qr'], $c['total']]),
            [0 => 'Total', 1 => 'suma', 2 => 'suma', 3 => 'suma', 4 => 'suma'], 'Por cajero');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'Fecha', 'tipo' => 'fecha'], ['titulo' => 'Ventas', 'tipo' => 'entero'], ['titulo' => 'Total', 'tipo' => 'moneda'],
        ], collect($d['porDia'])->map(fn ($dia, $fecha) => [Carbon::parse($fecha), $dia['cantidad'], $dia['total']]),
            [0 => 'Total', 1 => 'suma', 2 => 'suma'], 'Por día');

        [$hoja, $fila] = $libro->hoja('Ventas');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'N° de venta'], ['titulo' => 'Fecha y hora', 'tipo' => 'fechahora'], ['titulo' => 'Cajero'],
            ['titulo' => 'Medio de pago'], ['titulo' => 'Tipo'], ['titulo' => 'Unidades', 'tipo' => 'entero'],
            ['titulo' => 'Subtotal', 'tipo' => 'moneda'], ['titulo' => 'Descuento', 'tipo' => 'moneda'], ['titulo' => 'Total', 'tipo' => 'moneda'],
        ], $d['ventas']->map(fn (Venta $v) => [
            $v->numero_venta, $v->created_at, $v->user->name ?? 'Sin asignar',
            self::MEDIOS[$v->tipo_pago] ?? ucfirst((string) $v->tipo_pago),
            $v->es_directa ? 'Venta directa' : 'Con detalle',
            $v->es_directa ? null : $v->detalles->sum('cantidad'),
            $v->subtotal, $v->descuento, $v->total,
        ]), [0 => 'Total', 5 => 'suma', 6 => 'suma', 7 => 'suma', 8 => 'suma'], filtros: true);

        [$hoja, $fila] = $libro->hoja('Detalle de productos');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'N° de venta'], ['titulo' => 'Fecha y hora', 'tipo' => 'fechahora'], ['titulo' => 'Producto'],
            ['titulo' => 'Cantidad', 'tipo' => 'entero'], ['titulo' => 'Precio unitario', 'tipo' => 'moneda'], ['titulo' => 'Subtotal', 'tipo' => 'moneda'],
        ], $d['ventas']->flatMap(fn (Venta $v) => $v->detalles->map(fn ($det) => [
            $v->numero_venta, $v->created_at, $det->producto->nombre ?? 'Producto eliminado',
            $det->cantidad, $det->precio_unitario, $det->subtotal,
        ])), [0 => 'Total', 3 => 'suma', 5 => 'suma'], filtros: true);

        return $libro->descargar(self::archivo('ventas', $d['request']));
    }

    public static function productosMasVendidos(Collection $productos, $request): StreamedResponse
    {
        $libro = new LibroExcel('Productos más vendidos', self::periodo($request));
        $ingresos = (float) $productos->sum('ingresos');

        [$hoja, $fila] = $libro->hoja('Ranking');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'Puesto', 'tipo' => 'entero'], ['titulo' => 'Producto'], ['titulo' => 'Unidades vendidas', 'tipo' => 'entero'],
            ['titulo' => 'Ingresos', 'tipo' => 'moneda'], ['titulo' => '% de los ingresos', 'tipo' => 'porcentaje'],
        ], $productos->values()->map(fn ($p, $i) => [
            $i + 1, $p->nombre, $p->total_vendido, $p->ingresos, $ingresos > 0 ? $p->ingresos / $ingresos : 0,
        ]), [1 => 'Total', 2 => 'suma', 3 => 'suma', 4 => 'suma'], filtros: true);

        return $libro->descargar(self::archivo('productos-mas-vendidos', $request));
    }

    public static function inventario(array $d): StreamedResponse
    {
        $libro = new LibroExcel('Reporte de inventario', 'Existencias al '.now()->format('d/m/Y'));

        [$hoja, $fila] = $libro->hoja('Resumen');
        $libro->indicadores($hoja, $fila, 'Situación del inventario', [
            'Productos activos' => [$d['productos']->count(), 'entero'],
            'Productos con stock bajo' => [$d['productosStockBajo']->count(), 'entero'],
            'Valor de los productos a precio de venta' => [$d['productos']->sum(fn ($p) => $p->stock * $p->precio_venta), 'moneda'],
            'Insumos activos' => [$d['insumos']->count(), 'entero'],
            'Insumos con stock bajo' => [$d['insumosStockBajo']->count(), 'entero'],
            'Valor de los insumos a costo' => [$d['insumos']->sum(fn ($i) => $i->cantidad_stock * $i->costo_unitario), 'moneda'],
        ]);

        [$hoja, $fila] = $libro->hoja('Productos');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'Producto'], ['titulo' => 'Categoría'], ['titulo' => 'Precio de venta', 'tipo' => 'moneda'],
            ['titulo' => 'Stock', 'tipo' => 'entero'], ['titulo' => 'Stock mínimo', 'tipo' => 'entero'],
            ['titulo' => 'Valor en stock', 'tipo' => 'moneda'], ['titulo' => 'Estado'],
        ], $d['productos']->map(fn ($p) => [
            $p->nombre, $p->categoria->nombre ?? '—', $p->precio_venta, $p->stock, $p->stock_minimo,
            $p->stock * $p->precio_venta, $p->stock <= $p->stock_minimo ? 'Stock bajo' : 'Normal',
        ]), [0 => 'Total', 3 => 'suma', 5 => 'suma'], filtros: true);

        [$hoja, $fila] = $libro->hoja('Insumos');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'Insumo'], ['titulo' => 'Unidad'], ['titulo' => 'Stock', 'tipo' => 'decimal'],
            ['titulo' => 'Stock mínimo', 'tipo' => 'decimal'], ['titulo' => 'Costo unitario', 'tipo' => 'moneda'],
            ['titulo' => 'Valor en stock', 'tipo' => 'moneda'], ['titulo' => 'Estado'],
        ], $d['insumos']->map(fn ($i) => [
            $i->nombre, $i->unidad_medida, $i->cantidad_stock, $i->stock_minimo, $i->costo_unitario,
            $i->cantidad_stock * $i->costo_unitario, $i->cantidad_stock <= $i->stock_minimo ? 'Stock bajo' : 'Normal',
        ]), [0 => 'Total', 5 => 'suma'], filtros: true);

        return $libro->descargar('reporte-inventario-'.now()->format('Y-m-d').'.xlsx');
    }

    public static function cortesCaja(array $d): StreamedResponse
    {
        $libro = new LibroExcel('Reporte de caja', self::periodo($d['request']));

        [$hoja, $fila] = $libro->hoja('Resumen');
        $libro->indicadores($hoja, $fila, 'Totales del período', [
            'Cortes de caja' => [$d['cortes']->count(), 'entero'],
            'Cuadrados' => [$d['cortesCuadrados'], 'entero'],
            'Con sobrante' => [$d['cortesConSobrante'], 'entero'],
            'Con faltante' => [$d['cortesConFaltante'], 'entero'],
            'Monto inicial (todos los cortes)' => [$d['totalInicial'], 'moneda'],
            'Ventas de cortes cerrados' => [$d['totalVentas'], 'moneda'],
            'Efectivo contado' => [$d['totalEfectivo'], 'moneda'],
            'QR verificado' => [$d['totalQr'], 'moneda'],
        ]);

        [$hoja, $fila] = $libro->hoja('Cortes');
        $libro->tabla($hoja, $fila, [
            ['titulo' => 'Fecha', 'tipo' => 'fecha'], ['titulo' => 'Cajero'], ['titulo' => 'Apertura'], ['titulo' => 'Cierre'],
            ['titulo' => 'Estado'], ['titulo' => 'Monto inicial', 'tipo' => 'moneda'], ['titulo' => 'Ventas en efectivo', 'tipo' => 'moneda'],
            ['titulo' => 'Ventas por QR', 'tipo' => 'moneda'], ['titulo' => 'Total ventas', 'tipo' => 'moneda'],
            ['titulo' => 'Efectivo esperado', 'tipo' => 'moneda'], ['titulo' => 'Efectivo contado', 'tipo' => 'moneda'],
            ['titulo' => 'Diferencia efectivo', 'tipo' => 'moneda'], ['titulo' => 'QR verificado', 'tipo' => 'moneda'],
            ['titulo' => 'Diferencia QR', 'tipo' => 'moneda'], ['titulo' => 'Resultado'], ['titulo' => 'Cerrado por'], ['titulo' => 'Observaciones'],
        ], $d['cortes']->map(function (CorteCaja $c) {
            $cerrado = $c->estado === 'cerrado';

            return [
                $c->fecha_corte, $c->user->name ?? '—', self::hora($c->hora_apertura), self::hora($c->hora_cierre),
                $cerrado ? 'Cerrado' : 'Abierto', $c->monto_inicial, $c->ventas_efectivo, $c->ventas_qr, $c->total_ventas,
                $c->efectivo_esperado,
                $cerrado ? $c->total_efectivo : null, $cerrado ? $c->diferencia_efectivo : null,
                $cerrado ? $c->total_qr : null, $cerrado ? $c->diferencia_qr_real : null,
                ! $cerrado ? 'Caja abierta' : (abs($c->diferencia_efectivo) < 0.01 ? 'Cuadrado' : ($c->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante')),
                $c->cerradoPor->name ?? null, $c->observaciones,
            ];
        }), [0 => 'Total', 5 => 'suma', 6 => 'suma', 7 => 'suma', 8 => 'suma', 10 => 'suma', 11 => 'suma', 12 => 'suma', 13 => 'suma'], filtros: true);

        return $libro->descargar(self::archivo('caja', $d['request']));
    }

    private static function periodo($request): string
    {
        return 'Del '.Carbon::parse($request->fecha_inicio)->format('d/m/Y').' al '.Carbon::parse($request->fecha_fin)->format('d/m/Y');
    }

    private static function archivo(string $nombre, $request): string
    {
        return "reporte-{$nombre}-{$request->fecha_inicio}-a-{$request->fecha_fin}.xlsx";
    }

    private static function hora(?string $hora): ?string
    {
        return $hora ? substr($hora, 0, 5) : null;
    }
}
