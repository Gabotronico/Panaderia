<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Más Vendidos</title>
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
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍞 Obrador</h1>
        <h2>Productos Más Vendidos</h2>
        <p>Período: {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Producto</th>
                <th class="text-center">Unidades Vendidas</th>
                <th class="text-right">Ingresos Generados</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $index => $producto)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td class="text-center">{{ $producto->total_vendido }}</td>
                    <td class="text-right">Bs{{ number_format($producto->ingresos, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2" class="text-right">TOTALES:</td>
                <td class="text-center">{{ $productos->sum('total_vendido') }}</td>
                <td class="text-right">Bs{{ number_format($productos->sum('ingresos'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Obrador - Sistema de Gestión</p>
        <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>