@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Pelanggan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Daftar Pelanggan</div>
        <div class="page-desc">Kelola profil pelanggan, syarat pembayaran, dan plafon kredit.</div>
    </div>
    <a href="{{ route('sales.customers.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Pelanggan
    </a>
</div>

{{-- KPI Cards --}}
@php
    $coll = $customers->getCollection();
    $totalActive  = $coll->where('status','active')->count();
    $overCredit   = $coll->filter(fn($c) => $c->credit_limit > 0 && ($c->credit_exposure ?? 0) > $c->credit_limit)->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="kpi-label">Total Pelanggan</div>
        <div class="kpi-value">{{ $customers->total() }}</div>
        <div class="kpi-sub">Seluruh pelanggan terdaftar</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Akun Aktif</div>
        <div class="kpi-value">{{ $totalActive }}</div>
        <div class="kpi-sub">Pelanggan berstatus aktif</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FEF2F2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="kpi-label">Melampaui Kredit</div>
        <div class="kpi-value">{{ $overCredit }}</div>
        <div class="kpi-sub">Eksposur melebihi batas kredit</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.customers.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search pelanggan, ID, atau kota..." value="{{ request('search') }}">
        </div>
        <select name="type" class="toolbar-select">
            <option value="">Semua Tipe</option>
            <option value="Individu"   {{ request('type') == 'Individu'   ? 'selected' : '' }}>Individu</option>
            <option value="Perusahaan" {{ request('type') == 'Perusahaan' ? 'selected' : '' }}>Perusahaan</option>
        </select>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('sales.customers.index') }}" class="btn btn-outline btn-sm">Reset</a>
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
                    <th>Tipe</th>
                    <th>Kontak</th>
                    <th style="text-align:right">Batas Kredit</th>
                    <th>Penggunaan Kredit</th>
                    <th>Status</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                @php
                    $limit    = $customer->credit_limit ?? 0;
                    $exposure = $customer->credit_exposure ?? 0;
                    $pct      = $limit > 0 ? min(100, round($exposure / $limit * 100)) : 0;
                    $fillColor = $pct >= 90 ? '#EF4444' : ($pct >= 60 ? '#D97706' : '#10B981');
                @endphp
                <tr>
                    <td><span style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $customer->contact_id }}</span></td>
                    <td>
                        <div style="font-weight:600;font-size:13.5px;color:#1E1C1A">
                            <a href="{{ route('sales.customers.show', $customer->contact_id ?? $customer->id) }}" style="color:#18382E;text-decoration:none">{{ $customer->name }}</a>
                        </div>
                        @if($customer->city)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ $customer->city }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $customer->customer_type === 'Perusahaan' ? 'b-purple' : 'b-blue' }}">{{ $customer->customer_type ?? 'Individu' }}</span>
                    </td>
                    <td>
                        @if($customer->email)
                        <div style="font-size:12.5px;color:#4D4944">{{ $customer->email }}</div>
                        @endif
                        @if($customer->phone)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ $customer->phone }}</div>
                        @endif
                    </td>
                    <td style="text-align:right;font-size:13px;color:#1E1C1A">
                        @if($limit > 0)
                        Rp {{ number_format($limit, 0, ',', '.') }}
                        @else
                        <span style="color:#B8B2A6;font-size:12px">Tanpa Batas</span>
                        @endif
                    </td>
                    <td>
                        @if($limit > 0)
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="credit-bar" style="width:60px;flex-shrink:0">
                                <div class="credit-fill" style="width:{{ $pct }}%;background:{{ $fillColor }}"></div>
                            </div>
                            <span style="font-size:11px;color:{{ $fillColor }};font-weight:600">{{ $pct }}%</span>
                        </div>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $customer->status === 'active' ? 'b-green' : 'b-gray' }}">{{ $customer->status === 'active' ? 'Aktif' : 'Non-Aktif' }}</span>
                    </td>
                    <td>
                        <a href="{{ route('sales.customers.show', $customer->contact_id ?? $customer->id) }}" class="btn btn-outline btn-xs">Lihat</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="empty-title">Tidak ada data pelanggan</div>
            <div class="empty-text">Daftarkan pelanggan pertama untuk memulai pencatatan pesanan.</div>
            <a href="{{ route('sales.customers.create') }}" class="btn btn-primary btn-sm">Tambah Pelanggan Baru</a>
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
