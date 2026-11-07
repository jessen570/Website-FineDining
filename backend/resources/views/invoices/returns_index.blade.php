@extends('layouts.app')
@section('title', 'Retur Penjualan & Credit Note — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item">Keuangan</span>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Retur & Credit Note</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Retur Penjualan & Credit Note</h1>
            <p class="page-desc">F-19 · SR-17 · SR-20 — Retur membalik stock move, HPP, dan piutang; Credit Note diterbitkan otomatis</p>
        </div>
        <a href="{{ route('returns.create') }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Buat Retur Baru
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ session('error') }}</div>
    @endif

    {{-- KPI Cards --}}
    @php
        $totalRet   = \App\Models\ReturnOrder::count();
        $draftRet   = \App\Models\ReturnOrder::where('status','Draft')->count();
        $postedRet  = \App\Models\ReturnOrder::where('is_posted', true)->count();
        $totalCN    = \App\Models\ReturnOrder::where('is_posted', true)->sum('total_credit_note');
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi">
            <div class="kpi-divider bg-[#18382E]"></div>
            <div class="kpi-label">Total Retur</div>
            <div class="kpi-value">{{ number_format($totalRet) }}</div>
            <div class="kpi-sub">Semua dokumen</div>
            <div class="kpi-icon bg-stone-100 text-stone-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-amber-500"></div>
            <div class="kpi-label">Draft</div>
            <div class="kpi-value text-amber-700">{{ number_format($draftRet) }}</div>
            <div class="kpi-sub">Menunggu validasi</div>
            <div class="kpi-icon bg-amber-50 text-amber-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-emerald-500"></div>
            <div class="kpi-label">Posted / Validated</div>
            <div class="kpi-value text-emerald-700">{{ number_format($postedRet) }}</div>
            <div class="kpi-sub">Stok & jurnal dibalik</div>
            <div class="kpi-icon bg-emerald-50 text-emerald-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-rose-500"></div>
            <div class="kpi-label">Total Credit Note</div>
            <div class="kpi-value text-rose-700" style="font-size:1.1rem">Rp {{ number_format($totalCN, 0, ',', '.') }}</div>
            <div class="kpi-sub">Nilai piutang dibalik</div>
            <div class="kpi-icon bg-rose-50 text-rose-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($returns->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg></div>
                <div class="empty-title">Belum ada retur</div>
                <div class="empty-text">Retur penjualan akan muncul di sini. Retur terkait ke Delivered Quantity dan Posted Invoice, lalu menerbitkan Credit Note.</div>
                <a href="{{ route('returns.create') }}" class="btn btn-primary btn-sm">Buat Retur Pertama</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Return ID</th>
                            <th>Invoice Asal</th>
                            <th>Customer</th>
                            <th>SKU Produk</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Credit Subtotal</th>
                            <th class="text-right">PBJT Reversal</th>
                            <th class="text-right">Total Credit Note</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returns as $ret)
                        @php
                            $retBadge = match($ret->status) {
                                'Draft'     => 'b-amber',
                                'Validated' => 'b-blue',
                                'Posted'    => 'b-emerald',
                                'Cancelled' => 'b-gray',
                                default     => 'b-gray',
                            };
                        @endphp
                        <tr>
                            <td class="col-id">
                                <a href="{{ route('returns.show', $ret->return_id) }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] font-semibold hover:underline">
                                    {{ $ret->return_id }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('invoices.show', $ret->original_invoice_id) }}" class="text-sm text-blue-700 hover:underline font-mono">
                                    {{ $ret->original_invoice_id }}
                                </a>
                            </td>
                            <td>
                                <div class="font-medium text-stone-800 text-sm">{{ $ret->customer?->name ?? '—' }}</div>
                                <div class="text-xs text-stone-400">{{ $ret->customer_id }}</div>
                            </td>
                            <td>
                                <div class="font-mono text-sm text-stone-700">{{ $ret->variant_sku }}</div>
                                <div class="text-xs text-stone-400">{{ $ret->product?->name ?? '—' }}</div>
                            </td>
                            <td class="text-right font-mono text-sm">{{ number_format($ret->quantity, 0) }}</td>
                            <td class="text-right font-mono text-sm text-stone-700">Rp {{ number_format($ret->credit_subtotal, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-sm text-[#C6A76B]">Rp {{ number_format($ret->pbjt_reversal, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-sm font-semibold text-rose-700">Rp {{ number_format($ret->total_credit_note, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $retBadge }}">{{ $ret->status }}</span></td>
                            <td class="text-center col-actions">
                                <a href="{{ route('returns.show', $ret->return_id) }}" class="btn btn-outline btn-xs">Lihat</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($returns->hasPages())
                <div class="px-4 py-3 border-t border-stone-100">{{ $returns->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Business Rule Note --}}
    <div class="card card-body text-xs text-stone-500 space-y-1">
        <p><strong class="text-stone-700">SR-17:</strong> Return terikat ke Delivered Quantity serta Posted Invoice; diterima satu kali, menaikkan stok, membalik COGS dan menerbitkan Credit Note sesuai quantity.</p>
        <p><strong class="text-stone-700">SR-20:</strong> Credit Note yang sama tidak boleh diposting dua kali — sistem memblokir duplikasi secara otomatis.</p>
    </div>

</div>
@endsection
