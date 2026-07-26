@props([
    'icon'    => 'inbox',
    'title'   => 'No hay datos',
    'message' => null,
])

<div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-{{ $icon }}"></i></div>
    <div class="empty-state-title">{{ $title }}</div>
    @if($message)<div class="empty-state-message">{{ $message }}</div>@endif
    @if(trim($slot))<div class="mt-3">{{ $slot }}</div>@endif
</div>
