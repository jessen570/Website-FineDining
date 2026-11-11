@extends('layouts.app')

@section('title', $receipt->receipt_id . ' — Receipt')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.index') }}">Pembelian</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.show', $receipt->purchase_order_id) }}">{{ $receipt->purchase_order_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $receipt->receipt_id }}</span>
@endsection

@section('content')
<div style="max-width:1000px">

    {{-- ── Header ───────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('purchase.show', $receipt->purchase_order_id) }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke PO
            </a>
            <div>
                <div style="display:flex;align-items:center;gap:10px">
                    <h1 class="page-title">{{ $receipt->receipt_id }}</h1>
                    <span class="badge b-green" style="font-size:12px">{{ $receipt->status }}</span>
                    @if($receipt->is_posted)
                        <span class="badge b-forest">Posted · GRNI</span>
                    @endif
                </div>
                <p class="page-desc">
                    PO: <a href="{{ route('purchase.show', $receipt->purchase_order_id) }}" style="color:#18382E">{{ $receipt->purchase_order_id }}</a>
                    · {{ $receipt->purchaseOrder?->vendor?->name }}
                    · {{ $receipt->receipt_date?->format('d M Y') }}
                    · F-13 · SR-06, SR-09
                </p>
            </div>
        </div>
        @if($receipt->purchaseOrder?->billing_status === 'To Bill' && in_array(auth()->user()->role, ['owner','finance']))
        <form method="POST" action="{{ route('invoices.createFromReceipt', $receipt->receipt_id) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                Buat Vendor Bill
            </button>
        </form>
        @endif
    </div>

    {{-- SR-06 enforcement notice --}}
    <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-left:3px solid #059669;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:12.5px;color:#065F46">
        <strong>SR-06:</strong> Hanya <strong>Accepted Quantity</strong> yang menambah On Hand. Rejected tidak masuk stok. Vendor Bill maksimal sebesar Accepted Qty × Harga PO.
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

        {{-- ── Main Content ──────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- Receipt Lines --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <span class="card-title">Detail Penerimaan</span>
                        <div style="font-size:11px;color:#8A8478;margin-top:2px">
                            Three-Way Match: Ordered vs Received vs Accepted · F-13, F-14
                        </div>
                    </div>
                    <span class="badge b-gold">Valuation: Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</span>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Produk</th>
                                <th style="text-align:right;background:#EFF6FF">Ordered</th>
                                <th style="text-align:right">Received</th>
                                <th style="text-align:right;background:#F0FDF4">✓ Accepted</th>
                                <th style="text-align:right;background:#FEF2F2">✗ Rejected</th>
                                <th style="text-align:right">Harga PO</th>
                                <th style="text-align:right">Std Cost</th>
                                <th style="text-align:right;background:#FFFBEB">Valuation</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAccepted = 0; $totalRejected = 0; @endphp
                            @foreach($receipt->lines as $line)
                            @php
                                $totalAccepted += $line->accepted_qty;
                                $totalRejected += $line->rejected_qty;
                                $loc = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                                $acceptPct = $line->received_qty > 0
                                    ? round(($line->accepted_qty / $line->received_qty) * 100, 0)
                                    : 0;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $line->variant_sku) }}"
                                       class="text-mono" style="color:#18382E;font-weight:700;font-size:11px;text-decoration:none">
                                        {{ $line->variant_sku }}
                                    </a>
                                </td>
                                <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                                <td style="text-align:right;background:#F0F9FF;font-weight:700;color:#1D4ED8">
                                    {{ number_format($line->ordered_qty, 0) }}
                                </td>
                                <td style="text-align:right;font-weight:600">{{ number_format($line->received_qty, 0) }}</td>
                                <td style="text-align:right;background:#F0FDF4">
                                    <span style="font-size:15px;font-weight:700;color:#059669">{{ number_format($line->accepted_qty, 0) }}</span>
                                    <div style="font-size:10px;color:#8A8478;margin-top:1px">{{ $acceptPct }}% diterima</div>
                                </td>
                                <td style="text-align:right;background:#FEF2F2">
                                    @if($line->rejected_qty > 0)
                                        <span style="font-size:15px;font-weight:700;color:#DC2626">{{ number_format($line->rejected_qty, 0) }}</span>
                                        <span class="badge b-red" style="font-size:9px;display:block;margin-top:2px;width:fit-content;margin-left:auto">Ditolak</span>
                                    @else
                                        <span style="color:#B8B2A6">0</span>
                                    @endif
                                </td>
                                <td style="text-align:right;color:#6B6560">Rp{{ number_format($line->unit_purchase_price, 0, ',', '.') }}</td>
                                <td style="text-align:right;color:#C6A76B">Rp{{ number_format($line->standard_cost, 0, ',', '.') }}</td>
                                <td style="text-align:right;background:#FFFBEB;font-weight:700;color:#18382E">
                                    Rp{{ number_format($line->valuation_amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge {{ str_starts_with($line->variant_sku,'BV') ? 'b-blue' : 'b-green' }}" style="font-size:10px">
                                        {{ $loc }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#FAFAF9;border-top:2px solid #E8E4DA">
                            <tr>
                                <td colspan="4" style="padding:10px 16px;font-weight:700;font-size:11.5px;color:#6B6560;text-transform:uppercase;letter-spacing:.05em">Total</td>
                                <td style="text-align:right;padding:10px 16px;font-size:15px;font-weight:700;color:#059669;background:#F0FDF4">
                                    {{ number_format($totalAccepted, 0) }}
                                </td>
                                <td style="text-align:right;padding:10px 16px;font-size:15px;font-weight:700;color:#DC2626;background:#FEF2F2">
                                    {{ number_format($totalRejected, 0) }}
                                </td>
                                <td colspan="2"></td>
                                <td style="text-align:right;padding:10px 16px;font-size:15px;font-weight:700;color:#18382E;background:#FFFBEB">
                                    Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- GRNI Journal Entry --}}
            @if($receipt->is_posted)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Journal Entry — GRNI</span>
                    <span class="badge b-forest">Posted · SR-20</span>
                </div>
                <div class="card-body">
                    <div style="font-size:12px;color:#8A8478;margin-bottom:12px">
                        Penerimaan barang menghasilkan satu set jurnal: DR Persediaan / CR GRNI (Barang Diterima Belum Ditagih). Saat Vendor Bill diposting, GRNI di-clear dengan CR Utang Usaha.
                    </div>
                    <table class="data-table" style="margin:0">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Kode Akun</th>
                                <th>Nama Akun</th>
                                <th style="text-align:right">Debit</th>
                                <th style="text-align:right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge b-blue">DR</span></td>
                                <td class="text-mono">1200</td>
                                <td>Persediaan</td>
                                <td style="text-align:right;font-weight:700;color:#059669">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</td>
                                <td style="text-align:right;color:#B8B2A6">—</td>
                            </tr>
                            <tr>
                                <td><span class="badge b-gray">CR</span></td>
                                <td class="text-mono">2030</td>
                                <td>GRNI: Barang Diterima Belum Ditagih</td>
                                <td style="text-align:right;color:#B8B2A6">—</td>
                                <td style="text-align:right;font-weight:700;color:#DC2626">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot style="background:#FAFAF9;border-top:1px solid #E8E4DA">
                            <tr>
                                <td colspan="3" style="padding:8px 16px;font-weight:700;font-size:12px;color:#6B6560">Total (Debit = Kredit · SR-20)</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#059669">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#DC2626">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- KPI Summary --}}
            <div class="kpi" style="border-left:3px solid #059669">
                <div class="kpi-label">Accepted</div>
                <div class="kpi-value" style="color:#059669">{{ number_format($totalAccepted, 0) }}</div>
                <div class="kpi-sub">unit masuk stok</div>
            </div>
            <div class="kpi" style="border-left:3px solid #DC2626">
                <div class="kpi-label">Rejected</div>
                <div class="kpi-value" style="color:#DC2626">{{ number_format($totalRejected, 0) }}</div>
                <div class="kpi-sub">unit tidak masuk stok</div>
            </div>
            <div class="kpi" style="border-left:3px solid #C6A76B">
                <div class="kpi-label">Valuation (GRNI)</div>
                <div class="kpi-value" style="font-size:1rem">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</div>
                <div class="kpi-sub">std cost × accepted qty</div>
            </div>

            {{-- Receipt Info --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi Receipt</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="section-title">Receipt ID</div>
                        <div class="text-mono" style="font-weight:700;color:#18382E">{{ $receipt->receipt_id }}</div>
                    </div>
                    <div>
                        <div class="section-title">Purchase Order</div>
                        <a href="{{ route('purchase.show', $receipt->purchase_order_id) }}"
                           class="text-mono" style="color:#18382E;font-weight:600;text-decoration:none">
                            {{ $receipt->purchase_order_id }}
                        </a>
                    </div>
                    <div>
                        <div class="section-title">Vendor</div>
                        <div style="font-weight:600">{{ $receipt->purchaseOrder?->vendor?->name }}</div>
                    </div>
                    <div>
                        <div class="section-title">Tanggal Terima</div>
                        <div>{{ $receipt->receipt_date?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="section-title">Status</div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <span class="badge b-green">{{ $receipt->status }}</span>
                            @if($receipt->is_posted)
                                <span class="badge b-forest">GRNI Posted</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Business Rules --}}
            <div class="card" style="border-left:3px solid #C6A76B">
                <div class="card-header"><span class="card-title">Aturan Bisnis</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div>
                        <span class="badge b-amber">SR-06</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">Vendor Bill maks = Accepted Qty × Harga PO.</p>
                    </div>
                    <div>
                        <span class="badge b-amber">SR-09</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">Accepted ≤ Received. Stok negatif tidak diizinkan.</p>
                    </div>
                    <div>
                        <span class="badge b-amber">SR-20</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">GRNI diposting satu kali. Debit = Kredit per dokumen.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
