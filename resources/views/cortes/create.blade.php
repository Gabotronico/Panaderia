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
                    
                    {{-- Se puede abrir una caja de un día pasado para cargar en
                         diferido lo vendido, por ejemplo, el domingo. --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label for="fecha_corte" class="form-label">Fecha de la caja</label>
                            <input type="date" class="form-control @error('fecha_corte') is-invalid @enderror"
                                   id="fecha_corte" name="fecha_corte"
                                   value="{{ old('fecha_corte', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}">
                            @error('fecha_corte')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">
                                Cambiala solo si estás abriendo la caja de un día anterior.
                            </small>
                        </div>

                        <div class="col-sm-5">
                            <label for="hora_apertura" class="form-label">Hora de apertura</label>
                            <input type="time" class="form-control @error('hora_apertura') is-invalid @enderror"
                                   id="hora_apertura" name="hora_apertura"
                                   value="{{ old('hora_apertura', now()->format('H:i')) }}">
                            @error('hora_apertura')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="alert alert-light border" style="font-size:.85rem;">
                        <i class="fas fa-circle-info me-1 text-primary"></i>
                        La caja toma las ventas de esa fecha registradas <strong>después</strong> de la hora
                        de apertura. Si vas a cargar ventas de un domingo, poné una hora temprana.
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