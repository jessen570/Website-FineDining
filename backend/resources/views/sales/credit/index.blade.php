@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Kontrol Kredit</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Kontrol & Batas Kredit Pelanggan</div>
        <div class="page-desc">Pantau eksposur piutang, pesanan berjalan, dan batas kredit on-account restoran.</div>
    </div>
</div>

{{-- KPI Cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="kpi-label">Dengan Fasilitas Kredit</div>
        <div class="kpi-value">{{ $summary['with_limit'] ?? 0 }}</div>
        <div class="kpi-sub">Pelanggan memiliki plafon kredit</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FEF2F2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="kpi-label">Melampaui Batas (Over)</div>
        <div class="kpi-value">{{ $summary['over_limit'] ?? 0 }}</div>
        <div class="kpi-sub">Eksposur melewati batas kredit</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FFFBEB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="kpi-label">Mendekati Batas (80–100%)</div>
        <div class="kpi-value">{{ $summary['near_limit'] ?? 0 }}</div>
        <div class="kpi-sub">Peringatan limit kredit menipis</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.credit.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search pelanggan atau ID..." value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Filter
        </button>
        <a href="{{ route('sales.credit.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($customers->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:80px">ID</th>
                    <th>Nama Pelanggan</th>
                    <th style="text-align:right">Batas Kredit</th>
                    <th style="text-align:right">Eksposur Kredit</th>
                    <th style="text-align:right">Sisa Kredit</th>
                    <th style="width:160px">Utilisasi</th>
                    <th>Status</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                @php
                    $limit     = $customer->credit_limit ?? 0;
                    $exposure  = $customer->credit_exposure ?? 0;
                    $avail     = max(0, $limit - $exposure);
                    $rawPct    = $limit > 0 ? round($exposure / $limit * 100) : 0;
                    $barPct    = min(100, $rawPct);
                    $overLimit = $limit > 0 && $exposure > $limit;
                    $nearLimit = $limit > 0 && $rawPct >= 80 && !$overLimit;
                    $fillCol   = ($overLimit || $rawPct >= 90) ? '#EF4444' : ($rawPct >= 60 ? '#D97706' : '#10B981');
                @endphp
                <tr>
                    <td><span style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $customer->contact_id }}</span></td>
                    <td>
                        <a href="{{ route('sales.credit.show', $customer->contact_id ?? $customer->id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $customer->name }}</a>
                    </td>
                    <td style="text-align:right;font-size:13px;color:#1E1C1A">
                        @if($limit > 0)
                        Rp {{ number_format($limit, 0, ',', '.') }}
                        @else
                        <span style="color:#B8B2A6">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-size:13px;font-weight:600;color:{{ $overLimit ? '#DC2626' : '#1E1C1A' }}">
                        Rp {{ number_format($exposure, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right;font-size:13px;font-weight:600;color:{{ $avail > 0 ? '#059669' : '#DC2626' }}">
                        @if($limit > 0)
                        Rp {{ number_format($avail, 0, ',', '.') }}
                        @else
                        <span style="color:#B8B2A6">—</span>
                        @endif
                    </td>
                    <td>
                        @if($limit > 0)
                        <div style="display:flex;align-items:center;gap:6px">
                            <div class="credit-bar" style="flex:1">
                                <div class="credit-fill" style="width:{{ $barPct }}%;background:{{ $fillCol }}"></div>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:{{ $fillCol }};width:42px;text-align:right;flex-shrink:0">{{ $rawPct }}%</span>
                        </div>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">Tanpa Batas</span>
                        @endif
                    </td>
                    <td>
                        @if($overLimit)
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:#DC2626">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Melebihi
                        </span>
                        @elseif($nearLimit)
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:#D97706">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                            Mendekati
                        </span>
                        @else
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:#059669">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Aman
                        </span>
                        @endif
                    </td>
                    <td><a href="{{ route('sales.credit.show', $customer->contact_id ?? $customer->id) }}" class="btn btn-outline btn-xs">Lihat</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div class="empty-title">Tidak ada data pelanggan</div>
            <div class="empty-text">Daftarkan pelanggan dengan fasilitas batas kredit untuk memantaunya di sini.</div>
        </div>
        @endif
    </div>
    @if($customers->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $customers->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
