@extends('layouts.app')
@section('page-title', 'Control de Pagos')
@section('content')

@php
    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $nombreMes    = $mesesNombres[(int) $month] ?? '';
    $estadoBadge  = ['pendiente'=>'warning text-dark','pagado'=>'success','vencido'=>'danger'];
    $catBadge     = ['alquiler'=>'danger','servicios'=>'primary','mantenimiento'=>'warning text-dark','impuestos'=>'dark','otro'=>'secondary'];
    $prevMonth    = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonth();
    $nextMonth    = \Carbon\Carbon::createFromDate($year, $month, 1)->addMonth();
@endphp

<x-page-header title="Control de Pagos" icon="calendar-check"
               :subtitle="$nombreMes.' de '.$year">
    <a href="{{ route('gastos-pagos.anual', ['year' => $year]) }}" class="btn btn-light border">
        <i class="fas fa-chart-line me-1"></i>Resumen {{ $year }}
    </a>
    <a href="{{ route('gastos-fijos.index') }}" class="btn btn-light border">
        <i class="fas fa-gear me-1"></i>Gastos Fijos
    </a>
</x-page-header>

<x-alerts />

{{-- Navegación de mes --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('gastos-pagos.index') }}"
          class="d-flex gap-2 align-items-center flex-wrap">
        <a href="{{ route('gastos-pagos.index', ['year'=>$prevMonth->year,'month'=>$prevMonth->month]) }}"
           class="btn btn-light border" title="Mes anterior">
            <i class="fas fa-chevron-left"></i>
        </a>

        <select name="month" class="form-select" style="width:140px;">
            @foreach($mesesNombres as $num => $nom)
                @if($num > 0)
                <option value="{{ $num }}" @selected($month == $num)>{{ $nom }}</option>
                @endif
            @endforeach
        </select>
        <input type="number" name="year" class="form-control text-center" style="width:95px;"
               min="2020" max="2099" value="{{ $year }}">
        <button type="submit" class="btn btn-primary">Ir</button>

        <a href="{{ route('gastos-pagos.index', ['year'=>$nextMonth->year,'month'=>$nextMonth->month]) }}"
           class="btn btn-light border" title="Mes siguiente">
            <i class="fas fa-chevron-right"></i>
        </a>

        @if($year != now()->year || $month != now()->month)
            <a href="{{ route('gastos-pagos.index') }}" class="btn btn-link text-muted px-2">
                Ir al mes actual
            </a>
        @endif
    </form>
</div>

