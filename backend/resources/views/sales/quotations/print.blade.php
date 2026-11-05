<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_id }} – MAISON ASTER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #1E1C1A;
            background: white;
            line-height: 1.5;
        }
        .page {
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 48px;
        }
        /* Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #E8E4DA;
        }
        .company-brand {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #18382E;
            letter-spacing: 0.02em;
        }
        .company-tagline {
            font-size: 12px;
            color: #8A8478;
            margin-top: 3px;
            letter-spacing: 0.06em;
        }
        .doc-type {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #C6A76B;
            text-align: right;
            letter-spacing: 0.05em;
        }
        /* Two-column meta */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .meta-block { }
        .meta-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8A8478;
            margin-bottom: 8px;
        }
        .meta-name {
            font-size: 15px;
            font-weight: 700;
            color: #1E1C1A;
            margin-bottom: 4px;
        }
        .meta-line {
            font-size: 12.5px;
            color: #4D4944;
            line-height: 1.7;
        }
        /* Ref table */
        .ref-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            background: #FAFAF9;
            border: 1px solid #E8E4DA;
            border-radius: 8px;
            overflow: hidden;
        }
        .ref-table th {
            padding: 8px 14px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8A8478;
            text-align: left;
            background: #F0EEE8;
        }
        .ref-table td {
            padding: 10px 14px;
            font-size: 13px;
            color: #1E1C1A;
        }
        /* Line items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .items-table thead th {
            padding: 10px 12px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8A8478;
            border-bottom: 2px solid #E8E4DA;
            background: #FAFAF9;
        }
        .items-table tbody td {
            padding: 10px 12px;
            font-size: 12.5px;
            border-bottom: 1px solid #F0EEE8;
            vertical-align: middle;
        }
        .items-table tfoot td {
            padding: 8px 12px;
            font-size: 12.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        /* Totals box */
        .totals-box {
            background: #F7F5F0;
            border: 1px solid #E8E4DA;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 12px;
            margin-left: auto;
            width: 320px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }
        .totals-row.grand {
            border-top: 2px solid #E8E4DA;
            margin-top: 4px;
            padding-top: 10px;
            font-size: 15px;
            font-weight: 700;
        }
        .totals-row.grand .amt { color: #C6A76B; }
        /* Sections */
        .section {
            margin-top: 28px;
        }
        .section-heading {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8A8478;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #E8E4DA;
        }
        .section-body {
            font-size: 12.5px;
            color: #4D4944;
            line-height: 1.7;
        }
        /* Signature */
        .sig-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }
        .sig-block {
            border-top: 1px solid #E8E4DA;
            padding-top: 8px;
        }
        .sig-label {
            font-size: 11px;
            color: #8A8478;
            letter-spacing: 0.06em;
        }
        .sig-name {
            font-size: 13px;
            font-weight: 600;
            color: #1E1C1A;
            margin-top: 36px;
        }
        /* Footer */
        .doc-footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #E8E4DA;
            text-align: center;
            font-size: 10.5px;
            color: #B8B2A6;
            letter-spacing: 0.06em;
        }
        /* Print button */
        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #18382E;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        @media print {
            .print-btn { display: none !important; }
            body { font-size: 11px; }
            .page { padding: 20px 24px; }
        }
    </style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="doc-header">
        <div>
            <div class="company-brand">MAISON ASTER</div>
            <div class="company-tagline">Fine Dining &bull; South Jakarta</div>
        </div>
        <div>
            <div class="doc-type">QUOTATION</div>
        </div>
    </div>

    {{-- Meta: Company + Customer --}}
    <div class="meta-grid">
        <div class="meta-block">
            <div class="meta-label">From</div>
            <div class="meta-name">MAISON ASTER</div>
            <div class="meta-line">Fine Dining Restaurant</div>
            <div class="meta-line">South Jakarta, Indonesia</div>
            <div class="meta-line">maison@aster.co.id</div>
        </div>
        <div class="meta-block">
            <div class="meta-label">To</div>
            <div class="meta-name">{{ $quotation->customer?->name ?? '—' }}</div>
            @if($quotation->customer?->address)
            <div class="meta-line">{{ $quotation->customer->address }}</div>
            @endif
            @if($quotation->customer?->city)
            <div class="meta-line">{{ $quotation->customer->city }}</div>
            @endif
            @if($quotation->customer?->email)
            <div class="meta-line">{{ $quotation->customer->email }}</div>
            @endif
            @if($quotation->customer?->phone)
            <div class="meta-line">{{ $quotation->customer->phone }}</div>
            @endif
            @if($quotation->customer?->tax_id)
            <div class="meta-line">NPWP: {{ $quotation->customer->tax_id }}</div>
            @endif
        </div>
    </div>

    {{-- Reference Table --}}
    <table class="ref-table">
        <thead>
            <tr>
                <th>Quotation No.</th>
                <th>Date</th>
                <th>Expiry Date</th>
                <th>Salesperson</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-family:monospace;font-weight:700">{{ $quotation->quotation_id }}</td>
                <td>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}</td>
                <td>{{ $quotation->expiration_date ? \Carbon\Carbon::parse($quotation->expiration_date)->format('d M Y') : '—' }}</td>
                <td>{{ $quotation->salesperson?->name ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Line Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width:30px">#</th>
                <th>Product / Description</th>
                <th class="text-right" style="width:60px">Qty</th>
                <th class="text-right" style="width:120px">Unit Price</th>
                <th class="text-right" style="width:60px">Disc%</th>
                <th class="text-right" style="width:120px">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $grandSubtotal = 0; @endphp
            @foreach($quotation->lines as $idx => $line)
            @php
                $qty   = $line->quantity ?? 0;
                $price = $line->unit_price ?? 0;
                $disc  = $line->discount_percent ?? 0;
                $sub   = $qty * $price * (1 - $disc/100);
                $grandSubtotal += $sub;
            @endphp
            <tr>
                <td class="text-center" style="color:#B8B2A6">{{ $idx+1 }}</td>
                <td>
                    <div style="font-weight:600">{{ $line->product?->name ?? '—' }}</div>
                    @if($line->product?->sku)
                    <div style="font-size:11px;color:#8A8478">SKU: {{ $line->product->sku }}</div>
                    @endif
                </td>
                <td class="text-right">{{ $qty }}</td>
                <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $disc > 0 ? $disc.'%' : '—' }}</td>
                <td class="text-right">Rp {{ number_format($sub, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals Box --}}
    @php
        $discPct   = $quotation->discount_percent ?? 0;
        $discount  = $grandSubtotal * $discPct / 100;
        $afterDisc = $grandSubtotal - $discount;
        $pbjt      = $afterDisc * 0.10;
        $grandTotal= $afterDisc + $pbjt;
    @endphp
    <div class="totals-box">
        <div class="totals-row">
            <span style="color:#6B6560">Subtotal</span>
            <span>Rp {{ number_format($grandSubtotal, 0, ',', '.') }}</span>
        </div>
        @if($discPct > 0)
        <div class="totals-row">
            <span style="color:#D97706">Discount ({{ $discPct }}%)</span>
            <span style="color:#D97706">- Rp {{ number_format($discount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="totals-row">
            <span style="color:#6B6560">PBJT (10%)</span>
            <span>Rp {{ number_format($pbjt, 0, ',', '.') }}</span>
        </div>
        <div class="totals-row grand">
            <span>GRAND TOTAL</span>
            <span class="amt">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Terms --}}
    @if($quotation->terms_conditions || $quotation->notes)
    <div class="section">
        @if($quotation->notes)
        <div class="section-heading">Notes</div>
        <div class="section-body">{{ $quotation->notes }}</div>
        @endif
        @if($quotation->terms_conditions)
        <div class="section-heading" style="margin-top:14px">Terms &amp; Conditions</div>
        <div class="section-body">{{ $quotation->terms_conditions }}</div>
        @endif
    </div>
    @endif

    {{-- Signature --}}
    <div class="sig-grid">
        <div class="sig-block">
            <div class="sig-label">PREPARED BY</div>
            <div class="sig-name">{{ $quotation->salesperson?->name ?? 'Maison Aster' }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-label">ACCEPTED BY CUSTOMER</div>
            <div class="sig-name">{{ $quotation->customer?->name ?? '' }}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="doc-footer">
        MAISON ASTER &mdash; Confidential &bull; This document is valid until {{ $quotation->expiration_date ? \Carbon\Carbon::parse($quotation->expiration_date)->format('d M Y') : 'further notice' }}
    </div>
</div>

<button class="print-btn" onclick="window.print()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print / Save PDF
</button>
</body>
</html>
