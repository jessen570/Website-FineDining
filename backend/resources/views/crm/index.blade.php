@extends('layouts.app')

@section('title', 'CRM — Maison Aster ERP')
@section('breadcrumb')
    <span class="text-gray-600">CRM</span>
@endsection

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Pipeline CRM</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola prospek dan peluang bisnis</p>
    </div>
    <a href="{{ route('crm.create') }}" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Tambah Lead
    </a>
</div>

{{-- ===== KANBAN BOARD ===== --}}
@php
    $stageConfig = [
        'Lead'        => ['color' => 'border-gray-300 bg-gray-50',        'badge' => 'bg-gray-100 text-gray-600',        'icon' => 'circle'],
        'Qualified'   => ['color' => 'border-blue-300 bg-blue-50',        'badge' => 'bg-blue-100 text-blue-700',        'icon' => 'check-circle'],
        'Proposition' => ['color' => 'border-orange-300 bg-orange-50',    'badge' => 'bg-orange-100 text-orange-700',    'icon' => 'send'],
        'Won'         => ['color' => 'border-green-300 bg-green-50',      'badge' => 'bg-green-100 text-green-700',      'icon' => 'trophy'],
        'Lost'        => ['color' => 'border-red-300 bg-red-50',          'badge' => 'bg-red-100 text-red-600',          'icon' => 'x-circle'],
    ];
@endphp

<div class="flex gap-4 overflow-x-auto pb-4 mb-8">
    @foreach($stageConfig as $stage => $cfg)
    @php $cards = $kanban[$stage] ?? collect(); @endphp
    <div class="flex-shrink-0 w-64">
        {{-- Column Header --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <i data-lucide="{{ $cfg['icon'] }}" class="w-4 h-4 text-gray-500"></i>
                <span class="text-sm font-semibold text-gray-700">{{ $stage }}</span>
            </div>
            <span class="status-badge {{ $cfg['badge'] }}">{{ $cards->count() }}</span>
        </div>

        {{-- Cards --}}
        <div class="space-y-2">
            @forelse($cards as $lead)
            <a href="{{ route('crm.show', $lead->lead_id) }}"
               class="block bg-white rounded-lg border {{ $cfg['color'] }} border p-3 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <p class="text-sm font-medium text-gray-800 leading-tight">{{ $lead->name }}</p>
                    @if($lead->is_converted)
                        <span class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full mt-1" title="Dikonversi"></span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-2">
                    {{ $lead->customer->name ?? '—' }}
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono text-gray-400">{{ $lead->lead_id }}</span>
                    @if($lead->expected_revenue)
                        <span class="text-xs font-semibold {{ $stage === 'Won' ? 'text-green-700' : ($stage === 'Lost' ? 'text-red-600' : 'text-gray-700') }}">
                            Rp {{ number_format($lead->expected_revenue, 0, ',', '.') }}
                        </span>
                    @endif
                </div>
            </a>
            @empty
            <div class="bg-white rounded-lg border border-dashed border-gray-200 p-4 text-center">
                <p class="text-xs text-gray-400">Tidak ada lead</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- ===== LIST VIEW ===== --}}
<div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Semua Lead</h3>
        <span class="text-xs text-gray-400">{{ $leads->count() }} total lead</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Lead ID</th>
                    <th class="table-th">Nama Lead</th>
                    <th class="table-th">Pelanggan</th>
                    <th class="table-th">Stage</th>
                    <th class="table-th text-right">Est. Revenue</th>
                    <th class="table-th">Status</th>
                    <th class="table-th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($leads as $lead)
                @php
                    $stageBadge = match($lead->stage) {
                        'Lead'        => 'bg-gray-100 text-gray-600',
                        'Qualified'   => 'bg-blue-50 text-blue-700',
                        'Proposition' => 'bg-orange-50 text-orange-700',
                        'Won'         => 'bg-green-50 text-green-700',
                        'Lost'        => 'bg-red-50 text-red-600',
                        default       => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="table-td font-mono text-xs text-gray-500">{{ $lead->lead_id }}</td>
                    <td class="table-td">
                        <a href="{{ route('crm.show', $lead->lead_id) }}" class="font-medium text-primary hover:underline">
                            {{ $lead->name }}
                        </a>
                    </td>
                    <td class="table-td text-gray-600">{{ $lead->customer->name ?? '—' }}</td>
                    <td class="table-td">
                        <span class="status-badge {{ $stageBadge }}">{{ $lead->stage }}</span>
                    </td>
                    <td class="table-td text-right font-medium">
                        @if($lead->expected_revenue)
                            Rp {{ number_format($lead->expected_revenue, 0, ',', '.') }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @if($lead->is_converted)
                            <span class="status-badge bg-green-50 text-green-700">Converted</span>
                        @else
                            <span class="status-badge bg-gray-100 text-gray-500">Open</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <div class="flex gap-2 justify-end">
                            <a href="{{ route('crm.show', $lead->lead_id) }}" class="text-gray-400 hover:text-primary" title="Lihat">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('crm.edit', $lead->lead_id) }}" class="text-gray-400 hover:text-accent" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="table-td text-center py-10 text-gray-400">
                        <i data-lucide="target" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm">Belum ada lead. <a href="{{ route('crm.create') }}" class="text-primary hover:underline">Tambah lead pertama</a></p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())
    <div class="px-5 py-4 border-t border-surface-border">
        {{ $leads->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
