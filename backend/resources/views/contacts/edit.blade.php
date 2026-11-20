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
                <select name="contact_type" class="form-select" onchange="toggleEditVendorSection(this.value)" required>
                    <option value="Customer" {{ old('contact_type', $contact->contact_type) == 'Customer' ? 'selected' : '' }}>Customer</option>
                    @if($canManageVendors)
                    <option value="Vendor"   {{ old('contact_type', $contact->contact_type) == 'Vendor'   ? 'selected' : '' }}>Vendor</option>
                    @endif
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

@if($canManageVendors)
<div class="card mb-5" id="vendor-pricelist-section" style="{{ old('contact_type', $contact->contact_type) === 'Vendor' ? '' : 'display:none' }}">
    <div class="card-header">
        <div>
            <span class="card-title">Produk &amp; Harga Beli Vendor (Pricelist)</span>
            <p style="font-size:11.5px;color:#8A8478;margin-top:2px">Daftar produk dan harga tetap yang dijual oleh vendor ini.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addVendorItem()">+ Tambah Produk</button>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:55%">Produk (SKU)</th>
                    <th style="width:35%;text-align:right">Harga Beli Vendor (Rp)</th>
                    <th style="width:10%"></th>
                </tr>
            </thead>
            <tbody id="vendor-items-body">
                @php
                    $existingItems = $pricelists->map(fn($pl) => ['variant_sku' => $pl->variant_sku, 'unit_purchase_price' => (float)$pl->unit_purchase_price])->toArray();
                    $editItems = old('vendor_items', !empty($existingItems) ? $existingItems : [['variant_sku' => '', 'unit_purchase_price' => 0]]);
                @endphp
                @foreach($editItems as $idx => $vi)
                <tr id="vi-row-{{ $idx }}">
                    <td>
                        <select name="vendor_items[{{ $idx }}][variant_sku]" class="form-select">
                            <option value="">— Pilih Produk —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->variant_sku }}" {{ ($vi['variant_sku'] ?? '') == $p->variant_sku ? 'selected' : '' }}>
                                    {{ $p->variant_sku }} — {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="vendor_items[{{ $idx }}][unit_purchase_price]" class="form-input"
                            value="{{ $vi['unit_purchase_price'] ?? 0 }}" min="0" step="100" style="text-align:right">
                    </td>
                    <td style="text-align:center">
                        <button type="button" class="btn btn-ghost btn-xs" onclick="removeVendorItem({{ $idx }})" style="color:#DC2626" title="Hapus">✕</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="{{ route('contacts.show', $contact->contact_id) }}" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
</div>
</form>
</div>

@push('scripts')
<script>
const vendorProducts = @json($products->map(fn($p) => ['variant_sku' => $p->variant_sku, 'name' => $p->name]));
let viCount = {{ count($editItems ?? [[]]) }};

function toggleEditVendorSection(type) {
    const vpSec = document.getElementById('vendor-pricelist-section');
    if (vpSec) vpSec.style.display = (type === 'Vendor') ? '' : 'none';
}

function addVendorItem() {
    const idx = viCount++;
    const body = document.getElementById('vendor-items-body');
    const opts = vendorProducts.map(p => `<option value="${p.variant_sku}">${p.variant_sku} — ${p.name}</option>`).join('');
    const tr = document.createElement('tr');
    tr.id = `vi-row-${idx}`;
    tr.innerHTML = `
        <td><select name="vendor_items[${idx}][variant_sku]" class="form-select"><option value="">— Pilih Produk —</option>${opts}</select></td>
        <td><input type="number" name="vendor_items[${idx}][unit_purchase_price]" class="form-input" value="0" min="0" step="100" style="text-align:right"></td>
        <td style="text-align:center"><button type="button" class="btn btn-ghost btn-xs" onclick="removeVendorItem(${idx})" style="color:#DC2626" title="Hapus">✕</button></td>`;
    body.appendChild(tr);
}

function removeVendorItem(idx) {
    const row = document.getElementById(`vi-row-${idx}`);
    if (row && document.querySelectorAll('#vendor-items-body tr').length > 1) {
        row.remove();
    }
}
</script>
@endpush
@endsection
