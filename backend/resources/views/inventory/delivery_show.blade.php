@extends('layouts.app')

@section('title', 'Delivery ' . $delivery->delivery_id)

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('inventory.deliveries') }}">Delivery Orders</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $delivery->delivery_id }}</span>
@endsection

@section('content')
<div style="max-width:1000px">

    {{-- ── Header ───────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('inventory.deliveries') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
            <div>
                <div style="display:flex;align-items:center;gap:10px">
                    <h1 class="page-title">{{ $delivery->delivery_id }}</h1>
                    @php
                        $statusColor = match($delivery->status) {
                            'Done'      => 'b-green',
                            'Ready'     => 'b-blue',
                            'Draft'     => 'b-gray',
                            'Cancelled' => 'b-red',
                            default     => 'b-gray',
                        };
                    @endphp
                    <span class="badge {{ $statusColor }} " style="font-size:12px">
                        {{ $delivery->status }}
                    </span>
                    @if($delivery->is_posted)
                        <span class="badge b-forest">Posted</span>
                    @endif
                    @if($delivery->backorder_delivery_id)
                        <span class="badge b-amber">Has Backorder</span>
                    @endif
                </div>
                <p class="page-desc">
                    SO: {{ $delivery->sales_order_id }}
                    @if($delivery->delivery_date)
                        · {{ $delivery->delivery_date->format('d M Y') }}
                    @endif
                    · F-08, F-09, F-15
                </p>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            @if($delivery->salesOrder)
            <a href="{{ route('sales.orders.show', $delivery->sales_order_id) }}" class="btn btn-outline">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg>
                Lihat SO
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

    {{-- SR-20 Idempotency Warning --}}
    @if($delivery->is_posted)
    <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-left:3px solid #059669;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:12.5px;color:#065F46">
        <strong>SR-20 Idempotency:</strong> Delivery ini sudah diposting. Validasi ulang tidak akan menggandakan stok atau jurnal COGS.
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

        {{-- ── Main Content ──────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- Status Pipeline --}}
            <div class="card">
                <div class="card-body">
                    <div class="status-pipeline">
                        @php
                            $stages = ['Draft', 'Ready', 'Done'];
                            $currentIdx = array_search($delivery->status, $stages);
                            if ($delivery->status === 'Cancelled') $currentIdx = -1;
                        @endphp
                        @foreach($stages as $idx => $stage)
                        <div class="pipeline-step {{ $idx < $currentIdx ? 'done' : ($idx == $currentIdx ? 'active' : '') }}">
                            <div class="pipeline-dot">
                                @if($idx < $currentIdx)
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    {{ $idx + 1 }}
                                @endif
                            </div>
                            <div class="pipeline-label">{{ $stage }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Delivery Lines --}}
            @if($delivery->status === 'Ready' && !$delivery->is_posted && in_array(auth()->user()->role, ['owner','warehouse','procurement']))
            {{-- Validate Form --}}
            <form method="POST" action="{{ route('inventory.delivery.validate', $delivery->delivery_id) }}" id="validate-form">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="card-title">Validasi Pengiriman</span>
                            <div style="font-size:11px;color:#8A8478;margin-top:2px">SR-11: Delivery Done mengurangi On Hand & mencatat COGS · SR-09: stok negatif ditolak</div>
                        </div>
                        <span class="badge b-blue">F-09 Partial Delivery</span>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th style="text-align:right">Qty Ordered</th>
                                    <th style="text-align:right">Sudah Dikirim</th>
                                    <th style="text-align:right">Available</th>
                                    <th style="text-align:right">Kirim Sekarang</th>
                                    <th style="text-align:right">COGS (est.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($delivery->lines as $line)
                                @php
                                    $remaining = $line->ordered_qty - $line->delivered_qty;
                                    $loc = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                                    $stock = \App\Models\StockLevel::where('variant_sku', $line->variant_sku)
                                        ->where('location_code', $loc)->first();
                                    $avail = $stock?->available ?? 0;
                                @endphp
                                <tr>
                                    <td><span class="text-mono badge b-gray" style="font-size:11px">{{ $line->variant_sku }}</span></td>
                                    <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                                    <td style="text-align:right">{{ number_format($line->ordered_qty, 0) }}</td>
                                    <td style="text-align:right;color:#059669">{{ number_format($line->delivered_qty, 0) }}</td>
                                    <td style="text-align:right">
                                        <span style="color:{{ $avail >= $remaining ? '#059669' : '#DC2626' }};font-weight:600">{{ number_format($avail, 0) }}</span>
                                        <div style="font-size:10.5px;color:#8A8478">di {{ $loc }}</div>
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number" name="delivered_qtys[{{ $line->id }}]"
                                            class="form-input" style="width:90px;padding:6px 8px;text-align:right"
                                            value="{{ $remaining }}" min="0" max="{{ $remaining }}" step="1"
                                            oninput="recalcCogs({{ $line->id }}, {{ $line->standard_cost ?? 0 }}, this.value)">
                                    </td>
                                    <td style="text-align:right;font-weight:600" id="cogs-{{ $line->id }}">
                                        Rp{{ number_format($remaining * ($line->standard_cost ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body" style="border-top:1px solid #F0EEE8">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div>
                                <div style="font-size:12px;color:#8A8478">Total COGS (estimasi)</div>
                                <div style="font-size:18px;font-weight:700;color:#1E1C1A" id="total-cogs">
                                    Rp{{ number_format($delivery->lines->reduce(fn($c,$l) => $c + (($l->ordered_qty - $l->delivered_qty) * ($l->standard_cost ?? 0)), 0), 0, ',', '.') }}
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                Validasi Pengiriman
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            @else
            {{-- Read-only lines --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Baris Pengiriman</span>
                    @if($delivery->cost_of_goods_sold > 0)
                        <span class="badge b-amber">COGS: Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}</span>
                    @endif
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Produk</th>
                                <th style="text-align:right">Ordered</th>
                                <th style="text-align:right">Delivered</th>
                                <th style="text-align:right">Sisa (Backorder)</th>
                                <th style="text-align:right">COGS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->lines as $line)
                            @php
                                $remaining = $line->ordered_qty - $line->delivered_qty;
                            @endphp
                            <tr>
                                <td><span class="text-mono badge b-gray" style="font-size:11px">{{ $line->variant_sku }}</span></td>
                                <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                                <td style="text-align:right">{{ number_format($line->ordered_qty, 0) }}</td>
                                <td style="text-align:right;color:#059669;font-weight:700">{{ number_format($line->delivered_qty, 0) }}</td>
                                <td style="text-align:right">
                                    @if($remaining > 0)
                                        <span style="color:#D97706;font-weight:600">{{ number_format($remaining, 0) }}</span>
                                        <span class="badge b-amber" style="font-size:9px;margin-left:4px">Backorder</span>
                                    @else
                                        <span style="color:#059669;font-weight:600">0</span>
                                    @endif
                                </td>
                                <td style="text-align:right;font-weight:600">
                                    @if($line->subtotal_cogs ?? 0 > 0)
                                        Rp{{ number_format($line->subtotal_cogs, 0, ',', '.') }}
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── F-09: Partial Delivery Progress ─────────────────────── --}}
            @php
                $hasAnyBackorder = $delivery->lines->some(fn($l) => ($l->ordered_qty - $l->delivered_qty) > 0);
                $totalOrdered    = $delivery->lines->sum('ordered_qty');
                $totalDelivered  = $delivery->lines->sum('delivered_qty');
                $totalRemaining  = $totalOrdered - $totalDelivered;
                $pct = $totalOrdered > 0 ? round(($totalDelivered / $totalOrdered) * 100) : 0;
            @endphp
            @if($delivery->status === 'Done' || $delivery->is_posted)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Status Pengiriman (F-09)</span>
                    @if($totalRemaining > 0)
                        <span class="badge b-amber">Partial · {{ $totalRemaining }} unit backorder</span>
                    @else
                        <span class="badge b-green">Fully Delivered</span>
                    @endif
                </div>
                <div class="card-body">
                    {{-- Progress Bar --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:#8A8478;margin-bottom:6px">
                            <span>Terkirim: <strong style="color:#059669">{{ number_format($totalDelivered,0) }} unit</strong></span>
                            <span>Sisa: <strong style="color:{{ $totalRemaining > 0 ? '#D97706' : '#B8B2A6' }}">{{ number_format($totalRemaining,0) }} unit</strong></span>
                            <span><strong>{{ $pct }}%</strong> selesai</span>
                        </div>
                        <div class="credit-bar">
                            <div class="credit-fill" style="width:{{ $pct }}%;background:{{ $pct == 100 ? '#059669' : '#C6A76B' }}"></div>
                        </div>
                    </div>

                    {{-- Per-line progress --}}
                    <table class="data-table" style="margin:0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Produk</th>
                                <th style="text-align:right">Ordered</th>
                                <th style="text-align:right">Delivered</th>
                                <th style="text-align:right">Backorder</th>
                                <th style="text-align:right">COGS</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->lines as $line)
                            @php
                                $rem  = $line->ordered_qty - $line->delivered_qty;
                                $linePct = $line->ordered_qty > 0
                                    ? round(($line->delivered_qty / $line->ordered_qty) * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td><span class="text-mono badge b-gray" style="font-size:11px">{{ $line->variant_sku }}</span></td>
                                <td style="font-weight:600">{{ $line->product?->name ?? '—' }}</td>
                                <td style="text-align:right;font-weight:700">{{ number_format($line->ordered_qty,0) }}</td>
                                <td style="text-align:right;color:#059669;font-weight:700">{{ number_format($line->delivered_qty,0) }}</td>
                                <td style="text-align:right">
                                    @if($rem > 0)
                                        <span style="color:#D97706;font-weight:700">{{ number_format($rem,0) }}</span>
                                        <span class="badge b-amber" style="font-size:9px;margin-left:3px">BO</span>
                                    @else
                                        <span style="color:#B8B2A6">—</span>
                                    @endif
                                </td>
                                <td style="text-align:right;font-weight:600">
                                    @if(($line->subtotal_cogs ?? 0) > 0)
                                        Rp{{ number_format($line->subtotal_cogs, 0, ',', '.') }}
                                    @else —
                                    @endif
                                </td>
                                <td style="width:100px">
                                    <div class="credit-bar" style="margin-bottom:3px">
                                        <div class="credit-fill" style="width:{{ $linePct }}%;background:{{ $linePct == 100 ? '#059669' : '#C6A76B' }}"></div>
                                    </div>
                                    <div style="font-size:10.5px;color:#8A8478">{{ $linePct }}%</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#FAFAF9;border-top:2px solid #E8E4DA">
                            <tr>
                                <td colspan="2" style="padding:8px 16px;font-weight:700;font-size:11.5px;color:#6B6560;text-transform:uppercase">Total</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700">{{ number_format($totalOrdered,0) }}</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#059669">{{ number_format($totalDelivered,0) }}</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#D97706">{{ $totalRemaining > 0 ? number_format($totalRemaining,0) : '—' }}</td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700">
                                    Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── F-09: COGS Journal Entry ─────────────────────────────── --}}
            @if($delivery->is_posted && $delivery->cost_of_goods_sold > 0)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Journal Entry — COGS</span>
                    <span class="badge b-forest">Posted · SR-11, SR-20</span>
                </div>
                <div class="card-body">
                    <div style="font-size:12px;color:#8A8478;margin-bottom:12px">
                        Delivery Done menghasilkan satu set jurnal: DR HPP / CR Persediaan. Debit = Credit (SR-20). Tidak dapat diposting ulang.
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
                                <td><span class="badge b-red">DR</span></td>
                                <td class="text-mono">5010</td>
                                <td>Harga Pokok Penjualan</td>
                                <td style="text-align:right;font-weight:700;color:#DC2626">
                                    Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;color:#B8B2A6">—</td>
                            </tr>
                            <tr>
                                <td><span class="badge b-gray">CR</span></td>
                                <td class="text-mono">1200</td>
                                <td>Persediaan</td>
                                <td style="text-align:right;color:#B8B2A6">—</td>
                                <td style="text-align:right;font-weight:700;color:#059669">
                                    Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot style="background:#FAFAF9;border-top:1px solid #E8E4DA">
                            <tr>
                                <td colspan="3" style="padding:8px 16px;font-weight:700;font-size:11.5px;color:#6B6560">
                                    Total (Debit = Kredit · SR-20)
                                </td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#DC2626">
                                    Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;padding:8px 16px;font-weight:700;color:#059669">
                                    Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── F-09: Backorder Chain ────────────────────────────────── --}}
            @if($delivery->backorder_delivery_id)
            <div style="border:1px solid #FDE68A;border-left:4px solid #D97706;border-radius:8px;overflow:hidden">
                <div style="background:#FFFBEB;padding:12px 16px;display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#78350F;display:flex;align-items:center;gap:8px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Backorder Dibuat — F-09
                        </div>
                        <div style="font-size:12px;color:#92400E;margin-top:3px">
                            {{ number_format($totalRemaining, 0) }} unit sisa dilanjutkan ke delivery baru · SR-10: Reserved dipertahankan
                        </div>
                    </div>
                    <a href="{{ route('inventory.delivery.show', $delivery->backorder_delivery_id) }}"
                       class="btn btn-outline btn-sm" style="border-color:#FDE68A;color:#78350F;flex-shrink:0">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        Buka {{ $delivery->backorder_delivery_id }}
                    </a>
                </div>
                {{-- Visual chain --}}
                <div style="background:white;padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:12.5px">
                    <div style="padding:6px 12px;background:#F0FDF4;border:1px solid #A7F3D0;border-radius:6px;font-family:monospace;font-weight:700;color:#059669">
                        {{ $delivery->delivery_id }}
                        <span style="font-family:sans-serif;font-weight:400;color:#6B6560;margin-left:6px">Done ✓</span>
                    </div>
                    <svg width="20" height="14" viewBox="0 0 24 14" fill="none" style="flex-shrink:0"><path d="M0 7h20M14 1l6 6-6 6" stroke="#D97706" stroke-width="2" stroke-linecap="round"/></svg>
                    <div style="padding:6px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;font-family:monospace;font-weight:700;color:#D97706">
                        {{ $delivery->backorder_delivery_id }}
                        <span style="font-family:sans-serif;font-weight:400;color:#6B6560;margin-left:6px">Backorder</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Link balik: jika DO ini adalah backorder dari DO lain --}}
            @php
                $parentDO = \App\Models\DeliveryOrder::where('backorder_delivery_id', $delivery->delivery_id)->first();
            @endphp
            @if($parentDO)
            <div style="background:#F0F9FF;border:1px solid #BFDBFE;border-left:4px solid #3B82F6;border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-weight:700;font-size:13px;color:#1E40AF">
                        Ini adalah Backorder dari {{ $parentDO->delivery_id }}
                    </div>
                    <div style="font-size:12px;color:#1D4ED8;margin-top:2px">
                        SO asal: {{ $parentDO->sales_order_id }} · Klik untuk melihat pengiriman sebelumnya
                    </div>
                </div>
                <a href="{{ route('inventory.delivery.show', $parentDO->delivery_id) }}"
                   class="btn btn-outline btn-sm" style="border-color:#BFDBFE;color:#1E40AF;flex-shrink:0">
                    ← {{ $parentDO->delivery_id }}
                </a>
            </div>
            @endif

        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- DO Info --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi DO</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="section-title">Delivery ID</div>
                        <div class="text-mono" style="font-weight:700;color:#18382E">{{ $delivery->delivery_id }}</div>
                    </div>
                    <div>
                        <div class="section-title">Sales Order</div>
                        @if($delivery->salesOrder)
                        <a href="{{ route('sales.orders.show', $delivery->sales_order_id) }}"
                           class="text-mono" style="color:#18382E;font-weight:600;text-decoration:none">
                            {{ $delivery->sales_order_id }}
                        </a>
                        @else
                        <span style="color:#B8B2A6">—</span>
                        @endif
                    </div>
                    <div>
                        <div class="section-title">Pelanggan</div>
                        <div style="font-weight:600">{{ $delivery->salesOrder?->customer?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title">Tanggal Pengiriman</div>
                        <div>{{ $delivery->delivery_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title">COGS Total</div>
                        <div style="font-size:16px;font-weight:700;color:{{ $delivery->cost_of_goods_sold > 0 ? '#DC2626' : '#B8B2A6' }}">
                            Rp{{ number_format($delivery->cost_of_goods_sold, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- F-09 Delivery Progress KPI --}}
            @php
                $totOrd  = $delivery->lines->sum('ordered_qty');
                $totDel  = $delivery->lines->sum('delivered_qty');
                $totRem  = $totOrd - $totDel;
                $pctDone = $totOrd > 0 ? round(($totDel / $totOrd) * 100) : 0;
            @endphp
            <div class="card">
                <div class="card-header"><span class="card-title">F-09 · Progress</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div>
                        <div class="credit-bar" style="margin-bottom:6px">
                            <div class="credit-fill" style="width:{{ $pctDone }}%;background:{{ $pctDone == 100 ? '#059669' : '#C6A76B' }}"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:11.5px">
                            <span style="color:#059669;font-weight:700">{{ number_format($totDel,0) }} terkirim</span>
                            <span style="font-weight:700">{{ $pctDone }}%</span>
                        </div>
                    </div>
                    @if($totRem > 0)
                    <div style="background:#FFFBEB;border-radius:6px;padding:8px 10px;font-size:12px;color:#78350F">
                        <strong>{{ number_format($totRem,0) }} unit</strong> belum terkirim
                        @if($delivery->backorder_delivery_id)
                            <br>→ Backorder: <span class="text-mono" style="color:#D97706">{{ $delivery->backorder_delivery_id }}</span>
                        @endif
                    </div>
                    @endif
                    <div>
                        <div style="font-size:11px;color:#8A8478;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">SR-02</div>
                        <div style="font-size:11.5px;color:#6B6560;line-height:1.5">Invoice hanya bisa dibuat untuk qty yang sudah Delivered, bukan Ordered.</div>
                    </div>
                </div>
            </div>

            {{-- Business Rules --}}
            <div class="card" style="border-left:3px solid #C6A76B">
                <div class="card-header"><span class="card-title">Aturan Bisnis</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div>
                        <span class="badge b-amber">SR-09</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">Stok negatif ditolak. Available harus ≥ qty kirim.</p>
                    </div>
                    <div>
                        <span class="badge b-amber">SR-10</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">Reserved dikurangi saat barang benar-benar dikirim.</p>
                    </div>
                    <div>
                        <span class="badge b-amber">SR-11</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">COGS dicatat satu kali saat Done.</p>
                    </div>
                    <div>
                        <span class="badge b-amber">SR-20</span>
                        <p style="font-size:11.5px;color:#6B6560;margin:4px 0 0 0;line-height:1.5">Validasi ulang ditolak — idempotent posting.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
const lineData = {};
@foreach($delivery->lines as $line)
lineData[{{ $line->id }}] = { std_cost: {{ $line->standard_cost ?? 0 }} };
@endforeach

function recalcCogs(lineId, stdCost, qty) {
    const cogs = Math.round(parseFloat(qty || 0) * stdCost);
    document.getElementById('cogs-' + lineId).textContent =
        'Rp' + cogs.toLocaleString('id-ID');
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('[id^="cogs-"]').forEach(el => {
        const v = el.textContent.replace(/[^0-9]/g,'');
        total += parseInt(v) || 0;
    });
    const el = document.getElementById('total-cogs');
    if (el) el.textContent = 'Rp' + total.toLocaleString('id-ID');
}
</script>
@endpush
@endsection
