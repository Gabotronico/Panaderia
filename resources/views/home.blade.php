@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')

@if($isCajero)
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Vista de Cajero:</strong> Estás viendo solo la información de tus ventas y cortes de caja.
</div>
@endif

{{-- ── FILTRO DE PERÍODO ─────────────────────────────────────── --}}
{{-- Vive en una franja de una línea, no en una tarjeta: se toca de vez en
     cuando y no tiene por qué ocupar el lugar del dato principal. --}}
@php
    $mesAnterior  = $periodo['inicio']->copy()->subMonth();
    $mesSiguiente = $periodo['inicio']->copy()->addMonth();
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $esMesActual = $periodo['modo'] === 'mes' && $periodo['mes'] == now()->month && $periodo['anio'] == now()->year;
@endphp
<div class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-4">
    <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2">
        <a href="{{ route('home', ['mes' => $mesAnterior->month, 'anio' => $mesAnterior->year]) }}"
           class="btn btn-light border" title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
        <select name="mes" class="form-select" style="width:140px;">
            @foreach($meses as $i => $nombre)
                <option value="{{ $i + 1 }}" @selected($periodo['modo'] === 'mes' && $periodo['mes'] == $i + 1)>{{ $nombre }}</option>
            @endforeach
        </select>
        <input type="number" name="anio" class="form-control text-center" style="width:92px;"
               value="{{ $periodo['anio'] }}" min="2000" max="2999">
        <button class="btn btn-primary">Ir</button>
        <a href="{{ route('home', ['mes' => $mesSiguiente->month, 'anio' => $mesSiguiente->year]) }}"
           class="btn btn-light border" title="Mes siguiente"><i class="fas fa-chevron-right"></i></a>
    </form>

    <span class="text-muted d-none d-lg-inline" style="font-size:.8rem;">o</span>

    <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2">
        <input type="date" name="desde" class="form-control" style="width:150px;"
               value="{{ $periodo['modo'] === 'rango' ? $periodo['inicio']->toDateString() : '' }}" required>
        <span class="text-muted" style="font-size:.8rem;">a</span>
        <input type="date" name="hasta" class="form-control" style="width:150px;"
               value="{{ $periodo['modo'] === 'rango' ? $periodo['fin']->toDateString() : '' }}" required>
        <button class="btn btn-light border">Ver</button>
    </form>

    <div class="ms-auto d-flex align-items-center gap-2">
        @unless($esMesActual)
            <a href="{{ route('home') }}" class="btn btn-link text-muted px-2" style="font-size:.82rem;">Ir al mes actual</a>
        @endunless
        <span class="badge" style="background:var(--primary);">{{ $periodo['etiqueta'] }}</span>
    </div>
</div>

{{-- ── RESULTADO DEL PERÍODO ────────────────────────────────── --}}
{{-- Una sola cosa domina la pantalla, y es la pregunta que el dueño viene a
     hacerse. El desglose va al lado del número, no en otra tarjeta: es la
     explicación de cómo se llegó a él. --}}
@if($operacion)
@php
    $u = $operacion['utilidad_mes'];
    $conceptos = [
        'Ventas'           => ['monto' => $ventasPeriodo,                 'resta' => false],
        'Insumos'          => ['monto' => $totalGastoMes,                 'resta' => true],
        'Gastos variables' => ['monto' => $totalVariables,                'resta' => true],
        'Sueldos pagados'  => ['monto' => $operacion['planillas_periodo'],'resta' => true],
        'Gastos fijos'     => ['monto' => $operacion['fijos_periodo'],    'resta' => true],
    ];
