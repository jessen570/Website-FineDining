@extends('layouts.app')

@section('title', 'Delivery Orders')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Persediaan</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Delivery Orders</span>
@endsection

@section('content')
<div style="max-width:1100px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Delivery Orders</h1>
            <p class="page-desc">Pengiriman barang ke pelanggan · Partial Delivery & Backorder · F-08, F-09, F-15 · SR-02, SR-10, SR-11</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Stok
        </a>
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

    {{-- ── Status Filter ──────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('inventory.deliveries') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <select name="status" class="toolbar-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            @foreach(['Draft','Ready','Done','Cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        @if(request('status'))
            <a href="{{ route('inventory.deliveries') }}" class="btn btn-ghost">Reset</a>
        @endif
        <div style="margin-left:auto;font-size:12.5px;color:#8A8478">
            {{ $deliveries->total() }} delivery order
        </div>
    </div>
    </form>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Delivery ID</th>
                        <th>Sales Order</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th style="text-align:right">COGS</th>
                        <th>Backorder</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $do)
                    @php
                        $statusColor = match($do->status) {
                            'Done'      => 'b-green',
                            'Ready'     => 'b-blue',
                            'Draft'     => 'b-gray',
                            'Cancelled' => 'b-red',
                            default     => 'b-gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('inventory.delivery.show', $do->delivery_id) }}"
                               class="text-mono" style="color:#18382E;font-weight:700;text-decoration:none;font-size:12px">
                                {{ $do->delivery_id }}
                            </a>
                        </td>
                        <td>
                            @if($do->salesOrder)
                            <a href="{{ route('sales.orders.show', $do->sales_order_id) }}"
                               style="font-family:monospace;font-size:12px;color:#6B6560;text-decoration:none">
                                {{ $do->sales_order_id }}
                            </a>
                            @else
                                <span style="color:#B8B2A6">—</span>
                            @endif
                        </td>
                        <td style="font-weight:600;color:#1E1C1A">
                            {{ $do->salesOrder?->customer?->name ?? '—' }}
                        </td>
                        <td style="color:#6B6560">
                            {{ $do->delivery_date ? $do->delivery_date->format('d M Y') : '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $statusColor }}">
                                <span class="badge-dot" style="background:currentColor"></span>
                                {{ $do->status }}
                            </span>
                            @if($do->is_posted)
                                <span class="badge b-forest" style="font-size:10px;margin-left:4px">Posted</span>
                            @endif
                        </td>
                        <td style="text-align:right;font-weight:600;color:{{ $do->cost_of_goods_sold > 0 ? '#1E1C1A' : '#B8B2A6' }}">
                            @if($do->cost_of_goods_sold > 0)
                                Rp{{ number_format($do->cost_of_goods_sold, 0, ',', '.') }}
                            @else —
                            @endif
                        </td>
                        <td>
                            @if($do->backorder_delivery_id)
                                <a href="{{ route('inventory.delivery.show', $do->backorder_delivery_id) }}"
                                   class="badge b-amber" style="font-size:10px;text-decoration:none">
                                    {{ $do->backorder_delivery_id }}
                                </a>
                            @else
                                <span style="color:#D6D2C9">—</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <a href="{{ route('inventory.delivery.show', $do->delivery_id) }}" class="btn btn-ghost btn-xs">Detail</a>
                            @if($do->status === 'Ready' && in_array(auth()->user()->role, ['owner','warehouse','procurement']))
                                <a href="{{ route('inventory.delivery.show', $do->delivery_id) }}" class="btn btn-outline btn-xs" style="color:#059669;border-color:#A7F3D0">
                                    Validasi
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                </div>
                                <div class="empty-title">Tidak ada delivery order</div>
                                <div class="empty-text">Konfirmasi Sales Order untuk membuat delivery order secara otomatis.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deliveries->hasPages())
        <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:12.5px;color:#8A8478">
                Menampilkan {{ $deliveries->firstItem() }}–{{ $deliveries->lastItem() }} dari {{ $deliveries->total() }}
            </div>
            {{ $deliveries->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
