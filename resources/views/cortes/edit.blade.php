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
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Está a punto de cerrar la caja. Verifique que todos los montos sean correctos.
                </div>
                
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
                                <p class="mb-1"><strong>Total Ventas:</strong> <span class="text-success">Bs{{ number_format($totalVentas, 2) }}</span></p>
                                <hr class="my-2">
                                <p class="mb-1"><strong>Efectivo Esperado:</strong> <span class="text-info fs-5">Bs{{ number_format($corte->monto_inicial + $totalVentas, 2) }}</span></p>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Debe incluir el monto inicial
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerta Informativa -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Importante:</strong> Al cerrar la caja, el efectivo contado debe incluir:
                    <ul class="mb-0 mt-2">
                        <li>El <strong>monto inicial</strong> con el que se abrió la caja (Bs{{ number_format($corte->monto_inicial, 2) }})</li>
                        <li>Más el <strong>efectivo de las ventas</strong> del turno (Bs{{ number_format($totalVentas, 2) }})</li>
                        <li><strong>Total esperado:</strong> Bs {{ number_format($corte->monto_inicial + $totalVentas, 2) }}</li>
                    </ul>
                </div>
                
                <!-- Detalle de Ventas -->
                <div class="mb-4">
                    <h6><i class="fas fa-receipt me-2"></i>Ventas Realizadas en este Turno</h6>
                    @if($totalVentas > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Hora</th>
                                    <th>Número</th>
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
                                    <td class="text-end">Bs{{ number_format($venta->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total:</th>
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
    const totalVentas = {{ $totalVentas }};
    const efectivoEsperado = montoInicial + totalVentas;
    
    function calcularDiferencia() {
        const totalEfectivo = parseFloat(document.getElementById('total_efectivo').value) || 0;
        
        // Diferencia = Efectivo Contado - Total Ventas (cuánto sobra o falta respecto a las ventas)
        const diferencia = totalEfectivo - totalVentas;
        
        // Sobrante Real = Lo que queda después de cubrir las ventas
        const sobranteReal = totalEfectivo - totalVentas;
        
        // Diferencia con Monto Inicial = Cuánto sobra comparado con el monto inicial
        const diferenciaMontoInicial = sobranteReal - montoInicial;
        
        const diferenciaDisplay = document.getElementById('diferencia-display');
        const diferenciaTexto = document.getElementById('diferencia-texto');
        const diferenciaCard = document.getElementById('diferencia-card');
        
        // Remover clases anteriores
        diferenciaCard.classList.remove('border-success', 'border-danger', 'border-info', 'border-warning');
        
        // Mostrar la diferencia (sobrante después de ventas)
        diferenciaDisplay.textContent = 'Bs' + diferencia.toFixed(2);
        
        // Validar el sobrante comparado con el monto inicial
        if (Math.abs(diferenciaMontoInicial) < 0.01) {
            // PERFECTO: El sobrante coincide exactamente con el monto inicial
            diferenciaCard.classList.add('border-success', 'border', 'border-2');
            diferenciaTexto.innerHTML = '✓ Cuadra perfecto<br><small class="text-success">Sobrante (Bs' + sobranteReal.toFixed(2) + ') = Monto Inicial (Bs' + montoInicial.toFixed(2) + ')</small>';
            diferenciaTexto.className = 'text-success';
        } else if (diferencia < 0) {
            // FALTANTE: No alcanza ni para cubrir las ventas
            diferenciaCard.classList.add('border-danger', 'border', 'border-2');
            diferenciaTexto.innerHTML = '↓ Faltante crítico<br><small class="text-danger">Falta Bs' + Math.abs(diferencia).toFixed(2) + ' para cubrir las ventas</small>';
            diferenciaTexto.className = 'text-danger';
        } else if (sobranteReal < montoInicial) {
            // SOBRANTE MENOR: Hay sobrante pero es menor al monto inicial
            const faltante = montoInicial - sobranteReal;
            diferenciaCard.classList.add('border-warning', 'border', 'border-2');
            diferenciaTexto.innerHTML = '⚠️ Sobrante insuficiente<br><small class="text-warning">Sobrante: Bs' + sobranteReal.toFixed(2) + ' | Falta: Bs' + faltante.toFixed(2) + ' del monto inicial</small>';
            diferenciaTexto.className = 'text-warning';
        } else if (sobranteReal > montoInicial) {
            // SOBRANTE MAYOR: Hay más sobrante del esperado
            const excedente = sobranteReal - montoInicial;
            diferenciaCard.classList.add('border-info', 'border', 'border-2');
            diferenciaTexto.innerHTML = '↑ Sobrante mayor<br><small class="text-info">Sobrante: $' + sobranteReal.toFixed(2) + ' | Excede: Bs' + excedente.toFixed(2) + ' del monto inicial</small>';
            diferenciaTexto.className = 'text-info';
        } else if (diferencia === 0) {
            // CASO ESPECIAL: Solo cubrió las ventas, sin sobrante
            diferenciaCard.classList.add('border-danger', 'border', 'border-2');
            diferenciaTexto.innerHTML = '⚠️ Sin sobrante<br><small class="text-danger">Solo cubrió ventas. Falta monto inicial (Bs' + montoInicial.toFixed(2) + ')</small>';
            diferenciaTexto.className = 'text-danger';
        }
        
        // Log para debugging
        console.log('═══════════════════════════════════════');
        console.log('Monto Inicial:', montoInicial.toFixed(2));
        console.log('Total Ventas:', totalVentas.toFixed(2));
        console.log('Efectivo Esperado (Inicial + Ventas):', efectivoEsperado.toFixed(2));
        console.log('───────────────────────────────────────');
        console.log('Efectivo Contado:', totalEfectivo.toFixed(2));
        console.log('Diferencia (Contado - Ventas):', diferencia.toFixed(2));
        console.log('Sobrante Real:', sobranteReal.toFixed(2));
        console.log('Diferencia con Monto Inicial:', diferenciaMontoInicial.toFixed(2));
        console.log('═══════════════════════════════════════');
    }
    
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
        const totalDesglose = document.getElementById('total-desglose').textContent.replace('$', '');
        document.getElementById('total_efectivo').value = totalDesglose;
        calcularDiferencia();
    }
    
    // Validar antes de enviar
    document.getElementById('form-cierre').addEventListener('submit', function(e) {
        const totalEfectivo = parseFloat(document.getElementById('total_efectivo').value) || 0;
        
        if (totalEfectivo === 0) {
            e.preventDefault();
            alert('Debe ingresar el efectivo total contado en caja');
            return false;
        }
        
        const diferencia = totalEfectivo - totalVentas;
        const sobranteReal = totalEfectivo - totalVentas;
        const diferenciaMontoInicial = sobranteReal - montoInicial;
        
        // Alerta si hay faltante (no cubre ni las ventas)
        if (diferencia < 0) {
            if (!confirm(`⚠️ FALTANTE CRÍTICO:\n\nFalta Bs${Math.abs(diferencia).toFixed(2)} para cubrir las ventas.\n\n¿Está seguro de cerrar la caja con este faltante?`)) {
                e.preventDefault();
                return false;
            }
        }
        
        // Alerta si la diferencia con el monto inicial es muy grande
        if (Math.abs(diferenciaMontoInicial) > 100) {
            if (!confirm(`Hay una diferencia de Bs${diferenciaMontoInicial.toFixed(2)} con el monto inicial. ¿Está seguro de cerrar la caja?`)) {
                e.preventDefault();
                return false;
            }
        }
        
        // Alerta si el sobrante no coincide con el monto inicial
        if (Math.abs(diferenciaMontoInicial) > 0.01) {
            let mensaje;
            if (diferenciaMontoInicial < 0) {
                // Sobrante menor al monto inicial
                mensaje = `⚠️ ADVERTENCIA:\n\nSobrante: Bs${sobranteReal.toFixed(2)}\nMonto Inicial: $${montoInicial.toFixed(2)}\n\nFaltan $${Math.abs(diferenciaMontoInicial).toFixed(2)} del monto inicial.\n\n¿Desea continuar con el cierre?`;
            } else {
                // Sobrante mayor al monto inicial
                mensaje = `⚠️ ADVERTENCIA:\n\nSobrante: Bs${sobranteReal.toFixed(2)}\nMonto Inicial: $${montoInicial.toFixed(2)}\n\nHay $${diferenciaMontoInicial.toFixed(2)} de más del monto inicial.\n\n¿Desea continuar con el cierre?`;
            }
            
            if (!confirm(mensaje)) {
                e.preventDefault();
                return false;
            }
        }
        
        // Alerta crítica si solo cubrió las ventas (sin sobrante)
        if (diferencia === 0) {
            if (!confirm('⚠️ ALERTA CRÍTICA:\n\nSolo cubrió el total de ventas.\nNo hay sobrante del monto inicial (Bs' + montoInicial.toFixed(2) + ').\n\n¿Está seguro de que el conteo es correcto?')) {
                e.preventDefault();
                return false;
            }
        }
    });
</script>
@endpush