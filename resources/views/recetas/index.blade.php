@extends('layouts.app')

@section('page-title', 'Recetas')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-book-open me-2"></i>Gestión de Recetas</h4>
            @can('crear-recetas')
            <a href="{{ route('recetas.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Receta
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center">Rinde</th>
                        <th class="text-center">Insumos</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recetas as $receta)
                        <tr>
                            <td><strong>{{ $receta->nombre }}</strong></td>
                            <td class="text-muted">{{ Str::limit($receta->descripcion, 60) ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark">{{ $receta->rendimiento }} uds.</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $receta->insumos_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($receta->activo)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('crear-recetas')
                                <a href="{{ route('recetas.formUsar', $receta) }}" class="btn btn-success btn-sm" title="Usar receta (producción)">
                                    <i class="fas fa-industry"></i>
                                </a>
                                @endcan
                                @can('ver-recetas')
                                <a href="{{ route('recetas.show', $receta) }}" class="btn btn-info btn-sm" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                @can('editar-recetas')
                                <a href="{{ route('recetas.edit', $receta) }}" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('eliminar-recetas')
                                <form action="{{ route('recetas.destroy', $receta) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar esta receta?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay recetas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $recetas->links() }}
@endsection
