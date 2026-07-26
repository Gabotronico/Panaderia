@extends('layouts.app')
@section('page-title', 'Resumen Anual de Gastos')
@section('content')

@php
    $meses     = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $catBadge  = ['alquiler'=>'danger','servicios'=>'primary','mantenimiento'=>'warning text-dark','impuestos'=>'dark','otro'=>'secondary'];
    $catLabel  = ['alquiler'=>'Alquiler','servicios'=>'Servicios','mantenimiento'=>'Mantenim.','impuestos'=>'Impuestos','otro'=>'Otro'];
    $prevYear  = $year - 1;
    $nextYear  = $year + 1;
@endphp

{{-- Encabezado --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4><i class="fas fa-chart-bar me-2"></i>Resumen Anual de Gastos</h4>
    <a href="{{ route('gastos-pagos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-calendar-alt me-1"></i>Control mensual
    </a>
</div>

{{-- Selector de año --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('gastos-pagos.anual') }}" class="d-flex align-items-center gap-2">
            <a href="{{ route('gastos-pagos.anual', ['year' => $prevYear]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="input-group input-group-sm" style="width:110px;">
                <input type="number" name="year" class="form-control text-center fw-bold"
                       min="2020" max="2099" value="{{ $year }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Ir</button>
            <a href="{{ route('gastos-pagos.anual', ['year' => $nextYear]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-right"></i>
            </a>
            @if($year !== now()->year)
                <a href="{{ route('gastos-pagos.anual') }}" class="btn btn-link btn-sm text-muted">
                    Año actual
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Tarjetas resumen --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-success h-100">
            <div class="card-body text-center py-3">
                <div class="text-success mb-1"><i class="fas fa-check-circle fa-2x"></i></div>
                <div class="text-muted small">Total pagado {{ $year }}</div>
                <div class="fs-4 fw-bold text-success">Bs {{ number_format($totalAnio, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center py-3">
                <div class="text-warning mb-1"><i class="fas fa-clock fa-2x"></i></div>
                <div class="text-muted small">Pendiente / Vencido</div>
                <div class="fs-4 fw-bold text-warning">Bs {{ number_format($pendienteAnio, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center py-3">
                <div class="text-primary mb-1"><i class="fas fa-receipt fa-2x"></i></div>
                <div class="text-muted small">Total estimado año</div>
                @php $estimadoAnio = $gastosFijos->sum(fn($g) => collect($matriz[$g->id])->sum('monto_estimado')); @endphp
                <div class="fs-4 fw-bold">Bs {{ number_format($estimadoAnio, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-info h-100">
            <div class="card-body text-center py-3">
                <div class="text-info mb-1"><i class="fas fa-percentage fa-2x"></i></div>
                <div class="text-muted small">% pagado del año</div>
                @php $pct = $estimadoAnio > 0 ? round(($totalAnio / $estimadoAnio) * 100) : 0; @endphp
                <div class="fs-4 fw-bold text-info">{{ $pct }}%</div>
                <div class="progress mt-1" style="height:6px;">
                    <div class="progress-bar bg-info" style="width:{{ $pct }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gráfica de líneas SVG --}}
@php
    // Dimensiones del SVG
    $svgW  = 1200; $svgH  = 380;
    $padL  = 72;   $padR  = 30;
    $padT  = 30;   $padB  = 50;
    $cW    = $svgW - $padL - $padR;   // ancho del área de datos
    $cH    = $svgH - $padT - $padB;   // alto del área de datos

    $maxVal = max($totalesMes) > 0 ? max($totalesMes) : 1;

    // Calcular coordenadas de los 12 puntos
    $pts = [];
    for ($m = 1; $m <= 12; $m++) {
        $pts[$m] = [
            'x'   => round($padL + (($m - 1) / 11) * $cW, 1),
            'y'   => round($padT + $cH - ($totalesMes[$m] / $maxVal) * $cH, 1),
            'val' => $totalesMes[$m],
        ];
    }

    // Strings para polyline y polygon (área de relleno)
    $lineStr = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $pts));
    $baseY   = $padT + $cH;
    $areaStr = $lineStr . " {$pts[12]['x']},{$baseY} {$pts[1]['x']},{$baseY}";

    // Gridlines del eje Y (5 niveles)
    $grids = [];
    for ($i = 0; $i <= 4; $i++) {
        $fraction  = $i / 4;
        $grids[]   = [
            'y'     => round($padT + (1 - $fraction) * $cH, 1),
            'label' => 'Bs ' . number_format($maxVal * $fraction, 0),
        ];
    }

    $mesActual = now()->month;
    $esAnioActual = $year == now()->year;
@endphp

<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chart-line me-2"></i>Gasto mensual pagado — {{ $year }}</span>
        <span class="text-muted" style="font-size:.78rem;">
            Pico: Bs {{ number_format(max($totalesMes), 2) }}
            ({{ $meses[array_search(max($totalesMes), $totalesMes) - 1] ?? '—' }})
        </span>
    </div>
    <div class="card-body p-3">
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" xmlns="http://www.w3.org/2000/svg"
             style="width:100%; height:auto; display:block;">
            <defs>
                <linearGradient id="lineAreaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="#0d6efd" stop-opacity="0.18"/>
                    <stop offset="100%" stop-color="#0d6efd" stop-opacity="0.01"/>
                </linearGradient>
            </defs>

            {{-- Gridlines horizontales --}}
            @foreach($grids as $g)
            <line x1="{{ $padL }}" y1="{{ $g['y'] }}" x2="{{ $svgW - $padR }}" y2="{{ $g['y'] }}"
                  stroke="#dee2e6" stroke-width="1" stroke-dasharray="{{ $loop->first ? '0' : '4 3' }}"/>
            <text x="{{ $padL - 6 }}" y="{{ $g['y'] + 4 }}" text-anchor="end"
                  font-size="10" fill="#6c757d">{{ $g['label'] }}</text>
            @endforeach

            {{-- Líneas verticales guía por mes --}}
            @for($m = 1; $m <= 12; $m++)
            <line x1="{{ $pts[$m]['x'] }}" y1="{{ $padT }}"
                  x2="{{ $pts[$m]['x'] }}" y2="{{ $padT + $cH }}"
                  stroke="#dee2e6" stroke-width="1" stroke-dasharray="3 4" opacity="0.6"/>
            @endfor

            {{-- Área de relleno bajo la línea --}}
            <polygon points="{{ $areaStr }}" fill="url(#lineAreaGrad)"/>

            {{-- Línea principal --}}
            <polyline points="{{ $lineStr }}"
                      fill="none" stroke="#0d6efd" stroke-width="2.5"
                      stroke-linejoin="round" stroke-linecap="round"/>

            {{-- Puntos de datos --}}
            @for($m = 1; $m <= 12; $m++)
            @php
                $p      = $pts[$m];
                $esPico = $p['val'] == max($totalesMes) && $p['val'] > 0;
                $esMes  = $esAnioActual && $m == $mesActual;
                $color  = $esPico ? '#dc3545' : ($esMes ? '#0d6efd' : '#0d6efd');
                $r      = $esPico ? 6 : ($esMes ? 5.5 : 4);
                $inner  = $esPico ? 2.5 : ($esMes ? 2.5 : 2);
            @endphp
            <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $r }}"
                    fill="{{ $color }}" stroke="white" stroke-width="2" opacity="0.95">
                <title>{{ $meses[$m-1] }}: Bs {{ number_format($p['val'], 2) }}</title>
            </circle>
            {{-- Punto interior blanco --}}
            <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $inner }}" fill="white" pointer-events="none"/>

            {{-- Etiqueta de valor encima del punto (solo si > 0) --}}
            @if($p['val'] > 0)
            <text x="{{ $p['x'] }}" y="{{ $p['y'] - 10 }}"
                  text-anchor="middle" font-size="{{ $esPico ? 11 : 9.5 }}"
                  font-weight="{{ $esPico ? 'bold' : 'normal' }}"
                  fill="{{ $esPico ? '#dc3545' : '#495057' }}">
                {{ number_format($p['val'], 0) }}
            </text>
            @endif
            @endfor

            {{-- Etiquetas mes actual (resaltado) --}}
            @if($esAnioActual)
            @php $mx = $pts[$mesActual]['x']; @endphp
            <rect x="{{ $mx - 14 }}" y="{{ $padT + $cH + 4 }}"
                  width="28" height="14" rx="3" fill="#0d6efd" opacity="0.15"/>
            @endif

            {{-- Etiquetas eje X --}}
            @for($m = 1; $m <= 12; $m++)
            <text x="{{ $pts[$m]['x'] }}" y="{{ $padT + $cH + 15 }}"
                  text-anchor="middle" font-size="11"
                  font-weight="{{ ($esAnioActual && $m == $mesActual) ? 'bold' : 'normal' }}"
                  fill="{{ ($esAnioActual && $m == $mesActual) ? '#0d6efd' : '#6c757d' }}">
                {{ $meses[$m - 1] }}
            </text>
            @endfor

            {{-- Línea base eje X --}}
            <line x1="{{ $padL }}" y1="{{ $padT + $cH }}"
                  x2="{{ $svgW - $padR }}" y2="{{ $padT + $cH }}"
                  stroke="#adb5bd" stroke-width="1.5"/>
        </svg>

        {{-- Leyenda --}}
        <div class="d-flex gap-3 justify-content-center mt-1" style="font-size:.75rem; color:#6c757d;">
            <span><svg width="20" height="10"><line x1="0" y1="5" x2="20" y2="5" stroke="#0d6efd" stroke-width="2.5"/><circle cx="10" cy="5" r="3.5" fill="#0d6efd" stroke="white" stroke-width="1.5"/></svg> Pagado</span>
            <span><circle r="5" fill="#dc3545" xmlns="http://www.w3.org/2000/svg"></circle>
                <svg width="10" height="10"><circle cx="5" cy="5" r="5" fill="#dc3545"/></svg> Mes pico</span>
            @if($esAnioActual)
            <span><svg width="10" height="10"><circle cx="5" cy="5" r="5" fill="#0d6efd" opacity="0.3"/></svg> Mes actual</span>
            @endif
        </div>
    </div>
</div>

{{-- Tabla matriz anual --}}
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table me-2"></i>Detalle por gasto — {{ $year }}</span>
        <div class="d-flex gap-2 align-items-center" style="font-size:.75rem;">
            <span class="badge bg-success">Pagado</span>
            <span class="badge bg-warning text-dark">Pendiente</span>
            <span class="badge bg-danger">Vencido</span>
            <span class="badge bg-light text-muted border">No aplica</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width:160px; position:sticky; left:0; z-index:2; background:#212529;">Gasto</th>
                        <th class="text-center" style="width:75px;">Cat.</th>
                        @foreach($meses as $i => $mes)
                        <th class="text-center {{ ($i+1 == now()->month && $year == now()->year) ? 'table-primary' : '' }}"
                            style="min-width:72px;">{{ $mes }}</th>
                        @endforeach
                        <th class="text-end" style="min-width:90px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastosFijos as $gasto)
                    <tr>
                        <td style="position:sticky; left:0; z-index:1; background:#fff;">
                            <strong>{{ $gasto->nombre }}</strong>
                            @if($gasto->proveedor)
                                <br><small class="text-muted">{{ $gasto->proveedor }}</small>
                            @endif
                            @if(!$gasto->activo)
                                <br><span class="badge bg-secondary" style="font-size:.6rem;">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $catBadge[$gasto->categoria] ?? 'secondary' }}" style="font-size:.65rem;">
                                {{ $catLabel[$gasto->categoria] ?? '—' }}
                            </span>
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                        @php
                            $pago   = $matriz[$gasto->id]->get($m);
                            $aplica = $gasto->seVenceEnMes($year, $m);
                            $esMesActual = $m == now()->month && $year == now()->year;
                        @endphp
                        <td class="text-center p-1 {{ $esMesActual ? 'table-primary bg-opacity-25' : '' }}">
                            @if($pago)
                                @if($pago->estado === 'pagado')
                                    <span class="badge bg-success d-block" title="Real: Bs {{ number_format($pago->monto_real, 2) }}">
                                        Bs {{ number_format($pago->monto_real, 2) }}
                                    </span>
                                @elseif($pago->estado === 'vencido')
                                    <span class="badge bg-danger d-block" title="Vencido — Bs {{ number_format($pago->monto_estimado, 2) }}">
                                        Vencido
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark d-block" title="Pendiente — Bs {{ number_format($pago->monto_estimado, 2) }}">
                                        Pend.
                                    </span>
                                @endif
                            @elseif($aplica)
                                <span class="text-muted" title="No generado — estimado Bs {{ number_format($gasto->monto_estimado, 2) }}">
                                    <i class="fas fa-minus" style="font-size:.6rem;"></i>
                                </span>
                            @else
                                <span class="text-light">·</span>
                            @endif
                        </td>
                        @endfor
                        <td class="text-end fw-bold">
                            @if($totalesGasto[$gasto->id] > 0)
                                <span class="text-success">Bs {{ number_format($totalesGasto[$gasto->id], 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="text-center text-muted py-4">
                            No hay gastos fijos registrados.
                            <a href="{{ route('gastos-fijos.create') }}">Agregar el primero</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <td colspan="2" style="position:sticky; left:0; background:#212529;" class="fw-bold">
                            Total pagado por mes
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                        <td class="text-center fw-bold {{ ($m == now()->month && $year == now()->year) ? 'text-warning' : '' }}">
                            @if($totalesMes[$m] > 0)
                                Bs {{ number_format($totalesMes[$m], 0) }}
                            @else
                                <span class="text-muted opacity-50">—</span>
                            @endif
                        </td>
                        @endfor
                        <td class="text-end fw-bold text-success">
                            Bs {{ number_format($totalAnio, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection
