@extends('layouts.app')

@section('page-title', 'Insumos')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-boxes me-2"></i>Gestión de Insumos</h4>
            @can('crear-insumos')
            <a href="{{ route('insumos.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Insumo
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Listado de Insumos
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('insumos.index') }}" class="mb-3">
                    <div class="input-group" style="max-width: 380px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text"
                               name="buscar"
                               class="form-control"
                               placeholder="Buscar por nombre..."
                               value="{{ $buscar ?? '' }}"
                               autocomplete="off">
                        @if($buscar)
                            <a href="{{ route('insumos.index') }}" class="btn btn-outline-secondary" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @else
                            <button type="submit" class="btn btn-primary">Buscar</button>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Unidad</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-end">Costo Unitario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($insumos as $insumo)
                                <tr>
                                    <td><strong>{{ $insumo->nombre }}</strong></td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $insumo->unidad_medida }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($insumo->cantidad_stock <= $insumo->stock_minimo)
                                            <span class="badge bg-danger">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                        @else
                                            <span class="badge bg-success">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-muted">{{ number_format($insumo->stock_minimo, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($insumo->costo_unitario, 5) }}</td>
                                    <td>
                                        @if($insumo->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-danger">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('crear-insumos')
                                        <button type="button"
                                                class="btn btn-success btn-sm"
                                                title="Registrar compra"
                                                onclick="abrirCompra({{ $insumo->id }}, '{{ addslashes($insumo->nombre) }}', '{{ $insumo->unidad_medida }}', {{ $insumo->costo_unitario }})">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        @endcan

                                        @can('ver-insumos')
                                        <a href="{{ route('insumos.show', $insumo->id) }}"
                                           class="btn btn-info btn-sm"
                                           title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endcan

                                        @role('Administrador')
                                        <button type="button"
                                                class="btn btn-orange btn-sm"
                                                title="Registrar merma"
                                                style="background:#f97316;border-color:#f97316;color:#fff;"
                                                onclick="abrirMerma({{ $insumo->id }}, '{{ addslashes($insumo->nombre) }}', '{{ $insumo->unidad_medida }}', {{ $insumo->cantidad_stock }})">
                                            <i class="fas fa-minus-circle"></i>
                                        </button>
                                        @endrole

                                        @can('editar-insumos')
                                        <a href="{{ route('insumos.edit', $insumo->id) }}"
                                           class="btn btn-warning btn-sm"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan

                                        @can('eliminar-insumos')
                                        <form action="{{ route('insumos.destroy', $insumo->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Está seguro de eliminar este insumo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay insumos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $insumos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal registrar compra --}}
@can('crear-insumos')
<div class="modal fade" id="modalCompra" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shopping-cart me-2"></i>Registrar Compra de Insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCompra" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-3">
                        Insumo: <strong id="compra_nombre"></strong>
                        <span class="text-muted" id="compra_unidad"></span>
                    </p>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" id="compra_cantidad"
                                   class="form-control" min="0.00001" step="0.00001" required
                                   oninput="calcularTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Precio unitario (Bs) <span class="text-danger">*</span></label>
                            <input type="number" name="precio_unitario" id="compra_precio"
                                   class="form-control" min="0" step="0.00001" required
                                   oninput="calcularTotal()">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                Total de la compra: <strong id="compra_total">Bs 0.00</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Fecha de compra <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control"
                                   placeholder="Proveedor, factura, etc.">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Registrar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Modal registrar merma --}}
@role('Administrador')
<div class="modal fade" id="modalMerma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff7ed; border-bottom:3px solid #f97316;">
                <h5 class="modal-title" style="color:#c2410c;">
                    <i class="fas fa-minus-circle me-2"></i>Registrar Merma / Descuento de Stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMerma" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Insumo: <strong id="merma_nombre"></strong>
                        &nbsp;|&nbsp; Stock actual: <strong id="merma_stock_actual"></strong>
                        <span class="text-muted" id="merma_unidad"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Cantidad a descontar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="cantidad" id="merma_cantidad"
                                       class="form-control" min="0.01" step="0.01" required>
                                <span class="input-group-text" id="merma_unidad_addon"></span>
                            </div>
                            <div id="merma_error_stock" class="text-danger small mt-1 d-none">
                                <i class="fas fa-exclamation-circle me-1"></i>Cantidad mayor al stock disponible
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                            <select name="motivo" class="form-select" required>
                                <option value="">— Seleccione motivo —</option>
                                <option value="Vencimiento / Caducidad">Vencimiento / Caducidad</option>
                                <option value="Daño o deterioro">Daño o deterioro</option>
                                <option value="Derrame o pérdida">Derrame o pérdida</option>
                                <option value="Error de producción">Error de producción</option>
                                <option value="Ajuste de inventario">Ajuste de inventario</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"
                                      placeholder="Detalle adicional (opcional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btn_merma_submit">
                        <i class="fas fa-minus-circle me-2"></i>Registrar Merma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endrole

@push('scripts')
<script>
function abrirCompra(id, nombre, unidad, precio) {
    document.getElementById('compra_nombre').textContent = nombre;
    document.getElementById('compra_unidad').textContent = '(' + unidad + ')';
    document.getElementById('compra_precio').value = precio;
    document.getElementById('compra_cantidad').value = '';
    document.getElementById('compra_total').textContent = 'Bs 0.00';
    document.getElementById('formCompra').action = '/insumos/' + id + '/comprar';
    new bootstrap.Modal(document.getElementById('modalCompra')).show();
}

function calcularTotal() {
    const cantidad = parseFloat(document.getElementById('compra_cantidad').value) || 0;
    const precio   = parseFloat(document.getElementById('compra_precio').value) || 0;
    document.getElementById('compra_total').textContent = 'Bs ' + (cantidad * precio).toFixed(2);
}

function abrirMerma(id, nombre, unidad, stockActual) {
    document.getElementById('merma_nombre').textContent      = nombre;
    document.getElementById('merma_stock_actual').textContent = parseFloat(stockActual).toFixed(2) + ' ';
    document.getElementById('merma_unidad').textContent      = unidad;
    document.getElementById('merma_unidad_addon').textContent = unidad;
    document.getElementById('merma_cantidad').value          = '';
    document.getElementById('merma_cantidad').max            = stockActual;
    document.getElementById('merma_error_stock').classList.add('d-none');
    document.getElementById('formMerma').action              = '/insumos/' + id + '/merma';

    document.getElementById('merma_cantidad').oninput = function() {
        const val = parseFloat(this.value) || 0;
        const errorEl = document.getElementById('merma_error_stock');
        const btn     = document.getElementById('btn_merma_submit');
        if (val > stockActual) {
            errorEl.classList.remove('d-none');
            btn.disabled = true;
        } else {
            errorEl.classList.add('d-none');
            btn.disabled = false;
        }
    };

    new bootstrap.Modal(document.getElementById('modalMerma')).show();
}
</script>
@endpush
@endsection
