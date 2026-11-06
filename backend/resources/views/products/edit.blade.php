@extends('layouts.app')

@section('title', 'Edit ' . $product->name)

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('products.index') }}">Menu & Produk</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('products.show', $product->variant_sku) }}">{{ $product->variant_sku }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Edit</span>
@endsection

@section('content')
<div style="max-width:760px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Produk — {{ $product->name }}</h1>
            <p class="page-desc">SKU {{ $product->variant_sku }} tidak dapat diubah · F-03, SR-24</p>
        </div>
        <a href="{{ route('products.show', $product->variant_sku) }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </div>

    {{-- SR-24: SKU immutable notice --}}
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-left:3px solid #D97706;border-radius:8px;padding:10px 14px;margin-bottom:20px;font-size:12.5px;color:#78350F">
        <strong>SR-24:</strong> SKU <code style="background:#FEF3C7;padding:1px 5px;border-radius:3px">{{ $product->variant_sku }}</code> dikunci dan tidak dapat diubah. Perubahan harga atau biaya akan tercatat di Audit Log.
    </div>

    @if($errors->any())
    <div class="flash-error" style="margin-bottom:20px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin:4px 0 0 16px;padding:0">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('products.update', $product->variant_sku) }}">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header">
                <span class="card-title">Identitas Produk</span>
                <span class="badge b-gray text-mono">{{ $product->variant_sku }}</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                {{-- SKU: read-only, SR-24 --}}
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">SKU / Internal Reference</label>
                    <input type="text" class="form-input" value="{{ $product->variant_sku }}" disabled
                        style="background:#F7F5F0;color:#8A8478;cursor:not-allowed">
                    <div class="form-hint">SKU dikunci — tidak dapat diubah setelah disimpan (SR-24).</div>
                </div>

                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Nama Produk <span style="color:#DC2626">*</span></label>
                    <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                        value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Ukuran (Size)</label>
                    <input type="text" name="size" class="form-input"
                        value="{{ old('size', $product->size) }}" placeholder="cth: 200 g">
                </div>

                <div class="form-group">
                    <label class="form-label">Warna / Varian</label>
                    <input type="text" name="color" class="form-input"
                        value="{{ old('color', $product->color) }}" placeholder="cth: Original">
                </div>

                <div class="form-group">
                    <label class="form-label">Satuan <span style="color:#DC2626">*</span></label>
                    <select name="unit" class="form-select" required>
                        @foreach(['porsi','botol','gelas','cangkir','buah','liter','kg'] as $u)
                            <option value="{{ $u }}" {{ old('unit', $product->unit) == $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Vendor Default</label>
                    <select name="default_vendor_id" class="form-select">
                        <option value="">— Tidak ada —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->contact_id }}"
                                {{ old('default_vendor_id', $product->default_vendor_id) == $v->contact_id ? 'selected' : '' }}>
                                {{ $v->contact_id }} · {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-header">
                <span class="card-title">Harga & Biaya</span>
                <span style="font-size:11.5px;color:#8A8478">Perubahan tercatat di Audit Log · SR-24</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                <div class="form-group">
                    <label class="form-label">Harga Jual (excl. PBJT) <span style="color:#DC2626">*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:13px">Rp</span>
                        <input type="number" name="sales_price" id="inp-sales-price"
                            class="form-input {{ $errors->has('sales_price') ? 'is-error' : '' }}"
                            value="{{ old('sales_price', $product->sales_price) }}" min="0" step="1000" required
                            style="padding-left:30px">
                    </div>
                    <div class="form-hint">Saat ini: Rp{{ number_format($product->sales_price, 0, ',', '.') }} · +PBJT: <span id="price-with-tax">Rp{{ number_format($product->sales_price * 1.10, 0, ',', '.') }}</span></div>
                    @error('sales_price')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Standard Cost <span style="color:#DC2626">*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8A8478;font-size:13px">Rp</span>
                        <input type="number" name="standard_cost" id="inp-std-cost"
                            class="form-input {{ $errors->has('standard_cost') ? 'is-error' : '' }}"
                            value="{{ old('standard_cost', $product->standard_cost) }}" min="0" step="1000" required
                            style="padding-left:30px">
                    </div>
                    <div class="form-hint">Saat ini: Rp{{ number_format($product->standard_cost, 0, ',', '.') }} · Margin: <span id="margin-pct">{{ round((($product->sales_price - $product->standard_cost) / max($product->sales_price,1)) * 100, 1) }}%</span></div>
                    @error('standard_cost')<div class="form-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
            <a href="{{ route('products.show', $product->variant_sku) }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function updateCalc() {
    const sp  = parseFloat(document.getElementById('inp-sales-price').value) || 0;
    const sc  = parseFloat(document.getElementById('inp-std-cost').value) || 0;
    document.getElementById('price-with-tax').textContent =
        'Rp' + Math.round(sp * 1.10).toLocaleString('id-ID');
    const m = sp > 0 ? (((sp - sc) / sp) * 100).toFixed(1) : '0.0';
    document.getElementById('margin-pct').textContent = m + '%';
}
document.getElementById('inp-sales-price').addEventListener('input', updateCalc);
document.getElementById('inp-std-cost').addEventListener('input', updateCalc);
</script>
@endpush
@endsection
