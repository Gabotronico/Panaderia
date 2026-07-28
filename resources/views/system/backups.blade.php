@extends('layouts.app')

@section('page-title', 'Respaldos')

@section('content')
<x-page-header title="Respaldos del sistema" icon="database"
    subtitle="Proteja las ventas, el inventario, los usuarios y las imágenes de productos." />

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                        <i class="fas fa-download fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Crear respaldo</h5>
                        <p class="text-muted mb-0">Descarga un ZIP con la base de datos y las imágenes guardadas.</p>
                    </div>
                </div>

                <dl class="row small mb-4">
                    <dt class="col-6 text-muted fw-normal">Tamaño de la base</dt>
                    <dd class="col-6 text-end fw-semibold">{{ number_format($databaseSize / 1024, 1) }} KB</dd>
                    <dt class="col-6 text-muted fw-normal">Última modificación</dt>
                    <dd class="col-6 text-end fw-semibold">
                        {{ $lastModified?->format('d/m/Y H:i') ?? 'Sin información' }}
                    </dd>
                </dl>

                <a href="{{ route('system.backups.download') }}" class="btn btn-primary w-100">
                    <i class="fas fa-file-arrow-down me-2"></i>Descargar respaldo ahora
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 border-warning-subtle">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning-emphasis p-3">
                        <i class="fas fa-clock-rotate-left fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Restaurar respaldo</h5>
                        <p class="text-muted mb-0">Reemplaza los datos actuales con los contenidos en un respaldo anterior.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('system.backups.restore') }}" enctype="multipart/form-data"
                      onsubmit="return confirm('¿Restaurar este respaldo? Los datos actuales serán reemplazados.');">
                    @csrf
                    <div class="mb-3">
                        <label for="backup" class="form-label fw-semibold">Archivo ZIP</label>
                        <input id="backup" name="backup" type="file" accept=".zip,application/zip"
                               class="form-control @error('backup') is-invalid @enderror" required>
                        @error('backup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input id="confirmation" name="confirmation" value="1" type="checkbox"
                               class="form-check-input @error('confirmation') is-invalid @enderror" required>
                        <label for="confirmation" class="form-check-label">
                            Confirmo que deseo reemplazar los datos actuales.
                        </label>
                        @error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="fas fa-upload me-2"></i>Restaurar respaldo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0">
    <i class="fas fa-circle-info me-2"></i>
    Guarde al menos una copia fuera de esta PC, por ejemplo en una memoria USB o almacenamiento en la nube.
    Antes de restaurar, el sistema conserva automáticamente una copia de seguridad de la base actual.
</div>
@endsection
