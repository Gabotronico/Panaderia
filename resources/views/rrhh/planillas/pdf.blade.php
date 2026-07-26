<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }

/* ── ENCABEZADO ── */
.header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    padding: 14px 18px;
    border-radius: 6px 6px 0 0;
    margin-bottom: 0;
}
.header table { width:100%; border:none; }
.header td { border:none; padding:0; background:transparent; vertical-align:middle; }
.logo-area { font-size:20px; font-weight:bold; letter-spacing:-.3px; }
.logo-sub  { font-size:9px; opacity:.75; margin-top:2px; }
.doc-area  { text-align:right; }
.doc-area h2 { font-size:13px; font-weight:bold; letter-spacing:.5px; }
.doc-area p  { font-size:8.5px; opacity:.8; margin-top:3px; }

/* ── BANDA INFO ── */
.info-band {
    background:#f1f5f9;
    border: 1px solid #e2e8f0;
    border-top:none;
    padding: 8px 18px;
    margin-bottom:12px;
    border-radius:0 0 6px 6px;
}
.info-band table { width:100%; border:none; }
.info-band td { border:none; padding:4px 12px 4px 0; vertical-align:top; background:transparent; }
.info-lbl { font-size:7.5px; color:#64748b; text-transform:uppercase; letter-spacing:.4px; }
.info-val { font-size:10.5px; font-weight:bold; color:#1e3a5f; margin-top:1px; }

/* ── SECCIÓN TITLE ── */
.section-title {
    background:#1e3a5f;
    color:#fff;
    font-size:8.5px;
    font-weight:bold;
    letter-spacing:.6px;
    padding:4px 10px;
    border-radius:3px 3px 0 0;
    text-transform:uppercase;
}

/* ── TABLA ASISTENCIA ── */
.tbl { width:100%; border-collapse:collapse; margin-bottom:14px; }
.tbl thead tr { background:#2563eb; color:#fff; }
.tbl thead th {
    padding:6px 5px;
    font-size:8px;
    font-weight:bold;
    text-align:center;
    border:1px solid #1d4ed8;
    letter-spacing:.3px;
}
.tbl thead th.tleft { text-align:left; }
.tbl tbody tr:nth-child(odd)  { background:#ffffff; }
.tbl tbody tr:nth-child(even) { background:#f8fafc; }
.tbl tbody tr:hover { background:#eff6ff; }
.tbl tbody td {
    padding:6px 5px;
    border:1px solid #e2e8f0;
    vertical-align:middle;
    font-size:9px;
}
.tbl tbody td.tc { text-align:center; }
.tbl tbody td.tr { text-align:right; }

/* separadores de grupo en thead */
.tbl thead th.sep-left  { border-left:2px solid #93c5fd !important; }

/* colores de celdas de asistencia */
.day-ok   { color:#16a34a; font-weight:bold; }
.day-late { color:#d97706; font-weight:bold; }
.day-half { color:#0284c7; font-weight:bold; }
.day-abs  { color:#dc2626; font-weight:bold; }
.day-zero { color:#94a3b8; }

/* columnas financieras */
.fin-bruto    { color:#1e293b; }
.fin-desc     { color:#dc2626; }
.fin-extra    { color:#16a34a; }
.fin-adelanto { color:#d97706; }
.fin-neto     { color:#1d4ed8; font-weight:bold; font-size:10px; }

/* fila totales */
.tbl tfoot tr { background:#1e3a5f; color:#fff; }
.tbl tfoot td {
    padding:6px 5px;
    border:1px solid #1e40af;
    font-size:9px;
    font-weight:bold;
    text-align:right;
}
.tbl tfoot td.tleft { text-align:left; font-size:9px; }

/* firma */
.firma-col { width:80px; }
.firma-box { margin:14px 4px 2px; border-bottom:1px solid #94a3b8; }
.firma-lbl { text-align:center; font-size:7px; color:#94a3b8; margin-top:2px; }

/* ── BOTTOM AREA ── */
.bottom { width:100%; margin-top:6px; }
.bottom-left  { width:55%; vertical-align:top; }
.bottom-right { width:45%; vertical-align:top; padding-left:16px; }

/* Resumen financiero */
.resumen {
    border:1px solid #2563eb;
    border-radius:5px;
    overflow:hidden;
}
.resumen-header {
    background:#2563eb;
    color:#fff;
    font-size:8.5px;
    font-weight:bold;
    padding:5px 10px;
    letter-spacing:.4px;
    text-transform:uppercase;
}
.resumen-body { padding:8px 12px; }
.res-row {
    display:flex;
    justify-content:space-between;
    padding:3px 0;
    border-bottom:1px solid #f1f5f9;
    font-size:9px;
}
.res-row:last-child { border-bottom:none; }
.res-total {
    font-weight:bold;
    font-size:12px;
    color:#1d4ed8;
    background:#eff6ff;
    border-radius:3px;
    padding:5px 10px;
    margin-top:6px;
    display:flex;
    justify-content:space-between;
}

/* Firmas */
.firmas-area { margin-top:6px; }
.firmas-title {
    font-size:8px;
    font-weight:bold;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:.4px;
    margin-bottom:8px;
}
.firma-block {
    display:inline-block;
    width:47%;
    text-align:center;
    margin-right:5%;
}
.firma-linea {
    border-bottom:1.5px solid #475569;
    margin:28px 10px 4px;
}
.firma-nombre { font-size:8.5px; font-weight:bold; color:#1e293b; }
.firma-rol    { font-size:7.5px; color:#94a3b8; }

/* ── PIE ── */
.footer {
    margin-top:10px;
    padding-top:6px;
    border-top:1px solid #e2e8f0;
    display:flex;
    justify-content:space-between;
    font-size:7.5px;
    color:#94a3b8;
}

/* badge */
.badge {
    display:inline-block;
    padding:2px 7px;
    border-radius:10px;
    font-size:8px;
    font-weight:bold;
}
.b-mensual { background:#dbeafe; color:#1d4ed8; }
.b-semanal { background:#d1fae5; color:#065f46; }
.b-pagada  { background:#d1fae5; color:#065f46; }
.b-cerrada { background:#f1f5f9; color:#475569; }
.b-borrador{ background:#fef9c3; color:#92400e; }
</style>
</head>
<body>

@php
    $estadoLabel = ['borrador'=>'Borrador','cerrada'=>'Cerrada','pagada'=>'Pagada'];
    $diasLab = 0;
    $cur = $planilla->periodo_inicio->copy();
    while ($cur->lte($planilla->periodo_fin)) {
        if ($cur->dayOfWeek !== 0) $diasLab++;
        $cur->addDay();
    }
    $divisor = $planilla->tipo === 'mensual' ? 26 : 6;
@endphp

{{-- ══ ENCABEZADO ══ --}}
<div class="header">
    <table>
        <tr>
            <td style="width:55%;">
                <div class="logo-area">🍞 Panadería Luna</div>
                <div class="logo-sub">Sistema de Gestión Interna · Recursos Humanos</div>
            </td>
            <td style="width:45%;">
                <div class="doc-area">
                    <h2>PLANILLA DE SUELDOS Y SALARIOS</h2>
                    <p>Planilla #{{ $planilla->id }} &nbsp;·&nbsp; Generada: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ══ BANDA INFO ══ --}}
<div class="info-band">
    <table>
        <tr>
            <td>
                <div class="info-lbl">Tipo de planilla</div>
                <div class="info-val">
                    <span class="badge b-{{ $planilla->tipo }}">{{ ucfirst($planilla->tipo) }}</span>
                </div>
            </td>
            <td>
                <div class="info-lbl">Período</div>
                <div class="info-val">{{ $planilla->periodo_inicio->format('d/m/Y') }} — {{ $planilla->periodo_fin->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="info-lbl">Días laborables (lun–sáb)</div>
                <div class="info-val">{{ $diasLab }} días</div>
            </td>
            <td>
                <div class="info-lbl">N° de empleados</div>
                <div class="info-val">{{ $planilla->detalles->count() }}</div>
            </td>
            <td>
                <div class="info-lbl">Estado</div>
                <div class="info-val">
                    <span class="badge b-{{ $planilla->estado }}">{{ $estadoLabel[$planilla->estado] ?? $planilla->estado }}</span>
                </div>
            </td>
            <td>
                <div class="info-lbl">Elaborado por</div>
                <div class="info-val">{{ $planilla->user->name ?? 'Administrador' }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ══ TABLA PRINCIPAL ══ --}}
<div class="section-title">📋 Detalle de Empleados · Asistencia y Liquidación</div>
<table class="tbl">
    <thead>
        <tr>
            {{-- Empleado --}}
            <th class="tleft" style="width:16%;" rowspan="1">Empleado</th>
            <th class="tleft" style="width:9%;">Cargo</th>
            <th style="width:7%;">Sal. Base</th>
            <th style="width:5%;">Valor/Día</th>

            {{-- Asistencia --}}
            <th class="sep-left" style="width:4%;">Pres.</th>
            <th style="width:4%;">Tard.</th>
            <th style="width:4%;">Med.D</th>
            <th style="width:4%;">Aus.</th>
            <th style="width:4%;">H.Ext</th>
            <th style="width:5%;">Días Ef.</th>

            {{-- Financiero --}}
            <th class="sep-left" style="width:6%;">Bruto</th>
            <th style="width:6%;">-Tard.</th>
            <th style="width:6%;">+H.Ext</th>
            <th style="width:6%;">-Adel.</th>
            <th style="width:7%;">NETO</th>

            {{-- Firma --}}
            <th style="width:7%;">Firma / CI</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planilla->detalles as $det)
        @php
            $valorDia = (float)$det->empleado->salario_base / $divisor;
            $diasEf   = $det->dias_trabajados + ($det->dias_medio * 0.5);
        @endphp
        <tr>
            <td><strong>{{ $det->empleado->nombre_completo }}</strong></td>
            <td>{{ $det->empleado->cargo->nombre }}</td>
            <td class="tr">Bs {{ number_format($det->empleado->salario_base, 2) }}</td>
            <td class="tc">Bs {{ number_format($valorDia, 2) }}</td>

            {{-- Asistencia --}}
            <td class="tc {{ $det->dias_trabajados > 0 ? 'day-ok' : 'day-zero' }}">
                {{ $det->dias_trabajados ?: '—' }}
            </td>
            <td class="tc {{ $det->dias_tardanza > 0 ? 'day-late' : 'day-zero' }}">
                {{ $det->dias_tardanza ?: '—' }}
            </td>
            <td class="tc {{ $det->dias_medio > 0 ? 'day-half' : 'day-zero' }}">
                {{ $det->dias_medio ?: '—' }}
            </td>
            <td class="tc {{ $det->dias_ausentes > 0 ? 'day-abs' : 'day-zero' }}">
                {{ $det->dias_ausentes ?: '—' }}
            </td>
            <td class="tc {{ $det->horas_extra > 0 ? 'fin-extra' : 'day-zero' }}">
                {{ $det->horas_extra > 0 ? number_format($det->horas_extra,1).'h' : '—' }}
            </td>
            <td class="tc" style="font-weight:bold;">{{ number_format($diasEf, 1) }}</td>

            {{-- Financiero --}}
            <td class="tr fin-bruto">Bs {{ number_format($det->salario_bruto, 2) }}</td>
            <td class="tr fin-desc">
                {{ $det->descuento_tardanzas > 0 ? '-Bs '.number_format($det->descuento_tardanzas,2) : '—' }}
            </td>
            <td class="tr fin-extra">
                {{ $det->monto_horas_extra > 0 ? '+Bs '.number_format($det->monto_horas_extra,2) : '—' }}
            </td>
            <td class="tr fin-adelanto">
                {{ $det->adelantos_descontados > 0 ? '-Bs '.number_format($det->adelantos_descontados,2) : '—' }}
            </td>
            <td class="tr fin-neto">Bs {{ number_format($det->total_neto, 2) }}</td>

            {{-- Firma --}}
            <td class="firma-col">
                <div class="firma-box"></div>
                <div class="firma-lbl">Firma / CI</div>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="tleft" colspan="4">TOTALES</td>
            <td style="text-align:center;">{{ $planilla->detalles->sum('dias_trabajados') }}</td>
            <td style="text-align:center;">{{ $planilla->detalles->sum('dias_tardanza') }}</td>
            <td style="text-align:center;">{{ $planilla->detalles->sum('dias_medio') }}</td>
            <td style="text-align:center;">{{ $planilla->detalles->sum('dias_ausentes') }}</td>
            <td style="text-align:center;">{{ number_format($planilla->detalles->sum('horas_extra'),1) }}h</td>
            <td></td>
            <td>Bs {{ number_format($planilla->detalles->sum('salario_bruto'), 2) }}</td>
            <td>-Bs {{ number_format($planilla->detalles->sum('descuento_tardanzas'), 2) }}</td>
            <td>+Bs {{ number_format($planilla->detalles->sum('monto_horas_extra'), 2) }}</td>
            <td>-Bs {{ number_format($planilla->detalles->sum('adelantos_descontados'), 2) }}</td>
            <td>Bs {{ number_format($planilla->total_general, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- ══ PARTE INFERIOR ══ --}}
<table class="bottom">
    <tr>
        {{-- Firmas de autorización --}}
        <td class="bottom-left">
            <div class="firmas-title">Firmas de autorización</div>
            <table style="width:100%; border:none;">
                <tr>
                    <td style="text-align:center; padding:0 10px; border:none; background:transparent;">
                        <div class="firma-linea"></div>
                        <div class="firma-nombre">{{ $planilla->user->name ?? 'Administrador' }}</div>
                        <div class="firma-rol">Elaborado / Administración</div>
                    </td>
                    <td style="text-align:center; padding:0 10px; border:none; background:transparent;">
                        <div class="firma-linea"></div>
                        <div class="firma-nombre">&nbsp;</div>
                        <div class="firma-rol">Revisado / Visto bueno</div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- Resumen financiero --}}
        <td class="bottom-right">
            <div class="resumen">
                <div class="resumen-header">📊 Resumen Financiero</div>
                <div class="resumen-body">
                    <div class="res-row">
                        <span>Total bruto:</span>
                        <span>Bs {{ number_format($planilla->detalles->sum('salario_bruto'), 2) }}</span>
                    </div>
                    <div class="res-row" style="color:#dc2626;">
                        <span>Descuento tardanzas:</span>
                        <span>−Bs {{ number_format($planilla->detalles->sum('descuento_tardanzas'), 2) }}</span>
                    </div>
                    <div class="res-row" style="color:#16a34a;">
                        <span>Horas extra:</span>
                        <span>+Bs {{ number_format($planilla->detalles->sum('monto_horas_extra'), 2) }}</span>
                    </div>
                    <div class="res-row" style="color:#d97706;">
                        <span>Adelantos descontados:</span>
                        <span>−Bs {{ number_format($planilla->detalles->sum('adelantos_descontados'), 2) }}</span>
                    </div>
                    <div class="res-total">
                        <span>TOTAL A PAGAR:</span>
                        <span>Bs {{ number_format($planilla->total_general, 2) }}</span>
                    </div>
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ══ LEYENDA DE COLUMNAS ══ --}}
<p style="font-size:7.5px; color:#94a3b8; margin-top:8px;">
    <strong>Leyenda:</strong>
    Pres.&nbsp;= Días presentes &nbsp;|&nbsp;
    Tard.&nbsp;= Días con tardanza &nbsp;|&nbsp;
    Med.D&nbsp;= Medio día &nbsp;|&nbsp;
    Aus.&nbsp;= Días ausentes &nbsp;|&nbsp;
    H.Ext&nbsp;= Horas extra &nbsp;|&nbsp;
    Días Ef.&nbsp;= Días efectivos pagados (presentes + tardanzas + medios×0.5) &nbsp;|&nbsp;
    -Tard.&nbsp;= Descuento por minutos de tardanza &nbsp;|&nbsp;
    -Adel.&nbsp;= Adelantos descontados del período
</p>

{{-- ══ PIE ══ --}}
<div class="footer">
    <span>Panadería Luna · Planilla generada el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</span>
    <span>Planilla #{{ $planilla->id }} · {{ ucfirst($planilla->tipo) }} · Período: {{ $planilla->periodo_inicio->format('d/m/Y') }} al {{ $planilla->periodo_fin->format('d/m/Y') }}</span>
</div>

</body>
</html>
