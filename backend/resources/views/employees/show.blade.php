@extends('layouts.app')

@section('title', 'Karyawan ' . $employee->employee_id . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('employees.index') }}">Karyawan</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $employee->employee_id }}</span>
@endsection

@section('content')
<div style="max-width:820px">

@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- ── Smart Buttons (Odoo pattern — relasi dokumen) ──────────────────── --}}
@php
    $servedOrders   = \App\Models\TableOrder::where('server_id', $employee->employee_id)->count();
    $servedBookings = \App\Models\Booking::where('assigned_server_id', $employee->employee_id)->count();
@endphp
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    @if($servedOrders > 0)
    <a href="{{ route('table-orders.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:border-color 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"/></svg>
        Table Orders
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">{{ $servedOrders }}</span>
    </a>
    @endif
    @if($servedBookings > 0)
    <a href="{{ route('bookings.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:border-color 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservasi
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">{{ $servedBookings }}</span>
    </a>
    @endif
</div>

<div class="card mb-5">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:14px">
            {{-- Avatar --}}
            <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#18382E,#3D7A62);
                display:flex;align-items:center;justify-content:center;flex-shrink:0;
                border:2px solid rgba(198,167,107,0.3);color:#D4B87A;font-weight:800;font-size:18px">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div>
                <div style="font-size:17px;font-weight:700;color:#1E1C1A">{{ $employee->name }}</div>
                <div style="font-size:12px;color:#8A8478">{{ $employee->employee_id }} · {{ $employee->job_position }}</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            @if($employee->status === 'Aktif')
                <span class="badge b-emerald">
                    <span class="badge-dot" style="background:#059669"></span> Aktif
                </span>
            @else
                <span class="badge b-red">
                    <span class="badge-dot" style="background:#DC2626"></span> Tidak Aktif
                </span>
            @endif
            @if(in_array($role, ['owner','hr']))
                <a href="{{ route('employees.edit', $employee->employee_id) }}" class="btn btn-outline btn-sm">Edit</a>
            @endif
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    {{-- Informasi Jabatan --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Informasi Jabatan</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
                <tr><td style="padding:6px 0;color:#8A8478;width:45%">ID Karyawan</td>
                    <td class="text-mono font-semibold">{{ $employee->employee_id }}</td></tr>
                <tr><td style="padding:6px 0;color:#8A8478">Nama Lengkap</td>
                    <td class="font-semibold">{{ $employee->name }}</td></tr>
                <tr><td style="padding:6px 0;color:#8A8478">Jabatan</td>
                    <td>{{ $employee->job_position }}</td></tr>
                <tr><td style="padding:6px 0;color:#8A8478">Departemen</td>
                    <td><span class="badge b-forest">{{ $employee->department }}</span></td></tr>
                <tr><td style="padding:6px 0;color:#8A8478">Status</td>
                    <td>
                        @if($employee->status === 'Aktif')
                            <span class="badge b-emerald">Aktif</span>
                        @else
                            <span class="badge b-red">Tidak Aktif</span>
                            <span style="font-size:11px;color:#DC2626;display:block;margin-top:3px">SR-13: Tidak dapat ditugaskan</span>
                        @endif
                    </td></tr>
            </table>
        </div>
    </div>

    {{-- Kontak Privat (SR-14: hanya owner/HR) --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Kontak Privat</span>
            @if(!in_array($role, ['owner','hr']))
                <span class="badge b-amber" style="font-size:10px">SR-14: Akses Terbatas</span>
            @endif
        </div>
        <div class="card-body">
            @if(in_array($role, ['owner','hr']))
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <tr><td style="padding:6px 0;color:#8A8478;width:45%">No. HP Pribadi</td>
                        <td>{{ $employee->private_phone ?? '—' }}</td></tr>
                    <tr><td style="padding:6px 0;color:#8A8478">Email Pribadi</td>
                        <td>{{ $employee->private_email ?? '—' }}</td></tr>
                    <tr><td style="padding:6px 0;color:#8A8478">Alamat</td>
                        <td style="line-height:1.5">{{ $employee->private_address ?? '—' }}</td></tr>
                </table>
            @else
                <div style="text-align:center;padding:24px;color:#B8B2A6">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div style="font-size:13px;font-weight:600;color:#6B6560">Akses Terbatas</div>
                    <div style="font-size:12px;margin-top:4px">Informasi kontak privat hanya tersedia untuk Owner dan HR (SR-14).</div>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── SR-13 Notice ──────────────────────────────────────────────────────── --}}
@if($employee->status === 'Tidak Aktif')
<div class="card mt-5" style="border-color:#FECACA;background:#FEF2F2">
    <div class="card-body" style="display:flex;align-items:center;gap:12px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><path d="m10.29 3.86-8.69 15A2 2 0 0 0 3.27 22h17.46a2 2 0 0 0 1.67-3.14L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <div style="font-weight:700;color:#991B1B;font-size:13px">Karyawan Tidak Aktif (SR-13)</div>
            <div style="font-size:12px;color:#B91C1C;margin-top:2px">
                {{ $employee->name }} tidak dapat ditugaskan sebagai server Table Order atau di dalam Booking apa pun selama berstatus Tidak Aktif.
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Audit Log ─────────────────────────────────────────────────────────── --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'Employee')
        ->where('document_id', $employee->employee_id)
        ->orderByDesc('created_at')->limit(10)->get();
@endphp
@if($auditLogs->count())
<div class="card mt-5">
    <div class="card-header"><span class="card-title">Riwayat Perubahan (Audit Log)</span></div>
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
</div>
@endif

</div>
@endsection
