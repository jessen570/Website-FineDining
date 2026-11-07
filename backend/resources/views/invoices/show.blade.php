@extends('layouts.app')

@section('title', $invoice->invoice_id . ' — ' . $invoice->type)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Control Panel --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#c8a951]/20 pb-4">
        <div>
            <nav class="flex items-center space-x-2 text-xs text-[#8a9aa8] mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-[#c8a951]">Maison Aster</a>
                <span>/</span>
                <a href="{{ route('invoices.index') }}" class="hover:text-[#c8a951]">Invoices & Bills</a>
                <span>/</span>
                <span class="text-[#c8a951] font-medium">{{ $invoice->invoice_id }}</span>
            </nav>
            <h1 class="text-2xl font-serif font-bold text-white flex items-center gap-3">
                <span>{{ $invoice->invoice_id }}</span>
                <span class="text-xs px-2.5 py-1 rounded-full uppercase tracking-wider font-sans font-semibold
                    @if($invoice->status === 'Paid') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                    @elseif($invoice->status === 'Posted') bg-blue-500/10 text-blue-400 border border-blue-500/20
                    @elseif($invoice->status === 'Draft') bg-amber-500/10 text-amber-400 border border-amber-500/20
                    @else bg-gray-500/10 text-gray-400 border border-gray-500/20 @endif">
                    {{ $invoice->status }}
                </span>
                <span class="text-xs px-2.5 py-1 rounded-full uppercase tracking-wider font-sans font-semibold bg-[#1a2234] text-[#c8a951] border border-[#c8a951]/30">
                    {{ $invoice->type }}
                </span>
            </h1>
        </div>

        {{-- Action Buttons & Status Pipeline --}}
        <div class="flex items-center gap-3 flex-wrap">
            @if($invoice->status === 'Draft')
                <form action="{{ route('invoices.post', $invoice->invoice_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-[#c8a951] hover:bg-[#d8b961] text-[#0d131f] font-semibold text-sm rounded shadow flex items-center gap-2">
                        <i class="lucide-check-circle w-4 h-4"></i> Post / Confirm
                    </button>
                </form>
            @endif

            @if($invoice->status === 'Posted' && (float)$invoice->amount_due > 0)
                <a href="{{ route('invoices.pay.form', $invoice->invoice_id) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded shadow flex items-center gap-2">
                    <i class="lucide-banknote w-4 h-4"></i> Register Payment (S-13)
                </a>
            @endif

            @if(in_array($invoice->status, ['Draft', 'Posted']) && (float)$invoice->amount_paid == 0)
                <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Batalkan invoice ini?');">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-rose-900/30 hover:bg-rose-900/50 text-rose-300 border border-rose-800/40 text-sm rounded flex items-center gap-1.5">
                        <i class="lucide-x-circle w-4 h-4"></i> Cancel
                    </button>
                </form>
            @endif

            <a href="{{ route('invoices.index') }}" class="px-3 py-2 bg-[#1a2234] hover:bg-[#242f45] text-[#8a9aa8] hover:text-white border border-[#2a374e] text-sm rounded">
                Kembali
            </a>
        </div>
    </div>

    {{-- Main Document Card (Wireframe S-09) --}}
    <div class="bg-[#121824] border border-[#2a374e] rounded-xl shadow-xl overflow-hidden">
        
        {{-- Smart Buttons Header Bar (F-22) --}}
        <div class="bg-[#0f141f] border-b border-[#2a374e] px-6 py-3 flex items-center justify-end gap-2 overflow-x-auto">
            @if($invoice->source_doc_type === 'Sales Order' && $invoice->source_doc_id)
                <a href="{{ route('sales.orders.show', $invoice->source_doc_id) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#1a2234] hover:bg-[#242f45] border border-[#2a374e] rounded text-xs text-[#8a9aa8] hover:text-[#c8a951] transition">
                    <i class="lucide-shopping-bag w-3.5 h-3.5 text-[#c8a951]"></i>
                    <span>SO: <strong class="text-white">{{ $invoice->source_doc_id }}</strong></span>
                </a>
            @endif

            @if($invoice->linked_delivery_id)
                <a href="{{ route('inventory.delivery.show', $invoice->linked_delivery_id) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#1a2234] hover:bg-[#242f45] border border-[#2a374e] rounded text-xs text-[#8a9aa8] hover:text-blue-400 transition">
                    <i class="lucide-truck w-3.5 h-3.5 text-blue-400"></i>
                    <span>DO: <strong class="text-white">{{ $invoice->linked_delivery_id }}</strong></span>
                </a>
            @endif

            @if($invoice->linked_receipt_id)
                <a href="{{ route('purchase.receipt.show', $invoice->linked_receipt_id) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#1a2234] hover:bg-[#242f45] border border-[#2a374e] rounded text-xs text-[#8a9aa8] hover:text-emerald-400 transition">
                    <i class="lucide-package-check w-3.5 h-3.5 text-emerald-400"></i>
                    <span>Receipt: <strong class="text-white">{{ $invoice->linked_receipt_id }}</strong></span>
                </a>
            @endif

            @if($jePosted)
                <a href="{{ route('accounting.entry.show', $jePosted->entry_id) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#1a2234] hover:bg-[#242f45] border border-[#2a374e] rounded text-xs text-[#8a9aa8] hover:text-indigo-400 transition">
                    <i class="lucide-book-open w-3.5 h-3.5 text-indigo-400"></i>
                    <span>Journal Items: <strong class="text-white">{{ $jePosted->entry_id }}</strong></span>
                </a>
            @endif
        </div>

        {{-- Header Info Section --}}
        <div class="p-6 md:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                <div>
                    <span class="block text-xs uppercase tracking-wider text-[#8a9aa8] font-semibold mb-1">
                        {{ $invoice->type === 'Customer Invoice' ? 'Customer / Pelanggan' : 'Vendor / Pemasok' }}
                    </span>
                    <div class="text-white font-medium text-base">
                        {{ $invoice->partner?->name ?? '—' }}
                    </div>
                    <div class="text-xs text-[#8a9aa8]">
                        ID: {{ $invoice->partner_id }} • {{ $invoice->partner?->email ?? '-' }}
                    </div>
                </div>

                <div>
                    <span class="block text-xs uppercase tracking-wider text-[#8a9aa8] font-semibold mb-1">Tanggal Faktur</span>
                    <div class="text-white font-medium">
                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}
                    </div>
                    <div class="text-xs text-[#8a9aa8]">
                        Jatuh Tempo: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') : 'Saat Penyerahan' }}
                    </div>
                </div>

                <div>
                    <span class="block text-xs uppercase tracking-wider text-[#8a9aa8] font-semibold mb-1">Dokumen Rujukan</span>
                    <div class="text-white font-medium">
                        {{ $invoice->source_doc_type ?? 'Manual' }}: {{ $invoice->source_doc_id ?? '—' }}
                    </div>
                    @if($invoice->linked_delivery_id)
                        <div class="text-xs text-blue-400">
                            Penyerahan DO: {{ $invoice->linked_delivery_id }}
                        </div>
                    @endif
                </div>

                <div>
                    <span class="block text-xs uppercase tracking-wider text-[#8a9aa8] font-semibold mb-1">Status Pembayaran</span>
                    <div class="text-white font-medium flex items-center gap-2">
                        @if((float)$invoice->amount_due <= 0)
                            <span class="text-emerald-400 font-bold">LUNAS (PAID)</span>
                        @else
                            <span class="text-amber-400 font-bold">Belum Lunas</span>
                            <span class="text-xs text-[#8a9aa8]">(Sisa Rp {{ number_format($invoice->amount_due, 0, ',', '.') }})</span>
                        @endif
                    </div>
                    <div class="text-xs text-[#8a9aa8]">
                        Terbayar: Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            {{-- Line Items Table --}}
            <div class="mt-8">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-[#c8a951] mb-3">Rincian Baris Tagihan (Lines)</h3>
                <div class="overflow-x-auto border border-[#2a374e] rounded-lg">
                    <table class="w-full text-left text-sm text-[#e5e9f0]">
                        <thead class="bg-[#1a2234] text-xs uppercase text-[#8a9aa8] border-b border-[#2a374e]">
                            <tr>
                                <th class="px-4 py-3">Produk / Menu</th>
                                <th class="px-4 py-3 text-right">Qty Ditagihkan</th>
                                <th class="px-4 py-3 text-right">Harga Satuan</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-right">Pajak (PBJT 10%)</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2a374e]">
                            @forelse($invoice->lines as $line)
                                <tr class="hover:bg-[#1a2234]/50">
                                    <td class="px-4 py-3.5">
                                        <div class="font-medium text-white">{{ $line->product?->name ?? $line->variant_sku }}</div>
                                        <div class="text-xs text-[#8a9aa8] font-mono">{{ $line->variant_sku }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium">{{ number_format($line->invoiced_qty, 0) }}</td>
                                    <td class="px-4 py-3.5 text-right font-mono">Rp {{ number_format($line->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3.5 text-right font-mono">Rp {{ number_format($line->subtotal, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3.5 text-right font-mono text-[#c8a951]">Rp {{ number_format($line->pbjt_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3.5 text-right font-mono font-semibold text-white">Rp {{ number_format($line->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-[#8a9aa8]">Tidak ada baris item pada invoice ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Summary Subtotals & Tax Breakdown --}}
            <div class="flex flex-col md:flex-row justify-between gap-6 pt-4 border-t border-[#2a374e]">
                <div class="w-full md:w-1/2 space-y-3">
                    <div class="text-xs text-[#8a9aa8]">
                        <strong class="text-white">Catatan / Aturan Skenario:</strong>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li><strong>SR-02 Enforced:</strong> Penagihan berbasis penyerahan barang fisik yang telah diterima (*Delivered Quantity = Done*).</li>
                            <li><strong>Pajak Restoran (PBJT):</strong> Diterapkan tarif 10% untuk transaksi makanan dan minuman.</li>
                            <li><strong>Aturan Pembayaran (SR-16):</strong> Pembayaran sebagian diperbolehkan; pembayaran melebihi sisa tagihan ditolak sistem.</li>
                        </ul>
                    </div>
                </div>

                <div class="w-full md:w-1/3 bg-[#0f141f] p-5 rounded-lg border border-[#2a374e] space-y-2 text-sm">
                    <div class="flex justify-between text-[#8a9aa8]">
                        <span>Subtotal Bersih:</span>
                        <span class="font-mono text-white">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-[#8a9aa8]">
                        <span>PBJT Restoran (10%):</span>
                        <span class="font-mono text-[#c8a951]">Rp {{ number_format($invoice->pbjt_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-white pt-2 border-t border-[#2a374e]">
                        <span>Total Tagihan:</span>
                        <span class="font-mono text-emerald-400">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-[#8a9aa8] pt-1">
                        <span>Sudah Dibayar:</span>
                        <span class="font-mono text-white">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold text-amber-400 pt-1">
                        <span>Sisa Kewajiban (Due):</span>
                        <span class="font-mono">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Payments History Tab --}}
            @if($invoice->payments->count() > 0)
                <div class="mt-8 pt-6 border-t border-[#2a374e]">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                        <i class="lucide-credit-card w-4 h-4"></i> Riwayat Pembayaran (Payments Registered)
                    </h3>
                    <div class="overflow-x-auto border border-[#2a374e] rounded-lg">
                        <table class="w-full text-left text-xs text-[#e5e9f0]">
                            <thead class="bg-[#1a2234] text-[#8a9aa8] border-b border-[#2a374e]">
                                <tr>
                                    <th class="px-4 py-2.5">Payment ID</th>
                                    <th class="px-4 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Metode</th>
                                    <th class="px-4 py-2.5 text-right">Jumlah Dibayar</th>
                                    <th class="px-4 py-2.5 text-right">Sisa Tagihan</th>
                                    <th class="px-4 py-2.5">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#2a374e]">
                                @foreach($invoice->payments as $p)
                                    <tr class="hover:bg-[#1a2234]/40">
                                        <td class="px-4 py-2 font-mono text-[#c8a951]">{{ $p->payment_id }}</td>
                                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($p->payment_date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-4 py-2">{{ $p->payment_method }}</td>
                                        <td class="px-4 py-2 text-right font-mono font-bold text-emerald-400">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-[#8a9aa8]">Rp {{ number_format($p->remaining_due, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-[#8a9aa8]">{{ $p->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
