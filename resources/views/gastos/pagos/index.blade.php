@extends('layouts.app')
@section('page-title', 'Control de Pagos')
@section('content')

@php
    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $nombreMes    = $mesesNombres[(int) $month] ?? '';
    $estadoBadge  = ['pendiente'=>'warning text-dark','pagado'=>'success','vencido'=>'danger'];
    $catBadge     = ['alquiler'=>'danger','servicios'=>'primary','mantenimiento'=>'warning','impuestos'=>'dark','otro'=>'secondary'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4><i class="fas fa-calendar-check me-2"></i>Control de Pagos — {{ $nombreMes }} {{ $year }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('gastos-pagos.anual', ['year' => $year]) }}" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-chart-bar me-1"></i>Resumen {{ $year }}
        </a>
        <a href="{{ route('gastos-fijos.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-cog me-1"></i>Gastos Fijos
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Navegación de mes --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('gastos-pagos.index') }}" class="d-flex gap-2 align-items-end flex-wrap">
            @php
                $prevMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonth();
                $nextMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->addMonth();
            @endphp
            <a href="{{ route('gastos-pagos.index', ['year'=>$prevMonth->year,'month'=>$prevMonth->month]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm" style="width:130px;">
                    @foreach($mesesNombres as $num => $nom)
                        @if($num > 0)
                        <option value="{{ $num }}" @selected($month == $num)>{{ $nom }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="number" name="year" class="form-control form-control-sm" style="width:90px;"
                       min="2020" max="2099" value="{{ $year }}">
                <button type="submit" class="btn btn-primary btn-sm">Ir</button>
            </div>
            <a href="{{ route('gastos-pagos.index', ['year'=>$nextMonth->year,'month'=>$nextMonth->month]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-right"></i>
            </a>
        </form>
    </div>
</div>

{{-- Resumen cards --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <div class="text-warning fs-4"><i class="fas fa-clock"></i></div>
                <div class="fw-bold mt-1">Pendientes</div>
                <div class="fs-4 fw-bold">Bs {{ number_format($resumen['pendiente'], 2) }}</div>
                <small class="text-muted">{{ $pagos->where('estado','pendiente')->count() }} item(s)</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <div class="text-danger fs-4"><i class="fas fa-exclamation-circle"></i></div>
                <div class="fw-bold mt-1">Vencidos</div>
                <div class="fs-4 fw-bold text-danger">Bs {{ number_format($resumen['vencido'], 2) }}</div>
                <small class="text-muted">{{ $pagos->where('estado','vencido')->count() }} item(s)</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <div class="text-success fs-4"><i class="fas fa-check-circle"></i></div>
                <div class="fw-bold mt-1">Pagados</div>
                <div class="fs-4 fw-bold text-success">Bs {{ number_format($resumen['pagado'], 2) }}</div>
                <small class="text-muted">{{ $pagos->where('estado','pagado')->count() }} item(s)</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <div class="text-primary fs-4"><i class="fas fa-receipt"></i></div>
                <div class="fw-bold mt-1">Total mes</div>
                <div class="fs-4 fw-bold">Bs {{ number_format($resumen['total'], 2) }}</div>
                <small class="text-muted">{{ $pagos->count() }} gasto(s)</small>
            </div>
        </div>
    </div>
</div>

{{-- Botón generar --}}
@if(!$yaGenerado)
<div class="alert alert-info d-flex align-items-center gap-3">
    <i class="fas fa-info-circle fa-2x"></i>
    <div>
        <strong>No hay gastos generados para este mes.</strong>
        Genera los gastos correspondientes a <strong>{{ $nombreMes }} {{ $year }}</strong>
        basándote en los gastos fijos activos.
    </div>
    <form action="{{ route('gastos-pagos.generar') }}" method="POST" class="ms-auto">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-primary text-nowrap">
            <i class="fas fa-magic me-1"></i>Generar mes
        </button>
    </form>
</div>
@else
<div class="d-flex justify-content-end gap-2 mb-2">
    <form action="{{ route('gastos-pagos.generar') }}" method="POST">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-outline-secondary btn-sm"
                onclick="return confirm('¿Regenerar? Solo agregará los que falten, no duplicará.')">
            <i class="fas fa-sync me-1"></i>Agregar faltantes
        </button>
    </form>
    <form action="{{ route('gastos-pagos.borrar-mes') }}" method="POST"
          onsubmit="return confirm('¿Borrar TODOS los gastos generados de {{ $nombreMes }} {{ $year }}? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-danger btn-sm">
            <i class="fas fa-trash me-1"></i>Borrar mes completo
        </button>
    </form>
</div>
@endif

{{-- Tabla de pagos --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Gasto</th>
                        <th>Categoría</th>
                        <th class="text-center">Vencimiento</th>
                        <th class="text-end">Est.</th>
                        <th class="text-end">Real</th>
                        <th class="text-center">Estado</th>
                        <th>Ref. / Fecha pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $p)
                    <tr class="{{ $p->estado === 'vencido' ? 'table-danger' : ($p->estado === 'pagado' ? 'table-success bg-opacity-25' : '') }}">
                        <td>
                            <strong>{{ $p->gastoFijo->nombre }}</strong>
                            @if($p->gastoFijo->proveedor)
                                <br><small class="text-muted">{{ $p->gastoFijo->proveedor }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $catBadge[$p->gastoFijo->categoria] ?? 'secondary' }}">
                                {{ \App\Models\GastoFijo::etiquetaCategoria($p->gastoFijo->categoria) }}
                            </span>
                        </td>
                        <td class="text-center">
                            {{ $p->fecha_vencimiento->format('d/m/Y') }}
                            @if($p->estado === 'vencido')
                                <br><small class="text-danger">{{ $p->fecha_vencimiento->diffForHumans() }}</small>
                            @endif
                        </td>
                        <td class="text-end">Bs {{ number_format($p->monto_estimado, 2) }}</td>
                        <td class="text-end">
                            @if($p->monto_real !== null)
                                <strong>Bs {{ number_format($p->monto_real, 2) }}</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $estadoBadge[$p->estado] ?? 'secondary' }}">
                                {{ ucfirst($p->estado) }}
                            </span>
                        </td>
                        <td>
                            @if($p->estado === 'pagado')
                                {{ $p->referencia ?? '—' }}<br>
                                <small class="text-muted">{{ $p->fecha_pago?->format('d/m/Y') }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if($p->estado !== 'pagado')
                                <button class="btn btn-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPagar"
                                        data-id="{{ $p->id }}"
                                        data-nombre="{{ $p->gastoFijo->nombre }}"
                                        data-estimado="{{ $p->monto_estimado }}">
                                    <i class="fas fa-check me-1"></i>Pagar
                                </button>
                                @else
                                <form action="{{ route('gastos-pagos.anular', $p) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Anular este pago?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                                @endif

                                <form action="{{ route('gastos-pagos.destroy', $p) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ $p->gastoFijo->nombre }}» de este mes?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay gastos generados para este mes.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Pagar --}}
<div class="modal fade" id="modalPagar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPagar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2 text-success"></i>Registrar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold mb-3" id="modalNombre"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monto pagado (Bs) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="number" name="monto_real" id="montoReal"
                                       class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <small class="text-muted">Estimado: Bs <span id="montoEstimado"></span></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha de pago <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_pago" class="form-control"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">N° Recibo / Referencia</label>
                            <input type="text" name="referencia" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" maxlength="200">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Confirmar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('modalPagar').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalNombre').textContent = btn.dataset.nombre;
    document.getElementById('montoEstimado').textContent = parseFloat(btn.dataset.estimado).toFixed(2);
    document.getElementById('montoReal').value = btn.dataset.estimado;
    document.getElementById('formPagar').action = `/gastos-pagos/${btn.dataset.id}/pagar`;
});
</script>
@endpush
@endsection
