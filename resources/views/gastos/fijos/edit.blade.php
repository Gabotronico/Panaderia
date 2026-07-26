@extends('layouts.app')
@section('page-title', 'Editar Gasto Fijo')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-edit me-2"></i>Editar: {{ $gasto->nombre }}</h4>
    <a href="{{ route('gastos-fijos.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <form action="{{ route('gastos-fijos.update', $gasto) }}" method="POST">
            @csrf @method('PUT')
            @include('gastos.fijos._form')
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                           @checked($gasto->activo)>
                    <label class="form-check-label" for="activo">Activo (incluir al generar nuevos meses)</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Actualizar
            </button>
        </form>
    </div>
</div>
@endsection
