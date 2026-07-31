@extends('layouts.app')
@section('page-title', 'Editar Empleado')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-user-edit me-2"></i>Editar: {{ $empleado->nombre_completo }}</h4>
        <a href="{{ route('empleados.show', $empleado) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form action="{{ route('empleados.update', $empleado) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $empleado->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Apellido <span class="text-danger">*</span></label>
                    <input type="text" name="apellido" class="form-control @error('apellido') is-invalid @enderror"
                           value="{{ old('apellido', $empleado->apellido) }}" required>
                    @error('apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">CI <span class="text-danger">*</span></label>
                    <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror"
                           value="{{ old('ci', $empleado->ci) }}" required maxlength="20">
                    @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                           value="{{ old('telefono', $empleado->telefono) }}" maxlength="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Cargo <span class="text-danger">*</span></label>
                    <select name="cargo_id" class="form-select @error('cargo_id') is-invalid @enderror" required>
                        @foreach($cargos as $c)
                            <option value="{{ $c->id }}" @selected(old('cargo_id', $empleado->cargo_id) == $c->id)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cargo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Fecha de ingreso <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_ingreso" class="form-control"
                           value="{{ old('fecha_ingreso', $empleado->fecha_ingreso->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Salario base (Bs) <span class="text-danger">*</span></label>
                    <input type="number" id="salario_base" name="salario_base" class="form-control"
                           value="{{ old('salario_base', $empleado->salario_base) }}" min="0" step="0.01" required
                           oninput="calcEquivalencias()">
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-bold">Tipo de pago</label>
                    <select id="tipo_pago" name="tipo_pago" class="form-select" onchange="calcEquivalencias()">
                        <option value="mensual" @selected(old('tipo_pago', $empleado->tipo_pago) === 'mensual')>Mensual</option>
                        <option value="semanal" @selected(old('tipo_pago', $empleado->tipo_pago) === 'semanal')>Semanal</option>
                    </select>
                </div>

                {{-- Horario: habilita el cálculo automático en asistencias --}}
                <div class="col-12">
                    <hr class="my-1">
                    <label class="form-label fw-bold mb-1">
                        <i class="fas fa-clock me-1"></i>Horario de trabajo
                        <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <div class="small text-muted mb-2">
                        Con un horario definido, al registrar la asistencia el sistema calcula solo
                        los minutos de atraso y las horas extra. Si lo dejas vacío, esos valores
                        se siguen cargando a mano.
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Hora de entrada</label>
                    <input type="time" id="hora_entrada" name="hora_entrada"
                           class="form-control @error('hora_entrada') is-invalid @enderror"
                           value="{{ old('hora_entrada', $empleado->hora_entrada ? \Carbon\Carbon::parse($empleado->hora_entrada)->format('H:i') : '') }}"
                           onchange="calcJornada()">
                    @error('hora_entrada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Hora de salida</label>
                    <input type="time" id="hora_salida" name="hora_salida"
                           class="form-control @error('hora_salida') is-invalid @enderror"
                           value="{{ old('hora_salida', $empleado->hora_salida ? \Carbon\Carbon::parse($empleado->hora_salida)->format('H:i') : '') }}"
                           onchange="calcJornada()">
                    @error('hora_salida')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tolerancia (min)</label>
                    <input type="number" name="minutos_tolerancia"
                           class="form-control @error('minutos_tolerancia') is-invalid @enderror"
                           value="{{ old('minutos_tolerancia', $empleado->minutos_tolerancia) }}" min="0" max="120"
                           placeholder="{{ config('nomina.tolerancia_tardanza') }}">
                    @error('minutos_tolerancia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Minutos de gracia antes de contar atraso</small>
                </div>
                <div class="col-12">
                    <div class="alert alert-light border py-2 mb-0" id="jornadaInfo" style="display:none;">
                        <i class="fas fa-hourglass-half me-1 text-primary"></i>
                        Jornada de <strong id="jornadaHoras">—</strong>
                        <span class="text-muted" id="jornadaNota"></span>
                    </div>
                </div>

                {{-- Calculador de equivalencias --}}
                <div class="col-12">
                    <div class="card bg-light border-0" id="calcCard">
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

                <div class="col-md-6">
                    <label class="form-label fw-bold">Estado</label>
                    <select name="activo" class="form-select">
                        <option value="1" @selected($empleado->activo)>Activo</option>
                        <option value="0" @selected(!$empleado->activo)>Inactivo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones', $empleado->observaciones) }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const DIAS_MES = 26;
const DIAS_SEM = 6;

function calcEquivalencias() {
    const monto = parseFloat(document.getElementById('salario_base').value) || 0;
    const tipo  = document.getElementById('tipo_pago').value;

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
    document.getElementById('calc_dia').textContent    = monto ? fmt(diario)  : 'Bs —';
    document.getElementById('calc_semana').textContent = monto ? fmt(semanal) : 'Bs —';
    document.getElementById('calc_mes').textContent    = monto ? fmt(mensual) : 'Bs —';
    document.getElementById('calc_hora').textContent   = monto ? fmt(diario / 8) : 'Bs —';
}

// Muestra la duración de la jornada mientras se define el horario, para
// detectar de una un turno mal cargado antes de guardarlo.
function calcJornada() {
    const entrada = document.getElementById('hora_entrada').value;
    const salida  = document.getElementById('hora_salida').value;
    const caja    = document.getElementById('jornadaInfo');

    if (!entrada || !salida) { caja.style.display = 'none'; return; }

    const aMin = h => { const [hh, mm] = h.split(':').map(Number); return hh * 60 + mm; };
    let minutos = aMin(salida) - aMin(entrada);
    const cruzaMedianoche = minutos <= 0;
    if (cruzaMedianoche) minutos += 1440;

    caja.style.display = 'block';
    document.getElementById('jornadaHoras').textContent =
        (minutos / 60).toFixed(2).replace(/\.00$/, '') + ' horas';
    document.getElementById('jornadaNota').textContent =
        cruzaMedianoche ? '— el turno cruza la medianoche' : '';
}

// Calcular al cargar con los valores actuales
calcEquivalencias();
calcJornada();
</script>
@endpush
@endsection
