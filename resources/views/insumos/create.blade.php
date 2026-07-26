@extends('layouts.app')

@section('page-title', 'Nuevo Insumo')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>Crear Nuevo Insumo
            </div>
            <div class="card-body">
                <form action="{{ route('insumos.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            Nombre del Insumo <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('nombre') is-invalid @enderror" 
                               id="nombre" 
                               name="nombre" 
                               value="{{ old('nombre') }}" 
                               required 
                               autofocus
                               placeholder="Ej: Harina de Trigo">
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"
                                  placeholder="Descripción opcional del insumo">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unidad_medida" class="form-label">
                                    Unidad de Medida <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('unidad_medida') is-invalid @enderror" 
                                        id="unidad_medida" 
                                        name="unidad_medida" 
                                        required>
                                    <option value="">Seleccione una unidad</option>
                                    <option value="kg" {{ old('unidad_medida') == 'kg' ? 'selected' : '' }}>Kilogramos (kg)</option>
                                    <option value="g" {{ old('unidad_medida') == 'g' ? 'selected' : '' }}>Gramos (g)</option>
                                    <option value="l" {{ old('unidad_medida') == 'l' ? 'selected' : '' }}>Litros (l)</option>
                                    <option value="ml" {{ old('unidad_medida') == 'ml' ? 'selected' : '' }}>Mililitros (ml)</option>
                                    <option value="unidades" {{ old('unidad_medida') == 'unidades' ? 'selected' : '' }}>Unidades</option>
                                    <option value="piezas" {{ old('unidad_medida') == 'piezas' ? 'selected' : '' }}>Piezas</option>
                                    <option value="cajas" {{ old('unidad_medida') == 'cajas' ? 'selected' : '' }}>Cajas</option>
                                    <option value="paquetes" {{ old('unidad_medida') == 'paquetes' ? 'selected' : '' }}>Paquetes</option>
                                </select>
                                @error('unidad_medida')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="costo_unitario" class="form-label">
                                    Costo Unitario <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                           class="form-control @error('costo_unitario') is-invalid @enderror"
                                           id="costo_unitario"
                                           name="costo_unitario"
                                           value="{{ old('costo_unitario', 0) }}"
                                           step="0.00001"
                                           min="0"
                                           required>
                                    @error('costo_unitario')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cantidad_stock" class="form-label">
                                    Cantidad en Stock <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('cantidad_stock') is-invalid @enderror" 
                                       id="cantidad_stock" 
                                       name="cantidad_stock" 
                                       value="{{ old('cantidad_stock', 0) }}" 
                                       step="0.01" 
                                       min="0" 
                                       required>
                                @error('cantidad_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_minimo" class="form-label">
                                    Stock Mínimo <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('stock_minimo') is-invalid @enderror" 
                                       id="stock_minimo" 
                                       name="stock_minimo" 
                                       value="{{ old('stock_minimo', 1) }}" 
                                       step="0.01" 
                                       min="0" 
                                       required>
                                @error('stock_minimo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Cantidad mínima para generar alerta</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   value="1" 
                                   {{ old('activo', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">
                                Activo
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('insumos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection