@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')

@if($isCajero)
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Vista de Cajero:</strong> Estás viendo solo la información de tus ventas y cortes de caja.
</div>
@endif

{{-- ── STAT CARDS ────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Ventas Hoy --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 20px rgba(16,185,129,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Ventas Hoy</p>
                        <div class="stat-card-value">Bs {{ number_format($ventasHoy, 2) }}</div>
                        <div class="stat-card-sub">ingresos del día</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Efectivo Hoy — es la plata que se arquea en el cierre de caja --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); box-shadow: 0 6px 20px rgba(20,184,166,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Efectivo Hoy</p>
                        <div class="stat-card-value">Bs {{ number_format($porPagoHoy['efectivo'], 2) }}</div>
                        <div class="stat-card-sub">
                            mes: Bs {{ number_format($porPagoMes['efectivo'], 2) }}
                        </div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QR Hoy — no pasa por el cajón, va directo a la cuenta --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); box-shadow: 0 6px 20px rgba(14,165,233,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">QR Hoy</p>
                        <div class="stat-card-value">Bs {{ number_format($porPagoHoy['qr'], 2) }}</div>
                        <div class="stat-card-sub">
                            mes: Bs {{ number_format($porPagoMes['qr'], 2) }}
                        </div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ventas del Mes --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); box-shadow: 0 6px 20px rgba(99,102,241,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Ventas del Mes</p>
                        <div class="stat-card-value">Bs {{ number_format($ventasMes, 2) }}</div>
                        <div class="stat-card-sub">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM Y') }}</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Productos con stock bajo --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 6px 20px rgba(245,158,11,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Productos Stock Bajo</p>
                        <div class="stat-card-value">{{ $productosStockBajo }}</div>
                        <div class="stat-card-sub">requieren atención</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-cookie-bite"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Insumos con stock bajo --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 6px 20px rgba(239,68,68,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Insumos Stock Bajo</p>
                        <div class="stat-card-value">{{ $insumosStockBajo }}</div>
                        <div class="stat-card-sub">requieren reposición</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isCajero)
    {{-- Gasto en compras mes --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 6px 20px rgba(139,92,246,.28);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-card-label">Compras este Mes</p>
                        <div class="stat-card-value">Bs {{ number_format($totalGastoMes, 2) }}</div>
                        <div class="stat-card-sub">gasto en insumos</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ── DISTRIBUCIÓN POR MEDIO DE PAGO ────────────────────────── --}}
@if($porPagoHoy['total'] > 0)
@php
    $pctEfectivoHoy = round($porPagoHoy['efectivo'] / $porPagoHoy['total'] * 100);
    $pctQrHoy       = 100 - $pctEfectivoHoy;
@endphp
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="fas fa-scale-balanced me-2"></i>Cómo se cobró hoy</h6>
            <span class="text-muted small">Bs {{ number_format($porPagoHoy['total'], 2) }} en total</span>
        </div>
        <div class="progress" style="height: 1.5rem;" role="img"
             aria-label="Efectivo {{ $pctEfectivoHoy }}%, QR {{ $pctQrHoy }}%">
            @if($porPagoHoy['efectivo'] > 0)
            <div class="progress-bar" style="width: {{ $pctEfectivoHoy }}%; background-color: #0d9488;">
                {{ $pctEfectivoHoy }}%
            </div>
            @endif
            @if($porPagoHoy['qr'] > 0)
            <div class="progress-bar" style="width: {{ $pctQrHoy }}%; background-color: #0284c7;">
                {{ $pctQrHoy }}%
            </div>
            @endif
        </div>
        <div class="d-flex gap-4 mt-2 small">
            <span>
                <i class="fas fa-money-bill-wave me-1" style="color:#0d9488;"></i>
                Efectivo <strong>Bs {{ number_format($porPagoHoy['efectivo'], 2) }}</strong>
                <span class="text-muted">— se arquea en el cierre de caja</span>
            </span>
            <span>
                <i class="fas fa-qrcode me-1" style="color:#0284c7;"></i>
                QR <strong>Bs {{ number_format($porPagoHoy['qr'], 2) }}</strong>
                <span class="text-muted">— va directo a la cuenta</span>
            </span>
        </div>
    </div>
</div>
@endif

{{-- ── PANEL DE OPERACIÓN (solo administrador) ──────────────── --}}
@if($operacion)
<div class="row g-3 mb-4">

    {{-- Resultado del mes --}}
    <div class="col-lg-4">
        <a href="{{ route('finanzas.index') }}" class="card h-100 text-decoration-none"
           style="margin-bottom:0;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chart-pie me-2"></i>Resultado del mes</span>
                <i class="fas fa-arrow-right text-muted" style="font-size:.75rem;"></i>
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-center">
                @php $u = $operacion['utilidad_mes']; @endphp
                <div class="fw-bold mb-1"
                     style="font-size:1.75rem; line-height:1; color: {{ $u >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ $u < 0 ? '−' : '' }}Bs {{ number_format(abs($u), 2) }}
                </div>
                <div class="text-muted" style="font-size:.8rem;">
                    {{ $u >= 0 ? 'de ganancia' : 'de pérdida' }} en
                    {{ \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM') }}
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                    <span class="text-muted">Ventas</span>
                    <span class="text-success fw-semibold">Bs {{ number_format($ventasMes, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                    <span class="text-muted">Insumos + sueldos + gastos</span>
                    <span class="text-danger fw-semibold">
                        Bs {{ number_format($ventasMes - $u, 2) }}
                    </span>
                </div>
            </div>
        </a>
    </div>

    {{-- Personal hoy --}}
    <div class="col-lg-4">
        <div class="card h-100" style="margin-bottom:0;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Personal hoy</span>
                <a href="{{ route('empleados.index') }}" class="text-muted" style="font-size:.75rem;">Ver todos</a>
            </div>
            <div class="card-body">
                @if($operacion['es_domingo'])
                    <div class="text-center py-3">
                        <div class="mb-2" style="font-size:1.6rem;">🛌</div>
                        <div class="fw-semibold">Hoy es domingo</div>
                        <div class="text-muted" style="font-size:.8rem;">Día no laborable</div>
                    </div>
                @elseif(!$operacion['asistencia_tomada'])
                    <div class="text-center py-2">
                        <div class="text-warning mb-2"><i class="fas fa-clipboard-list fa-2x"></i></div>
                        <div class="fw-semibold mb-1">Falta tomar asistencia</div>
                        <div class="text-muted mb-3" style="font-size:.8rem;">
                            {{ $operacion['empleados_activos'] }} empleado(s) sin registrar hoy
                        </div>
                        <a href="{{ route('asistencias.registrar') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-clipboard-check me-1"></i>Tomar asistencia
                        </a>
                    </div>
                @else
                    <div class="row text-center g-2 mb-3">
                        <div class="col-4">
                            <div class="fw-bold text-success" style="font-size:1.5rem;">
                                {{ $operacion['presentes_hoy'] }}
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">presentes</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold {{ $operacion['ausentes_hoy'] > 0 ? 'text-danger' : 'text-muted' }}"
                                 style="font-size:1.5rem;">
                                {{ $operacion['ausentes_hoy'] }}
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">ausentes</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-muted" style="font-size:1.5rem;">
                                {{ $operacion['empleados_activos'] }}
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">en planilla</div>
                        </div>
                    </div>
                    <a href="{{ route('asistencias.index') }}" class="btn btn-light border btn-sm w-100">
                        Ver detalle del día
                    </a>
                @endif

                @if($operacion['adelantos_pend'] > 0)
                    <div class="alert alert-warning py-2 mt-3 mb-0" style="font-size:.78rem;">
                        <i class="fas fa-hand-holding-dollar me-1"></i>
                        Bs {{ number_format($operacion['adelantos_pend'], 2) }} en adelantos
                        por descontar en la próxima planilla.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Pendientes de pago --}}
    <div class="col-lg-4">
        <div class="card h-100" style="margin-bottom:0;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2"></i>Por pagar este mes</span>
                <a href="{{ route('gastos-pagos.index') }}" class="text-muted" style="font-size:.75rem;">Ver gastos</a>
            </div>
            <div class="card-body">
                @if($operacion['gastos_pendientes'] === 0)
                    <div class="text-center py-3">
                        <div class="text-success mb-2"><i class="fas fa-circle-check fa-2x"></i></div>
                        <div class="fw-semibold">Todo al día</div>
                        <div class="text-muted" style="font-size:.8rem;">
                            No hay gastos pendientes este mes
                        </div>
                    </div>
                @else
                    <div class="text-center mb-3">
                        <div class="fw-bold {{ $operacion['gastos_vencidos'] > 0 ? 'text-danger' : 'text-warning' }}"
                             style="font-size:1.75rem; line-height:1;">
                            Bs {{ number_format($operacion['monto_por_pagar'], 2) }}
                        </div>
                        <div class="text-muted" style="font-size:.8rem;">
                            {{ $operacion['gastos_pendientes'] }} gasto(s) sin pagar
                        </div>
                    </div>

                    @if($operacion['gastos_vencidos'] > 0)
                        <div class="alert alert-danger py-2 mb-2" style="font-size:.78rem;">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            <strong>{{ $operacion['gastos_vencidos'] }}</strong> ya pasaron su fecha de vencimiento.
                        </div>
                    @endif

                    <a href="{{ route('gastos-pagos.index') }}" class="btn btn-light border btn-sm w-100">
                        Registrar pagos
                    </a>
                @endif

                @if($operacion['planillas_borrador'] > 0)
                    <div class="alert alert-info py-2 mt-2 mb-0" style="font-size:.78rem;">
                        <i class="fas fa-file-pen me-1"></i>
                        {{ $operacion['planillas_borrador'] }} planilla(s) en borrador sin cerrar.
                        <a href="{{ route('planillas.index') }}">Revisar</a>
                    </div>
                @endif
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
                <i class="fas fa-chart-line me-2"></i>Ventas — Últimos 7 Días
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
                <i class="fas fa-chart-bar me-2"></i>Gasto en Compras de Insumos — Últimos 30 Días
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
        <span><i class="fas fa-receipt me-2"></i>Últimas Ventas</span>
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
                label: 'Gasto (Bs)',
                data: @json($gastosCompras ?? []),
                backgroundColor: 'rgba(139,92,246,0.75)',
                borderColor: '#8b5cf6',
                borderWidth: 1,
                borderRadius: 5,
                borderSkipped: false,
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
                    ticks: { maxTicksLimit: 10, font: { size: 11 }, color: '#94a3b8' }
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
</script>
@endpush
