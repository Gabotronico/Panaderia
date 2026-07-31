@extends('layouts.app')

@section('page-title', 'Reporte de Cortes de Caja')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-cash-register me-2"></i>Reporte de Cortes de Caja</h4>
            <a href="{{ route('reportes.cortes-caja', ['fecha_inicio' => $request->fecha_inicio, 'fecha_fin' => $request->fecha_fin, 'tipo' => 'pdf']) }}" 
               class="btn btn-danger" 
               target="_blank">
                <i class="fas fa-file-pdf me-2"></i>Descargar PDF
            </a>
        </div>
    </div>
</div>

<!-- Resumen del Periodo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Período: {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Total Cortes</h6>
                            <h3 class="text-primary">{{ $cortes->count() }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Monto Inicial Total</h6>
                            <h3 class="text-info">Bs{{ number_format($totalInicial, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Total Ventas</h6>
                            <h3 class="text-success">Bs{{ number_format($totalVentas, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Diferencia Total</h6>
                            <h3 class="text-{{ $totalDiferencia >= 0 ? 'success' : 'danger' }}">
                                @if($totalDiferencia >= 0)
                                    +Bs{{ number_format($totalDiferencia, 2) }}
                                @else
                                    Bs{{ number_format($totalDiferencia, 2) }}
                                @endif
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de Cortes -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Detalle de Cortes de Caja
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cajero</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th class="text-end">Monto Inicial</th>
                                <th class="text-end">Total Ventas</th>
                                <th class="text-end">Efectivo Contado</th>
                                <th class="text-end">QR</th>
                                <th class="text-end">Monto Final</th>
                                <th class="text-end">Diferencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cortes as $corte)
                                <tr>
                                    <td>{{ $corte->id }}</td>
                                    <td>{{ $corte->fecha_corte->format('d/m/Y') }}</td>
                                    <td>{{ $corte->user->name }}</td>
                                    <td>{{ $corte->hora_apertura }}</td>
                                    <td>{{ $corte->hora_cierre ?? '-' }}</td>
                                    <td class="text-end">Bs{{ number_format($corte->monto_inicial, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($corte->total_ventas, 2) }}</td>
                                    <td class="text-end">
                                        @if($corte->estado == 'cerrado')
                                            Bs{{ number_format($corte->total_efectivo, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($corte->estado == 'cerrado')
                                            Bs{{ number_format($corte->total_qr, 2) }}
                                            @if(abs($corte->diferencia_qr_real) >= 0.01)
                                                <span class="badge bg-{{ $corte->diferencia_qr_real < 0 ? 'danger' : 'warning' }}"
                                                      title="Ventas por QR: Bs{{ number_format($corte->ventas_qr, 2) }}">
                                                    {{ $corte->diferencia_qr_real < 0 ? '−' : '+' }}Bs{{ number_format(abs($corte->diferencia_qr_real), 2) }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($corte->estado == 'cerrado')
                                            Bs{{ number_format($corte->monto_final, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($corte->estado == 'cerrado')
                                            @php $dif = $corte->diferencia_efectivo; @endphp
                                            @if(abs($dif) < 0.01)
                                                <span class="badge bg-success">Bs0.00</span>
                                            @elseif($dif > 0)
                                                <span class="badge bg-info">+Bs{{ number_format($dif, 2) }}</span>
                                            @else
                                                <span class="badge bg-danger">Bs{{ number_format($dif, 2) }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($corte->estado == 'abierto')
                                            <span class="badge bg-success">Abierto</span>
                                        @else
                                            <span class="badge bg-secondary">Cerrado</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted">No hay cortes en el período seleccionado</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Totales:</th>
                                <th class="text-end">Bs{{ number_format($totalInicial, 2) }}</th>
                                <th class="text-end">Bs{{ number_format($totalVentas, 2) }}</th>
                                <th class="text-end">Bs{{ number_format($totalEfectivo, 2) }}</th>
                                <th class="text-end">Bs{{ number_format($totalQr, 2) }}</th>
                                <th class="text-end">Bs{{ number_format($cortes->where('estado', 'cerrado')->sum('monto_final'), 2) }}</th>
                                <th class="text-end">
                                    <span class="badge bg-{{ $totalDiferencia >= 0 ? 'success' : 'danger' }}">
                                        @if($totalDiferencia >= 0)
                                            +Bs{{ number_format($totalDiferencia, 2) }}
                                        @else
                                            Bs{{ number_format($totalDiferencia, 2) }}
                                        @endif
                                    </span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Análisis de Diferencias -->
@if($cortes->where('estado', 'cerrado')->count() > 0)
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Estado de Cortes
            </div>
            <div class="card-body">
                <canvas id="estadoChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle me-2"></i>Análisis de Diferencias
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Cortes Cuadrados:</span>
                        <span class="badge bg-success fs-6">{{ $cortesCuadrados }}</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" 
                             style="width: {{ $cortes->where('estado', 'cerrado')->count() > 0 ? ($cortesCuadrados / $cortes->where('estado', 'cerrado')->count()) * 100 : 0 }}%">
                            {{ $cortes->where('estado', 'cerrado')->count() > 0 ? number_format(($cortesCuadrados / $cortes->where('estado', 'cerrado')->count()) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Cortes con Sobrante:</span>
                        <span class="badge bg-warning text-dark fs-6">{{ $cortesConSobrante }}</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-warning" 
                             style="width: {{ $cortes->where('estado', 'cerrado')->count() > 0 ? ($cortesConSobrante / $cortes->where('estado', 'cerrado')->count()) * 100 : 0 }}%">
                            {{ $cortes->where('estado', 'cerrado')->count() > 0 ? number_format(($cortesConSobrante / $cortes->where('estado', 'cerrado')->count()) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Cortes con Faltante:</span>
                        <span class="badge bg-danger fs-6">{{ $cortesConFaltante }}</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-danger" 
                             style="width: {{ $cortes->where('estado', 'cerrado')->count() > 0 ? ($cortesConFaltante / $cortes->where('estado', 'cerrado')->count()) * 100 : 0 }}%">
                            {{ $cortes->where('estado', 'cerrado')->count() > 0 ? number_format(($cortesConFaltante / $cortes->where('estado', 'cerrado')->count()) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>

                <hr>

                <div class="alert alert-info mb-0">
                    <small>
                        <strong><i class="fas fa-info-circle me-1"></i>Criterios de clasificación:</strong><br>
                        • <strong>Cuadrado:</strong> Diferencia = Monto Inicial<br>
                        • <strong>Sobrante:</strong> Diferencia {'>'} Monto Inicial<br>
                        • <strong>Faltante:</strong> Diferencia {'<'} Monto Inicial
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('estadoChart');
    if (ctx) {
        const cortesCuadrados = {{ $cortesCuadrados }};
        const cortesConSobrante = {{ $cortesConSobrante }};
        const cortesConFaltante = {{ $cortesConFaltante }};
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Cuadrados', 'Con Sobrante', 'Con Faltante'],
                datasets: [{
                    data: [cortesCuadrados, cortesConSobrante, cortesConFaltante],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush