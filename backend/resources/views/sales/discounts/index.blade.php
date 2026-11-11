@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Persetujuan Diskon</span>
@endsection

@section('content')
<x-page-header
    title="Persetujuan Diskon Penjualan"
    description="Tinjau dan kelola permintaan diskon khusus di atas kebijakan standar (SR-08)."
/>

{{-- KPI Summary --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
    <x-kpi-card
        title="Menunggu Persetujuan"
        :value="$summary['pending'] ?? 0"
        sub="Diskon 11–15% perlu tinjauan"
        iconBg="#FFFBEB"
        iconColor="#D97706"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Disetujui"
        :value="$summary['approved'] ?? 0"
        sub="Permintaan telah disetujui"
        iconBg="#ECFDF5"
        iconColor="#059669"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Ditolak"
        :value="$summary['rejected'] ?? 0"
        sub="Permintaan tidak disetujui"
        iconBg="#FEF2F2"
        iconColor="#DC2626"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.discounts.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Cari kode persetujuan, dokumen, pelanggan, atau pemohon..." value="{{ request('search') }}">
        </div>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            <option value="Pending"  {{ request('status') == 'Pending'  ? 'selected' : '' }}>Menunggu Persetujuan</option>
            <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Disetujui</option>
            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Ditolak</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('sales.discounts.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

<div class="card">
    <div style="overflow-x:auto">
        @if(isset($approvals) && $approvals->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Dokumen Terkait</th>
                    <th>Pelanggan</th>
                    <th style="text-align:center">Diskon %</th>
                    <th style="text-align:right">Nilai Transaksi</th>
                    <th>Diminta Oleh</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvals as $approval)
                <tr>
                    <td>
                        <a href="{{ route('sales.discounts.show', $approval->approval_code ?? $approval->id) }}" style="font-family:monospace;font-size:11.5px;color:#18382E;font-weight:700;text-decoration:none">
                            {{ $approval->approval_code }}
                        </a>
                    </td>
                    <td>
                        @php $isQuo = strtolower((string) $approval->document_type) === 'quotation'; @endphp
                        <span class="badge {{ $isQuo ? 'b-blue' : 'b-purple' }}" style="font-size:10px">
                            {{ $isQuo ? 'Quotation' : 'Sales Order' }}
                        </span>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px;font-family:monospace">{{ $approval->document_id ?? '—' }}</div>
                    </td>
                    <td style="font-size:13px;color:#343130">
                        {{ $approval->customer?->name ?? $approval->customer_id ?? '—' }}
                    </td>
                    <td style="text-align:center">
                        <span style="font-size:13px;font-weight:700;color:{{ ($approval->discount_percent ?? 0) > 10 ? '#D97706' : '#1E1C1A' }}">{{ number_format($approval->discount_percent ?? 0, 1) }}%</span>
                    </td>
                    <td style="text-align:right;font-size:12.5px;font-weight:600">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</td>
                    <td>
                        <div style="font-size:12.5px;color:#343130;font-weight:500">{{ $approval->requested_by ?? '—' }}</div>
                        @if($approval->requested_by_role)
                        <div style="font-size:11px;color:#8A8478;text-transform:uppercase">{{ $approval->requested_by_role }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6B6560">{{ \Carbon\Carbon::parse($approval->created_at)->translatedFormat('d M Y') }}</td>
                    <td><x-badge :status="$approval->status" /></td>
                    <td><a href="{{ route('sales.discounts.show', $approval->approval_code ?? $approval->id) }}" class="btn btn-outline btn-xs">Tinjau</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <x-empty-state
            title="Tidak ada permohonan diskon"
            message="Permintaan diskon di atas 10% akan muncul di sini untuk ditinjau oleh Owner atau Finance."
        />
        @endif
    </div>
    @if(isset($approvals) && $approvals->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $approvals->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
