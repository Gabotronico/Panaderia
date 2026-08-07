@extends('layouts.app')
@section('page-title', 'Nuevo Gasto Variable')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Nuevo Gasto Variable</h4>
    <a href="{{ route('gastos-variables.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
</div>

<div class="card" style="max-width:820px;">
    <div class="card-body">
        <form action="{{ route('gastos-variables.store') }}" method="POST">
            @csrf
            @include('gastos.variables._form')
            <div class="mt-3">
                <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Registrar Gasto</button>
            </div>
        </form>
    </div>
</div>
@endsection
