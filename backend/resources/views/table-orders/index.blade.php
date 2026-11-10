@extends('layouts.app')

@section('title', 'Pesanan Meja — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Pesanan Meja</span>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Pesanan Meja &amp; Floor Plan (Table Order)</h1>
        <p class="page-desc">Floor plan restoran terintegrasi — PBJT 10%, server aktif wajib (SR-13), split bill (SR-23).</p>
    </div>
    <a href="{{ route('table-orders.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Buka Table Order
    </a>
</div>

@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="flash-error mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── Floor Plan — dikelompokkan per area (F-18: Main Dining, Private Room, Window) ── --}}
@php
    $areas = $tables->groupBy('floor');
    $areaColors = [
        'Main Dining'  => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'label' => '#14532D'],
        'Private Room' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'label' => '#1E40AF'],
        'Window'       => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'label' => '#78350F'],
    ];
    $statusStyles = [
        'available' => 'background:#ECFDF5;border-color:#A7F3D0;color:#065F46',
        'reserved'  => 'background:#FFFBEB;border-color:#FDE68A;color:#78350F',
        'occupied'  => 'background:#FEF2F2;border-color:#FECACA;color:#991B1B',
    ];
@endphp

<div class="card mb-6">
    <div class="card-header">
        <span class="card-title">Floor Plan Restoran</span>
        <div style="display:flex;gap:10px;font-size:11px;align-items:center">
            @foreach([['label'=>'Tersedia','bg'=>'#ECFDF5','border'=>'#A7F3D0'],['label'=>'Dipesan','bg'=>'#FFFBEB','border'=>'#FDE68A'],['label'=>'Occupied','bg'=>'#FEF2F2','border'=>'#FECACA']] as $leg)
            <span style="display:flex;align-items:center;gap:4px">
                <span style="width:12px;height:12px;border-radius:3px;background:{{ $leg['bg'] }};border:1.5px solid {{ $leg['border'] }};display:inline-block"></span>
                {{ $leg['label'] }}
            </span>
            @endforeach
        </div>
    </div>
    <div class="card-body" style="padding:20px">
        @foreach($areas as $areaName => $areaTables)
        @php
            $ac = $areaColors[$areaName] ?? ['bg'=>'#F9FAFB','border'=>'#E5E7EB','label'=>'#374151'];
        @endphp
        <div style="margin-bottom:20px">
            {{-- Area header --}}
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                <div style="height:2px;flex:1;background:{{ $ac['border'] }}"></div>
                <span style="font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;
                             color:{{ $ac['label'] }};background:{{ $ac['bg'] }};
                             padding:3px 12px;border-radius:100px;border:1px solid {{ $ac['border'] }}">
                    {{ $areaName }}
                </span>
                <div style="height:2px;flex:1;background:{{ $ac['border'] }}"></div>
            </div>

            {{-- Tables in area --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px">
                @foreach($areaTables as $t)
                @php
                    $ss      = $statusStyles[$t->status] ?? 'background:#F9FAFB;border-color:#E5E7EB;color:#374151';
                    $activeBk = $t->bookings->first();
                    $activeOrd = $orders->where('table_id', $t->table_id)->first();
                @endphp
                <a href="{{ $t->status === 'available' ? route('table-orders.create').'?table_id='.$t->table_id : route('table-orders.index') }}"
                   style="border:1.5px solid;border-radius:10px;padding:14px 12px;text-decoration:none;{{ $ss }};
                          text-align:center;transition:box-shadow 0.15s;position:relative">

                    {{-- Jika ada order aktif, tampilkan badge --}}
                    @if($activeOrd)
                    <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444"></span>
                    @endif

                    <div style="font-size:17px;font-weight:800;letter-spacing:0.05em">{{ $t->table_id }}</div>
                    <div style="font-size:11px;margin-top:2px;font-weight:500;opacity:0.8">{{ $t->seats }} kursi</div>

                    <div style="margin-top:7px">
                        <span style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em">
                            {{ $t->status }}
                        </span>
                    </div>

                    @if($activeBk)
                        <div style="font-size:10px;margin-top:3px;opacity:0.75;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $activeBk->customer?->name ?? $activeBk->booking_id }}
                        </div>
                    @elseif($activeOrd)
                        <div style="font-size:10px;margin-top:3px;opacity:0.75;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $activeOrd->customer?->name ?? $activeOrd->order_id }}
                        </div>
                    @endif

                    @if($t->status === 'available')
                        <div style="font-size:9.5px;margin-top:5px;color:inherit;opacity:0.6">Klik untuk buka order</div>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Control Panel: Search / Filters / Group By ──────────────────────── --}}
