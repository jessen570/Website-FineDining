@extends('layouts.app')

@section('title', 'Penjualan — Maison Aster ERP')
@section('breadcrumb')
    <span class="text-gray-600">Penjualan</span>
@endsection

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Sales Order</h1>
        <p class="text-sm text-gray-500 mt-0.5">Daftar semua pesanan penjualan</p>
    </div>
    <a href="{{ route('sales.create') }}" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Buat SO
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl border border-surface-border shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Order ID atau nama pelanggan..."
                    class="w-full pl-9 pr-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
        </div>
        <div class="min-w-40">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Semua Status</option>
                <option value="Draft"     {{ request('status') === 'Draft'     ? 'selected' : '' }}>Draft</option>
                <option value="Confirmed" {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="Done"      {{ request('status') === 'Done'      ? 'selected' : '' }}>Done</option>
                <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">
                <i data-lucide="filter" class="w-4 h-4"></i>
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('sales.index') }}" class="btn-outline">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-surface-border">
                <tr>
                    <th class="table-th">Order ID</th>
                    <th class="table-th">Pelanggan</th>
                    <th class="table-th">Tanggal</th>
                    <th class="table-th text-right">Subtotal</th>
                    <th class="table-th text-right">PBJT</th>
                    <th class="table-th text-right">Total</th>
                    <th class="table-th">Status</th>
                    <th class="table-th">Pengiriman</th>
                    <th class="table-th">Faktur</th>
                    <th class="table-th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                @php
                    $sc = match($order->status) {
                        'Confirmed' => 'bg-green-50 text-green-700',
                        'Draft'     => 'bg-gray-100 text-gray-600',
                        'Done'      => 'bg-blue-50 text-blue-700',
                        'Cancelled' => 'bg-red-50 text-red-600',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                    $dc = match($order->delivery_status ?? '') {
                        'Done'    => 'bg-blue-50 text-blue-700',
                        'Partial' => 'bg-orange-50 text-orange-700',
                        default   => 'bg-gray-100 text-gray-500',
                    };
                    $ic = match($order->invoice_status ?? '') {
                        'Fully Invoiced'    => 'bg-green-50 text-green-700',
                        'Partially Invoiced'=> 'bg-orange-50 text-orange-700',
                        default             => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="table-td">
                        <a href="{{ route('sales.show', $order->order_id) }}" class="font-medium text-primary hover:underline font-mono text-xs">
                            {{ $order->order_id }}
                        </a>
                    </td>
                    <td class="table-td text-gray-700">{{ $order->customer->name ?? '—' }}</td>
                    <td class="table-td text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</td>
                    <td class="table-td text-right">Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td text-right text-gray-500">Rp {{ number_format($order->pbjt_amount ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td text-right font-semibold">Rp {{ number_format($order->gross_amount ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td"><span class="status-badge {{ $sc }}">{{ $order->status }}</span></td>
                    <td class="table-td"><span class="status-badge {{ $dc }}">{{ $order->delivery_status ?? 'Pending' }}</span></td>
                    <td class="table-td"><span class="status-badge {{ $ic }}">{{ $order->invoice_status ?? 'Not Invoiced' }}</span></td>
                    <td class="table-td">
                        <div class="flex gap-2 justify-end">
                            <a href="{{ route('sales.show', $order->order_id) }}" class="text-gray-400 hover:text-primary" title="Lihat">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="table-td text-center py-12">
                        <div class="flex flex-col items-center gap-2 text-gray-400">
                            <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                            <p class="text-sm">Belum ada sales order.</p>
                            <a href="{{ route('sales.create') }}" class="text-primary text-sm hover:underline">Buat SO pertama</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="px-5 py-4 border-t border-surface-border">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
