@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.opportunities.index') }}">Peluang</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.opportunities.show', $opp->opportunity_id ?? $opp->lead_id ?? $opp->id) }}">{{ $opp->opportunity_id ?? $opp->lead_id ?? $opp->id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Ubah</span>
@endsection

@section('content')
@php
    $oppId = $opp->opportunity_id ?? $opp->lead_id ?? $opp->id;
@endphp
<div class="page-header">
    <div>
        <div class="page-title">Ubah Peluang Penjualan — {{ $oppId }}</div>
        <div class="page-desc">Perbarui rincian tahapan, estimasi nilai, dan penanggung jawab peluang.</div>
    </div>
    <a href="{{ route('crm.opportunities.show', $oppId) }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

@if($errors->any())
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;font-size:12.5px;color:#991B1B;margin-bottom:6px">Silakan perbaiki kesalahan berikut:</div>
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $error)
        <li style="font-size:12.5px;color:#DC2626">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('crm.opportunities.update', $oppId) }}">
    @csrf
    @method('PATCH')
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Rincian Peluang</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Nama Peluang / Proyek <span style="color:#DC2626">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $opp->name) }}" required>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Perusahaan / Organisasi</label>
                            <input type="text" name="company_name" class="form-input" value="{{ old('company_name', $opp->company_name) }}">
                            @error('company_name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pelanggan Terdaftar</label>
                            <select name="customer_id" class="form-input" style="appearance:none">
                                <option value="">— Belum Terdaftar sebagai Pelanggan —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}" {{ old('customer_id', $opp->customer_id) == $customer->contact_id ? 'selected' : '' }}>
                                    {{ $customer->name }}{{ $customer->city ? ' — '.$customer->city : '' }} ({{ $customer->contact_id }})
                                </option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tahapan Pipeline</label>
                            <select name="stage" class="form-input" style="appearance:none">
                                @php
                                    $stageNames = ['Lead'=>'Prospek (Lead)','Qualified'=>'Terkualifikasi (Qualified)','Proposition'=>'Penawaran (Proposition)','Negotiation'=>'Negosiasi (Negotiation)','Won'=>'Berhasil Menang (Won)','Lost'=>'Gagal (Lost)'];
                                @endphp
                                @foreach($stages as $stage)
                                <option value="{{ $stage }}" {{ old('stage', $opp->stage) == $stage ? 'selected' : '' }}>{{ $stageNames[$stage] ?? $stage }}</option>
                                @endforeach
                            </select>
                            @error('stage')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estimasi Nilai Pendapatan (Rp)</label>
                            <input type="number" name="expected_revenue" class="form-input" value="{{ old('expected_revenue', $opp->expected_revenue) }}" min="0">
                            @error('expected_revenue')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Probabilitas Kemenangan (%)</label>
                            <input type="number" name="probability" class="form-input" value="{{ old('probability', $opp->probability) }}" min="0" max="100">
                            <div class="form-hint">Masukkan nilai persentase antara 0 sampai 100.</div>
                            @error('probability')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Target Tanggal Penutupan</label>
                            <input type="date" name="closing_date" class="form-input" value="{{ old('closing_date', $opp->closing_date ? \Carbon\Carbon::parse($opp->closing_date)->format('Y-m-d') : '') }}">
                            @error('closing_date')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Penanggung Jawab (Staf)</label>
                            <select name="assigned_to" class="form-input" style="appearance:none">
                                <option value="">— Belum Ditugaskan —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $opp->assigned_to) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-input form-textarea">{{ old('notes', $opp->notes) }}</textarea>
                            @error('notes')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-top:20px;display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('crm.opportunities.show', $oppId) }}" class="btn btn-outline">Batal</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan --}}
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi Sistem</span></div>
                <div class="card-body" style="font-size:12.5px;color:#6B6560;line-height:1.6">
                    <p style="margin-bottom:8px"><strong>ID Peluang:</strong> {{ $oppId }}</p>
                    <p style="margin-bottom:8px"><strong>Dibuat Pada:</strong> {{ \Carbon\Carbon::parse($opp->created_at)->translatedFormat('d F Y, H:i') }}</p>
                    <p><strong>Terakhir Diperbarui:</strong> {{ \Carbon\Carbon::parse($opp->updated_at)->translatedFormat('d F Y, H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