<form method="GET" action="{{ route('table-orders.index') }}" style="margin-bottom:16px">
<div class="toolbar">
    <div class="toolbar-search" style="flex:1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Cari order ID, pelanggan…" value="{{ request('search') }}">
    </div>
    <select name="status" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        @foreach(['Open','Served','Billed','Paid','Cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <select name="group_by" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Group By: —</option>
        <option value="status" {{ request('group_by')=='status' ? 'selected' : '' }}>Group: Status</option>
        <option value="table"  {{ request('group_by')=='table'  ? 'selected' : '' }}>Group: Meja</option>
        <option value="server" {{ request('group_by')=='server' ? 'selected' : '' }}>Group: Server</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['search','status','group_by']))
        <a href="{{ route('table-orders.index') }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</div>
</form>

{{-- ── Daftar Pesanan ───────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Pesanan Aktif &amp; Riwayat</span>
        <span class="badge b-amber">{{ $orders->count() }} pesanan</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Meja</th>
                    <th>Area</th>
                    <th>Server</th>
                    <th>Pelanggan</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">PBJT 10%</th>
                    <th class="text-right">Total</th>
                    <th>Split</th>
                    <th>Status</th>
                    <th>Dibuka</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $ord)
                @php
                    $statusMap = ['Open'=>'b-amber','Served'=>'b-blue','Billed'=>'b-purple','Paid'=>'b-emerald','Cancelled'=>'b-red'];
                    $cls = $statusMap[$ord->status] ?? 'b-gray';
                @endphp
                <tr>
                    <td class="col-id">
                        <a href="{{ route('table-orders.show', $ord->order_id) }}" class="font-semibold hover:underline" style="color:#18382E">
                            {{ $ord->order_id }}
                        </a>
                    </td>
                    <td><span class="badge b-forest">{{ $ord->table_id }}</span></td>
                    <td class="text-xs text-stone-500">{{ $ord->table?->floor ?? '—' }}</td>
                    <td class="text-xs">{{ $ord->server?->name ?? '—' }}</td>
                    <td class="text-xs">{{ $ord->customer?->name ?? 'Walk-in' }}</td>
                    <td class="col-num text-right">Rp {{ number_format($ord->subtotal, 0, ',', '.') }}</td>
                    <td class="col-num text-right" style="color:#D97706">Rp {{ number_format($ord->pbjt_amount, 0, ',', '.') }}</td>
                    <td class="col-num text-right font-semibold">Rp {{ number_format($ord->gross_amount, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($ord->split_count > 1)
                            <span class="badge b-gold" style="font-size:10px">÷{{ $ord->split_count }}</span>
                        @else
                            <span class="text-stone-300 text-xs">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $cls }}">{{ $ord->status }}</span></td>
                    <td class="text-xs text-stone-500">{{ $ord->created_at?->format('d/m H:i') }}</td>
                    <td class="col-actions">
                        <a href="{{ route('table-orders.show', $ord->order_id) }}" class="btn btn-outline btn-xs">Buka</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5"/></svg>
                            </div>
                            <div class="empty-title">Tidak ada pesanan aktif</div>
                            <div class="empty-text">Klik meja di floor plan untuk membuka table order baru.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
