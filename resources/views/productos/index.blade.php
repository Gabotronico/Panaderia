@extends('layouts.app')
@section('page-title', 'Productos')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4><i class="fas fa-cookie-bite me-2"></i>Gestión de Productos</h4>
    @can('crear-productos')
    <a href="{{ route('productos.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nuevo Producto
    </a>
    @endcan
</div>

{{-- Sin almacenes no hay a dónde transferir. Se avisa acá porque el botón
     de transferencia queda deshabilitado y sin este aviso no se entiende. --}}
@if($almacenes->isEmpty())
<div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="fas fa-warehouse mt-1"></i>
    <div>
        <strong>No hay almacenes creados.</strong>
        Para entregarle productos a un cajero primero necesitás un almacén: creá uno,
        asignale el cajero desde su ficha y recién ahí vas a poder transferir stock
        con el botón <i class="fas fa-truck"></i> de cada producto.
        @can('crear-almacenes')
        <div class="mt-2">
            <a href="{{ route('almacenes.create') }}" class="btn btn-sm btn-warning">
                <i class="fas fa-plus me-1"></i>Crear almacén
            </a>
        </div>
        @endcan
    </div>
</div>
@endif

{{-- Buscador live --}}
<div class="row g-2 align-items-center mb-3">
    <div class="col-sm-5 col-md-4">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0 ps-0"
                   placeholder="Buscar producto…" autocomplete="off">
            <button class="btn btn-outline-secondary" id="btnLimpiar" style="display:none;" title="Limpiar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="col-sm-4 col-md-3">
        <select id="filtroCategoria" class="form-select">
            <option value="">— Todas las categorías —</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto text-muted" style="font-size:.85rem;">
        <span id="contador">{{ $productos->count() }}</span> producto(s)
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>Listado de Productos</span>
        <div class="btn-group btn-group-sm">
            <button type="button" id="btn-vista-tabla" class="btn btn-outline-secondary active"
                    title="Vista tabla" onclick="setVista('tabla')">
                <i class="fas fa-list"></i>
            </button>
            <button type="button" id="btn-vista-grid" class="btn btn-outline-secondary"
                    title="Vista tarjetas" onclick="setVista('grid')">
                <i class="fas fa-th-large"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0 p-md-3">

        {{-- ===== VISTA TABLA ===== --}}
        <div id="vista-tabla">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th class="text-center">Stock Bodega</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        @forelse($productos as $producto)
                        <tr data-nombre="{{ strtolower($producto->nombre) }}"
                            data-categoria="{{ $producto->categoria_id }}">
                            <td>
                                @if($producto->imagen)
                                    <img src="{{ asset('storage/' . $producto->imagen) }}"
                                         alt="{{ $producto->nombre }}" class="rounded"
                                         style="width:50px; height:50px; object-fit:cover;">
                                @else
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                         style="width:50px; height:50px;">
                                        <i class="fas fa-image text-white"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $producto->nombre }}</td>
                            <td><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></td>
                            <td>Bs{{ number_format($producto->precio_venta, 2) }}</td>
                            <td class="text-center">
                                @if($producto->stock <= $producto->stock_minimo)
                                    <span class="badge bg-danger">{{ $producto->stock }}</span>
                                @else
                                    <span class="badge bg-success">{{ $producto->stock }}</span>
                                @endif
                                <small class="text-muted d-block" style="font-size:.7rem;">en bodega</small>
                            </td>
                            <td>
                                @if($producto->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @can('editar-productos')
                                {{-- El botón se muestra siempre: si no se puede transferir,
                                     queda deshabilitado explicando por qué. Antes desaparecía
                                     sin aviso y no había forma de saber qué faltaba. --}}
                                @if($producto->stock > 0 && $almacenes->isNotEmpty())
                                <button class="btn btn-primary btn-sm" title="Transferir a almacén"
                                        onclick="abrirTransferencia({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->stock }})">
                                    <i class="fas fa-truck"></i>
                                </button>
                                @else
                                <button class="btn btn-outline-secondary btn-sm" disabled
                                        title="{{ $almacenes->isEmpty()
                                            ? 'No hay almacenes creados. Creá uno y asignale un cajero para poder transferir.'
                                            : 'Este producto no tiene stock disponible para transferir.' }}">
                                    <i class="fas fa-truck"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ver-productos')
                                <a href="{{ route('productos.show', $producto) }}" class="btn btn-info btn-sm" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                @can('editar-productos')
                                <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('eliminar-productos')
                                <form action="{{ route('productos.destroy', $producto) }}" method="POST"
                                      class="d-inline" onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr id="sinProductos">
                            <td colspan="7" class="text-center text-muted py-4">No hay productos registrados</td>
                        </tr>
                        @endforelse
                        {{-- Fila vacía cuando el filtro no encuentra nada --}}
                        <tr id="sinResultadosTabla" style="display:none;">
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-search me-2"></i>Sin resultados para tu búsqueda
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== VISTA GRID ===== --}}
        <div id="vista-grid" class="d-none">
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3" id="gridBody">
                @foreach($productos as $producto)
                <div class="col producto-card"
                     data-nombre="{{ strtolower($producto->nombre) }}"
                     data-categoria="{{ $producto->categoria_id }}">
                    <div class="card h-100 shadow-sm">
                        @if($producto->imagen)
                            <img src="{{ asset('storage/' . $producto->imagen) }}"
                                 class="card-img-top" alt="{{ $producto->nombre }}"
                                 style="height:140px; object-fit:cover;">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height:140px;">
                                <i class="fas fa-cookie-bite fa-3x text-muted"></i>
                            </div>
                        @endif
                        <div class="card-body p-2 d-flex flex-column">
                            <h6 class="card-title mb-1 fw-bold" style="font-size:.85rem; line-height:1.2;">
                                {{ $producto->nombre }}
                            </h6>
                            <div class="mb-1">
                                <span class="badge bg-info" style="font-size:.7rem;">{{ $producto->categoria->nombre }}</span>
                                @if(!$producto->activo)
                                    <span class="badge bg-danger" style="font-size:.7rem;">Inactivo</span>
                                @endif
                            </div>
                            <div class="fw-bold text-success mb-1">Bs{{ number_format($producto->precio_venta, 2) }}</div>
                            <div class="mb-2">
                                @if($producto->stock <= $producto->stock_minimo)
                                    <span class="badge bg-danger" style="font-size:.7rem;">Stock: {{ $producto->stock }}</span>
                                @else
                                    <span class="badge bg-success" style="font-size:.7rem;">Stock: {{ $producto->stock }}</span>
                                @endif
                            </div>
                            <div class="mt-auto d-flex gap-1 flex-wrap">
                                @can('editar-productos')
                                @if($producto->stock > 0 && $almacenes->isNotEmpty())
                                <button class="btn btn-primary btn-sm flex-fill" title="Transferir"
                                        onclick="abrirTransferencia({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->stock }})">
                                    <i class="fas fa-truck"></i>
                                </button>
                                @else
                                <button class="btn btn-outline-secondary btn-sm flex-fill" disabled
                                        title="{{ $almacenes->isEmpty()
                                            ? 'No hay almacenes creados. Creá uno y asignale un cajero para poder transferir.'
                                            : 'Este producto no tiene stock disponible para transferir.' }}">
                                    <i class="fas fa-truck"></i>
                                </button>
                                @endif
                                @endcan
                                @can('ver-productos')
                                <a href="{{ route('productos.show', $producto) }}" class="btn btn-info btn-sm flex-fill" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                @can('editar-productos')
                                <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning btn-sm flex-fill" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('eliminar-productos')
                                <form action="{{ route('productos.destroy', $producto) }}" method="POST"
                                      class="flex-fill" onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm w-100" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div id="sinResultadosGrid" class="text-center text-muted py-5" style="display:none;">
                <i class="fas fa-search fa-2x mb-2 d-block"></i>Sin resultados para tu búsqueda
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
                    <p class="mb-1">Producto: <strong id="modal_nombre"></strong></p>
                    <p class="mb-3 text-muted">Stock disponible: <strong id="modal_stock_disponible"></strong> uds.</p>
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
                        <input type="number" name="cantidad" id="modal_cantidad" class="form-control" min="1" required>
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
// ── Transferencia modal ──────────────────────────────────────────────────
function abrirTransferencia(id, nombre, stock) {
    document.getElementById('modal_nombre').textContent          = nombre;
    document.getElementById('modal_stock_disponible').textContent = stock;
    document.getElementById('modal_cantidad').max                 = stock;
    document.getElementById('modal_cantidad').value               = '';
    document.getElementById('formTransferir').action              = '/productos/' + id + '/transferir-almacen';
    new bootstrap.Modal(document.getElementById('modalTransferir')).show();
}

