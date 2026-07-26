@extends('layouts.app')
@section('page-title', 'Planillas')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-file-invoice-dollar me-2"></i>Planillas de Pago</h4>
        <a href="{{ route('planillas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Generar Planilla
        </a>
    </div>
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
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th class="text-center">Empleados</th>
                        <th class="text-end">Total General</th>
                        <th>Estado</th>
                        <th>Generada por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planillas as $planilla)
                    <tr>
                        <td>{{ $planilla->id }}</td>
                        <td>
                            @if($planilla->tipo === 'mensual')
                                <span class="badge bg-primary">Mensual</span>
                            @else
                                <span class="badge bg-info">Semanal</span>
                            @endif
                        </td>
                        <td>
                            {{ $planilla->periodo_inicio->format('d/m/Y') }} —
                            {{ $planilla->periodo_fin->format('d/m/Y') }}
                        </td>
                        <td class="text-center">{{ $planilla->detalles_count }}</td>
                        <td class="text-end fw-bold text-success">Bs {{ number_format($planilla->total_general, 2) }}</td>
                        <td>
                            @php
                                $estadoBadge = ['borrador'=>'warning text-dark','cerrada'=>'secondary','pagada'=>'success'];
                                $estadoLabel = ['borrador'=>'Borrador','cerrada'=>'Cerrada','pagada'=>'Pagada'];
                            @endphp
                            <span class="badge bg-{{ $estadoBadge[$planilla->estado] ?? 'secondary' }}">
                                {{ $estadoLabel[$planilla->estado] ?? $planilla->estado }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $planilla->user->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('planillas.show', $planilla) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay planillas generadas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $planillas->links() }}</div>
    </div>
</div>
@endsection
