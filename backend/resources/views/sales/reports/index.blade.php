@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Laporan Penjualan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Laporan Penjualan (Sales Reports)</div>
        <div class="page-desc">Analisis kinerja penjualan, tren pendapatan, dan performa konversi.</div>
    </div>
</div>

{{-- Date Range Filter --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" action="{{ route('sales.reports.index') }}">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <span style="font-size:12.5px;font-weight:700;color:#6B6560">Rentang Tanggal:</span>
                <div style="display:flex;align-items:center;gap:8px">
                    <input type="date" name="date_from" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
                    <span style="font-size:12.5px;color:#8A8478">sampai</span>
                    <input type="date" name="date_to" class="form-input" style="width:auto;font-size:12.5px;padding:7px 10px" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filter
                </button>
                <a href="{{ route('sales.reports.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- 6 KPI Cards (3x2) --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#F9FAFB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        </div>
        <div class="kpi-label">Total Quotations</div>
        <div class="kpi-value">{{ $totalQuotations ?? 0 }}</div>
        <div class="kpi-sub">Dalam periode terpilih</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="kpi-label">Total Sales Orders</div>
        <div class="kpi-value">{{ $totalSO ?? 0 }}</div>
        <div class="kpi-sub">Semua status pesanan</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Pesanan Dikonfirmasi</div>
        <div class="kpi-value">{{ $confirmedSO ?? 0 }}</div>
        <div class="kpi-sub">Confirmed &amp; Done</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FFFBEB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="kpi-label">Nilai Penjualan</div>
        <div class="kpi-value" style="font-size:1.3rem">Rp {{ number_format(($totalSalesValue ?? 0) / 1000000, 1, ',', '.') }} jt</div>
        <div class="kpi-sub">Total pendapatan terkonfirmasi</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FFFBEB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="kpi-label">Quotation Tertunda</div>
        <div class="kpi-value">{{ $pendingQuotations ?? 0 }}</div>
        <div class="kpi-sub">Status Draft atau Sent</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value" style="color:#059669">{{ $conversionRate ?? 0 }}%</div>
        <div class="kpi-sub">Quotation → Sales Order</div>
    </div>
</div>

{{-- Sales by Customer --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">Penjualan per Pelanggan</span>
        <span style="font-size:11.5px;color:#8A8478">Peringkat pelanggan berdasarkan nilai pesanan</span>
    </div>
    <div style="overflow-x:auto">
        @if(isset($salesByCustomer) && count($salesByCustomer))
        @php $maxVal = collect($salesByCustomer)->max('total_value') ?: 1; @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Customer</th>
                    <th style="text-align:right">Pesanan</th>
                    <th style="text-align:right">Total Nilai</th>
                    <th style="width:200px">Pangsa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesByCustomer as $idx => $row)
                @php $barPct = round(($row['total_value'] ?? 0) / $maxVal * 100); @endphp
                <tr>
                    <td style="text-align:center;color:#8A8478;font-size:12px">{{ $idx + 1 }}</td>
                    <td style="font-size:13px;font-weight:500;color:#1E1C1A">{{ $row['customer_name'] ?? '—' }}</td>
                    <td style="text-align:right;font-size:13px">{{ $row['order_count'] ?? 0 }}</td>
                    <td style="text-align:right;font-size:13px;font-weight:700">Rp {{ number_format($row['total_value'] ?? 0, 0, ',', '.') }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;height:6px;background:#F0EEE8;border-radius:100px;overflow:hidden">
                                <div style="width:{{ $barPct }}%;height:100%;background:#18382E;border-radius:100px"></div>
                            </div>
                            <span style="font-size:10.5px;color:#8A8478;width:35px">{{ $barPct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state" style="padding:24px">
            <div class="empty-text">Belum ada data penjualan pelanggan untuk periode ini.</div>
        </div>
        @endif
    </div>
</div>

{{-- Monthly Trend --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">Tren Bulanan (Monthly Trend)</span>
        <span style="font-size:11.5px;color:#8A8478">Volume pesanan dan pendapatan bulanan</span>
    </div>
    <div style="overflow-x:auto">
        @if(isset($monthlyTrend) && count($monthlyTrend))
        @php
            $prevValue = 0;
        @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th style="text-align:right">Pesanan</th>
                    <th style="text-align:right">Total Nilai</th>
                    <th style="width:80px">Pertumbuhan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $row)
                @php
                    $growth = $prevValue > 0 ? round((($row['total_value'] ?? 0) - $prevValue) / $prevValue * 100) : null;
                    $prevValue = $row['total_value'] ?? 0;
                @endphp
                <tr>
                    <td style="font-size:13px;font-weight:500;color:#1E1C1A">{{ $row['month'] ?? '—' }}</td>
                    <td style="text-align:right;font-size:13px">{{ $row['order_count'] ?? 0 }}</td>
                    <td style="text-align:right;font-size:13px;font-weight:700">Rp {{ number_format($row['total_value'] ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @if($growth !== null)
                        <span style="font-size:11.5px;font-weight:700;color:{{ $growth >= 0 ? '#059669' : '#DC2626' }}">
                            {{ $growth >= 0 ? '▲' : '▼' }} {{ abs($growth) }}%
                        </span>
                        @else
                        <span style="font-size:11.5px;color:#B8B2A6">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state" style="padding:24px">
            <div class="empty-text">Belum ada data tren bulanan untuk periode ini.</div>
        </div>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- Recent Orders --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Pesanan Terbaru</span>
            <a href="{{ route('sales.orders.index') }}" class="btn btn-ghost btn-xs">Lihat Semua</a>
        </div>
        <div style="overflow-x:auto">
            @if(isset($recentOrders) && $recentOrders->count())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th style="text-align:right">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $o)
                    <tr>
                        <td><a href="{{ route('sales.orders.show', $o->order_id ?? $o->id) }}" style="font-family:monospace;font-size:11.5px;color:#18382E;text-decoration:none;font-weight:600">{{ $o->order_id }}</a></td>
                        <td style="font-size:12.5px;color:#343130">{{ $o->customer?->name ?? '—' }}</td>
                        <td style="text-align:right;font-size:12.5px;font-weight:700">Rp {{ number_format($o->gross_amount ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @php
                                $oMap = ['Draft'=>'b-gray','Confirmed'=>'b-green','Done'=>'b-blue','Cancelled'=>'b-red'];
                                $oLabel = ['Draft'=>'Draft','Confirmed'=>'Confirmed','Done'=>'Selesai','Cancelled'=>'Dibatalkan'];
                            @endphp
                            <span class="badge {{ $oMap[$o->status] ?? 'b-gray' }}" style="font-size:10px">{{ $oLabel[$o->status] ?? $o->status }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state" style="padding:24px">
                <div class="empty-text">Belum ada pesanan terbaru.</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Pending Quotations --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Quotation Menunggu Tindakan</span>
            <a href="{{ route('sales.quotations.index') }}" class="btn btn-ghost btn-xs">Lihat Semua</a>
        </div>
        <div style="overflow-x:auto">
            @if(isset($pendingQuotationsList) && $pendingQuotationsList->count())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Quotation #</th>
                        <th>Customer</th>
                        <th>Tanggal</th>
                        <th style="text-align:right">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingQuotationsList as $q)
                    <tr>
                        <td><a href="{{ route('sales.quotations.show', $q->quotation_id ?? $q->id) }}" style="font-family:monospace;font-size:11.5px;color:#18382E;text-decoration:none;font-weight:600">{{ $q->quotation_id }}</a></td>
                        <td style="font-size:12.5px;color:#343130">{{ $q->customer?->name ?? '—' }}</td>
                        <td style="font-size:11.5px;color:#6B6560">{{ \Carbon\Carbon::parse($q->quotation_date)->format('d M Y') }}</td>
                        <td style="text-align:right;font-size:12.5px;font-weight:700">Rp {{ number_format($q->gross_amount ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @php
                                $qMap = ['Draft'=>'b-amber','Sent'=>'b-blue','Accepted'=>'b-green','Rejected'=>'b-red','Converted'=>'b-purple'];
                                $qLabel = ['Draft'=>'Draft','Sent'=>'Terkirim','Accepted'=>'Accepted','Rejected'=>'Ditolak','Converted'=>'Terkonversi'];
                            @endphp
                            <span class="badge {{ $qMap[$q->status] ?? 'b-gray' }}" style="font-size:10px">{{ $qLabel[$q->status] ?? $q->status }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state" style="padding:24px">
                <div class="empty-text">Belum ada quotation pending.</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
