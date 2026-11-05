@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.quotations.index') }}">Quotations</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $quotation->quotation_id }}</span>
@endsection

@section('content')
@php
    $statusMap = ['Draft'=>'b-amber','Sent'=>'b-blue','Accepted'=>'b-green','Rejected'=>'b-red','Converted'=>'b-purple'];
    $steps     = ['Draft','Sent','Accepted','Converted'];
    $currentStep = array_search($quotation->status, $steps);
    if($quotation->status === 'Rejected') $currentStep = -1;
@endphp

{{-- Status Pipeline --}}
<div style="background:white;border:1px solid #E8E4DA;border-radius:10px;padding:16px 24px;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:0">
        @foreach($steps as $idx => $step)
        @php
            $isDone    = $idx < $currentStep;
            $isCurrent = $quotation->status === $step;
        @endphp
        <div style="display:flex;align-items:center;flex:1">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                    {{ $isDone ? 'background:#059669;' : ($isCurrent ? 'background:#18382E;' : 'background:#F0EEE8;border:2px solid #E8E4DA;') }}">
                    @if($isDone)
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    @elseif($isCurrent)
                    <div style="width:8px;height:8px;border-radius:50%;background:white"></div>
                    @else
                    <div style="width:8px;height:8px;border-radius:50%;background:#D1CFC9"></div>
                    @endif
                </div>
                <span style="font-size:11px;font-weight:{{ $isCurrent ? '700' : '400' }};color:{{ $isCurrent ? '#18382E' : ($isDone ? '#059669' : '#B8B2A6') }};white-space:nowrap">{{ $step }}</span>
            </div>
            @if(!$loop->last)
            <div style="flex:1;height:2px;background:{{ $isDone ? '#059669' : '#F0EEE8' }};margin:0 8px;margin-bottom:18px"></div>
            @endif
        </div>
        @endforeach
        @if($quotation->status === 'Rejected')
        <div style="margin-left:16px;display:flex;flex-direction:column;align-items:center;gap:4px">
            <div style="width:28px;height:28px;border-radius:50%;background:#DC2626;display:flex;align-items:center;justify-content:center">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <span style="font-size:11px;font-weight:700;color:#DC2626">Rejected</span>
        </div>
        @endif
    </div>
</div>

