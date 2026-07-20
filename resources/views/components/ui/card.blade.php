@props(['title' => null])
<div {{ $attributes->merge(['class' => 'card app-card']) }}>
    @if ($title || isset($tools))
        <div class="card-header">
            @if ($title)
                <h3 class="card-title">{{ $title }}</h3>
            @endif
            @isset($tools)
                <div class="card-tools">
                    {{ $tools }}
                </div>
            @endisset
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
