@extends('layouts.app')

@section('title', ($contact->name ?? 'Kontak') . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Kontak</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $contact->contact_id }}</span>
@endsection

@section('content')
<div style="max-width:900px">

@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="flash-error mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── Smart Buttons (Odoo pattern) ──────────────────────────────────── --}}
@php
    $soCount  = $contact->contact_type === 'Customer'
        ? \App\Models\SalesOrder::where('customer_id', $contact->contact_id)->count() : 0;
    $invCount = \App\Models\Invoice::where('partner_id', $contact->contact_id)->count();
    $poCount  = $contact->contact_type === 'Vendor'
        ? \App\Models\PurchaseOrder::where('vendor_id', $contact->contact_id)->count() : 0;
@endphp
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    @if($soCount > 0)
    <a href="{{ route('sales.orders.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:border-color 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg>
        Sales Orders
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">{{ $soCount }}</span>
    </a>
    @endif
    @if($invCount > 0)
    <a href="{{ route('invoices.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:border-color 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        Invoice
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">{{ $invCount }}</span>
    </a>
    @endif
    @if($poCount > 0)
    <a href="{{ route('purchase.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:border-color 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Purchase Orders
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">{{ $poCount }}</span>
    </a>
    @endif
</div>

{{-- ── Header Card ─────────────────────────────────────────────────────── --}}
<div class="card mb-5">
    <div class="card-header">
        <div>
            <span class="card-title" style="font-size:16px">{{ $contact->name }}</span>
            <span class="badge {{ $contact->contact_type === 'Customer' ? 'b-blue' : 'b-amber' }} ml-2">
                {{ $contact->contact_type }}
            </span>
            @if($contact->customer_type)
                <span class="badge b-gray ml-1" style="font-size:10px">{{ $contact->customer_type }}</span>
            @endif
        </div>
        <a href="{{ route('contacts.edit', $contact->contact_id) }}" class="btn btn-outline btn-sm">Edit</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

            {{-- Identitas --}}
            <div>
                <div class="section-title">Identitas</div>
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <tr><td style="padding:5px 0;color:#8A8478;width:45%">ID Kontak</td>
                        <td class="font-semibold text-mono">{{ $contact->contact_id }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Nama</td>
                        <td class="font-semibold">{{ $contact->name }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Kota</td>
                        <td>{{ $contact->city ?? '—' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Termin Bayar</td>
                        <td>{{ $contact->payment_terms ?? '—' }}</td></tr>
                </table>
            </div>

            {{-- Kredit (khusus Customer) --}}
            @if($contact->contact_type === 'Customer')
            <div>
                <div class="section-title">Kredit &amp; Eksposur (SR-15)</div>
                @php
                    $limit   = (float) $contact->credit_limit;
                    $exp     = (float) ($exposure ?? 0);
                    $pct     = $limit > 0 ? min(100, round(($exp / $limit) * 100)) : 0;
                    $barColor = $pct >= 100 ? '#EF4444' : ($pct >= 80 ? '#F59E0B' : '#10B981');
                @endphp
                <table style="width:100%;font-size:13px;border-collapse:collapse;margin-bottom:10px">
                    <tr><td style="padding:5px 0;color:#8A8478;width:50%">Credit Limit</td>
                        <td class="font-semibold">Rp {{ number_format($limit, 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Opening AR</td>
                        <td>Rp {{ number_format($contact->opening_ar ?? 0, 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Eksposur Saat Ini</td>
                        <td class="font-semibold" style="color:{{ $pct >= 100 ? '#DC2626' : '#18382E' }}">
                            Rp {{ number_format($exp, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Utilisasi Kredit</td>
                        <td><strong style="color:{{ $pct >= 100 ? '#DC2626' : '#18382E' }}">{{ $pct }}%</strong></td></tr>
                </table>
                @if($limit > 0)
                <div class="credit-bar">
                    <div class="credit-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                </div>
                @if($pct >= 100)
                    <p style="font-size:11px;color:#DC2626;margin-top:4px;font-weight:600">⚠ Batas kredit terlampaui — pesanan baru akan diblokir (SR-15)</p>
                @elseif($pct >= 80)
                    <p style="font-size:11px;color:#D97706;margin-top:4px">Mendekati batas kredit</p>
                @endif
                @endif
            </div>
            @else
            {{-- Vendor AP --}}
            <div>
                <div class="section-title">Utang &amp; Pembayaran</div>
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <tr><td style="padding:5px 0;color:#8A8478;width:50%">Opening AP</td>
                        <td class="font-semibold">Rp {{ number_format($contact->opening_ap ?? 0, 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Termin Bayar</td>
                        <td>{{ $contact->payment_terms ?? '—' }}</td></tr>
                </table>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- ── Audit Log ────────────────────────────────────────────────────────── --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'Contact')
        ->where('document_id', $contact->contact_id)
        ->orderByDesc('created_at')->limit(10)->get();
@endphp
<div class="card">
    <div class="card-header">
        <span class="card-title">Log Catatan &amp; Riwayat (Audit / History)</span>
        <span class="badge b-gray">{{ $auditLogs->count() }} entri</span>
    </div>
    @if($auditLogs->count())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Waktu</th><th>Aksi</th><th>Aktor</th><th>Peran</th><th>Pesan</th></tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                <tr>
                    <td class="text-xs text-stone-500">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td><span class="badge b-blue" style="font-size:10px">{{ $log->action }}</span></td>
                    <td class="font-semibold text-xs">{{ $log->actor_name }}</td>
                    <td class="text-xs text-stone-500 capitalize">{{ $log->actor_role }}</td>
                    <td class="text-xs">{{ $log->message }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body">
        <p style="font-size:12.5px;color:#B8B2A6;text-align:center">Belum ada riwayat perubahan.</p>
    </div>
    @endif
</div>

</div>
@endsection
