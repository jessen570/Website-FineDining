@extends('layouts.app')

@section('title', 'Edit Kontak ' . $contact->contact_id . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Kontak</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('contacts.show', $contact->contact_id) }}">{{ $contact->contact_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Edit</span>
@endsection

@section('content')
<div style="max-width:820px">

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Kontak — {{ $contact->name }}</h1>
        <p class="page-desc">Perubahan credit limit langsung memengaruhi validasi pesanan baru (SR-15).</p>
    </div>
    <a href="{{ route('contacts.show', $contact->contact_id) }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
    <div><strong>Validasi gagal:</strong>
        <ul class="mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('contacts.update', $contact->contact_id) }}">
@csrf @method('PATCH')

<div class="card mb-5">
    <div class="card-header"><span class="card-title">Identitas Kontak</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            <div class="form-group">
                <label class="form-label">ID Kontak</label>
                <input type="text" class="form-input" value="{{ $contact->contact_id }}" disabled
                    style="background:#F7F5F0;color:#8A8478">
                <p class="form-hint">ID tidak dapat diubah.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                    value="{{ old('name', $contact->name) }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Tipe Kontak *</label>
                <select name="contact_type" class="form-select" required>
                    <option value="Customer" {{ old('contact_type', $contact->contact_type) == 'Customer' ? 'selected' : '' }}>Customer</option>
                    <option value="Vendor"   {{ old('contact_type', $contact->contact_type) == 'Vendor'   ? 'selected' : '' }}>Vendor</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tipe Pelanggan</label>
                <select name="customer_type" class="form-select">
                    <option value="">—</option>
                    <option value="Individu"   {{ old('customer_type', $contact->customer_type) == 'Individu'   ? 'selected' : '' }}>Individu</option>
                    <option value="Perusahaan" {{ old('customer_type', $contact->customer_type) == 'Perusahaan' ? 'selected' : '' }}>Perusahaan</option>
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Kota</label>
                <input type="text" name="city" class="form-input" value="{{ old('city', $contact->city) }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Kredit &amp; Termin Pembayaran</span>
        <span class="badge b-amber" style="font-size:10px">SR-15</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            <div class="form-group">
                <label class="form-label">Credit Limit (Rp)</label>
                <input type="number" name="credit_limit" class="form-input"
                    value="{{ old('credit_limit', $contact->credit_limit) }}" min="0" step="1000">
                <p class="form-hint">0 = tunai saja, tidak ada kredit.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Terms</label>
                <select name="payment_terms" class="form-select">
                    <option value="">— Pilih —</option>
                    @foreach(['Tunai','Tunai / 7 hari','7 hari','14 hari','30 hari'] as $t)
                        <option value="{{ $t }}" {{ old('payment_terms', $contact->payment_terms) == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Opening AR (Piutang Awal)</label>
                <input type="number" name="opening_ar" class="form-input"
                    value="{{ old('opening_ar', $contact->opening_ar) }}" min="0">
            </div>

            <div class="form-group">
                <label class="form-label">Opening AP (Utang Awal)</label>
                <input type="number" name="opening_ap" class="form-input"
                    value="{{ old('opening_ap', $contact->opening_ap) }}" min="0">
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="{{ route('contacts.show', $contact->contact_id) }}" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
</div>
</form>
</div>
@endsection
