@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Sales</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.customers.index') }}">Customers</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('sales.customers.show', $customer) }}">{{ $customer->name }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Edit</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Edit Customer</div>
        <div class="page-desc">Update customer profile information.</div>
    </div>
    <a href="{{ route('sales.customers.show', $customer) }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
</div>

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

<form method="POST" action="{{ route('sales.customers.update', $customer) }}">
    @csrf
    @method('PATCH')
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Left --}}
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Customer Information</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        {{-- Contact ID: readonly --}}
                        <div class="form-group">
                            <label class="form-label">Contact ID</label>
                            <div style="padding:8px 12px;background:#F7F5F0;border:1px solid #E8E4DA;border-radius:7px;font-size:13.5px;color:#6B6560;font-family:monospace">{{ $customer->contact_id }}</div>
                            <input type="hidden" name="contact_id" value="{{ $customer->contact_id }}">
                            <div class="form-hint">Contact ID cannot be changed.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Full Name <span style="color:#DC2626">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $customer->name) }}" required>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Customer Type</label>
                            <select name="customer_type" class="form-input" style="appearance:none">
                                <option value="Individu"   {{ old('customer_type', $customer->customer_type) == 'Individu'   ? 'selected' : '' }}>Individu</option>
                                <option value="Perusahaan" {{ old('customer_type', $customer->customer_type) == 'Perusahaan' ? 'selected' : '' }}>Perusahaan</option>
                            </select>
                            @error('customer_type')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-input" value="{{ old('phone', $customer->phone) }}">
                            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="{{ old('email', $customer->email) }}">
                            @error('email')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-input" value="{{ old('city', $customer->city) }}">
                            @error('city')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input form-textarea">{{ old('address', $customer->address) }}</textarea>
                            @error('address')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tax ID (NPWP)</label>
                            <input type="text" name="tax_id" class="form-input" value="{{ old('tax_id', $customer->tax_id) }}" placeholder="00.000.000.0-000.000">
                            @error('tax_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input" style="appearance:none">
                                <option value="active"   {{ old('status', $customer->status) == 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $customer->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:8px">
                        <button type="submit" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Changes
                        </button>
                        <a href="{{ route('sales.customers.show', $customer) }}" class="btn btn-outline">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            <div class="card">
                <div class="card-header"><span class="card-title">Credit &amp; Payment Terms</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Credit Limit (Rp)</label>
                        <input type="number" name="credit_limit" class="form-input" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" min="0">
                        <div class="form-hint">Set to 0 for no credit limit.</div>
                        @error('credit_limit')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-input" value="{{ old('payment_terms', $customer->payment_terms) }}" placeholder="e.g. Net 14, Net 30, Tunai">
                        @error('payment_terms')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Opening AR (Rp)</label>
                        <input type="number" name="opening_ar" class="form-input" value="{{ old('opening_ar', $customer->opening_ar ?? 0) }}" min="0">
                        <div class="form-hint">Opening Accounts Receivable balance.</div>
                        @error('opening_ar')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
