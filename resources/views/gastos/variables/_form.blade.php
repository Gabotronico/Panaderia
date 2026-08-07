@php $g = $gasto ?? null; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
        <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
               value="{{ old('fecha', $g?->fecha?->toDateString() ?? now()->toDateString()) }}" required>
        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label class="form-label fw-bold">Concepto <span class="text-danger">*</span></label>
        <input type="text" name="concepto" class="form-control @error('concepto') is-invalid @enderror"
               value="{{ old('concepto', $g?->concepto) }}" maxlength="150" required
               placeholder="Ej: flete de harina desde el proveedor">
        @error('concepto')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
        <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
            @foreach(\App\Models\GastoVariable::CATEGORIAS as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('categoria', $g?->categoria) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Monto <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Bs</span>
            <input type="number" name="monto" class="form-control @error('monto') is-invalid @enderror"
                   value="{{ old('monto', $g?->monto) }}" step="0.01" min="0.01" required>
            @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Proveedor</label>
        <input type="text" name="proveedor" class="form-control" maxlength="150"
               value="{{ old('proveedor', $g?->proveedor) }}" placeholder="Opcional">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Observaciones</label>
        <input type="text" name="observaciones" class="form-control" maxlength="500"
               value="{{ old('observaciones', $g?->observaciones) }}" placeholder="Opcional">
    </div>
</div>

<div class="alert alert-light border mt-3 mb-0" style="font-size:.85rem;">
    <i class="fas fa-circle-info me-1 text-primary"></i>
    Los gastos variables se descuentan de la <strong>utilidad bruta</strong>, junto con las
    compras de insumos. Son costo de operar, distintos de los gastos fijos de estructura.
</div>
