@extends('layouts.app')
@section('page-title', 'Almacenes')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-warehouse me-2"></i>Gestión de Almacenes</h4>
    @can('crear-almacenes')
    <a href="{{ route('almacenes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nuevo Almacén
    </a>
    @endcan
</div>

<div class="row">
    @forelse($almacenes as $almacen)
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-warehouse me-2"></i>{{ $almacen->nombre }}</span>
                @if($almacen->activo)
                    <span class="badge bg-success">Activo</span>
                @else
                    <span class="badge bg-secondary">Inactivo</span>
                @endif
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ $almacen->descripcion ?? 'Sin descripción' }}</p>
                <div class="d-flex gap-3 mb-3">
                    <div class="text-center">
                        <div class="fw-bold fs-5 text-primary">{{ $almacen->cajeros_count }}</div>
                        <small class="text-muted">Cajeros</small>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                @can('ver-almacenes')
                <a href="{{ route('almacenes.show', $almacen) }}" class="btn btn-info btn-sm flex-fill">
                    <i class="fas fa-eye me-1"></i>Ver
                </a>
                @endcan
                @can('editar-almacenes')
                <a href="{{ route('almacenes.edit', $almacen) }}" class="btn btn-warning btn-sm flex-fill">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                @endcan
                @can('eliminar-almacenes')
                <form action="{{ route('almacenes.destroy', $almacen) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar almacén? Los cajeros asignados quedarán sin almacén.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info text-center">
            No hay almacenes registrados.
            @can('crear-almacenes')
            <a href="{{ route('almacenes.create') }}">Crear el primero</a>
            @endcan
        </div>
    </div>
    @endforelse
</div>
@endsection
