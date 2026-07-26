@extends('layouts.app')
@section('page-title', 'Empleados')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-users me-2"></i>Empleados</h4>
        <a href="{{ route('empleados.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Empleado
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header"><i class="fas fa-list me-2"></i>Listado de Personal</div>
    <div class="card-body">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('empleados.index') }}" class="row g-2 mb-3">
            <div class="col-sm-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar nombre, apellido o CI..."
                           value="{{ $buscar ?? '' }}" autocomplete="off">
                </div>
            </div>
            <div class="col-sm-3">
                <select name="cargo_id" class="form-select">
                    <option value="">— Todos los cargos —</option>
                    @foreach($cargos as $c)
                        <option value="{{ $c->id }}" @selected($cargo == $c->id)>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>CI</th>
                        <th>Cargo</th>
                        <th class="text-end">Salario Base</th>
                        <th>Tipo Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($empleados as $emp)
                    <tr>
                        <td>
                            <strong>{{ $emp->nombre_completo }}</strong>
                            @if($emp->telefono)
                                <br><small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $emp->telefono }}</small>
                            @endif
                        </td>
                        <td>{{ $emp->ci }}</td>
                        <td><span class="badge bg-info">{{ $emp->cargo->nombre }}</span></td>
                        <td class="text-end fw-bold">Bs {{ number_format($emp->salario_base, 2) }}</td>
                        <td>
                            @if($emp->tipo_pago === 'mensual')
                                <span class="badge bg-primary">Mensual</span>
                            @else
                                <span class="badge bg-warning text-dark">Semanal</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('empleados.show', $emp) }}" class="btn btn-info btn-sm" title="Ver ficha">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('empleados.edit', $emp) }}" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No hay empleados registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $empleados->links() }}</div>
    </div>
</div>
@endsection
