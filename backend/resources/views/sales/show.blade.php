@extends('layouts.app')

@section('title', $order->order_id . ' — Sales Order')
@section('breadcrumb')
    <a href="{{ route('sales.index') }}" class="text-gray-500 hover:text-gray-700">Penjualan</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <span class="text-gray-600">{{ $order->order_id }}</span>
@endsection

@section('content')

@php
    $statusFlow = ['Draft', 'Confirmed', 'Done'];
    $currentIdx = array_search($order->status, $statusFlow);
    $isCancelled = $order->status === 'Cancelled';
    $isLocked = $order->is_locked ?? false;
@endphp

{{-- Status Bar --}}
@if(!$isCancelled)
<div class="bg-white rounded-xl border border-surface-border shadow-sm px-6 py-4 mb-6">
    <div class="flex items-center justify-between">
        @foreach($statusFlow as $i => $step)
        <div class="flex items-center {{ $i < count($statusFlow) - 1 ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $currentIdx !== false && $i <= $currentIdx ? 'bg-primary text-white' : 'bg-gray-100 text-gray-400' }}">
                    {{ $i + 1 }}
                </div>
                <span class="text-xs mt-1 font-medium {{ $currentIdx !== false && $i <= $currentIdx ? 'text-primary' : 'text-gray-400' }}">
                    {{ $step }}
                </span>
            </div>
            @if($i < count($statusFlow) - 1)
            <div class="flex-1 h-0.5 mx-3 {{ $currentIdx !== false && $i < $currentIdx ? 'bg-primary' : 'bg-gray-200' }}"></div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@else
<div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 flex items-center gap-2">
    <i data-lucide="x-circle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
    <span class="text-sm font-medium text-red-700">Sales Order ini telah dibatalkan.</span>
</div>
@endif

