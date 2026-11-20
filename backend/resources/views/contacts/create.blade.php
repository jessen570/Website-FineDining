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

            @php
                $isProc = strtolower(auth()->user()->role ?? '') === 'procurement';
                $reqType = in_array(request('type'), ['Customer', 'Vendor'], true) ? request('type') : ($isProc ? 'Vendor' : 'Customer');
                if (!$canManageVendors && $reqType === 'Vendor') {
                    $reqType = 'Customer';
                }
                $defaultType = old('contact_type', $reqType);
            @endphp
            <div class="form-group">
                <label class="form-label">Tipe Kontak *</label>
                <select name="contact_type" class="form-select {{ $errors->has('contact_type') ? 'is-error' : '' }}"
                    onchange="toggleCustomerVendorFields(this.value)" required>
                    <option value="">— Pilih Tipe —</option>
                    <option value="Customer" {{ $defaultType == 'Customer' ? 'selected' : '' }}>Customer (Pelanggan)</option>
                    @if($canManageVendors)
                    <option value="Vendor" {{ $defaultType == 'Vendor' ? 'selected' : '' }}>Vendor (Pemasok)</option>
                    @endif
                </select>
                @if(!$canManageVendors)
                <p class="form-hint">Pendaftaran Vendor baru hanya dapat dilakukan oleh role Procurement atau Owner.</p>
                @endif
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

            <div class="form-group" id="credit-limit-group">
                <label class="form-label">Credit Limit (Rp)</label>
                <input type="number" name="credit_limit" class="form-input {{ $errors->has('credit_limit') ? 'is-error' : '' }}"
                    value="{{ old('credit_limit', 0) }}" min="0" step="1000" placeholder="0">
                @error('credit_limit')<p class="form-error">{{ $message }}</p>@enderror
                <p class="form-hint">0 = tidak ada kredit (tunai saja). SR-15: pesanan baru diblokir jika melampaui limit.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Terms</label>
                <select name="payment_terms" class="form-select {{ $errors->has('payment_terms') ? 'is-error' : '' }}">
                    @foreach(['Tunai','Tunai / 7 hari','7 hari','14 hari','30 hari'] as $t)
                        <option value="{{ $t }}" {{ old('payment_terms', 'Tunai') == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                @error('payment_terms')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="opening-ar-group">
                <label class="form-label">Opening AR (Piutang Awal)</label>
                <input type="number" name="opening_ar" class="form-input" value="{{ old('opening_ar', 0) }}" min="0">
                <p class="form-hint">Saldo piutang awal sebelum sistem dipakai. Masukkan 0 jika tidak ada.</p>
            </div>

            <div class="form-group" id="opening-ap-group">
                <label class="form-label">Opening AP (Utang Awal)</label>
                <input type="number" name="opening_ap" class="form-input" value="{{ old('opening_ap', 0) }}" min="0">
                <p class="form-hint">Saldo utang awal ke vendor ini. Masukkan 0 jika tidak ada.</p>
            </div>
        </div>
    </div>
</div>

@if($canManageVendors)
{{-- Produk & Harga Beli Vendor (Pricelist) --}}
<div class="card mb-5" id="vendor-pricelist-section" style="display:none">
    <div class="card-header">
        <div>
            <span class="card-title">Produk &amp; Harga Beli Vendor (Pricelist)</span>
            <p style="font-size:11.5px;color:#8A8478;margin-top:2px">Daftarkan produk yang dijual vendor ini beserta harga tetapnya untuk pembuatan RFQ/PO.</p>
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
                @php $oldItems = old('vendor_items', [['variant_sku' => '', 'unit_purchase_price' => 0]]); @endphp
                @foreach($oldItems as $idx => $vi)
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
    <a href="{{ route('contacts.index') }}" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Kontak</button>
</div>
</form>
</div>

@push('scripts')
<script>
const vendorProducts = @json($products->map(fn($p) => ['variant_sku' => $p->variant_sku, 'name' => $p->name]));
let viCount = {{ count(old('vendor_items', [['variant_sku' => '']])) }};

function toggleCustomerVendorFields(type) {
    const isVendor = (type === 'Vendor');
    const ctGroup = document.getElementById('customer-type-group');
    if (ctGroup) ctGroup.style.display = isVendor ? 'none' : '';
    const clGroup = document.getElementById('credit-limit-group');
    if (clGroup) clGroup.style.display = isVendor ? 'none' : '';
    const arGroup = document.getElementById('opening-ar-group');
    if (arGroup) arGroup.style.display = isVendor ? 'none' : '';
    const apGroup = document.getElementById('opening-ap-group');
    if (apGroup) apGroup.style.display = isVendor ? '' : 'none';
    const vpSec = document.getElementById('vendor-pricelist-section');
    if (vpSec) vpSec.style.display = isVendor ? '' : 'none';
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

// Run on load for old() values
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.querySelector('[name="contact_type"]');
    if (sel && sel.value) toggleCustomerVendorFields(sel.value);
});
</script>
@endpush

@endsection
