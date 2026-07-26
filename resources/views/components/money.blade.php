@props([
    'amount',
    'decimals' => 2,
    'zero'     => '—',   // qué mostrar cuando el monto es 0 o null
    'sign'     => false, // anteponer + / − según el signo
])

@php
    $valor = (float) $amount;
@endphp

@if($valor == 0 && $zero !== null)
    <span {{ $attributes->merge(['class' => 'text-muted']) }}>{{ $zero }}</span>
@else
    <span {{ $attributes }}>@if($sign && $valor > 0)+@endif{{ $valor < 0 ? '−' : '' }}Bs {{ number_format(abs($valor), $decimals) }}</span>
@endif
