@extends('layouts.central')

@section('title', 'Nueva panadería')

@section('content')
@php
    $modo = old('modo', 'administrador');
    $base = filter_var(request()->getHost(), FILTER_VALIDATE_IP) !== false ? 'localhost' : request()->getHost();
@endphp

<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <h1 class="h4 titulo mb-0"><i class="fas fa-plus me-2"></i>Nueva panadería</h1>
    <a href="{{ route('central.panaderias.index') }}" class="btn btn-light border">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<form method="POST" action="{{ route('central.panaderias.store') }}" enctype="multipart/form-data"
      class="row g-4" id="form-alta">
    @csrf

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="h6 titulo mb-3">Datos de la panadería</h2>

                <div class="mb-3">
                    <label for="negocio" class="form-label fw-semibold">Nombre del negocio</label>
                    <input id="negocio" name="negocio" value="{{ old('negocio') }}" required maxlength="100"
                           class="form-control @error('negocio') is-invalid @enderror" placeholder="Panadería Luna">
                    @error('negocio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="id" class="form-label fw-semibold">Id / subdominio</label>
                    <div class="input-group">
                        <input id="id" name="id" value="{{ old('id') }}" required maxlength="40"
                               pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?"
                               class="form-control @error('id') is-invalid @enderror" placeholder="luna">
                        <span class="input-group-text">.{{ $base }}</span>
                        @error('id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">Minúsculas, números y guiones. No se puede cambiar después.</div>
                </div>

                <div class="mb-0">
                    <label for="dominios_texto" class="form-label fw-semibold">
                        Dominios propios <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input id="dominios_texto" name="dominios_texto" value="{{ old('dominios_texto') }}"
                           class="form-control @error('dominios.*') is-invalid @enderror"
                           placeholder="panaderialuna.com">
                    @error('dominios.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Separados por coma. El dominio tiene que apuntar a este servidor.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="h6 titulo mb-3">¿Cómo empieza?</h2>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="modo" id="modo-admin" value="administrador"
                           @checked($modo === 'administrador')>
                    <label class="form-check-label" for="modo-admin">
                        <strong>Vacía, con un administrador nuevo</strong>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="modo" id="modo-respaldo" value="respaldo"
                           @checked($modo === 'respaldo')>
                    <label class="form-check-label" for="modo-respaldo">
                        <strong>Con los datos de un respaldo</strong>
                        <span class="d-block small text-muted">Un .zip de la aplicación de escritorio o de la web. Trae sus propios usuarios.</span>
                    </label>
                </div>

                <div data-modo="administrador" @class(['d-none' => $modo !== 'administrador'])>
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre del administrador</label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100"
                               class="form-control @error('nombre') is-invalid @enderror">
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <input id="password" name="password" type="password" autocomplete="new-password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Repetir</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                   autocomplete="new-password" class="form-control">
                        </div>
                    </div>
                    <div class="form-text">Mínimo 10 caracteres, con mayúsculas, minúsculas y números.</div>
                </div>

                <div data-modo="respaldo" @class(['d-none' => $modo !== 'respaldo'])>
                    <label for="respaldo" class="form-label fw-semibold">Archivo de respaldo (.zip)</label>
                    <input id="respaldo" name="respaldo" type="file" accept=".zip,application/zip"
                           class="form-control @error('respaldo') is-invalid @enderror">
                    @error('respaldo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary btn-lg" id="btn-alta">
            <i class="fas fa-check me-1"></i>Crear panadería
        </button>
    </div>
</form>

<script>
    // Muestra solo los campos del modo elegido y avisa que el alta demora:
    // crea y migra una base entera.
    document.querySelectorAll('input[name="modo"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('[data-modo]').forEach(function (bloque) {
                bloque.classList.toggle('d-none', bloque.dataset.modo !== radio.value);
            });
        });
    });

    document.getElementById('form-alta').addEventListener('submit', function () {
        const boton = document.getElementById('btn-alta');
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando la base de datos…';
    });
</script>
@endsection