@endphp
<div class="card mb-4" style="background:var(--primary); border:none; box-shadow:var(--shadow-md);">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="width:22px; height:2px; background:var(--oro); display:inline-block;"></span>
                    <span style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.1em; color:var(--oro);">
                        Resultado de {{ $periodo['etiqueta'] }}
                    </span>
                </div>
                <div style="font-family:Georgia,serif; font-size:2.9rem; font-weight:700; line-height:1; letter-spacing:-.015em; color:#fff; font-variant-numeric:tabular-nums;">
                    {{ $u < 0 ? '−' : '' }}Bs {{ number_format(abs($u), 2) }}
                </div>
                <div class="mt-2" style="font-size:.82rem; color:#9db2ca;">
                    {{ $u >= 0 ? 'de ganancia' : 'de pérdida' }} ·
                    <a href="{{ route('finanzas.index') }}" style="color:var(--oro-claro);">ver finanzas</a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ps-lg-4" style="border-left:1px solid rgba(255,255,255,.10);">
                    @foreach($conceptos as $concepto => $d)
                        @if($d['monto'] > 0)
                        <div class="d-flex justify-content-between align-items-baseline py-1
                                    {{ $loop->first ? 'pb-2 mb-1' : '' }}"
                             @if($loop->first) style="border-bottom:1px solid rgba(255,255,255,.08);" @endif>
                            <span style="font-size:.84rem; color:{{ $loop->first ? '#c7d6e6' : '#9db2ca' }};">{{ $concepto }}</span>
                            <span style="font-size:{{ $loop->first ? '.92rem' : '.86rem' }}; font-weight:{{ $loop->first ? '600' : '400' }}; color:{{ $loop->first ? '#fff' : '#c7d6e6' }}; font-variant-numeric:tabular-nums;">
                                {{ $d['resta'] ? '−' : '' }}Bs {{ number_format($d['monto'], 2) }}
                            </span>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── CIFRAS DEL PERÍODO ───────────────────────────────────── --}}
{{-- Tarjetas blancas con un filete de 3 px que dice de qué tipo de dato se
     trata. El color significa algo; antes cada tarjeta era un gradiente
     distinto y ninguna mandaba sobre las otras. --}}
@php
    $tiles = [
        [
            'label' => 'Ventas del período',
            'valor' => 'Bs ' . number_format($ventasPeriodo, 2),
            'sub'   => $cantidadVentas . ' ' . ($cantidadVentas === 1 ? 'venta' : 'ventas') . ' · ticket Bs ' . number_format($ticketPromedio, 2),
            'color' => 'var(--primary)',
        ],
        [
            'label' => 'Efectivo',
            'valor' => 'Bs ' . number_format($porPagoPeriodo['efectivo'], 2),
            'sub'   => 'se arquea en el cierre de caja',
            'color' => '#0d9488',
        ],
        [
            'label' => 'QR',
            'valor' => 'Bs ' . number_format($porPagoPeriodo['qr'], 2),
            'sub'   => 'va directo a la cuenta',
            'color' => '#0284c7',
        ],
    ];

    $tiles[] = $isCajero
        ? [
            'label' => 'Ventas hoy',
            'valor' => 'Bs ' . number_format($ventasHoy, 2),
            'sub'   => \Carbon\Carbon::today()->locale('es')->isoFormat('D [de] MMMM'),
            'color' => '#4f46e5',
        ]
        : [
            'label' => 'Salida de caja',
            'valor' => 'Bs ' . number_format($totalGastoMes + $totalVariables, 2),
            'sub'   => 'insumos + gastos variables',
            'color' => '#b45309',
        ];
