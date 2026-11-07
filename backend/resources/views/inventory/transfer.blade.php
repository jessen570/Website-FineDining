@extends('layouts.app')

@section('title', 'Transfer Stok Antar Lokasi')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Persediaan</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Transfer Stok</span>
@endsection

@section('content')
<div style="max-width:720px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Transfer Stok Antar Lokasi</h1>
            <p class="page-desc">Mutasi stok persediaan KITCH-JKT ↔ BAR-JKT</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </div>

    {{-- Business Rules Banner --}}
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-left:3px solid #D97706;border-radius:8px;padding:12px 16px;margin-bottom:20px">
        <div style="font-weight:700;font-size:12.5px;color:#78350F;margin-bottom:6px">Ketentuan Transfer</div>
        <div style="display:flex;flex-direction:column;gap:5px">
            <div style="font-size:12px;color:#92400E">
                • Transfer akan ditolak jika jumlah melebihi stok yang tersedia (Available) di lokasi asal.
            </div>
            <div style="font-size:12px;color:#92400E">
                • Total stok perusahaan tidak berubah — stok berkurang di asal dan bertambah di tujuan dengan jumlah yang sama.
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="flash-error" style="margin-bottom:20px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <strong>Transfer gagal:</strong>
            <ul style="margin:4px 0 0 16px;padding:0">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('inventory.transfer.store') }}" id="transfer-form">
        @csrf

        <div class="card">
            <div class="card-header">
                <span class="card-title">Detail Transfer</span>
                <span class="badge b-forest">F-20 · Stock Move</span>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px">

                {{-- From / To Locations --}}
                <div style="display:grid;grid-template-columns:1fr 40px 1fr;gap:12px;align-items:end">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Dari Lokasi <span style="color:#DC2626">*</span></label>
                        <select name="from_location" id="sel-from" class="form-select {{ $errors->has('from_location') ? 'is-error' : '' }}" required onchange="updateStockInfo()">
                            <option value="">Pilih lokasi asal...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->location_code }}"
                                    {{ (old('from_location', request('from')) == $loc->location_code) ? 'selected' : '' }}>
                                    {{ $loc->location_code }} — {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_location')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;align-items:center;justify-content:center;padding-bottom:2px">
                        <div style="width:32px;height:32px;border-radius:50%;background:#F0EEE8;border:1px solid #DDD8CC;display:flex;align-items:center;justify-content:center;cursor:pointer" onclick="swapLocations()" title="Tukar asal dan tujuan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B6560" stroke-width="2.5"><path d="m17 4 4 4-4 4"/><path d="M3 8h18"/><path d="m7 20-4-4 4-4"/><path d="M21 16H3"/></svg>
                        </div>
                    </div>

                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ke Lokasi <span style="color:#DC2626">*</span></label>
                        <select name="to_location" id="sel-to" class="form-select {{ $errors->has('to_location') ? 'is-error' : '' }}" required>
                            <option value="">Pilih lokasi tujuan...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->location_code }}"
                                    {{ old('to_location') == $loc->location_code ? 'selected' : '' }}>
                                    {{ $loc->location_code }} — {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_location')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- SKU --}}
                <div class="form-group" style="margin:0">
                    <label class="form-label">Produk (SKU) <span style="color:#DC2626">*</span></label>
                    <select name="variant_sku" id="sel-sku" class="form-select {{ $errors->has('variant_sku') ? 'is-error' : '' }}" required onchange="updateStockInfo()">
                        <option value="">Pilih produk...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->variant_sku }}"
                                {{ (old('variant_sku', request('sku')) == $p->variant_sku) ? 'selected' : '' }}>
                                {{ $p->variant_sku }} — {{ $p->name }} ({{ $p->size }}/{{ $p->color }})
                            </option>
                        @endforeach
                    </select>
                    @error('variant_sku')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                {{-- Stock Info Panel (live) --}}
                <div id="stock-info" style="background:#F7F5F0;border:1px solid #E8E4DA;border-radius:8px;padding:14px;display:none">
                    <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#8A8478;margin-bottom:10px">
                        Stok Saat Ini di Lokasi Asal
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;margin-bottom:4px">On Hand</div>
                            <div style="font-size:20px;font-weight:700;color:#1E1C1A" id="info-onhand">—</div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;margin-bottom:4px">Reserved</div>
                            <div style="font-size:20px;font-weight:700;color:#D97706" id="info-reserved">—</div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;margin-bottom:4px">Available (Max)</div>
                            <div style="font-size:20px;font-weight:700;color:#059669" id="info-available">—</div>
                        </div>
                    </div>
                </div>

                {{-- Quantity --}}
                <div class="form-group" style="margin:0">
                    <label class="form-label">Jumlah Transfer <span style="color:#DC2626">*</span></label>
                    <input type="number" name="quantity" id="inp-qty"
                        class="form-input {{ $errors->has('quantity') ? 'is-error' : '' }}"
                        value="{{ old('quantity') }}" min="1" step="1" placeholder="0" required
                        oninput="checkQty()">
                    <div class="form-hint" id="qty-hint">Masukkan jumlah unit yang akan dipindah.</div>
                    @error('quantity')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                {{-- Warning if over available --}}
                <div id="qty-warning" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-left:3px solid #EF4444;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#991B1B">
                    <strong>Peringatan:</strong> Jumlah melebihi stok yang tersedia di lokasi asal. Transfer akan ditolak oleh sistem.
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Alasan transfer, catatan operasional...">{{ old('notes') }}</textarea>
                </div>

            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary" id="btn-submit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 9 7-7 7 7"/><path d="m5 15 7 7 7-7"/></svg>
                Proses Transfer
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
// Stock data from backend (passed from InventoryController@transferForm)
const stockData = {!! json_encode($stockData ?? []) !!};

