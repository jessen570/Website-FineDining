@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.orders.index') }}">Sales Orders</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $order->order_id }}</span>
@endsection

@section('content')
@php
    $statusMap = ['Draft'=>'b-gray','Confirmed'=>'b-green','Done'=>'b-blue','Cancelled'=>'b-red'];
    $steps     = ['Draft','Confirmed','Done'];
    $currentStep = array_search($order->status, $steps);
    $isCancelled = $order->status === 'Cancelled';
@endphp

{{-- Status Pipeline --}}
<div style="background:white;border:1px solid #E8E4DA;border-radius:10px;padding:16px 24px;margin-bottom:20px">
    <div style="display:flex;align-items:center">
        @foreach($steps as $idx => $step)
        @php
            $isDone    = !$isCancelled && $idx < $currentStep;
            $isCurrent = !$isCancelled && $order->status === $step;
        @endphp
        <div style="display:flex;align-items:center;flex:1">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                    {{ $isDone ? 'background:#059669;' : ($isCurrent ? 'background:#18382E;' : 'background:#F0EEE8;border:2px solid #E8E4DA;') }}">
                    @if($isDone)<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    @elseif($isCurrent)<div style="width:8px;height:8px;border-radius:50%;background:white"></div>
                    @else<div style="width:8px;height:8px;border-radius:50%;background:#D1CFC9"></div>@endif
                </div>
                <span style="font-size:11px;font-weight:{{ $isCurrent ? '700' : '400' }};color:{{ $isCurrent ? '#18382E' : ($isDone ? '#059669' : '#B8B2A6') }};white-space:nowrap">{{ $step }}</span>
            </div>
            @if(!$loop->last)<div style="flex:1;height:2px;background:{{ $isDone ? '#059669' : '#F0EEE8' }};margin:0 8px;margin-bottom:18px"></div>@endif
        </div>
        @endforeach
        @if($isCancelled)
        <div style="margin-left:16px;display:flex;flex-direction:column;align-items:center;gap:4px">
            <div style="width:28px;height:28px;border-radius:50%;background:#DC2626;display:flex;align-items:center;justify-content:center">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <span style="font-size:11px;font-weight:700;color:#DC2626">Cancelled</span>
        </div>
        @endif
    </div>
</div>

