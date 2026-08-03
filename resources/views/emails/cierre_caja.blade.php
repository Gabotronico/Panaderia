<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .header { background: #dc3545; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; }
  .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
  .body { padding: 28px 32px; }
  .badge { display: inline-block; background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
  .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .info-table td { padding: 10px 4px; border-bottom: 1px solid #f0f0f0; font-size: 15px; }
  .info-table td:first-child { color: #666; width: 46%; }
  .info-table td:last-child { font-weight: 600; }
  .section-title { font-size: 14px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .5px; margin: 28px 0 10px; border-bottom: 2px solid #f0f0f0; padding-bottom: 6px; }
  .venta-card { border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 10px; overflow: hidden; }
  .venta-header { background: #f9fafb; padding: 8px 14px; display: flex; justify-content: space-between; font-size: 13px; }
  .venta-header .num { font-weight: 700; color: #374151; }
  .venta-header .hora { color: #6b7280; }
  .venta-header .total { font-weight: 700; color: #16a34a; }
  .venta-body { padding: 8px 14px; }
  .venta-body table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .venta-body td { padding: 4px 0; color: #555; }
  .venta-body td:last-child { text-align: right; font-weight: 600; color: #333; }
  .pago-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; }
  .pago-efectivo { background: #d1fae5; color: #065f46; }
  .pago-qr { background: #dbeafe; color: #1e40af; }
  .resumen-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin-top: 20px; }
  .resumen-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 15px; border-bottom: 1px solid #eee; }
  .resumen-row:last-child { border-bottom: none; font-size: 17px; font-weight: 700; margin-top: 4px; }
  .dif-positiva { color: #16a34a; }
  .dif-negativa { color: #dc2626; }
  .no-ventas { text-align: center; color: #9ca3af; padding: 20px; font-style: italic; }
  .footer { background: #f9fafb; padding: 18px 32px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>🔒 Cierre de Caja</h1>
    <p>Obrador — Notificación automática</p>
  </div>
  <div class="body">
    <span class="badge">🏁 CAJA CERRADA</span>

    <table class="info-table">
      <tr>
        <td>Cajero</td>
        <td>{{ $corte->user->name }}</td>
      </tr>
      <tr>
        <td>Almacén</td>
        <td>{{ $corte->user->almacen?->nombre ?? '—' }}</td>
      </tr>
      <tr>
        <td>Fecha</td>
        <td>{{ $corte->fecha_corte->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <td>Hora apertura</td>
        <td>{{ $corte->hora_apertura }}</td>
      </tr>
      <tr>
        <td>Hora cierre</td>
        <td>{{ $corte->hora_cierre }}</td>
      </tr>
    </table>

    {{-- Detalle de ventas --}}
    <div class="section-title">Ventas del Turno ({{ count($ventas) }})</div>

    @if(count($ventas) === 0)
      <div class="no-ventas">Sin ventas registradas en este turno</div>
    @else
      @foreach($ventas as $venta)
      <div class="venta-card">
        <div class="venta-header">
          <span class="num">{{ $venta['numero_venta'] }}</span>
          <span class="hora">{{ \Carbon\Carbon::parse($venta['created_at'])->format('H:i') }}</span>
          <span>
            <span class="pago-badge {{ $venta['tipo_pago'] === 'qr' ? 'pago-qr' : 'pago-efectivo' }}">
              {{ strtoupper($venta['tipo_pago']) }}
            </span>
          </span>
          <span class="total">Bs {{ number_format($venta['total'], 2) }}</span>
        </div>
        @if(!empty($venta['detalles']))
        <div class="venta-body">
          <table>
            @foreach($venta['detalles'] as $detalle)
            <tr>
              <td>{{ $detalle['producto']['nombre'] ?? '—' }}</td>
              <td>x{{ $detalle['cantidad'] }}</td>
              <td>Bs {{ number_format($detalle['subtotal'], 2) }}</td>
            </tr>
            @endforeach
            @if($venta['descuento'] > 0)
            <tr>
              <td colspan="2" style="color:#ef4444">Descuento</td>
              <td style="color:#ef4444">-Bs {{ number_format($venta['descuento'], 2) }}</td>
            </tr>
            @endif
          </table>
        </div>
        @endif
      </div>
      @endforeach
    @endif

    {{-- Resumen financiero --}}
    <div class="section-title">Resumen Financiero</div>
    <div class="resumen-box">
      <div class="resumen-row">
        <span>Monto inicial (fondo)</span>
        <span>Bs {{ number_format($corte->monto_inicial, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Ventas en efectivo</span>
        <span>Bs {{ number_format($corte->ventas_efectivo, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Ventas por QR</span>
        <span>Bs {{ number_format($corte->ventas_qr, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Total ventas del turno</span>
        <span>Bs {{ number_format($corte->total_ventas, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Efectivo esperado (fondo + ventas en efectivo)</span>
        <span>Bs {{ number_format($corte->efectivo_esperado, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Efectivo contado</span>
        <span>Bs {{ number_format($corte->total_efectivo, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Diferencia en efectivo</span>
        <span class="{{ $corte->diferencia >= 0 ? 'dif-positiva' : 'dif-negativa' }}">
          {{ $corte->diferencia >= 0 ? '+' : '' }}Bs {{ number_format($corte->diferencia, 2) }}
        </span>
      </div>
      <div class="resumen-row">
        <span>QR verificado</span>
        <span>Bs {{ number_format($corte->total_qr, 2) }}</span>
      </div>
      <div class="resumen-row">
        <span>Diferencia en QR</span>
        <span class="{{ $corte->diferencia_qr >= 0 ? 'dif-positiva' : 'dif-negativa' }}">
          {{ $corte->diferencia_qr >= 0 ? '+' : '' }}Bs {{ number_format($corte->diferencia_qr, 2) }}
        </span>
      </div>
      <div class="resumen-row">
        <span>Total en caja</span>
        <span>Bs {{ number_format($corte->monto_final, 2) }}</span>
      </div>
    </div>

    @if($corte->observaciones)
    <div style="margin-top:16px; padding:12px 16px; background:#fffbeb; border-left:4px solid #f59e0b; border-radius:4px; font-size:14px;">
      <strong>Observaciones:</strong> {{ $corte->observaciones }}
    </div>
    @endif
  </div>
  <div class="footer">
    Obrador &bull; Sistema de Gestión &bull; {{ now()->format('d/m/Y H:i') }}
  </div>
</div>
</body>
</html>
