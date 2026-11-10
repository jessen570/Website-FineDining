@extends('layouts.app')

@section('title', 'Table Order ' . $order->order_id . ' — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('table-orders.index') }}">Pesanan Meja</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $order->order_id }}</span>
@endsection

@section('content')
<div style="max-width:960px">

{{-- ── Flash ──────────────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="flash-error mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── Status Pipeline ────────────────────────────────────────────────── --}}
@php
    $stages  = ['Open','Served','Billed','Paid'];
    $curIdx  = array_search($order->status, $stages);
    $isClosed= in_array($order->status, ['Paid','Cancelled']);
@endphp
<div class="card mb-5">
    <div class="card-body" style="padding:16px 24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#8A8478">LIFECYCLE PESANAN</span>
            @if($order->status === 'Cancelled')
                <span class="badge b-red">DIBATALKAN</span>
            @endif
        </div>
        @if($order->status !== 'Cancelled')
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

{{-- ── Header ─────────────────────────────────────────────────────────── --}}
<div class="card mb-5">
    <div class="card-header">
        <div>
            <span class="card-title" style="font-size:16px">{{ $order->order_id }}</span>
            @php
                $statusMap = ['Open'=>'b-amber','Served'=>'b-blue','Billed'=>'b-purple','Paid'=>'b-emerald','Cancelled'=>'b-red'];
            @endphp
            <span class="ml-2 badge {{ $statusMap[$order->status] ?? 'b-gray' }}">{{ $order->status }}</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            {{-- Serve --}}
            @if($order->status === 'Open')
                <form method="POST" action="{{ route('table-orders.serve', $order->order_id) }}">
                    @csrf
                    <button class="btn btn-gold btn-sm"
                        onclick="return confirm('Tandai semua item sebagai Served? Stok akan dikurangi.')">
                        🍽 Sajikan
                    </button>
                </form>
            @endif
            {{-- Bill --}}
            @if(in_array($order->status, ['Open','Served']) && $order->invoices->where('status','!=','Cancelled')->isEmpty())
                <form method="POST" action="{{ route('table-orders.bill', $order->order_id) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm"
                        onclick="return confirm('Buat invoice untuk pesanan ini?')">
                        🧾 Buat Tagihan
                    </button>
                </form>
            @endif
            {{-- Cancel --}}
            @if(!$isClosed)
                <form method="POST" action="{{ route('table-orders.cancel', $order->order_id) }}"
                    onsubmit="return confirm('Batalkan pesanan {{ $order->order_id }}? Reservasi stok akan dilepas.')">
                    @csrf
                    <button class="btn btn-danger btn-sm">Batalkan</button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
            <div>
                <div class="section-title">Meja &amp; Server</div>
                <table style="font-size:13px;border-collapse:collapse;width:100%">
                    <tr><td style="color:#8A8478;padding:4px 0;width:45%">Meja</td>
                        <td><span class="badge b-forest">{{ $order->table_id }}</span>
                            <span class="ml-1 text-xs text-stone-500">{{ $order->table?->floor }}</span></td></tr>
                    <tr><td style="color:#8A8478;padding:4px 0">Server</td>
                        <td class="font-semibold">{{ $order->server?->name ?? '—' }}</td></tr>
                    <tr><td style="color:#8A8478;padding:4px 0">Pelanggan</td>
                        <td>{{ $order->customer?->name ?? 'Walk-in' }}</td></tr>
                </table>
            </div>
            <div>
                <div class="section-title">Reservasi Terkait</div>
                @if($order->booking)
                    <a href="{{ route('bookings.show', $order->booking->booking_id) }}"
                       class="badge b-blue" style="text-decoration:none">
                        {{ $order->booking->booking_id }}
                    </a>
                    <div style="font-size:12px;color:#8A8478;margin-top:4px">
                        {{ $order->booking->start_time ? \Carbon\Carbon::parse($order->booking->start_time)->format('d/m/Y H:i') : '' }}
                    </div>
                @else
                    <span style="font-size:13px;color:#B8B2A6">Walk-in (tanpa reservasi)</span>
                @endif
            </div>
            <div>
                <div class="section-title">Ringkasan Biaya</div>
                <table style="font-size:13px;border-collapse:collapse;width:100%">
                    <tr><td style="color:#8A8478;padding:4px 0">Subtotal</td>
                        <td class="text-right font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
                    <tr><td style="color:#8A8478;padding:4px 0">PBJT 10%</td>
                        <td class="text-right text-amber-700">Rp {{ number_format($order->pbjt_amount, 0, ',', '.') }}</td></tr>
                    <tr style="border-top:1px solid #F0EEE8">
                        <td style="padding:6px 0;font-weight:700">Total Tagihan</td>
                        <td class="text-right font-bold" style="font-size:15px">Rp {{ number_format($order->gross_amount, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Add Item Form ───────────────────────────────────────────────────── --}}
@if($order->status === 'Open')
<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Tambah Item Pesanan</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('table-orders.line.add', $order->order_id) }}">
        @csrf
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label">Produk / Menu *</label>
                <select name="variant_sku" class="form-select" required>
                    <option value="">— Pilih Item —</option>
                    <optgroup label="🍽 Makanan (KITCH-JKT)">
                        @foreach($products->filter(fn($p) => str_starts_with($p->variant_sku, 'FD')) as $pr)
                        <option value="{{ $pr->variant_sku }}" {{ old('variant_sku') == $pr->variant_sku ? 'selected' : '' }}>
                            {{ $pr->variant_sku }} — {{ $pr->name }}
                            (Rp {{ number_format($pr->sales_price, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="🥤 Minuman (BAR-JKT)">
                        @foreach($products->filter(fn($p) => str_starts_with($p->variant_sku, 'BV')) as $pr)
                        <option value="{{ $pr->variant_sku }}" {{ old('variant_sku') == $pr->variant_sku ? 'selected' : '' }}>
                            {{ $pr->variant_sku }} — {{ $pr->name }}
                            (Rp {{ number_format($pr->sales_price, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Qty *</label>
                <input type="number" name="quantity" class="form-input" value="{{ old('quantity', 1) }}" min="1" step="1" required>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Diskon %</label>
                <input type="number" name="discount" class="form-input" value="{{ old('discount', 0) }}"
                    min="0" max="15" step="0.5" placeholder="0–15">
                <p class="form-hint">&gt;10% butuh approval (SR-08)</p>
            </div>
            <div>
                <button type="submit" class="btn btn-success" style="margin-bottom:0">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah
                </button>
            </div>
        </div>
        </form>
    </div>
</div>
@endif

{{-- ── Order Lines ─────────────────────────────────────────────────────── --}}
<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Item Pesanan</span>
        <span class="badge b-amber">{{ $order->lines->count() }} item</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">PBJT 10%</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->lines as $i => $line)
                @php
                    $lineStatusMap = ['Ordered'=>'b-amber','Served'=>'b-blue','Invoiced'=>'b-purple'];
                @endphp
                <tr>
                    <td class="text-stone-500">{{ $i + 1 }}</td>
                    <td class="col-id">{{ $line->variant_sku }}</td>
                    <td class="col-name">{{ $line->product?->name ?? $line->variant_sku }}</td>
                    <td class="col-num text-right">Rp {{ number_format($line->unit_price, 0, ',', '.') }}</td>
                    <td class="col-num text-right">{{ $line->quantity }}</td>
                    <td class="col-num text-right">
                        @php $disc = $line->unit_price > 0 ? (1 - ($line->subtotal / ($line->quantity * $line->unit_price))) * 100 : 0 @endphp
                        {{ $disc > 0 ? number_format($disc, 1) . '%' : '—' }}
                    </td>
                    <td class="col-num text-right">Rp {{ number_format($line->subtotal, 0, ',', '.') }}</td>
                    <td class="col-num text-right text-amber-700">Rp {{ number_format($line->pbjt_amount, 0, ',', '.') }}</td>
                    <td class="col-num text-right font-semibold">Rp {{ number_format($line->total_amount, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $lineStatusMap[$line->status] ?? 'b-gray' }}">{{ $line->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="empty-state" style="padding:28px">
                            <div class="empty-title">Belum ada item</div>
                            <div class="empty-text">Gunakan form di atas untuk menambahkan makanan atau minuman.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($order->lines->count())
            <tfoot>
                <tr style="background:#FAFAF9">
                    <td colspan="6" style="padding:12px 16px;text-align:right;font-weight:700;font-size:12px;color:#8A8478;text-transform:uppercase;letter-spacing:0.08em">Subtotal Bersih</td>
                    <td class="col-num text-right font-semibold" style="padding:12px 16px">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                    <td class="col-num text-right font-semibold text-amber-700" style="padding:12px 16px">Rp {{ number_format($order->pbjt_amount, 0, ',', '.') }}</td>
                    <td colspan="2" style="padding:12px 16px"></td>
                </tr>
                <tr style="background:#F0FDF4">
                    <td colspan="8" style="padding:12px 16px;text-align:right;font-weight:800;font-size:13px;color:#1E1C1A">TOTAL TAGIHAN</td>
                    <td class="col-num text-right" style="padding:12px 16px;font-weight:800;font-size:15px;color:#18382E">
                        Rp {{ number_format($order->gross_amount, 0, ',', '.') }}
                    </td>
                    <td style="padding:12px 16px"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Linked Invoices ─────────────────────────────────────────────────── --}}
@if($order->invoices->count())
<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Invoice Terkait</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Invoice ID</th><th>Tanggal</th><th class="text-right">Total</th><th class="text-right">Dibayar</th><th class="text-right">Sisa</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach($order->invoices as $inv)
                @php $is=['Draft'=>'b-gray','Posted'=>'b-blue','Paid'=>'b-emerald','Cancelled'=>'b-red']; @endphp
                <tr>
                    <td class="col-id">{{ $inv->invoice_id }}</td>
                    <td class="text-xs">{{ $inv->invoice_date }}</td>
                    <td class="col-num text-right">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                    <td class="col-num text-right text-emerald-700">Rp {{ number_format($inv->amount_paid, 0, ',', '.') }}</td>
                    <td class="col-num text-right {{ $inv->amount_due > 0 ? 'text-red-600' : '' }}">
                        Rp {{ number_format($inv->amount_due, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $is[$inv->status] ?? 'b-gray' }}">{{ $inv->status }}</span></td>
                    <td><a href="{{ route('invoices.show', $inv->invoice_id) }}" class="btn btn-outline btn-xs">Detail</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Split Bill (SR-23) ─────────────────────────────────────────────── --}}
@if(in_array($order->status, ['Open','Served']) && $order->invoices->where('status','!=','Cancelled')->isEmpty())
<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Split Bill (SR-23)</span>
        <span class="badge b-gold">Pembagian Tagihan</span>
    </div>
    <div class="card-body">
        <p style="font-size:12.5px;color:#6B6560;margin-bottom:14px">
            Atur jumlah bagian tagihan. Total subtotal + PBJT 10% seluruh bagian dijamin sama dengan order asli tanpa penggandaan item (SR-23).
        </p>
        <form method="POST" action="{{ route('table-orders.split', $order->order_id) }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap">
            @csrf
            <div class="form-group" style="margin:0;flex:0 0 180px">
                <label class="form-label">Jumlah Bagian</label>
                <input type="number" name="split_count" class="form-input"
                    value="{{ $order->split_count }}" min="1" max="10" required>
                <p class="form-hint">1 = tidak dibagi, maks 10</p>
            </div>
            <div style="padding-bottom:20px">
                <button type="submit" class="btn btn-gold btn-sm">Terapkan Split</button>
            </div>
            @if($order->split_count > 1)
            <div style="padding-bottom:20px">
                <div style="background:#F7F5F0;border:1px solid #E8E4DA;border-radius:8px;padding:8px 14px;font-size:12.5px;color:#343130">
                    Tagihan dibagi <strong>{{ $order->split_count }}</strong> bagian ·
                    Per bagian: <strong style="color:#18382E">Rp {{ number_format($order->gross_amount / $order->split_count, 0, ',', '.') }}</strong>
                    <span style="font-size:11px;color:#8A8478">(termasuk PBJT)</span>
                </div>
            </div>
            @endif
        </form>
    </div>
</div>
@elseif($order->split_count > 1)
<div class="card mb-5" style="border-color:rgba(198,167,107,0.4)">
    <div class="card-body" style="display:flex;align-items:center;gap:12px">
        <span class="badge b-gold">Split Bill ÷{{ $order->split_count }}</span>
        <span style="font-size:13px">Tagihan dibagi <strong>{{ $order->split_count }}</strong> bagian ·
            Per bagian: <strong>Rp {{ number_format($order->gross_amount / $order->split_count, 0, ',', '.') }}</strong>
            · Jumlah total tetap: <strong style="color:#18382E">Rp {{ number_format($order->gross_amount, 0, ',', '.') }}</strong>
            (SR-23: tidak ada penggandaan item)
        </span>
    </div>
</div>
@endif

{{-- ── Notes ───────────────────────────────────────────────────────────── --}}
@if($order->notes)
<div class="card mb-5">
    <div class="card-header"><span class="card-title">Catatan</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:#4D4944">{{ $order->notes }}</p>
    </div>
</div>
@endif

{{-- ── Audit Log ─────────────────────────────────────────────────────────── --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'TableOrder')
        ->where('document_id', $order->order_id)
        ->orderByDesc('created_at')->limit(10)->get();
@endphp
@if($auditLogs->count())
<div class="card">
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
