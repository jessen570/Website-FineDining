@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($headerActions))
        <div class="card-header">
            @if($title)
                <span class="card-title">{{ $title }}</span>
            @endif
            @if(isset($headerActions))
                <div>{{ $headerActions }}</div>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>

