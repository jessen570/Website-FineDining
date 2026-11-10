@extends('layouts.app')

@section('title', 'Buka Table Order — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('table-orders.index') }}">Pesanan Meja</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Buka Pesanan</span>
@endsection

@section('content')
<div style="max-width:760px">

<div class="page-header">
    <div>
        <h1 class="page-title">Buka Table Order</h1>
        <p class="page-desc">Server yang ditugaskan harus berstatus Aktif (SR-13). Item ditambahkan setelah order dibuka.</p>
    </div>
    <a href="{{ route('table-orders.index') }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
    <div><strong>Gagal:</strong>
        <ul class="mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif
@if(session('error'))
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif

<form method="POST" action="{{ route('table-orders.store') }}">
@csrf
<div class="card">
    <div class="card-header"><span class="card-title">Informasi Pesanan</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            {{-- Meja --}}
            <div class="form-group">
                <label class="form-label">Meja *</label>
                <select name="table_id" class="form-select {{ $errors->has('table_id') ? 'is-error' : '' }}" required>
                    <option value="">— Pilih Meja —</option>
                    @foreach($tables as $t)
                        <option value="{{ $t->table_id }}" {{ old('table_id', $preTable) == $t->table_id ? 'selected' : '' }}>
                            {{ $t->table_id }} — {{ $t->floor }} ({{ $t->seats }} kursi)
                        </option>
                    @endforeach
                </select>
                @error('table_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Server --}}
            <div class="form-group">
                <label class="form-label">Server / Pelayan *</label>
                <select name="server_id" class="form-select {{ $errors->has('server_id') ? 'is-error' : '' }}" required>
                    <option value="">— Pilih Server Aktif —</option>
                    @foreach($servers as $srv)
                        <option value="{{ $srv->employee_id }}" {{ old('server_id') == $srv->employee_id ? 'selected' : '' }}>
                            {{ $srv->employee_id }} — {{ $srv->name }} ({{ $srv->job_position }})
                        </option>
                    @endforeach
                </select>
                @error('server_id')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">SR-13: hanya karyawan Aktif yang dapat melayani meja.</p>
            </div>

            {{-- Pelanggan (opsional) --}}
            <div class="form-group">
                <label class="form-label">Pelanggan (Opsional)</label>
                <select name="customer_id" class="form-select {{ $errors->has('customer_id') ? 'is-error' : '' }}">
                    <option value="">— Walk-in / Tanpa Akun —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->contact_id }}" {{ old('customer_id') == $c->contact_id ? 'selected' : '' }}>
                            {{ $c->contact_id }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Booking (opsional) --}}
            <div class="form-group">
                <label class="form-label">Hubungkan ke Reservasi (Opsional)</label>
                <select name="booking_id" class="form-select {{ $errors->has('booking_id') ? 'is-error' : '' }}">
                    <option value="">— Tanpa Reservasi —</option>
                    @foreach($bookings as $bk)
                        <option value="{{ $bk->booking_id }}" {{ old('booking_id', request('booking_id')) == $bk->booking_id ? 'selected' : '' }}>
                            {{ $bk->booking_id }} — {{ $bk->customer?->name }} — {{ $bk->table_id }}
                            ({{ $bk->start_time ? \Carbon\Carbon::parse($bk->start_time)->format('d/m H:i') : '' }})
                        </option>
                    @endforeach
                </select>
                <p class="form-hint">Pilih jika tamu sudah check-in melalui reservasi.</p>
            </div>

            {{-- Catatan --}}
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Catatan Pesanan</label>
                <textarea name="notes" class="form-input form-textarea" placeholder="Instruksi khusus dapur, preferensi…">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('table-orders.index') }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-gold">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4"/></svg>
            Buka Pesanan
        </button>
    </div>
</div>
</form>
</div>
@endsection
