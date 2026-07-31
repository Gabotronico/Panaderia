@extends('layouts.app')
@section('page-title', 'Planilla #' . $planilla->id)
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4>
            <i class="fas fa-file-invoice-dollar me-2"></i>
            Planilla #{{ $planilla->id }} —
            {{ $planilla->periodo_inicio->format('d/m/Y') }} al {{ $planilla->periodo_fin->format('d/m/Y') }}
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            @if($planilla->estado === 'borrador')
            <form action="{{ route('planillas.cerrar', $planilla) }}" method="POST"
                  onsubmit="return confirm('¿Cerrar esta planilla? No se podrá modificar.')">
                @csrf
                <button class="btn btn-secondary"><i class="fas fa-lock me-1"></i>Cerrar Planilla</button>
            </form>
            @endif
            @if($planilla->estado === 'cerrada')
            <form action="{{ route('planillas.pagar', $planilla) }}" method="POST"
                  onsubmit="return confirm('¿Marcar planilla como PAGADA?')">
                @csrf
                <button class="btn btn-success"><i class="fas fa-money-bill-wave me-1"></i>Marcar Pagada</button>
            </form>
            @endif
            <a href="{{ route('planillas.pdf', $planilla) }}" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Descargar PDF
            </a>

            @php
                // Comillas simples a propósito: el \n queda literal y confirm() lo
                // interpreta como salto de línea. El aviso extra sale solo si la
                // planilla ya se pagó, porque ahí se borra un pago real.
                $avisoBorrado = ($planilla->estado === 'pagada'
                        ? 'ATENCIÓN: esta planilla figura como PAGADA.\n\n' : '')
                    . 'Vas a eliminar la planilla #' . $planilla->id
                    . ' del ' . $planilla->periodo_inicio->format('d/m/Y')
                    . ' al ' . $planilla->periodo_fin->format('d/m/Y') . '.\n\n'
                    . 'Se borrará el detalle de ' . $planilla->detalles->count() . ' empleado(s)'
                    . ' y los adelantos descontados volverán a quedar pendientes.\n\n'
                    . 'Esta acción no se puede deshacer. ¿Continuar?';
            @endphp

            <form action="{{ route('planillas.destroy', $planilla) }}" method="POST"
                  onsubmit="return confirm('{{ $avisoBorrado }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash me-1"></i>Eliminar Planilla
                </button>
            </form>

            <a href="{{ route('planillas.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Resumen --}}
<div class="row g-3 mb-3">
    <div class="col-sm-3">
        <div class="card text-center border-primary">
            <div class="card-body py-2">
                <div class="text-muted small">Tipo</div>
                <div class="fw-bold">{{ ucfirst($planilla->tipo) }}</div>
                <div class="text-muted" style="font-size:.7rem;">
                    {{ $planilla->tipo === 'mensual' ? 'Empleados mensuales · ÷26 días' : 'Empleados semanales · ÷6 días' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Empleados</div>
                <div class="fw-bold fs-5">{{ $planilla->detalles->count() }}</div>
            </div>
        </div>
    </div>
    @php
        $diasLab = 0;
        $cur = $planilla->periodo_inicio->copy();
        while ($cur->lte($planilla->periodo_fin)) {
            if ($cur->dayOfWeek !== 0) $diasLab++;
            $cur->addDay();
        }
    @endphp
    <div class="col-sm-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Días laborables (lun–sáb)</div>
                <div class="fw-bold fs-5">{{ $diasLab }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Estado</div>
                @php
                    $eb = ['borrador'=>'warning text-dark','cerrada'=>'secondary','pagada'=>'success'];
                    $el = ['borrador'=>'Borrador','cerrada'=>'Cerrada','pagada'=>'Pagada'];
                @endphp
                <span class="badge bg-{{ $eb[$planilla->estado] }} fs-6">{{ $el[$planilla->estado] }}</span>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center border-success">
            <div class="card-body py-2">
                <div class="text-muted small">Total a Pagar</div>
                <div class="fw-bold fs-5 text-success">Bs {{ number_format($planilla->total_general, 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabla de detalle --}}
<div class="card">
    <div class="card-header"><i class="fas fa-table me-2"></i>Detalle por Empleado</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Cargo</th>
                        <th class="text-end">Salario Base</th>
                        <th class="text-center">Días Trab.</th>
                        <th class="text-center">Ausentes</th>
                        <th class="text-center">Tardanzas</th>
                        <th class="text-end">Ganado</th>
                        <th class="text-end">Adelantos</th>
                        <th class="text-end fw-bold">Neto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planilla->detalles as $det)
                    <tr>
                        <td>
                            <a href="{{ route('empleados.show', $det->empleado_id) }}" class="text-decoration-none fw-bold">
                                {{ $det->empleado->nombre_completo }}
                            </a>
                        </td>
                        <td><span class="badge bg-info">{{ $det->empleado->cargo->nombre }}</span></td>
                        <td class="text-end">Bs {{ number_format($det->empleado->salario_base, 2) }}</td>
                        <td class="text-center">{{ $det->dias_trabajados + $det->dias_medio }}</td>
                        <td class="text-center">
                            @if($det->dias_ausentes > 0)
                                <span class="badge bg-danger">{{ $det->dias_ausentes }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="text-center">
                            @if($det->dias_tardanza > 0)
                                <span class="badge bg-warning text-dark">{{ $det->dias_tardanza }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="text-end">Bs {{ number_format($det->salario_bruto, 2) }}</td>
                        <td class="text-end text-warning">
                            {{ $det->adelantos_descontados > 0 ? '−Bs ' . number_format($det->adelantos_descontados, 2) : '—' }}
                            @php $arrastre = $det->empleado->adelantos_pendientes; @endphp
                            @if($arrastre > 0)
                                <br><small class="badge bg-danger" style="font-size:.62rem;"
                                           title="No alcanzó el sueldo para descontarlo. Se descuenta en la próxima planilla.">
                                    debe Bs {{ number_format($arrastre, 2) }}
                                </small>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-success fs-6">
                            Bs {{ number_format($det->total_neto, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="6" class="text-end">TOTALES:</td>
                        <td class="text-end">Bs {{ number_format($planilla->detalles->sum('salario_bruto'), 2) }}</td>
                        <td class="text-end text-warning">−Bs {{ number_format($planilla->detalles->sum('adelantos_descontados'), 2) }}</td>
                        <td class="text-end text-success fs-5">Bs {{ number_format($planilla->total_general, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
