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

@page { margin: 12mm 10mm 15mm; }

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 9px;
    color: #1f2937;
    line-height: 1.4;
}

/* ── TÍTULO ─────────────────────────────────────────────── */
.titulo {
    text-align: center;
    font-size: 23px;
    font-weight: bold;
    color: #12304f;
    letter-spacing: -.3px;
    text-transform: uppercase;
}
.subtitulo {
    text-align: center;
    font-size: 8.5px;
    color: #d9a441;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-top: 5px;
}
/* Línea dorada con punto a cada lado, como el encabezado del modelo */
.regla { width:100%; border-collapse:collapse; margin:6px 0 12px; }
.regla td { vertical-align:middle; }
.regla .linea { border-top:1px solid #e0c088; }
.regla .punto { width:6px; }
.regla .punto div { width:4px; height:4px; border-radius:50%; background:#d9a441; }

/* Folio arriba a la derecha */
.folio-caja {
    background:#12304f;
    color:#fff;
    text-align:center;
    padding:5px 12px;
    border-radius:4px;
    font-size:12px;
    font-weight:bold;
    letter-spacing:.5px;
}
.folio-periodo {
    border:1px solid #e3e8ef;
    border-radius:4px;
    padding:5px 10px;
    margin-top:5px;
    font-size:7.5px;
    color:#6b7280;
    text-align:center;
}

/* ── TARJETAS DE CABECERA ───────────────────────────────── */
.tarjetas { width:100%; border-collapse:separate; border-spacing:6px 0; margin:0 -6px 13px; }
.tarjetas td {
    border:1px solid #e3e8ef;
    border-radius:6px;
    padding:8px 10px;
    vertical-align:top;
    width:16.66%;
}
.tarjeta-lbl { font-size:6.6px; color:#9aa3b2; text-transform:uppercase; letter-spacing:.8px; }
.tarjeta-val { font-size:11px; font-weight:bold; color:#111827; margin-top:3px; }
.tarjetas td.destacada { background:#12304f; border-color:#12304f; }
.tarjetas td.destacada .tarjeta-lbl { color:#a9bdd4; }
.tarjetas td.destacada .tarjeta-val { color:#e8c684; font-size:13px; }

/* ── TÍTULO DE SECCIÓN ──────────────────────────────────── */
.seccion {
    font-size:8px;
    font-weight:bold;
    color:#12304f;
    text-transform:uppercase;
    letter-spacing:1.3px;
    margin-bottom:6px;
}
.seccion span { color:#d9a441; margin-right:5px; }

/* ── TABLA DE DETALLE ───────────────────────────────────── */
.detalle { width:100%; border-collapse:collapse; margin-bottom:13px; }

.detalle thead th {
    background:#12304f;
    color:#fff;
    padding:8px 4px;
    font-size:7px;
    font-weight:bold;
    letter-spacing:.3px;
    text-transform:uppercase;
    text-align:center;
    border-right:1px solid #2a4a6d;
    line-height:1.25;
}
.detalle thead th:last-child { border-right:none; }
.detalle thead th.izq { text-align:left; padding-left:8px; }

.detalle tbody td {
    padding:9px 4px;
    border-bottom:1px solid #edf1f6;
    font-size:8.6px;
    vertical-align:middle;
}
.detalle tbody tr:nth-child(even) { background:#fafbfd; }
.detalle tbody td.c { text-align:center; }
.detalle tbody td.d { text-align:right; }
.detalle tbody td.izq { padding-left:8px; }

.emp-num    { text-align:center; color:#9aa3b2; font-size:9px; }
.emp-nombre { font-weight:bold; color:#111827; font-size:9.2px; }
.emp-cargo  { color:#6b7280; font-size:7.4px; margin-top:1px; }
.emp-ci     { color:#9aa3b2; font-size:7.2px; }

.n-cero  { color:#c9d2de; }
.n-tarde { color:#b45309; font-weight:bold; }
.n-medio { color:#0b6ea8; font-weight:bold; }
.n-aus   { color:#b3261e; font-weight:bold; }

.m-suma  { color:#15803d; }
.m-resta { color:#b3261e; }
.m-neto  { color:#111827; font-weight:bold; font-size:9.6px; }

.firma-celda { width:9%; }
.firma-linea { border-bottom:1px solid #b9c3d1; margin:14px 6px 0; }

.detalle tfoot td {
    background:#12304f;
    color:#fff;
    padding:8px 4px;
    font-size:8.6px;
    font-weight:bold;
    text-align:right;
}
.detalle tfoot td.izq { text-align:left; letter-spacing:.6px; padding-left:8px; }
.detalle tfoot td.c   { text-align:center; }
.detalle tfoot td.verde { color:#7ee2a8; }
.detalle tfoot td.rojo  { color:#ffb4ac; }

/* ── ZONA INFERIOR ──────────────────────────────────────── */
.inferior { width:100%; }
.inferior > tbody > tr > td { vertical-align:top; }
.col-izq { width:41%; padding-right:14px; }
.col-der { width:59%; }

.resumen { width:100%; border:1px solid #e3e8ef; border-radius:6px; border-collapse:collapse; }
.resumen caption {
    background:#12304f;
    color:#fff;
    font-size:7.4px;
    font-weight:bold;
    letter-spacing:1.1px;
    text-transform:uppercase;
    padding:7px 11px;
    text-align:left;
}
.resumen td { padding:6px 11px; font-size:8.8px; border-bottom:1px solid #f0f3f7; }
.resumen td.d { text-align:right; font-weight:bold; }
.resumen tr.granTotal td {
    background:#f4f8fd;
    border-top:1.5px solid #12304f;
    border-bottom:none;
    font-size:12.5px;
    font-weight:bold;
    color:#12304f;
    padding:9px 11px;
}

/* Firmas: tres recuadros, como en el modelo */
.firmas { width:100%; border-collapse:separate; border-spacing:7px 0; margin:0 -7px; }
.firmas td {
    border:1px solid #e3e8ef;
    border-radius:6px;
    height:62px;
    vertical-align:bottom;
    padding:0 8px 7px;
    text-align:center;
    width:33.33%;
}
.firma-raya  { border-top:1px solid #9aa3b2; padding-top:4px; }
.firma-rol   { font-size:7px; color:#6b7280; }
.firma-nom   { font-size:7.4px; font-weight:bold; color:#111827; }

/* ── NOTA Y PIE ─────────────────────────────────────────── */
.nota {
    margin-top:12px;
    border:1px solid #e3e8ef;
    border-left:3px solid #d9a441;
    border-radius:5px;
    padding:9px 12px;
}
.nota-tit { font-size:7.6px; font-weight:bold; color:#12304f; text-transform:uppercase; letter-spacing:.9px; margin-bottom:3px; }
.nota-txt { font-size:7.4px; color:#4b5563; line-height:1.6; }

.pie {
    position: fixed;
    bottom: -10mm;
    left: 0; right: 0;
    border-top:1px solid #e3e8ef;
    padding-top:5px;
    font-size:7px;
    color:#6b7280;
}
.pie td { border:none; padding:0; }
.pie strong { color:#12304f; }

.chip { display:inline-block; padding:3px 9px; border-radius:9px; font-size:7.6px; font-weight:bold; }
.c-mensual  { background:#dce9f8; color:#12304f; }
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
    $sumAdel  = $planilla->detalles->sum('adelantos_descontados');
    $folio    = str_pad($planilla->id, 5, '0', STR_PAD_LEFT);
@endphp

{{-- ═══════ PIE FIJO (se repite en cada página) ═══════ --}}
<table class="pie">
    <tr>
        <td style="text-align:left;">
            <strong>Período:</strong>
            {{ $planilla->periodo_inicio->format('d/m/Y') }} al {{ $planilla->periodo_fin->format('d/m/Y') }}
        </td>
        <td style="text-align:center;">
            <strong>Elaborado por:</strong> {{ $planilla->user->name ?? 'Administración' }}
        </td>
        <td style="text-align:right;">
            <strong>Emisión:</strong> {{ now()->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>

{{-- ═══════ ENCABEZADO ═══════ --}}
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:22%;"></td>
        <td style="width:56%;">
            <div class="titulo">Planilla de Sueldos y Salarios</div>
            <table class="regla">
                <tr>
                    <td class="linea"></td>
                    <td class="punto"><div></div></td>
                    <td style="white-space:nowrap; padding:0 8px;">
                        <span class="subtitulo" style="margin:0; display:inline;">Sistema de Gestión Obrador</span>
                    </td>
                    <td class="punto"><div></div></td>
                    <td class="linea"></td>
                </tr>
            </table>
        </td>
        <td style="width:22%; vertical-align:top; padding-top:2px;">
            <div class="folio-caja">N.º {{ $folio }}</div>
            <div class="folio-periodo">
                Período del<br>
                {{ $planilla->periodo_inicio->format('d/m/Y') }} al {{ $planilla->periodo_fin->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- ═══════ TARJETAS ═══════ --}}
<table class="tarjetas">
    <tr>
        <td>
            <div class="tarjeta-lbl">Modalidad</div>
            <div class="tarjeta-val"><span class="chip c-{{ $planilla->tipo }}">{{ ucfirst($planilla->tipo) }}</span></div>
        </td>
        <td>
            <div class="tarjeta-lbl">Estado</div>
            <div class="tarjeta-val"><span class="chip c-{{ $planilla->estado }}">{{ $estadoLabel[$planilla->estado] ?? $planilla->estado }}</span></div>
        </td>
        <td>
            <div class="tarjeta-lbl">Días hábiles</div>
            <div class="tarjeta-val">{{ $diasLab }} <span style="font-weight:normal; font-size:7.4px; color:#9aa3b2;">lun–sáb</span></div>
        </td>
        <td>
            <div class="tarjeta-lbl">Empleados</div>
            <div class="tarjeta-val">{{ $planilla->detalles->count() }}</div>
        </td>
        <td>
            <div class="tarjeta-lbl">Elaborado por</div>
            <div class="tarjeta-val" style="font-size:8.6px;">{{ $planilla->user->name ?? 'Administración' }}</div>
        </td>
        <td class="destacada">
            <div class="tarjeta-lbl">Total a pagar</div>
            <div class="tarjeta-val">Bs {{ number_format($planilla->total_general, 2) }}</div>
        </td>
    </tr>
</table>

{{-- ═══════ DETALLE POR EMPLEADO ═══════ --}}
<div class="seccion"><span>—</span>Detalle de liquidación por empleado</div>
<table class="detalle">
    <thead>
        {{-- Los anchos suman exactamente 100% para que dompdf no los reescale --}}
        <tr>
            <th style="width:3%;">N°</th>
            <th class="izq" style="width:19%;">Empleado<br>(cargo)</th>
            <th style="width:8%;">Salario<br>base</th>
            <th style="width:7%;">Valor<br>día</th>
            <th style="width:6%;">Present.</th>
            <th style="width:6%;">Tardanz.</th>
            <th style="width:6%;">Medio<br>día</th>
            <th style="width:6%;">Ausent.</th>
            <th style="width:6%;">Días<br>efect.</th>
            <th style="width:9%;">Ganado</th>
            <th style="width:7%;">Adelantos</th>
            <th style="width:8%;">Líquido<br>pagable</th>
            <th style="width:9%;">Firma</th>
        </tr>
    </thead>
    <tbody>
        @forelse($planilla->detalles as $i => $det)
        @php $diasEf = $det->dias_trabajados + ($det->dias_medio * 0.5); @endphp
        <tr>
            <td class="emp-num">{{ $i + 1 }}</td>
            <td class="izq">
                <div class="emp-nombre">{{ $det->empleado->nombre_completo }}</div>
                <div class="emp-cargo">{{ $det->empleado->cargo->nombre }}</div>
                <div class="emp-ci">CI {{ $det->empleado->ci }}</div>
            </td>
            <td class="d">Bs {{ number_format($det->empleado->salario_base, 2) }}</td>
            <td class="d">Bs {{ number_format($det->empleado->valor_dia, 2) }}</td>

            <td class="c">{{ $det->dias_trabajados ?: '—' }}</td>
            <td class="c {{ $det->dias_tardanza > 0 ? 'n-tarde' : 'n-cero' }}">{{ $det->dias_tardanza ?: '—' }}</td>
            <td class="c {{ $det->dias_medio > 0 ? 'n-medio' : 'n-cero' }}">{{ $det->dias_medio ?: '—' }}</td>
            <td class="c {{ $det->dias_ausentes > 0 ? 'n-aus' : 'n-cero' }}">{{ $det->dias_ausentes ?: '—' }}</td>
            <td class="c" style="font-weight:bold;">{{ number_format($diasEf, 1) }}</td>

            <td class="d">Bs {{ number_format($det->salario_bruto, 2) }}</td>
            <td class="d {{ $det->adelantos_descontados > 0 ? 'm-resta' : 'n-cero' }}">
                {{ $det->adelantos_descontados > 0 ? '−Bs '.number_format($det->adelantos_descontados, 2) : '—' }}
            </td>
            <td class="d m-neto">Bs {{ number_format($det->total_neto, 2) }}</td>

            <td class="firma-celda"><div class="firma-linea"></div></td>
        </tr>
        @empty
        <tr><td colspan="13" class="vacio">Esta planilla no tiene empleados asignados.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td class="izq" colspan="4">TOTALES ({{ $planilla->detalles->count() }} {{ $planilla->detalles->count() === 1 ? 'empleado' : 'empleados' }})</td>
            <td class="c">{{ $planilla->detalles->sum('dias_trabajados') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_tardanza') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_medio') }}</td>
            <td class="c">{{ $planilla->detalles->sum('dias_ausentes') }}</td>
            <td class="c"></td>
            <td>Bs {{ number_format($sumBruto, 2) }}</td>
            <td class="rojo">−Bs {{ number_format($sumAdel, 2) }}</td>
            <td>Bs {{ number_format($planilla->total_general, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- ═══════ ZONA INFERIOR ═══════ --}}
<table class="inferior">
    <tr>
        <td class="col-izq">
            <table class="resumen">
                <caption>Resumen de liquidación</caption>
                <tr>
                    <td>Total ganado por días trabajados</td>
                    <td class="d">Bs {{ number_format($sumBruto, 2) }}</td>
                </tr>
                <tr>
                    <td class="m-resta">Adelantos descontados</td>
                    <td class="d m-resta">− Bs {{ number_format($sumAdel, 2) }}</td>
                </tr>
                <tr class="granTotal">
                    <td>TOTAL A PAGAR</td>
                    <td class="d">Bs {{ number_format($planilla->total_general, 2) }}</td>
                </tr>
            </table>
        </td>

        <td class="col-der">
            <div class="seccion"><span>—</span>Firmas de autorización</div>
            <table class="firmas">
                <tr>
                    <td>
                        <div class="firma-raya">
                            <div class="firma-rol">Elaborado por</div>
                            <div class="firma-nom">{{ $planilla->user->name ?? 'Administración' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="firma-raya">
                            <div class="firma-rol">Revisado y aprobado</div>
                            <div class="firma-nom">&nbsp;</div>
                        </div>
                    </td>
                    <td>
                        <div class="firma-raya">
                            <div class="firma-rol">Recibido por Contabilidad</div>
                            <div class="firma-nom">&nbsp;</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════ NOTA ═══════ --}}
<div class="nota">
    <div class="nota-tit">Nota importante</div>
    <div class="nota-txt">
        Al firmar esta planilla, el trabajador confirma haber recibido el importe indicado en
        <strong>“Líquido pagable”</strong>. Conserve una copia para su control.
        <br>
        <strong>Días efectivos</strong> = presentes + tardanzas + medios días × 0.5; sobre ese número se
        calcula lo ganado. Las tardanzas se registran para seguimiento pero no descuentan del sueldo.
        Los domingos no se contabilizan.
        @if($planilla->observaciones)
            <br><strong>Observaciones:</strong> {{ $planilla->observaciones }}
        @endif
    </div>
</div>

</body>
</html>
