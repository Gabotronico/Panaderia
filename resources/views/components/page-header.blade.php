@props([
    'title',
    'icon'     => null,
    'subtitle' => null,
    'back'     => null,   // URL del botón "Volver"
])

<div class="page-header">
    <div class="page-header-text">
        <h4 class="page-header-title">
            @if($icon)<i class="fas fa-{{ $icon }}"></i>@endif
            {{ $title }}
        </h4>
        @if($subtitle)
            <p class="page-header-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="page-header-actions">
        {{ $slot }}
        @if($back)
            <a href="{{ $back }}" class="btn btn-light border">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        @endif
    </div>
</div>
