@extends('layouts.app')

@section('page-title', 'Reportes')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <h4><i class="fas fa-chart-bar me-2"></i>Centro de Reportes</h4>
        <p class="text-muted">Genere y visualice reportes del sistema</p>
    </div>
</div>

<div class="row">
    <!-- Reporte de Ventas -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shopping-cart fa-3x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Reporte de Ventas</h5>
                        <p class="card-text text-muted mb-0">Consulte las ventas por rango de fechas</p>
                    </div>
                </div>
                <form action="{{ route('reportes.ventas') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label for="fecha_inicio_ventas" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio_ventas" name="fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_fin_ventas" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin_ventas" name="fecha_fin" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="tipo" value="web" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i>Ver Reporte
                        </button>
                        <button type="submit" name="tipo" value="pdf" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-2"></i>Descargar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reporte de Productos Más Vendidos -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-trophy fa-3x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Productos Más Vendidos</h5>
                        <p class="card-text text-muted mb-0">Top de productos por ventas</p>
                    </div>
                </div>
                <form action="{{ route('reportes.productos-mas-vendidos') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label for="fecha_inicio_productos" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio_productos" name="fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_fin_productos" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin_productos" name="fecha_fin" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="tipo" value="web" class="btn btn-warning">
                            <i class="fas fa-eye me-2"></i>Ver Reporte
                        </button>
                        <button type="submit" name="tipo" value="pdf" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-2"></i>Descargar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reporte de Inventario -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-boxes fa-3x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Reporte de Inventario</h5>
                        <p class="card-text text-muted mb-0">Estado actual del inventario</p>
                    </div>
                </div>
                <p class="mb-3">Visualice el estado actual de productos e insumos en inventario, incluyendo alertas de stock bajo.</p>
                <div class="d-grid">
                    <a href="{{ route('reportes.inventario') }}" class="btn btn-success" target="_blank">
                        <i class="fas fa-eye me-2"></i>Ver Reporte
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte de Cortes de Caja -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cash-register fa-3x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Reporte de Cortes de Caja</h5>
                        <p class="card-text text-muted mb-0">Historial de cortes y arqueos</p>
                    </div>
                </div>
                <form action="{{ route('reportes.cortes-caja') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label for="fecha_inicio_cortes" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio_cortes" name="fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_fin_cortes" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin_cortes" name="fecha_fin" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="tipo" value="web" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Ver Reporte
                        </button>
                        <button type="submit" name="tipo" value="pdf" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-2"></i>Descargar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Establecer fecha de hoy por defecto
    const today = new Date().toISOString().split('T')[0];
    const lastWeek = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    document.getElementById('fecha_inicio_ventas').value = lastWeek;
    document.getElementById('fecha_fin_ventas').value = today;
    
    document.getElementById('fecha_inicio_productos').value = lastWeek;
    document.getElementById('fecha_fin_productos').value = today;
    
    document.getElementById('fecha_inicio_cortes').value = lastWeek;
    document.getElementById('fecha_fin_cortes').value = today;
</script>
@endpush