{{-- Resumen del mes --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <x-stat-card label="Pendientes" :value="'Bs '.number_format($resumen['pendiente'], 2)"
                     icon="clock" variant="warning"
                     :sub="$pagos->where('estado','pendiente')->count().' por pagar'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Vencidos" :value="'Bs '.number_format($resumen['vencido'], 2)"
                     icon="triangle-exclamation"
                     :variant="$resumen['vencido'] > 0 ? 'danger' : 'neutral'"
                     :sub="$pagos->where('estado','vencido')->count().' fuera de plazo'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Pagados" :value="'Bs '.number_format($resumen['pagado'], 2)"
                     icon="circle-check" variant="success"
                     :sub="$pagos->where('estado','pagado')->count().' completados'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Total del mes" :value="'Bs '.number_format($resumen['total'], 2)"
                     icon="receipt" variant="primary"
                     :sub="$pagos->count().' gasto(s) generado(s)'" />
    </div>
</div>

{{-- Generar / administrar el mes --}}
@if(!$yaGenerado)
<div class="card">
    <div class="card-body">
        <x-empty-state icon="wand-magic-sparkles"
                       title="Este mes aún no tiene gastos generados"
                       :message="'Genera los gastos de '.$nombreMes.' '.$year.' a partir de tus gastos fijos activos. Solo se crean los que corresponden según su frecuencia.'">
            <form action="{{ route('gastos-pagos.generar') }}" method="POST">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="btn btn-primary">
                    <i class="fas fa-wand-magic-sparkles me-1"></i>Generar {{ $nombreMes }}
                </button>
            </form>
        </x-empty-state>
    </div>
</div>
@else

<div class="d-flex justify-content-end gap-2 mb-3">
    <form action="{{ route('gastos-pagos.generar') }}" method="POST">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-light border btn-sm"
                onclick="return confirm('Se agregarán solo los gastos que falten. Los existentes no se tocan.')">
            <i class="fas fa-rotate me-1"></i>Agregar faltantes
        </button>
    </form>
    <form action="{{ route('gastos-pagos.borrar-mes') }}" method="POST"
          onsubmit="return confirm('¿Borrar TODOS los gastos de {{ $nombreMes }} {{ $year }}? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-danger btn-sm">
            <i class="fas fa-trash me-1"></i>Borrar mes completo
        </button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Gasto</th>
                        <th>Categoría</th>
                        <th class="text-center">Vencimiento</th>
                        <th class="text-end">Estimado</th>
                        <th class="text-end">Real</th>
                        <th class="text-end">Dif.</th>
                        <th class="text-center">Estado</th>
                        <th>Comprobante</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pagos as $p)
                    @php
                        $dif = $p->monto_real !== null
                             ? (float) $p->monto_real - (float) $p->monto_estimado
                             : null;
                    @endphp
                    <tr class="{{ $p->estado === 'vencido' ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold text-dark">{{ $p->gastoFijo->nombre }}</div>
                            @if($p->gastoFijo->proveedor)
                                <small class="text-muted">{{ $p->gastoFijo->proveedor }}</small>
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
                                <br><small class="text-danger fw-semibold">
                                    {{ $p->fecha_vencimiento->diffForHumans() }}
                                </small>
                            @endif
                        </td>
                        <td class="text-end"><x-money :amount="$p->monto_estimado" /></td>
                        <td class="text-end">
                            @if($p->monto_real !== null)
                                <strong><x-money :amount="$p->monto_real" /></strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($dif !== null && abs($dif) >= 0.01)
                                <small class="fw-semibold {{ $dif > 0 ? 'text-danger' : 'text-success' }}">
                                    <x-money :amount="$dif" :sign="true" zero="" />
                                </small>
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
                                <small>{{ $p->referencia ?: '—' }}</small>
                                <br><small class="text-muted">{{ $p->fecha_pago?->format('d/m/Y') }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($p->estado !== 'pagado')
                                <button class="btn btn-success btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalPagar"
                                        data-id="{{ $p->id }}"
                                        data-nombre="{{ $p->gastoFijo->nombre }}"
                                        data-estimado="{{ $p->monto_estimado }}">
                                    <i class="fas fa-check me-1"></i>Pagar
                                </button>
                                <button class="btn btn-light border btn-sm" title="Ajustar monto esperado"
                                        data-bs-toggle="modal" data-bs-target="#modalAjustar"
                                        data-id="{{ $p->id }}"
                                        data-nombre="{{ $p->gastoFijo->nombre }}"
                                        data-estimado="{{ $p->monto_estimado }}"
                                        data-vence="{{ $p->fecha_vencimiento->format('Y-m-d') }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                            @else
                                <form action="{{ route('gastos-pagos.anular', $p) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Anular este pago? El gasto volverá a estado pendiente.')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-light border btn-sm" title="Anular pago">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('gastos-pagos.destroy', $p) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar «{{ $p->gastoFijo->nombre }}» de este mes?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" title="Eliminar del mes">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3">Totales del mes</td>
                        <td class="text-end"><x-money :amount="$pagos->sum('monto_estimado')" /></td>
                        <td class="text-end"><x-money :amount="$pagos->sum('monto_real')" /></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Modal: registrar pago --}}
<div class="modal fade" id="modalPagar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPagar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-circle-check me-2 text-success"></i>Registrar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-semibold mb-3" id="pagarNombre"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Monto pagado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="number" name="monto_real" id="pagarMonto"
                                       class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-text">Estimado: Bs <span id="pagarEstimado"></span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de pago <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_pago" class="form-control"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">N° de recibo o referencia</label>
                            <input type="text" name="referencia" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" maxlength="200">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Confirmar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: ajustar monto esperado --}}
<div class="modal fade" id="modalAjustar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAjustar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen me-2 text-primary"></i>Ajustar gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-semibold mb-1" id="ajustarNombre"></p>
                    <p class="text-muted mb-3" style="font-size:.82rem;">
                        Úsalo cuando llega la factura real (luz, agua) y difiere del estimado,
                        o si cambió la fecha de vencimiento.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Monto esperado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="number" name="monto_estimado" id="ajustarMonto"
                                       class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vence el <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_vencimiento" id="ajustarVence"
                                   class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('modalPagar').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('pagarNombre').textContent   = b.dataset.nombre;
    document.getElementById('pagarEstimado').textContent = parseFloat(b.dataset.estimado).toFixed(2);
    document.getElementById('pagarMonto').value          = b.dataset.estimado;
    document.getElementById('formPagar').action          = `/gastos-pagos/${b.dataset.id}/pagar`;
});

document.getElementById('modalAjustar').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('ajustarNombre').textContent = b.dataset.nombre;
    document.getElementById('ajustarMonto').value        = b.dataset.estimado;
    document.getElementById('ajustarVence').value        = b.dataset.vence;
    document.getElementById('formAjustar').action        = `/gastos-pagos/${b.dataset.id}/ajustar`;
});
</script>
@endpush
@endsection
