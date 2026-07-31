@extends('layouts.app')

@section('page-title', 'Reporte de Ventas')

@section('content')
@php
    $ini  = \Carbon\Carbon::parse($request->fecha_inicio);
    $fin  = \Carbon\Carbon::parse($request->fecha_fin);
    $dias = $ini->diffInDays($fin) + 1;

    $totalMedios = $porPago['efectivo'] + $porPago['qr'];
    $pctEfectivo = $totalMedios > 0 ? round($porPago['efectivo'] / $totalMedios * 100) : 0;
    $pctQr       = $totalMedios > 0 ? 100 - $pctEfectivo : 0;
@endphp

{{-- ── CABECERA ────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Reporte de Ventas</h4>
        <div class="text-muted small">
            Del <strong>{{ $ini->format('d/m/Y') }}</strong> al <strong>{{ $fin->format('d/m/Y') }}</strong>
            · {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }} · solo ventas completadas
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Reportes
        </a>
        <a href="{{ route('reportes.ventas', ['fecha_inicio' => $request->fecha_inicio, 'fecha_fin' => $request->fecha_fin, 'tipo' => 'pdf']) }}"
           class="btn btn-danger">
            <i class="fas fa-file-pdf me-2"></i>Descargar PDF
        </a>
    </div>
</div>

{{-- ── INDICADORES ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #1e3a5f !important;">
            <div class="card-body">
                <div class="text-uppercase text-muted" style="font-size:.68rem; letter-spacing:.06em;">Facturación total</div>
                <div class="fw-bold mt-1" style="font-size:1.6rem; color:#1e3a5f;">Bs {{ number_format($totalVentas, 2) }}</div>
                <div class="text-muted small">{{ $cantidadVentas }} {{ $cantidadVentas === 1 ? 'venta' : 'ventas' }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #6366f1 !important;">
            <div class="card-body">
                <div class="text-uppercase text-muted" style="font-size:.68rem; letter-spacing:.06em;">Ticket promedio</div>
                <div class="fw-bold mt-1" style="font-size:1.6rem; color:#4f46e5;">Bs {{ number_format($ticketPromedio, 2) }}</div>
                <div class="text-muted small">por operación</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #0d9488 !important;">
            <div class="card-body">
                <div class="text-uppercase text-muted" style="font-size:.68rem; letter-spacing:.06em;">
                    <i class="fas fa-money-bill-wave me-1"></i>Efectivo
                </div>
                <div class="fw-bold mt-1" style="font-size:1.6rem; color:#0d9488;">Bs {{ number_format($porPago['efectivo'], 2) }}</div>
                <div class="text-muted small">{{ $pctEfectivo }}% del total</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #0284c7 !important;">
            <div class="card-body">
                <div class="text-uppercase text-muted" style="font-size:.68rem; letter-spacing:.06em;">
                    <i class="fas fa-qrcode me-1"></i>QR
                </div>
                <div class="fw-bold mt-1" style="font-size:1.6rem; color:#0284c7;">Bs {{ number_format($porPago['qr'], 2) }}</div>
                <div class="text-muted small">{{ $pctQr }}% del total</div>
            </div>
        </div>
    </div>
</div>

@if($cantidadVentas === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-receipt fa-2x mb-3 opacity-50"></i>
            <p class="mb-0">No se registraron ventas completadas en el período seleccionado.</p>
        </div>
    </div>
@else

<div class="row g-3 mb-4">
    {{-- ── VENTAS POR CAJERO ──────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <span class="fw-semibold"><i class="fas fa-user-tie me-2 text-muted"></i>Ventas por cajero</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cajero</th>
                                <th class="text-center">Ventas</th>
                                <th class="text-end">Total</th>
                                <th style="min-width:120px;">Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($porCajero as $nombre => $datos)
                            @php $pct = $totalVentas > 0 ? round($datos['total'] / $totalVentas * 100) : 0; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $nombre }}</div>
                                    <small class="text-muted">
                                        <i class="fas fa-money-bill-wave text-success"></i> Bs {{ number_format($datos['efectivo'], 2) }}
                                        <span class="mx-1">·</span>
                                        <i class="fas fa-qrcode text-primary"></i> Bs {{ number_format($datos['qr'], 2) }}
                                    </small>
                                </td>
                                <td class="text-center">{{ $datos['cantidad'] }}</td>
                                <td class="text-end fw-semibold">Bs {{ number_format($datos['total'], 2) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:.5rem;">
                                            <div class="progress-bar" style="width: {{ $pct }}%; background-color:#1e3a5f;"></div>
                                        </div>
                                        <small class="text-muted" style="min-width:2.2rem;">{{ $pct }}%</small>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── EVOLUCIÓN DIARIA ───────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <span class="fw-semibold"><i class="fas fa-calendar-day me-2 text-muted"></i>Evolución diaria</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 320px;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Día</th>
                                <th class="text-center">Ventas</th>
                                <th class="text-end">Facturado</th>
                                <th style="min-width:110px;">Peso</th>
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
                                    <div class="fw-semibold">{{ $fecha->format('d/m/Y') }}</div>
                                    <small class="text-muted text-capitalize">{{ $fecha->locale('es')->isoFormat('dddd') }}</small>
                                    @if($dia === $mejorDia && $porDia->count() > 1)
                                        <span class="badge bg-success ms-1" style="font-size:.6rem;">mejor día</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $datos['cantidad'] }}</td>
                                <td class="text-end fw-semibold">Bs {{ number_format($datos['total'], 2) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:.5rem;">
                                            <div class="progress-bar" style="width: {{ $pct }}%; background-color:#0284c7;"></div>
                                        </div>
                                        <small class="text-muted" style="min-width:2.2rem;">{{ $pct }}%</small>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── DETALLE DE VENTAS ───────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="fas fa-receipt me-2 text-muted"></i>Detalle de ventas</span>
        <span class="text-muted small">{{ $cantidadVentas }} registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N.º venta</th>
                        <th>Fecha y hora</th>
                        <th>Cajero</th>
                        <th class="text-center">Pago</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Descuento</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventas as $venta)
                    <tr>
                        <td><code class="text-body">{{ $venta->numero_venta }}</code></td>
                        <td>
                            {{ $venta->created_at->format('d/m/Y') }}
                            <small class="text-muted">{{ $venta->created_at->format('H:i') }}</small>
                        </td>
                        <td>{{ $venta->user->name ?? '—' }}</td>
                        <td class="text-center">
                            @if($venta->tipo_pago === 'qr')
                                <span class="badge bg-primary"><i class="fas fa-qrcode me-1"></i>QR</span>
                            @else
                                <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</span>
                            @endif
                        </td>
                        <td class="text-end">Bs {{ number_format($venta->subtotal, 2) }}</td>
                        <td class="text-end {{ $venta->descuento > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $venta->descuento > 0 ? '−Bs ' . number_format($venta->descuento, 2) : '—' }}
                        </td>
                        <td class="text-end fw-bold">Bs {{ number_format($venta->total, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-sm btn-outline-secondary" title="Ver venta">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">TOTALES:</td>
                        <td class="text-end">Bs {{ number_format($totalSubtotal, 2) }}</td>
                        <td class="text-end text-danger">−Bs {{ number_format($totalDescuentos, 2) }}</td>
                        <td class="text-end fs-6">Bs {{ number_format($totalVentas, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endif
@endsection
