@extends('layouts.app')
@section('page-title', 'Nuevo Usuario')
@section('content')

<x-page-header title="Nuevo Usuario" icon="user-plus"
               subtitle="Dar de alta un administrador o un cajero"
               :back="route('usuarios.index')" />

<x-alerts />

<div class="card" style="max-width:920px;">
    <div class="card-body">
        <form action="{{ route('usuarios.store') }}" method="POST">
            @csrf
            @include('usuarios._form')

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Crear usuario
                </button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
