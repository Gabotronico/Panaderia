@extends('layouts.app')

@section('page-title', 'Reporte de Inventario')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-boxes me-2"></i>Reporte de Inventario</h4>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Resumen General -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Productos</h6>
                <h3 class="text-primary">{{ $productos->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="text-muted">Productos Stock Bajo</h6>
                <h3 class="text-warning">{{ $productosStockBajo->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Insumos</h6>
                <h3 class="text-success">{{ $insumos->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6 class="text-muted">Insumos Stock Bajo</h6>
                <h3 class="text-danger">{{ $insumosStockBajo->count() }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Productos con Stock Bajo -->
@if($productosStockBajo->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-white">
                <i class="fas fa-exclamation-triangle me-2"></i>Productos con Stock Bajo
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productosStockBajo as $producto)
                                <tr>
                                    <td>{{ $producto->nombre }}</td>
                                    <td><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $producto->stock }}</span>
                                    </td>
                                    <td class="text-center">{{ $producto->stock_minimo }}</td>
                                    <td class="text-end">Bs {{ number_format($producto->precio_venta, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Inventario de Productos -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cookie-bite me-2"></i>Inventario de Productos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-end">Precio Venta</th>
                                <th class="text-end">Valor en Inventario</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalValor = 0; @endphp
                            @foreach($productos as $producto)
                                @php 
                                    $valorInventario = $producto->stock * $producto->precio_venta;
                                    $totalValor += $valorInventario;
                                @endphp
                                <tr>
                                    <td>{{ $producto->id }}</td>
                                    <td>{{ $producto->nombre }}</td>
                                    <td><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></td>
                                    <td class="text-center">
                                        @if($producto->stock <= $producto->stock_minimo)
                                            <span class="badge bg-danger">{{ $producto->stock }}</span>
                                        @else
                                            <span class="badge bg-success">{{ $producto->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $producto->stock_minimo }}</td>
                                    <td class="text-end">Bs {{ number_format($producto->precio_venta, 2) }}</td>
                                    <td class="text-end">Bs {{ number_format($valorInventario, 2) }}</td>
                                    <td>
                                        @if($producto->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">Valor Total del Inventario:</th>
                                <th class="text-end">Bs {{ number_format($totalValor, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Insumos con Stock Bajo -->
@if($insumosStockBajo->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle me-2"></i>Insumos con Stock Bajo
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Unidad</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-end">Costo Unitario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($insumosStockBajo as $insumo)
                                <tr>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td><span class="badge bg-secondary">{{ $insumo->unidad_medida }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                    </td>
                                    <td class="text-center">{{ number_format($insumo->stock_minimo, 2) }}</td>
                                    <td class="text-end">Bs {{ number_format($insumo->costo_unitario, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Inventario de Insumos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-boxes me-2"></i>Inventario de Insumos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Insumo</th>
                                <th>Unidad de Medida</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-end">Costo Unitario</th>
                                <th class="text-end">Valor en Inventario</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalValorInsumos = 0; @endphp
                            @foreach($insumos as $insumo)
                                @php 
                                    $valorInsumo = $insumo->cantidad_stock * $insumo->costo_unitario;
                                    $totalValorInsumos += $valorInsumo;
                                @endphp
                                <tr>
                                    <td>{{ $insumo->id }}</td>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td><span class="badge bg-secondary">{{ $insumo->unidad_medida }}</span></td>
                                    <td class="text-center">
                                        @if($insumo->cantidad_stock <= $insumo->stock_minimo)
                                            <span class="badge bg-danger">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                        @else
                                            <span class="badge bg-success">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($insumo->stock_minimo, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($insumo->costo_unitario, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($valorInsumo, 2) }}</td>
                                    <td>
                                        @if($insumo->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">Valor Total en Insumos:</th>
                                <th class="text-end">Bs{{ number_format($totalValorInsumos, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen Final -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">Resumen del Inventario</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Valor en Productos:</strong> <span class="text-success">Bs{{ number_format($totalValor, 2) }}</span></p>
                        <p class="mb-2"><strong>Valor en Insumos:</strong> <span class="text-primary">Bs{{ number_format($totalValorInsumos, 2) }}</span></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-0"><strong>Valor Total del Inventario:</strong></p>
                        <h3 class="text-success">Bs{{ number_format($totalValor + $totalValorInsumos, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .btn, .navbar, .sidebar {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
        }
    }
</style>
@endpush