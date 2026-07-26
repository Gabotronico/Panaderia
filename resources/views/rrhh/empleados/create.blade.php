@extends('layouts.app')
@section('page-title', 'Nuevo Empleado')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-user-plus me-2"></i>Registrar Empleado</h4>
        <a href="{{ route('empleados.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form action="{{ route('empleados.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Apellido <span class="text-danger">*</span></label>
                    <input type="text" name="apellido" class="form-control @error('apellido') is-invalid @enderror"
                           value="{{ old('apellido') }}" required>
                    @error('apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">CI <span class="text-danger">*</span></label>
                    <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror"
                           value="{{ old('ci') }}" required maxlength="20">
                    @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                           value="{{ old('telefono') }}" maxlength="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Cargo <span class="text-danger">*</span></label>
                    <select name="cargo_id" class="form-select @error('cargo_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar —</option>
                        @foreach($cargos as $c)
                            <option value="{{ $c->id }}" @selected(old('cargo_id') == $c->id)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cargo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Fecha de ingreso <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_ingreso" class="form-control @error('fecha_ingreso') is-invalid @enderror"
                           value="{{ old('fecha_ingreso', date('Y-m-d')) }}" required>
                    @error('fecha_ingreso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Salario base (Bs) <span class="text-danger">*</span></label>
                    <input type="number" id="salario_base" name="salario_base"
                           class="form-control @error('salario_base') is-invalid @enderror"
                           value="{{ old('salario_base') }}" min="0" step="0.01" required
                           oninput="calcEquivalencias()">
                    @error('salario_base')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tipo de pago <span class="text-danger">*</span></label>
                    <select id="tipo_pago" name="tipo_pago" class="form-select" required onchange="calcEquivalencias()">
                        <option value="mensual" @selected(old('tipo_pago') === 'mensual')>Mensual</option>
                        <option value="semanal" @selected(old('tipo_pago') === 'semanal')>Semanal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Factor hora extra</label>
                    <input type="number" name="factor_hora_extra" class="form-control"
                           value="{{ old('factor_hora_extra', 1.50) }}" min="1" step="0.05">
                    <small class="text-muted">Ej: 1.5 = 150%</small>
                </div>

                {{-- Calculador de equivalencias --}}
                <div class="col-12">
                    <div class="card bg-light border-0" id="calcCard" style="display:none;">
                        <div class="card-body py-2 px-3">
                            <small class="text-muted fw-bold d-block mb-2">
                                <i class="fas fa-calculator me-1"></i>Equivalencias salariales
                                <span class="text-muted fw-normal">(base: 26 días laborables/mes · 6 días/semana)</span>
                            </small>
                            <div class="d-flex gap-4 flex-wrap">
                                <div class="text-center">
                                    <div class="text-muted small">Por día</div>
                                    <div class="fw-bold text-primary fs-6" id="calc_dia">Bs —</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-muted small">Por semana</div>
                                    <div class="fw-bold text-success fs-6" id="calc_semana">Bs —</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-muted small">Por mes</div>
                                    <div class="fw-bold text-dark fs-6" id="calc_mes">Bs —</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-muted small">Por hora (8 h/día)</div>
                                    <div class="fw-bold text-secondary fs-6" id="calc_hora">Bs —</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Registrar Empleado
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const DIAS_MES = 26;   // días laborables promedio/mes (lun–sáb)
const DIAS_SEM = 6;    // días laborables por semana

function calcEquivalencias() {
    const monto = parseFloat(document.getElementById('salario_base').value) || 0;
    const tipo  = document.getElementById('tipo_pago').value;
    const card  = document.getElementById('calcCard');

    if (!monto) { card.style.display = 'none'; return; }
    card.style.display = 'block';

    let diario, semanal, mensual;
    if (tipo === 'mensual') {
        mensual = monto;
        diario  = monto / DIAS_MES;
        semanal = diario * DIAS_SEM;
    } else {
        semanal = monto;
        diario  = monto / DIAS_SEM;
        mensual = diario * DIAS_MES;
    }

    const fmt = n => 'Bs ' + n.toFixed(2);
    document.getElementById('calc_dia').textContent    = fmt(diario);
    document.getElementById('calc_semana').textContent = fmt(semanal);
    document.getElementById('calc_mes').textContent    = fmt(mensual);
    document.getElementById('calc_hora').textContent   = fmt(diario / 8);
}
</script>
@endpush
@endsection