<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $order->order_id }}</div>
            <span class="badge {{ $statusMap[$order->status] ?? 'b-gray' }}">{{ $order->status }}</span>
            @if($order->is_locked ?? false)
            <span class="badge b-gray">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Locked
            </span>
            @endif
        </div>
        <div class="page-desc" style="margin-top:4px">{{ $order->customer?->name ?? '—' }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @if($order->status === 'Draft')
        <form method="POST" action="{{ route('sales.orders.confirm', $order) }}" onsubmit="return confirm('Confirm this sales order?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Confirm Order
            </button>
        </form>
        <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-outline btn-sm">Edit</a>
        @endif
        @if(in_array($order->status, ['Draft','Confirmed']))
        <form method="POST" action="{{ route('sales.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order? This cannot be undone.')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
        </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Order Lines --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Order Lines</span></div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:90px">SKU</th>
                            <th>Product</th>
                            <th style="text-align:right">Ordered</th>
                            <th style="text-align:right">Delivered</th>
                            <th style="text-align:right">Invoiced</th>
                            <th style="text-align:right">Unit Price</th>
                            <th style="text-align:right">Disc%</th>
                            <th style="text-align:right">Subtotal</th>
                            <th style="text-align:right">PBJT</th>
                            <th style="text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->lines as $line)
                        @php
                            $sub   = ($line->quantity??0) * ($line->unit_price??0) * (1-(($line->discount_percent??0)/100));
                            $pbjt  = $sub * 0.10;
                            $total = $sub + $pbjt;
                            $delivQty = $line->delivered_qty ?? 0;
                            $invQty   = $line->invoiced_qty ?? 0;
                            $ordQty   = $line->quantity ?? 0;
                        @endphp
                        <tr>
                            <td style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $line->product?->sku ?? '—' }}</td>
                            <td style="font-size:13px;font-weight:500;color:#1E1C1A">{{ $line->product?->name ?? '—' }}</td>
                            <td style="text-align:right;font-size:13px;font-weight:600">{{ $ordQty }}</td>
                            <td style="text-align:right;font-size:12.5px;color:{{ $delivQty >= $ordQty ? '#059669' : '#D97706' }};font-weight:600">{{ $delivQty }}</td>
                            <td style="text-align:right;font-size:12.5px;color:{{ $invQty >= $ordQty ? '#059669' : '#6B6560' }};font-weight:600">{{ $invQty }}</td>
                            <td style="text-align:right;font-size:12.5px">Rp {{ number_format($line->unit_price ?? 0, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:12.5px;color:#D97706">{{ ($line->discount_percent??0) > 0 ? ($line->discount_percent).'%' : '—' }}</td>
                            <td style="text-align:right;font-size:12.5px">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:12.5px;color:#6B6560">Rp {{ number_format($pbjt, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:13px;font-weight:700">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="10" style="text-align:center;color:#B8B2A6;padding:24px;font-size:12.5px">No order lines</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Related Documents --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Related Documents</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px">
                    @if($order->quotation)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">Quotation</div>
                            <a href="{{ route('sales.quotations.show', $order->quotation) }}" style="font-family:monospace;font-size:13px;font-weight:600;color:#18382E;text-decoration:none">{{ $order->quotation->quotation_id }}</a>
                        </div>
                        <span class="badge b-gold">{{ $order->quotation->status }}</span>
                    </div>
                    @endif
                    @if($order->crmLead)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">CRM Lead</div>
                            <a href="{{ route('crm.leads.show', $order->crmLead) }}" style="font-family:monospace;font-size:13px;font-weight:600;color:#18382E;text-decoration:none">{{ $order->crmLead->lead_id }}</a>
                        </div>
                        <span class="badge b-blue">{{ $order->crmLead->stage }}</span>
                    </div>
                    @endif
                    @if($order->status === 'Confirmed')
                    <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <div style="font-size:12.5px;color:#065F46">
                            <strong>Ready for Inventory &amp; Accounting integration.</strong><br>
                            This confirmed order is available for delivery order creation and invoicing via the API contract.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Audit History --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Audit History</span>
                <span style="font-size:11.5px;color:#8A8478">{{ count($auditLogs) }} entries</span>
            </div>
            <div class="card-body">
                @if(count($auditLogs))
                <div style="display:flex;flex-direction:column;gap:12px">
                    @foreach($auditLogs as $log)
                    <div style="display:flex;gap:12px;align-items:flex-start">
                        <div style="width:28px;height:28px;border-radius:50%;background:#F0EEE8;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="badge b-blue" style="font-size:10px">{{ $log->action }}</span>
                                <span style="font-size:12.5px;font-weight:600;color:#343130">{{ $log->user?->name ?? 'System' }}</span>
                                <span style="font-size:11.5px;color:#B8B2A6">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}</span>
                            </div>
                            @if($log->description)
                            <div style="font-size:12px;color:#6B6560;margin-top:3px">{{ $log->description }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="font-size:12.5px;color:#8A8478">No activity recorded yet.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        {{-- Order Summary --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Order Summary</span></div>
            <div class="card-body">
                @php
                    $subtotal = $order->lines->sum(fn($l) => ($l->quantity??0)*($l->unit_price??0)*(1-(($l->discount_percent??0)/100)));
                    $discPct  = $order->discount_percent ?? 0;
                    $discount = $subtotal * $discPct / 100;
                    $afterDisc= $subtotal - $discount;
                    $pbjt     = $afterDisc * 0.10;
                    $grand    = $afterDisc + $pbjt;
                @endphp
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Customer</div>
                        @if($order->customer)
                        <a href="{{ route('sales.customers.show', $order->customer_id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $order->customer->name }}</a>
                        @else <div style="color:#B8B2A6">—</div> @endif
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Order Date</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</div>
                    </div>
                    @if($order->confirmed_at)
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Confirmed</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($order->confirmed_at)->format('d M Y, H:i') }}</div>
                    </div>
                    @endif
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Payment Terms</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $order->payment_terms ?: '—' }}</div>
                    </div>
                    <div style="border-top:1px solid #F0EEE8;padding-top:12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                            <span style="font-size:12.5px;color:#6B6560">Subtotal</span>
                            <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($discPct > 0)
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                            <span style="font-size:12.5px;color:#D97706">Discount ({{ $discPct }}%)</span>
                            <span style="font-size:12.5px;font-weight:600;color:#D97706">- Rp {{ number_format($discount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                            <span style="font-size:12.5px;color:#6B6560">PBJT (10%)</span>
                            <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($pbjt, 0, ',', '.') }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;border-top:2px solid #E8E4DA;padding-top:10px">
                            <span style="font-size:14px;font-weight:700;color:#343130">Grand Total</span>
                            <span style="font-size:1.1rem;font-weight:700;color:#C6A76B">Rp {{ number_format($grand, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Credit Validation --}}
        @if(isset($creditValidation) && $creditValidation)
        <div class="card">
            <div class="card-header">
                <span class="card-title">Credit Validation</span>
                <span class="badge {{ $creditValidation->passed ? 'b-green' : 'b-red' }}">
                    {{ $creditValidation->passed ? 'Passed' : 'Failed' }}
                </span>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12.5px;color:#6B6560">Exposure</span>
                        <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($creditValidation->exposure ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12.5px;color:#6B6560">Credit Limit</span>
                        <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($creditValidation->credit_limit ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if($creditValidation->exceeded_by ?? 0)
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12.5px;color:#DC2626">Exceeded By</span>
                        <span style="font-size:12.5px;font-weight:700;color:#DC2626">Rp {{ number_format($creditValidation->exceeded_by, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="font-size:11.5px;color:#B8B2A6">{{ \Carbon\Carbon::parse($creditValidation->validated_at ?? $creditValidation->created_at)->format('d M Y, H:i') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Discount Approval --}}
        @if(isset($approvalRecord) && $approvalRecord)
        <div class="card">
            <div class="card-header">
                <span class="card-title">Discount Approval</span>
                <span class="badge {{ $approvalRecord->status === 'Approved' ? 'b-green' : ($approvalRecord->status === 'Rejected' ? 'b-red' : 'b-amber') }}">{{ $approvalRecord->status }}</span>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Approval Code</div>
                        <div style="font-family:monospace;font-size:12.5px;color:#1E1C1A">{{ $approvalRecord->approval_code }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Approved By</div>
                        <div style="font-size:13px;color:#1E1C1A">{{ $approvalRecord->approvedBy?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Date</div>
                        <div style="font-size:12.5px;color:#6B6560">{{ \Carbon\Carbon::parse($approvalRecord->updated_at)->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
