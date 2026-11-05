@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Persetujuan Diskon</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Persetujuan Diskon Penjualan</div>
        <div class="page-desc">Tinjau dan kelola permintaan diskon khusus di atas kebijakan standar.</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.discounts.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search kode persetujuan atau dokumen..." value="{{ request('search') }}">
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
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvals as $approval)
                @php
                    $statusMap = ['Pending'=>'b-amber','Approved'=>'b-green','Rejected'=>'b-red'];
                    $statusLabel = ['Pending'=>'Menunggu','Approved'=>'Disetujui','Rejected'=>'Ditolak'];
                @endphp
                <tr>
                    <td style="font-family:monospace;font-size:11.5px;color:#6B6560;font-weight:600">{{ $approval->approval_code }}</td>
                    <td>
                        <span class="badge b-blue" style="font-size:10px">{{ ucfirst(str_replace('_',' ',$approval->document_type ?? '')) }}</span>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px;font-family:monospace">{{ $approval->document_id ?? '—' }}</div>
                    </td>
                    <td style="font-size:13px;color:#343130">{{ $approval->customer?->name ?? '—' }}</td>
                    <td style="text-align:center">
                        <span style="font-size:13px;font-weight:700;color:{{ ($approval->discount_percent ?? 0) > 10 ? '#D97706' : '#1E1C1A' }}">{{ $approval->discount_percent ?? 0 }}%</span>
                    </td>
                    <td style="text-align:right;font-size:12.5px;font-weight:600">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</td>
                    <td style="font-size:12.5px;color:#4D4944">{{ $approval->requestedBy?->name ?? '—' }}</td>
                    <td style="font-size:12px;color:#6B6560">{{ \Carbon\Carbon::parse($approval->created_at)->translatedFormat('d M Y') }}</td>
                    <td><span class="badge {{ $statusMap[$approval->status] ?? 'b-gray' }}">{{ $statusLabel[$approval->status] ?? $approval->status }}</span></td>
                    <td><a href="{{ route('sales.discounts.show', $approval->approval_code ?? $approval->id) }}" class="btn btn-outline btn-xs">Lihat</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="empty-title">Tidak ada permohonan diskon</div>
            <div class="empty-text">Permintaan diskon di atas batas wewenang staf akan muncul di sini untuk ditinjau oleh Owner atau Finance.</div>
        </div>
        @endif
    </div>
    @if(isset($approvals) && $approvals->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $approvals->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
