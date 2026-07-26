@extends('layouts.app')
@section('page-title', 'Gastos Fijos')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Gastos Fijos</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('gastos-pagos.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-calendar-alt me-1"></i>Control de Pagos
        </a>
        <a href="{{ route('gastos-fijos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nuevo Gasto
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    $categorias = ['alquiler'=>'danger','servicios'=>'primary','mantenimiento'=>'warning','impuestos'=>'dark','otro'=>'secondary'];
    $frecLabels = ['mensual'=>'Mensual','bimestral'=>'Bimestral','trimestral'=>'Trimestral','semestral'=>'Semestral','anual'=>'Anual'];
    $meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th class="text-end">Monto estimado</th>
                        <th>Frecuencia</th>
                        <th class="text-center">Vence día</th>
                        <th>Proveedor</th>
                        <th class="text-center">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastos as $g)
                    <tr class="{{ $g->activo ? '' : 'table-secondary text-muted' }}">
                        <td>
                            <strong>{{ $g->nombre }}</strong>
                            @if($g->observaciones)
                                <br><small class="text-muted">{{ Str::limit($g->observaciones, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $categorias[$g->categoria] ?? 'secondary' }}">
                                {{ \App\Models\GastoFijo::etiquetaCategoria($g->categoria) }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">Bs {{ number_format($g->monto_estimado, 2) }}</td>
                        <td>
                            {{ $frecLabels[$g->frecuencia] ?? $g->frecuencia }}
                            @if($g->frecuencia !== 'mensual')
                                <br><small class="text-muted">desde {{ $meses[$g->mes_inicio] }}</small>
                            @endif
                        </td>
                        <td class="text-center">Día {{ $g->dia_vencimiento }}</td>
                        <td>{{ $g->proveedor ?? '—' }}</td>
                        <td class="text-center">
                            @if($g->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('gastos-fijos.edit', $g) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('gastos-fijos.destroy', $g) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar «{{ $g->nombre }}»? Se eliminarán también sus registros de pago.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay gastos fijos registrados.
                            <a href="{{ route('gastos-fijos.create') }}">Agregar el primero</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($gastos->where('activo', true)->count())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2">Total estimado mensual (activos):</td>
                        <td class="text-end">
                            Bs {{ number_format($gastos->where('activo', true)->where('frecuencia', 'mensual')->sum('monto_estimado'), 2) }}
                        </td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
