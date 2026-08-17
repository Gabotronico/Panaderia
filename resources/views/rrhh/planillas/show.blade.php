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
                        <th class="text-end">Horas extra</th>
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
                        <td class="text-end">
                            {{-- Las horas extra no se deducen del horario: las carga
                                 el administrador y él decide cuánto se paga. --}}
                            @if($det->monto_horas_extra > 0)
                                <span class="text-primary fw-semibold">+Bs {{ number_format($det->monto_horas_extra, 2) }}</span>
                                <br><small class="text-muted">
                                    {{ rtrim(rtrim(number_format($det->horas_extra, 2), '0'), '.') }} h
                                    @if($det->detalle_horas_extra)
                                        · {{ $det->detalle_horas_extra }}
                                    @endif
                                </small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            @if($planilla->estado === 'borrador')
                                <br>
                                <button class="btn btn-link btn-sm p-0 text-decoration-none"
                                        style="font-size:.72rem;"
                                        data-bs-toggle="modal" data-bs-target="#modalHorasExtra{{ $det->id }}">
                                    <i class="fas fa-clock me-1"></i>{{ $det->monto_horas_extra > 0 ? 'Cambiar' : 'Cargar' }}
                                </button>
                            @endif
                        </td>
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
                        @php $totalExtra = $planilla->detalles->sum('monto_horas_extra'); @endphp
                        <td class="text-end text-primary">
                            {{ $totalExtra > 0 ? '+Bs ' . number_format($totalExtra, 2) : '—' }}
                        </td>
                        <td class="text-end text-warning">−Bs {{ number_format($planilla->detalles->sum('adelantos_descontados'), 2) }}</td>
                        <td class="text-end text-success fs-5">Bs {{ number_format($planilla->total_general, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Carga manual de horas extra, solo mientras la planilla es borrador --}}
@if($planilla->estado === 'borrador')
    @foreach($planilla->detalles as $det)
    <div class="modal fade" id="modalHorasExtra{{ $det->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Horas Extra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('planillas.horas-extra', [$planilla, $det]) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <p class="mb-3">
                            Empleado: <strong>{{ $det->empleado->nombre_completo }}</strong><br>
                            <small class="text-muted">
                                Tarifa por hora de referencia:
                                Bs {{ number_format($det->empleado->tarifa_hora, 2) }}
                            </small>
                        </p>

                        <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                            <i class="fas fa-circle-info me-1"></i>
                            Vos decidís cuánto se paga. La sugerencia usa la tarifa por hora,
                            pero podés escribir el monto acordado.
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Horas <span class="text-danger">*</span></label>
                                <input type="number" name="horas_extra" class="form-control"
                                       min="0" step="0.5" value="{{ $det->horas_extra + 0 }}"
                                       data-tarifa="{{ $det->empleado->tarifa_hora }}"
                                       data-monto="#montoExtra{{ $det->id }}"
                                       data-sugerencia="#sugerenciaExtra{{ $det->id }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Monto a pagar (Bs) <span class="text-danger">*</span></label>
                                <input type="number" name="monto_horas_extra" class="form-control"
                                       id="montoExtra{{ $det->id }}"
                                       min="0" step="0.01" value="{{ $det->monto_horas_extra + 0 }}" required>
                                <div class="form-text" id="sugerenciaExtra{{ $det->id }}" style="font-size:.72rem;"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Detalle</label>
                                <input type="text" name="detalle_horas_extra" class="form-control"
                                       value="{{ $det->detalle_horas_extra }}"
                                       placeholder="Ej.: dos domingos de horno">
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between" style="font-size:.85rem;">
                            <span class="text-muted">Neto actual</span>
                            <strong>Bs {{ number_format($det->total_neto, 2) }}</strong>
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">
                            Ganado Bs {{ number_format($det->salario_bruto, 2) }}
                            @if($det->adelantos_descontados > 0)
                                − adelantos Bs {{ number_format($det->adelantos_descontados, 2) }}
                            @endif
                            + horas extra
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    @push('scripts')
    <script>
        // Sugiere el monto al tipear las horas, pero sin pisar lo que el
        // administrador haya escrito a mano: la decisión del monto es suya.
        document.querySelectorAll('input[data-tarifa]').forEach(function (horas) {
            const monto  = document.querySelector(horas.dataset.monto);
            const ayuda  = document.querySelector(horas.dataset.sugerencia);
            const tarifa = parseFloat(horas.dataset.tarifa) || 0;

            horas.addEventListener('input', function () {
                const sugerido = (parseFloat(horas.value) || 0) * tarifa;

                if (sugerido <= 0) {
                    ayuda.textContent = '';
                    return;
                }

                ayuda.innerHTML = 'Sugerido: Bs ' + sugerido.toFixed(2) +
                    ' <a href="#" class="usar-sugerido">usar</a>';

                ayuda.querySelector('.usar-sugerido').addEventListener('click', function (e) {
                    e.preventDefault();
                    monto.value = sugerido.toFixed(2);
                });
            });
        });
    </script>
    @endpush
@endif
@endsection
