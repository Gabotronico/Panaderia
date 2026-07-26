@extends('layouts.app')
@section('page-title', 'Generar Planilla')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-file-invoice-dollar me-2"></i>Generar Planilla</h4>
        <a href="{{ route('planillas.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:620px;">
    <div class="card-header"><i class="fas fa-calculator me-2"></i>Configurar Período</div>
    <div class="card-body">

        {{-- Resumen de empleados por tipo --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="card border-primary text-center py-2 px-3">
                    <div class="text-muted small mb-1"><i class="fas fa-calendar-alt me-1"></i>Empleados Mensuales</div>
                    <div class="fw-bold fs-4 text-primary">{{ $totalMensuales }}</div>
                    <div class="text-muted" style="font-size:.72rem;">salario ÷ 26 días/mes</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-success text-center py-2 px-3">
                    <div class="text-muted small mb-1"><i class="fas fa-calendar-week me-1"></i>Empleados Semanales</div>
                    <div class="fw-bold fs-4 text-success">{{ $totalSemanales }}</div>
                    <div class="text-muted" style="font-size:.72rem;">salario ÷ 6 días/semana</div>
                </div>
            </div>
        </div>

        <div class="alert alert-info py-2">
            <i class="fas fa-info-circle me-1"></i>
            Cada planilla incluye <strong>solo los empleados del mismo tipo de pago</strong> (mensuales por un lado, semanales por otro).
            Los domingos no se contabilizan. Fórmula: <em>valor día × días trabajados + horas extra − tardanzas − adelantos</em>.
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('planillas.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-bold">Tipo de planilla <span class="text-danger">*</span></label>
                    <select name="tipo" id="tipoPlanilla" class="form-select" onchange="actualizarInfo(); autoFecha();">
                        <option value="mensual" @selected(old('tipo') === 'mensual')>Mensual</option>
                        <option value="semanal" @selected(old('tipo') === 'semanal')>Semanal</option>
                    </select>
                </div>
                <div class="col-6 d-flex align-items-end">
                    <div id="infoTipo" class="alert alert-secondary py-2 px-3 mb-0 w-100" style="font-size:.82rem;"></div>
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold">Período inicio <span class="text-danger">*</span></label>
                    <input type="date" name="periodo_inicio" id="periodoInicio"
                           class="form-control @error('periodo_inicio') is-invalid @enderror"
                           value="{{ old('periodo_inicio') }}" required onchange="autoFin()">
                    @error('periodo_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold">Período fin <span class="text-danger">*</span></label>
                    <input type="date" name="periodo_fin" id="periodoFin"
                           class="form-control @error('periodo_fin') is-invalid @enderror"
                           value="{{ old('periodo_fin') }}" required>
                    @error('periodo_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-cogs me-2"></i>Generar Planilla
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const COUNTS = { mensual: {{ $totalMensuales }}, semanal: {{ $totalSemanales }} };

function actualizarInfo() {
    const tipo  = document.getElementById('tipoPlanilla').value;
    const count = COUNTS[tipo];
    const el    = document.getElementById('infoTipo');
    if (count === 0) {
        el.className = 'alert alert-warning py-2 px-3 mb-0 w-100';
        el.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Sin empleados de tipo <strong>' + tipo + '</strong>';
    } else {
        el.className = 'alert alert-success py-2 px-3 mb-0 w-100';
        el.innerHTML = '<i class="fas fa-users me-1"></i><strong>' + count + '</strong> empleado(s) de tipo <strong>' + tipo + '</strong> serán incluidos';
    }
}

function autoFin() {
    const inicio = document.getElementById('periodoInicio').value;
    if (!inicio) return;
    const tipo = document.getElementById('tipoPlanilla').value;
    const d = new Date(inicio + 'T00:00:00');
    if (tipo === 'semanal') {
        d.setDate(d.getDate() + 6);
    } else {
        d.setMonth(d.getMonth() + 1);
        d.setDate(d.getDate() - 1);
    }
    document.getElementById('periodoFin').value = d.toISOString().split('T')[0];
}

function autoFecha() {
    if (document.getElementById('periodoInicio').value) autoFin();
}

// Inicializar info al cargar
actualizarInfo();
</script>
@endpush
@endsection
