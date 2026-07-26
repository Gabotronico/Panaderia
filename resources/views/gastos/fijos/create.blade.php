@extends('layouts.app')
@section('page-title', 'Nuevo Gasto Fijo')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-plus-circle me-2"></i>Nuevo Gasto Fijo</h4>
    <a href="{{ route('gastos-fijos.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <form action="{{ route('gastos-fijos.store') }}" method="POST">
            @csrf
            @include('gastos.fijos._form')
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
