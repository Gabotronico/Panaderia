@extends('layouts.app')

@section('page-title', 'Detalle de Receta')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-book-open me-2"></i>{{ $receta->nombre }}</span>
                @if($receta->activo)
                    <span class="badge bg-success">Activa</span>
                @else
                    <span class="badge bg-secondary">Inactiva</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="fas fa-layer-group me-1"></i>Rinde {{ $receta->rendimiento }} unidades
                    </span>
                </div>

                @if($receta->descripcion)
                    <p class="text-muted mb-4">{{ $receta->descripcion }}</p>
                @endif

                <h6 class="mb-3"><i class="fas fa-boxes me-2"></i>Insumos necesarios</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-end">Cantidad a descontar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receta->insumos as $insumo)
                                <tr>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $insumo->unidad_medida }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($insumo->pivot->cantidad_necesaria, 2) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('recetas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <div class="d-flex gap-2">
                        @can('crear-recetas')
                        <a href="{{ route('recetas.formUsar', $receta) }}" class="btn btn-success">
                            <i class="fas fa-industry me-2"></i>Usar Receta
                        </a>
                        @endcan
                        @can('editar-recetas')
                        <a href="{{ route('recetas.edit', $receta) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
