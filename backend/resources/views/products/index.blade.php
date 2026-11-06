@extends('layouts.app')

@section('title', 'Menu & Produk')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Menu & Produk</span>
@endsection

@section('content')
<div style="max-width:1200px">

    {{-- ── Page Header ─────────────────────────────────────────────── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Menu & Produk</h1>
            <p class="page-desc">Katalog SKU, harga jual, standard cost, dan stok per lokasi · F-03</p>
        </div>
        @if(in_array(auth()->user()->role, ['owner','warehouse','procurement']))
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Produk
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="flash-success" style="margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── KPI Summary ─────────────────────────────────────────────── --}}
    @php
        $totalProducts  = $products->total();
        $foodCount      = \App\Models\Product::where('variant_sku','like','FD-%')->count();
        $bevCount       = \App\Models\Product::where('variant_sku','like','BV-%')->count();
        $totalStockVal  = \App\Models\StockLevel::all()->reduce(fn($c,$sl) =>
            $c + ($sl->on_hand * optional(\App\Models\Product::find($sl->variant_sku))->standard_cost ?? 0), 0);
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
        <div class="kpi" style="border-left:3px solid #18382E">
            <div class="kpi-label">Total SKU</div>
            <div class="kpi-value">{{ $totalProducts }}</div>
            <div class="kpi-sub">item terdaftar</div>
        </div>
        <div class="kpi" style="border-left:3px solid #059669">
            <div class="kpi-label">Makanan (FD-)</div>
            <div class="kpi-value" style="color:#059669">{{ $foodCount }}</div>
            <div class="kpi-sub">menu dapur</div>
        </div>
        <div class="kpi" style="border-left:3px solid #3B82F6">
            <div class="kpi-label">Minuman (BV-)</div>
            <div class="kpi-value" style="color:#3B82F6">{{ $bevCount }}</div>
            <div class="kpi-sub">menu bar</div>
        </div>
        <div class="kpi" style="border-left:3px solid #C6A76B">
            <div class="kpi-label">Nilai Persediaan</div>
            <div class="kpi-value" style="font-size:1.1rem">Rp{{ number_format($totalStockVal/1000,0,',','.')  }}K</div>
            <div class="kpi-sub">standard cost × on hand</div>
        </div>
    </div>

    {{-- ── Toolbar ──────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('products.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B8B2A6" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau SKU...">
        </div>
        <select name="category" class="toolbar-select" onchange="this.form.submit()">
            <option value="" {{ !request('category') ? 'selected' : '' }}>Semua Kategori</option>
            <option value="food"     {{ request('category')=='food'     ? 'selected' : '' }}>Makanan (FD-)</option>
            <option value="beverage" {{ request('category')=='beverage' ? 'selected' : '' }}>Minuman (BV-)</option>
        </select>
        <button type="submit" class="btn btn-outline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Cari
        </button>
        @if(request('search') || request('category'))
            <a href="{{ route('products.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </div>
    </form>

    {{-- ── Product Table ────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daftar Produk</span>
            <span class="badge b-gray">{{ $products->total() }} item</span>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Ukuran / Varian</th>
                        <th>Satuan</th>
                        <th style="text-align:right">Harga Jual</th>
                        <th style="text-align:right">Standard Cost</th>
                        <th style="text-align:right">On Hand</th>
                        <th style="text-align:right">Available</th>
                        <th>Lokasi Utama</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    @php
                        $totalOnHand  = $product->stockLevels->sum('on_hand');
                        $totalReserved= $product->stockLevels->sum('reserved');
                        $totalAvail   = $totalOnHand - $totalReserved;
                        $isFood       = str_starts_with($product->variant_sku, 'FD-');
                        $primaryLoc   = $isFood ? 'KITCH-JKT' : 'BAR-JKT';
                    @endphp
                    <tr>
                        <td>
                            <span class="badge {{ $isFood ? 'b-green' : 'b-blue' }} text-mono">{{ $product->variant_sku }}</span>
                        </td>
                        <td class="col-name">
                            <a href="{{ route('products.show', $product->variant_sku) }}">{{ $product->name }}</a>
                        </td>
                        <td style="color:#6B6560;font-size:12.5px">{{ $product->size }} / {{ $product->color }}</td>
                        <td style="color:#6B6560">{{ $product->unit }}</td>
                        <td style="text-align:right;font-weight:600">Rp{{ number_format($product->sales_price, 0, ',', '.') }}</td>
                        <td style="text-align:right;color:#C6A76B;font-weight:600">Rp{{ number_format($product->standard_cost, 0, ',', '.') }}</td>
                        <td style="text-align:right;font-weight:700">{{ number_format($totalOnHand, 0) }}</td>
                        <td style="text-align:right">
                            <span style="font-weight:700;color:{{ $totalAvail > 0 ? '#059669' : '#DC2626' }}">
                                {{ number_format($totalAvail, 0) }}
                            </span>
                            @if($totalReserved > 0)
                                <div style="font-size:10.5px;color:#D97706">{{ number_format($totalReserved,0) }} reserved</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge b-gray text-mono" style="font-size:10px">{{ $primaryLoc }}</span>
                        </td>
                        <td class="col-actions">
                            <a href="{{ route('products.show', $product->variant_sku) }}" class="btn btn-ghost btn-xs">Detail</a>
                            @if(in_array(auth()->user()->role, ['owner','warehouse','procurement']))
                            <a href="{{ route('products.edit', $product->variant_sku) }}" class="btn btn-ghost btn-xs">Edit</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L6 6H3m4 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                                </div>
                                <div class="empty-title">Tidak ada produk ditemukan</div>
                                <div class="empty-text">Coba ubah filter pencarian atau tambahkan produk baru.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:12.5px;color:#8A8478">
                Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }} dari {{ $products->total() }} produk
            </div>
            {{ $products->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
