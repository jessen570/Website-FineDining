@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.customers.index') }}">Customers</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.customers.show', $customer) }}">{{ $customer->name }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Credit Profile</span>
@endsection

@section('content')
@php
    $limit     = $check['credit_limit'] ?? 0;
    $exp       = $exposure ?? 0;
    $avail     = max(0, $limit - $exp);
    $rawPct    = $limit > 0 ? round($exp / $limit * 100) : 0;
    $pct       = min(100, $rawPct);
    $overLimit = $limit > 0 && $exp > $limit;
    $fillCol   = ($overLimit || $rawPct >= 90) ? '#EF4444' : ($rawPct >= 60 ? '#D97706' : '#10B981');
@endphp

<div class="page-header">
    <div>
        <div class="page-title">{{ $customer->name }}</div>
        <div class="page-desc">Credit Profile</div>
    </div>
    <a href="{{ route('sales.customers.show', $customer) }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Customer
    </a>
</div>

{{-- 4 Large Metric Boxes --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
    <div class="kpi" style="padding:20px 24px">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="kpi-label">Credit Limit</div>
        <div class="kpi-value" style="color:#059669">@if($limit > 0) Rp {{ number_format($limit, 0, ',', '.') }} @else No Limit @endif</div>
        <div class="kpi-sub">Maximum allowed exposure</div>
    </div>
    <div class="kpi" style="padding:20px 24px">
        <div class="kpi-icon" style="background:{{ $overLimit ? '#FEF2F2' : '#FFFBEB' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $overLimit ? '#DC2626' : '#D97706' }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </div>
        <div class="kpi-label">Current Exposure</div>
        <div class="kpi-value" style="color:{{ $overLimit ? '#DC2626' : ($pct >= 60 ? '#D97706' : '#1E1C1A') }}">Rp {{ number_format($exp, 0, ',', '.') }}</div>
        <div class="kpi-sub">Outstanding AR + confirmed SOs</div>
    </div>
    <div class="kpi" style="padding:20px 24px">
        <div class="kpi-icon" style="background:{{ $avail > 0 ? '#ECFDF5' : '#FEF2F2' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $avail > 0 ? '#059669' : '#DC2626' }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
        </div>
        <div class="kpi-label">Available Credit</div>
        <div class="kpi-value" style="color:{{ $avail > 0 ? '#059669' : '#DC2626' }}">
            @if($limit > 0) Rp {{ number_format($avail, 0, ',', '.') }} @else Unlimited @endif
        </div>
        <div class="kpi-sub">Remaining credit available</div>
    </div>
    <div class="kpi" style="padding:20px 24px">
        <div class="kpi-icon" style="background:{{ $pct >= 90 ? '#FEF2F2' : ($pct >= 60 ? '#FFFBEB' : '#ECFDF5') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $fillCol }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
        </div>
        <div class="kpi-label">Utilization</div>
        <div class="kpi-value" style="color:{{ $fillCol }}">{{ $rawPct }}%</div>
        <div class="kpi-sub">Of credit limit used</div>
    </div>
</div>

{{-- Full-width credit bar --}}
@if($limit > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="font-size:12px;font-weight:700;color:#8A8478;letter-spacing:.06em;text-transform:uppercase">Credit Utilization</div>
            <div style="font-size:13px;font-weight:700;color:{{ $fillCol }}">{{ $rawPct }}% used</div>
        </div>
        <div class="credit-bar" style="height:10px">
            <div class="credit-fill" style="width:{{ $pct }}%;background:{{ $fillCol }}"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px">
            <span style="font-size:11.5px;color:#8A8478">Rp 0</span>
            <span style="font-size:11.5px;color:#8A8478">Rp {{ number_format($limit, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Breakdown --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Exposure Breakdown</span></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:0">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #F0EEE8">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">Opening AR</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">Opening Accounts Receivable balance</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['opening_ar'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #F0EEE8">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">Unpaid Invoices</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">Outstanding amount due on Customer Invoices</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['unpaid_invoices'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:2px solid #E8E4DA">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#343130">Confirmed Uninvoiced SOs</div>
                        <div style="font-size:11.5px;color:#8A8478;margin-top:2px">Confirmed sales orders not yet invoiced</div>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($check['confirmed_so_amount'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0">
                    <div style="font-size:14px;font-weight:700;color:#1E1C1A">Total Exposure</div>
                    <div style="font-size:1.1rem;font-weight:700;color:{{ $overLimit ? '#DC2626' : '#1E1C1A' }}">Rp {{ number_format($exp, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Open Orders --}}
    <div>
        @if(isset($orders) && $orders->count())
        <div class="card">
            <div class="card-header"><span class="card-title">Open Sales Orders</span></div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th style="text-align:right">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('sales.orders.show', $o->order_id) }}" style="color:#18382E;font-weight:600;text-decoration:none;font-family:monospace;font-size:11.5px">{{ $o->order_id }}</a></td>
                            <td style="text-align:right;font-size:12.5px;font-weight:600">Rp {{ number_format($o->gross_amount ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @php $oMap=['Draft'=>'b-gray','Confirmed'=>'b-green','Done'=>'b-blue','Cancelled'=>'b-red']; @endphp
                                <span class="badge {{ $oMap[$o->status] ?? 'b-gray' }}" style="font-size:10px">{{ $o->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <div style="font-size:12.5px;color:#8A8478">No open sales orders.</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
