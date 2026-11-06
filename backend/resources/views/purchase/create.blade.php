@extends('layouts.app')

@section('title', 'Buat RFQ')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.index') }}">Pembelian</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Buat RFQ</span>
@endsection

@section('content')
<div style="max-width:900px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Buat RFQ Baru</h1>
            <p class="page-desc">Request for Quotation → akan menjadi PO saat dikonfirmasi · F-12 · SR-18</p>
        </div>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </div>

    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-left:3px solid #D97706;border-radius:8px;padding:10px 16px;margin-bottom:20px;font-size:12.5px;color:#78350F">
        <strong>SR-18:</strong> Setelah PO dikonfirmasi, harga unit dan kuantitas terkunci. Perubahan memerlukan dokumen revisi dan tercatat di Audit Log.
    </div>

    @if($errors->any())
    <div class="flash-error" style="margin-bottom:20px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/></svg>
        <div>
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin:4px 0 0 16px;padding:0">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('purchase.store') }}" id="rfq-form">
        @csrf

        {{-- Header --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Header RFQ</span>
                <span class="badge b-purple">Status: RFQ / Draft</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Vendor / Pemasok <span style="color:#DC2626">*</span></label>
                    <select name="vendor_id" id="sel-vendor" class="form-select {{ $errors->has('vendor_id') ? 'is-error' : '' }}" required onchange="prefillPrices()">
                        <option value="">Pilih vendor...</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->contact_id }}" {{ old('vendor_id') == $v->contact_id ? 'selected' : '' }}>
                                {{ $v->contact_id }} — {{ $v->name }} ({{ $v->city }})
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Order <span style="color:#DC2626">*</span></label>
                    <input type="date" name="order_date" class="form-input {{ $errors->has('order_date') ? 'is-error' : '' }}"
                        value="{{ old('order_date', date('Y-m-d')) }}" required>
                    @error('order_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Terms <span style="color:#DC2626">*</span></label>
                    <select name="payment_terms" class="form-select {{ $errors->has('payment_terms') ? 'is-error' : '' }}" required>
                        <option value="">Pilih termin...</option>
                        @foreach(['7 hari','14 hari','30 hari','Tunai'] as $t)
                            <option value="{{ $t }}" {{ old('payment_terms') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('payment_terms')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Catatan pembelian...">{{ old('notes') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Lines --}}
        <div class="card" style="margin-top:16px">
            <div class="card-header">
                <span class="card-title">Baris Produk</span>
                <button type="button" class="btn btn-outline btn-sm" onclick="addLine()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Baris
                </button>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table" id="lines-table">
                    <thead>
                        <tr>
                            <th style="width:35%">Produk (SKU)</th>
                            <th style="text-align:right;width:12%">Qty</th>
                            <th style="text-align:right;width:18%">Harga Unit (Rp)</th>
                            <th style="text-align:right;width:18%">Subtotal</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @php $oldLines = old('lines', [[]]); @endphp
                        @foreach($oldLines as $i => $ol)
                        <tr id="line-row-{{ $i }}" data-idx="{{ $i }}">
                            <td>
                                <select name="lines[{{ $i }}][variant_sku]" class="form-select sku-sel" data-idx="{{ $i }}" onchange="onSkuChange({{ $i }})">
                                    <option value="">Pilih produk...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->variant_sku }}"
                                            data-std="{{ $p->standard_cost }}"
                                            {{ ($ol['variant_sku'] ?? '') == $p->variant_sku ? 'selected' : '' }}>
                                            {{ $p->variant_sku }} — {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="lines[{{ $i }}][qty]" class="form-input qty-inp" data-idx="{{ $i }}"
                                    value="{{ $ol['qty'] ?? 1 }}" min="1" step="1" oninput="recalcLine({{ $i }})" style="text-align:right">
                            </td>
                            <td>
                                <div style="position:relative">
                                    <span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:11px">Rp</span>
                                    <input type="number" name="lines[{{ $i }}][unit_price]" class="form-input price-inp" data-idx="{{ $i }}"
                                        value="{{ $ol['unit_price'] ?? 0 }}" min="0" step="100" oninput="recalcLine({{ $i }})" style="padding-left:24px;text-align:right">
                                </div>
                            </td>
                            <td style="text-align:right;font-weight:700" id="sub-{{ $i }}">—</td>
                            <td style="text-align:center">
                                <button type="button" class="btn btn-ghost btn-xs" onclick="removeLine({{ $i }})" style="color:#DC2626" title="Hapus baris">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body" style="border-top:1px solid #F0EEE8;display:flex;justify-content:flex-end">
                <div style="text-align:right">
                    <div style="font-size:12px;color:#8A8478;margin-bottom:4px">Total PO</div>
                    <div style="font-size:22px;font-weight:700;color:#18382E" id="grand-total">Rp 0</div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
            <a href="{{ route('purchase.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan RFQ
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
// Vendor pricelist data for auto-fill
const pricelist = @json(
    \App\Models\VendorPricelist::all()
        ->map(fn($pl) => [
            'vendor_id'          => $pl->vendor_id,
            'variant_sku'        => $pl->variant_sku,
            'unit_purchase_price'=> (float) $pl->unit_purchase_price,
        ])
);

let lineCount = {{ count(old('lines', [[]])) }};

function fmt(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function recalcLine(idx) {
    const qty   = parseFloat(document.querySelector(`.qty-inp[data-idx="${idx}"]`)?.value) || 0;
    const price = parseFloat(document.querySelector(`.price-inp[data-idx="${idx}"]`)?.value) || 0;
    const sub   = document.getElementById(`sub-${idx}`);
    if (sub) sub.textContent = fmt(qty * price);
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('#lines-body tr').forEach(row => {
        const idx   = row.dataset.idx;
        const qty   = parseFloat(document.querySelector(`.qty-inp[data-idx="${idx}"]`)?.value) || 0;
        const price = parseFloat(document.querySelector(`.price-inp[data-idx="${idx}"]`)?.value) || 0;
        total += qty * price;
    });
    document.getElementById('grand-total').textContent = fmt(total);
}

function onSkuChange(idx) {
    const vendorId = document.getElementById('sel-vendor').value;
    const sku      = document.querySelector(`.sku-sel[data-idx="${idx}"]`)?.value;
    if (!sku) return;
    // Look up pricelist for this vendor+sku
    const pl = pricelist.find(p => p.vendor_id === vendorId && p.variant_sku === sku);
    const priceInp = document.querySelector(`.price-inp[data-idx="${idx}"]`);
    if (pl && priceInp) {
        priceInp.value = pl.unit_purchase_price;
    }
    recalcLine(idx);
}

function prefillPrices() {
    document.querySelectorAll('#lines-body tr').forEach(row => {
        onSkuChange(row.dataset.idx);
    });
}

function addLine() {
    const idx  = lineCount++;
    const body = document.getElementById('lines-body');
    const products = @json($products->map(fn($p) => ['variant_sku' => $p->variant_sku, 'name' => $p->name]));
    const opts = products.map(p =>
        `<option value="${p.variant_sku}">${p.variant_sku} — ${p.name}</option>`
    ).join('');

    const tr = document.createElement('tr');
    tr.id = `line-row-${idx}`;
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td>
            <select name="lines[${idx}][variant_sku]" class="form-select sku-sel" data-idx="${idx}" onchange="onSkuChange(${idx})">
                <option value="">Pilih produk...</option>${opts}
            </select>
        </td>
        <td>
            <input type="number" name="lines[${idx}][qty]" class="form-input qty-inp" data-idx="${idx}"
                value="1" min="1" step="1" oninput="recalcLine(${idx})" style="text-align:right">
        </td>
        <td>
            <div style="position:relative">
                <span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:11px">Rp</span>
                <input type="number" name="lines[${idx}][unit_price]" class="form-input price-inp" data-idx="${idx}"
                    value="0" min="0" step="100" oninput="recalcLine(${idx})" style="padding-left:24px;text-align:right">
            </div>
        </td>
        <td style="text-align:right;font-weight:700" id="sub-${idx}">Rp 0</td>
        <td style="text-align:center">
            <button type="button" class="btn btn-ghost btn-xs" onclick="removeLine(${idx})" style="color:#DC2626" title="Hapus">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
        </td>`;
    body.appendChild(tr);
}

function removeLine(idx) {
    const row = document.getElementById(`line-row-${idx}`);
    if (row && document.querySelectorAll('#lines-body tr').length > 1) {
        row.remove();
        recalcTotal();
    }
}

// Init recalc on load
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#lines-body tr').forEach(row => recalcLine(row.dataset.idx));
});
</script>
@endpush
@endsection
