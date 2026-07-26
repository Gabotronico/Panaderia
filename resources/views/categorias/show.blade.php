@extends('layouts.app')

@section('page-title', 'Detalle de Categoría')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-eye me-2"></i>Detalle de Categoría
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>ID:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $categoria->id }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Nombre:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $categoria->nombre }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Descripción:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $categoria->descripcion ?? 'N/A' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Estado:</strong>
                    </div>
                    <div class="col-md-9">
                        @if($categoria->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-danger">Inactivo</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Fecha de Creación:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $categoria->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Última Actualización:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $categoria->updated_at->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('categorias.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    @can('editar-categorias')
                    <a href="{{ route('categorias.edit', $categoria->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection