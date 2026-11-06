@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Pembelian</span>
@endsection

@section('content')
<div style="max-width:1200px">

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Purchase Orders</h1>
            <p class="page-desc">RFQ → Confirmed PO → Receipt → Vendor Bill · F-12, F-13 · SR-05, SR-06, SR-18</p>
        </div>
        @if(in_array(auth()->user()->role, ['owner','procurement','warehouse']))
        <a href="{{ route('purchase.create') }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Buat RFQ Baru
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="flash-success" style="margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flash-error" style="margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── KPI Summary ──────────────────────────────────────────────── --}}
    @php
        $allPO       = \App\Models\PurchaseOrder::all();
        $rfqCount    = $allPO->where('status','RFQ')->count();
        $confCount   = $allPO->where('status','Confirmed')->count();
        $toBillCount = $allPO->where('billing_status','To Bill')->count();
        $totalValue  = $allPO->whereIn('status',['RFQ','Confirmed'])->sum('total_amount');
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
        <div class="kpi" style="border-left:3px solid #8B5CF6">
            <div class="kpi-label">RFQ (Draft)</div>
            <div class="kpi-value" style="color:#7C3AED">{{ $rfqCount }}</div>
            <div class="kpi-sub">menunggu konfirmasi</div>
        </div>
        <div class="kpi" style="border-left:3px solid #18382E">
            <div class="kpi-label">PO Confirmed</div>
            <div class="kpi-value">{{ $confCount }}</div>
            <div class="kpi-sub">terkunci · SR-18</div>
        </div>
        <div class="kpi" style="border-left:3px solid #D97706">
            <div class="kpi-label">Perlu Ditagih</div>
            <div class="kpi-value" style="color:#D97706">{{ $toBillCount }}</div>
            <div class="kpi-sub">receipt diterima</div>
        </div>
        <div class="kpi" style="border-left:3px solid #C6A76B">
            <div class="kpi-label">Total Nilai Aktif</div>
            <div class="kpi-value" style="font-size:1.1rem">Rp{{ number_format($totalValue/1000, 0, ',', '.') }}K</div>
            <div class="kpi-sub">RFQ + Confirmed</div>
        </div>
    </div>

    {{-- ── Filter Toolbar ───────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('purchase.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:200px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B8B2A6" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PO ID atau vendor...">
        </div>
        <select name="status" class="toolbar-select" onchange="this.form.submit()">
            <option value="" {{ !request('status') ? 'selected' : '' }}>Semua Status</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
        @if(request('search') || request('status'))
            <a href="{{ route('purchase.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </div>
    </form>

    {{-- ── PO Table ──────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daftar Purchase Orders</span>
            <span class="badge b-gray">{{ $orders->total() }} dokumen</span>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Vendor</th>
                        <th>Tanggal Order</th>
                        <th>Status</th>
                        <th>Receipt Status</th>
                        <th>Billing Status</th>
                        <th style="text-align:right">Total</th>
                        <th>Lock</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $po)
                    @php
                        $statusColor = match($po->status) {
                            'Confirmed' => 'b-green',
                            'RFQ'       => 'b-purple',
                            'Done'      => 'b-forest',
                            'Cancelled' => 'b-red',
                            default     => 'b-gray',
                        };
                        $billColor = match($po->billing_status) {
                            'To Bill'       => 'b-amber',
                            'Fully Billed'  => 'b-green',
                            default         => 'b-gray',
                        };
                        $recColor = match($po->receipt_status) {
                            'Pending'  => 'b-blue',
                            'Received' => 'b-green',
                            default    => 'b-gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('purchase.show', $po->po_id) }}"
                               class="text-mono" style="color:#18382E;font-weight:700;text-decoration:none;font-size:12px">
                                {{ $po->po_id }}
                            </a>
                        </td>
                        <td style="font-weight:600">{{ $po->vendor?->name ?? $po->vendor_id }}</td>
                        <td style="color:#6B6560">{{ $po->order_date?->format('d M Y') }}</td>
                        <td><span class="badge {{ $statusColor }}">{{ $po->status }}</span></td>
                        <td><span class="badge {{ $recColor }}" style="font-size:10.5px">{{ $po->receipt_status }}</span></td>
                        <td><span class="badge {{ $billColor }}" style="font-size:10.5px">{{ $po->billing_status }}</span></td>
                        <td style="text-align:right;font-weight:700">Rp{{ number_format($po->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center">
                            @if($po->is_locked)
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" title="Terkunci SR-18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            @else
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B8B2A6" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                            @endif
                        </td>
                        <td class="col-actions">
                            <a href="{{ route('purchase.show', $po->po_id) }}" class="btn btn-ghost btn-xs">Detail</a>
                            @if(!$po->is_locked && in_array(auth()->user()->role, ['owner','procurement']))
                                <a href="{{ route('purchase.edit', $po->po_id) }}" class="btn btn-ghost btn-xs">Edit</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                </div>
                                <div class="empty-title">Tidak ada purchase order</div>
                                <div class="empty-text">Buat RFQ baru untuk memulai proses pembelian.</div>
                                @if(in_array(auth()->user()->role, ['owner','procurement','warehouse']))
                                <a href="{{ route('purchase.create') }}" class="btn btn-primary" style="margin-top:12px">Buat RFQ</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:12.5px;color:#8A8478">
                Menampilkan {{ $orders->firstItem() }}–{{ $orders->lastItem() }} dari {{ $orders->total() }}
            </div>
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
