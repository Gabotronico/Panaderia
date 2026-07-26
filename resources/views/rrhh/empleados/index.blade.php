@extends('layouts.app')
@section('page-title', 'Empleados')
@section('content')

<x-page-header title="Empleados" icon="users"
               subtitle="Personal de la panadería, salarios y adelantos">
    <a href="{{ route('asistencias.registrar') }}" class="btn btn-light border">
        <i class="fas fa-clipboard-check me-1"></i>Tomar asistencia
    </a>
    <a href="{{ route('empleados.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Empleado
    </a>
</x-page-header>

<x-alerts />

{{-- Resumen del personal --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <x-stat-card label="Personal activo" :value="$resumen['total']" icon="users"
                     variant="primary"
                     :sub="$resumen['mensuales'].' mensual(es) · '.$resumen['semanales'].' semanal(es)'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Costo mensual estimado"
                     :value="'Bs '.number_format($resumen['costo_mensual'], 2)"
                     icon="sack-dollar" variant="info"
                     sub="Si todos trabajan el mes completo" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Adelantos pendientes"
                     :value="'Bs '.number_format($resumen['adelantos'], 2)"
                     icon="hand-holding-dollar"
                     :variant="$resumen['adelantos'] > 0 ? 'warning' : 'neutral'"
                     sub="Se descuentan en la próxima planilla" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Cargos registrados" :value="$cargos->count()" icon="briefcase"
                     variant="neutral" sub="Puestos de trabajo definidos"
                     :href="route('cargos.index')" />
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('empleados.index') }}" class="filter-bar">
    <div class="row g-2 align-items-center">
        <div class="col-sm-5 col-md-4">
            <div class="input-group">
                <span class="input-group-text border-end-0 bg-white">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0"
                       placeholder="Nombre, apellido o CI…"
                       value="{{ $buscar ?? '' }}" autocomplete="off">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="cargo_id" class="form-select">
                <option value="">— Todos los cargos —</option>
                @foreach($cargos as $c)
                    <option value="{{ $c->id }}" @selected($cargo == $c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i>Filtrar
            </button>
            @if($buscar || $cargo)
                <a href="{{ route('empleados.index') }}" class="btn btn-light border">Limpiar</a>
            @endif
        </div>
        <div class="col-auto ms-auto text-muted" style="font-size:.82rem;">
            {{ $empleados->total() }} empleado(s)
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th class="text-end">Salario base</th>
                        <th class="text-end">Por día</th>
                        <th class="text-end">Por hora</th>
                        <th>Antigüedad</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($empleados as $emp)
                    <tr class="{{ $emp->activo ? '' : 'opacity-50' }}">
                        <td>
                            <div class="fw-semibold text-dark">{{ $emp->nombre_completo }}</div>
                            <small class="text-muted">
                                CI {{ $emp->ci }}
                                @if($emp->telefono) · <i class="fas fa-phone"></i> {{ $emp->telefono }} @endif
                            </small>
                        </td>
                        <td><span class="badge bg-info">{{ $emp->cargo->nombre }}</span></td>
                        <td class="text-end">
                            <span class="fw-bold">Bs {{ number_format($emp->salario_base, 2) }}</span>
                            <br>
                            @if($emp->tipo_pago === 'mensual')
                                <small class="badge bg-primary">Mensual</small>
                            @else
                                <small class="badge bg-warning text-dark">Semanal</small>
                            @endif
                        </td>
                        <td class="text-end"><x-money :amount="$emp->valor_dia" /></td>
                        <td class="text-end text-muted"><x-money :amount="$emp->tarifa_hora" /></td>
                        <td>
                            <small>{{ $emp->antiguedad_texto }}</small>
                            <br><small class="text-muted">desde {{ $emp->fecha_ingreso?->format('d/m/Y') }}</small>
                        </td>
                        <td class="text-center">
                            @if($emp->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('empleados.show', $emp) }}" class="btn btn-info btn-sm" title="Ver ficha">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('empleados.edit', $emp) }}" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-0">
                            <x-empty-state icon="user-slash"
                                           title="No hay empleados que coincidan"
                                           message="Ajusta los filtros o registra el primer empleado del equipo.">
                                <a href="{{ route('empleados.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Registrar empleado
                                </a>
                            </x-empty-state>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($empleados->hasPages())
        <div class="d-flex justify-content-center py-3 border-top">
            {{ $empleados->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
