@extends('layouts.app')
@section('page-title', 'Resumen Financiero')
@section('content')

@php
    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $nombreMes    = $mesesNombres[(int) $month] ?? '';
    $prev         = $inicio->copy()->subMonthNoOverflow();
    $next         = $inicio->copy()->addMonthNoOverflow();
    $esMesActual  = $month == now()->month && $year == now()->year;

    // Devuelve la variación porcentual entre dos valores
    $delta = function ($hoy, $antes) {
        if ($antes == 0) return $hoy == 0 ? null : 100.0;
        return round((($hoy - $antes) / abs($antes)) * 100, 1);
    };
@endphp

<x-page-header title="Resumen Financiero" icon="chart-pie"
               :subtitle="$nombreMes.' de '.$year.' · todos los módulos consolidados'">
    <a href="{{ route('gastos-pagos.index', ['year'=>$year,'month'=>$month]) }}" class="btn btn-light border">
        <i class="fas fa-receipt me-1"></i>Gastos
    </a>
    <a href="{{ route('planillas.index') }}" class="btn btn-light border">
        <i class="fas fa-file-invoice-dollar me-1"></i>Planillas
    </a>
</x-page-header>

<x-alerts />

{{-- Selector de mes --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('finanzas.index') }}"
          class="d-flex gap-2 align-items-center flex-wrap">
        <a href="{{ route('finanzas.index', ['year'=>$prev->year,'month'=>$prev->month]) }}"
           class="btn btn-light border" title="Mes anterior">
            <i class="fas fa-chevron-left"></i>
        </a>

        <select name="month" class="form-select" style="width:140px;">
            @foreach($mesesNombres as $num => $nom)
                @if($num > 0)
                    <option value="{{ $num }}" @selected($month == $num)>{{ $nom }}</option>
                @endif
            @endforeach
        </select>
        <input type="number" name="year" class="form-control text-center" style="width:95px;"
               min="2020" max="2099" value="{{ $year }}">
        <button type="submit" class="btn btn-primary">Ir</button>

        <a href="{{ route('finanzas.index', ['year'=>$next->year,'month'=>$next->month]) }}"
           class="btn btn-light border" title="Mes siguiente">
            <i class="fas fa-chevron-right"></i>
        </a>

        @unless($esMesActual)
            <a href="{{ route('finanzas.index') }}" class="btn btn-link text-muted px-2">Ir al mes actual</a>
        @endunless

        <span class="ms-auto text-muted" style="font-size:.8rem;">
            {{ $inicio->format('d/m/Y') }} — {{ $fin->format('d/m/Y') }}
        </span>
    </form>
</div>

{{-- ── RESULTADO DEL MES ─────────────────────────────────────── --}}
{{-- El resultado y sus dos márgenes, juntos. Antes eran cuatro tarjetas que
     repetían lo que ya dice el estado de resultados de abajo. Los avisos que
     cambian cómo se leen estos números van acá y no como una alerta suelta
     que se saltea. --}}
@php
    $dUtilidad = $delta($actual['utilidad_neta'], $anterior['utilidad_neta']);
    // La usa la fila de ventas del estado de resultados, más abajo.
    $dIngresos = $delta($actual['ingresos'], $anterior['ingresos']);
    $un        = $actual['utilidad_neta'];
    $hayAvisos = $pendientes['borradores'] > 0 || $pendientes['cantidad'] > 0;
@endphp

