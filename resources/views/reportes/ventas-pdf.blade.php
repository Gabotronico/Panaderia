<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            color: #666;
            font-size: 16px;
        }
        .info-section {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍞 Panadería Luna</h1>
        <h2>Reporte de Ventas</h2>
        <p>Período: {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}</p>
    </div>

    <div class="info-section">
        <p><strong>Total de ventas:</strong> {{ $cantidadVentas }}</p>
        <p><strong>Monto total:</strong> Bs{{ number_format($totalVentas, 2) }}</p>
        <p><strong>Descuentos:</strong> Bs{{ number_format($totalDescuentos, 2) }}</p>
        <p><strong>Promedio por venta:</strong> Bs{{ $cantidadVentas > 0 ? number_format($totalVentas / $cantidadVentas, 2) : '0.00' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Cajero</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Descuento</th>
                <th class="text-right">Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
                <tr>
                    <td>{{ $venta->numero_venta }}</td>
                    <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $venta->user->name }}</td>
                    <td class="text-right">Bs {{ number_format($venta->subtotal, 2) }}</td>
                    <td class="text-right">
                        @if($venta->descuento > 0)
                            -Bs{{ number_format($venta->descuento, 2) }}
                        @else
                            Bs0.00
                        @endif
                    </td>
                    <td class="text-right">Bs{{ number_format($venta->total, 2) }}</td>
                    <td>
                        @if($venta->estado == 'completada')
                            <span class="badge badge-success">Completada</span>
                        @else
                            <span class="badge badge-danger">Cancelada</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="3" class="text-right">TOTALES:</td>
                <td class="text-right">Bs{{ number_format($ventas->sum('subtotal'), 2) }}</td>
                <td class="text-right">-Bs{{ number_format($ventas->sum('descuento'), 2) }}</td>
                <td class="text-right">Bs{{ number_format($ventas->sum('total'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Panadería Luna - Sistema de Gestión</p>
        <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>