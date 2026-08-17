@extends('layouts.app')
@section('page-title', 'Ficha Empleado')
@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4><i class="fas fa-user me-2"></i>{{ $empleado->nombre_completo }}</h4>
        <div>
            <a href="{{ route('empleados.edit', $empleado) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <a href="{{ route('empleados.index') }}" class="btn btn-secondary btn-sm">
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

<div class="row g-3">
    {{-- Datos generales --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-id-card me-2"></i>Datos Personales</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Cargo</th><td><span class="badge bg-info">{{ $empleado->cargo->nombre }}</span></td></tr>
                    <tr><th>CI</th><td>{{ $empleado->ci }}</td></tr>
                    <tr><th>Teléfono</th><td>{{ $empleado->telefono ?? '—' }}</td></tr>
                    <tr><th>Ingreso</th><td>{{ $empleado->fecha_ingreso->format('d/m/Y') }}</td></tr>
                    <tr><th>Estado</th><td>
                        @if($empleado->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-danger">Inactivo</span>
                        @endif
                    </td></tr>
                    <tr><th>Tipo pago</th><td>{{ ucfirst($empleado->tipo_pago) }}</td></tr>
                    <tr>
                        <th>Horario</th>
                        <td>
                            @if($empleado->tiene_horario)
                                <i class="fas fa-clock text-primary me-1"></i>{{ $empleado->horario_texto }}
                                <span class="text-muted">({{ $empleado->jornada_horas }} h)</span>
                                <br>
                                <small class="text-muted">
                                    Tolerancia: {{ $empleado->tolerancia_minutos }} min ·
                                    atraso y horas extra se calculan solos
                                </small>
                            @else
                                <span class="badge bg-secondary">Sin horario</span>
                                <br><small class="text-muted">Atraso y horas extra se cargan a mano</small>
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- Resumen de remuneración --}}
                @php
                    $diasMes = 26;
                    $diasSem = 6;
                    $base    = (float) $empleado->salario_base;
                    if ($empleado->tipo_pago === 'mensual') {
                        $mensual = $base;
                        $diario  = $base / $diasMes;
                        $semanal = $diario * $diasSem;
                    } else {
                        $semanal = $base;
                        $diario  = $base / $diasSem;
                        $mensual = $diario * $diasMes;
                    }
                    $hora = $diario / 8;
                @endphp
                <hr class="my-2">
                <small class="text-muted fw-bold"><i class="fas fa-calculator me-1"></i>Equivalencias salariales</small>
                <div class="row g-1 mt-1">
                    <div class="col-6">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="text-muted" style="font-size:.7rem;">Por hora</div>
                            <div class="fw-bold text-secondary">Bs {{ number_format($hora, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-primary bg-opacity-10 rounded p-2 text-center">
                            <div class="text-muted" style="font-size:.7rem;">Por día</div>
                            <div class="fw-bold text-primary">Bs {{ number_format($diario, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-success bg-opacity-10 rounded p-2 text-center">
                            <div class="text-muted" style="font-size:.7rem;">Por semana</div>
                            <div class="fw-bold text-success">Bs {{ number_format($semanal, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-dark bg-opacity-10 rounded p-2 text-center">
                            <div class="text-muted" style="font-size:.7rem;">Por mes</div>
                            <div class="fw-bold">Bs {{ number_format($mensual, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-1"><small class="text-muted">Base: 26 días hábiles/mes · 6 días/semana · 8 h/día</small></div>

                @if($empleado->observaciones)
                    <hr><small class="text-muted">{{ $empleado->observaciones }}</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Adelantos --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-hand-holding-usd me-2"></i>Adelantos</span>
                <span class="badge bg-warning text-dark">Pendiente: Bs {{ number_format($totalAdelantosPendientes, 2) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px; overflow-y:auto;">
                    <table class="table table-sm mb-0">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Fecha</th><th class="text-end">Monto</th><th>Estado</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adelantos as $adv)
                            <tr>
                                <td>
                                    {{ $adv->fecha->format('d/m/Y') }}
                                    @if($adv->descripcion)
                                        <br><small class="text-muted">{{ $adv->descripcion }}</small>
                                    @endif
                                </td>
                                <td class="text-end">Bs {{ number_format($adv->monto, 2) }}</td>
                                <td>
                                    @if($adv->planilla_id)
                                        <span class="badge bg-success" title="Descontado en planilla #{{ $adv->planilla_id }}">Descontado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Un adelanto ya descontado forma parte del total de una
                                         planilla cerrada: tocarlo desincronizaría ese pago. --}}
                                    @if($adv->planilla_id)
                                        <i class="fas fa-lock text-muted" style="font-size:.7rem;"
                                           title="Descontado en la planilla #{{ $adv->planilla_id }}. Para corregirlo hay que eliminar esa planilla."></i>
                                    @else
                                        <button class="btn btn-link btn-sm p-0 me-2 text-warning"
                                                title="Corregir adelanto"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarAdelanto{{ $adv->id }}">
                                            <i class="fas fa-pen" style="font-size:.7rem;"></i>
                                        </button>
                                        @php
                                            $avisoAdv = 'Vas a eliminar el adelanto de Bs '
                                                . number_format($adv->monto, 2) . ' del '
                                                . $adv->fecha->format('d/m/Y') . '.\n\n'
                                                . $empleado->nombre . ' deja de deber esa plata.\n\n¿Continuar?';
                                        @endphp
                                        <form action="{{ route('adelantos.destroy', $adv) }}" method="POST"
                                              class="d-inline" onsubmit="return confirm('{{ $avisoAdv }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-link btn-sm p-0 text-danger" title="Eliminar adelanto">
                                                <i class="fas fa-trash" style="font-size:.7rem;"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-2">Sin adelantos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalAdelanto">
                    <i class="fas fa-plus me-1"></i>Registrar Adelanto
                </button>
            </div>
        </div>
    </div>

    {{-- Últimas planillas --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-file-invoice-dollar me-2"></i>Historial de Pagos</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px; overflow-y:auto;">
                    <table class="table table-sm mb-0">
                        <thead class="sticky-top bg-white">
                            <tr><th>Período</th><th class="text-end">Neto</th></tr>
                        </thead>
                        <tbody>
                            @forelse($planillaDetalles as $det)
                            <tr>
                                <td>
                                    <a href="{{ route('planillas.show', $det->planilla_id) }}" class="text-decoration-none">
                                        {{ $det->planilla->periodo_inicio->format('d/m') }} — {{ $det->planilla->periodo_fin->format('d/m/Y') }}
                                    </a>
                                    <br><small class="text-muted">{{ $det->dias_trabajados }} días</small>
                                </td>
                                <td class="text-end fw-bold text-success">Bs {{ number_format($det->total_neto, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-2">Sin planillas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Asistencias recientes --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check me-2"></i>Asistencias Recientes (últimos 30 días)</span>
                <a href="{{ route('asistencias.index', ['empleado_id' => $empleado->id]) }}" class="btn btn-outline-primary btn-sm">
                    Ver todo
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Fecha</th><th>Estado</th><th>Entrada</th><th>Salida</th><th>Atraso</th><th>Obs.</th></tr>
                        </thead>
                        <tbody>
                            @forelse($asistencias as $asis)
                            <tr>
                                <td>{{ $asis->fecha->format('d/m/Y') }}</td>
                                <td>
                                    @php
                                        $badges = ['presente'=>'success','ausente'=>'danger','tardanza'=>'warning text-dark','medio_dia'=>'info','feriado'=>'secondary','licencia'=>'primary'];
                                        $labels = ['presente'=>'Presente','ausente'=>'Ausente','tardanza'=>'Tardanza','medio_dia'=>'Medio día','feriado'=>'Feriado','licencia'=>'Licencia'];
                                    @endphp
                                    <span class="badge bg-{{ $badges[$asis->estado] ?? 'secondary' }}">
                                        {{ $labels[$asis->estado] ?? $asis->estado }}
                                    </span>
                                </td>
                                <td>{{ $asis->hora_entrada ?? '—' }}</td>
                                <td>{{ $asis->hora_salida ?? '—' }}</td>
                                <td>{{ $asis->minutos_tardanza > 0 ? $asis->minutos_tardanza . ' min' : '—' }}</td>
                                <td class="text-muted small">{{ $asis->observaciones ?? '' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal adelanto --}}
<div class="modal fade" id="modalAdelanto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hand-holding-usd me-2"></i>Registrar Adelanto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('empleados.adelanto', $empleado) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-3">Empleado: <strong>{{ $empleado->nombre_completo }}</strong></p>

                    @php $capacidad = $empleado->capacidad_adelanto; @endphp

                    <div class="alert {{ $capacidad > 0 ? 'alert-info' : 'alert-warning' }} py-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Máximo disponible</span>
                            <strong>Bs {{ number_format($capacidad, 2) }}</strong>
                        </div>
                        <div class="mt-1" style="font-size:.76rem; opacity:.85;">
                            Sueldo del ciclo Bs {{ number_format($empleado->salario_base, 2) }}
                            @if($totalAdelantosPendientes > 0)
                                − Bs {{ number_format($totalAdelantosPendientes, 2) }} ya adelantados
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Monto (Bs) <span class="text-danger">*</span></label>
                            <input type="number" name="monto" class="form-control"
                                   min="0.01" step="0.01" max="{{ $capacidad }}"
                                   {{ $capacidad <= 0 ? 'disabled' : '' }} required>
                            @if($capacidad <= 0)
                                <div class="form-text text-danger">
                                    Ya tiene adelantado todo su sueldo del ciclo.
                                </div>
                            @endif
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" placeholder="Motivo del adelanto">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning" {{ $capacidad <= 0 ? 'disabled' : '' }}>
                        <i class="fas fa-save me-2"></i>Registrar Adelanto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Un modal de corrección por cada adelanto pendiente. Los ya descontados
     no llevan modal: su fila muestra un candado en lugar de los botones. --}}
@foreach($adelantos->whereNull('planilla_id') as $adv)
<div class="modal fade" id="modalEditarAdelanto{{ $adv->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Corregir Adelanto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('adelantos.update', $adv) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="mb-3">
                        Empleado: <strong>{{ $empleado->nombre_completo }}</strong><br>
                        <small class="text-muted">
                            Registrado el {{ $adv->created_at->format('d/m/Y H:i') }}
                            @if($adv->user) por {{ $adv->user->name }} @endif
                        </small>
                    </p>

                    @php
                        // El tope no cuenta este adelanto: si no, corregir 100 por
                        // 120 chocaría contra su propio monto viejo.
                        $otros  = $adelantos->whereNull('planilla_id')->where('id', '!=', $adv->id)->sum('monto');
                        $limite = round(max(0, (float) $empleado->salario_base - $otros), 2);
                    @endphp

                    <div class="alert alert-info py-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Máximo para este adelanto</span>
                            <strong>Bs {{ number_format($limite, 2) }}</strong>
                        </div>
                        @if($otros > 0)
                        <div class="mt-1" style="font-size:.76rem; opacity:.85;">
                            Sueldo del ciclo Bs {{ number_format($empleado->salario_base, 2) }}
                            − Bs {{ number_format($otros, 2) }} en otros adelantos pendientes
                        </div>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Monto (Bs) <span class="text-danger">*</span></label>
                            <input type="number" name="monto" class="form-control"
                                   min="0.01" step="0.01" max="{{ $limite }}"
                                   value="{{ $adv->monto }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control"
                                   value="{{ $adv->fecha->format('Y-m-d') }}" required>
                            <div class="form-text" style="font-size:.72rem;">
                                Define en qué planilla se descuenta.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <input type="text" name="descripcion" class="form-control"
                                   value="{{ $adv->descripcion }}" placeholder="Motivo del adelanto">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
