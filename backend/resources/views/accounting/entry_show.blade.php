@extends('layouts.app')
@section('title', $entry->entry_id . ' — Journal Entry')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('accounting.index') }}" class="breadcrumb-item">Akuntansi</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('accounting.entries') }}" class="breadcrumb-item">Semua Jurnal</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">{{ $entry->entry_id }}</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-stone-200">
        <div>
            <div class="flex items-center gap-3 flex-wrap mb-1">
                <h1 class="text-2xl font-bold text-stone-900 font-mono">{{ $entry->entry_id }}</h1>
                <span class="badge {{ $entry->is_posted ? 'b-blue' : 'b-amber' }} text-sm">
                    {{ $entry->status ?? ($entry->is_posted ? 'Posted' : 'Draft') }}
                </span>
                @php
                    $isBalanced = round((float)$entry->total_debit, 2) === round((float)$entry->total_credit, 2);
                @endphp
                @if($isBalanced)
                    <span class="badge b-forest text-xs">✓ Seimbang (SR-20)</span>
                @else
                    <span class="badge b-red text-xs">⚠ TIDAK SEIMBANG</span>
                @endif
            </div>
            <p class="text-sm text-stone-500">S-14 · F-15 · F-22 — Detail Journal Entry & Double-Entry Bookkeeping</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.entries') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Kembali ke Semua Jurnal
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>{{ session('success') }}</div>
    @endif

    {{-- Smart Buttons (F-22) --}}
    <div class="card card-body flex items-center gap-2 flex-wrap">
        <span class="text-xs font-bold uppercase tracking-wider text-stone-400 mr-2">Dokumen Sumber:</span>
        @if($entry->reference_id)
            @if(in_array($entry->reference_type, ['Customer Invoice','Vendor Bill']))
                <a href="{{ route('invoices.show', $entry->reference_id) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-stone-50 hover:bg-emerald-50 border border-stone-200 hover:border-emerald-300 rounded text-xs text-stone-600 hover:text-emerald-700 transition">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                    {{ $entry->reference_type }}: <strong>{{ $entry->reference_id }}</strong>
                </a>
            @elseif($entry->reference_type === 'Return')
                <a href="{{ route('returns.show', $entry->reference_id) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded text-xs text-rose-700 transition">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                    Return: <strong>{{ $entry->reference_id }}</strong>
                </a>
            @else
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-stone-50 border border-stone-200 rounded text-xs text-stone-600">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                    {{ $entry->reference_type }}: <strong>{{ $entry->reference_id }}</strong>
                </span>
            @endif
        @else
            <span class="text-xs text-stone-400 italic">Tidak ada dokumen sumber terkait</span>
        @endif
    </div>

    {{-- Entry Info Card --}}
    <div class="card card-body">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Entry ID</div>
                <div class="font-mono font-bold text-[#18382E]">{{ $entry->entry_id }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Tanggal</div>
                <div class="font-medium text-stone-800">
                    {{ $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('d F Y') : '—' }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Tipe Referensi</div>
                @php
                    $refBadge = match($entry->reference_type) {
                        'Opening'          => 'b-gray',
                        'Customer Invoice' => 'b-forest',
                        'Vendor Bill'      => 'b-gold',
                        'Delivery Order'   => 'b-blue',
                        'Receipt'          => 'b-blue',
                        'Payment'          => 'b-purple',
                        'Return'           => 'b-red',
                        'Credit Note'      => 'b-rose',
                        default            => 'b-gray',
                    };
                @endphp
                <span class="badge {{ $refBadge }}">{{ $entry->reference_type ?? '—' }}</span>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Reference ID</div>
                <div class="font-mono text-stone-700">{{ $entry->reference_id ?? '—' }}</div>
            </div>
        </div>
        @if($entry->description)
            <hr class="divider">
            <div>
                <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Deskripsi</div>
                <div class="text-stone-700">{{ $entry->description }}</div>
            </div>
        @endif
    </div>

    {{-- SR-20 Balance Check Banner --}}
    @if($isBalanced)
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-800">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div>
                <strong>SR-20 ✓ Seimbang:</strong> Total Debit = Total Kredit =
                <span class="font-mono font-bold">Rp {{ number_format($entry->total_debit, 0, ',', '.') }}</span>.
                Prinsip double-entry bookkeeping terpenuhi.
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-300 rounded-lg text-sm text-red-800">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <strong>⚠ SR-20 PELANGGARAN:</strong> Debit (Rp {{ number_format($entry->total_debit, 0, ',', '.') }}) ≠ Kredit (Rp {{ number_format($entry->total_credit, 0, ',', '.') }}).
                Selisih: <span class="font-mono font-bold">Rp {{ number_format(abs($entry->total_debit - $entry->total_credit), 0, ',', '.') }}</span>. Perlu investigasi segera.
            </div>
        </div>
    @endif

    {{-- Journal Items (Double-Entry) --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#18382E" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Journal Items — Double Entry
            </span>
            <span class="text-xs text-stone-400">{{ $entry->items->count() }} baris jurnal</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:36px">No</th>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Keterangan</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entry->items as $i => $item)
                    <tr>
                        <td class="text-xs text-stone-400 text-center">{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('accounting.ledger') }}?account_code={{ $item->account_code }}"
                               class="font-mono text-sm font-bold text-[#18382E] hover:text-[#C6A76B] hover:underline">
                                {{ $item->account_code }}
                            </a>
                        </td>
                        <td class="font-medium text-stone-800">{{ $item->account_name }}</td>
                        <td class="text-sm text-stone-500">{{ $item->description ?? '—' }}</td>
                        <td class="text-right font-mono {{ (float)$item->debit > 0 ? 'font-semibold text-emerald-700' : 'text-stone-300' }}">
                            {{ (float)$item->debit > 0 ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '—' }}
                        </td>
                        <td class="text-right font-mono {{ (float)$item->credit > 0 ? 'font-semibold text-red-600' : 'text-stone-300' }}">
                            {{ (float)$item->credit > 0 ? 'Rp ' . number_format($item->credit, 0, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-stone-400 py-8">Tidak ada item jurnal untuk entry ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="{{ $isBalanced ? 'bg-emerald-50' : 'bg-red-50' }} border-t-2 {{ $isBalanced ? 'border-emerald-300' : 'border-red-300' }}">
                        <td colspan="4" class="font-bold text-sm py-3">
                            @if($isBalanced)
                                <span class="flex items-center gap-2 text-emerald-700">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Total — SEIMBANG ✓ (SR-20 Compliant)
                                </span>
                            @else
                                <span class="flex items-center gap-2 text-red-700">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                                    Total — TIDAK SEIMBANG ⚠
                                </span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-bold text-emerald-700 text-base py-3">
                            Rp {{ number_format($entry->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono font-bold text-red-700 text-base py-3">
                            Rp {{ number_format($entry->total_credit, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if(!$isBalanced)
                    <tr class="bg-red-100">
                        <td colspan="4" class="text-xs text-red-700 font-semibold py-2">Selisih (harus = 0):</td>
                        <td colspan="2" class="text-right font-mono font-bold text-red-700 py-2">
                            Rp {{ number_format(abs((float)$entry->total_debit - (float)$entry->total_credit), 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SR-20 Rule Box --}}
    <div class="card card-body text-xs text-stone-500 space-y-1">
        <p><strong class="text-stone-700">SR-20:</strong> Satu peristiwa bisnis menghasilkan paling banyak satu set Journal Entry Posted; Debit = Credit per dokumen. Posting ganda tidak diperbolehkan.</p>
        <p><strong class="text-stone-700">F-22 (Audit):</strong> Smart buttons di atas menautkan jurnal ini ke dokumen bisnis asalnya untuk keperluan audit trail.</p>
        @if($entry->reference_type === 'Customer Invoice')
            <p><strong class="text-stone-700">SR-12:</strong> Customer Invoice Posted mendebit Piutang Usaha (1100) sebesar subtotal + PBJT 10%, mengkredit Pendapatan (4010) sebesar subtotal dan Utang PBJT (2020) sebesar pajak.</p>
        @elseif($entry->reference_type === 'Delivery Order')
            <p><strong class="text-stone-700">SR-11:</strong> Delivery Done mencatat COGS senilai qty × standard_cost; mengurangi Persediaan (1200) dan mendebit Harga Pokok Penjualan (5010).</p>
        @elseif($entry->reference_type === 'Return')
            <p><strong class="text-stone-700">SR-17:</strong> Return diterima membalik COGS (1200 DR / 5010 CR) dan piutang (Retur Penjualan 4020 DR / AR 1100 CR + PBJT 2020 DR).</p>
        @endif
    </div>

</div>
@endsection
