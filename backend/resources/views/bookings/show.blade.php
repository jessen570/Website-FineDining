@extends('layouts.app')

@section('title', 'Reservasi ' . $booking->booking_id . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('bookings.index') }}">Reservasi</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $booking->booking_id }}</span>
@endsection

@section('content')
<div style="max-width:900px">

{{-- ── Flash ──────────────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="flash-error mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── Status Bar / Lifecycle Pipeline (wajib untuk dokumen dengan lifecycle) ── --}}
@php
    $stages   = ['Draft', 'Confirmed', 'Checked-In', 'Done'];
    $curIdx   = array_search($booking->status, $stages);
    $cancelled = $booking->status === 'Cancelled';

    // Smart button counts
    $linkedOrders = \App\Models\TableOrder::where('booking_id', $booking->booking_id)->get();
    $linkedInvoices = collect();
    foreach ($linkedOrders as $to) {
        $linkedInvoices = $linkedInvoices->merge($to->invoices);
    }
@endphp

{{-- Smart Buttons (Odoo pattern — relasi dokumen terkait) --}}
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    @if($linkedOrders->count())
    <a href="{{ route('table-orders.show', $linkedOrders->first()->order_id) }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:all 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5"/></svg>
        Table Order
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">
            {{ $linkedOrders->count() }}
        </span>
    </a>
    @endif

    @if($linkedInvoices->count())
    <a href="{{ route('invoices.index') }}"
       style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;
              background:white;border:1px solid #E8E4DA;text-decoration:none;font-size:12.5px;
              color:#18382E;font-weight:600;transition:all 0.12s"
       onmouseover="this.style.borderColor='#C6A76B'" onmouseout="this.style.borderColor='#E8E4DA'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/></svg>
        Invoice
        <span style="background:#18382E;color:white;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700">
            {{ $linkedInvoices->count() }}
        </span>
    </a>
    @endif
</div>

{{-- Status Bar --}}
<div class="card mb-5">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#8A8478">
                LIFECYCLE RESERVASI
            </span>
            @if($cancelled)
                <span class="badge b-red">DIBATALKAN</span>
            @endif
            @if($booking->is_vip)
                <span class="badge b-gold" style="font-size:11px">★ VIP — Irisan maks 30 menit (SR-03)</span>
            @endif
        </div>
        @if(!$cancelled)
        <div class="status-pipeline">
            @foreach($stages as $i => $stage)
            <div class="pipeline-step {{ $i < $curIdx ? 'done' : ($i == $curIdx ? 'active' : '') }}">
                <div class="pipeline-dot">
                    @if($i < $curIdx)
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </div>
                <span class="pipeline-label">{{ $stage }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Header Card dengan action buttons ─────────────────────────────── --}}
<div class="card mb-5">
    <div class="card-header">
        <div>
            <span class="card-title" style="font-size:16px">{{ $booking->booking_id }}</span>
            <span class="ml-2 badge {{ ['Draft'=>'b-gray','Confirmed'=>'b-blue','Checked-In'=>'b-amber','Done'=>'b-emerald','Cancelled'=>'b-red'][$booking->status] ?? 'b-gray' }}">
                {{ $booking->status }}
            </span>
            @if($booking->is_vip)
                <span class="badge b-gold ml-1">★ VIP</span>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if(in_array($booking->status, ['Draft','Confirmed']))
                <a href="{{ route('bookings.edit', $booking->booking_id) }}" class="btn btn-outline btn-sm">Edit</a>
            @endif
            @if($booking->status === 'Draft')
                <form method="POST" action="{{ route('bookings.confirm', $booking->booking_id) }}">
                    @csrf
                    <button class="btn btn-success btn-sm">✓ Konfirmasi</button>
                </form>
            @elseif($booking->status === 'Confirmed')
                <form method="POST" action="{{ route('bookings.checkin', $booking->booking_id) }}">
                    @csrf
                    <button class="btn btn-gold btn-sm">Check-In</button>
                </form>
            @endif
            @if(!in_array($booking->status, ['Done','Cancelled']))
                <form method="POST" action="{{ route('bookings.cancel', $booking->booking_id) }}"
                    onsubmit="return confirm('Batalkan reservasi {{ $booking->booking_id }}?')">
                    @csrf
                    <button class="btn btn-danger btn-sm">Batalkan</button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

            {{-- Tamu --}}
            <div>
                <div class="section-title">Tamu / Pelanggan</div>
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <tr><td style="padding:5px 0;color:#8A8478;width:45%">ID Kontak</td>
                        <td><a href="{{ route('contacts.show', $booking->customer_id) }}" class="font-semibold" style="color:#18382E">{{ $booking->customer_id }}</a></td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Nama</td>
                        <td class="font-semibold">{{ $booking->customer?->name ?? '—' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Kota</td>
                        <td>{{ $booking->customer?->city ?? '—' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Jumlah Tamu</td>
                        <td><strong>{{ $booking->party_size }}</strong>
                            <span style="color:#8A8478;font-size:11px"> / {{ $booking->table?->seats }} kursi tersedia</span>
                        </td>
                    </tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Tipe Tamu</td>
                        <td>
                            @if($booking->is_vip)
                                <span class="badge b-gold">★ VIP</span>
                                <span style="font-size:11px;color:#8A8478;margin-left:6px">Irisan waktu maks 30 mnt (SR-03)</span>
                            @else
                                <span style="font-size:12px;color:#6B6560">Regular</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Jadwal & Meja --}}
            <div>
                <div class="section-title">Jadwal &amp; Meja</div>
                <table style="width:100%;font-size:13px;border-collapse:collapse">
                    <tr><td style="padding:5px 0;color:#8A8478;width:45%">Meja</td>
                        <td>
                            <span class="badge b-forest">{{ $booking->table_id }}</span>
                            <span style="font-size:11px;color:#8A8478;margin-left:6px">{{ $booking->table?->floor }}</span>
                        </td>
                    </tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Tanggal</td>
                        <td class="font-semibold">{{ $booking->booking_date?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Mulai</td>
                        <td class="font-semibold" style="color:#18382E">
                            {{ $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('H:i') : '—' }}
                        </td>
                    </tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Selesai Layanan</td>
                        <td>{{ $booking->end_time ? \Carbon\Carbon::parse($booking->end_time)->format('H:i') : '—' }}
                            <span style="font-size:10px;color:#B8B2A6"> (+120 menit)</span></td></tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Buffer Bersih</td>
                        <td style="color:#D97706;font-weight:600">
                            {{ $booking->buffer_end_time ? \Carbon\Carbon::parse($booking->buffer_end_time)->format('H:i') : '—' }}
                            <span style="font-size:10px;color:#B8B2A6;font-weight:400"> (+15 menit SR-22)</span>
                        </td>
                    </tr>
                    <tr><td style="padding:5px 0;color:#8A8478">Server</td>
                        <td>
                            @if($booking->assignedServer)
                                <a href="{{ route('employees.show', $booking->assigned_server_id) }}" style="color:#18382E;font-weight:600">
                                    {{ $booking->assignedServer->name }}
                                </a>
                                @if($booking->assignedServer->status !== 'Aktif')
                                    <span class="badge b-red ml-1" style="font-size:9px">Tidak Aktif</span>
                                @endif
                            @else
                                <span style="color:#B8B2A6">(Belum ditugaskan)</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($booking->notes)
            <hr class="divider">
            <div class="section-title">Catatan / Permintaan Khusus</div>
            <p style="font-size:13px;color:#4D4944;background:#FAFAF9;padding:10px 14px;border-radius:8px;border:1px solid #F0EEE8">
                {{ $booking->notes }}
            </p>
        @endif
    </div>
</div>

{{-- ── Check-In → Table Order shortcut ───────────────────────────────── --}}
@if($booking->status === 'Checked-In' && $linkedOrders->isEmpty())
<div class="card mb-5" style="border-color:rgba(198,167,107,0.5);background:linear-gradient(135deg,#FFFBEB,#FFF)">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
        <div>
            <div style="font-weight:700;color:#18382E;margin-bottom:4px">✓ Tamu sudah Check-In!</div>
            <div style="font-size:12.5px;color:#6B6560">
                Buka Table Order untuk mencatat pesanan makanan &amp; minuman. Server aktif wajib ditugaskan (SR-13).
            </div>
        </div>
        <a href="{{ route('table-orders.create') }}?booking_id={{ $booking->booking_id }}&table_id={{ $booking->table_id }}"
           class="btn btn-gold" style="white-space:nowrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"/></svg>
            Buka Table Order
        </a>
    </div>
</div>
@endif

{{-- ── Related Table Orders ──────────────────────────────────────────── --}}
@if($linkedOrders->count())
<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Table Order Terkait</span>
        <span class="badge b-amber">{{ $linkedOrders->count() }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th><th>Server</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">PBJT 10%</th>
                    <th class="text-right">Total</th>
                    <th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linkedOrders as $to)
                @php $sm=['Open'=>'b-amber','Served'=>'b-blue','Billed'=>'b-purple','Paid'=>'b-emerald','Cancelled'=>'b-red']; @endphp
                <tr>
                    <td class="col-id">{{ $to->order_id }}</td>
                    <td class="text-xs">{{ $to->server?->name ?? '—' }}</td>
                    <td class="col-num text-right">Rp {{ number_format($to->subtotal, 0, ',', '.') }}</td>
                    <td class="col-num text-right text-amber-700">Rp {{ number_format($to->pbjt_amount, 0, ',', '.') }}</td>
                    <td class="col-num text-right font-semibold">Rp {{ number_format($to->gross_amount, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $sm[$to->status] ?? 'b-gray' }}">{{ $to->status }}</span></td>
                    <td><a href="{{ route('table-orders.show', $to->order_id) }}" class="btn btn-outline btn-xs">Buka</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Audit Log / History ─────────────────────────────────────────────── --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'Booking')
        ->where('document_id', $booking->booking_id)
        ->orderByDesc('created_at')->limit(20)->get();
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
                <tr>
                    <th>Waktu</th>
                    <th>Aksi</th>
                    <th>Aktor</th>
                    <th>Peran</th>
                    <th>Pesan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                <tr>
                    <td class="text-xs text-stone-500" style="white-space:nowrap">
                        {{ $log->created_at?->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                        <span class="badge b-blue" style="font-size:10px">{{ $log->action }}</span>
                    </td>
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
        <p style="font-size:12.5px;color:#B8B2A6;text-align:center">Belum ada riwayat perubahan tercatat.</p>
    </div>
    @endif
</div>

</div>
@endsection
