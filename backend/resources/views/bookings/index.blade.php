@extends('layouts.app')

@section('title', 'Reservasi Meja — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Reservasi Meja</span>
@endsection

@section('content')

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div>
        <h1 class="page-title">Reservasi Meja (Dining Booking)</h1>
        <p class="page-desc">120 menit servis + 15 menit buffer (SR-22) · VIP overlap maks 30 menit (SR-03) · Kapasitas restoran 22 kursi</p>
    </div>
    <a href="{{ route('bookings.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Buat Reservasi
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
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── KPI Strip + Kapasitas Total (F-17) ──────────────────────────────── --}}
@php
    $totalSeats    = \App\Models\RestaurantTable::sum('seats'); // 22
    $occupiedSeats = \App\Models\Booking::whereIn('bookings.status',['Confirmed','Checked-In'])
        ->join('restaurant_tables','bookings.table_id','=','restaurant_tables.table_id')
        ->sum('restaurant_tables.seats');
    $availableSeats = max(0, $totalSeats - $occupiedSeats);
    $capacityPct    = $totalSeats > 0 ? round(($occupiedSeats / $totalSeats) * 100) : 0;
@endphp
<div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
    <div class="kpi">
        <div class="kpi-label">Total Reservasi</div>
        <div class="kpi-value">{{ $bookings->total() }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Dikonfirmasi</div>
        <div class="kpi-value" style="color:#2563EB">{{ \App\Models\Booking::where('status','Confirmed')->count() }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Check-In</div>
        <div class="kpi-value" style="color:#D97706">{{ \App\Models\Booking::where('status','Checked-In')->count() }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Draft</div>
        <div class="kpi-value" style="color:#6B7280">{{ \App\Models\Booking::where('status','Draft')->count() }}</div>
    </div>
    {{-- Kapasitas total restoran (F-17 / SR-04) --}}
    <div class="kpi" style="border-color:{{ $capacityPct >= 90 ? '#FECACA' : '#E8E4DA' }}">
        <div class="kpi-label">Kapasitas Restoran</div>
        <div class="kpi-value" style="font-size:1.1rem;color:{{ $capacityPct >= 90 ? '#DC2626' : '#18382E' }}">
            {{ $occupiedSeats }} / {{ $totalSeats }}
        </div>
        <div style="margin-top:8px">
            <div class="credit-bar">
                <div class="credit-fill" style="width:{{ $capacityPct }}%;background:{{ $capacityPct >= 90 ? '#EF4444' : ($capacityPct >= 70 ? '#F59E0B' : '#10B981') }}"></div>
            </div>
        </div>
        <div class="kpi-sub">{{ $availableSeats }} kursi tersedia</div>
    </div>
</div>

{{-- ── Control Panel: Search / Filters / Group By (Odoo S-01 pattern) ──── --}}
<form method="GET" action="{{ route('bookings.index') }}" id="filter-form">
<div class="toolbar mb-5">
    {{-- Search --}}
    <div class="toolbar-search" style="flex:1;min-width:220px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Cari ID reservasi, nama tamu…" value="{{ request('search') }}">
    </div>

    {{-- Filters --}}
    <select name="status" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        @foreach($statuses as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>

    <select name="table_id" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Meja</option>
        @foreach($tables as $t)
            <option value="{{ $t->table_id }}" {{ request('table_id') == $t->table_id ? 'selected' : '' }}>
                {{ $t->table_id }} — {{ $t->floor }}
            </option>
        @endforeach
    </select>

    <input type="date" name="date" class="toolbar-select" value="{{ request('date') }}"
        onchange="this.form.submit()" style="min-width:150px" title="Filter per tanggal">

    {{-- Group By --}}
    <select name="group_by" class="toolbar-select" onchange="this.form.submit()" title="Group By">
        <option value="">Group By: —</option>
        <option value="status"   {{ request('group_by') == 'status'    ? 'selected' : '' }}>Group: Status</option>
        <option value="table_id" {{ request('group_by') == 'table_id'  ? 'selected' : '' }}>Group: Meja</option>
        <option value="floor"    {{ request('group_by') == 'floor'     ? 'selected' : '' }}>Group: Area</option>
    </select>

    <button type="submit" class="btn btn-outline btn-sm">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Filter
    </button>
    @if(request()->hasAny(['search','status','date','table_id','group_by']))
        <a href="{{ route('bookings.index') }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</div>
</form>

{{-- ── List View (dengan Group By jika dipilih) ───────────────────────── --}}
@php
    $groupBy   = request('group_by');
    $collection = $bookings->getCollection();

    $statusMap = [
        'Draft'      => 'b-gray',
        'Confirmed'  => 'b-blue',
        'Checked-In' => 'b-amber',
        'Done'       => 'b-emerald',
        'Cancelled'  => 'b-red',
    ];

    // Build grouped structure
    if ($groupBy === 'status') {
        $grouped = $collection->groupBy('status');
    } elseif ($groupBy === 'table_id') {
        $grouped = $collection->groupBy('table_id');
    } elseif ($groupBy === 'floor') {
        $grouped = $collection->groupBy(fn($b) => $b->table?->floor ?? 'Lainnya');
    } else {
        $grouped = collect(['Semua Reservasi' => $collection]);
    }
@endphp

@foreach($grouped as $groupLabel => $groupItems)
<div class="card mb-5">
    @if($groupBy)
    <div class="card-header">
        <span class="card-title">{{ $groupLabel }}</span>
        <span class="badge b-gray">{{ count($groupItems) }} reservasi</span>
    </div>
    @endif
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Reservasi</th>
                    <th>Tamu / Pelanggan</th>
                    <th>Meja</th>
                    <th>Area</th>
                    <th>Kursi</th>
                    <th>Tanggal &amp; Jam Mulai</th>
                    <th>Selesai</th>
                    <th>Buffer</th>
                    <th>Server</th>
                    <th>Status</th>
                    <th>VIP</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groupItems as $bk)
                <tr>
                    <td class="col-id">
                        <a href="{{ route('bookings.show', $bk->booking_id) }}" class="font-semibold hover:underline" style="color:#18382E">
                            {{ $bk->booking_id }}
                        </a>
                    </td>
                    <td class="col-name">{{ $bk->customer?->name ?? '—' }}</td>
                    <td><span class="badge b-forest">{{ $bk->table_id }}</span></td>
                    <td class="text-xs text-stone-500">{{ $bk->table?->floor ?? '—' }}</td>
                    <td class="text-xs text-center">{{ $bk->party_size }} / {{ $bk->table?->seats }}</td>
                    <td class="text-xs">
                        {{ $bk->start_time ? \Carbon\Carbon::parse($bk->start_time)->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td class="text-xs">
                        {{ $bk->end_time ? \Carbon\Carbon::parse($bk->end_time)->format('H:i') : '—' }}
                    </td>
                    <td class="text-xs text-amber-600">
                        {{ $bk->buffer_end_time ? \Carbon\Carbon::parse($bk->buffer_end_time)->format('H:i') : '—' }}
                    </td>
                    <td class="text-xs">{{ $bk->assignedServer?->name ?? '—' }}</td>
                    <td><span class="badge {{ $statusMap[$bk->status] ?? 'b-gray' }}">{{ $bk->status }}</span></td>
                    <td>
                        @if($bk->is_vip)
                            <span class="badge b-gold" style="font-size:10px">VIP</span>
                        @else
                            <span class="text-stone-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="col-actions">
                        <div style="display:flex;gap:5px">
                            <a href="{{ route('bookings.show', $bk->booking_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            @if($bk->status === 'Draft')
                                <form method="POST" action="{{ route('bookings.confirm', $bk->booking_id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-xs">Konfirmasi</button>
                                </form>
                            @elseif($bk->status === 'Confirmed')
                                <form method="POST" action="{{ route('bookings.checkin', $bk->booking_id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-gold btn-xs">Check-In</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <div class="empty-title">Belum ada reservasi</div>
                            <div class="empty-text">Mulai buat reservasi meja untuk tamu Maison Aster.</div>
                            <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm">+ Buat Reservasi</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach

{{-- Pagination --}}
@if($bookings->hasPages())
<div style="display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:#8A8478;padding:0 4px">
    <span>Menampilkan {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} dari {{ $bookings->total() }} reservasi</span>
    {{ $bookings->withQueryString()->links('pagination::simple-tailwind') }}
</div>
@endif

@endsection
