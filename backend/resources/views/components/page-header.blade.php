@props([
    'title' => '',
    'description' => null,
])

<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $title }}</div>
            @if(isset($badge))
                {{ $badge }}
            @endif
        </div>
        @if($description)
            <div class="page-desc" style="margin-top:4px">{{ $description }}</div>
        @endif
    </div>
    @if(isset($actions))
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            {{ $actions }}
        </div>
    @endif
</div>

