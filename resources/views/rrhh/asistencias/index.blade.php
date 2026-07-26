@extends('layouts.app')
@section('page-title', 'Asistencias')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4><i class="fas fa-calendar-check me-2"></i>Control de Asistencia</h4>
        <a href="{{ route('asistencias.registrar') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Registrar Asistencia
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header"><i class="fas fa-filter me-2"></i>Filtros</div>
    <div class="card-body">
        <form method="GET" action="{{ route('asistencias.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label fw-bold">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label fw-bold">Empleado</label>
                <select name="empleado_id" class="form-select">
                    <option value="">— Todos —</option>
                    @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}" @selected($empleado_id == $emp->id)>{{ $emp->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="{{ route('asistencias.index') }}" class="btn btn-outline-secondary">Hoy</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th>Estado</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th class="text-center">Tardanza</th>
                        <th class="text-center">H. Extra</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asistencias as $asis)
                    @php
                        $badges = ['presente'=>'success','ausente'=>'danger','tardanza'=>'warning text-dark','medio_dia'=>'info','feriado'=>'secondary','licencia'=>'primary'];
                        $labels = ['presente'=>'Presente','ausente'=>'Ausente','tardanza'=>'Tardanza','medio_dia'=>'Medio día','feriado'=>'Feriado','licencia'=>'Licencia'];
                    @endphp
                    <tr>
                        <td>{{ $asis->fecha->format('d/m/Y') }}</td>
                        <td><strong>{{ $asis->empleado->nombre_completo }}</strong></td>
                        <td><span class="badge bg-info">{{ $asis->empleado->cargo->nombre }}</span></td>
                        <td><span class="badge bg-{{ $badges[$asis->estado] ?? 'secondary' }}">{{ $labels[$asis->estado] ?? $asis->estado }}</span></td>
                        <td>{{ $asis->hora_entrada ? \Carbon\Carbon::parse($asis->hora_entrada)->format('H:i') : '—' }}</td>
                        <td>{{ $asis->hora_salida ? \Carbon\Carbon::parse($asis->hora_salida)->format('H:i') : '—' }}</td>
                        <td class="text-center">{{ $asis->minutos_tardanza > 0 ? $asis->minutos_tardanza . ' min' : '—' }}</td>
                        <td class="text-center">{{ $asis->horas_extra > 0 ? number_format($asis->horas_extra, 1) . ' h' : '—' }}</td>
                        <td>
                            <a href="{{ route('asistencias.edit', $asis) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        No hay registros para esta fecha.
                        <a href="{{ route('asistencias.registrar', ['fecha' => $fecha]) }}">Registrar ahora</a>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $asistencias->links() }}</div>
    </div>
</div>
@endsection
