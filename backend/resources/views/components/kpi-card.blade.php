@props([
    'title' => '',
    'value' => 0,
    'sub' => null,
    'iconBg' => '#F7F5F0',
    'iconColor' => '#18382E',
])

<div {{ $attributes->merge(['class' => 'kpi']) }}>
    @if(isset($icon))
        <div class="kpi-icon" style="background:{{ $iconBg }};color:{{ $iconColor }}">
            {{ $icon }}
        </div>
    @endif
    <div class="kpi-label">{{ $title }}</div>
    <div class="kpi-value">{{ $value }}</div>
    @if($sub)
        <div class="kpi-sub">{{ $sub }}</div>
    @endif
</div>

