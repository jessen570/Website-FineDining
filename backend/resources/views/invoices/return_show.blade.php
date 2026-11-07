@extends('layouts.app')
@section('title', $return->return_id . ' — Retur Penjualan')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('returns.index') }}" class="breadcrumb-item">Retur & Credit Note</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">{{ $return->return_id }}</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Header + Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-stone-200">
        <div>
            <div class="flex items-center gap-3 flex-wrap mb-1">
                <h1 class="text-2xl font-bold text-stone-900">{{ $return->return_id }}</h1>
                @php
                    $retBadge = match($return->status) {
                        'Draft'     => 'b-amber',
                        'Validated' => 'b-blue',
                        'Posted'    => 'b-emerald',
                        'Cancelled' => 'b-gray',
                        default     => 'b-gray',
                    };
                @endphp
                <span class="badge {{ $retBadge }} text-sm">{{ $return->status }}</span>
                @if($return->is_posted)
                    <span class="badge b-emerald text-xs">✓ Posted</span>
                @endif
            </div>
            <p class="text-sm text-stone-500">F-19 · SR-17 · SR-20 — Return & Credit Note</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($return->status === 'Draft' && !$return->is_posted)
                <form action="{{ route('returns.validate', $return->return_id) }}" method="POST"
                      onsubmit="return confirm('Validasi retur ini? Tindakan ini akan membalik stok, COGS, dan piutang serta menerbitkan Credit Note. Tidak dapat dibatalkan.')">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Validasi & Post Retur
                    </button>
                </form>
            @endif
            <a href="{{ route('returns.index') }}" class="btn btn-outline btn-sm">Kembali</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ session('error') }}</div>
    @endif

    {{-- Smart Buttons (F-22) --}}
    <div class="card card-body flex items-center gap-2 flex-wrap">
        <span class="text-xs font-bold uppercase tracking-wider text-stone-400 mr-2">Dokumen Terkait:</span>
        <a href="{{ route('invoices.show', $return->original_invoice_id) }}"
           class="inline-flex items-center gap-2 px-3 py-1.5 bg-stone-50 hover:bg-stone-100 border border-stone-200 rounded text-xs text-stone-600 hover:text-[#18382E] transition">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
            Invoice Asal: <strong>{{ $return->original_invoice_id }}</strong>
        </a>
        @if($return->is_posted)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded text-xs text-emerald-700">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Credit Note Diterbitkan ✓
            </span>
        @endif
    </div>

    {{-- Status Pipeline --}}
    <div class="card card-body">
        <div class="section-title">Status Dokumen</div>
        <div class="status-pipeline">
            @php $steps = ['Draft','Validated','Posted']; $cur = array_search($return->status, $steps); @endphp
            @foreach($steps as $i => $step)
            <div class="pipeline-step {{ $i < $cur ? 'done' : ($i == $cur ? 'active' : '') }}">
                <div class="pipeline-dot">
                    @if($i < $cur)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>@else{{ $i + 1 }}@endif
                </div>
                <div class="pipeline-label">{{ $step }}</div>
            </div>
            @endforeach
        </div>
        @if($return->status === 'Draft')
            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-2">
                <strong>SR-17:</strong> Klik "Validasi & Post Retur" untuk menyelesaikan proses. Stok akan bertambah, COGS dibalik, dan Credit Note diterbitkan secara otomatis.
            </p>
        @elseif($return->is_posted)
            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded p-2 mt-2">
                <strong>SR-17 ✓:</strong> Retur telah divalidasi. Stok dikembalikan, COGS dibalik, dan Credit Note sudah diterbitkan.
            </p>
        @endif
    </div>

    {{-- Main Info Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card card-body space-y-3 text-sm">
            <div class="section-title">Informasi Retur</div>
            <div class="flex justify-between border-b border-stone-100 pb-2">
                <span class="text-stone-500">Return ID</span>
                <span class="font-mono font-semibold text-[#18382E]">{{ $return->return_id }}</span>
            </div>
            <div class="flex justify-between border-b border-stone-100 pb-2">
                <span class="text-stone-500">Invoice Asal</span>
                <a href="{{ route('invoices.show', $return->original_invoice_id) }}" class="font-mono text-blue-700 hover:underline">{{ $return->original_invoice_id }}</a>
            </div>
            <div class="flex justify-between border-b border-stone-100 pb-2">
                <span class="text-stone-500">Pelanggan</span>
                <span class="font-medium">{{ $return->customer?->name ?? $return->customer_id }}</span>
            </div>
            <div class="flex justify-between border-b border-stone-100 pb-2">
                <span class="text-stone-500">Produk (SKU)</span>
                <div class="text-right">
                    <div class="font-mono">{{ $return->variant_sku }}</div>
                    <div class="text-xs text-stone-400">{{ $return->product?->name ?? '—' }}</div>
                </div>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500">Kuantitas Diretur</span>
                <span class="font-bold text-lg">{{ number_format($return->quantity, 0) }} unit</span>
            </div>
        </div>

        <div class="card card-body space-y-3 text-sm">
            <div class="section-title">Detail Alasan</div>
            <div class="bg-stone-50 border border-stone-200 rounded-lg p-3 text-stone-700 leading-relaxed">
                {{ $return->reason ?? '—' }}
            </div>
            @if($return->is_posted)
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                <div class="text-xs font-bold text-emerald-700 mb-1">✓ Dampak yang Sudah Diterapkan:</div>
                <ul class="text-xs text-emerald-700 space-y-0.5">
                    <li>• Stok produk <strong>+{{ number_format($return->quantity, 0) }} unit</strong> dikembalikan ke gudang</li>
                    <li>• COGS dibalik: <strong class="font-mono">Rp {{ number_format($return->cogs_reversal, 0, ',', '.') }}</strong></li>
                    <li>• Credit Note diterbitkan: <strong class="font-mono">Rp {{ number_format($return->total_credit_note, 0, ',', '.') }}</strong></li>
                </ul>
            </div>
            @endif
        </div>
    </div>

    {{-- Credit Note Breakdown --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title">Rincian Credit Note (S-17)</span>
            <span class="badge b-rose">Nilai Kredit Diterbitkan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Keterangan</th>
                        <th class="text-right">Jumlah</th>
                        <th>Akun GL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Credit Subtotal</td>
                        <td class="text-stone-500 text-xs">Pembalikan pendapatan penjualan</td>
                        <td class="text-right font-mono font-semibold">Rp {{ number_format($return->credit_subtotal, 0, ',', '.') }}</td>
                        <td class="text-xs font-mono text-stone-400">4020 — Retur Penjualan</td>
                    </tr>
                    <tr>
                        <td class="font-medium">PBJT Reversal</td>
                        <td class="text-stone-500 text-xs">10% dari credit subtotal — pajak dibalik</td>
                        <td class="text-right font-mono text-[#C6A76B]">Rp {{ number_format($return->pbjt_reversal, 0, ',', '.') }}</td>
                        <td class="text-xs font-mono text-stone-400">2020 — Utang PBJT</td>
                    </tr>
                    <tr class="bg-rose-50">
                        <td class="font-bold text-rose-700">Total Credit Note</td>
                        <td class="text-xs text-rose-600">Pengurangan Piutang Usaha (AR)</td>
                        <td class="text-right font-mono font-bold text-rose-700 text-base">Rp {{ number_format($return->total_credit_note, 0, ',', '.') }}</td>
                        <td class="text-xs font-mono text-stone-400">1100 — Piutang Usaha (CR)</td>
                    </tr>
                    <tr class="bg-stone-50">
                        <td class="font-medium text-stone-600">COGS Reversal</td>
                        <td class="text-stone-500 text-xs">qty × standard_cost — HPP dibalik</td>
                        <td class="text-right font-mono text-stone-600">Rp {{ number_format($return->cogs_reversal, 0, ',', '.') }}</td>
                        <td class="text-xs font-mono text-stone-400">1200 DR / 5010 CR</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-[#18382E]/5">
                        <td colspan="2" class="font-bold text-xs uppercase tracking-wider text-stone-500">Total Dampak Jurnal (Debit = Credit)</td>
                        @php
                            $totalJE = $return->total_credit_note + $return->cogs_reversal;
                        @endphp
                        <td class="text-right font-mono font-bold text-[#18382E]">Rp {{ number_format($totalJE, 0, ',', '.') }}</td>
                        <td class="text-xs text-emerald-700">✓ Seimbang (SR-20)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SR-20 Warning if already posted --}}
    @if($return->is_posted)
    <div class="flex items-start gap-3 p-4 bg-stone-50 border border-stone-200 rounded-lg text-sm text-stone-600">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5 text-stone-400"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <div>
            <strong class="text-stone-700">SR-17 + SR-20 Enforced:</strong> Retur ini sudah divalidasi dan diposting.
            Posting ulang untuk retur yang sama akan ditolak sistem untuk mencegah duplikasi jurnal.
        </div>
    </div>
    @endif

</div>
@endsection
