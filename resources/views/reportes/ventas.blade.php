@extends('layouts.app')

@section('page-title', 'Reporte de Ventas')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-shopping-cart me-2"></i>Reporte de Ventas</h4>
            <a href="{{ route('reportes.ventas', ['fecha_inicio' => $request->fecha_inicio, 'fecha_fin' => $request->fecha_fin, 'tipo' => 'pdf']) }}" 
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
                            <h6 class="text-muted">Cantidad de Ventas</h6>
                            <h3 class="text-primary">{{ $cantidadVentas }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Total Ventas</h6>
                            <h3 class="text-success">Bs {{ number_format($totalVentas, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Descuentos</h6>
                            <h3 class="text-danger">Bs {{ number_format($totalDescuentos, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Promedio por Venta</h6>
                            <h3 class="text-info">${{ $cantidadVentas > 0 ? number_format($totalVentas / $cantidadVentas, 2) : '0.00' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de Ventas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Detalle de Ventas
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Fecha</th>
                                <th>Cajero</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Descuento</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ventas as $venta)
                                <tr>
                                    <td>{{ $venta->numero_venta }}</td>
                                    <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $venta->user->name }}</td>
                                    <td class="text-end">Bs {{ number_format($venta->subtotal, 2) }}</td>
                                    <td class="text-end">
                                        @if($venta->descuento > 0)
                                            <span class="text-danger">-Bs{{ number_format($venta->descuento, 2) }}</span>
                                        @else
                                            Bs0.00
                                        @endif
                                    </td>
                                    <td class="text-end"><strong>Bs{{ number_format($venta->total, 2) }}</strong></td>
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
                                    <td colspan="7" class="text-center text-muted">No hay ventas en el período seleccionado</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Totales:</th>
                                <th class="text-end">Bs{{ number_format($ventas->sum('subtotal'), 2) }}</th>
                                <th class="text-end text-danger">-Bs{{ number_format($ventas->sum('descuento'), 2) }}</th>
                                <th class="text-end">Bs{{ number_format($ventas->sum('total'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection