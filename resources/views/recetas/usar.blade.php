@extends('layouts.app')
@section('page-title', 'Usar Receta: ' . $receta->nombre)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-industry me-2"></i>Producción — {{ $receta->nombre }}</h4>
    <a href="{{ route('recetas.show', $receta) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-4">
    {{-- Formulario de producción --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-cogs me-2"></i>Configurar Producción
            </div>
            <div class="card-body">
                <form action="{{ route('recetas.ejecutarUsar', $receta) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="producto_id" class="form-label fw-bold">
                            Producto a producir <span class="text-danger">*</span>
                        </label>
                        <select name="producto_id" id="producto_id" class="form-select" required>
                            <option value="">— Seleccionar producto —</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}"
                                    {{ old('producto_id') == $producto->id ? 'selected' : '' }}>
                                    {{ $producto->nombre }}
                                    (stock: {{ $producto->stock }} uds.)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Número de tandas <span class="text-danger">*</span></label>
                        <input type="number" name="tandas" id="tandas" class="form-control"
                               value="{{ old('tandas', 1) }}" min="1" required>
                        <div class="form-text">
                            Rendimiento por tanda: <strong>{{ $receta->rendimiento }}</strong> unidades.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Unidades producidas reales
                            <span class="text-danger">*</span>
                            <span class="text-muted fw-normal" style="font-size:.82rem;">
                                — puedes ajustar si el resultado difiere del estimado
                            </span>
                        </label>
                        <div class="input-group">
                            <input type="number" name="cantidad_producida" id="cantidad_producida"
                                   class="form-control form-control-lg fw-bold"
                                   value="{{ old('cantidad_producida', $receta->rendimiento) }}"
                                   min="1" required>
                            <span class="input-group-text">unidades</span>
                        </div>
                        <div class="form-text">
                            Estimado: <strong id="totalUnidades">{{ $receta->rendimiento }}</strong> unidades
                            (<span id="diffLabel" class="text-muted">sin cambios</span>)
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        El stock producido se agregará al <strong>stock global</strong> del producto seleccionado.
                        Luego podrás distribuirlo a los almacenes desde el módulo de <strong>Productos</strong>.
                    </div>

                    <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('¿Confirmar producción? Se descontarán los insumos del stock.')">
                        <i class="fas fa-industry me-2"></i>Iniciar Producción
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Detalle de insumos requeridos --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <i class="fas fa-list me-2"></i>Insumos que se consumirán
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th class="text-end">Por tanda</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Disponible</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaInsumos">
                        @foreach($receta->insumos as $insumo)
                        <tr data-cantidad="{{ $insumo->pivot->cantidad_necesaria }}"
                            data-stock="{{ $insumo->cantidad_stock }}"
                            data-unidad="{{ $insumo->unidad_medida }}">
                            <td>{{ $insumo->nombre }}</td>
                            <td class="text-end">
                                {{ number_format($insumo->pivot->cantidad_necesaria, 2) }}
                                {{ $insumo->unidad_medida }}
                            </td>
                            <td class="text-end fw-bold col-total">
                                {{ number_format($insumo->pivot->cantidad_necesaria, 2) }}
                                {{ $insumo->unidad_medida }}
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($insumo->cantidad_stock, 2) }}
                                {{ $insumo->unidad_medida }}
                            </td>
                            <td class="text-center col-estado">
                                @if($insumo->cantidad_stock >= $insumo->pivot->cantidad_necesaria)
                                    <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times"></i></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($receta->descripcion)
        <div class="alert alert-secondary mt-3">
            <i class="fas fa-sticky-note me-2"></i>{{ $receta->descripcion }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
const rendimiento = {{ $receta->rendimiento }};

function actualizarEstimado() {
    const tandas    = parseInt(document.getElementById('tandas').value) || 1;
    const estimado  = rendimiento * tandas;
    const real      = parseInt(document.getElementById('cantidad_producida').value) || estimado;
    const diff      = real - estimado;
    const diffLabel = document.getElementById('diffLabel');

    document.getElementById('totalUnidades').textContent = estimado;

    if (diff === 0) {
        diffLabel.textContent = 'sin cambios';
        diffLabel.className   = 'text-muted';
    } else if (diff > 0) {
        diffLabel.textContent = `+${diff} sobre el estimado`;
        diffLabel.className   = 'text-success fw-bold';
    } else {
        diffLabel.textContent = `${diff} bajo el estimado`;
        diffLabel.className   = 'text-danger fw-bold';
    }

    document.querySelectorAll('#tablaInsumos tr').forEach(row => {
        const cantPorTanda = parseFloat(row.dataset.cantidad);
        const stock        = parseFloat(row.dataset.stock);
        const unidad       = row.dataset.unidad;
        const total        = cantPorTanda * tandas;

        row.querySelector('.col-total').textContent = total.toFixed(2) + ' ' + unidad;
        row.querySelector('.col-estado').innerHTML  = stock >= total
            ? '<span class="badge bg-success"><i class="fas fa-check"></i></span>'
            : '<span class="badge bg-danger"><i class="fas fa-times"></i></span>';
    });
}

document.getElementById('tandas').addEventListener('input', function () {
    // Sincronizar cantidad_producida con el nuevo estimado si el usuario no lo ha tocado
    const estimado = rendimiento * (parseInt(this.value) || 1);
    const campo    = document.getElementById('cantidad_producida');
    if (!campo.dataset.editado) campo.value = estimado;
    actualizarEstimado();
});

document.getElementById('cantidad_producida').addEventListener('input', function () {
    this.dataset.editado = '1';
    actualizarEstimado();
});
</script>
@endpush
@endsection
