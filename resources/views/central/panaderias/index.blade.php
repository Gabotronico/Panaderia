@extends('layouts.central')

@section('title', 'Panaderías')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 titulo mb-1"><i class="fas fa-store me-2"></i>Panaderías</h1>
        <p class="text-muted mb-0">Cada panadería tiene su propia base de datos y su propio subdominio.</p>
    </div>
    <a href="{{ route('central.panaderias.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nueva panadería
    </a>
</div>

<div class="card">
    @if ($panaderias->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-store-slash fa-2x mb-3 d-block"></i>
            Todavía no hay panaderías. Creá la primera con «Nueva panadería».
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Panadería</th>
                        <th>Id</th>
                        <th class="d-none d-md-table-cell">Dominios</th>
                        <th class="d-none d-lg-table-cell">Alta</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($panaderias as $panaderia)
                        <tr>
                            <td class="fw-semibold">{{ $panaderia->negocio ?? $panaderia->getTenantKey() }}</td>
                            <td><code class="id">{{ $panaderia->getTenantKey() }}</code></td>
                            <td class="d-none d-md-table-cell small text-muted">
                                {{ $panaderia->domains->pluck('domain')->implode(', ') }}
                            </td>
                            <td class="d-none d-lg-table-cell small text-muted">
                                {{ $panaderia->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ $panaderia->urlDesde(request()) }}" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-light border" title="Abrir la panadería">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                </a>
                                <a href="{{ route('central.panaderias.show', $panaderia) }}" class="btn btn-sm btn-primary">
                                    Administrar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
