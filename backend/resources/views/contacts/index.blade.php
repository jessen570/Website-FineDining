@extends('layouts.app')

@section('title', 'Kontak & Rekanan — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Kontak & Rekanan</span>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Kontak &amp; Rekanan</h1>
        <p class="page-desc">Pelanggan, pemasok, dan rekanan bisnis Maison Aster. Credit limit &amp; payment terms dikelola di sini (SR-15).</p>
    </div>
    <a href="{{ route('contacts.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Kontak
    </a>
</div>

@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- ── KPI strip ────────────────────────────────────────────────────────── --}}
@php
    $totalCustomers = $contacts->getCollection()->where('contact_type','Customer')->count();
    $totalVendors   = $contacts->getCollection()->where('contact_type','Vendor')->count();
    $overLimit      = $contacts->getCollection()->filter(fn($c) =>
        $c->contact_type === 'Customer' && $c->credit_limit > 0 && $c->credit_exposure > $c->credit_limit
    )->count();
@endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="kpi"><div class="kpi-label">Total Kontak</div><div class="kpi-value">{{ $contacts->total() }}</div></div>
    <div class="kpi"><div class="kpi-label">Pelanggan</div><div class="kpi-value" style="color:#2563EB">{{ \App\Models\Contact::where('contact_type','Customer')->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">Vendor</div><div class="kpi-value" style="color:#D97706">{{ \App\Models\Contact::where('contact_type','Vendor')->count() }}</div></div>
    <div class="kpi">
        <div class="kpi-label">Melebihi Kredit</div>
        <div class="kpi-value" style="color:{{ $overLimit > 0 ? '#DC2626' : '#059669' }}">{{ $overLimit }}</div>
        @if($overLimit > 0)<div class="kpi-sub">SR-15: pesanan baru diblokir</div>@endif
    </div>
</div>

{{-- ── Toolbar / Filters ────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('contacts.index') }}">
<div class="toolbar mb-5">
    <div class="toolbar-search flex-1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Cari nama, ID, kota…" value="{{ request('search') }}">
    </div>
    <select name="type" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Tipe</option>
        <option value="Customer" {{ request('type') == 'Customer' ? 'selected' : '' }}>Customer</option>
        <option value="Vendor" {{ request('type') == 'Vendor' ? 'selected' : '' }}>Vendor</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['search','type']))
        <a href="{{ route('contacts.index') }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</div>
</form>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Kota</th>
                    <th class="text-right">Credit Limit</th>
                    <th class="text-right">Eksposur</th>
                    <th>Payment Terms</th>
                    <th>Status Kredit</th>
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $c)
                @php
                    $exp   = $c->contact_type === 'Customer' ? (float) $c->credit_exposure : 0;
                    $limit = (float) $c->credit_limit;
                    $over  = $limit > 0 && $exp > $limit;
                @endphp
                <tr>
                    <td class="col-id">{{ $c->contact_id }}</td>
                    <td class="col-name">
                        <a href="{{ route('contacts.show', $c->contact_id) }}">{{ $c->name }}</a>
                    </td>
                    <td>
                        <span class="badge {{ $c->contact_type === 'Customer' ? 'b-blue' : 'b-amber' }}" style="font-size:10px">
                            {{ $c->contact_type }}
                        </span>
                        @if($c->customer_type)
                            <span class="text-xs text-stone-400 ml-1">{{ $c->customer_type }}</span>
                        @endif
                    </td>
                    <td class="text-xs">{{ $c->city ?? '—' }}</td>
                    <td class="col-num text-right text-xs">
                        @if($c->credit_limit > 0)
                            Rp {{ number_format($c->credit_limit, 0, ',', '.') }}
                        @else
                            <span class="text-stone-400">—</span>
                        @endif
                    </td>
                    <td class="col-num text-right text-xs {{ $over ? 'text-red-600 font-semibold' : '' }}">
                        @if($c->contact_type === 'Customer')
                            Rp {{ number_format($exp, 0, ',', '.') }}
                        @else
                            <span class="text-stone-400">—</span>
                        @endif
                    </td>
                    <td class="text-xs">{{ $c->payment_terms ?? '—' }}</td>
                    <td>
                        @if($c->contact_type === 'Customer' && $limit > 0)
                            @if($over)
                                <span class="badge b-red" style="font-size:10px">⚠ Over Limit</span>
                            @elseif($exp / $limit >= 0.8)
                                <span class="badge b-amber" style="font-size:10px">Mendekati</span>
                            @else
                                <span class="badge b-emerald" style="font-size:10px">Aman</span>
                            @endif
                        @else
                            <span class="text-stone-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="col-actions">
                        <div style="display:flex;gap:5px">
                            <a href="{{ route('contacts.show', $c->contact_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            <a href="{{ route('contacts.edit', $c->contact_id) }}" class="btn btn-ghost btn-xs">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <div class="empty-title">Belum ada kontak</div>
                            <div class="empty-text">Tambahkan pelanggan atau vendor pertama.</div>
                            <a href="{{ route('contacts.create') }}" class="btn btn-primary btn-sm">+ Tambah Kontak</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contacts->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:#8A8478">
        <span>Menampilkan {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} dari {{ $contacts->total() }}</span>
        {{ $contacts->withQueryString()->links('pagination::simple-tailwind') }}
    </div>
    @endif
</div>

@endsection