@endphp
<div class="row g-3 mb-4">
    @foreach($tiles as $t)
    <div class="col-6 col-xl-3">
        <div class="card h-100" style="margin-bottom:0; border-top:3px solid {{ $t['color'] }};">
            <div class="card-body py-3">
                <div style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">
                    {{ $t['label'] }}
                </div>
                <div class="mt-1" style="font-size:1.55rem; font-weight:700; line-height:1.1; color:var(--text-heading); font-variant-numeric:tabular-nums;">
                    {{ $t['valor'] }}
                </div>
                <div class="mt-1 text-muted" style="font-size:.76rem;">{{ $t['sub'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── DISTRIBUCIÓN POR MEDIO DE PAGO ────────────────────────── --}}
@if($porPagoPeriodo['total'] > 0)
@php
    $pctEfectivo = round($porPagoPeriodo['efectivo'] / $porPagoPeriodo['total'] * 100);
    $pctQr       = 100 - $pctEfectivo;
@endphp
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0" style="font-size:.875rem;">Cómo se cobró en el período</h6>
            <span class="text-muted small">Bs {{ number_format($porPagoPeriodo['total'], 2) }} en total</span>
        </div>
        <div class="progress" style="height: 1.4rem;" role="img"
             aria-label="Efectivo {{ $pctEfectivo }}%, QR {{ $pctQr }}%">
            @if($porPagoPeriodo['efectivo'] > 0)
                <div class="progress-bar" style="width: {{ $pctEfectivo }}%; background-color: #0d9488;">{{ $pctEfectivo }}%</div>
            @endif
            @if($porPagoPeriodo['qr'] > 0)
                <div class="progress-bar" style="width: {{ $pctQr }}%; background-color: #0284c7;">{{ $pctQr }}%</div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── HOY Y PENDIENTES (solo administrador) ─────────────────── --}}
{{-- "Hoy" va aparte del período filtrado a propósito: mezclarlos hacía
     difícil saber a qué fechas correspondía cada número. --}}
