@extends('layouts.app')

@section('page-title', 'Detalle de Producto')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-eye me-2"></i>Detalle de Producto
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Imagen -->
                    <div class="col-md-4 text-center mb-4">
                        @if($producto->imagen)
                            <img src="{{ asset('storage/' . $producto->imagen) }}"
                                 alt="{{ $producto->nombre }}"
                                 class="img-thumbnail"
                                 style="max-width: 100%;">
                        @else
                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                 style="width: 100%; height: 300px;">
                                <i class="fas fa-image fa-5x text-white"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Información -->
                    <div class="col-md-8">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th style="width:35%">Nombre:</th>
                                <td>{{ $producto->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Categoría:</th>
                                <td><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></td>
                            </tr>
                            <tr>
                                <th>Descripción:</th>
                                <td>{{ $producto->descripcion ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Precio de Venta:</th>
                                <td><span class="badge bg-success fs-6">Bs {{ number_format($producto->precio_venta, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th>Stock en Bodega:</th>
                                <td>
                                    @if($producto->stock <= $producto->stock_minimo)
                                        <span class="badge bg-danger fs-6">{{ $producto->stock }} unidades</span>
                                        <small class="text-danger d-block">Stock bajo</small>
                                    @else
                                        <span class="badge bg-success fs-6">{{ $producto->stock }} unidades</span>
                                    @endif
                                    <small class="text-muted">Sin distribuir a almacenes</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Stock Mínimo:</th>
                                <td>{{ $producto->stock_minimo }} unidades</td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    @if($producto->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Distribución por almacén --}}
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Stock por Almacén</h5>
                    @can('editar-productos')
                    @if($producto->stock > 0 && $almacenes->isNotEmpty())
                    <button type="button" class="btn btn-primary btn-sm"
                            onclick="abrirTransferencia({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->stock }})">
                        <i class="fas fa-truck me-2"></i>Transferir a Almacén
                    </button>
                    @endif
                    @endcan
                </div>

                @if($producto->almacenes->isEmpty())
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-2"></i>
                        Este producto aún no tiene stock distribuido en ningún almacén.
                        @if($producto->stock > 0)
                            Usa el botón <strong>Transferir a Almacén</strong> para distribuir las {{ $producto->stock }} unidades disponibles.
                        @endif
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Almacén</th>
                                <th class="text-center">Stock disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($producto->almacenes as $almacen)
                            <tr>
                                <td>
                                    <i class="fas fa-warehouse me-2 text-muted"></i>
                                    {{ $almacen->nombre }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $almacen->pivot->stock > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                        {{ $almacen->pivot->stock }} unidades
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total distribuido en almacenes:</th>
                                <th class="text-center">
                                    {{ $producto->almacenes->sum('pivot.stock') }} unidades
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('productos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    @can('editar-productos')
                    <a href="{{ route('productos.edit', $producto->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal transferencia --}}
@can('editar-productos')
<div class="modal fade" id="modalTransferir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-truck me-2"></i>Transferir a Almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransferir" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-1">Producto: <strong>{{ $producto->nombre }}</strong></p>
                    <p class="mb-3 text-muted">
                        Disponible en bodega: <strong>{{ $producto->stock }}</strong> unidades
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Almacén destino <span class="text-danger">*</span></label>
                        <select name="almacen_id" class="form-select" required>
                            <option value="">— Seleccionar almacén —</option>
                            @foreach($almacenes as $almacen)
                                <option value="{{ $almacen->id }}">{{ $almacen->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" class="form-control"
                               min="1" max="{{ $producto->stock }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-truck me-2"></i>Transferir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
function abrirTransferencia(id, nombre, stock) {
    document.getElementById('formTransferir').action = '/productos/' + id + '/transferir-almacen';
    new bootstrap.Modal(document.getElementById('modalTransferir')).show();
}
</script>
@endpush
@endsection
