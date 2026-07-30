@extends('layouts.app')

@section('page-title', 'Corregir Cierre de Caja')

@section('content')
@php
    $desfaseEfectivo = $totalesActuales['efectivo'] - (float) $corte->ventas_efectivo;
    $desfaseQr       = $totalesActuales['qr'] - (float) $corte->ventas_qr;
    $hayDesfase      = abs($desfaseEfectivo) >= 0.01 || abs($desfaseQr) >= 0.01;
@endphp

<div class="row">
    <div class="col-md-9 offset-md-1">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-pen-to-square me-2"></i>Corregir Cierre de Caja #{{ $corte->id }}
            </div>
            <div class="card-body">

                <div class="alert alert-danger">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-user-shield mt-1"></i>
                        <div>
                            <strong>Estás modificando un arqueo ya cerrado.</strong>
                            <div class="mt-1">
                                El turno es de <strong>{{ $corte->user->name }}</strong>
                                ({{ $corte->fecha_corte->format('d/m/Y') }}).
                                La corrección se anexa a las observaciones con tu nombre y la fecha,
                                para que quede claro quién tocó el cierre y por qué.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Información del turno --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Turno</h6>
                                <p class="mb-1"><strong>Cajero:</strong> {{ $corte->user->name }}</p>
                                <p class="mb-1"><strong>Fecha:</strong> {{ $corte->fecha_corte->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Apertura:</strong> {{ $corte->hora_apertura }}</p>
                                <p class="mb-1"><strong>Cierre:</strong> {{ $corte->hora_cierre ?? '-' }}</p>
                                <p class="mb-0">
                                    <strong>Cerrado por:</strong>
                                    {{ $corte->cerradoPor->name ?? '-' }}
                                    @if($corte->cerrado_por_tercero)
                                        <span class="badge bg-warning text-dark ms-1">administración</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Arqueo registrado</h6>
                                <p class="mb-1"><strong>Monto Inicial:</strong> Bs{{ number_format($corte->monto_inicial, 2) }}</p>
                                <p class="mb-1">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                    <strong>Ventas en efectivo:</strong> Bs{{ number_format($corte->ventas_efectivo, 2) }}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-qrcode text-primary me-1"></i>
                                    <strong>Ventas por QR:</strong> Bs{{ number_format($corte->ventas_qr, 2) }}
                                </p>
                                <hr class="my-2">
                                <p class="mb-1"><strong>Efectivo esperado:</strong> Bs{{ number_format($corte->efectivo_esperado, 2) }}</p>
                                <p class="mb-0"><strong>Total del turno:</strong> Bs{{ number_format($corte->total_ventas, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($hayDesfase)
                <div class="alert alert-warning">
                    <i class="fas fa-triangle-exclamation me-2"></i>
                    <strong>Las ventas del turno ya no coinciden con lo que se arqueó.</strong>
                    Probablemente se anuló o registró alguna venta después del cierre.
                    <ul class="mb-0 mt-2">
                        <li>Efectivo: arqueado Bs{{ number_format($corte->ventas_efectivo, 2) }},
                            hoy Bs{{ number_format($totalesActuales['efectivo'], 2) }}
                            ({{ $desfaseEfectivo >= 0 ? '+' : '−' }}Bs{{ number_format(abs($desfaseEfectivo), 2) }})</li>
                        <li>QR: arqueado Bs{{ number_format($corte->ventas_qr, 2) }},
                            hoy Bs{{ number_format($totalesActuales['qr'], 2) }}
                            ({{ $desfaseQr >= 0 ? '+' : '−' }}Bs{{ number_format(abs($desfaseQr), 2) }})</li>
                    </ul>
                </div>
                @endif

                <hr>

                <form action="{{ route('cortes.cierre.actualizar', $corte->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h6 class="mb-3"><i class="fas fa-calculator me-2"></i>Montos del arqueo</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_efectivo" class="form-label">
                                    Efectivo Contado <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                           class="form-control form-control-lg @error('total_efectivo') is-invalid @enderror"
                                           id="total_efectivo"
                                           name="total_efectivo"
                                           value="{{ old('total_efectivo', $corte->total_efectivo) }}"
                                           step="0.01"
                                           min="0"
                                           required>
                                    @error('total_efectivo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Registrado al cerrar: Bs{{ number_format($corte->total_efectivo, 2) }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_qr" class="form-label">
                                    QR Verificado <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                           class="form-control form-control-lg @error('total_qr') is-invalid @enderror"
                                           id="total_qr"
                                           name="total_qr"
                                           value="{{ old('total_qr', $corte->total_qr) }}"
                                           step="0.01"
                                           min="0"
                                           required>
                                    @error('total_qr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Registrado al cerrar: Bs{{ number_format($corte->total_qr, 2) }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="hidden" name="recalcular_ventas" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               value="1"
                               id="recalcular_ventas"
                               name="recalcular_ventas"
                               @checked(old('recalcular_ventas', $hayDesfase))>
                        <label class="form-check-label" for="recalcular_ventas">
                            Recalcular las ventas del turno
                            <small class="d-block text-muted">
                                Vuelve a leer las ventas completadas del turno y actualiza el desglose
                                efectivo/QR. Úsalo si se anuló o corrigió una venta después del cierre.
                            </small>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">
                            Motivo de la corrección <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('motivo') is-invalid @enderror"
                                  id="motivo"
                                  name="motivo"
                                  rows="3"
                                  required
                                  placeholder="Ej: el cajero registró Bs50 de más por error de conteo, verificado con el arqueo físico.">{{ old('motivo') }}</textarea>
                        @error('motivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Se anexa a las observaciones del corte junto con tu nombre y la fecha.</small>
                    </div>

                    @if($corte->observaciones)
                    <div class="mb-3">
                        <label class="form-label">Observaciones actuales</label>
                        <div class="border rounded p-2 bg-light small" style="white-space: pre-line;">{{ $corte->observaciones }}</div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cortes.show', $corte->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Corrección
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
