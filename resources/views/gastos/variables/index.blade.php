@extends('layouts.app')
@section('page-title', 'Gastos Variables')

@section('content')
@php
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $anterior = $inicio->copy()->subMonth();
    $siguiente = $inicio->copy()->addMonth();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Gastos Variables</h4>
        <small class="text-muted">Gastos ocasionales: transporte, reparaciones, compras de urgencia</small>
    </div>
    <a href="{{ route('gastos-variables.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nuevo Gasto
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Filtro por mes --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('gastos-variables.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('gastos-variables.index', ['mes' => $anterior->month, 'anio' => $anterior->year]) }}"
               class="btn btn-outline-secondary" title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
            <select name="mes" class="form-select" style="max-width:11rem;">
                @foreach($meses as $i => $nombre)
                    <option value="{{ $i + 1 }}" @selected($mes == $i + 1)>{{ $nombre }}</option>
                @endforeach
            </select>
            <input type="number" name="anio" class="form-control" style="max-width:6.5rem;"
                   value="{{ $anio }}" min="2000" max="2999">
            <button class="btn btn-primary px-3">Ir</button>
            <a href="{{ route('gastos-variables.index', ['mes' => $siguiente->month, 'anio' => $siguiente->year]) }}"
               class="btn btn-outline-secondary" title="Mes siguiente"><i class="fas fa-chevron-right"></i></a>

            <div class="ms-auto text-end">
                <div class="text-muted small">Total del mes</div>
                <div class="fs-4 fw-bold text-danger">Bs {{ number_format($total, 2) }}</div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    {{-- Detalle --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ ucfirst($inicio->locale('es')->isoFormat('MMMM [de] YYYY')) }}</span>
                <span class="text-muted small">{{ $gastos->count() }} {{ $gastos->count() === 1 ? 'gasto' : 'gastos' }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gastos as $gasto)
                            <tr>
                                <td class="text-nowrap">{{ $gasto->fecha->format('d/m/Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $gasto->concepto }}</div>
                                    @if($gasto->proveedor)
                                        <small class="text-muted">{{ $gasto->proveedor }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $gasto->etiqueta_categoria }}</span></td>
                                <td class="text-end fw-semibold">Bs {{ number_format($gasto->monto, 2) }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('gastos-variables.edit', $gasto) }}"
                                       class="btn btn-warning btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @php
                                        $aviso = 'Vas a eliminar el gasto "' . $gasto->concepto . '" de Bs '
                                            . number_format($gasto->monto, 2) . '.\n\n¿Continuar?';
                                    @endphp
                                    <form action="{{ route('gastos-variables.destroy', $gasto) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('{{ $aviso }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-receipt fa-2x mb-3 opacity-50 d-block"></i>
                                    No hay gastos variables en este mes.
                                    <div class="mt-3">
                                        <a href="{{ route('gastos-variables.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus me-1"></i>Registrar uno
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($gastos->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td class="text-end text-danger">Bs {{ number_format($total, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Reparto por categoría --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <span class="fw-semibold"><i class="fas fa-chart-pie me-2 text-muted"></i>Por categoría</span>
            </div>
            <div class="card-body">
                @forelse($porCategoria as $categoria => $monto)
                    @php $pct = $total > 0 ? round($monto / $total * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small">{{ \App\Models\GastoVariable::CATEGORIAS[$categoria] ?? ucfirst($categoria) }}</span>
                            <span class="small fw-semibold">Bs {{ number_format($monto, 2) }}</span>
                        </div>
                        <div class="progress" style="height:.5rem;">
                            <div class="progress-bar bg-danger" style="width: {{ $pct }}%;"></div>
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">{{ $pct }}% del mes</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Sin gastos para mostrar.</p>
                @endforelse
            </div>
        </div>

        <div class="alert alert-light border mt-3 mb-0" style="font-size:.82rem;">
            <i class="fas fa-circle-info me-1 text-primary"></i>
            Estos gastos se restan de la <strong>utilidad bruta</strong> en el resumen financiero,
            junto con las compras de insumos.
        </div>
    </div>
</div>
@endsection
