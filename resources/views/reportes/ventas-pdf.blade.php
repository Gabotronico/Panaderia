<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Ventas — Panadería Luna</title>
<style>
/*
 * Reporte de ventas — hoja A4 vertical.
 * dompdf no soporta flexbox ni grid: toda la maquetación va con tablas.
 */
* { margin:0; padding:0; box-sizing:border-box; }

@page { margin: 15mm 12mm 18mm; }

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 9px;
    color: #1f2937;
    line-height: 1.4;
}

/* ── ENCABEZADO ─────────────────────────────────────────── */
.doc-head { width:100%; border-bottom:2.5px solid #1e3a5f; padding-bottom:10px; margin-bottom:14px; }
.doc-head td { vertical-align:bottom; }

.marca     { font-size:18px; font-weight:bold; color:#1e3a5f; letter-spacing:-.4px; }
.marca-sub { font-size:7.5px; color:#6b7280; letter-spacing:1.2px; text-transform:uppercase; margin-top:3px; }

.doc-tipo  { font-size:13px; font-weight:bold; color:#1e3a5f; letter-spacing:.8px; text-transform:uppercase; }
.doc-meta  { font-size:8.5px; color:#6b7280; margin-top:4px; }

/* ── TARJETAS DE INDICADORES ────────────────────────────── */
.kpis { width:100%; border-collapse:separate; border-spacing:6px 0; margin:0 -6px 15px; }
.kpis td {
    width:25%;
    border:1px solid #e5e7eb;
    border-top:2.5px solid #1e3a5f;
    padding:8px 10px;
    background:#fbfcfd;
}
.kpi-lbl { font-size:6.8px; color:#9ca3af; text-transform:uppercase; letter-spacing:.8px; }
.kpi-val { font-size:15px; font-weight:bold; color:#1e3a5f; margin-top:3px; letter-spacing:-.4px; }
.kpi-pie { font-size:7px; color:#9ca3af; margin-top:2px; }

.kpis td.acento-verde { border-top-color:#15803d; }
.kpis td.acento-verde .kpi-val { color:#15803d; }
.kpis td.acento-azul  { border-top-color:#0369a1; }
.kpis td.acento-azul  .kpi-val { color:#0369a1; }

/* ── TÍTULO DE SECCIÓN ──────────────────────────────────── */
.sec {
    font-size:8px;
    font-weight:bold;
    color:#1e3a5f;
    text-transform:uppercase;
    letter-spacing:1.1px;
    padding-bottom:4px;
    border-bottom:1px solid #d1d5db;
    margin-bottom:7px;
}
.sec-espacio { margin-top:14px; }

/* ── TABLAS ─────────────────────────────────────────────── */
.tabla { width:100%; border-collapse:collapse; }

.tabla thead th {
    background:#1e3a5f;
    color:#fff;
    padding:6px 6px;
    font-size:7.4px;
    font-weight:bold;
    letter-spacing:.5px;
    text-transform:uppercase;
    text-align:left;
    border-right:1px solid #2c5282;
}
.tabla thead th:last-child { border-right:none; }
.tabla thead th.c { text-align:center; }
.tabla thead th.d { text-align:right; }

.tabla tbody td {
    padding:5.5px 6px;
    border-bottom:1px solid #eef2f7;
    font-size:8.6px;
}
.tabla tbody tr:nth-child(even) { background:#f9fafb; }
.tabla tbody td.c { text-align:center; }
.tabla tbody td.d { text-align:right; }

.tabla tfoot td {
    background:#1e3a5f;
    color:#fff;
    padding:6px;
    font-size:8.8px;
    font-weight:bold;
    text-align:right;
}
.tabla tfoot td.izq { text-align:left; letter-spacing:.6px; }
.tabla tfoot td.c { text-align:center; }

.mono   { font-family:"DejaVu Sans Mono", monospace; font-size:8px; color:#4b5563; }
.fuerte { font-weight:bold; color:#111827; }
.desc   { color:#b91c1c; }
.tenue  { color:#cbd5e1; }

/* barra proporcional dentro de una celda */
.barra-fondo { background:#eef2f7; height:7px; width:100%; }
.barra { height:7px; background:#1e3a5f; }

.chip { display:inline-block; padding:1.5px 6px; border-radius:8px; font-size:7.2px; font-weight:bold; }
.c-efectivo { background:#d1fae5; color:#065f46; }
.c-qr       { background:#dbeafe; color:#1e40af; }

/* ── PIE FIJO ───────────────────────────────────────────── */
.pie {
    position: fixed;
    bottom: -12mm;
    left: 0;
    right: 0;
    border-top:1px solid #e5e7eb;
    padding-top:4px;
    font-size:6.8px;
    color:#9ca3af;
}
.pie td { border:none; padding:0; }

.vacio { text-align:center; color:#9ca3af; padding:22px; font-size:9px; }
</style>
</head>
<body>

@php
    $ini = \Carbon\Carbon::parse($request->fecha_inicio);
    $fin = \Carbon\Carbon::parse($request->fecha_fin);
    $dias = $ini->diffInDays($fin) + 1;

    $totalMedios = $porPago['efectivo'] + $porPago['qr'];
    $pctEfectivo = $totalMedios > 0 ? round($porPago['efectivo'] / $totalMedios * 100) : 0;
    $pctQr       = $totalMedios > 0 ? 100 - $pctEfectivo : 0;

    $topCajero = $porCajero->first();
@endphp

{{-- ════════ PIE FIJO ════════ --}}
<table class="pie">
    <tr>
        <td style="text-align:left;">Panadería Luna · Reporte interno de ventas</td>
        <td style="text-align:center;">{{ $ini->format('d/m/Y') }} — {{ $fin->format('d/m/Y') }}</td>
        <td style="text-align:right;">Emitido {{ now()->format('d/m/Y H:i') }}</td>
    </tr>
</table>

{{-- ════════ ENCABEZADO ════════ --}}
<table class="doc-head">
    <tr>
        <td style="width:52%;">
            <div class="marca">Panadería Luna</div>
            <div class="marca-sub">Sistema de Gestión · Reportes</div>
        </td>
        <td style="width:48%; text-align:right;">
            <div class="doc-tipo">Reporte de Ventas</div>
            <div class="doc-meta">
                Del {{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}
                &nbsp;·&nbsp; {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}
                <br>Solo ventas completadas
            </div>
        </td>
    </tr>
</table>

{{-- ════════ INDICADORES ════════ --}}
<table class="kpis">
    <tr>
        <td>
            <div class="kpi-lbl">Facturación total</div>
            <div class="kpi-val">Bs {{ number_format($totalVentas, 2) }}</div>
            <div class="kpi-pie">{{ $cantidadVentas }} {{ $cantidadVentas === 1 ? 'venta' : 'ventas' }}</div>
        </td>
        <td>
            <div class="kpi-lbl">Ticket promedio</div>
            <div class="kpi-val">Bs {{ number_format($ticketPromedio, 2) }}</div>
            <div class="kpi-pie">por operación</div>
        </td>
        <td class="acento-verde">
            <div class="kpi-lbl">Cobrado en efectivo</div>
            <div class="kpi-val">Bs {{ number_format($porPago['efectivo'], 2) }}</div>
            <div class="kpi-pie">{{ $pctEfectivo }}% del total</div>
        </td>
        <td class="acento-azul">
            <div class="kpi-lbl">Cobrado por QR</div>
            <div class="kpi-val">Bs {{ number_format($porPago['qr'], 2) }}</div>
            <div class="kpi-pie">{{ $pctQr }}% del total</div>
        </td>
    </tr>
</table>

@if($cantidadVentas === 0)
    <div class="sec">Resultado</div>
    <div class="vacio">No se registraron ventas completadas en el período seleccionado.</div>
@else

{{-- ════════ RESUMEN POR CAJERO ════════ --}}
<div class="sec">Ventas por cajero</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:26%;">Cajero</th>
            <th class="c" style="width:9%;">Ventas</th>
            <th class="d" style="width:15%;">Efectivo</th>
            <th class="d" style="width:15%;">QR</th>
            <th class="d" style="width:15%;">Total</th>
            <th style="width:20%;">Participación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($porCajero as $nombre => $datos)
        @php $pct = $totalVentas > 0 ? round($datos['total'] / $totalVentas * 100) : 0; @endphp
        <tr>
            <td class="fuerte">{{ $nombre }}</td>
            <td class="c">{{ $datos['cantidad'] }}</td>
            <td class="d">Bs {{ number_format($datos['efectivo'], 2) }}</td>
            <td class="d">Bs {{ number_format($datos['qr'], 2) }}</td>
            <td class="d fuerte">Bs {{ number_format($datos['total'], 2) }}</td>
            <td>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="width:74%; padding:0; border:none;">
                            <div class="barra-fondo"><div class="barra" style="width:{{ max($pct, 1) }}%;"></div></div>
                        </td>
                        <td style="width:26%; padding:0 0 0 5px; border:none; font-size:7.6px; color:#6b7280;">{{ $pct }}%</td>
                    </tr>
                </table>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ════════ EVOLUCIÓN DIARIA ════════ --}}
<div class="sec sec-espacio">Evolución diaria</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:32%;">Día</th>
            <th class="c" style="width:12%;">Ventas</th>
            <th class="d" style="width:20%;">Facturado</th>
            <th style="width:36%;">Peso en el período</th>
        </tr>
    </thead>
    <tbody>
        @foreach($porDia as $dia => $datos)
        @php
            $fecha = \Carbon\Carbon::parse($dia);
            $pct   = $totalVentas > 0 ? round($datos['total'] / $totalVentas * 100) : 0;
        @endphp
        <tr>
            <td>
                <span class="fuerte">{{ $fecha->format('d/m/Y') }}</span>
                <span style="color:#9ca3af;">· {{ $fecha->locale('es')->isoFormat('dddd') }}</span>
                @if($dia === $mejorDia && $porDia->count() > 1)
                    <span class="chip c-efectivo">mejor día</span>
                @endif
            </td>
            <td class="c">{{ $datos['cantidad'] }}</td>
            <td class="d fuerte">Bs {{ number_format($datos['total'], 2) }}</td>
            <td>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="width:82%; padding:0; border:none;">
                            <div class="barra-fondo"><div class="barra" style="width:{{ max($pct, 1) }}%;"></div></div>
                        </td>
                        <td style="width:18%; padding:0 0 0 5px; border:none; font-size:7.6px; color:#6b7280;">{{ $pct }}%</td>
                    </tr>
                </table>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="izq">TOTAL DEL PERÍODO</td>
            <td class="c">{{ $cantidadVentas }}</td>
            <td>Bs {{ number_format($totalVentas, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- ════════ DETALLE DE VENTAS ════════ --}}
<div class="sec sec-espacio">Detalle de ventas</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:14%;">N.º venta</th>
            <th style="width:16%;">Fecha y hora</th>
            <th style="width:20%;">Cajero</th>
            <th class="c" style="width:11%;">Pago</th>
            <th class="d" style="width:13%;">Subtotal</th>
            <th class="d" style="width:12%;">Descuento</th>
            <th class="d" style="width:14%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ventas as $venta)
        <tr>
            <td class="mono">{{ $venta->numero_venta }}</td>
            <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $venta->user->name ?? '—' }}</td>
            <td class="c">
                <span class="chip {{ $venta->tipo_pago === 'qr' ? 'c-qr' : 'c-efectivo' }}">
                    {{ $venta->tipo_pago === 'qr' ? 'QR' : 'Efectivo' }}
                </span>
            </td>
            <td class="d">Bs {{ number_format($venta->subtotal, 2) }}</td>
            <td class="d {{ $venta->descuento > 0 ? 'desc' : 'tenue' }}">
                {{ $venta->descuento > 0 ? '−Bs '.number_format($venta->descuento, 2) : '—' }}
            </td>
            <td class="d fuerte">Bs {{ number_format($venta->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="izq" colspan="4">TOTALES ({{ $cantidadVentas }} ventas)</td>
            <td>Bs {{ number_format($totalSubtotal, 2) }}</td>
            <td>−Bs {{ number_format($totalDescuentos, 2) }}</td>
            <td>Bs {{ number_format($totalVentas, 2) }}</td>
        </tr>
    </tfoot>
</table>

@endif

</body>
</html>
