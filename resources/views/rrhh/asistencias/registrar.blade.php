@extends('layouts.app')
@section('page-title', 'Registrar Asistencia')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4><i class="fas fa-clipboard-list me-2"></i>Registrar Asistencia</h4>
        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Selector de fecha --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('asistencias.registrar') }}" class="d-flex gap-3 align-items-end">
            <div>
                <label class="form-label fw-bold mb-1">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" onchange="this.form.submit()">
            </div>
            <div class="d-flex align-items-end">
                <span class="text-muted fs-6">
                    {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                </span>
            </div>
        </form>
    </div>
</div>

@if($esDomingo)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="fas fa-exclamation-triangle fa-lg"></i>
    <div>
        <strong>Domingo</strong> — Día no laborable. Las asistencias del domingo
        <strong>no se contabilizan</strong> en el cálculo de la planilla.
    </div>
</div>
@endif

@if($registradas->isNotEmpty())
<div class="alert alert-info py-2">
    <i class="fas fa-info-circle me-2"></i>
    Esta fecha ya tiene <strong>{{ $registradas->count() }}</strong> registro(s) guardado(s).
    Los datos actuales están precargados — puedes modificarlos y volver a guardar.
</div>
@endif

<form action="{{ route('asistencias.store') }}" method="POST">
    @csrf
    <input type="hidden" name="fecha" value="{{ $fecha }}">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-users me-2"></i>Personal activo ({{ $empleados->count() }} empleados)</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" onclick="marcarTodos('presente')">
                    <i class="fas fa-check me-1"></i>Todos presentes
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="marcarTodos('ausente')">
                    <i class="fas fa-times me-1"></i>Todos ausentes
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width:160px;">Empleado</th>
                            <th>Cargo</th>
                            <th style="min-width:115px;">Horario</th>
                            <th style="min-width:150px;">Estado</th>
                            <th style="min-width:105px;">Hora entrada</th>
                            <th style="min-width:105px;">Hora salida</th>
                            <th style="min-width:115px;">Atraso</th>
                            <th style="min-width:180px;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleados as $emp)
                        @php
                            $prev = $registradas->get($emp->id); // objeto Asistencia o null
                        @endphp
                        <tr data-emp="{{ $emp->id }}"
                            data-horario="{{ $emp->tiene_horario ? '1' : '0' }}"
                            data-entrada-prog="{{ $emp->tiene_horario ? \Carbon\Carbon::parse($emp->hora_entrada)->format('H:i') : '' }}"
                            data-salida-prog="{{ $emp->tiene_horario ? \Carbon\Carbon::parse($emp->hora_salida)->format('H:i') : '' }}"
                            data-tolerancia="{{ $emp->tolerancia_minutos }}">
                            <td>
                                <strong>{{ $emp->nombre_completo }}</strong>
                                @if($prev)
                                    <br><span class="badge bg-secondary" style="font-size:.65rem;">Guardado</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $emp->cargo->nombre }}</span></td>

                            {{-- Horario programado: es la referencia del cálculo --}}
                            <td>
                                @if($emp->tiene_horario)
                                    <small class="text-nowrap fw-semibold">{{ $emp->horario_texto }}</small>
                                    <br><small class="text-muted">tol. {{ $emp->tolerancia_minutos }} min</small>
                                @else
                                    <span class="badge bg-secondary" style="font-size:.65rem;">Sin horario</span>
                                @endif
                            </td>

                            {{-- Estado --}}
                            <td>
                                <select name="asistencias[{{ $emp->id }}][estado]"
                                        class="form-select form-select-sm estado-select"
                                        data-emp="{{ $emp->id }}"
                                        onchange="filaCambio(this)">
                                    @foreach(['presente'=>'Presente','ausente'=>'Ausente','tardanza'=>'Tardanza','medio_dia'=>'Medio día','feriado'=>'Feriado','licencia'=>'Licencia'] as $val => $lbl)
                                    <option value="{{ $val }}"
                                        @selected(($prev ? $prev->estado : 'presente') === $val)>
                                        {{ $lbl }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Hora entrada --}}
                            <td>
                                <input type="time"
                                       name="asistencias[{{ $emp->id }}][hora_entrada]"
                                       class="form-control form-control-sm hora-entrada"
                                       value="{{ $prev?->hora_entrada ? \Carbon\Carbon::parse($prev->hora_entrada)->format('H:i') : '' }}"
                                       onchange="filaCambio(this)">
                            </td>

                            {{-- Hora salida --}}
                            <td>
                                <input type="time"
                                       name="asistencias[{{ $emp->id }}][hora_salida]"
                                       class="form-control form-control-sm hora-salida"
                                       value="{{ $prev?->hora_salida ? \Carbon\Carbon::parse($prev->hora_salida)->format('H:i') : '' }}"
                                       onchange="filaCambio(this)">
                            </td>

                            {{-- Atraso: calculado si hay horario, manual si no --}}
                            <td>
                                @if($emp->tiene_horario)
                                    <span class="badge bg-light text-muted border calc-tardanza" style="font-size:.8rem;">—</span>
                                @else
                                    <input type="number"
                                           name="asistencias[{{ $emp->id }}][minutos_tardanza]"
                                           class="form-control form-control-sm tardanza-field-{{ $emp->id }}"
                                           min="0" max="480" placeholder="0"
                                           value="{{ $prev?->minutos_tardanza ?: '' }}"
                                           {{ ($prev ? $prev->estado : 'presente') !== 'tardanza' ? 'disabled' : '' }}>
                                @endif
                            </td>

                            {{-- Observaciones --}}
                            <td>
                                <input type="text"
                                       name="asistencias[{{ $emp->id }}][observaciones]"
                                       class="form-control form-control-sm"
                                       maxlength="200"
                                       value="{{ $prev?->observaciones ?? '' }}">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay empleados activos registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Los cambios reemplazarán los registros existentes para esta fecha.
                En los empleados con horario, el atraso se calcula a partir de la
                hora de entrada marcada.
            </small>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-2"></i>Guardar Asistencias
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
// Estados que se comparan contra el horario. En los demás (ausente, feriado,
// licencia) no hay jornada que medir, así que no se calcula nada.
const ESTADOS_TRABAJADOS = ['presente', 'tardanza', 'medio_dia'];

