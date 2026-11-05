@extends('layouts.app')

@section('title', 'Buat Sales Order — Maison Aster ERP')
@section('breadcrumb')
    <a href="{{ route('sales.index') }}" class="text-gray-500 hover:text-gray-700">Penjualan</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <span class="text-gray-600">Buat SO</span>
@endsection

@section('content')

<div class="mb-6">
    <h1 class="text-xl font-semibold text-gray-800">Buat Sales Order Baru</h1>
    <p class="text-sm text-gray-500 mt-0.5">Isi detail pesanan penjualan</p>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
    <div class="flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('sales.store') }}" id="so-form">
    @csrf

    {{-- Hidden CRM lead reference --}}
    @if(isset($crmLeadId))
        <input type="hidden" name="crm_lead_id" value="{{ $crmLeadId }}">
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            {{-- Header --}}
            <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Informasi Order</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pelanggan <span class="text-red-500">*</span></label>
                        <select name="customer_id" id="customer_id"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-400 @enderror">
                            <option value="">— Pilih Pelanggan —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}"
                                    {{ old('customer_id', $preCustomer ?? null) == $customer->contact_id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Order <span class="text-red-500">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('order_date') border-red-400 @enderror">
                        @error('order_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ old('payment_terms') }}"
                            placeholder="Contoh: Net 30"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Diskon Order (%)</label>
                        <input type="number" name="discount_percent" id="discount_percent"
                            value="{{ old('discount_percent', 0) }}"
                            min="0" max="15" step="0.5"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-400 mt-1">Maks. 15%. Diatas 10% memerlukan persetujuan.</p>
                    </div>

                    {{-- Discount approval (hidden, shown via JS if discount > 10) --}}
                    <div id="approval_wrap" class="sm:col-span-2 hidden">
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                            <div class="flex items-start gap-2 mb-3">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-orange-500 flex-shrink-0 mt-0.5"></i>
                                <p class="text-xs font-medium text-orange-700">Diskon &gt; 10% memerlukan persetujuan Manager / Owner / Finance</p>
                            </div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Pilih Approver <span class="text-red-500">*</span></label>
                            <select name="approval_id" id="approval_id"
                                class="w-full px-3 py-2 border border-orange-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white @error('approval_id') border-red-400 @enderror">
                                <option value="">— Pilih Approver —</option>
                                @foreach(\App\Models\User::whereIn('role',['owner','finance'])->orderBy('name')->get() as $approver)
                                    <option value="{{ $approver->id }}" {{ old('approval_id') == $approver->id ? 'selected' : '' }}>
                                        {{ $approver->name }} ({{ ucfirst($approver->role) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('approval_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                        <textarea name="notes" rows="2"
                            placeholder="Instruksi khusus, catatan pengiriman, dll..."
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('notes') }}</textarea>
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
                    <table class="w-full" id="lines-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Produk (SKU)</th>
                                <th class="table-th w-24">Qty</th>
                                <th class="table-th w-36">Harga Satuan (Rp)</th>
                                <th class="table-th text-right w-32">Subtotal</th>
                                <th class="table-th w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-body">
                            @if(old('lines'))
                                @foreach(old('lines') as $i => $line)
                                <tr class="line-row">
                                    <td class="table-td">
                                        <select name="lines[{{ $i }}][variant_sku]" class="line-sku w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                                            <option value="">— Pilih Produk —</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->variant_sku }}" data-price="{{ $product->sales_price }}"
                                                    {{ $line['variant_sku'] === $product->variant_sku ? 'selected' : '' }}>
                                                    {{ $product->variant_sku }} — {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="table-td">
                                        <input type="number" name="lines[{{ $i }}][qty]" value="{{ $line['qty'] }}" min="1" step="1"
                                            class="line-qty w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-center">
                                    </td>
                                    <td class="table-td">
                                        <input type="number" name="lines[{{ $i }}][price]" value="{{ $line['price'] }}" min="0" step="500"
                                            class="line-price w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-right">
                                    </td>
                                    <td class="table-td text-right font-medium line-subtotal">Rp 0</td>
                                    <td class="table-td">
                                        <button type="button" class="remove-line text-gray-400 hover:text-red-500 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            {{-- Default first row --}}
                            <tr class="line-row">
                                <td class="table-td">
                                    <select name="lines[0][variant_sku]" class="line-sku w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                                        <option value="">— Pilih Produk —</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->variant_sku }}" data-price="{{ $product->sales_price }}">
                                                {{ $product->variant_sku }} — {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="table-td">
                                    <input type="number" name="lines[0][qty]" value="1" min="1" step="1"
                                        class="line-qty w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-center">
                                </td>
                                <td class="table-td">
                                    <input type="number" name="lines[0][price]" value="0" min="0" step="500"
                                        class="line-price w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-right">
                                </td>
                                <td class="table-td text-right font-medium line-subtotal">Rp 0</td>
                                <td class="table-td">
                                    <button type="button" class="remove-line text-gray-400 hover:text-red-500 transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Summary Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6 sticky top-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Ringkasan</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Subtotal</span>
                        <span class="text-sm font-medium text-gray-800" id="summary-subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">PBJT (10%)</span>
                        <span class="text-sm font-medium text-gray-800" id="summary-pbjt">Rp 0</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">Grand Total</span>
                        <span class="text-base font-bold text-primary" id="summary-total">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Sales Order
                </button>
                <a href="{{ route('sales.index') }}" class="btn-outline w-full justify-center">
                    Batal
                </a>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
    let lineIndex = document.querySelectorAll('.line-row').length;
    const productsData = @json($products->keyBy('variant_sku'));

    function fmt(n) {
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function recalc() {
        let subtotal = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const qty   = parseFloat(row.querySelector('.line-qty').value) || 0;
            const price = parseFloat(row.querySelector('.line-price').value) || 0;
            const sub   = qty * price;
            row.querySelector('.line-subtotal').textContent = fmt(sub);
            subtotal += sub;
        });
        const disc    = parseFloat(document.getElementById('discount_percent').value) || 0;
        const discAmt = subtotal * disc / 100;
        const net     = subtotal - discAmt;
        const pbjt    = net * 0.10;
        const total   = net + pbjt;
        document.getElementById('summary-subtotal').textContent = fmt(net);
        document.getElementById('summary-pbjt').textContent     = fmt(pbjt);
        document.getElementById('summary-total').textContent    = fmt(total);
    }

    function buildRow(idx) {
        const optionHtml = Object.entries(productsData).map(([sku, p]) =>
            `<option value="${sku}" data-price="${p.sales_price}">${sku} — ${p.name}</option>`
        ).join('');

        return `<tr class="line-row">
            <td class="table-td">
                <select name="lines[${idx}][variant_sku]" class="line-sku w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">— Pilih Produk —</option>
                    ${optionHtml}
                </select>
            </td>
            <td class="table-td">
                <input type="number" name="lines[${idx}][qty]" value="1" min="1" step="1"
                    class="line-qty w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-center">
            </td>
            <td class="table-td">
                <input type="number" name="lines[${idx}][price]" value="0" min="0" step="500"
                    class="line-price w-full px-2 py-1.5 border border-surface-border rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary text-right">
            </td>
            <td class="table-td text-right font-medium line-subtotal">Rp 0</td>
            <td class="table-td">
                <button type="button" class="remove-line text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        </tr>`;
    }

    document.getElementById('add-line').addEventListener('click', () => {
        const tbody = document.getElementById('lines-body');
        tbody.insertAdjacentHTML('beforeend', buildRow(lineIndex++));
        lucide.createIcons();
        bindEvents();
        recalc();
    });

    function bindEvents() {
        document.querySelectorAll('.line-sku').forEach(sel => {
            sel.onchange = function() {
                const sku = this.value;
                const row = this.closest('.line-row');
                if (sku && productsData[sku]) {
                    row.querySelector('.line-price').value = productsData[sku].sales_price;
                }
                recalc();
            };
        });
        document.querySelectorAll('.line-qty, .line-price').forEach(inp => {
            inp.oninput = recalc;
        });
        document.querySelectorAll('.remove-line').forEach(btn => {
            btn.onclick = function() {
                this.closest('.line-row').remove();
                recalc();
            };
        });
    }

    document.getElementById('discount_percent').addEventListener('input', function () {
        const val = parseFloat(this.value) || 0;
        document.getElementById('approval_wrap').classList.toggle('hidden', val <= 10);
        recalc();
    });

    bindEvents();
    recalc();
</script>
@endpush
