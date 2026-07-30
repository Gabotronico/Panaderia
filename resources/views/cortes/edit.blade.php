@extends('layouts.app')

@section('page-title', 'Cerrar Caja')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock me-2"></i>Cierre de Caja
            </div>
            <div class="card-body">
                <!-- Información del Corte -->
                @if($corte->user_id !== auth()->id())
                <div class="alert alert-danger">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-user-shield mt-1"></i>
                        <div>
                            <strong>Estás cerrando la caja de {{ $corte->user->name }}.</strong>
                            <div class="mt-1">
                                El cierre quedará registrado a tu nombre para que se sepa
                                quién hizo el arqueo. Contá el efectivo antes de registrar el monto.
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Está a punto de cerrar la caja. Verifique que todos los montos sean correctos.
                </div>
                @endif
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Información del Turno</h6>
                                <p class="mb-1"><strong>Cajero:</strong> {{ $corte->user->name }}</p>
                                <p class="mb-1"><strong>Fecha:</strong> {{ $corte->fecha_corte->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Hora Apertura:</strong> {{ $corte->hora_apertura }}</p>
                                <p class="mb-0"><strong>Hora Actual:</strong> {{ now()->format('H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Resumen Financiero</h6>
                                <p class="mb-1"><strong>Monto Inicial:</strong> <span class="text-primary">Bs{{ number_format($corte->monto_inicial, 2) }}</span></p>

                                @if($puedeVerEsperado)
                                    <p class="mb-1"><strong>Total Ventas:</strong> <span class="text-success">Bs{{ number_format($totalVentas, 2) }}</span></p>
                                    <p class="mb-1 ps-3 small">
                                        <i class="fas fa-money-bill-wave text-success me-1"></i>
                                        Efectivo: <strong>Bs{{ number_format($totales['efectivo'], 2) }}</strong>
                                    </p>
                                    <p class="mb-1 ps-3 small">
                                        <i class="fas fa-qrcode text-primary me-1"></i>
                                        QR: <strong>Bs{{ number_format($totales['qr'], 2) }}</strong>
                                    </p>
                                    <hr class="my-2">
                                    <p class="mb-1"><strong>Efectivo Esperado:</strong> <span class="text-info fs-5">Bs{{ number_format($corte->monto_inicial + $totales['efectivo'], 2) }}</span></p>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-info-circle"></i> Monto inicial + ventas en efectivo. El QR no entra al cajón.
                                    </small>
                                    <p class="mb-0 mt-2"><strong>QR Esperado:</strong> <span class="text-primary">Bs{{ number_format($totales['qr'], 2) }}</span></p>
                                @else
                                    <hr class="my-2">
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-eye-slash me-1"></i>
                                        <strong>Montos esperados:</strong> ocultos
                                    </p>
                                    <small class="text-muted">
                                        Contá el dinero de la caja y revisá los cobros por QR,
                                        y registrá cada monto exacto. El sistema hará la verificación.
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerta Informativa -->
                @if($puedeVerEsperado)
                <div class="alert alert-info mb-4">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Importante:</strong> el efectivo y el QR se arquean por separado.
                    <ul class="mb-0 mt-2">
                        <li>El <strong>efectivo contado</strong> debe incluir el monto inicial
                            (Bs{{ number_format($corte->monto_inicial, 2) }}) más las ventas cobradas en efectivo
                            (Bs{{ number_format($totales['efectivo'], 2) }}) =
                            <strong>Bs{{ number_format($corte->monto_inicial + $totales['efectivo'], 2) }}</strong></li>
                        <li>El <strong>QR</strong> se verifica contra los comprobantes recibidos:
                            <strong>Bs{{ number_format($totales['qr'], 2) }}</strong></li>
                    </ul>
                </div>
                @else
                <div class="alert alert-info mb-4">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Cómo hacer el cierre:</strong> contá todo el efectivo que hay en la caja,
                    incluyendo el monto inicial con el que abriste el turno
                    (Bs{{ number_format($corte->monto_inicial, 2) }}), y registrá ese total.
                    Aparte, revisá los cobros por QR en tu app bancaria y registrá esa suma.
                    <div class="mt-2 mb-0">
                        <small>
                            El conteo se hace a ciegas para que el arqueo sea confiable.
                            Si hay una diferencia, se revisa junto con la administración.
                        </small>
                    </div>
                </div>
                @endif
                
                <!-- Detalle de Ventas -->
                @if($puedeVerEsperado)
                <div class="mb-4">
                    <h6><i class="fas fa-receipt me-2"></i>Ventas Realizadas en este Turno</h6>
                    @if($totalVentas > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Hora</th>
                                    <th>Número</th>
                                    <th>Pago</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $ventasDelCorte = \App\Models\Venta::where('user_id', $corte->user_id)
                                        ->whereDate('created_at', $corte->fecha_corte)
                                        ->where('created_at', '>=', $corte->created_at)
                                        ->where('estado', 'completada')
                                        ->orderBy('created_at', 'desc')
                                        ->get();
                                @endphp
                                @foreach($ventasDelCorte as $venta)
                                <tr>
                                    <td>{{ $venta->created_at->format('H:i:s') }}</td>
                                    <td>{{ $venta->numero_venta }}</td>
                                    <td>
                                        @if($venta->tipo_pago === 'qr')
                                            <span class="badge bg-primary"><i class="fas fa-qrcode me-1"></i>QR</span>
                                        @else
                                            <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Bs{{ number_format($venta->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Efectivo:</th>
                                    <th class="text-end">Bs{{ number_format($totales['efectivo'], 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">QR:</th>
                                    <th class="text-end">Bs{{ number_format($totales['qr'], 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th class="text-end">Bs{{ number_format($totalVentas, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No se realizaron ventas en este turno.
                    </div>
                    @endif
                </div>
                @endif

                <hr>
                
                <!-- Formulario de Cierre -->
                <form action="{{ route('cortes.update', $corte->id) }}" method="POST" id="form-cierre">
                    @csrf
                    @method('PUT')
                    
                    <h6 class="mb-3"><i class="fas fa-dollar-sign me-2"></i>Conteo de Efectivo</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_efectivo" class="form-label">
                                    Efectivo Total en Caja <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                           class="form-control form-control-lg @error('total_efectivo') is-invalid @enderror"
                                           id="total_efectivo"
                                           name="total_efectivo"
                                           value="{{ old('total_efectivo') }}"
                                           step="0.01"
                                           min="0"
                                           required
                                           autofocus
                                           oninput="calcularDiferencia()">
                                    @error('total_efectivo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Cuente todo el efectivo presente en la caja (incluyendo monto inicial)</small>
                            </div>
                        </div>

                        @if($puedeVerEsperado)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Diferencia Calculada</label>
                                <div class="card" id="diferencia-card">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0" id="diferencia-display">Bs0.00</h3>
                                        <small class="text-muted" id="diferencia-texto">Ingrese el efectivo contado</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- El QR se arquea aparte: ese dinero va a la cuenta, no al cajón. --}}
                    <h6 class="mb-3 mt-2"><i class="fas fa-qrcode me-2"></i>Cobros por QR</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_qr" class="form-label">
                                    Total Cobrado por QR <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                           class="form-control form-control-lg @error('total_qr') is-invalid @enderror"
                                           id="total_qr"
                                           name="total_qr"
                                           value="{{ old('total_qr', 0) }}"
                                           step="0.01"
                                           min="0"
                                           required
                                           oninput="calcularDiferenciaQr()">
                                    @error('total_qr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">
                                    Sume los pagos por QR recibidos en el turno. Si no hubo, deje 0.
                                </small>
                            </div>
                        </div>

                        @if($puedeVerEsperado)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Diferencia QR</label>
                                <div class="card" id="diferencia-qr-card">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0" id="diferencia-qr-display">Bs0.00</h3>
                                        <small class="text-muted" id="diferencia-qr-texto">Ingrese el QR verificado</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Desglose de Billetes y Monedas (Opcional) -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#desglose" role="button">
                                <i class="fas fa-coins me-2"></i>Desglose de Billetes y Monedas (Opcional)
                                <i class="fas fa-chevron-down float-end"></i>
                            </a>
                        </div>
                        <div class="collapse" id="desglose">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Billetes</h6>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs1000 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs500 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs200 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs100 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs50 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs20 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Monedas</h6>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs10 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs5 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs2 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs1 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Bs0.50 x</label>
                                            <input type="number" class="form-control form-control-sm" min="0" value="0" onchange="calcularDesglose()">
                                        </div>
                                        <hr>
                                        <div class="alert alert-info mb-0">
                                            <strong>Total Desglose:</strong> <span id="total-desglose">Bs0.00</span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary mt-2" onclick="aplicarDesglose()">
                                    Aplicar al Total
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones del Cierre</label>
                        <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                  id="observaciones" 
                                  name="observaciones" 
                                  rows="3"
                                  placeholder="Observaciones opcionales sobre el cierre de caja">{{ old('observaciones', $corte->observaciones) }}</textarea>
                        @error('observaciones')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cortes.show', $corte->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-success" id="btn-cerrar">
                            <i class="fas fa-lock me-2"></i>Cerrar Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const montoInicial = {{ $corte->monto_inicial }};

@unless($puedeVerEsperado)
    // El cajero cierra la caja a ciegas: ni las ventas del turno ni el cálculo
    // de las diferencias se envían al navegador.
    function calcularDiferencia() {}
    function calcularDiferenciaQr() {}
@else
    // El QR queda fuera del efectivo esperado a propósito: ese dinero entra a
    // la cuenta, no al cajón. Cada medio de pago se arquea contra lo suyo.
    const ventasEfectivo   = {{ $totales['efectivo'] }};
    const ventasQr         = {{ $totales['qr'] }};
    const efectivoEsperado = montoInicial + ventasEfectivo;

    function pintarDiferencia(card, display, texto, diferencia, etiquetaCuadra) {
        card.classList.remove('border-success', 'border-danger', 'border-warning', 'border', 'border-2');
        display.textContent = 'Bs' + diferencia.toFixed(2);

        if (Math.abs(diferencia) < 0.01) {
            card.classList.add('border-success', 'border', 'border-2');
            texto.className = 'text-success';
            texto.innerHTML = '✓ ' + etiquetaCuadra;
        } else if (diferencia < 0) {
            card.classList.add('border-danger', 'border', 'border-2');
            texto.className = 'text-danger';
            texto.innerHTML = '↓ Faltan Bs' + Math.abs(diferencia).toFixed(2);
        } else {
            card.classList.add('border-warning', 'border', 'border-2');
            texto.className = 'text-warning';
            texto.innerHTML = '↑ Sobran Bs' + diferencia.toFixed(2);
        }
    }

    function calcularDiferencia() {
        const contado = parseFloat(document.getElementById('total_efectivo').value) || 0;

        pintarDiferencia(
            document.getElementById('diferencia-card'),
            document.getElementById('diferencia-display'),
            document.getElementById('diferencia-texto'),
            contado - efectivoEsperado,
            'Cuadra perfecto (esperado Bs' + efectivoEsperado.toFixed(2) + ')'
        );
    }

    function calcularDiferenciaQr() {
        const contado = parseFloat(document.getElementById('total_qr').value) || 0;

        pintarDiferencia(
            document.getElementById('diferencia-qr-card'),
            document.getElementById('diferencia-qr-display'),
            document.getElementById('diferencia-qr-texto'),
            contado - ventasQr,
            'Coincide con las ventas por QR (Bs' + ventasQr.toFixed(2) + ')'
        );
    }
@endunless

    function calcularDesglose() {
        const denominaciones = [
            { valor: 1000, input: 0 },
            { valor: 500, input: 1 },
            { valor: 200, input: 2 },
            { valor: 100, input: 3 },
            { valor: 50, input: 4 },
            { valor: 20, input: 5 },
            { valor: 10, input: 6 },
            { valor: 5, input: 7 },
            { valor: 2, input: 8 },
            { valor: 1, input: 9 },
            { valor: 0.50, input: 10 }
        ];

        const inputs = document.querySelectorAll('#desglose input[type="number"]');
        let total = 0;

        denominaciones.forEach(den => {
            const cantidad = parseFloat(inputs[den.input].value) || 0;
            total += den.valor * cantidad;
        });

        document.getElementById('total-desglose').textContent = 'Bs' + total.toFixed(2);
    }

    function aplicarDesglose() {
        const totalDesglose = document.getElementById('total-desglose').textContent.replace('Bs', '');
        document.getElementById('total_efectivo').value = totalDesglose;
        calcularDiferencia();
    }

    // Validar antes de enviar
    document.getElementById('form-cierre').addEventListener('submit', function(e) {
        const efectivoContado = parseFloat(document.getElementById('total_efectivo').value) || 0;

        if (efectivoContado === 0) {
            if (!confirm('Registró Bs0.00 de efectivo en caja. ¿Es correcto?')) {
                e.preventDefault();
                return false;
            }
        }

@unless($puedeVerEsperado)
        // Sin los montos esperados no hay nada más que validar del lado del
        // cajero: la verificación la hace el servidor al guardar el cierre.
        return true;
@else
        const qrContado = parseFloat(document.getElementById('total_qr').value) || 0;
        const difEfectivo = efectivoContado - efectivoEsperado;
        const difQr = qrContado - ventasQr;

        if (Math.abs(difEfectivo) >= 0.01) {
            const detalle = difEfectivo < 0
                ? `Faltan Bs${Math.abs(difEfectivo).toFixed(2)}`
                : `Sobran Bs${difEfectivo.toFixed(2)}`;

            if (!confirm(`⚠️ EFECTIVO\n\nContado: Bs${efectivoContado.toFixed(2)}\nEsperado: Bs${efectivoEsperado.toFixed(2)}\n${detalle}\n\n¿Desea continuar con el cierre?`)) {
                e.preventDefault();
                return false;
            }
        }

        if (Math.abs(difQr) >= 0.01) {
            const detalle = difQr < 0
                ? `Faltan Bs${Math.abs(difQr).toFixed(2)}`
                : `Sobran Bs${difQr.toFixed(2)}`;

            if (!confirm(`⚠️ QR\n\nVerificado: Bs${qrContado.toFixed(2)}\nVentas por QR: Bs${ventasQr.toFixed(2)}\n${detalle}\n\n¿Desea continuar con el cierre?`)) {
                e.preventDefault();
                return false;
            }
        }
@endunless
    });
</script>
@endpush