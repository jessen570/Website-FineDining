@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.quotations.index') }}">Quotations</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">New Quotation</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">New Quotation</div>
        <div class="page-desc">Create a price proposal for a customer.</div>
    </div>
    <a href="{{ route('sales.quotations.index') }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
</div>

@if(isset($fromLead) && $fromLead)
<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div style="font-size:12.5px;color:#065F46">Creating quotation from Lead <strong>{{ $fromLead->lead_id }}</strong>: <strong>{{ $fromLead->name }}</strong></div>
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

<form method="POST" action="{{ route('sales.quotations.store') }}" id="quotationForm">
    @csrf
    @if(isset($fromLead) && $fromLead)
    <input type="hidden" name="crm_lead_id" value="{{ $fromLead->id }}">
    @endif

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Left --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            {{-- Header Card --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Quotation Header</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Customer <span style="color:#DC2626">*</span></label>
                            <select name="customer_id" class="form-input" style="appearance:none" required>
                                <option value="">— Select customer —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', isset($preCustomer) ? $preCustomer->id : '') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}{{ $customer->city ? ' – '.$customer->city : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quotation Date</label>
                            <input type="date" name="quotation_date" class="form-input" value="{{ old('quotation_date', date('Y-m-d')) }}">
                            @error('quotation_date')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="expiration_date" class="form-input" value="{{ old('expiration_date') }}">
                            @error('expiration_date')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">CRM Lead Reference</label>
                            <select name="crm_lead_id" class="form-input" style="appearance:none">
                                <option value="">— No lead reference —</option>
                                @foreach($leads as $lead)
                                <option value="{{ $lead->id }}" {{ old('crm_lead_id', isset($fromLead) ? $fromLead->id : '') == $lead->id ? 'selected' : '' }}>
                                    {{ $lead->lead_id }} – {{ $lead->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('crm_lead_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Salesperson</label>
                            <select name="salesperson_id" class="form-input" style="appearance:none">
                                <option value="">— Select salesperson —</option>
                                @foreach($salesUsers as $user)
                                <option value="{{ $user->id }}" {{ old('salesperson_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ $user->role ? ' ('.$user->role.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('salesperson_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" rows="2" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Terms &amp; Conditions</label>
                            <textarea name="terms_conditions" class="form-input form-textarea" rows="2" placeholder="Payment terms, delivery conditions...">{{ old('terms_conditions') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Line Items Card --}}
            <div class="card" id="lineItems">
                <div class="card-header">
                    <span class="card-title">Line Items</span>
                    <button type="button" class="btn btn-outline btn-sm" id="addLine">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Line
                    </button>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table" id="linesTable">
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
                            {{-- pre-populated or default first row --}}
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
                                    <button type="button" class="btn btn-danger btn-xs remove-line" title="Remove">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right (sticky) --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            {{-- Discount --}}
            <div class="card">
                <div class="card-header"><span class="card-title">Discount</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Discount %</label>
                        <input type="number" name="discount_percent" id="discountInput" class="form-input" value="{{ old('discount_percent', 0) }}" min="0" max="15" step="0.5">
                        <div class="form-hint">Maximum discount: 15%</div>
                        @error('discount_percent')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Approval Section (hidden by default) --}}
                    <div id="approvalSection" style="display:none;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px;margin-top:8px">
                        <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <div style="font-size:12px;color:#92400E;font-weight:600">Discount above 10% requires Finance/Owner approval.</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:10.5px">Request Approval From</label>
                            <select name="approval_user_id" class="form-input" style="appearance:none;font-size:12.5px;padding:6px 10px">
                                <option value="">— Select approver —</option>
                                @foreach($salesUsers->whereIn('role', ['owner','finance']) as $approver)
                                <option value="{{ $approver->id }}">{{ $approver->name }} ({{ $approver->role }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Summary --}}
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
                        Create Quotation
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
    let lineCount = 1;

    function formatRp(val) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }

    function calcLine(row) {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const disc  = parseFloat(row.querySelector('.disc-input').value) || 0;
        const sub   = qty * price * (1 - disc / 100);
        const pbjt  = sub * PBJT_RATE;
        const total = sub + pbjt;
        row.querySelector('.line-subtotal').textContent = formatRp(sub);
        row.querySelector('.line-pbjt').textContent     = formatRp(pbjt);
        row.querySelector('.line-total').textContent    = formatRp(total);
        return { sub, pbjt, total };
    }

    function recalcAll() {
        let subtotal = 0;
        document.querySelectorAll('#linesBody tr').forEach(row => {
            const r = calcLine(row);
            subtotal += r.sub;
        });
        const discPct  = parseFloat(document.getElementById('discountInput').value) || 0;
        const discount = subtotal * discPct / 100;
        const afterDisc= subtotal - discount;
        const pbjt     = afterDisc * PBJT_RATE;
        const grand    = afterDisc + pbjt;
        document.getElementById('summarySubtotal').textContent = formatRp(subtotal);
        document.getElementById('summaryDiscount').textContent = '- ' + formatRp(discount);
        document.getElementById('summaryPbjt').textContent     = formatRp(pbjt);
        document.getElementById('summaryTotal').textContent    = formatRp(grand);
        // Show/hide approval section
        document.getElementById('approvalSection').style.display = discPct > 10 ? 'block' : 'none';
    }

    function addLine() {
        const idx  = lineCount++;
        const tbody = document.getElementById('linesBody');
        const tr   = document.createElement('tr');
        tr.setAttribute('data-line', idx);
        tr.innerHTML = `
            <td style="color:#B8B2A6;font-size:12px">${tbody.children.length + 1}</td>
            <td>
                <select name="lines[${idx}][product_id]" class="form-input product-select" style="appearance:none;font-size:12.5px;padding:5px 8px">
                    <option value="">— Select product —</option>
                    ${Array.from(document.querySelector('.product-select').options).slice(1).map(o =>
                        `<option value="${o.value}" data-price="${o.dataset.price}">${o.text}</option>`
                    ).join('')}
                </select>
            </td>
            <td><input type="number" name="lines[${idx}][quantity]" class="form-input qty-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="1" min="1"></td>
            <td><input type="number" name="lines[${idx}][unit_price]" class="form-input price-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0"></td>
            <td><input type="number" name="lines[${idx}][discount_percent]" class="form-input disc-input" style="text-align:right;padding:5px 8px;font-size:12.5px" value="0" min="0" max="100"></td>
            <td style="text-align:right;font-size:12.5px" class="line-subtotal">Rp 0</td>
            <td style="text-align:right;font-size:12.5px;color:#6B6560" class="line-pbjt">Rp 0</td>
            <td style="text-align:right;font-size:13px;font-weight:700" class="line-total">Rp 0</td>
            <td>
                <button type="button" class="btn btn-danger btn-xs remove-line" title="Remove">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </td>`;
        tbody.appendChild(tr);
        bindRow(tr);
        recalcAll();
    }

    function bindRow(row) {
        row.querySelector('.product-select').addEventListener('change', function() {
            const opt   = this.options[this.selectedIndex];
            const price = parseFloat(opt.dataset.price) || 0;
            row.querySelector('.price-input').value = price;
            recalcAll();
        });
        row.querySelectorAll('.qty-input, .price-input, .disc-input').forEach(inp => {
            inp.addEventListener('input', recalcAll);
        });
        row.querySelector('.remove-line').addEventListener('click', function() {
            if (document.querySelectorAll('#linesBody tr').length > 1) {
                row.remove();
                // re-number
                document.querySelectorAll('#linesBody tr').forEach((r, i) => {
                    r.children[0].textContent = i + 1;
                });
                recalcAll();
            }
        });
    }

    // bind existing rows
    document.querySelectorAll('#linesBody tr').forEach(bindRow);

    document.getElementById('addLine').addEventListener('click', addLine);
    document.getElementById('discountInput').addEventListener('input', recalcAll);

    recalcAll();
})();
</script>
@endpush
