@extends('layouts.app')

@section('title', $product->name . ' — Produk')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('products.index') }}">Menu & Produk</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $product->variant_sku }}</span>
@endsection

@section('content')
<div style="max-width:1100px">

    {{-- ── Page Header ─────────────────────────────────────────────── --}}
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('products.index') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
            <div>
                <div style="display:flex;align-items:center;gap:10px">
                    <h1 class="page-title">{{ $product->name }}</h1>
                    @php
                        $isFood = str_starts_with($product->variant_sku, 'FD-');
                    @endphp
                    <span class="badge {{ $isFood ? 'b-green' : 'b-blue' }}">
                        {{ $isFood ? 'Makanan' : 'Minuman' }}
                    </span>
                </div>
                <p class="page-desc">SKU: {{ $product->variant_sku }} · {{ $product->size }} / {{ $product->color }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('products.edit', $product->variant_sku) }}" class="btn btn-outline">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Produk
            </a>
        </div>
    </div>

    {{-- ── Flash ───────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="flash-success" style="margin-bottom:20px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

        {{-- ── Left: Detail & Stok ────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- Product Info Card --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informasi Produk</span>
                    <span class="badge b-forest">F-03 · SR-09, SR-10</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
                        <div>
                            <div class="section-title">Internal Reference (SKU)</div>
                            <div style="font-family:monospace;font-size:16px;font-weight:700;color:#18382E">{{ $product->variant_sku }}</div>
                        </div>
                        <div>
                            <div class="section-title">Ukuran / Varian</div>
                            <div style="font-size:14px;font-weight:600;color:#1E1C1A">{{ $product->size }} / {{ $product->color }}</div>
                        </div>
                        <div>
                            <div class="section-title">Satuan</div>
                            <div style="font-size:14px;font-weight:600;color:#1E1C1A">{{ $product->unit }}</div>
                        </div>
                        <div>
                            <div class="section-title">Harga Jual (excl. PBJT)</div>
                            <div style="font-size:18px;font-weight:700;color:#18382E">Rp{{ number_format($product->sales_price, 0, ',', '.') }}</div>
                            <div style="font-size:11px;color:#8A8478;margin-top:2px">+PBJT 10% = Rp{{ number_format($product->sales_price * 1.10, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="section-title">Standard Cost</div>
                            <div style="font-size:18px;font-weight:700;color:#C6A76B">Rp{{ number_format($product->standard_cost, 0, ',', '.') }}</div>
                            <div style="font-size:11px;color:#8A8478;margin-top:2px">Dasar COGS saat Delivery Done</div>
                        </div>
                        <div>
                            <div class="section-title">Gross Margin</div>
                            @php
                                $margin = $product->sales_price > 0
                                    ? round((($product->sales_price - $product->standard_cost) / $product->sales_price) * 100, 1)
                                    : 0;
                            @endphp
                            <div style="font-size:18px;font-weight:700;color:{{ $margin >= 50 ? '#059669' : ($margin >= 30 ? '#D97706' : '#DC2626') }}">{{ $margin }}%</div>
                            <div style="font-size:11px;color:#8A8478;margin-top:2px">Rp{{ number_format($product->sales_price - $product->standard_cost, 0, ',', '.') }} per unit</div>
                        </div>
                    </div>

                    @if($product->default_vendor_id)
                    <hr class="divider">
                    <div>
                        <div class="section-title">Vendor Default</div>
                        <div style="font-size:13.5px;font-weight:600;color:#1E1C1A">{{ $product->default_vendor_id }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Stock per Location Card - SR-09, SR-10 --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <span class="card-title">Stok per Lokasi</span>
                        <div style="font-size:11px;color:#8A8478;margin-top:2px">Available = On Hand − Reserved · SR-09, SR-10</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span class="badge b-gold">Total On Hand: {{ number_format($product->total_on_hand, 0) }} {{ $product->unit }}</span>
                    </div>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th>Kode Lokasi</th>
                                <th style="text-align:right">On Hand</th>
                                <th style="text-align:right">Reserved</th>
                                <th style="text-align:right">Available</th>
                                <th style="text-align:right">Nilai (Std Cost)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->stockLevels as $sl)
                            <tr>
                                <td>
                                    <div style="font-weight:600;color:#1E1C1A">{{ $sl->location->name ?? $sl->location_code }}</div>
                                </td>
                                <td><span class="badge b-gray text-mono">{{ $sl->location_code }}</span></td>
                                <td style="text-align:right;font-weight:700;color:#1E1C1A">{{ number_format($sl->on_hand, 0) }}</td>
                                <td style="text-align:right">
                                    @if($sl->reserved > 0)
                                        <span style="color:#D97706;font-weight:600">{{ number_format($sl->reserved, 0) }}</span>
                                    @else
                                        <span style="color:#B8B2A6">0</span>
                                    @endif
                                </td>
                                <td style="text-align:right">
                                    <span style="font-weight:700;color:{{ $sl->available > 0 ? '#059669' : '#DC2626' }};font-size:15px">
                                        {{ number_format($sl->available, 0) }}
                                    </span>
                                </td>
                                <td style="text-align:right;color:#6B6560">
                                    Rp{{ number_format($sl->on_hand * $product->standard_cost, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state" style="padding:24px">
                                        <div class="empty-text">Belum ada data stok untuk produk ini.</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($product->stockLevels->count() > 1)
                        <tfoot style="background:#F7F5F0;border-top:2px solid #E8E4DA">
                            <tr>
                                <td colspan="2" style="padding:10px 16px;font-weight:700;font-size:12px;color:#6B6560">TOTAL PERUSAHAAN</td>
                                <td style="text-align:right;padding:10px 16px;font-weight:700;font-size:14px;color:#1E1C1A">{{ number_format($product->total_on_hand, 0) }}</td>
                                <td style="text-align:right;padding:10px 16px;font-weight:700;font-size:14px;color:#D97706">{{ number_format($product->total_reserved, 0) }}</td>
                                <td style="text-align:right;padding:10px 16px;font-weight:700;font-size:15px;color:#059669">{{ number_format($product->total_available, 0) }}</td>
                                <td style="text-align:right;padding:10px 16px;font-weight:700;color:#6B6560">
                                    Rp{{ number_format($product->total_on_hand * $product->standard_cost, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Vendor Pricelist --}}
            @if($product->pricelists->count() > 0)
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Daftar Harga Pemasok</span>
                    <span class="badge b-blue">{{ $product->pricelists->count() }} vendor</span>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vendor ID</th>
                                <th style="text-align:right">Harga Pembelian</th>
                                <th style="text-align:right">Qty Minimum</th>
                                <th>Mata Uang</th>
                                <th style="text-align:right">Selisih vs Std Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->pricelists as $pl)
                            <tr>
                                <td><span class="badge b-gray text-mono">{{ $pl->vendor_id }}</span></td>
                                <td style="text-align:right;font-weight:700">Rp{{ number_format($pl->unit_purchase_price, 0, ',', '.') }}</td>
                                <td style="text-align:right">{{ $pl->min_qty }}</td>
                                <td>{{ $pl->currency }}</td>
                                <td style="text-align:right">
                                    @php $diff = $pl->unit_purchase_price - $product->standard_cost; @endphp
                                    <span style="color:{{ $diff > 0 ? '#DC2626' : '#059669' }};font-weight:600">
                                        {{ $diff >= 0 ? '+' : '' }}Rp{{ number_format($diff, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Right Sidebar ───────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Stock Summary KPIs --}}
            <div class="kpi" style="border-left:3px solid #18382E">
                <div class="kpi-label">Total On Hand</div>
                <div class="kpi-value">{{ number_format($product->total_on_hand, 0) }}</div>
                <div class="kpi-sub">unit · semua lokasi</div>
            </div>
            <div class="kpi" style="border-left:3px solid #D97706">
                <div class="kpi-label">Reserved</div>
                <div class="kpi-value" style="color:#D97706">{{ number_format($product->total_reserved, 0) }}</div>
                <div class="kpi-sub">terkunci oleh SO Confirmed</div>
            </div>
            <div class="kpi" style="border-left:3px solid #059669">
                <div class="kpi-label">Available</div>
                <div class="kpi-value" style="color:{{ $product->total_available > 0 ? '#059669' : '#DC2626' }}">
                    {{ number_format($product->total_available, 0) }}
                </div>
                <div class="kpi-sub">siap dikirim / dipindah</div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Aksi Cepat</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                    <a href="{{ route('inventory.transfer.create') }}?sku={{ $product->variant_sku }}" class="btn btn-outline" style="justify-content:center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 9 7-7 7 7"/><path d="m5 15 7 7 7-7"/></svg>
                        Transfer Stok
                    </a>
                    <a href="{{ route('purchase.create') }}?sku={{ $product->variant_sku }}" class="btn btn-outline" style="justify-content:center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Buat RFQ
                    </a>
                </div>
            </div>

            {{-- Business Rules Info --}}
            <div class="card" style="border-left:3px solid #C6A76B">
                <div class="card-header"><span class="card-title">Aturan Bisnis Aktif</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div style="font-size:12px">
                        <span class="badge b-amber" style="margin-bottom:4px">SR-09</span>
                        <p style="color:#6B6560;margin:4px 0 0 0;font-size:11.5px;line-height:1.5">Reserve, Delivery, Transfer dilarang jika Available &lt; permintaan.</p>
                    </div>
                    <div style="font-size:12px">
                        <span class="badge b-amber" style="margin-bottom:4px">SR-10</span>
                        <p style="color:#6B6560;margin:4px 0 0 0;font-size:11.5px;line-height:1.5">SO Confirmed membuat reservasi tanpa mengurangi On Hand.</p>
                    </div>
                    <div style="font-size:12px">
                        <span class="badge b-amber" style="margin-bottom:4px">SR-11</span>
                        <p style="color:#6B6560;margin:4px 0 0 0;font-size:11.5px;line-height:1.5">Delivery Done mengurangi On Hand dan mencatat COGS satu kali.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
