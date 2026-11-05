@extends('layouts.app')

@section('title', 'Edit SO — ' . $order->order_id)
@section('breadcrumb')
    <a href="{{ route('sales.index') }}" class="text-gray-500 hover:text-gray-700">Penjualan</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <a href="{{ route('sales.show', $order->order_id) }}" class="text-gray-500 hover:text-gray-700">{{ $order->order_id }}</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <span class="text-gray-600">Edit</span>
@endsection

@section('content')

<div class="mb-6">
    <h1 class="text-xl font-semibold text-gray-800">Edit Sales Order: {{ $order->order_id }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">Perbarui detail pesanan penjualan</p>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
    <div class="flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('sales.update', $order->order_id) }}" id="so-edit-form">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Informasi Order</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pelanggan <span class="text-red-500">*</span></label>
                        <select name="customer_id"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">— Pilih Pelanggan —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}" {{ old('customer_id', $order->customer_id) == $customer->contact_id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Order <span class="text-red-500">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', $order->order_date) }}"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ old('payment_terms', $order->payment_terms) }}"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Diskon Order (%)</label>
                        <input type="number" name="discount_percent" id="discount_percent"
                            value="{{ old('discount_percent', $order->discount_percent ?? 0) }}"
                            min="0" max="15" step="0.5"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div id="approval_wrap" class="sm:col-span-2 {{ ($order->discount_percent ?? 0) > 10 ? '' : 'hidden' }}">
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 flex items-start gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-orange-500 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-orange-700 mb-2">Diskon > 10% memerlukan kode persetujuan</p>
                                <input type="text" name="discount_approval_code" value="{{ old('discount_approval_code') }}"
                                    placeholder="Masukkan kode persetujuan manajer..."
                                    class="w-full px-3 py-2 border border-orange-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white rounded-xl border border-surface-border shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Item Pesanan</h2>
                    <button type="button" id="add-line" class="btn-outline text-xs">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Produk (SKU)</th>
                                <th class="table-th w-24">Qty</th>
                                <th class="table-th w-36">Harga (Rp)</th>
                                <th class="table-th text-right w-32">Subtotal</th>
                                <th class="table-th w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-body">
                            @foreach($order->lines as $i => $line)
                            <tr class="line-row">
                                <td class="table-td">
                                    <select name="lines[{{ $i }}][variant_sku]" class="line-sku w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                                        <option value="">— Pilih Produk —</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->variant_sku }}" data-price="{{ $product->sales_price }}"
                                                {{ $line->variant_sku === $product->variant_sku ? 'selected' : '' }}>
                                                {{ $product->variant_sku }} — {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $line->line_id ?? '' }}">
                                </td>
                                <td class="table-td">
                                    <input type="number" name="lines[{{ $i }}][qty]" value="{{ $line->ordered_qty }}" min="1"
                                        class="line-qty w-full px-2 py-1.5 border border-surface-border rounded text-sm text-center focus:outline-none focus:ring-1 focus:ring-primary">
                                </td>
                                <td class="table-td">
                                    <input type="number" name="lines[{{ $i }}][price]" value="{{ $line->unit_price }}" min="0" step="500"
                                        class="line-price w-full px-2 py-1.5 border border-surface-border rounded text-sm text-right focus:outline-none focus:ring-1 focus:ring-primary">
                                </td>
                                <td class="table-td text-right font-medium line-subtotal">
                                    Rp {{ number_format(($line->unit_price ?? 0) * ($line->ordered_qty ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="table-td">
                                    <button type="button" class="remove-line text-gray-400 hover:text-red-500">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6 sticky top-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Ringkasan</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Subtotal</span>
                        <span class="text-sm font-medium" id="summary-subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">PBJT (10%)</span>
                        <span class="text-sm font-medium" id="summary-pbjt">Rp 0</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <span class="text-sm font-semibold">Grand Total</span>
                        <span class="text-base font-bold text-primary" id="summary-total">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Perubahan
                </button>
                <a href="{{ route('sales.show', $order->order_id) }}" class="btn-outline w-full justify-center">Batal</a>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
    let lineIndex = document.querySelectorAll('.line-row').length;
    const productsData = @json($products->keyBy('variant_sku'));
    function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
    function recalc() {
        let subtotal = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            const price = parseFloat(row.querySelector('.line-price').value) || 0;
            const sub = qty * price;
            row.querySelector('.line-subtotal').textContent = fmt(sub);
            subtotal += sub;
        });
        const disc = parseFloat(document.getElementById('discount_percent').value) || 0;
        const net = subtotal * (1 - disc / 100);
        const pbjt = net * 0.10;
        document.getElementById('summary-subtotal').textContent = fmt(net);
        document.getElementById('summary-pbjt').textContent = fmt(pbjt);
        document.getElementById('summary-total').textContent = fmt(net + pbjt);
    }
    function buildRow(idx) {
        const opts = Object.entries(productsData).map(([sku, p]) =>
            `<option value="${sku}" data-price="${p.sales_price}">${sku} — ${p.name}</option>`).join('');
        return `<tr class="line-row"><td class="table-td"><select name="lines[${idx}][variant_sku]" class="line-sku w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary"><option value="">— Pilih Produk —</option>${opts}</select></td><td class="table-td"><input type="number" name="lines[${idx}][qty]" value="1" min="1" class="line-qty w-full px-2 py-1.5 border border-surface-border rounded text-sm text-center focus:outline-none"></td><td class="table-td"><input type="number" name="lines[${idx}][price]" value="0" min="0" step="500" class="line-price w-full px-2 py-1.5 border border-surface-border rounded text-sm text-right focus:outline-none"></td><td class="table-td text-right font-medium line-subtotal">Rp 0</td><td class="table-td"><button type="button" class="remove-line text-gray-400 hover:text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td></tr>`;
    }
    document.getElementById('add-line').addEventListener('click', () => {
        document.getElementById('lines-body').insertAdjacentHTML('beforeend', buildRow(lineIndex++));
        lucide.createIcons(); bindEvents(); recalc();
    });
    function bindEvents() {
        document.querySelectorAll('.line-sku').forEach(sel => {
            sel.onchange = function() {
                const row = this.closest('.line-row');
                if (this.value && productsData[this.value]) row.querySelector('.line-price').value = productsData[this.value].sales_price;
                recalc();
            };
        });
        document.querySelectorAll('.line-qty, .line-price').forEach(i => { i.oninput = recalc; });
        document.querySelectorAll('.remove-line').forEach(b => { b.onclick = function() { this.closest('.line-row').remove(); recalc(); }; });
    }
    document.getElementById('discount_percent').addEventListener('input', function() {
        document.getElementById('approval_wrap').classList.toggle('hidden', parseFloat(this.value) <= 10);
        recalc();
    });
    bindEvents(); recalc();
</script>
@endpush
