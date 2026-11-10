@extends('layouts.app')

@section('title', 'Edit Reservasi ' . $booking->booking_id . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Reservasi</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('bookings.show', $booking->booking_id) }}">{{ $booking->booking_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Edit</span>
@endsection

@section('content')
<div style="max-width:860px">

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Reservasi — {{ $booking->booking_id }}</h1>
        <p class="page-desc">Konflik jadwal akan dicek ulang setelah perubahan disimpan (SR-04/SR-22).</p>
    </div>
    <a href="{{ route('bookings.show', $booking->booking_id) }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Validasi gagal:</strong>
        <ul class="mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('bookings.update', $booking->booking_id) }}">
@csrf @method('PATCH')
<div class="card">
    <div class="card-header"><span class="card-title">Detail Reservasi</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Pelanggan *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">— Pilih Pelanggan —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->contact_id }}" {{ old('customer_id', $booking->customer_id) == $c->contact_id ? 'selected' : '' }}>
                            {{ $c->contact_id }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Meja *</label>
                <select name="table_id" class="form-select" required>
                    @foreach($tables as $t)
                        <option value="{{ $t->table_id }}" {{ old('table_id', $booking->table_id) == $t->table_id ? 'selected' : '' }}>
                            {{ $t->table_id }} — {{ $t->floor }} ({{ $t->seats }} kursi)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Jumlah Tamu *</label>
                <input type="number" name="party_size" class="form-input" value="{{ old('party_size', $booking->party_size) }}" min="1" required>
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Reservasi *</label>
                <input type="date" name="booking_date" class="form-input"
                    value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Jam Mulai *</label>
                <input type="time" name="start_time" class="form-input"
                    value="{{ old('start_time', $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('H:i') : '') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Server yang Ditugaskan</label>
                <select name="assigned_server_id" class="form-select">
                    <option value="">— Pilih Server (Opsional) —</option>
                    @foreach($servers as $srv)
                        <option value="{{ $srv->employee_id }}" {{ old('assigned_server_id', $booking->assigned_server_id) == $srv->employee_id ? 'selected' : '' }}>
                            {{ $srv->employee_id }} — {{ $srv->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="is_vip" value="1"
                        {{ old('is_vip', $booking->is_vip) ? 'checked' : '' }}
                        style="width:16px;height:16px;accent-color:#18382E">
                    <span class="form-label" style="margin:0">Tamu VIP (SR-03)</span>
                </label>
            </div>

            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-input form-textarea">{{ old('notes', $booking->notes) }}</textarea>
            </div>
        </div>
    </div>
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('bookings.show', $booking->booking_id) }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</div>
</form>
</div>
@endsection
