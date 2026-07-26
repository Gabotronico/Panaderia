@extends('layouts.app')

@section('page-title', 'Nueva Receta')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>Crear Nueva Receta
            </div>
            <div class="card-body">
                <form action="{{ route('recetas.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   id="nombre" name="nombre"
                                   value="{{ old('nombre') }}"
                                   placeholder="Ej: Masa para empanadas" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="rendimiento" class="form-label">
                                Rinde <span class="text-danger">*</span>
                                <small class="text-muted d-block">unidades que produce</small>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                       class="form-control @error('rendimiento') is-invalid @enderror"
                                       id="rendimiento" name="rendimiento"
                                       value="{{ old('rendimiento', 1) }}"
                                       min="1" required>
                                <span class="input-group-text">uds.</span>
                            </div>
                            @error('rendimiento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                                       {{ old('activo', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activo">Activa</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"
                                  rows="2" placeholder="Descripción opcional de la receta">{{ old('descripcion') }}</textarea>
                    </div>

                    <!-- Insumos de la receta -->
                    <div class="card mb-3 border">
                        <div class="card-header bg-light text-dark">
                            <i class="fas fa-boxes me-2"></i>Insumos de la Receta
                        </div>
                        <div class="card-body">
                            <div class="row fw-bold mb-2 text-muted small">
                                <div class="col-md-7">Insumo</div>
                                <div class="col-md-4">Cantidad</div>
                                <div class="col-md-1"></div>
                            </div>

                            <div id="insumos-container">
                                <div class="row mb-2 insumo-row">
                                    <div class="col-md-7">
                                        <select class="form-select" name="insumos[0][id]" required>
                                            <option value="">Seleccione un insumo</option>
                                            @foreach($insumos as $insumo)
                                                <option value="{{ $insumo->id }}">
                                                    {{ $insumo->nombre }} ({{ $insumo->unidad_medida }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control" name="insumos[0][cantidad]"
                                               placeholder="Cantidad" step="0.01" min="0.01" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeInsumo(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-success btn-sm mt-2" onclick="addInsumo()">
                                <i class="fas fa-plus me-1"></i>Agregar Insumo
                            </button>

                            @error('insumos')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('recetas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Receta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const insumosData = @json($insumos);
    let idx = 1;

    function buildOptions(selectedId = '') {
        return insumosData.map(i =>
            `<option value="${i.id}" ${i.id == selectedId ? 'selected' : ''}>${i.nombre} (${i.unidad_medida})</option>`
        ).join('');
    }

    function addInsumo(selectedId = '', cantidad = '') {
        const container = document.getElementById('insumos-container');
        const row = document.createElement('div');
        row.className = 'row mb-2 insumo-row';
        row.innerHTML = `
            <div class="col-md-7">
                <select class="form-select" name="insumos[${idx}][id]" required>
                    <option value="">Seleccione un insumo</option>
                    ${buildOptions(selectedId)}
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control" name="insumos[${idx}][cantidad]"
                       placeholder="Cantidad" step="0.01" min="0.01" value="${cantidad}" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeInsumo(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`;
        container.appendChild(row);
        idx++;
    }

    function removeInsumo(btn) {
        const rows = document.querySelectorAll('.insumo-row');
        if (rows.length > 1) {
            btn.closest('.insumo-row').remove();
        } else {
            alert('La receta debe tener al menos un insumo.');
        }
    }
</script>
@endpush