<div class="card mb-4" style="background:var(--primary); border:none; box-shadow:var(--shadow-md);">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">

            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="width:22px; height:2px; background:var(--oro); display:inline-block;"></span>
                    <span style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.1em; color:var(--oro);">
                        Utilidad neta de {{ $nombreMes }}
                    </span>
                </div>
                <div style="font-family:Georgia,serif; font-size:2.7rem; font-weight:700; line-height:1; letter-spacing:-.015em; color:#fff; font-variant-numeric:tabular-nums;">
                    {{ $un < 0 ? '−' : '' }}Bs {{ number_format(abs($un), 2) }}
                </div>
                <div class="mt-2" style="font-size:.82rem; color:#9db2ca;">
                    {{ $un >= 0 ? 'El negocio dio ganancia' : 'El negocio dio pérdida' }}
                </div>
            </div>

            <div class="col-lg-{{ $hayAvisos ? 4 : 8 }}">
                <div class="d-flex flex-wrap gap-4 ps-lg-4" style="border-left:1px solid rgba(255,255,255,.10);">
                    <div>
                        <div style="font-size:.72rem; color:#9db2ca;">Margen bruto</div>
                        <div class="mt-1" style="font-size:1.35rem; font-weight:700; color:#fff; line-height:1; font-variant-numeric:tabular-nums;">
                            {{ $actual['margen_bruto'] }}%
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.72rem; color:#9db2ca;">Margen neto</div>
                        <div class="mt-1" style="font-size:1.35rem; font-weight:700; color:#fff; line-height:1; font-variant-numeric:tabular-nums;">
                            {{ $actual['margen_neto'] }}%
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.72rem; color:#9db2ca;">Ingresos</div>
                        <div class="mt-1" style="font-size:1.35rem; font-weight:700; color:#fff; line-height:1; font-variant-numeric:tabular-nums;">
                            Bs {{ number_format($actual['ingresos'], 2) }}
                        </div>
                    </div>
                    @if($dUtilidad !== null)
                    <div>
                        <div style="font-size:.72rem; color:#9db2ca;">Vs. mes anterior</div>
                        <div class="mt-1 d-inline-flex align-items-center gap-1"
                             style="font-size:.86rem; font-weight:600; padding:4px 9px; border-radius:5px;
                                    background:{{ $dUtilidad >= 0 ? 'rgba(34,197,94,.16)' : 'rgba(239,68,68,.18)' }};
                                    color:{{ $dUtilidad >= 0 ? '#86efac' : '#fca5a5' }};">
                            <i class="fas fa-arrow-{{ $dUtilidad >= 0 ? 'up' : 'down' }}" style="font-size:.7rem;"></i>
                            {{ number_format(abs($dUtilidad), 1) }}%
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if($hayAvisos)
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-2 ps-lg-4" style="border-left:1px solid rgba(255,255,255,.10);">
                    @if($pendientes['borradores'] > 0)
                        <div class="d-flex align-items-start gap-2">
                            <span class="rounded-circle flex-shrink-0 mt-1" style="width:7px; height:7px; background:var(--oro);"></span>
                            <span style="font-size:.79rem; color:#c7d6e6; line-height:1.45;">
                                {{ $pendientes['borradores'] }} planilla(s) en borrador — los sueldos pueden cambiar al cerrarlas.
                                <a href="{{ route('planillas.index') }}" style="color:var(--oro-claro);">Revisar</a>
                            </span>
                        </div>
                    @endif
                    @if($pendientes['cantidad'] > 0)
                        <div class="d-flex align-items-start gap-2">
                            <span class="rounded-circle flex-shrink-0 mt-1"
                                  style="width:7px; height:7px; background:{{ $pendientes['vencidos'] > 0 ? '#fca5a5' : 'var(--oro)' }};"></span>
                            <span style="font-size:.79rem; color:#c7d6e6; line-height:1.45;">
                                Bs {{ number_format($pendientes['gastos'], 2) }} en gastos sin pagar
                                @if($pendientes['vencidos'] > 0)
                                    ({{ $pendientes['vencidos'] }} vencido(s))
                                @endif
                                — todavía no restan.
                                <a href="{{ route('gastos-pagos.index', ['year'=>$year,'month'=>$month]) }}" style="color:var(--oro-claro);">Ver gastos</a>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ══ ESTADO DE RESULTADOS ══ --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-scale-balanced me-2"></i>Estado de resultados — {{ $nombreMes }} {{ $year }}
            </div>
            <div class="card-body p-0">
                <table class="table mb-0 pl-table">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">vs. mes anterior</th>
                            <th class="text-end">% de ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Ingresos --}}
                        <tr class="pl-group">
                            <td><i class="fas fa-plus-circle text-success me-2"></i>Ventas</td>
                            <td class="text-end fw-bold text-success">
                                Bs {{ number_format($actual['ingresos'], 2) }}
                            </td>
                            <td class="text-end">
                                @include('finanzas._delta', ['valor' => $dIngresos, 'positivoEsBueno' => true])
                            </td>
                            <td class="text-end text-muted">100%</td>
                        </tr>

                        {{-- Costos directos --}}
                        <tr>
                            <td class="ps-4 text-muted">
                                <i class="fas fa-minus text-danger me-2"></i>Compras de insumos
                            </td>
                            <td class="text-end">Bs {{ number_format($actual['compras'], 2) }}</td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['compras'], $anterior['compras']),
                                    'positivoEsBueno' => false,
                                ])
                            </td>
                            <td class="text-end text-muted">
                                {{ $actual['ingresos'] > 0 ? round($actual['compras'] / $actual['ingresos'] * 100, 1) : 0 }}%
                            </td>
                        </tr>
                        {{-- Gastos variables: pesan sobre la utilidad bruta, igual
                             que las compras. --}}
                        <tr>
                            <td class="ps-4 text-muted">
                                <i class="fas fa-minus text-danger me-2"></i>Gastos variables
                                <div class="text-muted" style="font-size:.7rem; padding-left:1.65rem;">
                                    Transporte, fletes, reparaciones puntuales
                                </div>
                            </td>
                            <td class="text-end">Bs {{ number_format($actual['gastos_variables'], 2) }}</td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['gastos_variables'], $anterior['gastos_variables']),
                                    'positivoEsBueno' => false,
                                ])
                            </td>
                            <td class="text-end text-muted">
                                {{ $actual['ingresos'] > 0 ? round($actual['gastos_variables'] / $actual['ingresos'] * 100, 1) : 0 }}%
                            </td>
                        </tr>

                        {{-- La merma es informativa: el insumo perdido ya se pagó
                             al comprarlo, así que no vuelve a restar acá. Se
                             muestra para poder controlarla. --}}
                        <tr class="fst-italic" style="background:#fcfcfd;">
                            <td class="ps-4 text-muted">
                                <i class="fas fa-circle-info text-secondary me-2"></i>Mermas de insumos
                                <span class="badge bg-light text-secondary border ms-1" style="font-size:.62rem;">
                                    informativo
                                </span>
                                <div class="text-muted" style="font-size:.7rem; padding-left:1.65rem;">
                                    Ya incluidas en las compras · no se restan otra vez
                                </div>
                            </td>
                            <td class="text-end text-muted">Bs {{ number_format($actual['mermas'], 2) }}</td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['mermas'], $anterior['mermas']),
                                    'positivoEsBueno' => false,
                                ])
                            </td>
                            <td class="text-end text-muted">
                                {{ $actual['compras'] > 0 ? round($actual['mermas'] / $actual['compras'] * 100, 1) : 0 }}%
                                <div class="text-muted" style="font-size:.65rem;">de las compras</div>
                            </td>
                        </tr>

                        {{-- Subtotal: utilidad bruta --}}
                        <tr class="pl-subtotal">
                            <td class="fw-bold">= Utilidad bruta</td>
                            <td class="text-end fw-bold {{ $actual['utilidad_bruta'] >= 0 ? 'text-success' : 'text-danger' }}">
                                Bs {{ number_format($actual['utilidad_bruta'], 2) }}
                            </td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['utilidad_bruta'], $anterior['utilidad_bruta']),
                                    'positivoEsBueno' => true,
                                ])
                            </td>
                            <td class="text-end fw-semibold">{{ $actual['margen_bruto'] }}%</td>
                        </tr>

                        {{-- Gastos operativos --}}
                        <tr>
                            <td class="ps-4 text-muted">
                                <i class="fas fa-minus text-danger me-2"></i>Sueldos (planillas)
                            </td>
                            <td class="text-end">Bs {{ number_format($actual['planillas'], 2) }}</td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['planillas'], $anterior['planillas']),
                                    'positivoEsBueno' => false,
                                ])
                            </td>
                            <td class="text-end text-muted">
                                {{ $actual['ingresos'] > 0 ? round($actual['planillas'] / $actual['ingresos'] * 100, 1) : 0 }}%
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">
                                <i class="fas fa-minus text-danger me-2"></i>Gastos fijos pagados
                            </td>
                            <td class="text-end">Bs {{ number_format($actual['gastos_fijos'], 2) }}</td>
                            <td class="text-end">
                                @include('finanzas._delta', [
                                    'valor' => $delta($actual['gastos_fijos'], $anterior['gastos_fijos']),
                                    'positivoEsBueno' => false,
                                ])
                            </td>
                            <td class="text-end text-muted">
                                {{ $actual['ingresos'] > 0 ? round($actual['gastos_fijos'] / $actual['ingresos'] * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="pl-total">
                            <td class="fw-bold">UTILIDAD NETA</td>
                            <td class="text-end fw-bold fs-6 {{ $actual['utilidad_neta'] >= 0 ? 'text-success' : 'text-danger' }}">
                                Bs {{ number_format($actual['utilidad_neta'], 2) }}
                            </td>
                            <td class="text-end">
                                @include('finanzas._delta', ['valor' => $dUtilidad, 'positivoEsBueno' => true])
                            </td>
                            <td class="text-end fw-bold">{{ $actual['margen_neto'] }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ══ COMPOSICIÓN DE EGRESOS ══ --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-simple me-2"></i>¿En qué se va el dinero?
            </div>
            <div class="card-body">
                @if($actual['egresos_total'] > 0)
                    @php
                        // Las mermas quedan fuera del reparto: no son plata que
                        // salió aparte, ya están dentro de las compras. Si se
                        // incluyeran, los segmentos sumarían más que el total.
                        $partes = [
                            ['Compras de insumos', $actual['compras'],          '#0ea5e9'],
                            ['Gastos variables',   $actual['gastos_variables'], '#ef4444'],
                            ['Sueldos',            $actual['planillas'],        '#8b5cf6'],
                            ['Gastos fijos',       $actual['gastos_fijos'],     '#f59e0b'],
                        ];
                        $partes = array_filter($partes, fn($p) => $p[1] > 0);
                    @endphp

                    {{-- Barra apilada --}}
                    <div class="stacked-bar mb-3">
                        @foreach($partes as [$nombre, $monto, $color])
                            <div class="stacked-seg"
                                 style="width: {{ ($monto / $actual['egresos_total']) * 100 }}%; background: {{ $color }};"
                                 title="{{ $nombre }}: Bs {{ number_format($monto, 2) }}"></div>
                        @endforeach
                    </div>

                    <table class="table table-sm mb-0">
                        <tbody>
                        @foreach($partes as [$nombre, $monto, $color])
                            <tr>
                                <td style="width:14px;">
                                    <span class="legend-dot" style="background: {{ $color }};"></span>
                                </td>
                                <td>{{ $nombre }}</td>
                                <td class="text-end fw-semibold">Bs {{ number_format($monto, 2) }}</td>
                                <td class="text-end text-muted" style="width:52px;">
                                    {{ round($monto / $actual['egresos_total'] * 100) }}%
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($gastosPorCategoria->isNotEmpty())
                        <div class="section-title mt-4">Gastos fijos por categoría</div>
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach($gastosPorCategoria as $g)
                                <tr>
                                    <td>{{ \App\Models\GastoFijo::etiquetaCategoria($g->categoria) }}</td>
                                    <td class="text-end">Bs {{ number_format($g->total, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                @else
                    <x-empty-state icon="wallet"
                                   title="Sin egresos registrados"
                                   message="Este mes no tiene compras, sueldos ni gastos fijos pagados." />
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══ TENDENCIA 12 MESES ══ --}}
@php
    $svgW = 1200; $svgH = 340;
    $padL = 78;   $padR = 24;
    $padT = 26;   $padB = 46;
    $cW   = $svgW - $padL - $padR;
    $cH   = $svgH - $padT - $padB;

    $maxVal = max(
        collect($tendencia)->max('ingresos') ?: 0,
        collect($tendencia)->max('egresos')  ?: 0,
        1
    );
    $n = count($tendencia);

    $coord = function ($valor, $idx) use ($padL, $padT, $cW, $cH, $maxVal, $n) {
        return [
            'x' => round($padL + ($n > 1 ? ($idx / ($n - 1)) * $cW : $cW / 2), 1),
            'y' => round($padT + $cH - ($valor / $maxVal) * $cH, 1),
        ];
    };

    $ptsIngresos = $ptsEgresos = [];
    foreach ($tendencia as $i => $t) {
        $ptsIngresos[] = $coord($t['ingresos'], $i);
        $ptsEgresos[]  = $coord($t['egresos'],  $i);
    }

    $lineaIngresos = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $ptsIngresos));
    $lineaEgresos  = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $ptsEgresos));

    // Área entre ambas líneas = utilidad acumulada visualmente
    $areaUtilidad = $lineaIngresos . ' ' . implode(' ', array_map(
        fn($p) => "{$p['x']},{$p['y']}", array_reverse($ptsEgresos)
    ));

    $grids = [];
    for ($i = 0; $i <= 4; $i++) {
        $f = $i / 4;
        $grids[] = [
            'y'     => round($padT + (1 - $f) * $cH, 1),
            'label' => number_format($maxVal * $f, 0),
        ];
    }
@endphp

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-chart-line me-2"></i>Ingresos vs. egresos — últimos 12 meses</span>
        <div class="d-flex gap-3" style="font-size:.78rem;">
            <span><span class="legend-dot" style="background:#16a34a;"></span> Ingresos</span>
            <span><span class="legend-dot" style="background:#dc2626;"></span> Egresos</span>
            <span><span class="legend-dot" style="background:#6366f1; opacity:.35;"></span> Diferencia</span>
        </div>
    </div>
    <div class="card-body">
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" xmlns="http://www.w3.org/2000/svg"
             style="width:100%; height:auto; display:block;">

            {{-- Gridlines --}}
            @foreach($grids as $g)
                <line x1="{{ $padL }}" y1="{{ $g['y'] }}" x2="{{ $svgW - $padR }}" y2="{{ $g['y'] }}"
                      stroke="#e2e8f0" stroke-width="1"
                      stroke-dasharray="{{ $loop->last ? '0' : '4 4' }}"/>
                <text x="{{ $padL - 8 }}" y="{{ $g['y'] + 4 }}" text-anchor="end"
                      font-size="10" fill="#94a3b8">Bs {{ $g['label'] }}</text>
            @endforeach

            {{-- Área de diferencia entre ingresos y egresos --}}
            <polygon points="{{ $areaUtilidad }}" fill="#6366f1" opacity="0.13"/>

            {{-- Líneas --}}
            <polyline points="{{ $lineaEgresos }}" fill="none" stroke="#dc2626"
                      stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
            <polyline points="{{ $lineaIngresos }}" fill="none" stroke="#16a34a"
                      stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>

            {{-- Puntos y etiquetas --}}
            @foreach($tendencia as $i => $t)
                @php
                    $pi = $ptsIngresos[$i];
                    $pe = $ptsEgresos[$i];
                    $esActual = $t['mes'] == $month && $t['anio'] == $year;
                @endphp

                @if($esActual)
                    <line x1="{{ $pi['x'] }}" y1="{{ $padT }}" x2="{{ $pi['x'] }}" y2="{{ $padT + $cH }}"
                          stroke="#6366f1" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.5"/>
                @endif

                <circle cx="{{ $pe['x'] }}" cy="{{ $pe['y'] }}" r="{{ $esActual ? 5.5 : 4 }}"
                        fill="#dc2626" stroke="white" stroke-width="2">
                    <title>{{ $t['etiqueta'] }} {{ $t['anio'] }} — Egresos: Bs {{ number_format($t['egresos'], 2) }}</title>
                </circle>
                <circle cx="{{ $pi['x'] }}" cy="{{ $pi['y'] }}" r="{{ $esActual ? 5.5 : 4 }}"
                        fill="#16a34a" stroke="white" stroke-width="2">
                    <title>{{ $t['etiqueta'] }} {{ $t['anio'] }} — Ingresos: Bs {{ number_format($t['ingresos'], 2) }}</title>
                </circle>

                <text x="{{ $pi['x'] }}" y="{{ $padT + $cH + 18 }}" text-anchor="middle"
                      font-size="11" fill="{{ $esActual ? '#6366f1' : '#94a3b8' }}"
                      font-weight="{{ $esActual ? '700' : '400' }}">{{ $t['etiqueta'] }}</text>

                @if($t['utilidad'] != 0)
                    <text x="{{ $pi['x'] }}" y="{{ $padT + $cH + 33 }}" text-anchor="middle"
                          font-size="9.5" font-weight="600"
                          fill="{{ $t['utilidad'] >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $t['utilidad'] >= 0 ? '+' : '−' }}{{ number_format(abs($t['utilidad']), 0) }}
                    </text>
                @endif
            @endforeach
        </svg>
        <div class="text-center text-muted mt-2" style="font-size:.76rem;">
            El número bajo cada mes es la utilidad neta de ese período.
        </div>
    </div>
</div>

@push('styles')
<style>
    .pl-table tbody td { padding-top: 9px; padding-bottom: 9px; }
    .pl-group td       { background: #f8fafc; }
    .pl-subtotal td    { background: #f1f5f9; border-top: 1px solid var(--border); }
    .pl-total td       { background: #eef2ff; border-top: 2px solid var(--primary); font-size: .92rem; }

    .stacked-bar {
        display: flex;
        height: 30px;
        border-radius: 8px;
        overflow: hidden;
        background: #f1f5f9;
    }
    .stacked-seg { transition: opacity .15s; }
    .stacked-seg:hover { opacity: .82; }

    .legend-dot {
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        vertical-align: middle;
        margin-right: 3px;
    }
</style>
@endpush
@endsection
