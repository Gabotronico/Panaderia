<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Estado de resultados — {{ $periodo['etiqueta'] }}</title>
<style>
/*
 * Estado de resultados mensual — A4 VERTICAL.
 * dompdf no soporta flexbox ni grid: toda la maquetación va con tablas y
 * anchos porcentuales que deben sumar exactamente 100% (dompdf no reparte
 * el sobrante, lo deja en blanco a la derecha).
 *
 * El documento tiene que leerse bien impreso en blanco y negro: la pérdida,
 * los faltantes y las advertencias nunca se distinguen sólo por color, siempre
 * llevan además signo, palabra o marco.
 */
* { margin:0; padding:0; box-sizing:border-box; }

@page { size: A4 portrait; margin: 14mm 13mm 18mm; }

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 9px;
    color: #1f2937;
    line-height: 1.45;
}

/* ── TÍTULO ─────────────────────────────────────────────── */
.titulo {
    text-align: center;
    font-size: 21px;
    font-weight: bold;
    color: #12304f;
    letter-spacing: -.3px;
    text-transform: uppercase;
}
.subtitulo {
    font-size: 7.6px;
    color: #d9a441;
    letter-spacing: 2.6px;
    text-transform: uppercase;
}
/* Filete dorado con punto a cada lado */
.regla { width:100%; border-collapse:collapse; margin:6px 0 12px; }
.regla td { vertical-align:middle; }
.regla .linea { border-top:1px solid #e0c088; }
.regla .punto { width:6px; }
.regla .punto div { width:4px; height:4px; border-radius:50%; background:#d9a441; }

/* Caja de período, arriba a la derecha */
.folio-caja {
    background:#12304f;
    color:#fff;
    text-align:center;
    padding:5px 8px;
    border-radius:4px;
    font-size:11px;
    font-weight:bold;
    letter-spacing:.4px;
}
.folio-periodo {
    border:1px solid #e3e8ef;
    border-radius:4px;
    padding:5px 8px;
    margin-top:5px;
    font-size:7.2px;
    color:#6b7280;
    text-align:center;
}

/* ── TARJETAS DE CABECERA ───────────────────────────────── */
.tarjetas { width:100%; border-collapse:separate; border-spacing:6px 0; margin:0 -6px 11px; }
.tarjetas td {
    border:1px solid #e3e8ef;
    border-radius:6px;
    padding:8px 9px;
    vertical-align:top;
    width:25%;
}
.tarjeta-lbl { font-size:6.4px; color:#9aa3b2; text-transform:uppercase; letter-spacing:.8px; }
.tarjeta-val { font-size:12px; font-weight:bold; color:#111827; margin-top:3px; }
.tarjeta-sub { font-size:6.6px; color:#9aa3b2; margin-top:2px; }
.tarjetas td.destacada { background:#12304f; border-color:#12304f; }
.tarjetas td.destacada .tarjeta-lbl { color:#a9bdd4; }
.tarjetas td.destacada .tarjeta-val { color:#e8c684; font-size:13px; }
.tarjetas td.destacada .tarjeta-sub { color:#a9bdd4; }
/* En pérdida la tarjeta se invierte: marco grueso y palabra explícita, para
   que no dependa del color al imprimir. */
.tarjetas td.perdida { background:#fff; border:2px solid #12304f; }
.tarjetas td.perdida .tarjeta-val { color:#12304f; font-size:13px; }

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

/* ── ESTADO DE RESULTADOS ───────────────────────────────── */
.er { width:100%; border-collapse:collapse; }

.er thead th {
    background:#12304f;
    color:#fff;
    padding:7px 6px;
    font-size:6.8px;
    font-weight:bold;
    letter-spacing:.3px;
    text-transform:uppercase;
    text-align:right;
    border-right:1px solid #2a4a6d;
    line-height:1.25;
}
.er thead th:last-child { border-right:none; }
.er thead th.izq { text-align:left; }

.er tbody td {
    padding:5px 6px;
    border-bottom:1px solid #edf1f6;
    font-size:9px;
    vertical-align:middle;
}
.er tbody td.d { text-align:right; }
.er tbody td.c { text-align:center; }

/* Encabezado de bloque (INGRESOS, COSTO DE VENTAS, …) */
.er tr.bloque td {
    background:#f4f8fd;
    font-size:6.8px;
    font-weight:bold;
    color:#12304f;
    letter-spacing:1.1px;
    text-transform:uppercase;
    padding:5px 6px;
    border-bottom:1px solid #dce5f0;
}
/* Renglón de detalle: sangrado y con el signo de la operación */
.lin-concepto { padding-left:16px !important; }
.lin-nota { font-size:6.8px; color:#9aa3b2; padding-left:16px; }
.op { display:inline-block; width:11px; color:#6b7280; font-weight:bold; }

/* Subtotales (utilidad bruta, total gastos operativos) */
.er tr.subtotal td {
    background:#fafbfd;
    border-top:1px solid #c8d4e2;
    border-bottom:1px solid #c8d4e2;
    font-weight:bold;
    font-size:9.6px;
    color:#12304f;
    padding:8px 6px;
}
/* Resultado final */
.er tr.total td {
    background:#12304f;
    color:#fff;
    font-weight:bold;
    font-size:11px;
    padding:10px 6px;
    border-bottom:none;
}
.er tr.total td.monto { color:#e8c684; font-size:13px; }
.er tr.total td.monto.neg { color:#fff; }
/* Sobre la franja navy, verde y rojo pasan a tonos claros legibles */
.er tr.total td.monto.var-mejor { color:#7ee2a8; font-size:9px; }
.er tr.total td.monto.var-peor  { color:#ffb4ac; font-size:9px; }
.er tr.total td.monto.var-nula  { color:#7f95ad; font-size:9px; }
.er tr.total td.var-pct.var-mejor { color:#7ee2a8; }
.er tr.total td.var-pct.var-peor  { color:#ffb4ac; }
.er tr.total td.var-pct.var-nula  { color:#7f95ad; }
.er tr.total .etiqueta-res {
    display:block;
    font-size:6.6px;
    font-weight:normal;
    letter-spacing:1.4px;
    color:#a9bdd4;
    text-transform:uppercase;
    margin-top:2px;
}

.monto     { white-space:nowrap; }
.m-neg     { color:#b3261e; }
.m-pos     { color:#15803d; }
.pct-col   { color:#6b7280; font-size:7.6px; }
.var-nula  { color:#c9d2de; }
.var-mejor { color:#15803d; }
.var-peor  { color:#b3261e; }
.var-pct   { font-size:7.4px; }

/* ── NOTAS (fuera del resultado) ────────────────────────── */
.notas { width:100%; border-collapse:collapse; border:1px solid #e3e8ef; }
.notas caption {
    background:#eef2f7;
    border:1px solid #e3e8ef;
    border-bottom:none;
    color:#12304f;
    font-size:7.2px;
    font-weight:bold;
    letter-spacing:1.1px;
    text-transform:uppercase;
    padding:6px 9px;
    text-align:left;
}
.notas td { padding:5px 9px; border-bottom:1px solid #f0f3f7; font-size:8.4px; vertical-align:top; }
.notas tr:last-child td { border-bottom:none; }
.notas td.d { text-align:right; font-weight:bold; white-space:nowrap; }
.notas .n-tit { font-weight:bold; color:#111827; }
.notas .n-txt { color:#6b7280; font-size:7.2px; }

/* Advertencia: marco grueso + rótulo, legible sin color */
.alerta {
    border:1.5px solid #12304f;
    border-left:5px solid #d9a441;
    border-radius:5px;
    background:#fcf7ec;
    padding:8px 12px;
    margin-top:9px;
}
.alerta-tit {
    font-size:7.6px;
    font-weight:bold;
    color:#12304f;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:3px;
}
.alerta-txt { font-size:7.8px; color:#3f4854; line-height:1.6; }

.leyenda {
    margin-top:5px;
    font-size:6.9px;
    color:#6b7280;
    line-height:1.5;
    padding-left:2px;
}

.aclara {
    margin-top:9px;
    border:1px solid #e3e8ef;
    border-left:3px solid #d9a441;
    border-radius:5px;
    padding:8px 12px;
}
.aclara-tit { font-size:7.4px; font-weight:bold; color:#12304f; text-transform:uppercase; letter-spacing:.9px; margin-bottom:3px; }
.aclara-txt { font-size:7.2px; color:#4b5563; line-height:1.6; }

/* ── FIRMAS ─────────────────────────────────────────────── */
.firmas { width:100%; border-collapse:separate; border-spacing:7px 0; margin:11px -7px 0; }
.firmas td {
    border:1px solid #e3e8ef;
    border-radius:6px;
    height:48px;
    vertical-align:bottom;
    padding:0 8px 7px;
    text-align:center;
}
.firma-raya { border-top:1px solid #9aa3b2; padding-top:4px; }
.firma-rol  { font-size:6.8px; color:#6b7280; }
.firma-nom  { font-size:7.4px; font-weight:bold; color:#111827; }

/* ── PIE FIJO ───────────────────────────────────────────── */
.pie {
    position: fixed;
    bottom: -12mm;
    left: 0; right: 0;
    width:100%;
    border-top:1px solid #e3e8ef;
    padding-top:5px;
    font-size:6.8px;
    color:#6b7280;
    border-collapse:collapse;
}
.pie td { border:none; padding:0; }
.pie strong { color:#12304f; }
</style>
</head>
<body>

@php
    /* ── Datos base ──────────────────────────────────────────
       Todas las claves salen del contrato de $r; acá no se inventa ninguna,
       sólo se derivan porcentajes y variaciones a partir de ellas. */
    $ing = (float) $r['ingresos'];

    // ¿Hay mes anterior con el que comparar? De la respuesta dependen los
    // anchos de la tabla: las dos variantes deben sumar 100% exacto.
    $hayComp = !empty($comparativo);

    // Emisor: si el controlador manda $usuario se respeta; si no, el usuario
    // logueado; si tampoco (render de prueba), "Administración".
    $emisor = $usuario ?? (auth()->check() ? auth()->user()->name : 'Administración');

    // Bs con dos decimales; el signo va aparte para poder alinearlo.
    $bs = fn ($v) => 'Bs ' . number_format(abs((float) $v), 2);

    // Monto firmado: el menos es el signo tipográfico U+2212, más legible
    // impreso que el guion corto.
    $firmado = fn ($v) => ((float) $v < 0 ? '−' : '') . 'Bs ' . number_format(abs((float) $v), 2);

    // Porcentaje con un decimal y el mismo signo tipográfico que los montos.
    $pct1 = fn ($v) => ((float) $v < 0 ? '−' : '') . number_format(abs((float) $v), 1) . '%';

    // Peso sobre la venta del período.
    $pctIng = fn ($v) => $ing > 0 ? number_format(abs((float) $v) / $ing * 100, 1) . '%' : '—';

    // Variación contra el mes anterior. Devuelve null si esa línea no viene
    // en $comparativo (no todas las líneas tienen comparativo).
    $variacion = function ($clave, $actual) use ($comparativo, $hayComp) {
        if (!$hayComp || !array_key_exists($clave, $comparativo)) return null;
        $antes = (float) $comparativo[$clave];
        $dif   = (float) $actual - $antes;
        return [
            'dif' => $dif,
            'pct' => $antes == 0.0 ? null : round($dif / abs($antes) * 100, 1),
            'antes' => $antes,
        ];
    };

    /* Las dos celdas de la columna de variación.
       $buenoSube dice si que la cifra suba es favorable (ingresos, utilidades)
       o desfavorable (costos, gastos). Se devuelve HTML armado para no repetir
       el mismo bloque en cada renglón.
       La flecha ▲/▼ hace legible la dirección sin depender del color. */
    $celdasVar = function ($v, $buenoSube) {
        if ($v === null) {
            return '<td class="d monto var-nula">—</td><td class="d var-pct var-nula">—</td>';
        }
        $d      = (float) $v['dif'];
        $flecha = $d > 0 ? '▲' : ($d < 0 ? '▼' : '=');
        $clase  = $d == 0.0 ? '' : ((($d > 0) === (bool) $buenoSube) ? 'var-mejor' : 'var-peor');
        $mto    = ($d > 0 ? '+' : ($d < 0 ? '−' : '')) . 'Bs ' . number_format(abs($d), 2);

        if ($v['pct'] === null) {
            $pct = '<span class="var-nula">sin base</span>';
        } else {
            $p   = (float) $v['pct'];
            $pct = ($p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 1) . '%';
        }

        return '<td class="d monto ' . $clase . '">' . $flecha . ' ' . $mto . '</td>'
             . '<td class="d var-pct ' . $clase . '">' . $pct . '</td>';
    };

    $enGanancia = (float) $r['utilidad_neta'] >= 0;

    // Diferencias de caja: negativo = faltante (resta), positivo = sobrante.
    $dif      = (float) $r['diferencias_caja'];
    $difLabel = $dif < 0 ? 'Faltantes de caja' : 'Sobrantes de caja';

    // Anchos de columna. Suman exactamente 100 en las dos variantes.
    // Con comparativo:  38 + 17 + 10 + 18 + 17 = 100
    // Sin comparativo:  60 + 25 + 15           = 100
    $anchoConcepto = $hayComp ? 38 : 60;
    $anchoImporte  = $hayComp ? 17 : 25;
    $anchoPct      = $hayComp ? 10 : 15;
    $anchoVarBs    = 18;
    $anchoVarPct   = 17;
    $colspanTabla  = $hayComp ? 5 : 3;
@endphp

{{-- ═══════ PIE FIJO (se repite en cada página) ═══════ --}}
<table class="pie">
    <tr>
        <td style="width:34%; text-align:left;">
            <strong>Período:</strong>
            {{ $periodo['desde']->format('d/m/Y') }} al {{ $periodo['hasta']->format('d/m/Y') }}
        </td>
        <td style="width:33%; text-align:center;">
            <strong>Emitido por:</strong> {{ $emisor }}
        </td>
        <td style="width:33%; text-align:right;">
            <strong>Emisión:</strong> {{ now()->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>

{{-- ═══════ ENCABEZADO ═══════ --}}
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:23%;"></td>
        <td style="width:54%;">
            <div class="titulo">Estado de Resultados</div>
            <table class="regla">
                <tr>
                    <td class="linea"></td>
                    <td class="punto"><div></div></td>
                    <td style="white-space:nowrap; padding:0 8px;">
                        <span class="subtitulo">
                            {{ $empresa['nombre'] }} · {{ $empresa['subtitulo'] }}
                        </span>
                    </td>
                    <td class="punto"><div></div></td>
                    <td class="linea"></td>
                </tr>
            </table>
        </td>
        <td style="width:23%; vertical-align:top; padding-top:2px;">
            <div class="folio-caja">{{ $periodo['etiqueta'] }}</div>
            <div class="folio-periodo">
                Del {{ $periodo['desde']->format('d/m/Y') }}<br>
                al {{ $periodo['hasta']->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- ═══════ TARJETAS ═══════ --}}
<table class="tarjetas">
    <tr>
        <td>
            <div class="tarjeta-lbl">Ingresos por ventas</div>
            <div class="tarjeta-val">{{ $bs($r['ingresos']) }}</div>
            <div class="tarjeta-sub">
                {{ $r['num_ventas'] }} {{ $r['num_ventas'] == 1 ? 'venta' : 'ventas' }}
                @if($r['num_ventas'] > 0)
                    · ticket {{ $bs($ing / $r['num_ventas']) }}
                @endif
            </div>
        </td>
        <td>
            <div class="tarjeta-lbl">Utilidad bruta</div>
            <div class="tarjeta-val">{{ $firmado($r['utilidad_bruta']) }}</div>
            <div class="tarjeta-sub">Margen bruto {{ $pct1($r['margen_bruto']) }}</div>
        </td>
        <td>
            <div class="tarjeta-lbl">Gastos operativos</div>
            <div class="tarjeta-val">{{ $bs($r['gastos_operativos']) }}</div>
            <div class="tarjeta-sub">{{ $pctIng($r['gastos_operativos']) }} de la venta</div>
        </td>
        <td class="{{ $enGanancia ? 'destacada' : 'perdida' }}">
            <div class="tarjeta-lbl">{{ $enGanancia ? 'Utilidad neta' : 'Pérdida del período' }}</div>
            <div class="tarjeta-val">{{ $firmado($r['utilidad_neta']) }}</div>
            <div class="tarjeta-sub">Margen neto {{ $pct1($r['margen_neto']) }}</div>
        </td>
    </tr>
</table>

{{-- ═══════ ESTADO DE RESULTADOS ═══════ --}}
<div class="seccion"><span>—</span>Resultado del período</div>

<table class="er">
    <thead>
        {{-- Los anchos suman 100% exacto en las dos variantes: dompdf no
             reparte el sobrante. Con comparativo 38+17+10+18+17; sin
             comparativo 60+25+15. --}}
        <tr>
            <th class="izq" style="width:{{ $anchoConcepto }}%;">Concepto</th>
            <th style="width:{{ $anchoImporte }}%;">Importe</th>
            <th style="width:{{ $anchoPct }}%;">% s/<br>venta</th>
            @if($hayComp)
                <th style="width:{{ $anchoVarBs }}%;">Variación<br>mes anterior</th>
                <th style="width:{{ $anchoVarPct }}%;">Var. %</th>
            @endif
        </tr>
    </thead>
    <tbody>

        {{-- ── INGRESOS ── --}}
        <tr class="bloque"><td colspan="{{ $colspanTabla }}">Ingresos</td></tr>
        @php $v = $variacion('ingresos', $r['ingresos']); @endphp
        <tr>
            <td class="lin-concepto">
                <span class="op">+</span>Ingresos por ventas
                <div class="lin-nota" style="padding-left:11px;">
                    {{ $r['num_ventas'] }} {{ $r['num_ventas'] == 1 ? 'venta registrada' : 'ventas registradas' }} en el período
                </div>
            </td>
            <td class="d monto">{{ $firmado($r['ingresos']) }}</td>
            <td class="d pct-col">{{ $ing > 0 ? '100.0%' : '—' }}</td>
            @if($hayComp)
                {!! $celdasVar($v, true) !!}
            @endif
        </tr>

        {{-- ── COSTOS DIRECTOS ──
             El costo de ventas y los gastos variables se restan antes de la
             utilidad bruta: así la columna de importes suma exactamente el
             resultado que informa el controlador. --}}
        <tr class="bloque"><td colspan="{{ $colspanTabla }}">Costo de lo vendido</td></tr>

        @php $v = $variacion('costo_ventas', $r['costo_ventas']); @endphp
        <tr>
            <td class="lin-concepto">
                <span class="op">−</span>Costo de ventas
                <div class="lin-nota" style="padding-left:11px;">Insumos consumidos por lo efectivamente vendido</div>
            </td>
            <td class="d monto m-neg">{{ $firmado(-abs($r['costo_ventas'])) }}</td>
            <td class="d pct-col">{{ $pctIng($r['costo_ventas']) }}</td>
            @if($hayComp)
                {!! $celdasVar($v, false) !!}
            @endif
        </tr>

        @if((float) $r['gastos_variables'] != 0.0)
        <tr>
            <td class="lin-concepto">
                <span class="op">−</span>Gastos variables
                <div class="lin-nota" style="padding-left:11px;">Transporte, fletes y reparaciones puntuales</div>
            </td>
            <td class="d monto m-neg">{{ $firmado(-abs($r['gastos_variables'])) }}</td>
            <td class="d pct-col">{{ $pctIng($r['gastos_variables']) }}</td>
            @if($hayComp)
                {!! $celdasVar(null, false) !!}
            @endif
        </tr>
        @endif

        {{-- ── UTILIDAD BRUTA ── --}}
        @php $v = $variacion('utilidad_bruta', $r['utilidad_bruta']); @endphp
        <tr class="subtotal">
            <td>= Utilidad bruta</td>
            <td class="d monto">{{ $firmado($r['utilidad_bruta']) }}</td>
            <td class="d">{{ $pct1($r['margen_bruto']) }}</td>
            @if($hayComp)
                {!! $celdasVar($v, true) !!}
            @endif
        </tr>

        {{-- ── GASTOS OPERATIVOS ── --}}
        <tr class="bloque"><td colspan="{{ $colspanTabla }}">Gastos operativos</td></tr>

        @if((float) $r['planillas'] != 0.0)
        <tr>
            <td class="lin-concepto">
                <span class="op">−</span>Sueldos y salarios (planillas)
            </td>
            <td class="d monto m-neg">{{ $firmado(-abs($r['planillas'])) }}</td>
            <td class="d pct-col">{{ $pctIng($r['planillas']) }}</td>
            @if($hayComp)
                {!! $celdasVar(null, false) !!}
            @endif
        </tr>
        @endif

        @if((float) $r['gastos_fijos'] != 0.0)
        <tr>
            <td class="lin-concepto">
                <span class="op">−</span>Gastos fijos pagados
                <div class="lin-nota" style="padding-left:11px;">Alquiler, servicios y demás gastos del mes</div>
            </td>
            <td class="d monto m-neg">{{ $firmado(-abs($r['gastos_fijos'])) }}</td>
            <td class="d pct-col">{{ $pctIng($r['gastos_fijos']) }}</td>
            @if($hayComp)
                {!! $celdasVar(null, false) !!}
            @endif
        </tr>
        @endif

        @if($dif != 0.0)
        <tr>
            <td class="lin-concepto">
                <span class="op">{{ $dif < 0 ? '−' : '+' }}</span>{{ $difLabel }}
                <div class="lin-nota" style="padding-left:11px;">
                    Diferencia entre lo contado en caja y lo que registró el sistema
                </div>
            </td>
            <td class="d monto {{ $dif < 0 ? 'm-neg' : 'm-pos' }}">{{ $firmado($dif) }}</td>
            <td class="d pct-col">{{ $pctIng($dif) }}</td>
            @if($hayComp)
                {!! $celdasVar(null, true) !!}
            @endif
        </tr>
        @endif

        @php $v = $variacion('gastos_operativos', $r['gastos_operativos']); @endphp
        <tr class="subtotal">
            <td>= Total gastos operativos</td>
            <td class="d monto">{{ $firmado(-abs($r['gastos_operativos'])) }}</td>
            <td class="d">{{ $pctIng($r['gastos_operativos']) }}</td>
            @if($hayComp)
                {!! $celdasVar($v, false) !!}
            @endif
        </tr>
    </tbody>

    <tfoot>
        @php $v = $variacion('utilidad_neta', $r['utilidad_neta']); @endphp
        <tr class="total">
            <td>
                = Utilidad neta del período
                <span class="etiqueta-res">
                    {{ $enGanancia ? 'Resultado: ganancia' : 'Resultado: pérdida' }}
                </span>
            </td>
            <td class="d monto {{ $enGanancia ? '' : 'neg' }}">
                {{ $firmado($r['utilidad_neta']) }}
                @unless($enGanancia)
                    <span style="font-size:7px; letter-spacing:1px;"><br>(pérdida)</span>
                @endunless
            </td>
            <td class="d">{{ $pct1($r['margen_neto']) }}</td>
            @if($hayComp)
                {{-- Sobre la franja navy los verdes/rojos se reasignan por CSS
                     a tonos claros para que sigan siendo legibles. --}}
                {!! $celdasVar($v, true) !!}
            @endif
        </tr>
    </tfoot>
</table>

@if($hayComp)
<div class="leyenda">
    La variación compara contra el mes inmediato anterior: ▲ la cifra subió, ▼ bajó.
    Subir es favorable en ingresos y utilidades, y desfavorable en costos y gastos.
    Las líneas sin comparativo cargado llevan un guion.
</div>
@endif

{{-- ═══════ NOTAS — NO AFECTAN EL RESULTADO ═══════ --}}
@php
    $pctSinCosto = (float) $r['pct_sin_costo'];
    $hayNotas = (float) $r['compras'] != 0.0
             || (float) $r['mermas'] != 0.0
             || (float) $r['venta_sin_costo'] != 0.0;
@endphp

@if($hayNotas)
<div style="margin-top:11px;">
    <div class="seccion"><span>—</span>Notas informativas · no forman parte del resultado</div>

    <table class="notas">
        <caption>Movimientos e indicadores del período</caption>

        @if((float) $r['compras'] != 0.0)
        <tr>
            <td style="width:74%;">
                <span class="n-tit">Compras de insumos del período</span>
                <div class="n-txt">
                    Movimiento hacia el inventario, no un gasto del mes. Sólo llega al costo de
                    ventas la parte de esos insumos que se consumió en lo efectivamente vendido.
                </div>
            </td>
            <td class="d" style="width:26%;">{{ $bs($r['compras']) }}</td>
        </tr>
        @endif

        @if((float) $r['mermas'] != 0.0)
        <tr>
            <td style="width:74%;">
                <span class="n-tit">Mermas de insumos</span>
                <div class="n-txt">
                    Indicador de control. El insumo perdido ya se pagó al comprarlo, por eso no
                    vuelve a restar acá.
                    @if((float) $r['compras'] > 0)
                        Representa {{ $pct1(abs((float) $r['mermas']) / (float) $r['compras'] * 100) }}
                        de las compras del período.
                    @endif
                </div>
            </td>
            <td class="d" style="width:26%;">{{ $bs($r['mermas']) }}</td>
        </tr>
        @endif

        @if((float) $r['venta_sin_costo'] != 0.0)
        <tr>
            <td style="width:74%;">
                <span class="n-tit">Venta sin costo conocido</span>
                <div class="n-txt">
                    Ventas de productos que no tienen costo cargado en el sistema.
                    Equivalen a {{ $pct1($pctSinCosto) }} de los ingresos del período.
                </div>
            </td>
            <td class="d" style="width:26%;">{{ $bs($r['venta_sin_costo']) }}</td>
        </tr>
        @endif
    </table>
</div>
@endif

{{-- Advertencia: sólo cuando la venta sin costo pesa más de 5%. --}}
@if($pctSinCosto > 5)
<div class="alerta">
    <div class="alerta-tit">⚠ Atención · el margen bruto está sobreestimado</div>
    <div class="alerta-txt">
        El <strong>{{ $pct1($pctSinCosto) }} de la venta del período
        ({{ $bs($r['venta_sin_costo']) }}) no tiene costo conocido</strong>: son productos sin
        costo cargado en el sistema. Ese porcentaje de ingresos entró completo al resultado sin
        descontar lo que costó producirlo, así que
        <strong>el margen bruto de {{ $pct1($r['margen_bruto']) }} y la utilidad
        informada están por encima de lo real.</strong>
        Cargar el costo de esos productos corrige el estado de resultados del período.
    </div>
</div>
@endif

{{-- ═══════ FIRMAS ═══════ --}}
<table class="firmas">
    {{-- 34 + 33 + 33 = 100 exacto --}}
    <tr>
        <td style="width:34%;">
            <div class="firma-raya">
                <div class="firma-rol">Elaborado por</div>
                <div class="firma-nom">{{ $emisor }}</div>
            </div>
        </td>
        <td style="width:33%;">
            <div class="firma-raya">
                <div class="firma-rol">Revisado por</div>
                <div class="firma-nom">&nbsp;</div>
            </div>
        </td>
        <td style="width:33%;">
            <div class="firma-raya">
                <div class="firma-rol">Recibido por Contabilidad</div>
                <div class="firma-nom">&nbsp;</div>
            </div>
        </td>
    </tr>
</table>

<div class="aclara">
    <div class="aclara-tit">Alcance del documento</div>
    <div class="aclara-txt">
        Generado a partir de las ventas, producciones, planillas, gastos y cierres de caja cargados
        al {{ now()->format('d/m/Y H:i') }}. Las planillas en borrador y los gastos aún no pagados no
        entran en este resultado. Importes en bolivianos (Bs).
    </div>
</div>

</body>
</html>