const aMinutos = h => { const [hh, mm] = h.split(':').map(Number); return hh * 60 + mm; };

// Desfase normalizado a ±12 h, para que un turno que cruza la medianoche no
// se lea como casi un día de diferencia.
function desfase(programada, marcada) {
    let d = aMinutos(marcada) - aMinutos(programada);
    if (d > 720) d -= 1440;
    else if (d < -720) d += 1440;
    return d;
}

/**
 * Vista previa del atraso de una fila. El valor definitivo lo recalcula el
 * servidor al guardar; esto es solo para que el encargado vea el resultado
 * mientras carga las horas.
 */
function recalcularFila(fila) {
    if (fila.dataset.horario !== '1') return;

    const estado     = fila.querySelector('.estado-select').value;
    const entrada    = fila.querySelector('.hora-entrada').value;
    const tolerancia = parseInt(fila.dataset.tolerancia, 10) || 0;
    const badge      = fila.querySelector('.calc-tardanza');

    const pintar = (texto, clase) => {
        badge.textContent = texto;
        badge.className = 'badge ' + clase;
        badge.style.fontSize = '.8rem';
    };

    if (!ESTADOS_TRABAJADOS.includes(estado)) {
        pintar('no aplica', 'bg-light text-muted border');
        return;
    }

    if (!entrada) {
        pintar('—', 'bg-light text-muted border');
        return;
    }

    const atraso = Math.max(0, desfase(fila.dataset.entradaProg, entrada) - tolerancia);
    atraso > 0
        ? pintar(atraso + ' min tarde', 'bg-warning text-dark')
        : pintar('a tiempo', 'bg-success');
}

// Los empleados sin horario conservan la carga manual de siempre.
function toggleTardanza(select) {
    const field = document.querySelector(`.tardanza-field-${select.dataset.emp}`);
    if (!field) return;
    field.disabled = select.value !== 'tardanza';
    if (field.disabled) field.value = '';
}

function filaCambio(elemento) {
    const fila = elemento.closest('tr');
    if (elemento.classList.contains('estado-select')) toggleTardanza(elemento);
    recalcularFila(fila);
}

function marcarTodos(estado) {
    document.querySelectorAll('.estado-select').forEach(sel => {
        sel.value = estado;
        filaCambio(sel);
    });
}

document.querySelectorAll('tbody tr[data-emp]').forEach(fila => {
    const sel = fila.querySelector('.estado-select');
    if (sel) toggleTardanza(sel);
    recalcularFila(fila);
});
</script>
@endpush
@endsection
