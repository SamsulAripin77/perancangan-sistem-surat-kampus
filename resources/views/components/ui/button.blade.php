@props(['variant' => 'secondary', 'size' => 'sm', 'icon' => null, 'type' => 'button'])
<button type="{{ $type }}"
    {{ $attributes->merge(['class' => "btn btn-$variant btn-$size app-btn"]) }}>
    @if ($icon)
        <i class="fas fa-{{ $icon }}"></i>
    @endif
    {{ $slot }}
</button>
