@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('products.index') }}">Menu & Produk</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Tambah Produk</span>
@endsection

@section('content')
<div style="max-width:760px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Produk Baru</h1>
            <p class="page-desc">Daftarkan SKU menu baru ke katalog Maison Aster · F-03</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="flash-error" style="margin-bottom:20px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin:4px 0 0 16px;padding:0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        <div class="card">
            <div class="card-header">
                <span class="card-title">Identitas Produk</span>
                <span class="badge b-forest">F-03 · SR-09, SR-10</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">SKU / Internal Reference <span style="color:#DC2626">*</span></label>
                    <input type="text" name="variant_sku" class="form-input {{ $errors->has('variant_sku') ? 'is-error' : '' }}"
                        value="{{ old('variant_sku') }}" placeholder="cth: FD-016 atau BV-016" required>
                    <div class="form-hint">Gunakan prefix FD- untuk makanan, BV- untuk minuman. Tidak dapat diubah setelah disimpan.</div>
                    @error('variant_sku')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Nama Produk <span style="color:#DC2626">*</span></label>
                    <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                        value="{{ old('name') }}" placeholder="cth: Grilled Salmon" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Ukuran (Size)</label>
                    <input type="text" name="size" class="form-input"
                        value="{{ old('size') }}" placeholder="cth: 200 g, 330 ml">
                    @error('size')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Warna / Varian</label>
                    <input type="text" name="color" class="form-input"
                        value="{{ old('color') }}" placeholder="cth: Original, Medium, Dark">
                    @error('color')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Satuan (Unit) <span style="color:#DC2626">*</span></label>
                    <select name="unit" class="form-select {{ $errors->has('unit') ? 'is-error' : '' }}" required>
                        <option value="">Pilih satuan...</option>
                        @foreach(['porsi','botol','gelas','cangkir','buah','liter','kg'] as $u)
                            <option value="{{ $u }}" {{ old('unit') == $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                    @error('unit')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Vendor Default</label>
                    <select name="default_vendor_id" class="form-select">
                        <option value="">— Tidak ada —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->contact_id }}" {{ old('default_vendor_id') == $v->contact_id ? 'selected' : '' }}>
                                {{ $v->contact_id }} · {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_vendor_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-header">
                <span class="card-title">Harga & Biaya</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                <div class="form-group">
                    <label class="form-label">Harga Jual (excl. PBJT) <span style="color:#DC2626">*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:13px">Rp</span>
                        <input type="number" name="sales_price" class="form-input {{ $errors->has('sales_price') ? 'is-error' : '' }}"
                            value="{{ old('sales_price') }}" placeholder="0" min="0" step="1000" required
                            style="padding-left:30px" id="inp-sales-price">
                    </div>
                    <div class="form-hint" id="hint-with-tax">+PBJT 10%: <strong id="price-with-tax">—</strong></div>
                    @error('sales_price')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Standard Cost <span style="color:#DC2626">*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:13px">Rp</span>
                        <input type="number" name="standard_cost" class="form-input {{ $errors->has('standard_cost') ? 'is-error' : '' }}"
                            value="{{ old('standard_cost') }}" placeholder="0" min="0" step="1000" required
                            style="padding-left:30px" id="inp-std-cost">
                    </div>
                    <div class="form-hint" id="hint-margin">Gross margin: <strong id="margin-pct">—</strong></div>
                    @error('standard_cost')<div class="form-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
            <a href="{{ route('products.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Produk
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function updateCalc() {
    const sp  = parseFloat(document.getElementById('inp-sales-price').value) || 0;
    const sc  = parseFloat(document.getElementById('inp-std-cost').value) || 0;
    const tax = Math.round(sp * 0.10);
    document.getElementById('price-with-tax').textContent = sp > 0
        ? 'Rp' + (sp + tax).toLocaleString('id-ID') : '—';
    const margin = sp > 0 ? (((sp - sc) / sp) * 100).toFixed(1) : null;
    document.getElementById('margin-pct').textContent = margin !== null
        ? margin + '% (Rp' + (sp - sc).toLocaleString('id-ID') + ')' : '—';
}
document.getElementById('inp-sales-price').addEventListener('input', updateCalc);
document.getElementById('inp-std-cost').addEventListener('input', updateCalc);
</script>
@endpush
@endsection
