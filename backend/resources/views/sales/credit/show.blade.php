@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.credit.index') }}">Kontrol Kredit</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $customer->name }}</span>
@endsection

@section('content')
@php
    $limit     = $check['credit_limit'] ?? 0;
    $exp       = $exposure ?? 0;
    $avail     = max(0, $limit - $exp);
    $rawPct    = $limit > 0 ? round($exp / $limit * 100) : 0;
    $barPct    = min(100, $rawPct);
    $overLimit = $limit > 0 && $exp > $limit;
    $fillCol   = ($overLimit || $rawPct >= 90) ? '#EF4444' : ($rawPct >= 60 ? '#D97706' : '#10B981');
@endphp

<x-page-header
    :title="$customer->name"
    :description="'Profil Kontrol Kredit — ' . $customer->contact_id"
>
    <x-slot:badge>
        @if($overLimit)
            <span class="badge b-red">Melebihi Batas</span>
        @elseif($limit > 0 && $rawPct >= 80)
            <span class="badge b-amber">Mendekati Batas</span>
        @else
            <span class="badge b-green">Aman</span>
        @endif
    </x-slot:badge>
    <x-slot:actions>
        <a href="{{ route('sales.customers.show', $customer->contact_id) }}" class="btn btn-outline">Detail Pelanggan</a>
        <a href="{{ route('sales.credit.index') }}" class="btn btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
    </x-slot:actions>
</x-page-header>

