@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.orders.index') }}">Sales Orders</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.orders.show', $order) }}">{{ $order->order_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Edit</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Edit Sales Order — {{ $order->order_id }}</div>
        <div class="page-desc">Update order details.</div>
    </div>
    <a href="{{ route('sales.orders.show', $order) }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
</div>

@if($order->is_locked ?? false)
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    <div>
        <div style="font-size:12.5px;font-weight:700;color:#991B1B">This order is locked</div>
        <div style="font-size:12px;color:#DC2626;margin-top:2px">Confirmed or processed orders have limited edit capability.</div>
    </div>
</div>
@endif

@if($errors->any())
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;font-size:12.5px;color:#991B1B;margin-bottom:6px">Please fix the following errors:</div>
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $error)
        <li style="font-size:12.5px;color:#DC2626">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('sales.orders.update', $order) }}" id="orderForm">
    @csrf
    @method('PATCH')

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Left --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card">
                <div class="card-header"><span class="card-title">Order Header</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Customer <span style="color:#DC2626">*</span></label>
                            <select name="customer_id" class="form-input" style="appearance:none" required>
                                <option value="">— Select customer —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}{{ $customer->city ? ' – '.$customer->city : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Order Date</label>
                            <input type="date" name="order_date" class="form-input" value="{{ old('order_date', $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-input" value="{{ old('payment_terms', $order->payment_terms) }}" placeholder="e.g. Net 14, Tunai">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" rows="2">{{ old('notes', $order->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" id="lineItems">
                <div class="card-header">
                    <span class="card-title">Order Lines</span>
                    <button type="button" class="btn btn-outline btn-sm" id="addLine">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Line
                    </button>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:30px">#</th>
                                <th style="min-width:180px">Product</th>
                                <th style="width:70px;text-align:right">Qty</th>
                                <th style="width:120px;text-align:right">Unit Price</th>
                                <th style="width:70px;text-align:right">Disc%</th>
                                <th style="width:110px;text-align:right">Subtotal</th>
                                <th style="width:100px;text-align:right">PBJT</th>
                                <th style="width:110px;text-align:right">Total</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            @forelse($order->lines as $idx => $line)
                            @php
                                $sub   = ($line->quantity??0)*($line->unit_price??0)*(1-(($line->discount_percent??0)/100));
                                $pbjt  = $sub*0.10;
                                $total = $sub+$pbjt;
                            @endphp
                            <tr data-line="{{ $idx }}">
                                <td style="color:#B8B2A6;font-size:12px">{{ $idx+1 }}</td>
                                <td>
                                    <select name="lines[{{ $idx }}][product_id]" class="form-input product-select" style="appearance:none;font-size:12.5px;padding:5px 8px">
                                        <option value="">— Select product —</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->sales_price ?? 0 }}" {{ $line->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="lines[{{ $idx }}][id]" value="{{ $line->id }}">
                                </td>
                                <td><input type="number" name="lines[{{ $idx }}][quantity]" class="form-input qty-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="{{ $line->quantity ?? 1 }}" min="1"></td>
                                <td><input type="number" name="lines[{{ $idx }}][unit_price]" class="form-input price-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="{{ $line->unit_price ?? 0 }}" min="0"></td>
                                <td><input type="number" name="lines[{{ $idx }}][discount_percent]" class="form-input disc-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="{{ $line->discount_percent ?? 0 }}" min="0" max="100"></td>
                                <td style="text-align:right;font-size:12.5px" class="line-subtotal">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                <td style="text-align:right;font-size:12.5px;color:#6B6560" class="line-pbjt">Rp {{ number_format($pbjt, 0, ',', '.') }}</td>
                                <td style="text-align:right;font-size:13px;font-weight:700" class="line-total">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-xs remove-line">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr data-line="0">
                                <td style="color:#B8B2A6;font-size:12px">1</td>
                                <td>
                                    <select name="lines[0][product_id]" class="form-input product-select" style="appearance:none;font-size:12.5px;padding:5px 8px">
                                        <option value="">— Select product —</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->sales_price ?? 0 }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="lines[0][quantity]" class="form-input qty-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="1" min="1"></td>
                                <td><input type="number" name="lines[0][unit_price]" class="form-input price-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0"></td>
                                <td><input type="number" name="lines[0][discount_percent]" class="form-input disc-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0" max="100"></td>
                                <td style="text-align:right;font-size:12.5px" class="line-subtotal">Rp 0</td>
                                <td style="text-align:right;font-size:12.5px;color:#6B6560" class="line-pbjt">Rp 0</td>
                                <td style="text-align:right;font-size:13px;font-weight:700" class="line-total">Rp 0</td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-xs remove-line">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            <div class="card">
                <div class="card-header"><span class="card-title">Discount</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Discount %</label>
                        <input type="number" name="discount_percent" id="discountInput" class="form-input" value="{{ old('discount_percent', $order->discount_percent ?? 0) }}" min="0" max="15" step="0.5">
                        <div class="form-hint">Maximum discount: 15%</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">Order Summary</span></div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;justify-content:space-between">
                            <span style="font-size:13px;color:#6B6560">Subtotal</span>
                            <span style="font-size:13px;font-weight:600" id="summarySubtotal">Rp 0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between">
                            <span style="font-size:13px;color:#D97706">Discount</span>
                            <span style="font-size:13px;font-weight:600;color:#D97706" id="summaryDiscount">- Rp 0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between">
                            <span style="font-size:13px;color:#6B6560">PBJT (10%)</span>
                            <span style="font-size:13px;font-weight:600" id="summaryPbjt">Rp 0</span>
                        </div>
                        <div style="border-top:2px solid #E8E4DA;padding-top:10px;display:flex;justify-content:space-between">
                            <span style="font-size:14px;font-weight:700;color:#343130">Grand Total</span>
                            <span style="font-size:1.2rem;font-weight:700;color:#C6A76B" id="summaryTotal">Rp 0</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:16px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function() {
    const PBJT_RATE = 0.10;
    let lineCount = {{ $order->lines->count() > 0 ? $order->lines->count() : 1 }};
    function fmt(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); }
    function calcLine(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value)||0;
        const price = parseFloat(row.querySelector('.price-input').value)||0;
        const disc = parseFloat(row.querySelector('.disc-input').value)||0;
        const sub = qty * price * (1 - disc/100);
        const pbjt = sub * PBJT_RATE;
        const total = sub + pbjt;
        row.querySelector('.line-subtotal').textContent = fmt(sub);
        row.querySelector('.line-pbjt').textContent = fmt(pbjt);
        row.querySelector('.line-total').textContent = fmt(total);
        return sub;
    }
    function recalcAll() {
        let subtotal = 0;
        document.querySelectorAll('#linesBody tr').forEach(r => { subtotal += calcLine(r); });
        const discPct = parseFloat(document.getElementById('discountInput').value)||0;
        const disc = subtotal * discPct / 100;
        const after = subtotal - disc;
        const pbjt = after * PBJT_RATE;
        const grand = after + pbjt;
        document.getElementById('summarySubtotal').textContent = fmt(subtotal);
        document.getElementById('summaryDiscount').textContent = '- ' + fmt(disc);
        document.getElementById('summaryPbjt').textContent = fmt(pbjt);
        document.getElementById('summaryTotal').textContent = fmt(grand);
    }
    function bindRow(row) {
        const sel = row.querySelector('.product-select');
        if(sel) sel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            row.querySelector('.price-input').value = parseFloat(opt.dataset.price)||0;
            recalcAll();
        });
        row.querySelectorAll('.qty-input,.price-input,.disc-input').forEach(i => i.addEventListener('input', recalcAll));
        const btn = row.querySelector('.remove-line');
        if(btn) btn.addEventListener('click', function() {
            if(document.querySelectorAll('#linesBody tr').length > 1) {
                row.remove();
                document.querySelectorAll('#linesBody tr').forEach((r,i) => r.children[0].textContent = i+1);
                recalcAll();
            }
        });
    }
    document.querySelectorAll('#linesBody tr').forEach(bindRow);
    document.getElementById('discountInput').addEventListener('input', recalcAll);
    document.getElementById('addLine').addEventListener('click', function() {
        const idx = lineCount++;
        const tbody = document.getElementById('linesBody');
        const tr = document.createElement('tr');
        tr.setAttribute('data-line', idx);
        const opts = Array.from(document.querySelector('.product-select').options).slice(1)
            .map(o => `<option value="${o.value}" data-price="${o.dataset.price}">${o.text}</option>`).join('');
        tr.innerHTML = `<td style="color:#B8B2A6;font-size:12px">${tbody.children.length+1}</td>
            <td><select name="lines[${idx}][product_id]" class="form-input product-select" style="appearance:none;font-size:12.5px;padding:5px 8px"><option value="">— Select product —</option>${opts}</select></td>
            <td><input type="number" name="lines[${idx}][quantity]" class="form-input qty-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="1" min="1"></td>
            <td><input type="number" name="lines[${idx}][unit_price]" class="form-input price-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0"></td>
            <td><input type="number" name="lines[${idx}][discount_percent]" class="form-input disc-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0" max="100"></td>
            <td style="text-align:right;font-size:12.5px" class="line-subtotal">Rp 0</td>
            <td style="text-align:right;font-size:12.5px;color:#6B6560" class="line-pbjt">Rp 0</td>
            <td style="text-align:right;font-size:13px;font-weight:700" class="line-total">Rp 0</td>
            <td><button type="button" class="btn btn-danger btn-xs remove-line"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></td>`;
        tbody.appendChild(tr);
        bindRow(tr);
        recalcAll();
    });
    recalcAll();
})();
</script>
@endpush
