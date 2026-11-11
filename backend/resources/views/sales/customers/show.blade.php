@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.customers.index') }}">Customers</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $customer->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $customer->name }}</div>
            <span class="badge {{ $customer->customer_type === 'Perusahaan' ? 'b-purple' : 'b-blue' }}">{{ $customer->customer_type ?? 'Individu' }}</span>
            <span class="badge {{ $customer->status === 'active' ? 'b-green' : 'b-gray' }}">{{ ucfirst($customer->status ?? 'active') }}</span>
        </div>
        <div class="page-desc" style="margin-top:4px">{{ $customer->contact_id }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('sales.customers.edit', $customer) }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
        </a>
        <a href="{{ route('sales.quotations.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
            Create Quotation
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Main Left --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- General Info --}}
        <div class="card">
            <div class="card-header"><span class="card-title">General Information</span></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    @php
                    $fields = [
                        'Customer ID'  => $customer->contact_id,
                        'Name'         => $customer->name,
                        'Type'         => $customer->customer_type ?? 'Individu',
                        'Phone'        => $customer->phone,
                        'Email'        => $customer->email,
                        'City'         => $customer->city,
                        'Tax ID'       => $customer->tax_id,
                        'Status'       => ucfirst($customer->status ?? 'active'),
                        'Created'      => \Carbon\Carbon::parse($customer->created_at)->format('d M Y'),
                    ];
                    @endphp
                    @foreach($fields as $label => $value)
                    <div>
                        <div class="section-title" style="margin-bottom:2px">{{ $label }}</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $value ?: '—' }}</div>
                    </div>
                    @endforeach
                    @if($customer->address)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Address</div>
                        <div style="font-size:13.5px;color:#1E1C1A;line-height:1.5">{{ $customer->address }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sales History / Quotations --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Sales Quotations</span>
                <a href="{{ route('sales.quotations.create', ['customer_id' => $customer->id]) }}" class="btn btn-outline btn-xs">+ New</a>
            </div>
            <div style="overflow-x:auto">
                @if(isset($quotations) && $quotations->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Quotation #</th>
                            <th>Date</th>
                            <th style="text-align:right">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotations as $q)
                        <tr>
                            <td><a href="{{ route('sales.quotations.show', $q) }}" style="color:#18382E;font-weight:600;text-decoration:none;font-family:monospace;font-size:12px">{{ $q->quotation_id }}</a></td>
                            <td style="font-size:12.5px;color:#6B6560">{{ \Carbon\Carbon::parse($q->quotation_date)->format('d M Y') }}</td>
                            <td style="text-align:right;font-size:13px;font-weight:600">Rp {{ number_format($q->gross_amount ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @php $qMap=['Draft'=>'b-amber','Sent'=>'b-blue','Accepted'=>'b-green','Rejected'=>'b-red','Converted'=>'b-purple']; @endphp
                                <span class="badge {{ $qMap[$q->status] ?? 'b-gray' }}">{{ $q->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="empty-state" style="padding:24px">
                    <div class="empty-text">No quotations yet.</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Sales Orders --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Sales Orders</span>
            </div>
            <div style="overflow-x:auto">
                @if(isset($orders) && $orders->count())
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th style="text-align:right">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('sales.orders.show', $o) }}" style="color:#18382E;font-weight:600;text-decoration:none;font-family:monospace;font-size:12px">{{ $o->order_id }}</a></td>
                            <td style="font-size:12.5px;color:#6B6560">{{ \Carbon\Carbon::parse($o->order_date)->format('d M Y') }}</td>
                            <td style="text-align:right;font-size:13px;font-weight:600">Rp {{ number_format($o->gross_amount ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @php $oMap=['Draft'=>'b-gray','Confirmed'=>'b-green','Done'=>'b-blue','Cancelled'=>'b-red']; @endphp
                                <span class="badge {{ $oMap[$o->status] ?? 'b-gray' }}">{{ $o->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="empty-state" style="padding:24px">
                    <div class="empty-text">No orders yet.</div>
                </div>
                @endif
            </div>
        </div>

        {{-- CRM Leads --}}
        @if(isset($leads) && $leads->count())
        <div class="card">
            <div class="card-header"><span class="card-title">CRM Leads</span></div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Lead ID</th><th>Name</th><th>Stage</th></tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                        <tr>
                            <td><a href="{{ route('crm.leads.show', $lead) }}" style="color:#18382E;font-weight:600;text-decoration:none;font-family:monospace;font-size:12px">{{ $lead->lead_id }}</a></td>
                            <td style="font-size:13px;color:#343130">{{ $lead->name }}</td>
                            <td>
                                @php $sMap=['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red']; @endphp
                                <span class="badge {{ $sMap[$lead->stage] ?? 'b-gray' }}">{{ $lead->stage }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        {{-- Credit Info --}}
        @php
            $limit    = $customer->credit_limit ?? 0;
            $exp      = $exposure ?? 0;
            $avail    = max(0, $limit - $exp);
            $pct      = $limit > 0 ? min(100, round($exp / $limit * 100)) : 0;
            $fillCol  = $pct >= 90 ? '#EF4444' : ($pct >= 60 ? '#D97706' : '#10B981');
            $overLimit = $limit > 0 && $exp > $limit;
        @endphp
        <div class="card">
            <div class="card-header">
                <span class="card-title">Credit Information</span>
                @if($overLimit)
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                @endif
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Credit Limit</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#1E1C1A">
                            @if($limit > 0) Rp {{ number_format($limit, 0, ',', '.') }} @else <span style="color:#B8B2A6">No limit</span> @endif
                        </div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Exposure</div>
                        <div style="font-size:1.1rem;font-weight:700;color:{{ $overLimit ? '#DC2626' : '#1E1C1A' }}">Rp {{ number_format($exp, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Available</div>
                        <div style="font-size:1.1rem;font-weight:700;color:{{ $avail > 0 ? '#059669' : '#DC2626' }}">
                            @if($limit > 0) Rp {{ number_format($avail, 0, ',', '.') }} @else <span style="color:#8A8478">—</span> @endif
                        </div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Utilization</div>
                        <div style="font-size:1.1rem;font-weight:700;color:{{ $fillCol }}">{{ $pct }}%</div>
                    </div>
                </div>
                @if($limit > 0)
                <div class="credit-bar" style="margin-bottom:10px">
                    <div class="credit-fill" style="width:{{ $pct }}%;background:{{ $fillCol }}"></div>
                </div>
                @endif
                <a href="{{ route('sales.credit.show', $customer) }}" class="btn btn-outline btn-xs" style="width:100%;justify-content:center">View Credit Detail</a>
            </div>
        </div>

        {{-- Key Details --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Key Details</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Payment Terms</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $customer->payment_terms ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Opening AR</div>
                        <div style="font-size:13.5px;color:#1E1C1A">
                            @if($customer->opening_ar) Rp {{ number_format($customer->opening_ar, 0, ',', '.') }} @else — @endif
                        </div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Customer Since</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($customer->created_at)->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
