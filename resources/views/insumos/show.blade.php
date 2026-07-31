@extends('layouts.app')
@section('page-title', 'Insumo: ' . $insumo->nombre)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-boxes me-2"></i>{{ $insumo->nombre }}</h4>
    <div class="d-flex gap-2">
        @can('crear-insumos')
        <button type="button" class="btn btn-success btn-sm"
                onclick="new bootstrap.Modal(document.getElementById('modalCompraShow')).show()">
            <i class="fas fa-shopping-cart me-1"></i>Registrar Compra
        </button>
        @endcan
        @role('Administrador')
        <button type="button" class="btn btn-sm text-white"
                style="background:#f97316;border-color:#f97316;"
                onclick="new bootstrap.Modal(document.getElementById('modalMermaShow')).show()">
            <i class="fas fa-minus-circle me-1"></i>Registrar Merma
        </button>
        @endrole
        @role('Administrador')
        <button type="button" class="btn btn-info btn-sm text-white"
                onclick="new bootstrap.Modal(document.getElementById('modalMoverProducto')).show()">
            <i class="fas fa-exchange-alt me-1"></i>Mover a Producto
        </button>
        @endrole
        @can('editar-insumos')
        <a href="{{ route('insumos.edit', $insumo->id) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        @endcan
        <a href="{{ route('insumos.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Datos actuales --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Datos Actuales</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Unidad:</th>
                        <td><span class="badge bg-secondary">{{ $insumo->unidad_medida }}</span></td>
                    </tr>
                    <tr>
                        <th>Stock actual:</th>
                        <td>
                            @if($insumo->cantidad_stock <= $insumo->stock_minimo)
                                <span class="badge bg-danger fs-6">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                                <small class="text-danger d-block">Stock bajo</small>
                            @else
                                <span class="badge bg-success fs-6">{{ number_format($insumo->cantidad_stock, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Stock mínimo:</th>
                        <td class="text-muted">{{ number_format($insumo->stock_minimo, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Precio actual:</th>
                        <td><span class="badge bg-info fs-6">Bs {{ number_format($insumo->costo_unitario, 5) }}</span></td>
                    </tr>
                    <tr>
                        <th>Valor en stock:</th>
                        <td>
                            <strong class="text-success">
                                Bs {{ number_format($insumo->cantidad_stock * $insumo->costo_unitario, 2) }}
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <th>Estado:</th>
                        <td>
                            @if($insumo->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Total compras:</th>
                        <td><strong>{{ $compras->count() }}</strong></td>
                    </tr>
                    <tr>
                        <th>Gasto total:</th>
                        <td><strong class="text-warning">Bs {{ number_format($compras->sum('total'), 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Gráfica evolución de precio --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-line me-2"></i>Evolución del Precio</div>
            <div class="card-body">
                @if($preciosHistorial->isEmpty())
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                            <p>Sin historial de compras aún.<br>
                            Usa <strong>Registrar Compra</strong> para empezar a rastrear el precio.</p>
                        </div>
                    </div>
                @else
                    <canvas id="precioChart"></canvas>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Historial de compras --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2"></i>Historial de Compras</span>
        @if($compras->isNotEmpty())
        <span class="badge bg-primary">{{ $compras->count() }} compras</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($compras->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fas fa-shopping-cart fa-2x mb-2 opacity-25 d-block"></i>
                Sin compras registradas para este insumo.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Precio unitario</th>
                        <th class="text-end">Total</th>
                        <th>Observaciones</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @php $precioAnterior = null; @endphp
                    @foreach($compras as $compra)
                    <tr>
                        <td>{{ $compra->fecha->format('d/m/Y') }}</td>
                        <td class="text-end">
                            {{ number_format($compra->cantidad, 5) }} {{ $insumo->unidad_medida }}
                        </td>
                        <td class="text-end">
                            <strong>Bs {{ number_format($compra->precio_unitario, 5) }}</strong>
                            @if($precioAnterior !== null)
                                @if($compra->precio_unitario > $precioAnterior)
                                    <i class="fas fa-arrow-up text-danger ms-1" title="Precio subió"></i>
                                @elseif($compra->precio_unitario < $precioAnterior)
                                    <i class="fas fa-arrow-down text-success ms-1" title="Precio bajó"></i>
                                @endif
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="badge bg-warning text-dark">Bs {{ number_format($compra->total, 2) }}</span>
                        </td>
                        <td class="text-muted small">{{ $compra->observaciones ?? '—' }}</td>
                        <td class="small text-muted">{{ $compra->user->name ?? '—' }}</td>
                    </tr>
                    @php $precioAnterior = $compra->precio_unitario; @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">Total invertido:</th>
                        <th class="text-end">Bs {{ number_format($compras->sum('total'), 2) }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Historial de mermas --}}
@role('Administrador')
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="border-left:4px solid #f97316;">
        <span><i class="fas fa-minus-circle me-2" style="color:#f97316;"></i>Historial de Mermas / Descuentos</span>
        @if($mermas->isNotEmpty())
        <span class="badge" style="background:#f97316;">{{ $mermas->count() }} registros</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($mermas->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50 d-block"></i>
                Sin mermas registradas para este insumo.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Cantidad descontada</th>
                        <th>Motivo</th>
                        <th>Observaciones</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mermas as $merma)
                    <tr>
                        <td>{{ $merma->fecha->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <span class="badge bg-danger">
                                -{{ number_format($merma->cantidad, 2) }} {{ $insumo->unidad_medida }}
                            </span>
                        </td>
                        <td><span class="badge bg-secondary">{{ $merma->motivo }}</span></td>
                        <td class="text-muted small">{{ $merma->observaciones ?? '—' }}</td>
                        <td class="small text-muted">{{ $merma->user->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="1" class="text-end">Total descontado:</th>
                        <th class="text-end text-danger">
                            -{{ number_format($mermas->sum('cantidad'), 2) }} {{ $insumo->unidad_medida }}
                        </th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
@endrole

{{-- Modal registrar merma desde show --}}
@role('Administrador')
<div class="modal fade" id="modalMermaShow" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff7ed; border-bottom:3px solid #f97316;">
                <h5 class="modal-title" style="color:#c2410c;">
                    <i class="fas fa-minus-circle me-2"></i>Registrar Merma — {{ $insumo->nombre }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insumos.merma', $insumo) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Stock actual: <strong>{{ number_format($insumo->cantidad_stock, 2) }} {{ $insumo->unidad_medida }}</strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Cantidad a descontar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="cantidad"
                                       class="form-control" min="0.01" step="0.01"
                                       max="{{ $insumo->cantidad_stock }}" required>
                                <span class="input-group-text">{{ $insumo->unidad_medida }}</span>
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
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-minus-circle me-2"></i>Registrar Merma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endrole

{{-- Modal registrar compra desde show --}}
@can('crear-insumos')
<div class="modal fade" id="modalCompraShow" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shopping-cart me-2"></i>Registrar Compra — {{ $insumo->nombre }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insumos.comprar', $insumo) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">Stock actual: <strong>{{ number_format($insumo->cantidad_stock, 2) }} {{ $insumo->unidad_medida }}</strong></p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" id="showCantidad" class="form-control"
                                   min="0.00001" step="0.00001" required oninput="calcTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Precio unitario (Bs) <span class="text-danger">*</span></label>
                            <input type="number" name="precio_unitario" id="showPrecio" class="form-control"
                                   min="0" step="0.00001" value="{{ $insumo->costo_unitario }}" required oninput="calcTotal()">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                Total: <strong id="showTotal">Bs 0.00</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" placeholder="Proveedor, nº factura...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Modal: Mover insumo a producto (sin receta) --}}
@role('Administrador')
<div class="modal fade" id="modalMoverProducto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>Mover a Producto — {{ $insumo->nombre }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insumos.moverAProducto', $insumo) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Stock disponible: <strong>{{ number_format($insumo->cantidad_stock, 2) }} {{ $insumo->unidad_medida }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Producto destino <span class="text-danger">*</span></label>
                        <select name="producto_id" class="form-select" required onchange="actualizarStockProducto(this)">
                            <option value="">— Seleccionar producto —</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" data-stock="{{ $producto->stock }}">
                                    {{ $producto->nombre }} (stock actual: {{ $producto->stock }} uds.)
                                </option>
                            @endforeach
                        </select>
                        <div id="info-stock-producto" class="form-text text-muted mt-1 d-none">
                            Stock actual del producto: <strong id="stock-producto-valor">—</strong> unidades
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad a mover <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="cantidad" id="moverCantidad"
                                   class="form-control" min="0.00001" step="0.00001"
                                   max="{{ $insumo->cantidad_stock }}" required
                                   oninput="validarMover(this)">
                            <span class="input-group-text">{{ $insumo->unidad_medida }}</span>
                        </div>
                        <div id="mover-error-stock" class="text-danger small mt-1 d-none">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            Cantidad supera el stock disponible ({{ number_format($insumo->cantidad_stock, 2) }} {{ $insumo->unidad_medida }})
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La cantidad se descontará del stock de <strong>{{ $insumo->nombre }}</strong>
                        y se sumará como unidades enteras al producto seleccionado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnMoverSubmit" class="btn btn-info text-white">
                        <i class="fas fa-exchange-alt me-2"></i>Confirmar movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endrole

@push('scripts')
<script>
function calcTotal() {
    const cantidad = parseFloat(document.getElementById('showCantidad').value) || 0;
    const precio   = parseFloat(document.getElementById('showPrecio').value) || 0;
    document.getElementById('showTotal').textContent = 'Bs ' + (cantidad * precio).toFixed(2);
}

function actualizarStockProducto(select) {
    const opt   = select.options[select.selectedIndex];
    const stock = opt.dataset.stock;
    const info  = document.getElementById('info-stock-producto');
    if (stock !== undefined && select.value) {
        document.getElementById('stock-producto-valor').textContent = stock;
        info.classList.remove('d-none');
    } else {
        info.classList.add('d-none');
    }
}

function validarMover(input) {
    const max  = parseFloat(input.max) || 0;
    const val  = parseFloat(input.value) || 0;
    const err  = document.getElementById('mover-error-stock');
    const btn  = document.getElementById('btnMoverSubmit');
    if (val > max) {
        err.classList.remove('d-none');
        btn.disabled = true;
    } else {
        err.classList.add('d-none');
        btn.disabled = false;
    }
}

@if($preciosHistorial->isNotEmpty())
document.addEventListener('DOMContentLoaded', () => {
const precioData  = @json($preciosHistorial->pluck('precio_unitario'));
const fechaData   = @json($preciosHistorial->map(fn($c) => \Carbon\Carbon::parse($c->fecha)->format('d/m/Y')));

new Chart(document.getElementById('precioChart'), {
    type: 'line',
    data: {
        labels: fechaData,
        datasets: [{
            label: 'Precio unitario (Bs)',
            data: precioData,
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            pointBackgroundColor: 'rgb(99, 102, 241)',
            pointRadius: 5,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: ctx => 'Bs ' + ctx.parsed.y.toFixed(2)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: { callback: v => 'Bs ' + v }
            }
        }
    }
});
});
@endif
</script>
@endpush
@endsection
