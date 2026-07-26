@extends('layouts.app')
@section('page-title', $almacen->nombre)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-warehouse me-2"></i>{{ $almacen->nombre }}</h4>
    <div class="d-flex gap-2">
        @can('editar-almacenes')
        <a href="{{ route('almacenes.edit', $almacen) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        @endcan
        <a href="{{ route('almacenes.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    {{-- Panel: Cajeros asignados --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-users me-2"></i>Cajeros Asignados
            </div>
            <div class="card-body">
                @if($almacen->cajeros->isEmpty())
                    <p class="text-muted text-center py-3">Sin cajeros asignados.</p>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($almacen->cajeros as $cajero)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <i class="fas fa-user me-2 text-secondary"></i>
                            <strong>{{ $cajero->name }}</strong>
                            <br><small class="text-muted">{{ $cajero->email }}</small>
                        </div>
                        @can('editar-almacenes')
                        <form action="{{ route('almacenes.desasignarCajero', [$almacen, $cajero]) }}" method="POST"
                              onsubmit="return confirm('¿Remover a {{ $cajero->name }} del almacén?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Remover">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        @endcan
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @can('editar-almacenes')
            @if($cajerosSinAlmacen->isNotEmpty())
            <div class="card-footer">
                <form action="{{ route('almacenes.asignarCajero', $almacen) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <select name="user_id" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar cajero —</option>
                        @foreach($cajerosSinAlmacen as $cajero)
                            <option value="{{ $cajero->id }}">{{ $cajero->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus me-1"></i>Asignar
                    </button>
                </form>
            </div>
            @endif
            @endcan
        </div>
    </div>

    {{-- Panel: Stock de productos --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-boxes me-2"></i>Stock de Productos en este Almacén</span>
                @if($almacen->productos->isNotEmpty())
                <small class="opacity-75">
                    Total: {{ $almacen->productos->sum('pivot.stock') }} uds. distribuidas
                </small>
                @endif
            </div>
            <div class="card-body p-0">
                @if($almacen->productos->where('pivot.stock', '>', 0)->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                        Sin stock en este almacén.<br>
                        <small>Transfiere productos desde el módulo de <a href="{{ route('productos.index') }}">Productos</a>.</small>
                    </p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Stock aquí</th>
                                <th class="text-center">Stock bodega</th>
                                @can('editar-almacenes')
                                <th class="text-end">Acciones</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($almacen->productos->where('pivot.stock', '>', 0) as $producto)
                            <tr>
                                <td><strong>{{ $producto->nombre }}</strong></td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">{{ $producto->pivot->stock }}</span>
                                </td>
                                <td class="text-center text-muted">{{ $producto->stock }}</td>
                                @can('editar-almacenes')
                                <td class="text-end">
                                    <button class="btn btn-outline-warning btn-sm"
                                            title="Retornar a bodega"
                                            onclick="abrirRetorno({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->pivot->stock }})">
                                        <i class="fas fa-undo me-1"></i>Retornar
                                    </button>
                                </td>
                                @endcan
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @can('editar-almacenes')
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Para agregar stock, usa <a href="{{ route('productos.index') }}">Productos → botón Transferir</a>.
                    Para retornar stock a bodega, usa el botón <strong>Retornar</strong> de cada producto.
                </small>
            </div>
            @endcan
        </div>
    </div>
</div>

{{-- Modal retornar a bodega --}}
@can('editar-almacenes')
<div class="modal fade" id="modalRetornar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Retornar a Bodega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRetornar" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-1">Producto: <strong id="retorno_nombre"></strong></p>
                    <p class="mb-3 text-muted">
                        Stock disponible en este almacén: <strong id="retorno_stock"></strong> unidades
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad a retornar <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" id="retorno_cantidad"
                               class="form-control" min="1" required>
                        <div class="form-text">Las unidades volverán al stock de bodega del producto.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>Retornar a Bodega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
function abrirRetorno(productoId, nombre, stock) {
    document.getElementById('retorno_nombre').textContent = nombre;
    document.getElementById('retorno_stock').textContent = stock;
    document.getElementById('retorno_cantidad').max = stock;
    document.getElementById('retorno_cantidad').value = '';
    document.getElementById('formRetornar').action =
        '/almacenes/{{ $almacen->id }}/producto/' + productoId + '/retornar';
    new bootstrap.Modal(document.getElementById('modalRetornar')).show();
}
</script>
@endpush
@endsection
