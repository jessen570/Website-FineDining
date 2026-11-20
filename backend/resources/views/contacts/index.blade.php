@extends('layouts.app')

@php
    $isVendorTab = ($activeType === 'Vendor');
    $isProc = strtolower(auth()->user()->role ?? '') === 'procurement';
@endphp

@section('title', ($isVendorTab ? 'Vendor / Pemasok' : 'Pelanggan (Customer)') . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $isVendorTab ? 'Vendor / Pemasok' : 'Pelanggan (Customer)' }}</span>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $isVendorTab ? 'Daftar Vendor / Pemasok' : 'Daftar Pelanggan (Customer)' }}</h1>
        <p class="page-desc">
            @if($isVendorTab)
                Daftar pemasok resmi beserta jumlah produk &amp; harga beli tetap yang terdaftar untuk pembuatan RFQ/PO.
            @else
                Daftar pelanggan Maison Aster beserta batas kredit (Credit Limit), eksposur piutang, dan termin pembayaran (SR-15).
            @endif
        </p>
    </div>
    @if(!$isVendorTab || $canManageVendors)
    <a href="{{ route('contacts.create', ['type' => $activeType]) }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ $isVendorTab ? 'Tambah Vendor' : 'Tambah Pelanggan' }}
    </a>
    @endif
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

{{-- ── Tab Pemisah Customer & Vendor ────────────────────────────────────── --}}
@if(!$isProc)
<div style="display:flex;gap:8px;margin-bottom:18px;border-bottom:1px solid #E8E4DA;padding-bottom:12px">
    <a href="{{ route('contacts.index', ['type' => 'Customer']) }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid {{ !$isVendorTab ? '#18382E' : '#E8E4DA' }};background:{{ !$isVendorTab ? '#18382E' : '#FFFFFF' }};color:{{ !$isVendorTab ? '#FFFFFF' : '#343130' }}">
        Pelanggan (Customer)
        <span style="padding:1px 8px;border-radius:999px;font-size:11px;background:{{ !$isVendorTab ? 'rgba(255,255,255,0.18)' : '#F3F4F6' }};color:{{ !$isVendorTab ? '#FFFFFF' : '#4B5563' }}">{{ $customerCount }}</span>
    </a>
    <a href="{{ route('contacts.index', ['type' => 'Vendor']) }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid {{ $isVendorTab ? '#18382E' : '#E8E4DA' }};background:{{ $isVendorTab ? '#18382E' : '#FFFFFF' }};color:{{ $isVendorTab ? '#FFFFFF' : '#343130' }}">
        Vendor / Pemasok
        <span style="padding:1px 8px;border-radius:999px;font-size:11px;background:{{ $isVendorTab ? 'rgba(255,255,255,0.18)' : '#FEF3C7' }};color:{{ $isVendorTab ? '#FFFFFF' : '#92400E' }}">{{ $vendorCount }}</span>
    </a>
</div>
@endif

{{-- ── Toolbar / Search ────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('contacts.index') }}">
<input type="hidden" name="type" value="{{ $activeType }}">
<div class="toolbar mb-5">
    <div class="toolbar-search flex-1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="{{ $isVendorTab ? 'Cari nama vendor, ID, kota…' : 'Cari nama pelanggan, ID, kota…' }}" value="{{ request('search') }}">
    </div>
    <button type="submit" class="btn btn-outline btn-sm">Cari</button>
    @if(request()->filled('search'))
        <a href="{{ route('contacts.index', ['type' => $activeType]) }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</div>
