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
                            <th style="min-width:150px;">Estado</th>
                            <th style="min-width:105px;">Hora entrada</th>
                            <th style="min-width:105px;">Hora salida</th>
                            <th style="min-width:110px;">Min. tardanza</th>
                            <th style="min-width:95px;">H. extra</th>
                            <th style="min-width:150px;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleados as $emp)
                        @php
                            $prev = $registradas->get($emp->id); // objeto Asistencia o null
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $emp->nombre_completo }}</strong>
                                @if($prev)
                                    <br><span class="badge bg-secondary" style="font-size:.65rem;">Guardado</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $emp->cargo->nombre }}</span></td>

                            {{-- Estado --}}
                            <td>
                                <select name="asistencias[{{ $emp->id }}][estado]"
                                        class="form-select form-select-sm estado-select"
                                        data-emp="{{ $emp->id }}"
                                        onchange="toggleTardanza(this)">
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
                                       class="form-control form-control-sm"
                                       value="{{ $prev?->hora_entrada ? \Carbon\Carbon::parse($prev->hora_entrada)->format('H:i') : '' }}">
                            </td>

                            {{-- Hora salida --}}
                            <td>
                                <input type="time"
                                       name="asistencias[{{ $emp->id }}][hora_salida]"
                                       class="form-control form-control-sm"
                                       value="{{ $prev?->hora_salida ? \Carbon\Carbon::parse($prev->hora_salida)->format('H:i') : '' }}">
                            </td>

                            {{-- Minutos tardanza --}}
                            <td>
                                <input type="number"
                                       name="asistencias[{{ $emp->id }}][minutos_tardanza]"
                                       class="form-control form-control-sm tardanza-field-{{ $emp->id }}"
                                       min="0" max="480" placeholder="0"
                                       value="{{ $prev?->minutos_tardanza ?: '' }}"
                                       {{ ($prev ? $prev->estado : 'presente') !== 'tardanza' ? 'disabled' : '' }}>
                            </td>

                            {{-- Horas extra --}}
                            <td>
                                <input type="number"
                                       name="asistencias[{{ $emp->id }}][horas_extra]"
                                       class="form-control form-control-sm"
                                       min="0" max="12" step="0.5" placeholder="0"
                                       value="{{ $prev?->horas_extra ?: '' }}">
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
            </small>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-2"></i>Guardar Asistencias
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
function toggleTardanza(select) {
    const empId = select.dataset.emp;
    const field = document.querySelector(`.tardanza-field-${empId}`);
    field.disabled = select.value !== 'tardanza';
    if (field.disabled) field.value = '';
}

function marcarTodos(estado) {
    document.querySelectorAll('.estado-select').forEach(sel => {
        sel.value = estado;
        toggleTardanza(sel);
    });
}

// Inicializar estado de tardanza al cargar
document.querySelectorAll('.estado-select').forEach(sel => toggleTardanza(sel));
</script>
@endpush
@endsection