function getStock(loc, sku) {
    return stockData.find(s => s.location_code === loc && s.variant_sku === sku) || null;
}

function updateStockInfo() {
    const loc = document.getElementById('sel-from').value;
    const sku = document.getElementById('sel-sku').value;
    const panel = document.getElementById('stock-info');
    if (!loc || !sku) { panel.style.display = 'none'; return; }
    const s = getStock(loc, sku);
    if (!s) {
        document.getElementById('info-onhand').textContent = '0';
        document.getElementById('info-reserved').textContent = '0';
        document.getElementById('info-available').textContent = '0';
        panel.style.display = 'block';
    } else {
        document.getElementById('info-onhand').textContent = s.on_hand;
        document.getElementById('info-reserved').textContent = s.reserved;
        document.getElementById('info-available').textContent = s.available;
        panel.style.display = 'block';
    }
    checkQty();
}

function checkQty() {
    const loc = document.getElementById('sel-from').value;
    const sku = document.getElementById('sel-sku').value;
    const qty = parseFloat(document.getElementById('inp-qty').value) || 0;
    const warn = document.getElementById('qty-warning');
    const hint = document.getElementById('qty-hint');
    const s = getStock(loc, sku);
    const avail = s ? s.available : 0;
    if (qty > 0 && s && qty > avail) {
        warn.style.display = 'block';
        hint.textContent = `Maksimal transfer: ${avail} unit`;
    } else {
        warn.style.display = 'none';
        hint.textContent = s ? `Stok tersedia di lokasi asal: ${avail} unit` : 'Masukkan jumlah unit yang akan dipindah.';
    }
}

function swapLocations() {
    const from = document.getElementById('sel-from');
    const to   = document.getElementById('sel-to');
    const tmp  = from.value;
    from.value = to.value;
    to.value   = tmp;
    updateStockInfo();
}

// Auto-populate if query params exist
window.addEventListener('DOMContentLoaded', () => {
    updateStockInfo();
});
</script>
@endpush
@endsection
