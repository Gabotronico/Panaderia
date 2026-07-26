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
        <form action="{{ route('asistencias.update', $asistencia) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Estado</label>
                    <select name="estado" id="estadoSel" class="form-select" onchange="toggleTardanza()">
                        @foreach(['presente'=>'Presente','ausente'=>'Ausente','tardanza'=>'Tardanza','medio_dia'=>'Medio día','feriado'=>'Feriado','licencia'=>'Licencia'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($asistencia->estado === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Hora de entrada</label>
                    <input type="time" name="hora_entrada" class="form-control"
                           value="{{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i') : '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Hora de salida</label>
                    <input type="time" name="hora_salida" class="form-control"
                           value="{{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '' }}">
                </div>
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
function toggleTardanza() {
    const show = document.getElementById('estadoSel').value === 'tardanza';
    document.getElementById('tardanzaField').style.display = show ? 'block' : 'none';
}
toggleTardanza();
</script>
@endpush
@endsection
