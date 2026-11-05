@extends('layouts.app')

@section('title', $lead->name . ' — CRM')
@section('breadcrumb')
    <a href="{{ route('crm.index') }}" class="text-gray-500 hover:text-gray-700">CRM</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <span class="text-gray-600">{{ $lead->name }}</span>
@endsection

@section('content')

@php
    $stageBadge = match($lead->stage) {
        'Lead'        => 'bg-gray-100 text-gray-600',
        'Qualified'   => 'bg-blue-50 text-blue-700',
        'Proposition' => 'bg-orange-50 text-orange-700',
        'Won'         => 'bg-green-50 text-green-700',
        'Lost'        => 'bg-red-50 text-red-600',
        default       => 'bg-gray-100 text-gray-500',
    };
    $canConvert = !$lead->is_converted && in_array($lead->stage, ['Proposition', 'Won']);
@endphp

<div class="mb-6 flex items-start justify-between">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h1 class="text-xl font-semibold text-gray-800">{{ $lead->name }}</h1>
            <span class="status-badge {{ $stageBadge }}">{{ $lead->stage }}</span>
            @if($lead->is_converted)
                <span class="status-badge bg-green-50 text-green-700">
                    <i data-lucide="check" class="w-3 h-3 mr-1"></i>Converted
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 font-mono">{{ $lead->lead_id }}</p>
    </div>
    <div class="flex gap-2">
        @if($canConvert)
        <form method="POST" action="{{ route('crm.convert', $lead->lead_id) }}" onsubmit="return confirm('Konversi lead ini ke Sales Order?')">
            @csrf
            <button type="submit" class="btn-accent">
                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                Konversi ke SO
            </button>
        </form>
        @endif
        <a href="{{ route('crm.edit', $lead->lead_id) }}" class="btn-primary">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            Edit
        </a>
        <a href="{{ route('crm.index') }}" class="btn-outline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        {{-- Detail --}}
        <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Detail Lead</h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Lead ID</p>
                    <p class="text-sm font-medium text-gray-800 font-mono">{{ $lead->lead_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Nama Lead</p>
                    <p class="text-sm font-medium text-gray-800">{{ $lead->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Pelanggan</p>
                    <p class="text-sm font-medium text-gray-800">
                        @if($lead->customer)
                            <a href="{{ route('contacts.show', $lead->customer->contact_id) }}" class="text-primary hover:underline">
                                {{ $lead->customer->name }}
                            </a>
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Stage</p>
                    <span class="status-badge {{ $stageBadge }}">{{ $lead->stage }}</span>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Estimasi Revenue</p>
                    <p class="text-sm font-bold text-gray-800">
                        @if($lead->expected_revenue)
                            Rp {{ number_format($lead->expected_revenue, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Dibuat</p>
                    <p class="text-sm font-medium text-gray-800">{{ $lead->created_at ? $lead->created_at->format('d M Y, H:i') : '—' }}</p>
                </div>
            </div>

            @if($lead->notes)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-1">Catatan</p>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $lead->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Linked SO (if converted) --}}
        @if($lead->is_converted && $linkedSO)
        <div class="bg-green-50 rounded-xl border border-green-200 p-6">
            <h2 class="text-sm font-semibold text-green-800 mb-3 flex items-center gap-2">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                Sales Order Terhubung
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-green-600 mb-0.5">Order ID</p>
                    <a href="{{ route('sales.show', $linkedSO->order_id) }}" class="text-sm font-bold text-primary hover:underline">
                        {{ $linkedSO->order_id }}
                    </a>
                </div>
                <div>
                    <p class="text-xs text-green-600 mb-0.5">Status</p>
                    <span class="status-badge bg-green-100 text-green-700">{{ $linkedSO->status }}</span>
                </div>
                <div>
                    <p class="text-xs text-green-600 mb-0.5">Tanggal Order</p>
                    <p class="text-sm font-medium text-gray-800">{{ \Carbon\Carbon::parse($linkedSO->order_date)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-green-600 mb-0.5">Total</p>
                    <p class="text-sm font-bold text-gray-800">Rp {{ number_format($linkedSO->gross_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">

        {{-- Stage Changer --}}
        <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Ubah Stage</h2>
            <form method="POST" action="{{ route('crm.stage', $lead->lead_id) }}">
                @csrf
                @method('PATCH')
                <div class="space-y-3">
                    @foreach($stages as $stage)
                    @php
                        $sc = match($stage) {
                            'Lead'        => 'border-gray-300 hover:bg-gray-50',
                            'Qualified'   => 'border-blue-200 hover:bg-blue-50',
                            'Proposition' => 'border-orange-200 hover:bg-orange-50',
                            'Won'         => 'border-green-200 hover:bg-green-50',
                            'Lost'        => 'border-red-200 hover:bg-red-50',
                            default       => 'border-gray-200',
                        };
                        $active = $lead->stage === $stage;
                    @endphp
                    <label class="flex items-center gap-3 p-2.5 rounded-lg border {{ $sc }} {{ $active ? 'ring-2 ring-primary ring-offset-1' : '' }} cursor-pointer transition-colors">
                        <input type="radio" name="stage" value="{{ $stage }}" {{ $active ? 'checked' : '' }} class="text-primary">
                        <span class="text-sm font-medium text-gray-700">{{ $stage }}</span>
                        @if($active)
                            <i data-lucide="check" class="w-3 h-3 text-primary ml-auto"></i>
                        @endif
                    </label>
                    @endforeach
                    <button type="submit" class="btn-primary w-full justify-center mt-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Update Stage
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

{{-- Audit History --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'CrmLead')
        ->where('document_id', $lead->lead_id)
        ->orderByDesc('created_at')->get();
@endphp
@if($auditLogs->count() > 0)
<div class="mt-6 bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="history" class="w-4 h-4 text-gray-400"></i>
        <h2 class="text-sm font-semibold text-gray-700">Riwayat Audit</h2>
        <span class="ml-auto text-xs text-gray-400">{{ $auditLogs->count() }} entri</span>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($auditLogs as $log)
        <div class="px-5 py-3 flex items-center gap-4">
            <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="activity" class="w-3 h-3 text-gray-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-xs font-semibold text-gray-800">{{ $log->action }}</span>
                <span class="text-xs text-gray-400 ml-2">oleh {{ $log->actor_name }} ({{ $log->actor_role }})</span>
                @if($log->message)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $log->message }}</p>
                @endif
            </div>
            <span class="text-xs text-gray-400">{{ $log->created_at->format('d M H:i') }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
