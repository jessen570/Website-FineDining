@extends('layouts.app')

@section('title', 'Persediaan Stok')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Persediaan Stok</span>
@endsection

@section('content')
<div style="max-width:1200px">

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Persediaan Stok</h1>
            <p class="page-desc">Stock Overview per lokasi · Available = On Hand − Reserved · F-08, F-20 · SR-09, SR-10, SR-21</p>
        </div>
        <div style="display:flex;gap:8px">
            @if(in_array(auth()->user()->role, ['owner','warehouse','procurement']))
            <a href="{{ route('inventory.transfer.create') }}" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 9 7-7 7 7"/><path d="m5 15 7 7 7-7"/></svg>
                Transfer Stok
            </a>
            @endif
            <a href="{{ route('inventory.deliveries') }}" class="btn btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery Orders
            </a>
        </div>
    </div>

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

    {{-- ── KPI Summary ──────────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
        <div class="kpi" style="border-left:3px solid #18382E">
            <div class="kpi-label">Total On Hand</div>
            <div class="kpi-value">{{ number_format($totalOnHand, 0) }}</div>
            <div class="kpi-sub">unit · semua lokasi</div>
        </div>
        <div class="kpi" style="border-left:3px solid #D97706">
            <div class="kpi-label">Reserved</div>
            <div class="kpi-value" style="color:#D97706">{{ number_format($totalReserved, 0) }}</div>
            <div class="kpi-sub">dikunci oleh SO Confirmed</div>
        </div>
        <div class="kpi" style="border-left:3px solid #059669">
            <div class="kpi-label">Available</div>
            <div class="kpi-value" style="color:#059669">{{ number_format($totalAvailable, 0) }}</div>
            <div class="kpi-sub">siap kirim / transfer</div>
        </div>
        <div class="kpi" style="border-left:3px solid #C6A76B">
            <div class="kpi-label">Nilai Persediaan</div>
            <div class="kpi-value" style="font-size:1.1rem">Rp{{ number_format($totalValue/1000000, 2, ',', '.') }}M</div>
            <div class="kpi-sub">std cost × on hand</div>
        </div>
    </div>

    {{-- ── Location Filter & Search ──────────────────────────────────── --}}
    <form method="GET" action="{{ route('inventory.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:200px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B8B2A6" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SKU atau nama produk...">
        </div>
        <select name="location" class="toolbar-select" onchange="this.form.submit()">
            <option value="" {{ !request('location') ? 'selected' : '' }}>Semua Lokasi</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->location_code }}" {{ request('location') == $loc->location_code ? 'selected' : '' }}>
                    {{ $loc->location_code }} — {{ $loc->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
        @if(request('search') || request('location'))
            <a href="{{ route('inventory.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </div>
    </form>

    {{-- ── Per-Location Blocks ──────────────────────────────────────── --}}
    @php
        $grouped = $stockLevels->groupBy('location_code');
    @endphp

    @forelse($grouped as $locCode => $items)
    @php
        $locName   = $items->first()->location?->name ?? $locCode;
        $locOnHand = $items->sum('on_hand');
        $locReserved = $items->sum('reserved');
        $locAvail  = $locOnHand - $locReserved;
        $locValue  = $items->reduce(fn($c,$sl) => $c + ($sl->on_hand * ($sl->product?->standard_cost ?? 0)), 0);
        $isKitch   = $locCode === 'KITCH-JKT';
    @endphp
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:36px;height:36px;border-radius:8px;background:{{ $isKitch ? '#D1FAE5' : '#DBEAFE' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $isKitch ? '#059669' : '#2563EB' }}" stroke-width="1.8">
                        @if($isKitch)
                            <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                        @else
                            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3m8 0h3a2 2 0 0 0 2-2v-3"/>
                        @endif
                    </svg>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;color:#1E1C1A">{{ $locName }}</div>
                    <div style="font-size:11.5px;color:#8A8478;margin-top:1px">
                        <span class="badge b-gray text-mono" style="font-size:10px">{{ $locCode }}</span>
                        &nbsp;·&nbsp; {{ $items->count() }} SKU
                        &nbsp;·&nbsp; On Hand: <strong>{{ number_format($locOnHand,0) }}</strong>
                        &nbsp;·&nbsp; Available: <strong style="color:{{ $locAvail > 0 ? '#059669' : '#DC2626' }}">{{ number_format($locAvail,0) }}</strong>
                        &nbsp;·&nbsp; Nilai: <strong>Rp{{ number_format($locValue, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
            @if(in_array(auth()->user()->role, ['owner','warehouse','procurement']))
            <a href="{{ route('inventory.transfer.create') }}?from={{ $locCode }}" class="btn btn-outline btn-sm">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 9 7-7 7 7"/><path d="m5 15 7 7 7-7"/></svg>
                Transfer dari sini
            </a>
            @endif
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th style="text-align:right">On Hand</th>
                        <th style="text-align:right">Reserved</th>
                        <th style="text-align:right">Available</th>
                        <th style="text-align:right">Std Cost</th>
                        <th style="text-align:right">Nilai (On Hand)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items->sortBy('variant_sku') as $sl)
                    @php
                        $isFood = str_starts_with($sl->variant_sku, 'FD-');
                        $avail  = $sl->available;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $sl->variant_sku) }}"
                               style="font-family:monospace;font-size:12px;color:#18382E;font-weight:700;text-decoration:none">
                                {{ $sl->variant_sku }}
                            </a>
                        </td>
                        <td class="col-name">
                            <a href="{{ route('products.show', $sl->variant_sku) }}">
                                {{ $sl->product?->name ?? '—' }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $isFood ? 'b-green' : 'b-blue' }}" style="font-size:10px">
                                {{ $isFood ? 'Makanan' : 'Minuman' }}
                            </span>
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:14px">{{ number_format($sl->on_hand, 0) }}</td>
                        <td style="text-align:right">
                            @if($sl->reserved > 0)
                                <span style="color:#D97706;font-weight:600">{{ number_format($sl->reserved, 0) }}</span>
                            @else
                                <span style="color:#D6D2C9">0</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            @if($avail <= 0)
                                <span style="color:#DC2626;font-weight:700;font-size:14px">{{ number_format($avail, 0) }}</span>
                                <span class="badge b-red" style="font-size:9px;margin-left:4px">HABIS</span>
                            @elseif($avail <= 3)
                                <span style="color:#D97706;font-weight:700;font-size:14px">{{ number_format($avail, 0) }}</span>
                                <span class="badge b-amber" style="font-size:9px;margin-left:4px">RENDAH</span>
                            @else
                                <span style="color:#059669;font-weight:700;font-size:14px">{{ number_format($avail, 0) }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;color:#8A8478">
                            Rp{{ number_format($sl->product?->standard_cost ?? 0, 0, ',', '.') }}
                        </td>
                        <td style="text-align:right;font-weight:600;color:#1E1C1A">
                            Rp{{ number_format($sl->on_hand * ($sl->product?->standard_cost ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="col-actions">
                            <a href="{{ route('products.show', $sl->variant_sku) }}" class="btn btn-ghost btn-xs">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot style="background:#FAFAF9;border-top:2px solid #E8E4DA">
                    <tr>
                        <td colspan="3" style="padding:10px 16px;font-weight:700;font-size:11.5px;color:#6B6560;text-transform:uppercase;letter-spacing:.05em">
                            Subtotal {{ $locCode }}
                        </td>
                        <td style="text-align:right;padding:10px 16px;font-weight:700;font-size:14px">{{ number_format($locOnHand, 0) }}</td>
                        <td style="text-align:right;padding:10px 16px;font-weight:700;color:#D97706">{{ number_format($locReserved, 0) }}</td>
                        <td style="text-align:right;padding:10px 16px;font-weight:700;color:#059669;font-size:14px">{{ number_format($locAvail, 0) }}</td>
                        <td></td>
                        <td style="text-align:right;padding:10px 16px;font-weight:700;color:#1E1C1A">
                            Rp{{ number_format($locValue, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="empty-title">Tidak ada data stok</div>
            <div class="empty-text">Coba ubah filter atau pastikan seed data sudah dijalankan.</div>
        </div>
    </div>
    @endforelse

    {{-- ── Company Total ─────────────────────────────────────────────── --}}
    @if($grouped->count() > 1)
    <div style="background:#18382E;border-radius:10px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="color:rgba(255,255,255,0.7);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase">
            Total Perusahaan (SR-21: transfer tidak menciptakan / menghilangkan unit)
        </div>
        <div style="display:flex;gap:24px">
            <div style="text-align:center">
                <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase">On Hand</div>
                <div style="color:#fff;font-size:20px;font-weight:700">{{ number_format($totalOnHand, 0) }}</div>
            </div>
            <div style="text-align:center">
                <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase">Reserved</div>
                <div style="color:#FCD34D;font-size:20px;font-weight:700">{{ number_format($totalReserved, 0) }}</div>
            </div>
            <div style="text-align:center">
                <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase">Available</div>
                <div style="color:#6EE7B7;font-size:20px;font-weight:700">{{ number_format($totalAvailable, 0) }}</div>
            </div>
            <div style="text-align:center">
                <div style="color:rgba(255,255,255,0.5);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase">Nilai</div>
                <div style="color:#FCD34D;font-size:20px;font-weight:700">Rp{{ number_format($totalValue, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
