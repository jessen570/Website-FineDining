@props([
    'title' => 'Tidak ada data',
    'message' => null,
    'actionUrl' => null,
    'actionText' => null,
])

<div class="empty-state">
    <div class="empty-icon">
        @if(isset($icon))
            {{ $icon }}
        @else
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        @endif
    </div>
    <div class="empty-title">{{ $title }}</div>
    @if($message)
        <div class="empty-text">{{ $message }}</div>
    @endif
    @if($actionUrl && $actionText)
        <div style="margin-top:14px">
            <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">{{ $actionText }}</a>
        </div>
    @endif
</div>