@if($operacion)
<div class="row g-3 mb-4">

    {{-- Lo del día --}}
    <div class="col-lg-6">
        <div class="card h-100" style="margin-bottom:0;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-sun me-2"></i>Hoy</span>
                <span class="text-muted" style="font-size:.75rem;">
                    {{ \Carbon\Carbon::today()->locale('es')->isoFormat('D [de] MMMM') }}
                </span>
            </div>
            <div class="card-body">
                <div style="font-size:1.7rem; font-weight:700; line-height:1; color:var(--text-heading); font-variant-numeric:tabular-nums;">
                    Bs {{ number_format($ventasHoy, 2) }}
                </div>
                <div class="text-muted mt-1 mb-3" style="font-size:.78rem;">vendido en el día</div>

                <div class="d-flex flex-column gap-2">
                    {{-- Asistencia --}}
                    @if($operacion['es_domingo'])
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#cbd5e1;"></span>
                            <span class="text-muted flex-grow-1">Domingo — día no laborable</span>
                        </div>
                    @elseif(!$operacion['asistencia_tomada'])
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#b45309;"></span>
                            <span class="flex-grow-1">Falta tomar asistencia — {{ $operacion['empleados_activos'] }} sin registrar</span>
                            <a href="{{ route('asistencias.registrar') }}" class="fw-semibold">Tomar</a>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:{{ $operacion['ausentes_hoy'] > 0 ? '#b45309' : '#cbd5e1' }};"></span>
                            <span class="flex-grow-1">
                                {{ $operacion['presentes_hoy'] }} de {{ $operacion['empleados_activos'] }} presentes
                                @if($operacion['ausentes_hoy'] > 0)
                                    · {{ $operacion['ausentes_hoy'] }} ausente(s)
                                @endif
                            </span>
                            <a href="{{ route('asistencias.index') }}" class="text-muted">Ver</a>
                        </div>
                    @endif

                    {{-- Stock: estaban como dos tarjetas de gradiente propias, pero
                         son avisos de atención, no cifras del período. --}}
                    @if($insumosStockBajo > 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#b91c1c;"></span>
                            <span class="flex-grow-1">{{ $insumosStockBajo }} insumo(s) bajo el mínimo</span>
                            <a href="{{ route('insumos.index') }}" class="fw-semibold">Reponer</a>
                        </div>
                    @endif
                    @if($productosStockBajo > 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#b45309;"></span>
                            <span class="flex-grow-1">{{ $productosStockBajo }} producto(s) bajo el mínimo</span>
                            <a href="{{ route('productos.index') }}" class="text-muted">Ver</a>
                        </div>
                    @endif

                    @if($insumosStockBajo === 0 && $productosStockBajo === 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#15803d;"></span>
                            <span class="text-muted flex-grow-1">Stock de productos e insumos en orden</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Pendientes de pago --}}
    <div class="col-lg-6">
        <div class="card h-100" style="margin-bottom:0;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2"></i>Por pagar este mes</span>
                <a href="{{ route('gastos-pagos.index') }}" class="text-muted" style="font-size:.75rem;">Ver gastos</a>
            </div>
            <div class="card-body">
                @if($operacion['gastos_pendientes'] === 0)
                    <div style="font-size:1.7rem; font-weight:700; line-height:1; color:#15803d;">Al día</div>
                    <div class="text-muted mt-1 mb-3" style="font-size:.78rem;">No hay gastos pendientes este mes</div>
                @else
                    <div style="font-size:1.7rem; font-weight:700; line-height:1; font-variant-numeric:tabular-nums;
                                color:{{ $operacion['gastos_vencidos'] > 0 ? '#b91c1c' : '#b45309' }};">
                        Bs {{ number_format($operacion['monto_por_pagar'], 2) }}
                    </div>
                    <div class="text-muted mt-1 mb-3" style="font-size:.78rem;">
                        {{ $operacion['gastos_pendientes'] }} gasto(s) sin pagar
                    </div>
                @endif

                <div class="d-flex flex-column gap-2">
                    @if($operacion['gastos_vencidos'] > 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#b91c1c;"></span>
                            <span class="flex-grow-1">{{ $operacion['gastos_vencidos'] }} ya pasaron su vencimiento</span>
                            <a href="{{ route('gastos-pagos.index') }}" class="fw-semibold">Pagar</a>
                        </div>
                    @endif
                    @if($operacion['planillas_borrador'] > 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#b45309;"></span>
                            <span class="flex-grow-1">{{ $operacion['planillas_borrador'] }} planilla(s) en borrador sin cerrar</span>
                            <a href="{{ route('planillas.index') }}" class="text-muted">Revisar</a>
                        </div>
                    @endif
                    @if($operacion['adelantos_pend'] > 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#cbd5e1;"></span>
                            <span class="text-muted flex-grow-1">
                                Bs {{ number_format($operacion['adelantos_pend'], 2) }} en adelantos por descontar
                            </span>
                        </div>
                    @endif
                    @if($operacion['gastos_vencidos'] === 0 && $operacion['planillas_borrador'] === 0 && $operacion['adelantos_pend'] == 0)
                        <div class="d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <span class="rounded-circle flex-shrink-0" style="width:7px; height:7px; background:#15803d;"></span>
                            <span class="text-muted flex-grow-1">Nada vencido ni pendiente de cerrar</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── GRÁFICAS ROW 1 ───────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    {{-- Gráfica de ventas --}}
    <div class="col-lg-8">
        <div class="card h-100" style="margin-bottom:0">
            <div class="card-header">
                <i class="fas fa-chart-line me-2"></i>Ventas — {{ $periodo['etiqueta'] }}
            </div>
            <div class="card-body" style="padding:20px">
                <canvas id="ventasChart" height="90"></canvas>
            </div>
        </div>
    </div>

    {{-- Top 5 productos --}}
    <div class="col-lg-4">
        <div class="card h-100" style="margin-bottom:0">
            <div class="card-header">
                <i class="fas fa-trophy me-2"></i>Top 5 Productos
            </div>
            <div class="card-body p-0">
                @if($productosMasVendidos->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($productosMasVendidos as $i => $producto)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill" style="
                                background: {{ ['#6366f1','#8b5cf6','#10b981','#f59e0b','#ef4444'][$i] }};
                                width:22px; height:22px; display:flex; align-items:center; justify-content:center; font-size:.7rem; padding:0">
                                {{ $i + 1 }}
                            </span>
                            <span>{{ $producto->nombre }}</span>
                        </div>
                        <span class="badge bg-primary rounded-pill">{{ $producto->total_vendido }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chart-pie fa-2x mb-2 d-block opacity-25"></i>
                    Sin datos disponibles
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(!$isCajero)
{{-- ── GRÁFICAS ROW 2 (solo admin) ─────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100" style="margin-bottom:0">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i>Egresos directos — {{ $periodo['etiqueta'] }}
            </div>
            <div class="card-body" style="padding:20px">
                <canvas id="comprasChart" height="90"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100" style="margin-bottom:0">
            <div class="card-header">
                <i class="fas fa-clock-rotate-left me-2"></i>Últimas Compras
            </div>
            <div class="card-body p-0">
                @if($ultimasCompras->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>
                    Sin compras registradas.
                </div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($ultimasCompras as $compra)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div style="min-width:0">
                                <div class="fw-600 text-truncate" style="font-size:.845rem; font-weight:600">
                                    {{ $compra->insumo->nombre }}
                                </div>
                                <small class="text-muted">
                                    {{ number_format($compra->cantidad, 2) }} {{ $compra->insumo->unidad_medida }}
                                    &middot; {{ $compra->fecha->format('d/m/Y') }}
                                </small>
                            </div>
                            <span class="badge bg-warning text-dark flex-shrink-0">
                                Bs {{ number_format($compra->total, 2) }}
                            </span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('insumos.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-cart-shopping me-1"></i>Ir a Insumos
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── ÚLTIMAS VENTAS ───────────────────────────────────────── --}}
<div class="card" style="margin-bottom:0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-receipt me-2"></i>Últimas Ventas del Período</span>
        @can('ver-ventas')
        <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-outline-primary">
            Ver todas <i class="fas fa-arrow-right ms-1" style="font-size:.7rem"></i>
        </a>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cajero</th>
                        <th>Fecha</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimasVentas as $venta)
                    <tr>
                        <td><strong>{{ $venta->numero_venta }}</strong></td>
                        <td>{{ $venta->user->name }}</td>
                        <td class="text-muted">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end fw-600">Bs {{ number_format($venta->total, 2) }}</td>
                        <td>
                            @if($venta->estado == 'completada')
                                <span class="badge bg-success">Completada</span>
                            @else
                                <span class="badge bg-danger">Cancelada</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-receipt fa-2x mb-2 d-block opacity-25"></i>
                            No hay ventas registradas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Chart.js llega en el bundle de Vite, que es un módulo y por lo tanto se
// ejecuta diferido: cuando este script corre, `Chart` todavía no existe y los
// gráficos quedaban en blanco. Los módulos sí terminan antes de
// DOMContentLoaded, así que ese es el momento seguro para dibujarlos.
document.addEventListener('DOMContentLoaded', () => {

// Gráfica de Ventas
const ctxVentas = document.getElementById('ventasChart');
if (ctxVentas) {
    new Chart(ctxVentas, {
        type: 'line',
        data: {
            labels: @json($fechasUltimos7Dias),
            datasets: [{
                label: 'Ventas (Bs)',
                data: @json($ventasUltimos7Dias),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ' Bs ' + ctx.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#94a3b8' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', lineWidth: 1 },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        callback: v => 'Bs ' + v
                    }
                }
            }
        }
    });
}

// Gráfica de Compras (solo admin)
const ctxCompras = document.getElementById('comprasChart');
if (ctxCompras) {
    new Chart(ctxCompras, {
        type: 'bar',
        data: {
            labels: @json($fechasCompras ?? []),
            datasets: [{
                label: 'Insumos',
                data: @json($gastosCompras ?? []),
                backgroundColor: 'rgba(139,92,246,0.75)',
                borderColor: '#8b5cf6',
                borderWidth: 1,
                borderRadius: 5,
                borderSkipped: false,
            }, {
                label: 'Gastos variables',
                data: @json($gastosVariables ?? []),
                backgroundColor: 'rgba(249,115,22,0.75)',
                borderColor: '#f97316',
                borderWidth: 1,
                borderRadius: 5,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 }, color: '#64748b' }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': Bs ' + ctx.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { maxTicksLimit: 10, font: { size: 11 }, color: '#94a3b8' }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', lineWidth: 1 },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        callback: v => 'Bs ' + v
                    }
                }
            }
        }
    });
}

});
</script>
@endpush
