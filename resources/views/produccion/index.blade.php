@extends('layouts.app')
@section('page-title', 'Producción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-industry me-2"></i>Producción</h4>
    @can('crear-produccion')
    <a href="{{ route('produccion.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Registrar Producción
    </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Insumos consumidos</th>
                        <th>Productos obtenidos</th>
                        <th class="text-end">Costo</th>
                        <th class="text-end">Costo/unidad</th>
                        <th>Registró</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($producciones as $produccion)
                    <tr>
                        <td class="text-muted">{{ $produccion->id }}</td>
                        <td class="text-nowrap">{{ $produccion->fecha->format('d/m/Y') }}</td>
                        <td>
                            @foreach($produccion->insumos as $insumo)
                                <div class="small">
                                    <span class="text-muted">{{ rtrim(rtrim(number_format($insumo->pivot->cantidad, 3, '.', ''), '0'), '.') }}
                                        {{ $insumo->unidad_medida }}</span>
                                    {{ $insumo->nombre }}
                                </div>
                            @endforeach
                        </td>
                        <td>
                            @foreach($produccion->productos as $producto)
                                <div class="small">
                                    <span class="badge bg-success">{{ $producto->pivot->cantidad }}</span>
                                    {{ $producto->nombre }}
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">Bs {{ number_format($produccion->costo_total, 2) }}</td>
                        <td class="text-end text-muted">Bs {{ number_format($produccion->costo_por_unidad, 2) }}</td>
                        <td class="text-muted small">{{ $produccion->user->name ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('produccion.show', $produccion) }}" class="btn btn-info btn-sm" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('eliminar-produccion')
                            @php
                                $aviso = 'Vas a anular la producción #' . $produccion->id
                                    . ' del ' . $produccion->fecha->format('d/m/Y') . '.\n\n'
                                    . 'Los insumos vuelven al stock y los productos se descuentan.\n\n'
                                    . '¿Continuar?';
                            @endphp
                            <form action="{{ route('produccion.destroy', $produccion) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('{{ $aviso }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Anular producción">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-industry fa-2x mb-3 opacity-50 d-block"></i>
                            Todavía no registraste ninguna producción.
                            @can('crear-produccion')
                                <div class="mt-3">
                                    <a href="{{ route('produccion.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i>Registrar la primera
                                    </a>
                                </div>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($producciones->hasPages())
            <div class="p-3">{{ $producciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
