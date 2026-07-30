@extends('layouts.app')
@section('page-title', 'Editar Asistencia')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-calendar-check me-2"></i>Editar Asistencia</h4>
        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:550px;">
    <div class="card-header">
        {{ $asistencia->empleado->nombre_completo }} — {{ $asistencia->fecha->format('d/m/Y') }}
    </div>
    <div class="card-body">
        @php $emp = $asistencia->empleado; @endphp

        @if($emp->tiene_horario)
        <div class="alert alert-light border py-2">
            <i class="fas fa-clock text-primary me-1"></i>
            Horario: <strong>{{ $emp->horario_texto }}</strong>
            <span class="text-muted">· tolerancia {{ $emp->tolerancia_minutos }} min</span>
            <div class="small text-muted mt-1">
                El atraso y las horas extra se recalculan solos a partir de las horas marcadas.
            </div>
        </div>
        @endif

        <form action="{{ route('asistencias.update', $asistencia) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Estado</label>
                    <select name="estado" id="estadoSel" class="form-select" onchange="toggleTardanza(); recalcular();">
                        @foreach(['presente'=>'Presente','ausente'=>'Ausente','tardanza'=>'Tardanza','medio_dia'=>'Medio día','feriado'=>'Feriado','licencia'=>'Licencia'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($asistencia->estado === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Hora de entrada</label>
                    <input type="time" name="hora_entrada" id="horaEntrada" class="form-control"
                           value="{{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i') : '' }}"
                           onchange="recalcular()">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Hora de salida</label>
                    <input type="time" name="hora_salida" id="horaSalida" class="form-control"
                           value="{{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '' }}"
                           onchange="recalcular()">
                </div>
                @if($emp->tiene_horario)
                <div class="col-md-6">
                    <label class="form-label fw-bold">Atraso</label>
                    <div class="form-control bg-light" id="calcTardanza">—</div>
                    <small class="text-muted">Calculado desde el horario</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Horas extra</label>
                    <div class="form-control bg-light" id="calcExtra">—</div>
                    <small class="text-muted">Calculadas desde el horario</small>
                </div>
                @else
                <div class="col-md-6" id="tardanzaField">
                    <label class="form-label fw-bold">Minutos de tardanza</label>
                    <input type="number" name="minutos_tardanza" class="form-control"
                           min="0" max="480" value="{{ $asistencia->minutos_tardanza }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Horas extra</label>
                    <input type="number" name="horas_extra" class="form-control"
                           min="0" max="12" step="0.5" value="{{ $asistencia->horas_extra }}">
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label fw-bold">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" maxlength="200"
                           value="{{ $asistencia->observaciones }}">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Solo existe cuando el empleado no tiene horario; con horario el valor
// no se carga a mano.
function toggleTardanza() {
    const campo = document.getElementById('tardanzaField');
    if (!campo) return;
    campo.style.display = document.getElementById('estadoSel').value === 'tardanza' ? 'block' : 'none';
}

@if($emp->tiene_horario)
const ESTADOS_TRABAJADOS = ['presente', 'tardanza', 'medio_dia'];
const ENTRADA_PROG = '{{ \Carbon\Carbon::parse($emp->hora_entrada)->format('H:i') }}';
const SALIDA_PROG  = '{{ \Carbon\Carbon::parse($emp->hora_salida)->format('H:i') }}';
const TOLERANCIA   = {{ $emp->tolerancia_minutos }};

const aMinutos = h => { const [hh, mm] = h.split(':').map(Number); return hh * 60 + mm; };

// Normalizado a ±12 h para soportar turnos que cruzan la medianoche.
function desfase(programada, marcada) {
    let d = aMinutos(marcada) - aMinutos(programada);
    if (d > 720) d -= 1440;
    else if (d < -720) d += 1440;
    return d;
}

// Vista previa; el valor que se guarda lo recalcula el servidor.
function recalcular() {
    const estado  = document.getElementById('estadoSel').value;
    const entrada = document.getElementById('horaEntrada').value;
    const salida  = document.getElementById('horaSalida').value;
    const cajaT   = document.getElementById('calcTardanza');
    const cajaE   = document.getElementById('calcExtra');

    if (!ESTADOS_TRABAJADOS.includes(estado)) {
        cajaT.textContent = 'No aplica';
        cajaE.textContent = 'No aplica';
        return;
    }

    if (entrada) {
        const atraso = Math.max(0, desfase(ENTRADA_PROG, entrada) - TOLERANCIA);
        cajaT.textContent = atraso > 0 ? atraso + ' min tarde' : 'A tiempo';
    } else {
        cajaT.textContent = '—';
    }

    if (salida) {
        const extra = Math.max(0, desfase(SALIDA_PROG, salida));
        cajaE.textContent = extra > 0
            ? extra + ' min (' + (extra / 60).toFixed(2) + ' h)'
            : 'Sin extra';
    } else {
        cajaE.textContent = '—';
    }
}
@else
function recalcular() {}
@endif

toggleTardanza();
recalcular();
</script>
@endpush
@endsection
