@extends('layouts.app')

@section('title', 'Terima Barang — ' . $po->po_id)

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.index') }}">Pembelian</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.show', $po->po_id) }}">{{ $po->po_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Terima Barang</span>
@endsection

@section('content')
<div style="max-width:860px">

    <div class="page-header">
        <div>
            <h1 class="page-title">Terima Barang</h1>
            <p class="page-desc">{{ $po->po_id }} · {{ $po->vendor?->name }} · F-13 · SR-06, SR-09</p>
        </div>
        <a href="{{ route('purchase.show', $po->po_id) }}" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke PO
        </a>
    </div>

    {{-- Business Rules Banner --}}
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-left:3px solid #D97706;border-radius:8px;padding:12px 16px;margin-bottom:20px">
        <div style="font-weight:700;font-size:12.5px;color:#78350F;margin-bottom:8px">Aturan Penerimaan Barang</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div style="font-size:12px;color:#92400E">
                <span class="badge b-amber" style="margin-right:6px">SR-06</span>
                Hanya Accepted Qty yang menambah On Hand. Rejected tidak masuk stok.
            </div>
            <div style="font-size:12px;color:#92400E">
                <span class="badge b-amber" style="margin-right:6px">SR-09</span>
                Stok negatif tidak diizinkan. Accepted ≤ Received.
            </div>
        </div>
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

    <form method="POST" action="{{ route('purchase.receipt.store', $po->po_id) }}" id="receipt-form">
        @csrf

        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title">Detail Penerimaan</span>
                    <div style="font-size:11px;color:#8A8478;margin-top:2px">Pisahkan barang yang diterima dengan baik (Accepted) dan yang ditolak (Rejected)</div>
                </div>
                <span class="badge b-forest">F-13 · Three-Way Match</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Tanggal Penerimaan <span style="color:#DC2626">*</span></label>
                    <input type="date" name="receipt_date" class="form-input {{ $errors->has('receipt_date') ? 'is-error' : '' }}"
                        value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                    @error('receipt_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Referensi PO</label>
                    <input type="text" class="form-input text-mono" value="{{ $po->po_id }}" disabled
                        style="background:#F7F5F0;color:#18382E;font-weight:700">
                </div>
            </div>
        </div>

        {{-- Lines --}}
        <div class="card" style="margin-top:16px">
            <div class="card-header">
                <span class="card-title">Baris Penerimaan</span>
                <span style="font-size:12px;color:#8A8478">Accepted + Rejected = Received</span>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Produk</th>
                            <th style="text-align:right;background:#EFF6FF">Qty Dipesan</th>
                            <th style="text-align:right">Qty Diterima</th>
                            <th style="text-align:right;background:#F0FDF4">✓ Accepted</th>
                            <th style="text-align:right;background:#FEF2F2">✗ Rejected</th>
                            <th style="text-align:right">Harga PO</th>
                            <th style="text-align:right;background:#FFFBEB">Valuation</th>
                            <th>Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->lines as $i => $line)
                        @php
                            $loc = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                            $currStock = \App\Models\StockLevel::where('variant_sku', $line->variant_sku)
                                ->where('location_code', $loc)->value('on_hand') ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][variant_sku]" value="{{ $line->variant_sku }}">
                                <span class="text-mono badge b-gray" style="font-size:11px">{{ $line->variant_sku }}</span>
                            </td>
                            <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                            <td style="text-align:right;background:#F0F9FF;font-weight:700;color:#1D4ED8">
                                {{ number_format($line->ordered_qty, 0) }}
                            </td>
                            <td style="text-align:right">
                                <input type="number" name="lines[{{ $i }}][received_qty]"
                                    id="recv-{{ $i }}"
                                    class="form-input" style="width:80px;padding:6px 8px;text-align:right"
                                    value="{{ old("lines.{$i}.received_qty", $line->ordered_qty) }}"
                                    min="0" max="{{ $line->ordered_qty }}" step="1"
                                    oninput="syncRejected({{ $i }}, {{ $line->ordered_qty }})">
                            </td>
                            <td style="text-align:right;background:#F0FDF4">
                                <input type="number" name="lines[{{ $i }}][accepted_qty]"
                                    id="acc-{{ $i }}"
                                    class="form-input" style="width:80px;padding:6px 8px;text-align:right;border-color:#A7F3D0"
                                    value="{{ old("lines.{$i}.accepted_qty", $line->ordered_qty) }}"
                                    min="0" step="1"
                                    oninput="syncRejected({{ $i }}, {{ $line->ordered_qty }}, true)">
                            </td>
                            <td style="text-align:right;background:#FEF2F2">
                                <span id="rej-{{ $i }}" style="font-weight:700;color:#DC2626;font-size:14px">0</span>
                            </td>
                            <td style="text-align:right;color:#6B6560">
                                Rp{{ number_format($line->unit_purchase_price, 0, ',', '.') }}
                            </td>
                            <td style="text-align:right;background:#FFFBEB;font-weight:700" id="val-{{ $i }}">
                                Rp{{ number_format($line->ordered_qty * ($line->product?->standard_cost ?? 0), 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="badge {{ str_starts_with($line->variant_sku,'BV') ? 'b-blue' : 'b-green' }}" style="font-size:10px">
                                    {{ $loc }}
                                </span>
                                <div style="font-size:10.5px;color:#8A8478;margin-top:2px">Stok saat ini: {{ number_format($currStock, 0) }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Summary --}}
            <div class="card-body" style="border-top:1px solid #F0EEE8">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div style="display:flex;gap:20px">
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Total Dipesan</div>
                            <div style="font-size:18px;font-weight:700;color:#1D4ED8">
                                {{ $po->lines->sum('ordered_qty') }}
                            </div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Estimasi Valuation</div>
                            <div style="font-size:18px;font-weight:700;color:#18382E" id="total-val">
                                Rp{{ number_format($po->lines->reduce(fn($c,$l) => $c + ($l->ordered_qty * ($l->product?->standard_cost ?? 0)), 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:10.5px;color:#8A8478;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">GRNI (Inventory DR)</div>
                            <div style="font-size:18px;font-weight:700;color:#C6A76B" id="grni-val">
                                Rp{{ number_format($po->lines->reduce(fn($c,$l) => $c + ($l->ordered_qty * ($l->product?->standard_cost ?? 0)), 0), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="padding:10px 20px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                        Validasi Penerimaan
                    </button>
                </div>

                {{-- Journal preview --}}
                <div style="margin-top:16px;padding:12px;background:#F7F5F0;border-radius:8px;border:1px solid #E8E4DA">
                    <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#8A8478;margin-bottom:8px">
                        Preview Journal Entry (GRNI)
                    </div>
                    <div style="display:grid;grid-template-columns:auto 1fr 1fr;gap:4px 16px;font-size:12.5px">
                        <span class="badge b-blue" style="justify-self:start">DR</span>
                        <span style="color:#1E1C1A">1200 · Persediaan</span>
                        <span style="text-align:right;font-weight:600" id="je-debit">Rp—</span>
                        <span class="badge b-gray" style="justify-self:start">CR</span>
                        <span style="color:#1E1C1A">2030 · GRNI: Barang Diterima Belum Ditagih</span>
                        <span style="text-align:right;font-weight:600" id="je-credit">Rp—</span>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
const lineStdCosts = {
    @foreach($po->lines as $i => $line)
    {{ $i }}: {{ $line->product?->standard_cost ?? 0 }},
    @endforeach
};

function syncRejected(idx, orderedQty, fromAccepted = false) {
    const recvEl = document.getElementById(`recv-${idx}`);
    const accEl  = document.getElementById(`acc-${idx}`);
    const rejEl  = document.getElementById(`rej-${idx}`);
    const valEl  = document.getElementById(`val-${idx}`);

    let recv = Math.min(parseFloat(recvEl.value) || 0, orderedQty);
    let acc  = Math.min(parseFloat(accEl.value) || 0, recv);

    if (fromAccepted) {
        // If accepted > received, cap it
        if (acc > recv) { accEl.value = recv; acc = recv; }
    } else {
        // If received decreases below accepted, cap accepted
        if (acc > recv) { accEl.value = recv; acc = recv; }
    }

    const rej = recv - acc;
    rejEl.textContent = rej;
    rejEl.style.color = rej > 0 ? '#DC2626' : '#B8B2A6';

    // Update valuation
    const stdCost = lineStdCosts[idx] || 0;
    const val = Math.round(acc * stdCost);
    valEl.textContent = 'Rp' + val.toLocaleString('id-ID');

    updateTotals();
}

function updateTotals() {
    let total = 0;
    Object.keys(lineStdCosts).forEach(idx => {
        const acc = parseFloat(document.getElementById(`acc-${idx}`)?.value) || 0;
        total += acc * (lineStdCosts[idx] || 0);
    });
    const fmt = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
    document.getElementById('total-val').textContent = fmt(total);
    document.getElementById('grni-val').textContent  = fmt(total);
    document.getElementById('je-debit').textContent  = fmt(total);
    document.getElementById('je-credit').textContent = fmt(total);
}

// Init on load
window.addEventListener('DOMContentLoaded', () => {
    @foreach($po->lines as $i => $line)
    syncRejected({{ $i }}, {{ $line->ordered_qty }});
    @endforeach
    updateTotals();
});
</script>
@endpush
@endsection
