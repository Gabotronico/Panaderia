@extends('layouts.app')

@section('page-title', 'Detalle de Venta')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2"></i>Detalle de Venta</span>
                <div>
                    @if($venta->estado == 'completada')
                        <span class="badge bg-success">Completada</span>
                    @else
                        <span class="badge bg-danger">Cancelada</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <!-- Información de la Venta -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Número de Venta:</strong>
                            <p class="mb-0">{{ $venta->numero_venta }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Fecha y Hora:</strong>
                            <p class="mb-0">{{ $venta->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Cajero:</strong>
                            <p class="mb-0">{{ $venta->user->name }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Estado:</strong>
                            <p class="mb-0">
                                @if($venta->estado == 'completada')
                                    <span class="badge bg-success fs-6">Completada</span>
                                @else
                                    <span class="badge bg-danger fs-6">Cancelada</span>
                                @endif
                            </p>
                        </div>

                        <div class="mb-3">
                            <strong>Método de Pago:</strong>
                            <p class="mb-0">
                                @if($venta->tipo_pago === 'qr')
                                    <span class="badge bg-primary fs-6">
                                        <i class="fas fa-qrcode me-1"></i>Pago QR
                                    </span>
                                @else
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-money-bill-wave me-1"></i>Efectivo
                                    </span>
                                @endif
                            </p>
                        </div>

                        @if($venta->tipo_pago === 'efectivo' && $venta->monto_recibido)
                        <div class="mb-3">
                            <strong>Monto Recibido:</strong>
                            <p class="mb-0">Bs{{ number_format($venta->monto_recibido, 2) }}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Cambio Entregado:</strong>
                            <p class="mb-0 text-success fw-bold">Bs{{ number_format($venta->cambio, 2) }}</p>
                        </div>
                        @endif

                        @if($venta->observaciones)
                        <div class="mb-3">
                            <strong>Observaciones:</strong>
                            <p class="mb-0">{{ $venta->observaciones }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                
                <hr>
                
                <!-- Detalle de Productos -->
                <h5 class="mb-3"><i class="fas fa-list me-2"></i>Productos Vendidos</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($venta->detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->producto->nombre }}</td>
                                    <td class="text-center">{{ $detalle->cantidad }}</td>
                                    <td class="text-end">Bs{{ number_format($detalle->precio_unitario, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($detalle->subtotal, 2) }}</td>
                                </tr>
                            @empty
                                {{-- Las ventas directas se registran por monto: no hay
                                     productos que listar. --}}
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-cash-register me-2"></i>
                                        Venta registrada por monto total, sin detalle de productos.
                                        <div class="small mt-1">Por eso no descontó stock.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong>Bs{{ number_format($venta->subtotal, 2) }}</strong></td>
                            </tr>
                            @if($venta->descuento > 0)
                            <tr>
                                <td colspan="3" class="text-end"><strong>Descuento:</strong></td>
                                <td class="text-end text-danger"><strong>-Bs{{ number_format($venta->descuento, 2) }}</strong></td>
                            </tr>
                            @endif
                            <tr class="table-success">
                                <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong class="fs-5">Bs{{ number_format($venta->total, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <div>
                        <button onclick="imprimirTicket()" class="btn btn-info me-2">
                            <i class="fas fa-print me-2"></i>Imprimir Ticket
                        </button>
                        @can('editar-ventas')
                        @if($venta->estado == 'completada')
                        <a href="{{ route('ventas.edit', $venta->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Área oculta para impresión -->
<div id="ticket-print" style="display: none;">
    <div style="width: 300px; font-family: monospace; font-size: 12px;">
        <div style="text-align: center; margin-bottom: 10px;">
            <h3 style="margin: 0;">PANADERÍA LUNA</h3>
            <p style="margin: 5px 0;">Ticket de Venta</p>
            <p style="margin: 5px 0;">{{ $venta->numero_venta }}</p>
        </div>
        
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
            <p style="margin: 3px 0;">Fecha: {{ $venta->created_at->format('d/m/Y H:i') }}</p>
            <p style="margin: 3px 0;">Cajero: {{ $venta->user->name }}</p>
        </div>
        
        <table style="width: 100%; margin: 10px 0;">
            <thead>
                <tr>
                    <th style="text-align: left;">Producto</th>
                    <th style="text-align: center;">Cant.</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venta->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td style="text-align: center;">{{ $detalle->cantidad }}</td>
                    <td style="text-align: right;">Bs{{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Venta por monto total</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px;">
            <p style="margin: 3px 0; text-align: right;">Subtotal: Bs{{ number_format($venta->subtotal, 2) }}</p>
            @if($venta->descuento > 0)
            <p style="margin: 3px 0; text-align: right;">Descuento: -Bs{{ number_format($venta->descuento, 2) }}</p>
            @endif
            <p style="margin: 3px 0; text-align: right; font-size: 16px;"><strong>TOTAL: Bs{{ number_format($venta->total, 2) }}</strong></p>
            <p style="margin: 3px 0; text-align: right;">
                Pago: {{ $venta->tipo_pago === 'qr' ? 'Código QR' : 'Efectivo' }}
            </p>
            @if($venta->tipo_pago === 'efectivo' && $venta->monto_recibido)
            <p style="margin: 3px 0; text-align: right;">Recibido: Bs{{ number_format($venta->monto_recibido, 2) }}</p>
            <p style="margin: 3px 0; text-align: right;"><strong>Cambio: Bs{{ number_format($venta->cambio, 2) }}</strong></p>
            @endif
        </div>
        
        <div style="text-align: center; margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px;">
            <p style="margin: 5px 0;">¡Gracias por su compra!</p>
            <p style="margin: 5px 0; font-size: 10px;">www.panaderialuna.com</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function imprimirTicket() {
        const ticketContent = document.getElementById('ticket-print').innerHTML;
        const ventanaImpresion = window.open('', '_blank', 'width=400,height=600');
        
        ventanaImpresion.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ticket de Venta - {{ $venta->numero_venta }}</title>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    @media print {
                        body {
                            margin: 0;
                            padding: 0;
                        }
                    }
                </style>
            </head>
            <body>
                ${ticketContent}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        }
                    }
                <\/script>
            </body>
            </html>
        `);
        
        ventanaImpresion.document.close();
    }
</script>
@endpush