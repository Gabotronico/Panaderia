@extends('layouts.app')

@section('page-title', 'Cortes de Caja')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-cash-register me-2"></i>Gestión de Cortes de Caja</h4>
            @can('crear-cortes')
                @if(!$corteAbierto)
                <a href="{{ route('cortes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Abrir Caja
                </a>
                @else
                <a href="{{ route('cortes.edit', $corteAbierto->id) }}" class="btn btn-warning">
                    <i class="fas fa-lock me-2"></i>Cerrar Caja
                </a>
                @endif
            @endcan
        </div>
    </div>
</div>

<!-- Alerta de Caja Abierta -->
@if($corteAbierto)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-2"></i>
                <strong>Tienes una caja abierta</strong> - Apertura: {{ $corteAbierto->created_at->format('d/m/Y H:i') }}
            </div>
            <a href="{{ route('cortes.show', $corteAbierto->id) }}" class="btn btn-sm btn-info">
                Ver Detalle
            </a>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Historial de Cortes de Caja
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cajero</th>
                                <th>Fecha</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Monto Inicial</th>
                                <th>Total Ventas</th>
                                <th>Monto Final</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cortes as $corte)
                                @php
                                    // diferencia = efectivo contado - (monto inicial + ventas)
                                    // Se calcula desde los datos originales para corregir registros con fórmula anterior
                                    $diferenciaReal = ($corte->estado == 'cerrado')
                                        ? ($corte->monto_final - ($corte->monto_inicial + $corte->total_ventas))
                                        : 0;
                                    $esperado = $corte->monto_inicial + $corte->total_ventas;

                                    if ($corte->estado == 'abierto') {
                                        $bgColorDiferencia = '';
                                        $textColorDiferencia = 'text-muted';
                                        $badgeColor = 'secondary';
                                        $icono = '';
                                        $badgeLabel = '-';
                                        $mensaje = '';
                                    } elseif (abs($diferenciaReal) < 0.01) {
                                        $bgColorDiferencia = 'background-color: #d1e7dd;';
                                        $textColorDiferencia = 'text-success';
                                        $badgeColor = 'success';
                                        $icono = '<i class="fas fa-check-circle me-1"></i>';
                                        $badgeLabel = 'Cuadra Perfecto';
                                        $mensaje = 'Contado: Bs' . number_format($corte->monto_final, 2) . ' / Esperado: Bs' . number_format($esperado, 2);
                                    } elseif ($diferenciaReal < 0) {
                                        $bgColorDiferencia = 'background-color: #f8d7da;';
                                        $textColorDiferencia = 'text-danger';
                                        $badgeColor = 'danger';
                                        $icono = '<i class="fas fa-exclamation-triangle me-1"></i>';
                                        $badgeLabel = 'Falta Bs' . number_format(abs($diferenciaReal), 2);
                                        $mensaje = 'Contado: Bs' . number_format($corte->monto_final, 2) . ' / Esperado: Bs' . number_format($esperado, 2);
                                    } else {
                                        $bgColorDiferencia = 'background-color: #fff3cd;';
                                        $textColorDiferencia = 'text-warning';
                                        $badgeColor = 'warning';
                                        $icono = '<i class="fas fa-arrow-up me-1"></i>';
                                        $badgeLabel = 'Sobra Bs' . number_format($diferenciaReal, 2);
                                        $mensaje = 'Contado: Bs' . number_format($corte->monto_final, 2) . ' / Esperado: Bs' . number_format($esperado, 2);
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $corte->id }}</td>
                                    <td>{{ $corte->user->name }}</td>
                                    <td>{{ $corte->fecha_corte->format('d/m/Y') }}</td>
                                    <td>{{ $corte->hora_apertura }}</td>
                                    <td>{{ $corte->hora_cierre ?? '-' }}</td>
                                    <td>Bs{{ number_format($corte->monto_inicial, 2) }}</td>
                                    <td>Bs{{ number_format($corte->total_ventas, 2) }}</td>
                                    <td>
                                        @if($corte->estado == 'cerrado')
                                            Bs{{ number_format($corte->monto_final, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td style="{{ $bgColorDiferencia }} padding: 12px;">
                                        @if($corte->estado == 'cerrado')
                                            <span class="badge bg-{{ $badgeColor }} fs-6 d-block mb-1">
                                                {!! $icono !!}{{ $badgeLabel }}
                                            </span>
                                            @if($mensaje)
                                            <small class="{{ $textColorDiferencia }}">
                                                {{ $mensaje }}
                                            </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($corte->estado == 'abierto')
                                            <span class="badge bg-success">Abierto</span>
                                        @else
                                            <span class="badge bg-secondary">Cerrado</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('ver-cortes')
                                        <a href="{{ route('cortes.show', $corte->id) }}" 
                                           class="btn btn-info btn-sm" 
                                           title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endcan
                                        
                                        @can('editar-cortes')
                                        @php
                                            $esPropio  = $corte->user_id == Auth::id();
                                            $esAdmin   = Auth::user()->esAdministrador();
                                            $puedeCerrar = $corte->estado == 'abierto' && ($esPropio || $esAdmin);
                                        @endphp
                                        @if($puedeCerrar)
                                        <a href="{{ route('cortes.edit', $corte->id) }}"
                                           class="btn btn-warning btn-sm"
                                           title="{{ $esPropio ? 'Cerrar Caja' : 'Cerrar la caja de ' . $corte->user->name }}"
                                           @unless($esPropio)
                                               onclick="return confirm('Vas a cerrar la caja de {{ $corte->user->name }}. Quedará registrado que el cierre lo hiciste vos. ¿Continuar?')"
                                           @endunless>
                                            <i class="fas fa-lock"></i>
                                            @unless($esPropio)<i class="fas fa-user-shield ms-1" style="font-size:.65rem;"></i>@endunless
                                        </a>
                                        @endif
                                        @endcan
                                        
                                        @can('eliminar-cortes')
                                        @if($corte->estado == 'abierto')
                                        <form action="{{ route('cortes.destroy', $corte->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('¿Está seguro de eliminar este corte?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No hay cortes de caja registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $cortes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Efecto hover en las celdas de diferencia con color */
    td[style*="background-color"]:hover {
        filter: brightness(0.92);
        cursor: default;
    }
</style>
@endpush