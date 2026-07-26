@props([
    'label',
    'value',
    'icon'    => null,
    'sub'     => null,
    'variant' => 'primary',   // primary | success | warning | danger | info | neutral
    'href'    => null,
])

@php
    $gradientes = [
        'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
        'success' => 'linear-gradient(135deg, #16a34a, #22c55e)',
        'warning' => 'linear-gradient(135deg, #d97706, #f59e0b)',
        'danger'  => 'linear-gradient(135deg, #dc2626, #ef4444)',
        'info'    => 'linear-gradient(135deg, #0284c7, #38bdf8)',
        'neutral' => 'linear-gradient(135deg, #475569, #64748b)',
    ];
    $fondo = $gradientes[$variant] ?? $gradientes['primary'];
    $tag   = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="card stat-card {{ $href ? 'stat-card-link' : '' }}"
    style="background: {{ $fondo }};">
    <div class="card-body d-flex align-items-center justify-content-between">
        <div class="min-w-0">
            <div class="stat-card-label">{{ $label }}</div>
            <div class="stat-card-value">{{ $value }}</div>
            @if($sub)<div class="stat-card-sub">{{ $sub }}</div>@endif
        </div>
        @if($icon)
            <div class="stat-card-icon"><i class="fas fa-{{ $icon }}"></i></div>
        @endif
    </div>
</{{ $tag }}>
