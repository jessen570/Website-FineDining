@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Surat Penawaran</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Surat Penawaran (Quotations)</div>
        <div class="page-desc">Kelola proposal harga resmi, masa berlaku, dan konversi ke pesanan penjualan.</div>
    </div>
    <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Quotation
    </a>
</div>

{{-- KPI Cards --}}
@php
    $coll        = $quotations->getCollection();
    $countDraft  = $coll->where('status','Draft')->count();
    $countSent   = $coll->where('status','Sent')->count();
    $countConv   = $coll->where('status','Converted')->count();
    $statusIndo  = ['Draft'=>'Draf','Sent'=>'Terkirim','Accepted'=>'Disetujui','Rejected'=>'Ditolak','Converted'=>'Terkonversi ke SO'];
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#F9FAFB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-label">Total Penawaran</div>
        <div class="kpi-value">{{ $quotations->total() }}</div>
        <div class="kpi-sub">Semua surat penawaran</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FFFBEB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="kpi-label">Draf</div>
        <div class="kpi-value">{{ $countDraft }}</div>
        <div class="kpi-sub">Menunggu tinjauan</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </div>
        <div class="kpi-label">Terkirim</div>
        <div class="kpi-value">{{ $countSent }}</div>
        <div class="kpi-sub">Menunggu konfirmasi klien</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Terkonversi</div>
        <div class="kpi-value">{{ $countConv }}</div>
        <div class="kpi-sub">Menjadi pesanan resmi (SO)</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.quotations.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search nomor penawaran atau pelanggan..." value="{{ request('search') }}">
        </div>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            @foreach($statuses as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $statusIndo[$s] ?? $s }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_from') }}" placeholder="Dari">
        <input type="date" name="date_to" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_to') }}" placeholder="Sampai">
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('sales.quotations.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($quotations->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nomor Penawaran</th>
                    <th>Pelanggan</th>
                    <th>Tanggal / Batas Berlaku</th>
                    <th style="text-align:center">Diskon %</th>
                    <th style="text-align:right">Total Nilai</th>
                    <th>Status</th>
                    <th style="width:150px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotations as $q)
                @php
                    $expiry     = $q->expiration_date ? \Carbon\Carbon::parse($q->expiration_date) : null;
                    $expiryRed  = $expiry && $expiry->isPast() && !in_array($q->status, ['Converted','Accepted']);
                    $statusMap  = ['Draft'=>'b-amber','Sent'=>'b-blue','Accepted'=>'b-green','Rejected'=>'b-red','Converted'=>'b-purple'];
                    $qid        = $q->quotation_id ?? $q->id;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('sales.quotations.show', $qid) }}" style="font-family:monospace;font-size:12px;color:#18382E;text-decoration:none;font-weight:600">{{ $q->quotation_id }}</a>
                    </td>
                    <td>
                        @if($q->customer)
                        <a href="{{ route('sales.customers.show', $q->customer->contact_id ?? $q->customer_id) }}" style="font-size:13px;color:#343130;text-decoration:none;font-weight:500">{{ $q->customer->name }}</a>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:12.5px;color:#4D4944">{{ \Carbon\Carbon::parse($q->quotation_date)->translatedFormat('d M Y') }}</div>
                        @if($expiry)
                        <div style="font-size:11.5px;color:{{ $expiryRed ? '#DC2626' : '#8A8478' }};margin-top:1px;font-weight:{{ $expiryRed ? '600' : '400' }}">
                            Batas: {{ $expiry->translatedFormat('d M Y') }}
                        </div>
                        @endif
                    </td>
                    <td style="text-align:center;font-size:13px;color:#4D4944">{{ $q->discount_percent ?? 0 }}%</td>
                    <td style="text-align:right;font-weight:700;font-size:13px;color:#1E1C1A">Rp {{ number_format($q->gross_amount ?? 0, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $statusMap[$q->status] ?? 'b-gray' }}">{{ $statusIndo[$q->status] ?? $q->status }}</span></td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="{{ route('sales.quotations.show', $qid) }}" class="btn btn-outline btn-xs">Lihat</a>
                            @if($q->status === 'Draft')
                            <a href="{{ route('sales.quotations.edit', $qid) }}" class="btn btn-ghost btn-xs">Ubah</a>
                            @endif
                            @if($q->status === 'Accepted')
                            <form method="POST" action="{{ route('sales.quotations.to-order', $qid) }}" onsubmit="return confirm('Konversi penawaran ini menjadi Pesanan Penjualan (Sales Order)?')">
                                @csrf
                                <button type="submit" class="btn btn-xs b-gold" style="background:#FFFBEB;color:#78350F;border-color:#FDE68A">Konversi ke SO</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="empty-title">Belum ada surat penawaran</div>
            <div class="empty-text">Buat penawaran harga pertama untuk calon klien atau acara rombongan.</div>
            <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary btn-sm">Buat Penawaran Baru</a>
        </div>
        @endif
    </div>
    @if($quotations->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $quotations->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
