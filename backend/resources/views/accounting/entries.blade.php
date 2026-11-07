@extends('layouts.app')
@section('title', 'Semua Jurnal — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('accounting.index') }}" class="breadcrumb-item">Akuntansi</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Semua Jurnal</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Semua Journal Entries</h1>
            <p class="page-desc">S-14 · F-15 · F-22 — Riwayat seluruh posting buku besar Maison Aster</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.ledger') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Buku Besar
            </a>
            <a href="{{ route('accounting.index') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Neraca
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('accounting.entries') }}" class="toolbar">
        <select name="ref_type" class="toolbar-select">
            <option value="">Semua Tipe</option>
            @foreach($refTypes as $rt)
                <option value="{{ $rt }}" {{ request('ref_type') == $rt ? 'selected' : '' }}>{{ $rt }}</option>
            @endforeach
        </select>
        <div style="display:flex;align-items:center;gap:6px">
            <label class="text-xs text-stone-500 font-semibold whitespace-nowrap">Dari:</label>
            <input type="date" name="date_from" class="form-input" style="width:150px;padding:7px 10px" value="{{ request('date_from') }}">
        </div>
        <div style="display:flex;align-items:center;gap:6px">
            <label class="text-xs text-stone-500 font-semibold whitespace-nowrap">Sampai:</label>
            <input type="date" name="date_to" class="form-input" style="width:150px;padding:7px 10px" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        @if(request('ref_type') || request('date_from') || request('date_to'))
            <a href="{{ route('accounting.entries') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
        <div class="ml-auto text-xs text-stone-400">{{ $entries->total() }} jurnal ditemukan</div>
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($entries->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg></div>
                <div class="empty-title">Tidak ada jurnal ditemukan</div>
                <div class="empty-text">Coba ubah filter pencarian atau lihat semua jurnal tanpa filter.</div>
                <a href="{{ route('accounting.entries') }}" class="btn btn-outline btn-sm">Tampilkan Semua</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entry ID</th>
                            <th>Tanggal</th>
                            <th>Tipe Referensi</th>
                            <th>Reference ID</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Total Debit</th>
                            <th class="text-right">Total Kredit</th>
                            <th>Keseimbangan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $je)
                        @php
                            $isBalanced = round((float)$je->total_debit, 2) === round((float)$je->total_credit, 2);
                            $refBadge = match($je->reference_type) {
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
                        <tr class="{{ !$isBalanced ? 'bg-red-50' : '' }}">
                            <td class="col-id">
                                <a href="{{ route('accounting.entry.show', $je->entry_id) }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] font-semibold hover:underline">
                                    {{ $je->entry_id }}
                                </a>
                            </td>
                            <td class="text-sm text-stone-600">
                                {{ $je->entry_date ? \Carbon\Carbon::parse($je->entry_date)->format('d M Y') : '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $refBadge }} text-xs">{{ $je->reference_type }}</span>
                            </td>
                            <td class="col-id text-xs">
                                @if($je->reference_id)
                                    @if($je->reference_type === 'Customer Invoice' || $je->reference_type === 'Vendor Bill')
                                        <a href="{{ route('invoices.show', $je->reference_id) }}" class="text-blue-600 hover:underline font-mono">{{ $je->reference_id }}</a>
                                    @else
                                        <span class="font-mono text-stone-500">{{ $je->reference_id }}</span>
                                    @endif
                                @else
                                    <span class="text-stone-300">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-stone-600 max-w-xs">
                                <div class="truncate">{{ $je->description }}</div>
                            </td>
                            <td class="text-right font-mono text-sm font-semibold text-emerald-700">
                                Rp {{ number_format($je->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-mono text-sm font-semibold text-red-700">
                                Rp {{ number_format($je->total_credit, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($isBalanced)
                                    <span class="badge b-forest text-xs">✓ Seimbang</span>
                                @else
                                    <span class="badge b-red text-xs">⚠ Tidak Seimbang</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $je->is_posted ? 'b-blue' : 'b-amber' }}">
                                    {{ $je->status ?? ($je->is_posted ? 'Posted' : 'Draft') }}
                                </span>
                            </td>
                            <td class="text-center col-actions">
                                <a href="{{ route('accounting.entry.show', $je->entry_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($entries->hasPages())
                <div class="px-4 py-3 border-t border-stone-100">
                    {{ $entries->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Legend --}}
    <div class="card card-body">
        <div class="section-title">Legenda Tipe Jurnal</div>
        <div class="flex flex-wrap gap-2">
            @foreach(['Opening'=>'b-gray','Customer Invoice'=>'b-forest','Vendor Bill'=>'b-gold','Delivery Order'=>'b-blue','Receipt'=>'b-blue','Payment'=>'b-purple','Return'=>'b-red','Credit Note'=>'b-rose'] as $type => $color)
                <span class="badge {{ $color }}">{{ $type }}</span>
            @endforeach
        </div>
        <p class="text-xs text-stone-400 mt-2"><strong>SR-20:</strong> Setiap dokumen bisnis hanya menghasilkan satu set Journal Entry Posted. Badge merah "Tidak Seimbang" menandakan Debit ≠ Credit yang perlu investigasi.</p>
    </div>

</div>
@endsection
