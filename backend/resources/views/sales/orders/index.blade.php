@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Pesanan Penjualan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Pesanan Penjualan (Sales Orders)</div>
        <div class="page-desc">Kelola pesanan pelanggan terkonfirmasi, status pengiriman, dan penagihan.</div>
    </div>
    <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Sales Order
    </a>
</div>

{{-- KPI Cards --}}
@php
    $coll           = $orders->getCollection();
    $countDraft     = $coll->where('status','Draft')->count();
    $countConfirmed = $coll->where('status','Confirmed')->count();
    $countDone      = $coll->where('status','Done')->count();
    $countCancelled = $coll->where('status','Cancelled')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#F9FAFB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        </div>
        <div class="kpi-label">Draf</div>
        <div class="kpi-value">{{ $countDraft }}</div>
        <div class="kpi-sub">Belum dikonfirmasi</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Dikonfirmasi</div>
        <div class="kpi-value">{{ $countConfirmed }}</div>
        <div class="kpi-sub">Pesanan aktif berjalan</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
        </div>
        <div class="kpi-label">Selesai (Done)</div>
        <div class="kpi-value">{{ $countDone }}</div>
        <div class="kpi-sub">Pesanan tuntas dilayani</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FEF2F2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="kpi-label">Dibatalkan</div>
        <div class="kpi-value">{{ $countCancelled }}</div>
        <div class="kpi-sub">Pesanan dibatalkan</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('sales.orders.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search nomor pesanan atau nama pelanggan..." value="{{ request('search') }}">
        </div>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            @php
                $statusIndo = ['Draft'=>'Draf','Confirmed'=>'Dikonfirmasi','Done'=>'Selesai','Cancelled'=>'Dibatalkan'];
            @endphp
            @foreach($statuses as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $statusIndo[$s] ?? $s }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_from') }}">
        <input type="date" name="date_to" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_to') }}">
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('sales.orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($orders->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nomor Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Tanggal Pesanan</th>
                    <th>Penawaran Terkait</th>
                    <th style="text-align:center">Diskon %</th>
                    <th style="text-align:right">Total Akhir</th>
                    <th>Status</th>
                    <th>Pengiriman</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                @php
                    $statusMap   = ['Draft'=>'b-gray','Confirmed'=>'b-green','Done'=>'b-blue','Cancelled'=>'b-red'];
                    $statusLabels = ['Draft'=>'Draf','Confirmed'=>'Dikonfirmasi','Done'=>'Selesai','Cancelled'=>'Dibatalkan'];
                    $deliveryMap = ['Pending'=>'b-amber','Partially Delivered'=>'b-blue','Delivered'=>'b-green'];
                    $delivLabels = ['Pending'=>'Menunggu','Partially Delivered'=>'Terkirim Sebagian','Delivered'=>'Selesai Dikirim'];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('sales.orders.show', $order->order_id ?? $order->id) }}" style="font-family:monospace;font-size:12px;color:#18382E;text-decoration:none;font-weight:600">{{ $order->order_id }}</a>
                    </td>
                    <td style="font-size:13px;color:#343130;font-weight:500">{{ $order->customer?->name ?? '—' }}</td>
                    <td>
                        <div style="font-size:12.5px;color:#4D4944">{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d M Y') }}</div>
                        @if($order->confirmed_at)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ \Carbon\Carbon::parse($order->confirmed_at)->translatedFormat('d M Y') }}</div>
                        @endif
                    </td>
                    <td>
                        @if($order->quotation_id && $order->quotation)
                        <a href="{{ route('sales.quotations.show', $order->quotation->quotation_id ?? $order->quotation_id) }}" style="font-family:monospace;font-size:11.5px;color:#18382E;text-decoration:none">{{ $order->quotation->quotation_id }}</a>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;font-size:13px">{{ $order->discount_percent ?? 0 }}%</td>
                    <td style="text-align:right;font-weight:700;font-size:13px;color:#1E1C1A">Rp {{ number_format($order->grand_total ?? 0, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $statusMap[$order->status] ?? 'b-gray' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                    <td>
                        @if($order->delivery_status)
                        <span class="badge {{ $deliveryMap[$order->delivery_status] ?? 'b-gray' }}">{{ $delivLabels[$order->delivery_status] ?? $order->delivery_status }}</span>
                        @else
                        <span class="badge b-amber">Menunggu</span>
                        @endif
                    </td>
                    <td><a href="{{ route('sales.orders.show', $order->order_id ?? $order->id) }}" class="btn btn-outline btn-xs">Lihat</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
            </div>
            <div class="empty-title">Belum ada pesanan penjualan</div>
            <div class="empty-text">Buat pesanan penjualan baru atau konversikan dari penawaran harga resmi.</div>
            <a href="{{ route('sales.orders.create') }}" class="btn btn-primary btn-sm">Buat Pesanan Baru</a>
        </div>
        @endif
    </div>
    @if($orders->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
