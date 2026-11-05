@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.leads.index') }}">Prospek</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.leads.show', $lead->lead_id ?? $lead->id) }}">{{ $lead->lead_id }}</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Ubah</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Ubah Data Prospek — {{ $lead->lead_id }}</div>
        <div class="page-desc">Perbarui rincian informasi dan status prospek pelanggan.</div>
    </div>
    <a href="{{ route('crm.leads.show', $lead->lead_id ?? $lead->id) }}" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

@if($lead->is_converted)
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div>
        <div style="font-size:12.5px;font-weight:700;color:#92400E">Prospek telah dikonversi</div>
        <div style="font-size:12px;color:#78350F;margin-top:2px">Prospek ini telah dikonversikan menjadi peluang aktif. Perubahan data di sini tetap dicatat dalam riwayat audit.</div>
    </div>
</div>
@endif

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

<form method="POST" action="{{ route('crm.leads.update', $lead->lead_id ?? $lead->id) }}">
    @csrf
    @method('PATCH')
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Sisi Kiri --}}
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informasi Prospek</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Nama Lengkap / Kontak <span style="color:#DC2626">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $lead->name) }}" required>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Perusahaan / Organisasi</label>
                            <input type="text" name="company_name" class="form-input" value="{{ old('company_name', $lead->company_name) }}">
                            @error('company_name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontak Person</label>
                            <input type="text" name="contact_person" class="form-input" value="{{ old('contact_person', $lead->contact_person) }}">
                            @error('contact_person')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-input" value="{{ old('email', $lead->email) }}">
                            @error('email')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" class="form-input" value="{{ old('phone', $lead->phone) }}">
                            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hubungkan Pelanggan Terdaftar</label>
                            <select name="customer_id" class="form-input" style="appearance:none">
                                <option value="">— Belum Terdaftar sebagai Pelanggan —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}" {{ old('customer_id', $lead->customer_id) == $customer->contact_id ? 'selected' : '' }}>
                                    {{ $customer->name }}{{ $customer->city ? ' — '.$customer->city : '' }} ({{ $customer->contact_id }})
                                </option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sumber Prospek</label>
                            <select name="lead_source" class="form-input" style="appearance:none">
                                <option value="">— Pilih Sumber Prospek —</option>
                                @foreach($sources as $src)
                                <option value="{{ $src }}" {{ old('lead_source', $lead->lead_source) == $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                            @error('lead_source')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status Prospek</label>
                            <select name="lead_status" class="form-input" style="appearance:none">
                                @php
                                    $statusNames = ['New'=>'Baru (New)','Contacted'=>'Telah Dihubungi (Contacted)','Qualified'=>'Terkualifikasi (Qualified)','Unqualified'=>'Tidak Memenuhi Syarat'];
                                @endphp
                                @foreach($statuses as $s)
                                <option value="{{ $s }}" {{ old('lead_status', $lead->lead_status) == $s ? 'selected' : '' }}>{{ $statusNames[$s] ?? $s }}</option>
                                @endforeach
                            </select>
                            @error('lead_status')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tahapan Pipeline</label>
                            <select name="stage" class="form-input" style="appearance:none">
                                @php
                                    $stageNames = ['Lead'=>'Prospek (Lead)','Qualified'=>'Terkualifikasi (Qualified)','Proposition'=>'Penawaran (Proposition)','Negotiation'=>'Negosiasi (Negotiation)','Won'=>'Berhasil Menang (Won)','Lost'=>'Gagal (Lost)'];
                                @endphp
                                @foreach($stages as $stage)
                                <option value="{{ $stage }}" {{ old('stage', $lead->stage) == $stage ? 'selected' : '' }}>{{ $stageNames[$stage] ?? $stage }}</option>
                                @endforeach
                            </select>
                            @error('stage')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estimasi Nilai Pendapatan (Rp)</label>
                            <input type="number" name="expected_revenue" class="form-input" value="{{ old('expected_revenue', $lead->expected_revenue) }}" min="0">
                            @error('expected_revenue')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Probabilitas (%)</label>
                            <input type="number" name="probability" class="form-input" value="{{ old('probability', $lead->probability) }}" min="0" max="100">
                            @error('probability')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Target Tanggal Penutupan</label>
                            <input type="date" name="closing_date" class="form-input" value="{{ old('closing_date', $lead->closing_date ? \Carbon\Carbon::parse($lead->closing_date)->format('Y-m-d') : '') }}">
                            @error('closing_date')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Penanggung Jawab (Staf)</label>
                            <select name="assigned_to" class="form-input" style="appearance:none">
                                <option value="">— Belum Ditugaskan —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-input form-textarea">{{ old('notes', $lead->notes) }}</textarea>
                            @error('notes')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-top:20px;display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('crm.leads.show', $lead->lead_id ?? $lead->id) }}" class="btn btn-outline">Batal</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan: Metadata --}}
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi Sistem</span></div>
                <div class="card-body" style="font-size:12.5px;color:#6B6560;line-height:1.6">
                    <p style="margin-bottom:8px"><strong>ID Prospek:</strong> {{ $lead->lead_id }}</p>
                    <p style="margin-bottom:8px"><strong>Dibuat Pada:</strong> {{ \Carbon\Carbon::parse($lead->created_at)->translatedFormat('d F Y, H:i') }}</p>
                    <p><strong>Terakhir Diperbarui:</strong> {{ \Carbon\Carbon::parse($lead->updated_at)->translatedFormat('d F Y, H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
