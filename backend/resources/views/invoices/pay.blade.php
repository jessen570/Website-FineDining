@extends('layouts.app')
@section('title', 'Register Payment — ' . $invoice->invoice_id)

@section('breadcrumb')
<a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('invoices.index') }}" class="breadcrumb-item">Faktur & Tagihan</a>
<span class="breadcrumb-sep">›</span>
<a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="breadcrumb-item">{{ $invoice->invoice_id }}</a>
<span class="breadcrumb-sep">›</span>
<span class="breadcrumb-item current">Bayar</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Register Pembayaran</h1>
            <p class="page-desc">S-13 · F-16 · SR-16 — Catat pembayaran untuk <strong>{{ $invoice->invoice_id }}</strong></p>
        </div>
        <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </a>
    </div>

    {{-- Flash --}}
    @if(session('error'))
        <div class="flash-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Invoice Summary Card --}}
    <div class="card overflow-hidden">
        <div class="card-header" style="background:linear-gradient(135deg,#18382E,#244A3D)">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-white/60 mb-1">{{ $invoice->type }}</div>
                <div class="text-xl font-bold text-white font-mono">{{ $invoice->invoice_id }}</div>
            </div>
            <span class="badge {{ $invoice->status === 'Paid' ? 'b-emerald' : ($invoice->status === 'Posted' ? 'b-blue' : 'b-amber') }} text-sm">
                {{ $invoice->status }}
            </span>
        </div>
        <div class="card-body">
            {{-- Status Pipeline --}}
            <div class="status-pipeline mb-4">
                @php
                    $steps = ['Draft','Posted','Paid'];
                    $cur = array_search($invoice->status, $steps);
                @endphp
                @foreach($steps as $i => $step)
                <div class="pipeline-step {{ $i < $cur ? 'done' : ($i == $cur ? 'active' : '') }}">
                    <div class="pipeline-dot">
                        @if($i < $cur)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @else{{ $i + 1 }}@endif
                    </div>
                    <div class="pipeline-label">{{ $step }}</div>
                </div>
                @endforeach
            </div>
            <hr class="divider">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">
                        {{ $invoice->type === 'Customer Invoice' ? 'Pelanggan' : 'Vendor' }}
                    </div>
                    <div class="font-semibold text-stone-800">{{ $invoice->partner?->name ?? '—' }}</div>
                    <div class="text-xs text-stone-400">{{ $invoice->partner_id }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-stone-400 font-semibold mb-1">Tanggal Invoice</div>
                    <div class="font-medium text-stone-700">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</div>
                </div>
            </div>
            <hr class="divider">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-stone-50 rounded-lg p-3 text-center border border-stone-200">
                    <div class="text-xs text-stone-400 uppercase tracking-wider font-semibold mb-1">Total Tagihan</div>
                    <div class="text-base font-bold text-stone-800 font-mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-3 text-center border border-emerald-200">
                    <div class="text-xs text-emerald-600 uppercase tracking-wider font-semibold mb-1">Sudah Dibayar</div>
                    <div class="text-base font-bold text-emerald-700 font-mono">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</div>
                </div>
                <div class="bg-amber-50 rounded-lg p-3 text-center border border-amber-200">
                    <div class="text-xs text-amber-600 uppercase tracking-wider font-semibold mb-1">Sisa Tagihan</div>
                    <div class="text-xl font-bold text-amber-700 font-mono">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- SR-16 Warning --}}
    <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <strong>SR-16:</strong> Jumlah pembayaran tidak boleh melebihi sisa tagihan.
            Maksimum yang dapat dibayarkan saat ini: <strong class="font-mono">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</strong>.
            Overpayment akan otomatis ditolak sistem.
        </div>
    </div>

    {{-- Payment Form --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#18382E" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Form Registrasi Pembayaran
            </span>
        </div>
        <div class="card-body">
            <form action="{{ route('invoices.pay', $invoice->invoice_id) }}" method="POST" id="payForm">
                @csrf
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Jumlah Pembayaran (IDR) <span class="text-red-500">*</span></label>
                        <input type="number"
                               name="amount"
                               id="amount"
                               class="form-input @error('amount') is-error @enderror"
                               placeholder="Masukkan jumlah pembayaran"
                               value="{{ old('amount', number_format($invoice->amount_due, 0, '.', '')) }}"
                               min="1"
                               max="{{ $invoice->amount_due }}"
                               step="1"
                               required>
                        <p class="form-hint">Maksimum: <span class="font-mono font-semibold text-amber-700">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span> (SR-16)</p>
                        @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Metode Pembayaran <span class="text-red-500">*</span></label>
                        <select name="payment_method" class="form-select @error('payment_method') is-error @enderror" required>
                            <option value="">— Pilih metode —</option>
                            <option value="Bank" {{ old('payment_method') == 'Bank' ? 'selected' : '' }}>🏦 Bank Transfer (Akun 1010)</option>
                            <option value="Kas Restoran" {{ old('payment_method') == 'Kas Restoran' ? 'selected' : '' }}>💵 Kas Restoran (Akun 1015)</option>
                        </select>
                        @error('payment_method')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date"
                               name="payment_date"
                               class="form-input @error('payment_date') is-error @enderror"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                               required>
                        @error('payment_date')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan / Keterangan</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Catatan pembayaran (opsional)">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Preview Calculation --}}
                    <div class="bg-stone-50 border border-stone-200 rounded-lg p-4" id="payPreview">
                        <div class="text-xs font-bold uppercase tracking-wider text-stone-500 mb-3">Pratinjau Jurnal Pembayaran</div>
                        <div class="space-y-2 text-sm">
                            @if($invoice->type === 'Customer Invoice')
                                <div class="flex justify-between">
                                    <span class="text-stone-500">DR Bank / Kas</span>
                                    <span class="font-mono font-semibold text-emerald-700" id="prevDebit">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-stone-500">CR Piutang Usaha (1100)</span>
                                    <span class="font-mono font-semibold text-red-600" id="prevCredit">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="flex justify-between">
                                    <span class="text-stone-500">DR Utang Usaha (2010)</span>
                                    <span class="font-mono font-semibold text-emerald-700" id="prevDebit">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-stone-500">CR Bank / Kas</span>
                                    <span class="font-mono font-semibold text-red-600" id="prevCredit">Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <hr class="divider">
                            <div class="flex justify-between text-xs text-stone-400">
                                <span>Sisa tagihan setelah bayar:</span>
                                <span class="font-mono" id="prevRemaining">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-stone-100">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi pembayaran?')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Konfirmasi Pembayaran
                    </button>
                    <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Payment History --}}
    @if($invoice->payments->count() > 0)
    <div class="card overflow-hidden">
        <div class="card-header">
            <span class="card-title">Riwayat Pembayaran Sebelumnya</span>
            <span class="badge b-gray">{{ $invoice->payments->count() }} transaksi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Tanggal</th>
                        <th>Metode</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Sisa Setelah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $pay)
                    <tr>
                        <td class="col-id">{{ $pay->payment_id }}</td>
                        <td class="text-sm">{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</td>
                        <td class="text-sm">{{ $pay->payment_method }}</td>
                        <td class="text-right font-mono font-semibold text-emerald-700">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                        <td class="text-right font-mono text-stone-500">Rp {{ number_format($pay->remaining_due, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<script>
document.getElementById('amount')?.addEventListener('input', function() {
    const val   = parseFloat(this.value) || 0;
    const due   = {{ (float)$invoice->amount_due }};
    const fmt   = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const remaining = Math.max(0, due - val);
    document.querySelectorAll('#prevDebit,#prevCredit').forEach(el => el.textContent = fmt(val));
    const remEl = document.getElementById('prevRemaining');
    if (remEl) remEl.textContent = fmt(remaining);
});
</script>
@endsection