{{-- Header --}}
<div class="mb-6 flex items-start justify-between">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h1 class="text-xl font-semibold text-gray-800">{{ $order->order_id }}</h1>
            @if($isLocked)
            <span class="flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                <i data-lucide="lock" class="w-3 h-3"></i> Terkunci
            </span>
            @endif
        </div>
        <p class="text-sm text-gray-500">
            Pelanggan: <strong class="text-gray-700">{{ $order->customer->name ?? '—' }}</strong>
            &middot;
            {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}
        </p>
    </div>
    <div class="flex gap-2 flex-wrap justify-end">
        @if($order->status === 'Draft' && !$isLocked)
        <form method="POST" action="{{ route('sales.confirm', $order->order_id) }}">
            @csrf
            <button type="submit" class="btn-primary">
                <i data-lucide="check" class="w-4 h-4"></i>
                Konfirmasi SO
            </button>
        </form>
        <a href="{{ route('sales.edit', $order->order_id) }}" class="btn-outline">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            Edit
        </a>
        <form method="POST" action="{{ route('sales.cancel', $order->order_id) }}" onsubmit="return confirm('Batalkan sales order ini?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Batalkan
            </button>
        </form>
        @endif
        <a href="{{ route('sales.index') }}" class="btn-outline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        {{-- Order Lines --}}
        <div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Item Pesanan</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">SKU</th>
                            <th class="table-th">Produk</th>
                            <th class="table-th text-center">Dipesan</th>
                            <th class="table-th text-center">Dikirim</th>
                            <th class="table-th text-center">Difaktur</th>
                            <th class="table-th text-right">Harga</th>
                            <th class="table-th text-right">Subtotal</th>
                            <th class="table-th text-right">PBJT</th>
                            <th class="table-th text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($order->lines as $line)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs text-gray-500">{{ $line->variant_sku }}</td>
                            <td class="table-td font-medium">{{ $line->product->name ?? $line->variant_sku }}</td>
                            <td class="table-td text-center">{{ $line->ordered_qty }}</td>
                            <td class="table-td text-center">{{ $line->delivered_qty ?? 0 }}</td>
                            <td class="table-td text-center">{{ $line->invoiced_qty ?? 0 }}</td>
                            <td class="table-td text-right">Rp {{ number_format($line->unit_price ?? 0, 0, ',', '.') }}</td>
                            <td class="table-td text-right">Rp {{ number_format(($line->unit_price ?? 0) * ($line->ordered_qty ?? 0), 0, ',', '.') }}</td>
                            <td class="table-td text-right text-gray-500">Rp {{ number_format($line->pbjt_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="table-td text-right font-medium">Rp {{ number_format($line->total_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="table-td text-center text-gray-400 py-6">Tidak ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Delivery Orders --}}
        <div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Pengiriman Terkait</h2>
            </div>
            @if($order->deliveryOrders && $order->deliveryOrders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Delivery ID</th>
                            <th class="table-th">Tanggal</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($order->deliveryOrders as $do)
                        <tr>
                            <td class="table-td">
                                <a href="{{ route('inventory.delivery.show', $do->delivery_id) }}" class="text-primary font-medium hover:underline font-mono text-xs">
                                    {{ $do->delivery_id }}
                                </a>
                            </td>
                            <td class="table-td text-gray-600">{{ $do->delivery_date ? \Carbon\Carbon::parse($do->delivery_date)->format('d M Y') : '—' }}</td>
                            <td class="table-td">
                                <span class="status-badge {{ $do->status === 'Done' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700' }}">{{ $do->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-5 py-6 text-center text-sm text-gray-400">Belum ada delivery order.</div>
            @endif
        </div>

        {{-- Invoices --}}
        <div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Faktur Terkait</h2>
            </div>
            @if($order->invoices && $order->invoices->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Invoice ID</th>
                            <th class="table-th">Tanggal</th>
                            <th class="table-th text-right">Total</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($order->invoices as $inv)
                        <tr>
                            <td class="table-td">
                                <a href="{{ route('invoices.show', $inv->invoice_id) }}" class="text-primary font-medium hover:underline font-mono text-xs">
                                    {{ $inv->invoice_id }}
                                </a>
                            </td>
                            <td class="table-td text-gray-600">{{ $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') : '—' }}</td>
                            <td class="table-td text-right font-medium">Rp {{ number_format($inv->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="table-td">
                                <span class="status-badge {{ $inv->status === 'Posted' ? 'bg-blue-50 text-blue-700' : ($inv->status === 'Paid' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500') }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-5 py-6 text-center text-sm text-gray-400">Belum ada faktur.</div>
            @endif
        </div>

    </div>

    {{-- Summary Sidebar --}}
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Ringkasan Order</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500">Status</span>
                    <span class="status-badge
                        {{ $order->status === 'Confirmed' ? 'bg-green-50 text-green-700' : ($order->status === 'Done' ? 'bg-blue-50 text-blue-700' : ($order->status === 'Cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600')) }}">
                        {{ $order->status }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500">Pelanggan</span>
                    <span class="text-sm font-medium text-gray-700">{{ $order->customer->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500">Tanggal Order</span>
                    <span class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span>
                </div>
                @if($order->payment_terms)
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500">Payment Terms</span>
                    <span class="text-sm text-gray-700">{{ $order->payment_terms }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 pt-3 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-xs text-gray-500">Subtotal</span>
                        <span class="text-sm font-medium">Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if(($order->discount_percent ?? 0) > 0)
                    <div class="flex justify-between text-red-600">
                        <span class="text-xs">Diskon ({{ $order->discount_percent }}%)</span>
                        <span class="text-sm font-medium">- Rp {{ number_format($order->discount_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-xs text-gray-500">PBJT (10%)</span>
                        <span class="text-sm font-medium">Rp {{ number_format($order->pbjt_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="text-sm font-semibold text-gray-700">Grand Total</span>
                        <span class="text-base font-bold text-primary">Rp {{ number_format($order->gross_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gap 7: Approval info --}}
        @if($order->approval_id)
        <div class="bg-orange-50 rounded-xl border border-orange-200 p-5">
            <h3 class="text-xs font-semibold text-orange-700 mb-2 flex items-center gap-1">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Persetujuan Diskon
            </h3>
            @php $approver = \App\Models\User::find($order->approval_id); @endphp
            <p class="text-sm text-orange-800">
                Disetujui oleh: <strong>{{ $approver?->name ?? 'ID: '.$order->approval_id }}</strong>
                ({{ ucfirst($approver?->role ?? '—') }})
            </p>
            <p class="text-xs text-orange-600 mt-1">Diskon {{ $order->discount_percent }}% diterapkan</p>
        </div>
        @endif

        {{-- Gap 6: Credit Exposure Breakdown --}}
        @if($order->customer && ($order->customer->credit_limit ?? 0) > 0)
        <div class="bg-white rounded-xl border border-surface-border shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i data-lucide="credit-card" class="w-4 h-4 text-gray-400"></i>
                Eksposur Kredit Pelanggan
            </h3>
            @php
                $customer    = $order->customer;
                $limit       = (float)$customer->credit_limit;
                $exposure    = (float)$customer->credit_exposure;
                $available   = max(0, $limit - $exposure);
                $pct         = $limit > 0 ? min(100, round(($exposure / $limit) * 100)) : 0;
                $barColor    = $pct >= 90 ? 'bg-red-500' : ($pct >= 60 ? 'bg-orange-400' : 'bg-green-500');
            @endphp
            <div class="space-y-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Terpakai</span><span>{{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mb-3">
                    <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <div class="flex justify-between text-xs py-1 border-b border-gray-50">
                    <span class="text-gray-500">Credit Limit</span>
                    <span class="font-medium">Rp {{ number_format($limit, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs py-1 border-b border-gray-50">
                    <span class="text-gray-500">Eksposur Saat Ini</span>
                    <span class="font-medium text-orange-600">Rp {{ number_format($exposure, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs py-1">
                    <span class="text-gray-500">Sisa Tersedia</span>
                    <span class="font-bold {{ $available <= 0 ? 'text-red-600' : 'text-green-700' }}">
                        Rp {{ number_format($available, 0, ',', '.') }}
                    </span>
                </div>
            </div>
            <a href="{{ route('contacts.show', $customer->contact_id) }}" class="mt-3 flex items-center gap-1 text-xs text-primary hover:underline">
                <i data-lucide="external-link" class="w-3 h-3"></i> Lihat detail kontak
            </a>
        </div>
        @endif

    </div>
</div>

{{-- Gap 5: Audit History --}}
@php
    $auditLogs = \App\Models\AuditLog::where('document_type', 'SalesOrder')
        ->where('document_id', $order->order_id)
        ->orderByDesc('created_at')
        ->get();
@endphp
@if($auditLogs->count() > 0)
<div class="mt-6 bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="history" class="w-4 h-4 text-gray-400"></i>
        <h2 class="text-sm font-semibold text-gray-700">Riwayat Audit</h2>
        <span class="ml-auto text-xs text-gray-400">{{ $auditLogs->count() }} entri</span>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($auditLogs as $log)
        <div class="px-5 py-3 flex items-start gap-4">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                <i data-lucide="{{ match($log->action) {
                    'CREATE'    => 'plus-circle',
                    'UPDATE'    => 'edit-2',
                    'CONFIRM'   => 'check-circle',
                    'CANCEL'    => 'x-circle',
                    default     => 'activity'
                } }}" class="w-3.5 h-3.5 text-gray-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-semibold text-gray-800">{{ $log->action }}</span>
                    <span class="text-xs text-gray-400">oleh <strong class="text-gray-600">{{ $log->actor_name }}</strong> ({{ $log->actor_role }})</span>
                </div>
                @if($log->message)
                    <p class="text-xs text-gray-500">{{ $log->message }}</p>
                @endif
                @if($log->before_payload || $log->after_payload)
                <details class="mt-1">
                    <summary class="text-xs text-primary cursor-pointer hover:underline">Lihat perubahan</summary>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @if($log->before_payload)
                        <div class="bg-red-50 rounded p-2 text-xs font-mono text-red-700 overflow-auto max-h-24">
                            <p class="font-semibold mb-1 text-red-500">Sebelum:</p>
                            {{ json_encode($log->before_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                        </div>
                        @endif
                        @if($log->after_payload)
                        <div class="bg-green-50 rounded p-2 text-xs font-mono text-green-700 overflow-auto max-h-24">
                            <p class="font-semibold mb-1 text-green-600">Sesudah:</p>
                            {{ json_encode($log->after_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                        </div>
                        @endif
                    </div>
                </details>
                @endif
            </div>
            <span class="text-xs text-gray-400 flex-shrink-0">{{ $log->created_at->format('d M H:i') }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
