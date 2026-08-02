@extends('layouts.app')

@section('page-title', 'Ventas')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-shopping-cart me-2"></i>Gestión de Ventas</h4>
            @can('crear-ventas')
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('ventas.directa') }}" class="btn btn-outline-primary"
                   title="Registrar el total vendido sin detallar productos">
                    <i class="fas fa-cash-register me-2"></i>Venta Directa
                </a>
                <a href="{{ route('ventas.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nueva Venta
                </a>
            </div>
            @endcan
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('ventas.index') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" 
                               class="form-control" 
                               id="fecha_inicio" 
                               name="fecha_inicio" 
                               value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" 
                               class="form-control" 
                               id="fecha_fin" 
                               name="fecha_fin" 
                               value="{{ request('fecha_fin') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelada" {{ request('estado') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <a href="{{ route('ventas.index') }}" class="btn btn-secondary flex-fill">
                            <i class="fas fa-redo me-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Indicadores de filtros activos -->
@if(request()->hasAny(['fecha_inicio', 'fecha_fin', 'estado']))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
            <div>
                <i class="fas fa-filter me-2"></i>
                <strong>Filtros activos:</strong>
                @if(request('fecha_inicio'))
                    <span class="badge bg-primary ms-2">
                        Desde: {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }}
                    </span>
                @endif
                @if(request('fecha_fin'))
                    <span class="badge bg-primary ms-2">
                        Hasta: {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}
                    </span>
                @endif
                @if(request('estado'))
                    <span class="badge bg-{{ request('estado') == 'completada' ? 'success' : 'danger' }} ms-2">
                        Estado: {{ ucfirst(request('estado')) }}
                    </span>
                @endif
            </div>
            <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Limpiar filtros
            </a>
        </div>
    </div>
</div>
@endif

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Cajero</th>
                <th>Subtotal</th>
                <th>Descuento</th>
                <th>Total</th>
                <th>Pago</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
                <tr>
                    <td>
                        <strong>{{ $venta->numero_venta }}</strong>
                        @if($venta->es_directa)
                            <br><span class="badge bg-secondary" style="font-size:.62rem;"
                                      title="Registrada por monto total, sin detalle de productos">
                                venta directa
                            </span>
                        @endif
                    </td>
                    <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $venta->user->name }}</td>
                    <td>Bs {{ number_format($venta->subtotal, 2) }}</td>
                    <td>
                        @if($venta->descuento > 0)
                            <span class="text-danger">-Bs {{ number_format($venta->descuento, 2) }}</span>
                        @else
                            Bs 0.00
                        @endif
                    </td>
                    <td>
                        <strong class="text-success">Bs {{ number_format($venta->total, 2) }}</strong>
                    </td>
                    <td>
                        @if(isset($venta->tipo_pago) && $venta->tipo_pago === 'qr')
                            <span class="badge bg-primary">
                                <i class="fas fa-qrcode me-1"></i>QR
                            </span>
                        @else
                            <span class="badge bg-success">
                                <i class="fas fa-money-bill-wave me-1"></i>Efectivo
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($venta->estado == 'completada')
                            <span class="badge bg-success">Completada</span>
                        @else
                            <span class="badge bg-danger">Cancelada</span>
                        @endif
                    </td>
                    <td>
                        @can('ver-ventas')
                        <a href="{{ route('ventas.show', $venta->id) }}" 
                           class="btn btn-info btn-sm" 
                           title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endcan
                        
                        @can('editar-ventas')
                        @if($venta->estado != 'cancelada')
                        <a href="{{ route('ventas.edit', $venta->id) }}" 
                           class="btn btn-warning btn-sm" 
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">
                        @if(request()->hasAny(['fecha_inicio', 'fecha_fin', 'estado']))
                            No se encontraron ventas con los filtros aplicados
                        @else
                            No hay ventas registradas
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="table-light">
                <th colspan="6" class="text-end">
                    @if(request()->hasAny(['fecha_inicio', 'fecha_fin', 'estado']))
                        Total (filtrado):
                    @else
                        Total (página actual):
                    @endif
                </th>
                <th>Bs {{ number_format($ventas->sum('total'), 2) }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
</div>
                
               
                  <!-- Paginación mejorada -->
                     <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    Mostrando {{ $ventas->firstItem() }} a {{ $ventas->lastItem() }} de {{ $ventas->total() }} resultados
                                </small>
                              </div>
                              <div>
                                      {{ $ventas->links() }}
                                      </div>
                         </div>
            </div>
        </div>
    </div>
</div>
@endsection