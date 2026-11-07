@extends('layouts.app')
@section('title', 'Faktur & Tagihan — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item">Keuangan</span>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Faktur & Tagihan</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Faktur & Tagihan</h1>
            <p class="page-desc">Customer Invoice (F-10, SR-02, SR-12) dan Vendor Bill (F-14, SR-06) · Semua transaksi penagihan</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('returns.create') }}" class="btn btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                Buat Retur
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="flash-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>{{ session('error') }}</div>
    @endif

    {{-- KPI Cards --}}
    @php
        $allInv    = $invoices->getCollection();
        $totalAll  = \App\Models\Invoice::count();
        $draftCnt  = \App\Models\Invoice::where('status','Draft')->count();
        $postedCnt = \App\Models\Invoice::where('status','Posted')->count();
        $paidCnt   = \App\Models\Invoice::where('status','Paid')->count();
        $totalDue  = \App\Models\Invoice::whereIn('status',['Posted','Draft'])->sum('amount_due');
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi">
            <div class="kpi-divider bg-[#18382E]"></div>
            <div class="kpi-label">Total Dokumen</div>
            <div class="kpi-value">{{ number_format($totalAll) }}</div>
            <div class="kpi-sub">Invoice & Bill</div>
            <div class="kpi-icon bg-stone-100 text-stone-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-amber-500"></div>
            <div class="kpi-label">Draft</div>
            <div class="kpi-value text-amber-700">{{ number_format($draftCnt) }}</div>
            <div class="kpi-sub">Belum diposting</div>
            <div class="kpi-icon bg-amber-50 text-amber-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-blue-500"></div>
            <div class="kpi-label">Posted / Open</div>
            <div class="kpi-value text-blue-700">{{ number_format($postedCnt) }}</div>
            <div class="kpi-sub">Sisa: Rp {{ number_format($totalDue, 0, ',', '.') }}</div>
            <div class="kpi-icon bg-blue-50 text-blue-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-emerald-500"></div>
            <div class="kpi-label">Lunas (Paid)</div>
            <div class="kpi-value text-emerald-700">{{ number_format($paidCnt) }}</div>
            <div class="kpi-sub">Pembayaran selesai</div>
            <div class="kpi-icon bg-emerald-50 text-emerald-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        </div>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('invoices.index') }}" class="toolbar">
        <div class="toolbar-search" style="flex:2;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B8B2A6" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Cari ID faktur atau nama mitra…" value="{{ request('search') }}">
        </div>
        <select name="type" class="toolbar-select">
            <option value="">Semua Tipe</option>
            @foreach($types as $t)
                <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Filter
        </button>
        @if(request('search') || request('type') || request('status'))
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
        <div class="ml-auto text-xs text-stone-400">{{ $invoices->total() }} dokumen ditemukan</div>
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($invoices->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <div class="empty-title">Belum ada faktur</div>
                <div class="empty-text">Invoice dan vendor bill akan muncul di sini setelah dibuat dari Delivery Order atau Receipt.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Tipe</th>
                            <th>Mitra</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-right">Subtotal</th>
                            <th class="text-right">PBJT / Tax</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Sisa Tagihan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        @php
                            $statusBadge = match($inv->status) {
                                'Draft'     => 'b-amber',
                                'Posted'    => 'b-blue',
                                'Paid'      => 'b-emerald',
                                'Cancelled' => 'b-gray',
                                default     => 'b-gray',
                            };
                            $typeBadge = $inv->type === 'Customer Invoice' ? 'b-forest' : 'b-gold';
                        @endphp
                        <tr>
                            <td class="col-id">
                                <a href="{{ route('invoices.show', $inv->invoice_id) }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] font-semibold hover:underline">
                                    {{ $inv->invoice_id }}
                                </a>
                            </td>
                            <td><span class="badge {{ $typeBadge }} text-xs">{{ $inv->type === 'Customer Invoice' ? 'Cust. Invoice' : 'Vendor Bill' }}</span></td>
                            <td>
                                <div class="font-medium text-stone-800 text-sm">{{ $inv->partner?->name ?? '—' }}</div>
                                <div class="text-xs text-stone-400">{{ $inv->partner_id }}</div>
                            </td>
                            <td class="text-sm text-stone-600">
                                {{ $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') : '—' }}
                            </td>
                            <td><span class="badge {{ $statusBadge }}">{{ $inv->status }}</span></td>
                            <td class="text-right text-sm font-mono text-stone-700">Rp {{ number_format($inv->subtotal, 0, ',', '.') }}</td>
                            <td class="text-right text-sm font-mono text-[#C6A76B]">Rp {{ number_format($inv->pbjt_amount, 0, ',', '.') }}</td>
                            <td class="text-right text-sm font-mono font-semibold text-stone-900">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                            <td class="text-right text-sm font-mono {{ (float)$inv->amount_due > 0 ? 'text-amber-700 font-semibold' : 'text-emerald-700' }}">
                                Rp {{ number_format($inv->amount_due, 0, ',', '.') }}
                            </td>
                            <td class="text-center col-actions">
                                <a href="{{ route('invoices.show', $inv->invoice_id) }}" class="btn btn-outline btn-xs">Lihat</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="px-4 py-3 border-t border-stone-100">
                    {{ $invoices->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Business Rule Note --}}
    <div class="card card-body text-xs text-stone-500 space-y-1">
        <p><strong class="text-stone-700">SR-02:</strong> Customer Invoice hanya memuat Delivered Quantity yang sudah Done dan belum pernah ditagih.</p>
        <p><strong class="text-stone-700">SR-12:</strong> Invoice Pelanggan dikenakan PBJT 10% dari subtotal setelah diskon; dibulatkan per baris ke rupiah terdekat.</p>
        <p><strong class="text-stone-700">SR-06:</strong> Vendor Bill hanya dapat diposting maksimum sebesar Accepted Quantity yang belum ditagih pada Receipt terkait.</p>
        <p><strong class="text-stone-700">SR-16:</strong> Pembayaran tidak boleh melebihi sisa tagihan (Amount Due). Status Paid hanya jika sisa nol.</p>
    </div>

</div>
@endsection