{{-- 4 Metric Boxes --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <x-kpi-card
        title="Batas Kredit"
        :value="$limit > 0 ? 'Rp ' . number_format($limit, 0, ',', '.') : 'Tanpa Batas'"
        sub="Plafon maksimum yang diizinkan"
        iconBg="#ECFDF5"
        iconColor="#059669"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Eksposur Saat Ini"
        :value="'Rp ' . number_format($exp, 0, ',', '.')"
        sub="Opening AR + Faktur + SO terbuka"
        :iconBg="$overLimit ? '#FEF2F2' : '#FFFBEB'"
        :iconColor="$overLimit ? '#DC2626' : '#D97706'"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Sisa Kredit Tersedia"
        :value="$limit > 0 ? 'Rp ' . number_format($avail, 0, ',', '.') : 'Tidak Terbatas'"
        sub="Kapasitas kredit tersisa"
        :iconBg="$avail > 0 ? '#ECFDF5' : '#FEF2F2'"
        :iconColor="$avail > 0 ? '#059669' : '#DC2626'"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Utilisasi Kredit"
        :value="$rawPct . '%'"
        sub="Dari batas kredit digunakan"
        :iconBg="($overLimit || $rawPct >= 90) ? '#FEF2F2' : ($rawPct >= 60 ? '#FFFBEB' : '#ECFDF5')"
        :iconColor="$fillCol"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

{{-- Full-width credit bar --}}
@if($limit > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="font-size:12px;font-weight:700;color:#8A8478;letter-spacing:.06em;text-transform:uppercase">Utilisasi Batas Kredit (SR-15)</div>
            <div style="font-size:13px;font-weight:700;color:{{ $fillCol }}">{{ $rawPct }}% terpakai</div>
        </div>
        <div class="credit-bar" style="height:10px">
            <div class="credit-fill" style="width:{{ $barPct }}%;background:{{ $fillCol }}"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px">
            <span style="font-size:11.5px;color:#8A8478">Rp 0</span>
            <span style="font-size:11.5px;color:#8A8478">Batas: Rp {{ number_format($limit, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:20px">
    {{-- Left Column: Breakdown & Tables --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Breakdown --}}
        <x-card title="Rincian Eksposur Kredit (SR-15)">
            <div style="display:flex;flex-direction:column;gap:0">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #F0EEE8">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">1. Opening AR (Piutang Awal)</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">Saldo piutang awal pelanggan</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['opening_ar'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #F0EEE8">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">2. Faktur Pelanggan Belum Lunas</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">Sisa tagihan (amount due) Customer Invoice berstatus Draft/Posted</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['unpaid_invoices'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:2px solid #E8E4DA">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">3. Sales Order Confirmed Belum Difakturkan</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">SO berstatus Confirmed dengan invoice_status != Invoiced (single-entry)</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['confirmed_so_amount'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0">
                    <div style="font-size:14px;font-weight:700;color:#1E1C1A">Total Eksposur Kredit</div>
                    <div style="font-size:1.1rem;font-weight:700;color:{{ $overLimit ? '#DC2626' : '#1E1C1A' }}">Rp {{ number_format($exp, 0, ',', '.') }}</div>
                </div>
            </div>
        </x-card>

        {{-- Open Orders --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Sales Order Terbuka (Confirmed Belum Difakturkan)</span>
                <span class="badge b-blue">{{ $openOrders->count() }}</span>
            </div>
            @if($openOrders->count())
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Tanggal</th>
                            <th style="text-align:right">Total (Gross)</th>
                            <th>Status Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openOrders as $o)
                        <tr>
                            <td><a href="{{ route('sales.orders.show', $o->order_id) }}" style="color:#18382E;font-weight:600;text-decoration:none;font-family:monospace;font-size:12px">{{ $o->order_id }}</a></td>
                            <td style="font-size:12px;color:#6B6560">{{ \Carbon\Carbon::parse($o->order_date)->format('d M Y') }}</td>
                            <td style="text-align:right;font-size:12.5px;font-weight:600">Rp {{ number_format($o->gross_amount ?? 0, 0, ',', '.') }}</td>
                            <td><x-badge :status="$o->invoice_status" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body">
                <div style="font-size:12.5px;color:#8A8478">Tidak ada Sales Order terbuka yang belum difakturkan.</div>
            </div>
            @endif
        </div>

        {{-- Unpaid Invoices --}}
        @if(isset($unpaidInvoices) && $unpaidInvoices->count())
        <div class="card">
            <div class="card-header">
                <span class="card-title">Faktur Belum Lunas</span>
                <span class="badge b-amber">{{ $unpaidInvoices->count() }}</span>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tanggal</th>
                            <th style="text-align:right">Sisa Tagihan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidInvoices as $inv)
                        <tr>
                            <td style="font-family:monospace;font-size:12px;font-weight:600;color:#18382E">{{ $inv->invoice_id }}</td>
                            <td style="font-size:12px;color:#6B6560">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                            <td style="text-align:right;font-size:12.5px;font-weight:600;color:#DC2626">Rp {{ number_format($inv->amount_due ?? 0, 0, ',', '.') }}</td>
                            <td><x-badge :status="$inv->status" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Right Column: Simulasi Validasi Kredit & Riwayat --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <x-card title="Simulasi &amp; Uji Validasi Kredit">
            <form method="POST" action="{{ route('sales.credit.validate') }}">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->contact_id }}">
                <input type="hidden" name="document_type" value="ManualCheck">
                <div class="form-group">
                    <label class="form-label">Referensi Dokumen / Catatan</label>
                    <input type="text" name="document_id" class="form-input" value="CHK-{{ now()->format('Ymd-His') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nilai Transaksi Calon Order (Rp)</label>
                    <input type="number" name="candidate_amount" class="form-input" min="0" step="1000" placeholder="Contoh: 500000" required>
                    <div class="form-hint">Akan diuji terhadap sisa kredit tersedia: Rp {{ number_format($avail, 0, ',', '.') }}</div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    Jalankan Validasi Kredit
                </button>
            </form>
        </x-card>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Riwayat Validasi Kredit</span>
                <span class="badge b-gray">{{ $history->count() }}</span>
            </div>
            @if($history->count())
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode / Dokumen</th>
                            <th style="text-align:right">Calon Nilai</th>
                            <th>Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                        <tr>
                            <td>
                                <div style="font-family:monospace;font-size:11.5px;font-weight:600;color:#18382E">{{ $h->validation_code }}</div>
                                <div style="font-size:11px;color:#8A8478">{{ $h->document_type }}: {{ $h->document_id }}</div>
                                <div style="font-size:10.5px;color:#B8B2A6">{{ \Carbon\Carbon::parse($h->created_at)->format('d M Y H:i') }} • {{ $h->validated_by }}</div>
                            </td>
                            <td style="text-align:right;font-size:12px;font-weight:600">
                                Rp {{ number_format($h->candidate_amount ?? 0, 0, ',', '.') }}
                                @if(!$h->passed && $h->exceeded_by > 0)
                                <div style="font-size:10.5px;color:#DC2626">+Rp {{ number_format($h->exceeded_by, 0, ',', '.') }} over</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $h->passed ? 'b-green' : 'b-red' }}">
                                    {{ $h->passed ? 'Lulus' : 'Ditolak' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body">
                <div style="font-size:12.5px;color:#8A8478">Belum ada riwayat validasi kredit untuk pelanggan ini.</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
