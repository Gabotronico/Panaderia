@extends('layouts.app')
@section('page-title', 'Cargos')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-briefcase me-2"></i>Cargos / Puestos</h4>
        <a href="{{ route('cargos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Cargo
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
    <div class="card-header"><i class="fas fa-list me-2"></i>Listado de Cargos</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th>Descripción</th>
                        <th class="text-center">Empleados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cargos as $cargo)
                    <tr>
                        <td><strong>{{ $cargo->nombre }}</strong></td>
                        <td class="text-muted">{{ $cargo->descripcion ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $cargo->empleados_count }}</span>
                        </td>
                        <td>
                            <a href="{{ route('cargos.edit', $cargo) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('cargos.destroy', $cargo) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este cargo?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No hay cargos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
