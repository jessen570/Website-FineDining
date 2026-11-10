@extends('layouts.app')

@section('title', 'Buat Reservasi — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Reservasi</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Buat Reservasi</span>
@endsection

@section('content')
<div style="max-width:860px">

<div class="page-header">
    <div>
        <h1 class="page-title">Buat Reservasi Baru</h1>
        <p class="page-desc">Durasi standart 120 menit + 15 menit buffer pembersihan (SR-22). Konflik otomatis diblokir (SR-04).</p>
    </div>
    <a href="{{ route('bookings.index') }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <strong>Validasi gagal:</strong>
        <ul class="mt-1 space-y-1">
            @foreach($errors->all() as $err)<li>• {{ $err }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

@if(session('error'))
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif

<form method="POST" action="{{ route('bookings.store') }}">
@csrf
<div class="card">
    <div class="card-header">
        <span class="card-title">Detail Reservasi</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            {{-- Pelanggan --}}
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Pelanggan *</label>
                <select name="customer_id" class="form-select {{ $errors->has('customer_id') ? 'is-error' : '' }}" required>
                    <option value="">— Pilih Pelanggan —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->contact_id }}" {{ old('customer_id') == $c->contact_id ? 'selected' : '' }}>
                            {{ $c->contact_id }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Meja --}}
            <div class="form-group">
                <label class="form-label">Meja *</label>
                <select name="table_id" class="form-select {{ $errors->has('table_id') ? 'is-error' : '' }}" required>
                    <option value="">— Pilih Meja —</option>
                    @foreach($tables as $t)
                        <option value="{{ $t->table_id }}" {{ old('table_id') == $t->table_id ? 'selected' : '' }}>
                            {{ $t->table_id }} — {{ $t->floor }} ({{ $t->seats }} kursi)
                        </option>
                    @endforeach
                </select>
                @error('table_id')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">Hanya meja dengan status "available" yang ditampilkan.</p>
            </div>

            {{-- Jumlah Tamu --}}
            <div class="form-group">
                <label class="form-label">Jumlah Tamu *</label>
                <input type="number" name="party_size" class="form-input {{ $errors->has('party_size') ? 'is-error' : '' }}"
                    value="{{ old('party_size', 1) }}" min="1" max="20" required>
                @error('party_size')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Tanggal --}}
            <div class="form-group">
                <label class="form-label">Tanggal Reservasi *</label>
                <input type="date" name="booking_date" class="form-input {{ $errors->has('booking_date') ? 'is-error' : '' }}"
                    value="{{ old('booking_date', date('Y-m-d')) }}" required>
                @error('booking_date')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Jam Mulai --}}
            <div class="form-group">
                <label class="form-label">Jam Mulai *</label>
                <input type="time" name="start_time" class="form-input {{ $errors->has('start_time') ? 'is-error' : '' }}"
                    value="{{ old('start_time', '18:00') }}" required>
                @error('start_time')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">Selesai otomatis: +120 menit. Buffer bersih: +135 menit dari waktu mulai.</p>
            </div>

            {{-- Server --}}
            <div class="form-group">
                <label class="form-label">Server yang Ditugaskan</label>
                <select name="assigned_server_id" class="form-select {{ $errors->has('assigned_server_id') ? 'is-error' : '' }}">
                    <option value="">— Pilih Server (Opsional) —</option>
                    @foreach($servers as $srv)
                        <option value="{{ $srv->employee_id }}" {{ old('assigned_server_id') == $srv->employee_id ? 'selected' : '' }}>
                            {{ $srv->employee_id }} — {{ $srv->name }} ({{ $srv->job_position }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_server_id')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">Hanya karyawan berstatus Aktif (SR-13) yang ditampilkan.</p>
            </div>

            {{-- VIP --}}
            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="is_vip" value="1" {{ old('is_vip') ? 'checked' : '' }}
                        style="width:16px;height:16px;accent-color:#18382E">
                    <span class="form-label" style="margin:0">Tamu VIP</span>
                </label>
                <p class="form-hint mt-1">VIP: irisan waktu maksimum 30 menit untuk meja yang sama (SR-03).</p>
            </div>

            {{-- Catatan --}}
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Catatan / Permintaan Khusus</label>
                <textarea name="notes" class="form-input form-textarea {{ $errors->has('notes') ? 'is-error' : '' }}"
                    placeholder="Mis: alergi, dekorasi khusus, preferensi meja…">{{ old('notes') }}</textarea>
                @error('notes')<p class="form-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('bookings.index') }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Buat Reservasi
        </button>
    </div>
</div>
</form>

</div>
@endsection
