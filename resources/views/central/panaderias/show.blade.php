@extends('layouts.central')

@section('title', $panaderia->negocio ?? $panaderia->getTenantKey())

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 titulo mb-1">{{ $panaderia->negocio ?? $panaderia->getTenantKey() }}</h1>
        <p class="text-muted mb-0">
            <code class="id">{{ $panaderia->getTenantKey() }}</code>
            · {{ $panaderia->urlDesde(request()) }}
            · alta {{ $panaderia->created_at?->format('d/m/Y') }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ $panaderia->urlDesde(request()) }}" target="_blank" rel="noopener" class="btn btn-light border">
            <i class="fas fa-arrow-up-right-from-square me-1"></i>Abrir
        </a>
        <a href="{{ route('central.panaderias.index') }}" class="btn btn-light border">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="h6 titulo mb-3"><i class="fas fa-user-tie me-2"></i>Administradores</h2>

                @forelse ($administradores as $admin)
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span class="fw-semibold">{{ $admin['name'] }}</span>
                        <span class="text-muted">{{ $admin['email'] }}</span>
                    </div>
                @empty
                    <p class="text-warning-emphasis small mb-0">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        Esta panadería no tiene administradores: nadie puede ingresar todavía.
                    </p>
                @endforelse

                <hr class="my-4">

                <h3 class="h6 titulo mb-1">Crear o restablecer administrador</h3>
                <p class="small text-muted">Con un correo que ya existe, se le asigna esta contraseña nueva.</p>

                <form method="POST" action="{{ route('central.panaderias.administrador', $panaderia) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <input name="nombre" value="{{ old('nombre') }}" required maxlength="100"
                                   class="form-control" placeholder="Nombre" aria-label="Nombre">
                        </div>
                        <div class="col-sm-6">
                            <input name="email" type="email" value="{{ old('email') }}" required maxlength="150"
                                   class="form-control" placeholder="Correo" aria-label="Correo">
                        </div>
                        <div class="col-sm-6">
                            <input name="password" type="password" required autocomplete="new-password"
                                   class="form-control" placeholder="Contraseña" aria-label="Contraseña">
                        </div>
                        <div class="col-sm-6">
                            <input name="password_confirmation" type="password" required autocomplete="new-password"
                                   class="form-control" placeholder="Repetir contraseña" aria-label="Repetir contraseña">
                        </div>
                    </div>
                    <div class="form-text mb-3">Mínimo 10 caracteres, con mayúsculas, minúsculas y números.</div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-1"></i>Guardar administrador
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6 d-flex flex-column gap-4">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="h6 titulo mb-1"><i class="fas fa-file-import me-2"></i>Importar respaldo</h2>
                <p class="small text-muted">
                    Reemplaza todos los datos de la panadería, usuarios incluidos, con los de un .zip de la
                    aplicación de escritorio o de la web. Antes se guarda una copia de los datos actuales.
                </p>

                <form method="POST" action="{{ route('central.panaderias.importar', $panaderia) }}"
                      enctype="multipart/form-data"
                      onsubmit="return confirm('¿Reemplazar todos los datos de {{ $panaderia->getTenantKey() }}?');">
                    @csrf
                    <input name="respaldo" type="file" accept=".zip,application/zip" required class="form-control mb-3">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirmacion" value="1" id="confirmar-importacion" required>
                        <label class="form-check-label small" for="confirmar-importacion">
                            Entiendo que se reemplazan los datos actuales.
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="fas fa-upload me-1"></i>Importar
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-danger-subtle">
            <div class="card-body p-4">
                <h2 class="h6 text-danger mb-1"><i class="fas fa-trash me-2"></i>Eliminar panadería</h2>
                <p class="small text-muted">
                    Borra su base de datos y sus archivos. No se puede deshacer:
                    descargá antes un respaldo desde la panadería.
                </p>

                <form method="POST" action="{{ route('central.panaderias.destroy', $panaderia) }}">
                    @csrf
                    @method('DELETE')
                    <label for="confirmacion-borrado" class="form-label small">
                        Escribí <code class="id">{{ $panaderia->getTenantKey() }}</code> para confirmar
                    </label>
                    <div class="input-group">
                        <input id="confirmacion-borrado" name="confirmacion" required autocomplete="off" class="form-control">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