// ── Vista tabla / grid ───────────────────────────────────────────────────
function setVista(modo) {
    const tabla = document.getElementById('vista-tabla');
    const grid  = document.getElementById('vista-grid');
    const btnT  = document.getElementById('btn-vista-tabla');
    const btnG  = document.getElementById('btn-vista-grid');

    if (modo === 'grid') {
        tabla.classList.add('d-none');
        grid.classList.remove('d-none');
        btnT.classList.remove('active');
        btnG.classList.add('active');
    } else {
        grid.classList.add('d-none');
        tabla.classList.remove('d-none');
        btnG.classList.remove('active');
        btnT.classList.add('active');
    }
    localStorage.setItem('productos_vista', modo);
}

(function () {
    if (localStorage.getItem('productos_vista') === 'grid') setVista('grid');
})();

// ── Buscador live ────────────────────────────────────────────────────────
const buscador       = document.getElementById('buscador');
const filtroCategoria = document.getElementById('filtroCategoria');
const btnLimpiar     = document.getElementById('btnLimpiar');
const contador       = document.getElementById('contador');
const sinTabla       = document.getElementById('sinResultadosTabla');
const sinGrid        = document.getElementById('sinResultadosGrid');

let debounce;

function filtrar() {
    const q   = buscador.value.toLowerCase().trim();
    const cat = filtroCategoria.value;

    // Mostrar/ocultar botón limpiar
    btnLimpiar.style.display = (q || cat) ? '' : 'none';

    // Filas tabla
    const filas = document.querySelectorAll('#tablaBody tr[data-nombre]');
    // Cards grid
    const cards = document.querySelectorAll('.producto-card[data-nombre]');

    let visible = 0;

    filas.forEach(el => {
        const ok = (!q || el.dataset.nombre.includes(q)) &&
                   (!cat || el.dataset.categoria === cat);
        el.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });

    cards.forEach(el => {
        const ok = (!q || el.dataset.nombre.includes(q)) &&
                   (!cat || el.dataset.categoria === cat);
        el.style.display = ok ? '' : 'none';
    });

    contador.textContent = visible;
    sinTabla.style.display = (visible === 0 && filas.length > 0) ? '' : 'none';
    sinGrid.style.display  = (visible === 0 && cards.length > 0) ? '' : 'none';
}

buscador.addEventListener('input', function () {
    clearTimeout(debounce);
    debounce = setTimeout(filtrar, 200);
});

filtroCategoria.addEventListener('change', filtrar);

btnLimpiar.addEventListener('click', function () {
    buscador.value       = '';
    filtroCategoria.value = '';
    filtrar();
    buscador.focus();
});
</script>
@endpush
@endsection
