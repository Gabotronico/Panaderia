@php
    $g = $gasto ?? null;
    $meses = ['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio',
              '7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
@endphp

@if($errors->any())
<div class="alert alert-danger mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $g?->nombre) }}" placeholder="Ej: Alquiler local, ELFEC, SEMAPA">
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
        <select name="categoria" class="form-select @error('categoria') is-invalid @enderror">
            @foreach(['alquiler'=>'Alquiler','servicios'=>'Servicios (luz, agua, gas, internet)','mantenimiento'=>'Mantenimiento','impuestos'=>'Impuestos / tasas','otro'=>'Otro'] as $val => $lbl)
            <option value="{{ $val }}" @selected(old('categoria', $g?->categoria) === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Monto estimado (Bs) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Bs</span>
            <input type="number" name="monto_estimado" class="form-control @error('monto_estimado') is-invalid @enderror"
                   step="0.01" min="0.01" value="{{ old('monto_estimado', $g?->monto_estimado) }}" placeholder="0.00">
            @error('monto_estimado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Frecuencia <span class="text-danger">*</span></label>
        <select name="frecuencia" id="frecuencia" class="form-select @error('frecuencia') is-invalid @enderror"
                onchange="toggleMesInicio()">
            @foreach(['mensual'=>'Mensual','bimestral'=>'Bimestral (cada 2 meses)','trimestral'=>'Trimestral (cada 3 meses)','semestral'=>'Semestral (cada 6 meses)','anual'=>'Anual'] as $val => $lbl)
            <option value="{{ $val }}" @selected(old('frecuencia', $g?->frecuencia ?? 'mensual') === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        @error('frecuencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Día de vencimiento <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" name="dia_vencimiento" class="form-control @error('dia_vencimiento') is-invalid @enderror"
                   min="1" max="28" value="{{ old('dia_vencimiento', $g?->dia_vencimiento ?? 5) }}">
            <span class="input-group-text">de cada mes</span>
            @error('dia_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <small class="text-muted">Máx. 28 para evitar problemas en febrero.</small>
    </div>

    <div class="col-md-6" id="mesInicioWrap">
        <label class="form-label fw-bold">Mes de inicio / referencia</label>
        <select name="mes_inicio" class="form-select @error('mes_inicio') is-invalid @enderror">
            @foreach($meses as $num => $nom)
            <option value="{{ $num }}" @selected(old('mes_inicio', $g?->mes_inicio ?? 1) == $num)>{{ $nom }}</option>
            @endforeach
        </select>
        <small class="text-muted">Primer mes en que se genera este gasto.</small>
        @error('mes_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Proveedor / Acreedor</label>
        <input type="text" name="proveedor" class="form-control"
               value="{{ old('proveedor', $g?->proveedor) }}" placeholder="Ej: ELFEC, propietario, YPFB">
    </div>

    <div class="col-12">
        <label class="form-label fw-bold">Observaciones</label>
        <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones', $g?->observaciones) }}</textarea>
    </div>
</div>

@push('scripts')
<script>
function toggleMesInicio() {
    const frec = document.getElementById('frecuencia').value;
    document.getElementById('mesInicioWrap').style.display = frec === 'mensual' ? 'none' : 'block';
}
toggleMesInicio();
</script>
@endpush
