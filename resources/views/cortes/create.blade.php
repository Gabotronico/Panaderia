@extends('layouts.app')

@section('page-title', 'Abrir Caja')

@section('content')
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cash-register me-2"></i>Apertura de Caja
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Está a punto de abrir una nueva caja. Ingrese el monto inicial con el que comenzará el turno.
                </div>
                
                <form action="{{ route('cortes.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Cajero</label>
                        <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hora de Apertura</label>
                        <input type="text" class="form-control" value="{{ now()->format('H:i') }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="monto_inicial" class="form-label">
                            Monto Inicial en Caja <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number" 
                                   class="form-control @error('monto_inicial') is-invalid @enderror" 
                                   id="monto_inicial" 
                                   name="monto_inicial" 
                                   value="{{ old('monto_inicial', 0) }}" 
                                   step="0.01" 
                                   min="0" 
                                   required 
                                   autofocus>
                            @error('monto_inicial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Ingrese el efectivo inicial con el que comienza el turno</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                  id="observaciones" 
                                  name="observaciones" 
                                  rows="3"
                                  placeholder="Observaciones opcionales sobre la apertura">{{ old('observaciones') }}</textarea>
                        @error('observaciones')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cortes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-unlock me-2"></i>Abrir Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection