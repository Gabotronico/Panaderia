@extends('layouts.app')
@section('page-title', 'Editar Cargo')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-briefcase me-2"></i>Editar Cargo</h4>
        <a href="{{ route('cargos.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form action="{{ route('cargos.update', $cargo) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-bold">Nombre del cargo <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre', $cargo->nombre) }}" required>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Descripción</label>
                <input type="text" name="descripcion" class="form-control"
                       value="{{ old('descripcion', $cargo->descripcion) }}" maxlength="255">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Actualizar
            </button>
        </form>
    </div>
</div>
@endsection
