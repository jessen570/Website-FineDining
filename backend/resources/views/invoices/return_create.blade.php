@extends('layouts.app')
@section('title', 'Buat Retur Penjualan — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('returns.index') }}" class="breadcrumb-item">Retur & Credit Note</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Buat Retur</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Buat Retur Penjualan Baru</h1>
            <p class="page-desc">F-19 · T-33 · SR-17 — Return membalik stok, HPP, dan piutang secara otomatis</p>
        </div>
        <a href="{{ route('returns.index') }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </a>
    </div>

    {{-- SR-17 Info --}}
    <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <strong>SR-17:</strong> Return hanya dapat dilakukan terhadap <strong>Customer Invoice yang sudah Posted</strong>.
            Setiap kombinasi invoice + produk hanya dapat diretur <strong>satu kali</strong>. Duplikasi retur akan ditolak sistem.
            Validasi return akan menaikkan stok, membalik COGS, dan menerbitkan Credit Note secara otomatis.
        </div>
    </div>

    {{-- Flash --}}
    @if(session('error'))
        <div class="flash-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Form --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Detail Retur</span>
        </div>
        <div class="card-body">
            <form action="{{ route('returns.store') }}" method="POST" id="returnForm">
                @csrf
                <div class="space-y-4">

                    {{-- Invoice Selection --}}
                    <div class="form-group">
                        <label class="form-label">Invoice Asal <span class="text-red-500">*</span></label>
                        <select name="original_invoice_id" id="invoiceSelect" class="form-select @error('original_invoice_id') is-error @enderror" required onchange="updateInvoiceDetail(this)">
                            <option value="">— Pilih Customer Invoice (Posted) —</option>
                            @foreach($invoices as $inv)
                                <option value="{{ $inv->invoice_id }}"
                                    data-total="{{ $inv->total_amount }}"
                                    data-subtotal="{{ $inv->subtotal }}"
                                    data-pbjt="{{ $inv->pbjt_amount }}"
                                    data-partner="{{ $inv->partner?->name ?? $inv->partner_id }}"
                                    {{ old('original_invoice_id') == $inv->invoice_id ? 'selected' : '' }}>
                                    {{ $inv->invoice_id }} — {{ $inv->partner?->name ?? $inv->partner_id }} — Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-hint">Hanya Customer Invoice berstatus Posted yang dapat diretur</p>
                        @error('original_invoice_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Invoice Detail Preview --}}
                    <div id="invoiceDetail" class="hidden bg-stone-50 border border-stone-200 rounded-lg p-3 text-sm space-y-1">
                        <div class="flex justify-between"><span class="text-stone-500">Pelanggan:</span><span class="font-medium" id="detPartner">—</span></div>
                        <div class="flex justify-between"><span class="text-stone-500">Total Invoice:</span><span class="font-mono font-semibold" id="detTotal">—</span></div>
                        <div class="flex justify-between"><span class="text-stone-500">Subtotal:</span><span class="font-mono" id="detSubtotal">—</span></div>
                        <div class="flex justify-between"><span class="text-stone-500">PBJT (10%):</span><span class="font-mono text-[#C6A76B]" id="detPbjt">—</span></div>
                    </div>

                    {{-- SKU --}}
                    <div class="form-group">
                        <label class="form-label">SKU Produk yang Diretur <span class="text-red-500">*</span></label>
                        <input type="text"
                               name="variant_sku"
                               class="form-input @error('variant_sku') is-error @enderror"
                               placeholder="Contoh: FD-001, BV-011"
                               value="{{ old('variant_sku') }}"
                               required
                               style="text-transform:uppercase"
                               oninput="this.value=this.value.toUpperCase();updateCalc()">
                        <p class="form-hint">Masukkan kode SKU sesuai produk pada invoice</p>
                        @error('variant_sku')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Quantity --}}
                    <div class="form-group">
                        <label class="form-label">Kuantitas Retur <span class="text-red-500">*</span></label>
                        <input type="number"
                               name="quantity"
                               id="qtyInput"
                               class="form-input @error('quantity') is-error @enderror"
                               placeholder="Masukkan jumlah unit yang diretur"
                               value="{{ old('quantity', 1) }}"
                               min="1"
                               step="1"
                               required
                               oninput="updateCalc()">
                        @error('quantity')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Reason --}}
                    <div class="form-group">
                        <label class="form-label">Alasan Retur <span class="text-red-500">*</span></label>
                        <textarea name="reason"
                                  class="form-input form-textarea @error('reason') is-error @enderror"
                                  placeholder="Jelaskan alasan retur (contoh: produk rusak, salah kirim, tidak sesuai pesanan)"
                                  required>{{ old('reason') }}</textarea>
                        @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Credit Note Preview --}}
                    <div class="bg-rose-50 border border-rose-200 rounded-lg p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-3 flex items-center gap-2">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            Pratinjau Credit Note yang Akan Diterbitkan
                        </div>
                        <div class="space-y-2 text-sm" id="cnPreview">
                            <div class="flex justify-between text-stone-500">
                                <span>Credit Subtotal (qty × harga satuan):</span>
                                <span class="font-mono" id="cnSub">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-stone-500">
                                <span>PBJT Reversal (10%):</span>
                                <span class="font-mono text-[#C6A76B]" id="cnPbjt">Rp 0</span>
                            </div>
                            <div class="flex justify-between font-bold text-rose-700 border-t border-rose-200 pt-2">
                                <span>Total Credit Note:</span>
                                <span class="font-mono" id="cnTotal">Rp 0</span>
                            </div>
                            <div class="text-xs text-stone-400 mt-1">* Kalkulasi bersifat estimasi. Harga satuan final mengikuti baris invoice asal.</div>
                        </div>
                    </div>

                    {{-- What happens next --}}
                    <div class="bg-[#18382E]/5 border border-[#18382E]/20 rounded-lg p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#18382E] mb-2">Yang Terjadi Setelah Validasi:</div>
                        <ul class="text-xs text-stone-600 space-y-1.5">
                            <li class="flex items-start gap-2"><span class="text-emerald-600 mt-0.5">✓</span> Stok produk di lokasi asal akan <strong>bertambah</strong> sejumlah quantity retur</li>
                            <li class="flex items-start gap-2"><span class="text-emerald-600 mt-0.5">✓</span> Cost of Goods Sold (COGS) akan <strong>dibalik</strong> senilai qty × standard_cost</li>
                            <li class="flex items-start gap-2"><span class="text-emerald-600 mt-0.5">✓</span> Piutang Usaha (AR) akan <strong>dikurangi</strong> sebesar total Credit Note</li>
                            <li class="flex items-start gap-2"><span class="text-emerald-600 mt-0.5">✓</span> Credit Note otomatis diterbitkan dan jurnal reversalnya diposting</li>
                            <li class="flex items-start gap-2"><span class="text-red-500 mt-0.5">✗</span> Retur yang sudah divalidasi <strong>tidak dapat diulang</strong> (SR-17, SR-20)</li>
                        </ul>
                    </div>

                </div>

                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-stone-100">
                    <button type="submit" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                        Buat Retur
                    </button>
                    <a href="{{ route('returns.index') }}" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function updateInvoiceDetail(sel) {
    const opt = sel.options[sel.selectedIndex];
    const detail = document.getElementById('invoiceDetail');
    if (!opt.value) { detail.classList.add('hidden'); return; }
    detail.classList.remove('hidden');
    document.getElementById('detPartner').textContent  = opt.dataset.partner || '—';
    document.getElementById('detTotal').textContent    = 'Rp ' + parseInt(opt.dataset.total || 0).toLocaleString('id-ID');
    document.getElementById('detSubtotal').textContent = 'Rp ' + parseInt(opt.dataset.subtotal || 0).toLocaleString('id-ID');
    document.getElementById('detPbjt').textContent     = 'Rp ' + parseInt(opt.dataset.pbjt || 0).toLocaleString('id-ID');
    updateCalc();
}
function updateCalc() {
    const qty  = parseFloat(document.getElementById('qtyInput')?.value) || 0;
    const inv  = document.getElementById('invoiceSelect');
    const opt  = inv?.options[inv.selectedIndex];
    if (!opt?.value || qty <= 0) {
        ['cnSub','cnPbjt','cnTotal'].forEach(id => { const el=document.getElementById(id); if(el) el.textContent='Rp 0'; });
        return;
    }
    const subPerQty = parseFloat(opt.dataset.subtotal||0);
    const sub  = Math.round(subPerQty);  // This is approximate
    const pbjt = Math.round(sub * 0.10);
    const total = sub + pbjt;
    document.getElementById('cnSub').textContent   = 'Rp ' + sub.toLocaleString('id-ID');
    document.getElementById('cnPbjt').textContent  = 'Rp ' + pbjt.toLocaleString('id-ID');
    document.getElementById('cnTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
// Init on load
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('invoiceSelect');
    if (sel?.value) updateInvoiceDetail(sel);
});
</script>
@endsection
