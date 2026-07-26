@extends('layouts.app')

@section('page-title', 'Detalle de Corte de Caja')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-cash-register me-2"></i>Detalle de Corte de Caja #{{ $corte->id }}</span>
                <div>
                    @if($corte->estado == 'abierto')
                        <span class="badge bg-success">Abierto</span>
                    @else
                        <span class="badge bg-secondary">Cerrado</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <!-- Información del Corte -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Cajero:</strong>
                            <p class="mb-0">{{ $corte->user->name }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Fecha del Corte:</strong>
                            <p class="mb-0">{{ $corte->fecha_corte->format('d/m/Y') }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Hora de Apertura:</strong>
                            <p class="mb-0">{{ $corte->hora_apertura }}</p>
                        </div>
                        
                        @if($corte->hora_cierre)
                        <div class="mb-3">
                            <strong>Hora de Cierre:</strong>
                            <p class="mb-0">{{ $corte->hora_cierre }}</p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Monto Inicial:</strong>
                            <p class="mb-0 text-primary fs-5">Bs{{ number_format($corte->monto_inicial, 2) }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Total de Ventas:</strong>
                            <p class="mb-0 text-success fs-5">Bs{{ number_format($totalVentas, 2) }}</p>
                        </div>
                        
                        @if($corte->estado == 'cerrado')
                        <div class="mb-3">
                            <strong>Efectivo Contado:</strong>
                            <p class="mb-0 fs-5">Bs{{ number_format($corte->total_efectivo, 2) }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Monto Final:</strong>
                            <p class="mb-0 fs-5">Bs{{ number_format($corte->monto_final, 2) }}</p>
                        </div>
                        
                        @php
                            $diferenciaShow = $corte->monto_final - ($corte->monto_inicial + $corte->total_ventas);
                        @endphp
                        <div class="mb-3">
                            <strong>Diferencia:</strong>
                            <p class="mb-0">
                                @if(abs($diferenciaShow) < 0.01)
                                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Cuadra Perfecto</span>
                                    <small class="text-success d-block">El cajero entregó el monto exacto</small>
                                @elseif($diferenciaShow < 0)
                                    <span class="badge bg-danger fs-6"><i class="fas fa-exclamation-triangle me-1"></i>Falta Bs{{ number_format(abs($diferenciaShow), 2) }}</span>
                                    <small class="text-danger d-block">El cajero entregó menos de lo esperado</small>
                                @else
                                    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-arrow-up me-1"></i>Sobra Bs{{ number_format($diferenciaShow, 2) }}</span>
                                    <small class="text-warning d-block">El cajero entregó más de lo esperado</small>
                                @endif
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if($corte->observaciones)
                <div class="mb-4">
                    <strong>Observaciones:</strong>
                    <p class="mb-0">{{ $corte->observaciones }}</p>
                </div>
                @endif
                
                <hr>
                
                <!-- Ventas del Corte -->
                <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Ventas Realizadas</h5>
                @if($ventasCorte->count() > 0)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Número</th>
                                <th>Hora</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ventasCorte as $venta)
                                <tr>
                                    <td>{{ $venta->numero_venta }}</td>
                                    <td>{{ $venta->created_at->format('H:i:s') }}</td>
                                    <td>Bs{{ number_format($venta->total, 2) }}</td>
                                    <td>
                                        @if($venta->estado == 'completada')
                                            <span class="badge bg-success">Completada</span>
                                        @else
                                            <span class="badge bg-danger">Cancelada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('ventas.show', $venta->id) }}" 
                                           class="btn btn-info btn-sm" 
                                           title="Ver Venta">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total:</th>
                                <th>Bs{{ number_format($totalVentas, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No se han realizado ventas en este corte.
                </div>
                @endif
                
                <!-- Resumen del Corte -->
                @if($corte->estado == 'cerrado')
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">Resumen del Corte</h5>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Monto Inicial:</td>
                                <td class="text-end">Bs{{ number_format($corte->monto_inicial, 2) }}</td>
                            </tr>
                            <tr>
                                <td>+ Ventas del Turno:</td>
                                <td class="text-end text-success">+Bs{{ number_format($corte->total_ventas, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>= Efectivo Esperado:</strong></td>
                                <td class="text-end"><strong>Bs{{ number_format($corte->monto_inicial + $corte->total_ventas, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Efectivo Contado:</td>
                                <td class="text-end">Bs{{ number_format($corte->total_efectivo, 2) }}</td>
                            </tr>
                            @php
                                $difResumen = $corte->monto_final - ($corte->monto_inicial + $corte->total_ventas);
                                $claseResumen = abs($difResumen) < 0.01 ? 'success' : ($difResumen > 0 ? 'warning' : 'danger');
                            @endphp
                            <tr class="table-{{ $claseResumen }}">
                                <td><strong>Diferencia:</strong></td>
                                <td class="text-end">
                                    <strong>
                                        @if(abs($difResumen) < 0.01)
                                            Cuadra Perfecto (Bs0.00)
                                        @elseif($difResumen < 0)
                                            Falta Bs{{ number_format(abs($difResumen), 2) }}
                                        @else
                                            Sobra Bs{{ number_format($difResumen, 2) }}
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif
                
                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('cortes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <div>
                        @if($corte->estado == 'abierto' && $corte->user_id == Auth::id())
                        <a href="{{ route('cortes.edit', $corte->id) }}" class="btn btn-warning">
                            <i class="fas fa-lock me-2"></i>Cerrar Caja
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection