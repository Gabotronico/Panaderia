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

                        @if($corte->cerradoPor)
                        <div class="mb-3">
                            <strong>Cerrado por:</strong>
                            <p class="mb-0">
                                {{ $corte->cerradoPor->name }}
                                @if($corte->cerrado_por_tercero)
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-user-shield me-1"></i>Cerrado por administración
                                    </span>
                                @endif
                            </p>
                            @if($corte->cerrado_por_tercero)
                                <small class="text-muted">
                                    El arqueo no lo hizo el cajero del turno.
                                </small>
                            @endif
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
                            @if($puedeVerEsperado)
                                <p class="mb-1 text-success fs-5">Bs{{ number_format($totalVentas, 2) }}</p>
                                <p class="mb-0 small">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                    Efectivo: <strong>Bs{{ number_format($totales['efectivo'], 2) }}</strong>
                                    <span class="mx-2 text-muted">|</span>
                                    <i class="fas fa-qrcode text-primary me-1"></i>
                                    QR: <strong>Bs{{ number_format($totales['qr'], 2) }}</strong>
                                </p>
                            @else
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-eye-slash me-1"></i>
                                    Se muestra al cerrar la caja
                                </p>
                            @endif
                        </div>

                        @if($corte->estado == 'cerrado')
                        <div class="mb-3">
                            <strong>Efectivo Contado:</strong>
                            <p class="mb-0 fs-5">Bs{{ number_format($corte->total_efectivo, 2) }}</p>
                        </div>

                        <div class="mb-3">
                            <strong>QR Verificado:</strong>
                            <p class="mb-0 fs-5">Bs{{ number_format($corte->total_qr, 2) }}</p>
                        </div>

                        @php
                            $diferenciaShow = $corte->diferencia_efectivo;
                            $diferenciaQr   = $corte->diferencia_qr_real;
                        @endphp
                        <div class="mb-3">
                            <strong>Diferencia en Efectivo:</strong>
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

                        <div class="mb-3">
                            <strong>Diferencia en QR:</strong>
                            <p class="mb-0">
                                @if(abs($diferenciaQr) < 0.01)
                                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Coincide</span>
                                    <small class="text-success d-block">El QR verificado coincide con las ventas registradas</small>
                                @elseif($diferenciaQr < 0)
                                    <span class="badge bg-danger fs-6"><i class="fas fa-exclamation-triangle me-1"></i>Falta Bs{{ number_format(abs($diferenciaQr), 2) }}</span>
                                    <small class="text-danger d-block">Se verificó menos QR del que registran las ventas</small>
                                @else
                                    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-arrow-up me-1"></i>Sobra Bs{{ number_format($diferenciaQr, 2) }}</span>
                                    <small class="text-warning d-block">Se verificó más QR del que registran las ventas</small>
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
                                <th>Pago</th>
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
                                    <td>
                                        @if($venta->tipo_pago === 'qr')
                                            <span class="badge bg-primary"><i class="fas fa-qrcode me-1"></i>QR</span>
                                        @else
                                            <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</span>
                                        @endif
                                    </td>
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
                                <th colspan="3" class="text-end">Total:</th>
                                <th>
                                    @if($puedeVerEsperado)
                                        Bs{{ number_format($totalVentas, 2) }}
                                    @else
                                        <span class="text-muted fw-normal">—</span>
                                    @endif
                                </th>
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
                        @php
                            $difResumen   = $corte->diferencia_efectivo;
                            $claseResumen = abs($difResumen) < 0.01 ? 'success' : ($difResumen > 0 ? 'warning' : 'danger');
                            $difQrResumen = $corte->diferencia_qr_real;
                            $claseQr      = abs($difQrResumen) < 0.01 ? 'success' : ($difQrResumen > 0 ? 'warning' : 'danger');
                        @endphp
                        <table class="table table-sm mb-0">
                            <tr class="table-light">
                                <th colspan="2"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</th>
                            </tr>
                            <tr>
                                <td>Monto Inicial:</td>
                                <td class="text-end">Bs{{ number_format($corte->monto_inicial, 2) }}</td>
                            </tr>
                            <tr>
                                <td>+ Ventas en Efectivo:</td>
                                <td class="text-end text-success">+Bs{{ number_format($corte->ventas_efectivo, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>= Efectivo Esperado:</strong></td>
                                <td class="text-end"><strong>Bs{{ number_format($corte->efectivo_esperado, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Efectivo Contado:</td>
                                <td class="text-end">Bs{{ number_format($corte->total_efectivo, 2) }}</td>
                            </tr>
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

                            <tr class="table-light">
                                <th colspan="2" class="pt-3"><i class="fas fa-qrcode me-1"></i>QR</th>
                            </tr>
                            <tr>
                                <td>Ventas por QR:</td>
                                <td class="text-end">Bs{{ number_format($corte->ventas_qr, 2) }}</td>
                            </tr>
                            <tr>
                                <td>QR Verificado:</td>
                                <td class="text-end">Bs{{ number_format($corte->total_qr, 2) }}</td>
                            </tr>
                            <tr class="table-{{ $claseQr }}">
                                <td><strong>Diferencia QR:</strong></td>
                                <td class="text-end">
                                    <strong>
                                        @if(abs($difQrResumen) < 0.01)
                                            Coincide (Bs0.00)
                                        @elseif($difQrResumen < 0)
                                            Falta Bs{{ number_format(abs($difQrResumen), 2) }}
                                        @else
                                            Sobra Bs{{ number_format($difQrResumen, 2) }}
                                        @endif
                                    </strong>
                                </td>
                            </tr>

                            <tr class="border-top">
                                <td class="pt-3"><strong>Total del Turno:</strong></td>
                                <td class="text-end pt-3"><strong>Bs{{ number_format($corte->total_ventas, 2) }}</strong></td>
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
                    <div class="d-flex gap-2">
                        @if($corte->estado == 'abierto' && ($corte->user_id == Auth::id() || Auth::user()->esAdministrador()))
                        <a href="{{ route('cortes.edit', $corte->id) }}" class="btn btn-warning">
                            <i class="fas fa-lock me-2"></i>Cerrar Caja
                        </a>
                        @endif

                        @if($corte->estado == 'cerrado')
                            @can('editar-cortes-cerrados')
                            <a href="{{ route('cortes.cierre.editar', $corte->id) }}" class="btn btn-outline-primary">
                                <i class="fas fa-pen-to-square me-2"></i>Corregir Cierre
                            </a>
                            @endcan

                            @can('eliminar-cortes-cerrados')
                            <form action="{{ route('cortes.destroy', $corte->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Vas a eliminar el cierre de caja de {{ $corte->user->name }} del {{ $corte->fecha_corte->format('d/m/Y') }}. Esta acción no se puede deshacer. ¿Continuar?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-trash me-2"></i>Eliminar Cierre
                                </button>
                            </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection