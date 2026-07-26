@extends('layouts.app')

@section('page-title', 'Editar Venta')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit me-2"></i>Editar Venta - {{ $venta->numero_venta }}
            </div>
            <div class="card-body">
                <!-- Información de la Venta -->
                <div class="alert alert-info">
                    <strong>Número de Venta:</strong> {{ $venta->numero_venta }}<br>
                    <strong>Fecha:</strong> {{ $venta->created_at->format('d/m/Y H:i') }}<br>
                    <strong>Cajero:</strong> {{ $venta->user->name }}<br>
                    <strong>Total:</strong> Bs {{ number_format($venta->total, 2) }}
                </div>
                
                <!-- Detalle de Productos -->
                <h5 class="mb-3">Productos Vendidos</h5>
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
                            @foreach($venta->detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->producto->nombre }}</td>
                                    <td class="text-center">{{ $detalle->cantidad }}</td>
                                    <td class="text-end">Bs{{ number_format($detalle->precio_unitario, 2) }}</td>
                                    <td class="text-end">Bs{{ number_format($detalle->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Formulario de Edición -->
                <form action="{{ route('ventas.update', $venta->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                  id="observaciones" 
                                  name="observaciones" 
                                  rows="3">{{ old('observaciones', $venta->observaciones) }}</textarea>
                        @error('observaciones')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" 
                                id="estado" 
                                name="estado" 
                                required>
                            <option value="completada" {{ old('estado', $venta->estado) == 'completada' ? 'selected' : '' }}>
                                Completada
                            </option>
                            <option value="cancelada" {{ old('estado', $venta->estado) == 'cancelada' ? 'selected' : '' }}>
                                Cancelada
                            </option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Al cancelar una venta, se devolverá el stock de los productos e insumos.
                        </small>
                    </div>
                    
                    <div class="alert alert-warning" id="warning-cancelar" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¡Atención!</strong> Está a punto de cancelar esta venta. 
                        Esta acción devolverá el stock de los productos e insumos vendidos.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
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
    document.getElementById('estado').addEventListener('change', function() {
        const warning = document.getElementById('warning-cancelar');
        if (this.value === 'cancelada') {
            warning.style.display = 'block';
        } else {
            warning.style.display = 'none';
        }
    });
    
    // Mostrar advertencia si ya está cancelada
    window.addEventListener('load', function() {
        const estado = document.getElementById('estado').value;
        const warning = document.getElementById('warning-cancelar');
        if (estado === 'cancelada') {
            warning.style.display = 'block';
        }
    });
</script>
@endpush