@extends('layouts.app')
@section('title', 'Akuntansi & Neraca — Maison Aster')

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item">Keuangan</span>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Neraca & Buku Besar</span>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Akuntansi & Neraca</h1>
            <p class="page-desc">S-14 · F-15 · F-22 — Balance Sheet, Chart of Accounts, dan General Ledger Maison Aster</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.ledger') }}" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Buku Besar
            </a>
            <a href="{{ route('accounting.entries') }}" class="btn btn-primary btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                Semua Jurnal
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    @php
        $totalAset      = $accounts->whereIn('type', ['Aset'])->sum('balance');
        $totalLiab      = $accounts->whereIn('type', ['Liabilitas'])->sum('balance');
        $totalEkuitas   = $accounts->whereIn('type', ['Ekuitas'])->sum('balance');
        $totalPendapatan= $accounts->whereIn('type', ['Pendapatan'])->sum('balance');
        $totalBeban     = $accounts->whereIn('type', ['Beban','Kontra Pendapatan'])->sum('balance');
        $labaRugi       = $totalPendapatan - $totalBeban;
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi">
            <div class="kpi-divider bg-emerald-500"></div>
            <div class="kpi-label">Total Aset</div>
            <div class="kpi-value text-emerald-700" style="font-size:1rem">Rp {{ number_format($totalAset, 0, ',', '.') }}</div>
            <div class="kpi-sub">Kas + Piutang + Persediaan</div>
            <div class="kpi-icon bg-emerald-50 text-emerald-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-red-500"></div>
            <div class="kpi-label">Total Liabilitas</div>
            <div class="kpi-value text-red-700" style="font-size:1rem">Rp {{ number_format($totalLiab, 0, ',', '.') }}</div>
            <div class="kpi-sub">Utang Usaha + PBJT + GRNI</div>
            <div class="kpi-icon bg-red-50 text-red-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider bg-purple-500"></div>
            <div class="kpi-label">Total Ekuitas</div>
            <div class="kpi-value text-purple-700" style="font-size:1rem">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</div>
            <div class="kpi-sub">Modal + Laba Ditahan</div>
            <div class="kpi-icon bg-purple-50 text-purple-600"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
        </div>
        <div class="kpi">
            <div class="kpi-divider {{ $labaRugi >= 0 ? 'bg-blue-500' : 'bg-rose-500' }}"></div>
            <div class="kpi-label">Laba / Rugi Periode</div>
            <div class="kpi-value {{ $labaRugi >= 0 ? 'text-blue-700' : 'text-rose-700' }}" style="font-size:1rem">
                {{ $labaRugi >= 0 ? '+' : '' }}Rp {{ number_format($labaRugi, 0, ',', '.') }}
            </div>
            <div class="kpi-sub">Pendapatan − Beban</div>
            <div class="kpi-icon {{ $labaRugi >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600' }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
        </div>
    </div>

    {{-- Balance Sheet by Type --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#18382E" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Neraca (Balance Sheet) — Chart of Accounts
            </span>
            <span class="text-xs text-stone-400">Sumber: Jurnal yang sudah Posted</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Tipe</th>
                        <th class="text-right">Total Debit</th>
                        <th class="text-right">Total Kredit</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $typeGroups = [
                            'Aset'             => ['color' => 'b-forest',  'icon' => '🏦'],
                            'Liabilitas'       => ['color' => 'b-red',     'icon' => '📋'],
                            'Ekuitas'          => ['color' => 'b-purple',  'icon' => '💼'],
                            'Pendapatan'       => ['color' => 'b-blue',    'icon' => '💰'],
                            'Kontra Pendapatan'=> ['color' => 'b-rose',    'icon' => '↩️'],
                            'Beban'            => ['color' => 'b-amber',   'icon' => '📊'],
                        ];
                        $prevType = null;
                        $typeTotal = [];
                    @endphp
                    @foreach($accounts->sortBy(fn($a) => array_search($a->type, array_keys($typeGroups))) as $acc)
                        @if($acc->type !== $prevType)
                            @if($prevType !== null)
                                <tr class="bg-stone-50 border-t border-stone-200">
                                    <td colspan="3" class="text-xs font-bold uppercase tracking-wider text-stone-500 py-2">Subtotal {{ $prevType }}</td>
                                    <td class="text-right font-mono font-bold text-stone-700">Rp {{ number_format($typeTotal[$prevType]['d'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-right font-mono font-bold text-stone-700">Rp {{ number_format($typeTotal[$prevType]['c'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-right font-mono font-bold {{ ($typeTotal[$prevType]['b'] ?? 0) >= 0 ? 'text-[#18382E]' : 'text-red-600' }}">
                                        Rp {{ number_format(abs($typeTotal[$prevType]['b'] ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="bg-[#18382E]/5">
                                <td colspan="6" class="py-2 px-4">
                                    <span class="text-xs font-bold uppercase tracking-wider text-[#18382E] flex items-center gap-2">
                                        {{ $typeGroups[$acc->type]['icon'] ?? '' }}
                                        {{ $acc->type }}
                                        <span class="badge {{ $typeGroups[$acc->type]['color'] ?? 'b-gray' }}">{{ $accounts->where('type', $acc->type)->count() }} akun</span>
                                    </span>
                                </td>
                            </tr>
                            @php $prevType = $acc->type; @endphp
                        @endif
                        @php
                            if (!isset($typeTotal[$acc->type])) $typeTotal[$acc->type] = ['d'=>0,'c'=>0,'b'=>0];
                            $typeTotal[$acc->type]['d'] += $acc->total_debit ?? 0;
                            $typeTotal[$acc->type]['c'] += $acc->total_credit ?? 0;
                            $typeTotal[$acc->type]['b'] += $acc->balance ?? 0;
                        @endphp
                        <tr>
                            <td class="col-id">
                                <a href="{{ route('accounting.ledger') }}?account_code={{ $acc->account_code }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] hover:underline">
                                    {{ $acc->account_code }}
                                </a>
                            </td>
                            <td>
                                <div class="font-medium text-stone-800">{{ $acc->account_name }}</div>
                            </td>
                            <td><span class="badge {{ $typeGroups[$acc->type]['color'] ?? 'b-gray' }} text-xs">{{ $acc->type }}</span></td>
                            <td class="text-right font-mono text-sm text-stone-600">Rp {{ number_format($acc->total_debit ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-sm text-stone-600">Rp {{ number_format($acc->total_credit ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-sm font-semibold {{ ($acc->balance ?? 0) >= 0 ? 'text-[#18382E]' : 'text-red-600' }}">
                                Rp {{ number_format($acc->balance ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    {{-- Last group subtotal --}}
                    @if($prevType)
                        <tr class="bg-stone-50 border-t border-stone-200">
                            <td colspan="3" class="text-xs font-bold uppercase tracking-wider text-stone-500 py-2">Subtotal {{ $prevType }}</td>
                            <td class="text-right font-mono font-bold text-stone-700">Rp {{ number_format($typeTotal[$prevType]['d'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-stone-700">Rp {{ number_format($typeTotal[$prevType]['c'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-mono font-bold text-[#18382E]">Rp {{ number_format(abs($typeTotal[$prevType]['b'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    {{-- Grand Total --}}
                    @php
                        $grandDebit  = collect($typeTotal)->sum('d');
                        $grandCredit = collect($typeTotal)->sum('c');
                        $balanced    = round($grandDebit, 2) === round($grandCredit, 2);
                    @endphp
                    <tr class="{{ $balanced ? 'bg-emerald-50' : 'bg-red-50' }} border-t-2 {{ $balanced ? 'border-emerald-300' : 'border-red-300' }}">
                        <td colspan="3" class="font-bold text-sm py-3">
                            @if($balanced)
                                <span class="flex items-center gap-2 text-emerald-700"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> SEIMBANG (SR-20 ✓)</span>
                            @else
                                <span class="flex items-center gap-2 text-red-700"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> TIDAK SEIMBANG ⚠️</span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-bold text-emerald-700">Rp {{ number_format($grandDebit, 0, ',', '.') }}</td>
                        <td class="text-right font-mono font-bold text-red-700">Rp {{ number_format($grandCredit, 0, ',', '.') }}</td>
                        <td class="text-right font-mono font-bold text-stone-700">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Journal Entries --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title">Jurnal Terbaru (10 Terakhir)</span>
            <a href="{{ route('accounting.entries') }}" class="btn btn-outline btn-xs">Lihat Semua →</a>
        </div>
        @if($recentEntries->isEmpty())
            <div class="empty-state py-8">
                <div class="empty-text">Belum ada jurnal yang diposting.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entry ID</th>
                            <th>Tanggal</th>
                            <th>Tipe Referensi</th>
                            <th>Ref ID</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Total Debit</th>
                            <th class="text-right">Total Kredit</th>
                            <th>Status</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentEntries as $je)
                        @php
                            $isBalanced = round($je->total_debit, 2) === round($je->total_credit, 2);
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
                        <tr>
                            <td class="col-id">
                                <a href="{{ route('accounting.entry.show', $je->entry_id) }}" class="font-mono text-[#18382E] hover:text-[#C6A76B] font-semibold hover:underline">
                                    {{ $je->entry_id }}
                                </a>
                            </td>
                            <td class="text-sm text-stone-600">{{ $je->entry_date ? \Carbon\Carbon::parse($je->entry_date)->format('d M Y') : '—' }}</td>
                            <td><span class="badge {{ $refBadge }} text-xs">{{ $je->reference_type }}</span></td>
                            <td class="col-id text-xs">{{ $je->reference_id ?? '—' }}</td>
                            <td class="text-sm text-stone-600 max-w-xs truncate">{{ $je->description }}</td>
                            <td class="text-right font-mono text-sm text-emerald-700">Rp {{ number_format($je->total_debit, 0, ',', '.') }}</td>
                            <td class="text-right font-mono text-sm text-red-700">Rp {{ number_format($je->total_credit, 0, ',', '.') }}</td>
                            <td>
                                @if(!$isBalanced)
                                    <span class="badge b-red text-xs">⚠ Tidak Seimbang</span>
                                @else
                                    <span class="badge {{ $je->is_posted ? 'b-forest' : 'b-amber' }}">{{ $je->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('accounting.entry.show', $je->entry_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- SR-20 Note --}}
    <div class="card card-body text-xs text-stone-500 space-y-1">
        <p><strong class="text-stone-700">SR-20:</strong> Satu peristiwa bisnis menghasilkan paling banyak satu set Journal Entry Posted; Debit = Credit per dokumen.</p>
        <p><strong class="text-stone-700">SR-12:</strong> Customer Invoice memisahkan penjualan (4010) dan pajak daerah PBJT (2020) secara terpisah dalam jurnal.</p>
    </div>

</div>
@endsection
