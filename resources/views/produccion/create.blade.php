@extends('layouts.app')
@section('page-title', 'Registrar Producción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-industry me-2"></i>Registrar Producción</h4>
    <a href="{{ route('produccion.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <i class="fas fa-triangle-exclamation me-2"></i>
    <strong>Revisá la carga:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if($insumos->isEmpty() || $productos->isEmpty())
<div class="alert alert-warning">
    <i class="fas fa-circle-info me-2"></i>
    @if($insumos->isEmpty())
        No hay insumos activos cargados. Registrá insumos antes de producir.
    @else
        No hay productos activos cargados. Registrá los productos que vas a obtener.
    @endif
</div>
@else

<form action="{{ route('produccion.store') }}" method="POST" id="formProduccion">
    @csrf

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control"
                           value="{{ old('fecha', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" maxlength="500"
                           value="{{ old('observaciones') }}" placeholder="Opcional — ej: tanda de la mañana">
                </div>
            </div>
        </div>
    </div>

    {{-- ── INSUMOS QUE SE CONSUMEN ───────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="fas fa-wheat-awn me-2 text-warning"></i>Insumos que se consumen
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarInsumo()">
                <i class="fas fa-plus me-1"></i>Agregar insumo
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:220px;">Insumo</th>
                            <th style="min-width:150px;">Cantidad</th>
                            <th>Stock disponible</th>
                            <th class="text-end">Costo</th>
                            <th style="width:3rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="filasInsumos"></tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Costo total de la producción:</th>
                            <th class="text-end" id="costoTotal">Bs 0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PRODUCTOS QUE SE OBTIENEN ─────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="fas fa-bread-slice me-2 text-success"></i>Productos que salen para la venta
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarProducto()">
                <i class="fas fa-plus me-1"></i>Agregar producto
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:220px;">Producto</th>
                            <th style="min-width:150px;">Unidades</th>
                            <th>Stock actual</th>
                            <th class="text-end">Quedará en</th>
                            <th style="width:3rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="filasProductos"></tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Unidades producidas:</th>
                            <th class="text-end" id="totalUnidades">0</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">
                <i class="fas fa-circle-info me-1"></i>
                Al confirmar se descuentan los insumos y se suman los productos al stock de bodega.
                <span id="costoUnitario"></span>
            </div>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-check me-2"></i>Confirmar Producción
            </button>
        </div>
    </div>
</form>
@endif
@endsection

@push('scripts')
@php
    // Se arman acá y no dentro de @json(...): el parser de Blade corta el
    // argumento de la directiva en el primer ')' y se confunde con los
    // corchetes del array que devuelve la función flecha.
    $catalogoInsumos = $insumos->map(fn ($i) => [
        'id'     => $i->id,
        'nombre' => $i->nombre,
        'unidad' => $i->unidad_medida,
        'stock'  => (float) $i->cantidad_stock,
        'costo'  => (float) $i->costo_unitario,
    ])->values();

    $catalogoProductos = $productos->map(fn ($p) => [
        'id'     => $p->id,
        'nombre' => $p->nombre,
        'stock'  => (int) $p->stock,
    ])->values();
@endphp
<script>
// Catálogos que vienen del servidor; el navegador solo arma filas con esto.
const INSUMOS   = @json($catalogoInsumos);
const PRODUCTOS = @json($catalogoProductos);

let nInsumo = 0, nProducto = 0;

const fmt = n => 'Bs ' + n.toFixed(2);
const limpio = n => Number.isInteger(n) ? n : parseFloat(n.toFixed(3));

function opciones(lista) {
    return lista.map(x => `<option value="${x.id}">${x.nombre}</option>`).join('');
}

function agregarInsumo() {
    const i = nInsumo++;
    const fila = document.createElement('tr');
    fila.className = 'fila-insumo';
    fila.innerHTML = `
        <td>
            <select name="insumos[${i}][insumo_id]" class="form-select sel-insumo" required onchange="refrescarInsumos()">
                ${opciones(INSUMOS)}
            </select>
        </td>
        <td>
            <div class="input-group">
                <input type="number" name="insumos[${i}][cantidad]" class="form-control cant-insumo"
                       step="0.001" min="0.001" required oninput="refrescarInsumos()">
                <span class="input-group-text unidad">—</span>
            </div>
        </td>
        <td class="stock-insumo text-muted">—</td>
        <td class="text-end costo-insumo text-muted">—</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); refrescarInsumos();">
                <i class="fas fa-trash"></i>
            </button>
        </td>`;
    document.getElementById('filasInsumos').appendChild(fila);
    refrescarInsumos();
}

function agregarProducto() {
    const i = nProducto++;
    const fila = document.createElement('tr');
    fila.className = 'fila-producto';
    fila.innerHTML = `
        <td>
            <select name="productos[${i}][producto_id]" class="form-select sel-producto" required onchange="refrescarProductos()">
                ${opciones(PRODUCTOS)}
            </select>
        </td>
        <td>
            <input type="number" name="productos[${i}][cantidad]" class="form-control cant-producto"
                   step="1" min="1" required oninput="refrescarProductos()">
        </td>
        <td class="stock-producto text-muted">—</td>
        <td class="text-end quedara fw-semibold text-success">—</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); refrescarProductos();">
                <i class="fas fa-trash"></i>
            </button>
        </td>`;
    document.getElementById('filasProductos').appendChild(fila);
    refrescarProductos();
}

// Avisa en pantalla si la cantidad pedida supera el stock, para no llegar al
// servidor con una carga que va a ser rechazada entera.
function refrescarInsumos() {
    let costoTotal = 0;

    document.querySelectorAll('.fila-insumo').forEach(fila => {
        const dato = INSUMOS.find(x => x.id == fila.querySelector('.sel-insumo').value);
        const campo = fila.querySelector('.cant-insumo');
        const cantidad = parseFloat(campo.value) || 0;
        if (!dato) return;

        fila.querySelector('.unidad').textContent = dato.unidad;
        fila.querySelector('.stock-insumo').textContent = `${limpio(dato.stock)} ${dato.unidad}`;

        const costo = cantidad * dato.costo;
        costoTotal += costo;
        fila.querySelector('.costo-insumo').textContent = cantidad > 0 ? fmt(costo) : '—';

        const excede = cantidad > dato.stock;
        campo.classList.toggle('is-invalid', excede);
        fila.querySelector('.stock-insumo').className = excede
            ? 'stock-insumo text-danger fw-semibold' : 'stock-insumo text-muted';
    });

    document.getElementById('costoTotal').textContent = fmt(costoTotal);
    actualizarCostoUnitario(costoTotal);
}

function refrescarProductos() {
    let unidades = 0;

    document.querySelectorAll('.fila-producto').forEach(fila => {
        const dato = PRODUCTOS.find(x => x.id == fila.querySelector('.sel-producto').value);
        const cantidad = parseInt(fila.querySelector('.cant-producto').value) || 0;
        if (!dato) return;

        unidades += cantidad;
        fila.querySelector('.stock-producto').textContent = dato.stock;
        fila.querySelector('.quedara').textContent = cantidad > 0 ? (dato.stock + cantidad) : '—';
    });

    document.getElementById('totalUnidades').textContent = unidades;
    actualizarCostoUnitario();
}

function actualizarCostoUnitario(costoTotal) {
    if (costoTotal === undefined) {
        costoTotal = parseFloat(document.getElementById('costoTotal').textContent.replace('Bs ', '')) || 0;
    }
    const unidades = parseInt(document.getElementById('totalUnidades').textContent) || 0;
    const caja = document.getElementById('costoUnitario');

    caja.textContent = (costoTotal > 0 && unidades > 0)
        ? ` Costo por unidad: ${fmt(costoTotal / unidades)}.`
        : '';
}

document.getElementById('formProduccion')?.addEventListener('submit', function (e) {
    if (!document.querySelector('.fila-insumo') || !document.querySelector('.fila-producto')) {
        e.preventDefault();
        alert('Agregá al menos un insumo consumido y un producto obtenido.');
        return;
    }

    if (document.querySelector('.cant-insumo.is-invalid')) {
        e.preventDefault();
        alert('Hay insumos con una cantidad mayor al stock disponible. Corregilos antes de confirmar.');
    }
});

// Arranca con una fila de cada lado para que se vea qué hay que cargar.
agregarInsumo();
agregarProducto();
</script>
@endpush
