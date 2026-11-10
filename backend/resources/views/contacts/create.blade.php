@extends('layouts.app')

@section('title', 'Tambah Kontak — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Kontak</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Tambah</span>
@endsection

@section('content')
<div style="max-width:820px">

<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Kontak Baru</h1>
        <p class="page-desc">Pelanggan baru memerlukan Credit Limit dan Payment Terms (SR-15). Vendor memerlukan Opening AP jika ada utang awal.</p>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Validasi gagal:</strong>
        <ul class="mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('contacts.store') }}">
@csrf
<div class="card mb-5">
    <div class="card-header"><span class="card-title">Identitas Kontak</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            <div class="form-group">
                <label class="form-label">ID Kontak *</label>
                <input type="text" name="contact_id" class="form-input {{ $errors->has('contact_id') ? 'is-error' : '' }}"
                    value="{{ old('contact_id') }}" placeholder="C-007 / V-05" required>
                @error('contact_id')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">Format: C-XXX untuk pelanggan, V-XX untuk vendor.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                    value="{{ old('name') }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Tipe Kontak *</label>
                <select name="contact_type" class="form-select {{ $errors->has('contact_type') ? 'is-error' : '' }}"
                    onchange="toggleCustomerVendorFields(this.value)" required>
                    <option value="">— Pilih Tipe —</option>
                    <option value="Customer" {{ old('contact_type') == 'Customer' ? 'selected' : '' }}>Customer (Pelanggan)</option>
                    <option value="Vendor" {{ old('contact_type') == 'Vendor' ? 'selected' : '' }}>Vendor (Pemasok)</option>
                </select>
                @error('contact_type')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="customer-type-group" style="{{ old('contact_type') == 'Vendor' ? 'display:none' : '' }}">
                <label class="form-label">Tipe Pelanggan</label>
                <select name="customer_type" class="form-select {{ $errors->has('customer_type') ? 'is-error' : '' }}">
                    <option value="">— Pilih —</option>
                    <option value="Individu" {{ old('customer_type') == 'Individu' ? 'selected' : '' }}>Individu</option>
                    <option value="Perusahaan" {{ old('customer_type') == 'Perusahaan' ? 'selected' : '' }}>Perusahaan</option>
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Kota</label>
                <input type="text" name="city" class="form-input" value="{{ old('city') }}" placeholder="Jakarta, Bogor, …">
            </div>
        </div>
    </div>
</div>

{{-- Kredit & Pembayaran (SR-15) --}}
<div class="card mb-5" id="credit-section">
    <div class="card-header">
        <span class="card-title">Kredit &amp; Termin Pembayaran</span>
        <span class="badge b-amber" style="font-size:10px">SR-15</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">

            <div class="form-group">
                <label class="form-label">Credit Limit (Rp)</label>
                <input type="number" name="credit_limit" class="form-input {{ $errors->has('credit_limit') ? 'is-error' : '' }}"
                    value="{{ old('credit_limit', 0) }}" min="0" step="1000" placeholder="0">
                @error('credit_limit')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">0 = tidak ada kredit (tunai saja). SR-15: pesanan baru diblokir jika melampaui limit.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Terms</label>
                <select name="payment_terms" class="form-select {{ $errors->has('payment_terms') ? 'is-error' : '' }}">
                    <option value="">— Pilih —</option>
                    @foreach(['Tunai','Tunai / 7 hari','7 hari','14 hari','30 hari'] as $t)
                        <option value="{{ $t }}" {{ old('payment_terms') == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                @error('payment_terms')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Opening AR (Piutang Awal)</label>
                <input type="number" name="opening_ar" class="form-input" value="{{ old('opening_ar', 0) }}" min="0">
                <p class="form-hint">Saldo piutang awal sebelum sistem dipakai. Masukkan 0 jika tidak ada.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Opening AP (Utang Awal)</label>
                <input type="number" name="opening_ap" class="form-input" value="{{ old('opening_ap', 0) }}" min="0">
                <p class="form-hint">Saldo utang awal ke vendor ini. Masukkan 0 jika tidak ada.</p>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="{{ route('contacts.index') }}" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Kontak</button>
</div>
</form>
</div>

@push('scripts')
<script>
function toggleCustomerVendorFields(type) {
    const ctGroup = document.getElementById('customer-type-group');
    if (ctGroup) ctGroup.style.display = (type === 'Vendor') ? 'none' : '';
}
// Run on load for old() values
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.querySelector('[name="contact_type"]');
    if (sel && sel.value) toggleCustomerVendorFields(sel.value);
});
</script>
@endpush

@endsection
