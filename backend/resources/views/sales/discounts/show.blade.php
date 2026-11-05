@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.discounts.index') }}">Discount Approvals</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $approval->approval_code }}</span>
@endsection

@section('content')
@php
    $statusMap = ['Pending'=>'b-amber','Approved'=>'b-green','Rejected'=>'b-red'];
    $typeMap   = ['quotation'=>'b-blue','sales_order'=>'b-purple'];
    $canAct    = $approval->status === 'Pending' && in_array(auth()->user()->role ?? '', ['owner','finance']);
@endphp
<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px">
            <div class="page-title">{{ $approval->approval_code }}</div>
            <span class="badge {{ $statusMap[$approval->status] ?? 'b-gray' }}">{{ $approval->status }}</span>
        </div>
        <div class="page-desc" style="margin-top:4px">Discount approval request</div>
    </div>
    <a href="{{ route('sales.discounts.index') }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Request Details --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Request Details</span></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Approval Code</div>
                        <div style="font-size:13.5px;color:#1E1C1A;font-family:monospace;font-weight:600">{{ $approval->approval_code }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Document Type</div>
                        <span class="badge {{ $typeMap[$approval->document_type] ?? 'b-gray' }}">{{ ucfirst(str_replace('_',' ',$approval->document_type ?? '')) }}</span>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Document</div>
                        @if($approval->document_type === 'quotation' && $approval->quotation)
                        <a href="{{ route('sales.quotations.show', $approval->quotation) }}" style="font-family:monospace;font-size:13px;color:#18382E;text-decoration:none;font-weight:600">{{ $approval->quotation->quotation_id }}</a>
                        @elseif($approval->document_type === 'sales_order' && $approval->salesOrder)
                        <a href="{{ route('sales.orders.show', $approval->salesOrder) }}" style="font-family:monospace;font-size:13px;color:#18382E;text-decoration:none;font-weight:600">{{ $approval->salesOrder->order_id }}</a>
                        @else
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->document_id ?? '—' }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Customer</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->customer?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Discount %</div>
                        <div style="font-size:1.6rem;font-weight:700;color:{{ ($approval->discount_percent ?? 0) > 10 ? '#D97706' : '#1E1C1A' }};line-height:1">
                            {{ $approval->discount_percent ?? 0 }}%
                        </div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Document Amount</div>
                        <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Requested By</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->requestedBy?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Requester Role</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->requestedBy?->role ?? '—' }}</div>
                    </div>
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Requested At</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($approval->created_at)->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Cards (only for pending + authorized users) --}}
        @if($canAct)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            {{-- Approve --}}
            <div class="card">
                <div class="card-header" style="background:#ECFDF5">
                    <span class="card-title" style="color:#065F46">Approve Request</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales.discounts.approve', $approval) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Approval Notes (optional)</label>
                            <textarea name="approval_notes" class="form-input form-textarea" rows="3" placeholder="Add notes if needed..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Approve Discount
                        </button>
                    </form>
                </div>
            </div>

            {{-- Reject --}}
            <div class="card">
                <div class="card-header" style="background:#FEF2F2">
                    <span class="card-title" style="color:#991B1B">Reject Request</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales.discounts.reject', $approval) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Rejection Reason <span style="color:#DC2626">*</span></label>
                            <textarea name="rejection_reason" class="form-input form-textarea" rows="3" placeholder="Explain why this discount is rejected..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Reject
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Resolution (if resolved) --}}
        @if(in_array($approval->status, ['Approved','Rejected']))
        <div class="card">
            <div class="card-header">
                <span class="card-title">Resolution</span>
                <span class="badge {{ $approval->status === 'Approved' ? 'b-green' : 'b-red' }}">{{ $approval->status }}</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">{{ $approval->status === 'Approved' ? 'Approved' : 'Rejected' }} By</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->approvedBy?->name ?? $approval->rejectedBy?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Role</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->approvedBy?->role ?? $approval->rejectedBy?->role ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Date</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($approval->updated_at)->format('d M Y, H:i') }}</div>
                    </div>
                    @if($approval->status === 'Approved' && $approval->approval_notes)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Notes</div>
                        <div style="font-size:13px;color:#4D4944">{{ $approval->approval_notes }}</div>
                    </div>
                    @endif
                    @if($approval->status === 'Rejected' && $approval->rejection_reason)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Rejection Reason</div>
                        <div style="font-size:13px;color:#DC2626">{{ $approval->rejection_reason }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        {{-- Discount Summary --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Discount Summary</span></div>
            <div class="card-body" style="text-align:center">
                <div style="width:80px;height:80px;border-radius:50%;background:#FFFBEB;border:3px solid #FDE68A;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                    <span style="font-size:1.5rem;font-weight:700;color:#C6A76B">{{ $approval->discount_percent ?? 0 }}%</span>
                </div>
                <div style="font-size:12px;color:#8A8478;margin-bottom:14px">Requested Discount</div>
                <div style="display:flex;flex-direction:column;gap:8px;text-align:left">
                    <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F7F5F0;border-radius:7px">
                        <span style="font-size:12.5px;color:#6B6560">Document Total</span>
                        <span style="font-size:12.5px;font-weight:700">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F7F5F0;border-radius:7px">
                        <span style="font-size:12.5px;color:#6B6560">Net After Discount</span>
                        @php $net = ($approval->document_amount ?? 0) * (1 - ($approval->discount_percent ?? 0) / 100); @endphp
                        <span style="font-size:12.5px;font-weight:700;color:#18382E">Rp {{ number_format($net, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Policy Reference --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Discount Policy</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#F7F5F0;border-radius:7px">
                        <div style="width:8px;height:8px;border-radius:50%;background:#059669;flex-shrink:0;margin-top:4px"></div>
                        <div>
                            <div style="font-size:12.5px;font-weight:700;color:#343130">0 – 10%</div>
                            <div style="font-size:11.5px;color:#6B6560">No approval required</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:7px">
                        <div style="width:8px;height:8px;border-radius:50%;background:#D97706;flex-shrink:0;margin-top:4px"></div>
                        <div>
                            <div style="font-size:12.5px;font-weight:700;color:#92400E">11 – 15%</div>
                            <div style="font-size:11.5px;color:#78350F;font-weight:500">Finance / Owner approval required</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#FEF2F2;border-radius:7px">
                        <div style="width:8px;height:8px;border-radius:50%;background:#DC2626;flex-shrink:0;margin-top:4px"></div>
                        <div>
                            <div style="font-size:12.5px;font-weight:700;color:#991B1B">&gt; 15%</div>
                            <div style="font-size:11.5px;color:#DC2626">Not permitted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
