<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cortes de Caja</title>
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
        .info-section p {
            margin: 5px 0;
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
        .text-center {
            text-align: center;
        }
        .totals {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1> Obrador</h1>
        <h2>Reporte de Cortes de Caja</h2>
        <p>Período: {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}</p>
    </div>

    <div class="info-section">
        <p><strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Total de cortes:</strong> {{ $cortes->count() }}</p>
        <p><strong>Monto inicial total:</strong> Bs{{ number_format($totalInicial, 2) }}</p>
        <p><strong>Total de ventas:</strong> Bs{{ number_format($totalVentas, 2) }}</p>
        <p><strong>Diferencia total:</strong> 
            @if($totalDiferencia >= 0)
                <span style="color: green;">+Bs{{ number_format($totalDiferencia, 2) }}</span>
            @else
                <span style="color: red;">Bs{{ number_format($totalDiferencia, 2) }}</span>
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cajero</th>
                <th>Fecha</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th class="text-right">Monto Inicial</th>
                <th class="text-right">Ventas</th>
                <th class="text-right">QR</th>
                <th class="text-right">Monto Final</th>
                <th class="text-right">Diferencia</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cortes as $corte)
                <tr>
                    <td>{{ $corte->id }}</td>
                    <td>{{ $corte->user->name }}</td>
                    <td>{{ $corte->fecha_corte->format('d/m/Y') }}</td>
                    <td>{{ $corte->hora_apertura }}</td>
                    <td>{{ $corte->hora_cierre ?? '-' }}</td>
                    <td class="text-right">Bs{{ number_format($corte->monto_inicial, 2) }}</td>
                    <td class="text-right">Bs{{ number_format($corte->total_ventas, 2) }}</td>
                    <td class="text-right">
                        @if($corte->estado == 'cerrado')
                            Bs{{ number_format($corte->total_qr, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($corte->estado == 'cerrado')
                            Bs {{ number_format($corte->monto_final, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($corte->estado == 'cerrado')
                            @php $dif = $corte->diferencia_efectivo; @endphp
                            @if(abs($dif) < 0.01)
                                <span class="badge badge-success">Bs0.00</span>
                            @elseif($dif > 0)
                                <span class="badge badge-warning">+Bs{{ number_format($dif, 2) }}</span>
                            @else
                                <span class="badge badge-danger">Bs{{ number_format($dif, 2) }}</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($corte->estado == 'abierto')
                            <span class="badge badge-success">Abierto</span>
                        @else
                            <span class="badge badge-info">Cerrado</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="5" class="text-right">TOTALES:</td>
                <td class="text-right">Bs{{ number_format($totalInicial, 2) }}</td>
                <td class="text-right">Bs{{ number_format($totalVentas, 2) }}</td>
                <td class="text-right">Bs{{ number_format($totalQr, 2) }}</td>
                <td class="text-right">Bs{{ number_format($cortes->where('estado', 'cerrado')->sum('monto_final'), 2) }}</td>
                <td class="text-right">
                    @if($totalDiferencia >= 0)
                        <span style="color: green;">+Bs{{ number_format($totalDiferencia, 2) }}</span>
                    @else
                        <span style="color: red;">Bs{{ number_format($totalDiferencia, 2) }}</span>
                    @endif
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Obrador - Sistema de Gestión</p>
        <p>Reporte generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>