<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Planilla de sueldos N.º {{ str_pad($planilla->id, 5, '0', STR_PAD_LEFT) }}</title>
<style>
/*
 * Planilla de sueldos — A4 horizontal.
 * dompdf no soporta flexbox ni grid: toda la maquetación va con tablas y
 * anchos porcentuales que deben sumar exactamente 100%.
 */
* { margin:0; padding:0; box-sizing:border-box; }

@page { margin: 13mm 11mm 16mm; }

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 9px;
    color: #1f2937;
    line-height: 1.4;
}

/* ── ENCABEZADO ─────────────────────────────────────────── */
.encabezado { width:100%; border-bottom:2.5px solid #14304f; padding-bottom:9px; margin-bottom:11px; }
.encabezado td { vertical-align:bottom; }

.marca      { font-size:17px; font-weight:bold; color:#14304f; letter-spacing:-.4px; }
.marca-sub  { font-size:7.4px; color:#8a94a6; letter-spacing:1.3px; text-transform:uppercase; margin-top:3px; }

.doc-tipo   { font-size:12px; font-weight:bold; color:#14304f; letter-spacing:.9px; text-transform:uppercase; }
.doc-folio  { font-size:10px; font-weight:bold; color:#a2540a; margin-top:3px; letter-spacing:.5px; }
.doc-periodo{ font-size:8.4px; color:#6b7280; margin-top:2px; }

/* ── FICHA DE DATOS ─────────────────────────────────────── */
.ficha { width:100%; border-collapse:collapse; margin-bottom:12px; }
.ficha td { border:1px solid #e3e8ef; padding:5px 9px; width:16.66%; vertical-align:top; }
.ficha .lbl { font-size:6.6px; color:#9aa3b2; text-transform:uppercase; letter-spacing:.8px; }
.ficha .val { font-size:10px; font-weight:bold; color:#111827; margin-top:2px; }
.ficha td.destacada { background:#f4f8fd; border-color:#c9dcf0; }
.ficha td.destacada .val { color:#14304f; font-size:11.5px; }

/* ── TÍTULO DE SECCIÓN ──────────────────────────────────── */
.seccion {
    font-size:7.8px;
    font-weight:bold;
    color:#14304f;
    text-transform:uppercase;
    letter-spacing:1.2px;
    padding-bottom:4px;
    border-bottom:1px solid #d5dbe4;
    margin-bottom:0;
}

/* ── TABLA DE DETALLE ───────────────────────────────────── */
.detalle { width:100%; border-collapse:collapse; margin-bottom:13px; }

.detalle thead th {
    background:#14304f;
    color:#fff;
    padding:7px 4px;
    font-size:7.2px;
    font-weight:bold;
    letter-spacing:.4px;
    text-transform:uppercase;
    text-align:center;
    border-right:1px solid #29486b;
}
.detalle thead th:last-child { border-right:none; }
.detalle thead th.izq { text-align:left; padding-left:7px; }

.detalle tbody td {
    padding:7px 4px;
    border-bottom:1px solid #edf1f6;
    font-size:8.6px;
    vertical-align:middle;
}
.detalle tbody tr:nth-child(even) { background:#fafbfd; }
.detalle tbody td.c { text-align:center; }
.detalle tbody td.d { text-align:right; }
.detalle tbody td.izq { padding-left:7px; }

.nombre { font-weight:bold; color:#111827; font-size:9.2px; }
.cargo  { color:#8a94a6; font-size:7.2px; margin-top:1px; }

/* separadores entre bloques de columnas */
.corte { border-left:1.5px solid #c9d2de !important; }

.n-ok    { color:#15803d; font-weight:bold; }
.n-tarde { color:#a2540a; font-weight:bold; }
.n-medio { color:#0b6ea8; font-weight:bold; }
.n-aus   { color:#b3261e; font-weight:bold; }
.n-nada  { color:#d3d9e2; }

.m-suma { color:#15803d; }
.m-resta{ color:#a2540a; }
.m-neto { color:#14304f; font-weight:bold; font-size:10px; }

.detalle tfoot td {
    background:#14304f;
    color:#fff;
    padding:7px 4px;
    font-size:8.6px;
    font-weight:bold;
    text-align:right;
}
.detalle tfoot td.izq { text-align:left; letter-spacing:.7px; padding-left:7px; }
.detalle tfoot td.c   { text-align:center; }

.firma-linea { border-bottom:1px solid #a8b1c0; margin:13px 4px 3px; }
.firma-texto { text-align:center; font-size:6.2px; color:#a8b1c0; letter-spacing:.3px; }

/* ── ZONA INFERIOR ──────────────────────────────────────── */
.inferior { width:100%; }
.inferior > tbody > tr > td { vertical-align:top; }
.col-izq { width:58%; padding-right:16px; }
.col-der { width:42%; }

.totales { width:100%; border:1px solid #14304f; border-collapse:collapse; }
.totales caption {
    background:#14304f;
    color:#fff;
    font-size:7.4px;
    font-weight:bold;
    letter-spacing:1.1px;
    text-transform:uppercase;
    padding:6px 10px;
    text-align:left;
}
.totales td { padding:5px 10px; font-size:8.8px; border-bottom:1px solid #f0f3f7; }
.totales td.d { text-align:right; font-weight:bold; }
.totales tr.granTotal td {
    background:#f4f8fd;
    border-top:1.5px solid #14304f;
    border-bottom:none;
    font-size:12.5px;
    font-weight:bold;
    color:#14304f;
    padding:8px 10px;
    letter-spacing:.3px;
}

.aut-titulo { font-size:7.2px; font-weight:bold; color:#8a94a6; text-transform:uppercase; letter-spacing:1px; margin-bottom:2px; }
.aut { width:100%; border-collapse:collapse; }
.aut td { width:50%; text-align:center; padding:0 14px; vertical-align:bottom; }
.aut-linea  { border-bottom:1.2px solid #4b5563; margin-top:36px; }
.aut-nombre { font-size:8.4px; font-weight:bold; color:#111827; margin-top:3px; }
.aut-rol    { font-size:7px; color:#a8b1c0; }

/* ── NOTAS Y PIE ────────────────────────────────────────── */
.notas {
    margin-top:11px;
    padding:7px 10px;
    background:#fafbfd;
    border-left:2.5px solid #c9d2de;
    font-size:6.9px;
    color:#6b7280;
    line-height:1.6;
}
.notas strong { color:#4b5563; }

.pie {
    position: fixed;
    bottom: -10mm;
    left: 0; right: 0;
    border-top:1px solid #e3e8ef;
    padding-top:4px;
    font-size:6.6px;
    color:#a8b1c0;
}
.pie td { border:none; padding:0; }

.chip { display:inline-block; padding:2px 8px; border-radius:9px; font-size:7.2px; font-weight:bold; }
.c-mensual  { background:#dce9f8; color:#14304f; }
.c-semanal  { background:#d6f2e3; color:#0f5132; }
.c-pagada   { background:#d6f2e3; color:#0f5132; }
.c-cerrada  { background:#e8ebf0; color:#374151; }
.c-borrador { background:#fdf0d0; color:#8a5a06; }

.vacio { text-align:center; color:#a8b1c0; padding:22px; font-size:9px; }
</style>
</head>
<body>

@php
    $estadoLabel = ['borrador' => 'Borrador', 'cerrada' => 'Cerrada', 'pagada' => 'Pagada'];

    // Días hábiles reales del período (lunes a sábado)
    $diasLab = 0;
    $cur = $planilla->periodo_inicio->copy();
    while ($cur->lte($planilla->periodo_fin)) {
        if ($cur->dayOfWeek !== 0) $diasLab++;
        $cur->addDay();
    }

    $sumBruto = $planilla->detalles->sum('salario_bruto');
    $sumExtra = $planilla->detalles->sum('monto_horas_extra');
    $sumAdel  = $planilla->detalles->sum('adelantos_descontados');
    $folio    = str_pad($planilla->id, 5, '0', STR_PAD_LEFT);
@endphp

{{-- ═══════ PIE FIJO (se repite en cada página) ═══════ --}}
<table class="pie">
    <tr>
        <td style="text-align:left;">Panadería Luna · Documento interno de uso administrativo</td>
        <td style="text-align:center;">Planilla N.º {{ $folio }}</td>
        <td style="text-align:right;">Emitido el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</td>
    </tr>
</table>

{{-- ═══════ ENCABEZADO ═══════ --}}
<table class="encabezado">
    <tr>
        <td style="width:54%;">
            <div class="marca">Panadería Luna</div>
            <div class="marca-sub">Recursos Humanos · Sistema de Gestión</div>
        </td>
        <td style="width:46%; text-align:right;">
            <div class="doc-tipo">Planilla de Sueldos y Salarios</div>
            <div class="doc-folio">N.º {{ $folio }}</div>
            <div class="doc-periodo">
                Período del {{ $planilla->periodo_inicio->format('d/m/Y') }}
                al {{ $planilla->periodo_fin->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- ═══════ FICHA DE DATOS ═══════ --}}
<table class="ficha">
    <tr>
        <td>
            <div class="lbl">Modalidad</div>
            <div class="val"><span class="chip c-{{ $planilla->tipo }}">{{ ucfirst($planilla->tipo) }}</span></div>
        </td>
        <td>
            <div class="lbl">Estado</div>
            <div class="val"><span class="chip c-{{ $planilla->estado }}">{{ $estadoLabel[$planilla->estado] ?? $planilla->estado }}</span></div>
        </td>
        <td>
            <div class="lbl">Días hábiles</div>
            <div class="val">{{ $diasLab }} <span style="font-weight:normal; font-size:7.4px; color:#a8b1c0;">lun–sáb</span></div>
        </td>
        <td>
            <div class="lbl">Empleados</div>
            <div class="val">{{ $planilla->detalles->count() }}</div>
        </td>
        <td>
            <div class="lbl">Elaborado por</div>
            <div class="val" style="font-size:8.4px;">{{ $planilla->user->name ?? 'Administración' }}</div>
        </td>
        <td class="destacada">
            <div class="lbl">Total a pagar</div>
            <div class="val">Bs {{ number_format($planilla->total_general, 2) }}</div>
        </td>
    </tr>
</table>

{{-- ═══════ DETALLE POR EMPLEADO ═══════ --}}
<div class="seccion">Detalle de liquidación por empleado</div>
<table class="detalle">
    <thead>
        {{-- Los anchos suman exactamente 100% para que dompdf no los reescale --}}
        <tr>
            <th class="izq" style="width:21%;">Empleado</th>
            <th style="width:9%;">Salario base</th>
            <th style="width:7%;">Valor día</th>

            <th class="corte" style="width:6%;">Present.</th>
            <th style="width:6%;">Tardanz.</th>
            <th style="width:6%;">Medio día</th>
            <th style="width:6%;">Ausent.</th>
            <th style="width:6%;">Días efect.</th>

            <th class="corte" style="width:9%;">Ganado</th>
            <th style="width:7%;">Horas extra</th>
            <th style="width:8%;">Adelantos</th>
            <th style="width:9%;">Líquido pagable</th>
        </tr>
    </thead>
    <tbody>
        @forelse($planilla->detalles as $det)
        @php $diasEf = $det->dias_trabajados + ($det->dias_medio * 0.5); @endphp
        <tr>
            <td class="izq">
                <div class="nombre">{{ $det->empleado->nombre_completo }}</div>
                <div class="cargo">{{ $det->empleado->cargo->nombre }} · CI {{ $det->empleado->ci }}</div>
            </td>
            <td class="d">Bs {{ number_format($det->empleado->salario_base, 2) }}</td>
            <td class="d">Bs {{ number_format($det->empleado->valor_dia, 2) }}</td>

            <td class="c corte {{ $det->dias_trabajados > 0 ? 'n-ok' : 'n-nada' }}">{{ $det->dias_trabajados ?: '—' }}</td>
            <td class="c {{ $det->dias_tardanza > 0 ? 'n-tarde' : 'n-nada' }}">{{ $det->dias_tardanza ?: '—' }}</td>
            <td class="c {{ $det->dias_medio > 0 ? 'n-medio' : 'n-nada' }}">{{ $det->dias_medio ?: '—' }}</td>
            <td class="c {{ $det->dias_ausentes > 0 ? 'n-aus' : 'n-nada' }}">{{ $det->dias_ausentes ?: '—' }}</td>
            <td class="c" style="font-weight:bold;">{{ number_format($diasEf, 1) }}</td>

            <td class="d corte">Bs {{ number_format($det->salario_bruto, 2) }}</td>
            <td class="d m-suma">{{ $det->monto_horas_extra > 0 ? '+Bs '.number_format($det->monto_horas_extra, 2) : '—' }}</td>
            <td class="d m-resta">{{ $det->adelantos_descontados > 0 ? '−Bs '.number_format($det->adelantos_descontados, 2) : '—' }}</td>
            <td class="d m-neto">Bs {{ number_format($det->total_neto, 2) }}</td>
        </tr>
        <tr>
            <td colspan="12" style="padding:0 4px 9px; border-bottom:1px solid #edf1f6;">
                <div class="firma-linea"></div>
                <div class="firma-texto">Firma de conformidad y recepción — {{ $det->empleado->nombre_completo }} · CI {{ $det->empleado->ci }}</div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="12" class="vacio">Esta planilla no tiene empleados asignados.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td class="izq" colspan="3">TOTALES · {{ $planilla->detalles->count() }} {{ $planilla->detalles->count() === 1 ? 'empleado' : 'empleados' }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_trabajados') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_tardanza') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_medio') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_ausentes') }}</td>
            <td class="c"></td>
            <td>Bs {{ number_format($sumBruto, 2) }}</td>
            <td>+Bs {{ number_format($sumExtra, 2) }}</td>
            <td>−Bs {{ number_format($sumAdel, 2) }}</td>
            <td>Bs {{ number_format($planilla->total_general, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- ═══════ ZONA INFERIOR ═══════ --}}
<table class="inferior">
    <tr>
        <td class="col-izq">
            <div class="aut-titulo">Firmas de autorización</div>
            <table class="aut">
                <tr>
                    <td>
                        <div class="aut-linea"></div>
                        <div class="aut-nombre">{{ $planilla->user->name ?? 'Administración' }}</div>
                        <div class="aut-rol">Elaborado por</div>
                    </td>
                    <td>
                        <div class="aut-linea"></div>
                        <div class="aut-nombre">&nbsp;</div>
                        <div class="aut-rol">Revisado y aprobado</div>
                    </td>
                </tr>
            </table>
        </td>

        <td class="col-der">
            <table class="totales">
                <caption>Resumen de liquidación</caption>
                <tr>
                    <td>Total ganado por días trabajados</td>
                    <td class="d">Bs {{ number_format($sumBruto, 2) }}</td>
                </tr>
                <tr>
                    <td class="m-suma">Horas extra</td>
                    <td class="d m-suma">+Bs {{ number_format($sumExtra, 2) }}</td>
                </tr>
                <tr>
                    <td class="m-resta">Adelantos descontados</td>
                    <td class="d m-resta">−Bs {{ number_format($sumAdel, 2) }}</td>
                </tr>
                <tr class="granTotal">
                    <td>TOTAL A PAGAR</td>
                    <td class="d">Bs {{ number_format($planilla->total_general, 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════ NOTAS ═══════ --}}
<div class="notas">
    <strong>Cómo leer esta planilla.</strong>
    <strong>Días efectivos</strong> = presentes + tardanzas + medios días × 0.5; sobre ese número se calcula lo ganado.
    Las <strong>tardanzas</strong> se registran para seguimiento del personal pero <strong>no descuentan del sueldo</strong>:
    el día llegado tarde cuenta como día trabajado.
    Los <strong>adelantos</strong> son los entregados durante el período; si el sueldo no alcanzó a cubrirlos,
    el saldo queda pendiente para la siguiente planilla. Los domingos no se contabilizan.
    @if($planilla->observaciones)
        <br><strong>Observaciones:</strong> {{ $planilla->observaciones }}
    @endif
</div>

</body>
</html>
