@extends('layouts.app')
@section('title', 'Buku Besar — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('accounting.index') }}" class="breadcrumb-item">Akuntansi</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Buku Besar</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Buku Besar (General Ledger)</h1>
            <p class="page-desc">S-14 · F-15 — Riwayat transaksi per akun dengan saldo berjalan</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.entries') }}" class="btn btn-outline btn-sm">Semua Jurnal</a>
            <a href="{{ route('accounting.index') }}" class="btn btn-outline btn-sm">Neraca</a>
        </div>
    </div>

    {{-- Account Selector --}}
    <div class="card card-body">
        <form method="GET" action="{{ route('accounting.ledger') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-60">
                <label class="form-label">Pilih Akun</label>
                <select name="account_code" class="form-select" onchange="this.form.submit()">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->account_code }}" {{ $selectedCode == $acc->account_code ? 'selected' : '' }}>
                            {{ $acc->account_code }} — {{ $acc->account_name }} ({{ $acc->type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Account Info & SR Note --}}
    @if($account)
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="card card-body flex-1">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Kode Akun</div>
                    <div class="font-mono font-bold text-[#18382E] text-lg">{{ $account->account_code }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Nama Akun</div>
                    <div class="font-semibold text-stone-800">{{ $account->account_name }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Tipe</div>
                    @php
                        $typeBadge = match($account->type) {
                            'Aset'              => 'b-forest',
                            'Liabilitas'        => 'b-red',
                            'Ekuitas'           => 'b-purple',
                            'Pendapatan'        => 'b-blue',
                            'Beban'             => 'b-amber',
                            'Kontra Pendapatan' => 'b-rose',
                            default             => 'b-gray',
                        };
                        $normalBalance = in_array($account->type, ['Aset','Beban','Kontra Pendapatan']) ? 'Debit' : 'Kredit';
                    @endphp
                    <span class="badge {{ $typeBadge }}">{{ $account->type }}</span>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Normal Balance</div>
                    <span class="badge b-gray">{{ $normalBalance }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Buku besar ini hanya menampilkan transaksi yang sudah <strong>Posted</strong>. Saldo berjalan dihitung menggunakan metode normal balance akun ({{ $normalBalance }} increases = positive).
    </div>

    {{-- Ledger Table --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title">Buku Besar — Akun {{ $account->account_code }}: {{ $account->account_name }}</span>
            <span class="text-xs text-stone-400">{{ $items->total() }} transaksi · Hal. {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
        </div>
        @if($items->isEmpty())
            <div class="empty-state py-8">
                <div class="empty-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/></svg></div>
                <div class="empty-title">Tidak ada transaksi</div>
                <div class="empty-text">Akun ini belum memiliki transaksi yang diposting.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:36px">#</th>
                            <th>Tanggal</th>
                            <th>Entry ID</th>
                            <th>Tipe Referensi</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                            <th class="text-right">Saldo Berjalan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                            $rowNum = ($items->currentPage() - 1) * $items->perPage() + 1;
                            // Pre-calculate running balance for previous pages
                            if ($items->currentPage() > 1) {
                                $prevItems = \App\Models\JournalItem::with('entry')
                                    ->where('account_code', $selectedCode)
                                    ->orderBy('created_at')
                                    ->take(($items->currentPage() - 1) * $items->perPage())
                                    ->get();
                                foreach ($prevItems as $pi) {
                                    $d = (float)$pi->debit;
                                    $c = (float)$pi->credit;
                                    $runningBalance += in_array($account->type, ['Aset','Beban','Kontra Pendapatan']) ? ($d - $c) : ($c - $d);
                                }
                            }
                        @endphp
                        @foreach($items as $item)
                        @php
                            $d = (float)$item->debit;
                            $c = (float)$item->credit;
                            $isNormalDebit = in_array($account->type, ['Aset','Beban','Kontra Pendapatan']);
                            $runningBalance += $isNormalDebit ? ($d - $c) : ($c - $d);
                            $refBadge = match($item->entry?->reference_type) {
                                'Customer Invoice' => 'b-forest',
                                'Vendor Bill'      => 'b-gold',
                                'Payment'          => 'b-purple',
                                'Return'           => 'b-red',
                                'Opening'          => 'b-gray',
                                default            => 'b-blue',
                            };
                        @endphp
                        <tr>
                            <td class="text-xs text-stone-400">{{ $rowNum++ }}</td>
                            <td class="text-sm text-stone-600">
                                {{ $item->entry?->entry_date ? \Carbon\Carbon::parse($item->entry->entry_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="col-id">
                                @if($item->entry_id)
                                    <a href="{{ route('accounting.entry.show', $item->entry_id) }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] text-xs hover:underline">
                                        {{ $item->entry_id }}
                                    </a>
                                @else
                                    <span class="text-stone-300">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->entry?->reference_type)
                                    <span class="badge {{ $refBadge }} text-xs">{{ $item->entry->reference_type }}</span>
                                @else
                                    <span class="text-stone-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-stone-600 max-w-xs">
                                <div class="truncate">{{ $item->description ?? $item->account_name }}</div>
                            </td>
                            <td class="text-right font-mono text-sm {{ $d > 0 ? 'text-emerald-700 font-semibold' : 'text-stone-300' }}">
                                {{ $d > 0 ? 'Rp ' . number_format($d, 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-right font-mono text-sm {{ $c > 0 ? 'text-red-600 font-semibold' : 'text-stone-300' }}">
                                {{ $c > 0 ? 'Rp ' . number_format($c, 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-right font-mono text-sm font-bold {{ $runningBalance >= 0 ? 'text-[#18382E]' : 'text-red-600' }}">
                                {{ $runningBalance >= 0 ? '' : '(' }}Rp {{ number_format(abs($runningBalance), 0, ',', '.') }}{{ $runningBalance >= 0 ? '' : ')' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $totalD = $items->sum(fn($i) => (float)$i->debit);
                            $totalC = $items->sum(fn($i) => (float)$i->credit);
                        @endphp
                        <tr class="bg-stone-100 border-t-2 border-stone-300">
                            <td colspan="5" class="font-bold text-xs uppercase tracking-wider text-stone-500 py-3">Total Halaman Ini</td>
                            <td class="text-right font-mono font-bold text-emerald-700">Rp {{ number_format($totalD, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-red-600">Rp {{ number_format($totalC, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-[#18382E]">Rp {{ number_format(abs($runningBalance), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-stone-100">
                    {{ $items->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
    @endif

</div>
@endsection