</form>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                @if($isVendorTab)
                <tr>
                    <th style="width:90px">ID</th>
                    <th>Nama Vendor</th>
                    <th>Kota</th>
                    <th class="text-right">Opening AP</th>
                    <th class="text-center">Produk Terdaftar</th>
                    <th>Payment Terms</th>
                    <th class="col-actions">Aksi</th>
                </tr>
                @else
                <tr>
                    <th style="width:90px">ID</th>
                    <th>Nama Pelanggan</th>
                    <th>Kategori</th>
                    <th>Kota</th>
                    <th class="text-right">Credit Limit</th>
                    <th class="text-right">Eksposur</th>
                    <th>Payment Terms</th>
                    <th>Status Kredit</th>
                    <th class="col-actions">Aksi</th>
                </tr>
                @endif
            </thead>
            <tbody>
                @forelse($contacts as $c)
                @if($isVendorTab)
                @php $prodCount = (int) ($pricelistCounts[$c->contact_id] ?? 0); @endphp
                <tr>
                    <td class="col-id" style="white-space:nowrap">{{ $c->contact_id }}</td>
                    <td class="col-name">
                        <a href="{{ route('contacts.show', $c->contact_id) }}">{{ $c->name }}</a>
                    </td>
                    <td class="text-xs">{{ $c->city ?? '—' }}</td>
                    <td class="col-num text-right text-xs">
                        Rp {{ number_format($c->opening_ap ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $prodCount > 0 ? 'b-forest' : 'b-amber' }}" style="font-size:10.5px">
                            {{ $prodCount }} produk
                        </span>
                    </td>
                    <td class="text-xs">{{ $c->payment_terms ?? '—' }}</td>
                    <td class="col-actions">
                        <div style="display:flex;gap:5px">
                            <a href="{{ route('contacts.show', $c->contact_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            @if($canManageVendors)
                            <a href="{{ route('contacts.edit', $c->contact_id) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <form method="POST" action="{{ route('contacts.destroy', $c->contact_id) }}"
                                  onsubmit="return confirm('Yakin ingin menghapus vendor {{ addslashes($c->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs" style="color:#DC2626">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @else
                @php
                    $exp   = (float) $c->credit_exposure;
                    $limit = (float) $c->credit_limit;
                    $over  = $limit > 0 && $exp > $limit;
                @endphp
                <tr>
                    <td class="col-id" style="white-space:nowrap">{{ $c->contact_id }}</td>
                    <td class="col-name">
                        <a href="{{ route('contacts.show', $c->contact_id) }}">{{ $c->name }}</a>
                    </td>
                    <td>
                        <span class="badge b-blue" style="font-size:10px">{{ $c->customer_type ?? 'Customer' }}</span>
                    </td>
                    <td class="text-xs">{{ $c->city ?? '—' }}</td>
                    <td class="col-num text-right text-xs">
                        @if($c->credit_limit > 0)
                            Rp {{ number_format($c->credit_limit, 0, ',', '.') }}
                        @else
                            <span class="text-stone-400">Tunai</span>
                        @endif
                    </td>
                    <td class="col-num text-right text-xs {{ $over ? 'text-red-600 font-semibold' : '' }}">
                        Rp {{ number_format($exp, 0, ',', '.') }}
                    </td>
                    <td class="text-xs">{{ $c->payment_terms ?? '—' }}</td>
                    <td>
                        @if($limit > 0)
                            @if($over)
                                <span class="badge b-red" style="font-size:10px">⚠ Over Limit</span>
                            @elseif($exp / $limit >= 0.8)
                                <span class="badge b-amber" style="font-size:10px">Mendekati</span>
                            @else
                                <span class="badge b-emerald" style="font-size:10px">Aman</span>
                            @endif
                        @else
                            <span class="badge b-gray" style="font-size:10px">Tunai</span>
                        @endif
                    </td>
                    <td class="col-actions">
                        <div style="display:flex;gap:5px">
                            <a href="{{ route('contacts.show', $c->contact_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            <a href="{{ route('contacts.edit', $c->contact_id) }}" class="btn btn-ghost btn-xs">Edit</a>
                            @if(in_array(strtolower(auth()->user()->role ?? ''), ['owner', 'finance', 'host'], true))
                            <form method="POST" action="{{ route('contacts.destroy', $c->contact_id) }}"
                                  onsubmit="return confirm('Yakin ingin menghapus pelanggan {{ addslashes($c->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs" style="color:#DC2626">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="{{ $isVendorTab ? 7 : 9 }}">
                        <div class="empty-state">
                            <div class="empty-title">{{ $isVendorTab ? 'Belum ada vendor' : 'Belum ada pelanggan' }}</div>
                            <div class="empty-text">{{ $isVendorTab ? 'Daftarkan pemasok baru beserta produk dan harganya.' : 'Tambahkan data pelanggan pertama.' }}</div>
                            @if(!$isVendorTab || $canManageVendors)
                            <a href="{{ route('contacts.create', ['type' => $activeType]) }}" class="btn btn-primary btn-sm">
                                {{ $isVendorTab ? '+ Tambah Vendor' : '+ Tambah Pelanggan' }}
                            </a>
                            @endif
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