<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $quotation->quotation_id }}</div>
            <span class="badge {{ $statusMap[$quotation->status] ?? 'b-gray' }}">{{ $quotation->status }}</span>
        </div>
        <div class="page-desc" style="margin-top:4px">{{ $quotation->customer?->name ?? '—' }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @if($quotation->status === 'Draft')
        <a href="{{ route('sales.quotations.edit', $quotation) }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
        </a>
        <form method="POST" action="{{ route('sales.quotations.send', $quotation) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send
            </button>
        </form>
        <form method="POST" action="{{ route('sales.quotations.confirm', $quotation) }}" onsubmit="return confirm('Confirm/Accept this quotation?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Confirm</button>
        </form>
        @elseif($quotation->status === 'Sent')
        <form method="POST" action="{{ route('sales.quotations.confirm', $quotation) }}" onsubmit="return confirm('Mark quotation as Accepted?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Accept</button>
        </form>
        @elseif($quotation->status === 'Accepted')
        <form method="POST" action="{{ route('sales.quotations.to-order', $quotation) }}" onsubmit="return confirm('Convert this quotation to a Sales Order?')">
            @csrf
            <button type="submit" class="btn btn-gold btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
                Convert to Order
            </button>
        </form>
        @endif
        @if(!in_array($quotation->status, ['Converted','Rejected']))
        <form method="POST" action="{{ route('sales.quotations.cancel', $quotation) }}" onsubmit="return confirm('Cancel this quotation?')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
        </form>
        @endif
        <a href="{{ route('sales.quotations.print', $quotation) }}" target="_blank" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Line Items --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Line Items</span></div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:30px">#</th>
                            <th style="width:90px">SKU</th>
                            <th>Product</th>
                            <th style="text-align:right">Qty</th>
                            <th style="text-align:right">Unit Price</th>
                            <th style="text-align:right">Disc%</th>
                            <th style="text-align:right">Subtotal</th>
                            <th style="text-align:right">PBJT</th>
                            <th style="text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotation->lines as $idx => $line)
                        @php
                            $unitPrice = $line->unit_price ?? 0;
                            $qty       = $line->quantity ?? 0;
                            $disc      = $line->discount_percent ?? 0;
                            $sub       = $qty * $unitPrice * (1 - $disc/100);
                            $pbjt      = $sub * 0.10;
                            $total     = $sub + $pbjt;
                        @endphp
                        <tr>
                            <td style="color:#B8B2A6;font-size:12px">{{ $idx+1 }}</td>
                            <td style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $line->product?->sku ?? '—' }}</td>
                            <td style="font-size:13px;font-weight:500;color:#1E1C1A">{{ $line->product?->name ?? '—' }}</td>
                            <td style="text-align:right;font-size:13px">{{ $qty }}</td>
                            <td style="text-align:right;font-size:12.5px">Rp {{ number_format($unitPrice, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:12.5px;color:#D97706">{{ $disc > 0 ? $disc.'%' : '—' }}</td>
                            <td style="text-align:right;font-size:12.5px">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:12.5px;color:#6B6560">Rp {{ number_format($pbjt, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:13px;font-weight:700">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" style="text-align:center;color:#B8B2A6;padding:24px;font-size:12.5px">No line items</td></tr>
                        @endforelse
                    </tbody>
                    @php
                        $subtotal   = $quotation->lines->sum(fn($l) => ($l->quantity??0)*($l->unit_price??0)*(1-(($l->discount_percent??0)/100)));
                        $pbjt       = $subtotal * 0.10;
                        $grandTotal = $subtotal + $pbjt;
                    @endphp
                    <tfoot style="background:#FAFAF9">
                        <tr>
                            <td colspan="6" style="text-align:right;padding:10px 16px;font-size:12px;color:#8A8478;font-weight:700">SUBTOTAL</td>
                            <td colspan="3" style="text-align:right;padding:10px 16px;font-size:13px;font-weight:700">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" style="text-align:right;padding:4px 16px;font-size:12px;color:#8A8478;font-weight:700">PBJT (10%)</td>
                            <td colspan="3" style="text-align:right;padding:4px 16px;font-size:13px">Rp {{ number_format($pbjt, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="border-top:2px solid #E8E4DA">
                            <td colspan="6" style="text-align:right;padding:12px 16px;font-size:13px;font-weight:700;color:#343130">GRAND TOTAL</td>
                            <td colspan="3" style="text-align:right;padding:12px 16px;font-size:1.1rem;font-weight:700;color:#18382E">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Notes & Terms --}}
        @if($quotation->notes || $quotation->terms_conditions)
        <div class="card">
            <div class="card-header"><span class="card-title">Notes &amp; Terms</span></div>
            <div class="card-body">
                @if($quotation->notes)
                <div style="margin-bottom:14px">
                    <div class="section-title" style="margin-bottom:6px">Notes</div>
                    <div style="font-size:13px;color:#4D4944;line-height:1.6;white-space:pre-line">{{ $quotation->notes }}</div>
                </div>
                @endif
                @if($quotation->terms_conditions)
                <div>
                    <div class="section-title" style="margin-bottom:6px">Terms &amp; Conditions</div>
                    <div style="font-size:13px;color:#4D4944;line-height:1.6;white-space:pre-line">{{ $quotation->terms_conditions }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Right --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        {{-- Order Details --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Order Details</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Customer</div>
                        @if($quotation->customer)
                        <a href="{{ route('sales.customers.show', $quotation->customer_id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $quotation->customer->name }}</a>
                        @else <div style="color:#B8B2A6">—</div> @endif
                    </div>
                    @if($quotation->lead)
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Lead Reference</div>
                        <a href="{{ route('crm.leads.show', $quotation->lead) }}" style="font-size:13px;color:#18382E;text-decoration:none">{{ $quotation->lead->lead_id }}</a>
                    </div>
                    @endif
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Salesperson</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $quotation->salesperson?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Date</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}</div>
                    </div>
                    @if($quotation->expiration_date)
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Expiry</div>
                        @php $expPast = \Carbon\Carbon::parse($quotation->expiration_date)->isPast(); @endphp
                        <div style="font-size:13.5px;color:{{ $expPast && !in_array($quotation->status,['Converted','Accepted']) ? '#DC2626' : '#1E1C1A' }};font-weight:{{ $expPast ? '600' : '400' }}">{{ \Carbon\Carbon::parse($quotation->expiration_date)->format('d M Y') }}</div>
                    </div>
                    @endif
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Payment Terms</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $quotation->customer?->payment_terms ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financials --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Financials</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:13px;color:#6B6560">Subtotal</span>
                        <span style="font-size:13px;font-weight:600">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($quotation->discount_percent > 0)
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:13px;color:#D97706">Discount ({{ $quotation->discount_percent }}%)</span>
                        <span style="font-size:13px;font-weight:600;color:#D97706">- Rp {{ number_format($subtotal * $quotation->discount_percent / 100, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:13px;color:#6B6560">PBJT (10%)</span>
                        <span style="font-size:13px;font-weight:600">Rp {{ number_format($pbjt, 0, ',', '.') }}</span>
                    </div>
                    <div style="border-top:2px solid #E8E4DA;padding-top:10px;display:flex;justify-content:space-between">
                        <span style="font-size:14px;font-weight:700;color:#343130">Grand Total</span>
                        <span style="font-size:1.1rem;font-weight:700;color:#C6A76B">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

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
                        <div class="section-title" style="margin-bottom:2px">Approved/Reviewed By</div>
                        <div style="font-size:13px;color:#1E1C1A">{{ $approvalRecord->approvedBy?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Date</div>
                        <div style="font-size:13px;color:#1E1C1A">{{ \Carbon\Carbon::parse($approvalRecord->updated_at)->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(($quotation->discount_percent ?? 0) > 10)
        <div class="card">
            <div class="card-header">
                <span class="card-title">Discount Approval</span>
                <span class="badge b-amber">Required</span>
            </div>
            <div class="card-body">
                <p style="font-size:12.5px;color:#6B6560;margin:0">This quotation has a discount above 10% and requires Finance/Owner approval before confirming.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
