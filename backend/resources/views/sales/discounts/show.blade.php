@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Penjualan</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.discounts.index') }}">Persetujuan Diskon</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $approval->approval_code }}</span>
@endsection

@section('content')
@php
    $docTypeLower = strtolower((string) $approval->document_type);
    $isQuotation  = $docTypeLower === 'quotation';
    $isSalesOrder = in_array($docTypeLower, ['salesorder', 'sales_order']);
    $canAct       = $approval->status === 'Pending' && in_array(auth()->user()->role ?? '', ['owner', 'finance']);
@endphp

<x-page-header
    :title="$approval->approval_code"
    description="Detail permintaan persetujuan diskon penjualan (SR-08)"
>
    <x-slot:badge>
        <x-badge :status="$approval->status" />
    </x-slot:badge>
    <x-slot:actions>
        <a href="{{ route('sales.discounts.index') }}" class="btn btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
    </x-slot:actions>
</x-page-header>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Left --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Request Details --}}
        <x-card title="Detail Permohonan">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                <div>
                    <div class="section-title" style="margin-bottom:2px">Kode Persetujuan</div>
                    <div style="font-size:13.5px;color:#1E1C1A;font-family:monospace;font-weight:600">{{ $approval->approval_code }}</div>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Tipe Dokumen</div>
                    <span class="badge {{ $isQuotation ? 'b-blue' : 'b-purple' }}">
                        {{ $isQuotation ? 'Quotation' : ($isSalesOrder ? 'Sales Order' : ($approval->document_type ?? '—')) }}
                    </span>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Dokumen Terkait</div>
                    @if($isQuotation && $approval->quotation)
                    <a href="{{ route('sales.quotations.show', $approval->quotation->quotation_id) }}" style="font-family:monospace;font-size:13px;color:#18382E;text-decoration:none;font-weight:600">
                        {{ $approval->quotation->quotation_id }} ↗
                    </a>
                    @elseif($isSalesOrder && $approval->salesOrder)
                    <a href="{{ route('sales.orders.show', $approval->salesOrder->order_id) }}" style="font-family:monospace;font-size:13px;color:#18382E;text-decoration:none;font-weight:600">
                        {{ $approval->salesOrder->order_id }} ↗
                    </a>
                    @else
                    <div style="font-size:13.5px;font-family:monospace;color:#1E1C1A">{{ $approval->document_id ?? '—' }}</div>
                    @endif
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Pelanggan</div>
                    @if($approval->customer)
                    <a href="{{ route('sales.customers.show', $approval->customer->contact_id) }}" style="font-size:13.5px;color:#18382E;font-weight:600;text-decoration:none">
                        {{ $approval->customer->name }}
                    </a>
                    @else
                    <div style="font-size:13.5px;color:#1E1C1A">{{ $approval->customer_id ?? '—' }}</div>
                    @endif
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Persentase Diskon</div>
                    <div style="font-size:1.6rem;font-weight:700;color:{{ ($approval->discount_percent ?? 0) > 10 ? '#D97706' : '#1E1C1A' }};line-height:1">
                        {{ number_format($approval->discount_percent ?? 0, 1) }}%
                    </div>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Nilai Dokumen (Total Akhir)</div>
                    <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Diminta Oleh</div>
                    <div style="font-size:13.5px;color:#1E1C1A;font-weight:500">{{ $approval->requested_by ?? '—' }}</div>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Peran Pemohon</div>
                    <div style="font-size:13.5px;color:#1E1C1A;text-transform:capitalize">{{ $approval->requested_by_role ?? '—' }}</div>
                </div>
                <div>
                    <div class="section-title" style="margin-bottom:2px">Tanggal Permohonan</div>
                    <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($approval->created_at)->format('d M Y, H:i') }}</div>
                </div>
                @if($approval->notes)
                <div style="grid-column:span 2">
                    <div class="section-title" style="margin-bottom:2px">Catatan</div>
                    <div style="font-size:13px;color:#4D4944;background:#F7F5F0;padding:10px 12px;border-radius:7px">{{ $approval->notes }}</div>
                </div>
                @endif
            </div>
        </x-card>

        {{-- Action Cards (only for pending + authorized users) --}}
        @if($canAct)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            {{-- Approve --}}
            <div class="card">
                <div class="card-header" style="background:#ECFDF5">
                    <span class="card-title" style="color:#065F46">Setujui Permintaan</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales.discounts.approve', $approval->approval_code) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Catatan Persetujuan (opsional)</label>
                            <textarea name="notes" class="form-input form-textarea" rows="3" placeholder="Tambahkan catatan persetujuan jika diperlukan..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Setujui Diskon
                        </button>
                    </form>
                </div>
            </div>

            {{-- Reject --}}
            <div class="card">
                <div class="card-header" style="background:#FEF2F2">
                    <span class="card-title" style="color:#991B1B">Tolak Permintaan</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales.discounts.reject', $approval->approval_code) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Alasan Penolakan <span style="color:#DC2626">*</span></label>
                            <textarea name="rejection_reason" class="form-input form-textarea" rows="3" placeholder="Jelaskan alasan penolakan diskon..." required></textarea>
                            @error('rejection_reason')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Tolak Diskon
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
                <span class="card-title">Keputusan Persetujuan</span>
                <x-badge :status="$approval->status" />
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">{{ $approval->status === 'Approved' ? 'Disetujui Oleh' : 'Ditolak Oleh' }}</div>
                        <div style="font-size:13.5px;color:#1E1C1A;font-weight:600">{{ $approval->approved_by ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Peran Penyetuju</div>
                        <div style="font-size:13.5px;color:#1E1C1A;text-transform:capitalize">{{ $approval->approved_by_role ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Waktu Keputusan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($approval->approved_at ?? $approval->updated_at)->format('d M Y, H:i') }}</div>
                    </div>
                    @if($approval->status === 'Approved' && $approval->notes)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Catatan Persetujuan</div>
                        <div style="font-size:13px;color:#4D4944;background:#ECFDF5;padding:10px 12px;border-radius:7px">{{ $approval->notes }}</div>
                    </div>
                    @endif
                    @if($approval->status === 'Rejected' && $approval->rejection_reason)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Alasan Penolakan</div>
                        <div style="font-size:13px;color:#DC2626;background:#FEF2F2;padding:10px 12px;border-radius:7px">{{ $approval->rejection_reason }}</div>
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
        <x-card title="Ringkasan Diskon">
            <div style="text-align:center">
                <div style="width:80px;height:80px;border-radius:50%;background:#FFFBEB;border:3px solid #FDE68A;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                    <span style="font-size:1.5rem;font-weight:700;color:#C6A76B">{{ number_format($approval->discount_percent ?? 0, 1) }}%</span>
                </div>
                <div style="font-size:12px;color:#8A8478;margin-bottom:14px">Diskon yang Diajukan</div>
                <div style="display:flex;flex-direction:column;gap:8px;text-align:left">
                    @php
                        $docModel = $isQuotation ? $approval->quotation : ($isSalesOrder ? $approval->salesOrder : null);
                    @endphp
                    @if($docModel)
                    <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F7F5F0;border-radius:7px">
                        <span style="font-size:12.5px;color:#6B6560">Subtotal (Setelah Diskon)</span>
                        <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($docModel->subtotal ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F7F5F0;border-radius:7px">
                        <span style="font-size:12.5px;color:#6B6560">PBJT (10%)</span>
                        <span style="font-size:12.5px;font-weight:600">Rp {{ number_format($docModel->pbjt_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F7F5F0;border-radius:7px">
                        <span style="font-size:12.5px;color:#6B6560">Total Akhir Dokumen</span>
                        <span style="font-size:12.5px;font-weight:700;color:#18382E">Rp {{ number_format($approval->document_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Policy Reference --}}
        <x-card title="Kebijakan Diskon (SR-08)">
            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#F7F5F0;border-radius:7px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#059669;flex-shrink:0;margin-top:4px"></div>
                    <div>
                        <div style="font-size:12.5px;font-weight:700;color:#343130">0 – 10%</div>
                        <div style="font-size:11.5px;color:#6B6560">Wewenang standar staf (tanpa persetujuan)</div>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:7px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#D97706;flex-shrink:0;margin-top:4px"></div>
                    <div>
                        <div style="font-size:12.5px;font-weight:700;color:#92400E">11 – 15%</div>
                        <div style="font-size:11.5px;color:#78350F;font-weight:500">Memerlukan tinjauan &amp; persetujuan Finance / Owner</div>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;background:#FEF2F2;border-radius:7px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#DC2626;flex-shrink:0;margin-top:4px"></div>
                    <div>
                        <div style="font-size:12.5px;font-weight:700;color:#991B1B">&gt; 15%</div>
                        <div style="font-size:11.5px;color:#DC2626">Tidak diizinkan oleh kebijakan</div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
