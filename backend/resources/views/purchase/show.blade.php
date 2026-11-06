@extends('layouts.app')

@section('title', $po->po_id . ' — Purchase Order')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('purchase.index') }}">Pembelian</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $po->po_id }}</span>
@endsection

@section('content')
<div style="max-width:1050px">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('purchase.index') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <h1 class="page-title">{{ $po->po_id }}</h1>
                    @php
                        $statusColor = match($po->status) {
                            'Confirmed' => 'b-green',
                            'RFQ'       => 'b-purple',
                            'Done'      => 'b-forest',
                            'Cancelled' => 'b-red',
                            default     => 'b-gray',
                        };
                    @endphp
                    <span class="badge {{ $statusColor }}" style="font-size:12px">{{ $po->status }}</span>
                    @if($po->is_locked)
                        <span class="badge b-amber">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Terkunci · SR-18
                        </span>
                    @endif
                </div>
                <p class="page-desc">{{ $po->vendor?->name }} · {{ $po->order_date?->format('d M Y') }} · F-12, F-13</p>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($po->status === 'RFQ' && in_array(auth()->user()->role, ['owner','procurement']))
                <form method="POST" action="{{ route('purchase.confirm', $po->po_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Konfirmasi PO ini? PO akan dikunci dan tidak dapat diedit (SR-18).')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Konfirmasi PO
                    </button>
                </form>
                <a href="{{ route('purchase.edit', $po->po_id) }}" class="btn btn-outline">Edit RFQ</a>
                <form method="POST" action="{{ route('purchase.cancel', $po->po_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan RFQ ini?')">Batalkan</button>
                </form>
            @endif
            @if($po->status === 'Confirmed' && $po->receipt_status === 'Pending' && in_array(auth()->user()->role, ['owner','warehouse','procurement']))
                <a href="{{ route('purchase.receipt.create', $po->po_id) }}" class="btn btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                    Terima Barang
                </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash-success" style="margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flash-error" style="margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

        {{-- ── Main ────────────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- Status Pipeline --}}
            <div class="card">
                <div class="card-body" style="padding:14px 18px">
                    <div class="status-pipeline">
                        @php
                            $stages = ['RFQ','Confirmed','Done'];
                            $ci = array_search($po->status, $stages);
                            if ($po->status === 'Cancelled') $ci = -1;
                        @endphp
                        @foreach($stages as $idx => $stage)
                        <div class="pipeline-step {{ $idx < $ci ? 'done' : ($idx == $ci ? 'active' : '') }}">
                            <div class="pipeline-dot">
                                @if($idx < $ci)
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                @else {{ $idx + 1 }}
                                @endif
                            </div>
                            <div class="pipeline-label">{{ $stage }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Lines --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Baris Pesanan</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        @if($po->is_locked)
                            <span style="font-size:11.5px;color:#D97706">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Harga & Qty terkunci SR-18
                            </span>
                        @endif
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Produk</th>
                                <th style="text-align:right">Ordered</th>
                                <th style="text-align:right">Received</th>
                                <th style="text-align:right">Accepted</th>
                                <th style="text-align:right">Billed</th>
                                <th style="text-align:right">Harga Unit</th>
                                <th style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->lines as $line)
                            <tr>
                                <td><span class="text-mono badge b-gray" style="font-size:11px">{{ $line->variant_sku }}</span></td>
                                <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                                <td style="text-align:right;font-weight:700">{{ number_format($line->ordered_qty, 0) }}</td>
                                <td style="text-align:right;color:#3B82F6">{{ number_format($line->received_qty, 0) }}</td>
                                <td style="text-align:right;color:#059669;font-weight:600">{{ number_format($line->accepted_qty, 0) }}</td>
                                <td style="text-align:right;color:#D97706">{{ number_format($line->billed_qty, 0) }}</td>
                                <td style="text-align:right">Rp{{ number_format($line->unit_purchase_price, 0, ',', '.') }}</td>
                                <td style="text-align:right;font-weight:700">Rp{{ number_format($line->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#FAFAF9;border-top:2px solid #E8E4DA">
                            <tr>
                                <td colspan="7" style="padding:10px 16px;font-weight:700;font-size:12px;color:#6B6560;text-transform:uppercase">Total PO</td>
                                <td style="text-align:right;padding:10px 16px;font-weight:700;font-size:16px;color:#18382E">
                                    Rp{{ number_format($po->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Receipts --}}
            @if($po->receipts->count() > 0)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Penerimaan Barang</span>
                    <span class="badge b-green">{{ $po->receipts->count() }} receipt</span>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Receipt ID</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th style="text-align:right">Valuation</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->receipts as $receipt)
                            <tr>
                                <td><span class="text-mono" style="color:#18382E;font-weight:700;font-size:12px">{{ $receipt->receipt_id }}</span></td>
                                <td>{{ $receipt->receipt_date?->format('d M Y') }}</td>
                                <td><span class="badge b-green">{{ $receipt->status }}</span></td>
                                <td style="text-align:right;font-weight:600">Rp{{ number_format($receipt->valuation_total, 0, ',', '.') }}</td>
                                <td><a href="{{ route('purchase.receipt.show', $receipt->receipt_id) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Vendor Bills --}}
            @if($po->bills->count() > 0)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Vendor Bills</span>
                    <span class="badge b-amber">{{ $po->bills->count() }} tagihan</span>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th style="text-align:right">Total</th>
                                <th style="text-align:right">Outstanding</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->bills as $bill)
                            <tr>
                                <td><span class="text-mono" style="color:#18382E;font-weight:700;font-size:12px">{{ $bill->invoice_id }}</span></td>
                                <td>{{ $bill->invoice_date?->format('d M Y') }}</td>
                                <td>
                                    @php $bc = match($bill->status) { 'Posted'=>'b-green','Paid'=>'b-forest','Cancelled'=>'b-red', default=>'b-gray' }; @endphp
                                    <span class="badge {{ $bc }}">{{ $bill->status }}</span>
                                </td>
                                <td style="text-align:right;font-weight:700">Rp{{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                                <td style="text-align:right;color:{{ $bill->amount_due > 0 ? '#DC2626' : '#059669' }};font-weight:600">
                                    Rp{{ number_format($bill->amount_due ?? 0, 0, ',', '.') }}
                                </td>
                                <td><a href="{{ route('invoices.show', $bill->invoice_id) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- PO Info --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Detail PO</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="section-title">Vendor</div>
                        <div style="font-weight:700">{{ $po->vendor?->name }}</div>
                        <div style="font-size:11.5px;color:#8A8478">{{ $po->vendor_id }} · {{ $po->vendor?->city }}</div>
                    </div>
                    <div>
                        <div class="section-title">Payment Terms</div>
                        <div style="font-weight:600">{{ $po->payment_terms }}</div>
                    </div>
                    <div>
                        <div class="section-title">Receipt Status</div>
                        @php $rs = match($po->receipt_status) { 'Pending'=>'b-blue','Received'=>'b-green', default=>'b-gray' }; @endphp
                        <span class="badge {{ $rs }}">{{ $po->receipt_status }}</span>
                    </div>
                    <div>
                        <div class="section-title">Billing Status</div>
                        @php $bs = match($po->billing_status) { 'To Bill'=>'b-amber','Fully Billed'=>'b-green', default=>'b-gray' }; @endphp
                        <span class="badge {{ $bs }}">{{ $po->billing_status }}</span>
                    </div>
                    @if($po->notes)
                    <div>
                        <div class="section-title">Catatan</div>
                        <div style="font-size:12.5px;color:#6B6560">{{ $po->notes }}</div>
                    </div>
                    @endif
                    <hr class="divider" style="margin:0">
                    <div>
                        <div class="section-title">Total PO</div>
                        <div style="font-size:20px;font-weight:700;color:#18382E">
                            Rp{{ number_format($po->total_amount, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Smart Buttons --}}
            @if($po->receipts->count() > 0 || $po->bills->count() > 0)
            <div class="card">
                <div class="card-header"><span class="card-title">Dokumen Terkait</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                    @if($po->receipts->count())
                    <a href="{{ route('purchase.receipt.show', $po->receipts->first()->receipt_id) }}"
                       class="btn btn-outline" style="justify-content:space-between">
                        <span style="display:flex;align-items:center;gap:6px">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/></svg>
                            Receipts
                        </span>
                        <span class="badge b-green">{{ $po->receipts->count() }}</span>
                    </a>
                    @endif
                    @if($po->bills->count())
                    <a href="{{ route('invoices.show', $po->bills->first()->invoice_id) }}"
                       class="btn btn-outline" style="justify-content:space-between">
                        <span style="display:flex;align-items:center;gap:6px">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                            Vendor Bills
                        </span>
                        <span class="badge b-amber">{{ $po->bills->count() }}</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- SR-18 Lock Notice --}}
            @if($po->is_locked)
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-left:3px solid #D97706;border-radius:8px;padding:12px 14px">
                <div style="font-weight:700;font-size:12px;color:#78350F;margin-bottom:4px">SR-18 — PO Terkunci</div>
                <div style="font-size:11.5px;color:#92400E;line-height:1.5">Harga unit dan kuantitas tidak dapat diubah setelah PO dikonfirmasi. Perubahan memerlukan dokumen revisi.</div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
