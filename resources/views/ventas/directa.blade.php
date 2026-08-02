@extends('layouts.app')
@section('page-title', 'Venta Directa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-cash-register me-2"></i>Registrar Venta Directa</h4>
    <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <form action="{{ route('ventas.directa.store') }}" method="POST" id="formDirecta">
            @csrf

            <div class="card mb-3">
                <div class="card-header bg-white">
                    <span class="fw-semibold"><i class="fas fa-coins me-2 text-warning"></i>¿Cuánto se vendió?</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Cargá el total cobrado por cada medio. Si solo hubo uno, dejá el otro en blanco.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-money-bill-wave text-success me-1"></i>Efectivo
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Bs</span>
                                <input type="number" name="efectivo" id="efectivo" class="form-control"
                                       step="0.01" min="0" value="{{ old('efectivo') }}"
                                       placeholder="0.00" autofocus oninput="recalcular()">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="fas fa-qrcode text-primary me-1"></i>QR
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Bs</span>
                                <input type="number" name="qr" id="qr" class="form-control"
                                       step="0.01" min="0" value="{{ old('qr') }}"
                                       placeholder="0.00" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Total a registrar</span>
                        <span class="fs-4 fw-bold text-success" id="total">Bs 0.00</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    @if($almacenes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Almacén</label>
                        <select name="almacen_id" class="form-select">
                            <option value="">— Sin almacén —</option>
                            @foreach($almacenes as $a)
                                <option value="{{ $a->id }}" @selected(old('almacen_id') == $a->id)>{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">A qué punto de venta corresponde lo cobrado.</small>
                    </div>
                    @elseif($almacen)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Almacén</label>
                        <div class="form-control bg-light">{{ $almacen->nombre }}</div>
                    </div>
                    @endif

                    <label class="form-label fw-bold">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" maxlength="500"
                           value="{{ old('observaciones') }}"
                           placeholder="Opcional — ej: ventas del turno de la tarde">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-check me-2"></i>Registrar Venta
            </button>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card border-warning">
            <div class="card-header bg-white">
                <span class="fw-semibold"><i class="fas fa-circle-info me-2 text-warning"></i>Tené en cuenta</span>
            </div>
            <div class="card-body">
                <ul class="mb-0 ps-3" style="font-size:.9rem;">
                    <li class="mb-2">
                        <strong>No descuenta stock.</strong> Como no se indica qué productos se
                        vendieron, el sistema no puede saber qué descontar. El stock hay que
                        ajustarlo aparte si hace falta.
                    </li>
                    <li class="mb-2">
                        <strong>Sí cuenta para el cierre de caja.</strong> El efectivo entra al
                        arqueo del cajón y el QR se verifica aparte, igual que una venta normal.
                    </li>
                    <li class="mb-2">
                        <strong>Sí cuenta como ingreso</strong> en el resumen financiero, el
                        dashboard y el reporte de ventas.
                    </li>
                    <li>
                        <strong>No aparece en "productos más vendidos"</strong>, porque no hay
                        productos que contar.
                    </li>
                </ul>
            </div>
        </div>

        <div class="alert alert-warning mt-3 mb-0" style="font-size:.85rem;">
            <i class="fas fa-triangle-exclamation me-1"></i>
            No cargues la misma venta por las dos vías. Si ya la registraste producto por
            producto desde <strong>Nueva Venta</strong>, cargarla acá otra vez duplicaría
            los ingresos del día.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function recalcular() {
    const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
    const qr       = parseFloat(document.getElementById('qr').value) || 0;

    document.getElementById('total').textContent = 'Bs ' + (efectivo + qr).toFixed(2);
}

document.getElementById('formDirecta').addEventListener('submit', function (e) {
    const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
    const qr       = parseFloat(document.getElementById('qr').value) || 0;

    if (efectivo <= 0 && qr <= 0) {
        e.preventDefault();
        alert('Ingresá al menos un monto: efectivo o QR.');
        return;
    }

    const total = (efectivo + qr).toFixed(2);
    if (!confirm(`Vas a registrar una venta de Bs ${total}.\n\nEsto NO descuenta stock.\n\n¿Continuar?`)) {
        e.preventDefault();
    }
});

recalcular();
</script>
@endpush
