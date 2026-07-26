{{--
    Muestra la variación contra el mes anterior.
    $valor           → porcentaje (float) o null si no hay base de comparación
    $positivoEsBueno → true en ingresos/utilidad; false en costos (subir es malo)
--}}
@php
    $positivoEsBueno = $positivoEsBueno ?? true;
@endphp

@if($valor === null)
    <span class="text-muted" style="font-size:.78rem;">—</span>
@elseif(abs($valor) < 0.05)
    <span class="text-muted" style="font-size:.78rem;">sin cambio</span>
@else
    @php
        $subio  = $valor > 0;
        $bueno  = $subio === $positivoEsBueno;
        $clase  = $bueno ? 'text-success' : 'text-danger';
        $flecha = $subio ? 'arrow-up' : 'arrow-down';
    @endphp
    <span class="{{ $clase }} fw-semibold" style="font-size:.78rem;">
        <i class="fas fa-{{ $flecha }}" style="font-size:.65rem;"></i>
        {{ number_format(abs($valor), 1) }}%
    </span>
@endif
