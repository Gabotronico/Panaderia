@extends('layouts.app')
@section('page-title', 'Editar Usuario')
@section('content')

<x-page-header title="Editar Usuario" icon="user-pen"
               :subtitle="$usuario->name"
               :back="route('usuarios.index')" />

<x-alerts />

<div class="card" style="max-width:920px;">
    <div class="card-body">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
            @csrf @method('PUT')
            @include('usuarios._form')

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar cambios
                </button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
