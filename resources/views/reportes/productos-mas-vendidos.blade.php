@extends('layouts.app')

@section('page-title', 'Productos Más Vendidos')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-trophy me-2"></i>Productos Más Vendidos</h4>
            <a href="{{ route('reportes.productos-mas-vendidos', ['fecha_inicio' => $request->fecha_inicio, 'fecha_fin' => $request->fecha_fin, 'tipo' => 'pdf']) }}" 
               class="btn btn-danger" 
               target="_blank">
                <i class="fas fa-file-pdf me-2"></i>Descargar PDF
            </a>
        </div>
    </div>
</div>

<!-- Período -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-calendar me-2"></i>
            <strong>Período:</strong> {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}
        </div>
    </div>
</div>

<!-- Top 10 Productos -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list-ol me-2"></i>Ranking de Productos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th class="text-center">Unidades Vendidas</th>
                                <th class="text-end">Ingresos Generados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $index => $producto)
                                <tr>
                                    <td>
                                        @if($index < 3)
                                            <i class="fas fa-medal text-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}"></i>
                                        @endif
                                        {{ $index + 1 }}
                                    </td>
                                    <td><strong>{{ $producto->nombre }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $producto->total_vendido }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">Bs{{ number_format($producto->ingresos, 2) }}</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay datos disponibles</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total:</th>
                                <th class="text-center">{{ $productos->sum('total_vendido') }}</th>
                                <th class="text-end">Bs{{ number_format($productos->sum('ingresos'), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Top 5
            </div>
            <div class="card-body">
                <canvas id="productosChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('productosChart');
    if (ctx) {
        const productos = @json($productos->take(5));
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: productos.map(p => p.nombre),
                datasets: [{
                    data: productos.map(p => p.total_vendido),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
@endpush