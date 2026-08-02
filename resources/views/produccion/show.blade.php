@extends('layouts.app')
@section('page-title', 'Producción #' . $produccion->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">
        <i class="fas fa-industry me-2"></i>Producción #{{ $produccion->id }}
        <span class="text-muted fs-6">· {{ $produccion->fecha->format('d/m/Y') }}</span>
    </h4>
    <div class="d-flex gap-2 flex-wrap">
        @can('eliminar-produccion')
        @php
            $aviso = 'Vas a anular la producción #' . $produccion->id . '.\n\n'
                . 'Los insumos vuelven al stock y los productos se descuentan.\n\n'
                . '¿Continuar?';
        @endphp
        <form action="{{ route('produccion.destroy', $produccion) }}" method="POST"
              onsubmit="return confirm('{{ $aviso }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-rotate-left me-1"></i>Anular Producción
            </button>
        </form>
        @endcan
        <a href="{{ route('produccion.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Resumen --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Costo de insumos</div>
                <div class="fw-bold fs-5">Bs {{ number_format($produccion->costo_total, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Unidades producidas</div>
                <div class="fw-bold fs-5 text-success">{{ $produccion->unidades_producidas }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-primary">
            <div class="card-body py-3">
                <div class="text-muted small">Costo por unidad</div>
                <div class="fw-bold fs-5 text-primary">Bs {{ number_format($produccion->costo_por_unidad, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Registró</div>
                <div class="fw-semibold">{{ $produccion->user->name ?? '—' }}</div>
                <div class="text-muted" style="font-size:.72rem;">{{ $produccion->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>

@if($produccion->observaciones)
<div class="alert alert-light border">
    <i class="fas fa-note-sticky me-2 text-muted"></i>{{ $produccion->observaciones }}
</div>
@endif

<div class="row g-3">
    {{-- Insumos consumidos --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <span class="fw-semibold"><i class="fas fa-wheat-awn me-2 text-warning"></i>Insumos consumidos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Costo unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($produccion->insumos as $insumo)
                            @php $subtotal = (float) $insumo->pivot->cantidad * (float) $insumo->pivot->costo_unitario; @endphp
                            <tr>
                                <td>{{ $insumo->nombre }}</td>
                                <td class="text-end">
                                    {{ rtrim(rtrim(number_format($insumo->pivot->cantidad, 3, '.', ''), '0'), '.') }}
                                    <span class="text-muted">{{ $insumo->unidad_medida }}</span>
                                </td>
                                <td class="text-end text-muted">Bs {{ number_format($insumo->pivot->costo_unitario, 2) }}</td>
                                <td class="text-end">Bs {{ number_format($subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">Total:</td>
                                <td class="text-end">Bs {{ number_format($produccion->costo_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-3 py-2 text-muted" style="font-size:.72rem;">
                    El costo unitario es el que regía el día de la producción, no el actual del insumo.
                </div>
            </div>
        </div>
    </div>

    {{-- Productos obtenidos --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <span class="fw-semibold"><i class="fas fa-bread-slice me-2 text-success"></i>Productos obtenidos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Unidades</th>
                                <th class="text-end">Stock actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($produccion->productos as $producto)
                            <tr>
                                <td>
                                    <a href="{{ route('productos.show', $producto) }}" class="text-decoration-none">
                                        {{ $producto->nombre }}
                                    </a>
                                </td>
                                <td class="text-end"><span class="badge bg-success">{{ $producto->pivot->cantidad }}</span></td>
                                <td class="text-end text-muted">{{ $producto->stock }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="text-end">Total:</td>
                                <td class="text-end">{{ $produccion->unidades_producidas }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-3 py-2 text-muted" style="font-size:.72rem;">
                    Las unidades quedaron en bodega. Para entregarlas a un cajero, transferilas desde Productos.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
