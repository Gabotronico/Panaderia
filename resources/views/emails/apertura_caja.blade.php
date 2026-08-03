<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .header { background: #198754; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; }
  .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
  .body { padding: 28px 32px; }
  .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
  .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .info-table td { padding: 10px 4px; border-bottom: 1px solid #f0f0f0; font-size: 15px; }
  .info-table td:first-child { color: #666; width: 46%; }
  .info-table td:last-child { font-weight: 600; }
  .highlight { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 14px 18px; border-radius: 4px; margin-top: 20px; }
  .footer { background: #f9fafb; padding: 18px 32px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>🔓 Apertura de Caja</h1>
    <p>Obrador — Notificación automática</p>
  </div>
  <div class="body">
    <span class="badge">✅ CAJA ABIERTA</span>

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
        <td>Hora de apertura</td>
        <td>{{ $corte->hora_apertura }}</td>
      </tr>
      <tr>
        <td>Monto inicial (fondo)</td>
        <td>Bs {{ number_format($corte->monto_inicial, 2) }}</td>
      </tr>
      @if($corte->observaciones)
      <tr>
        <td>Observaciones</td>
        <td>{{ $corte->observaciones }}</td>
      </tr>
      @endif
    </table>

    <div class="highlight">
      <strong>El cajero ha iniciado su turno.</strong><br>
      <span style="color:#555; font-size:14px;">Recibirás otra notificación cuando se realice el cierre de caja con el resumen de ventas.</span>
    </div>
  </div>
  <div class="footer">
    Obrador &bull; Sistema de Gestión &bull; {{ now()->format('d/m/Y H:i') }}
  </div>
</div>
</body>
</html>
