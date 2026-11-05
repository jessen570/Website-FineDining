@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.leads.index') }}">Prospek</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Tambah Lead</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Tambah Prospek Baru</div>
        <div class="page-desc">Catat prospek pelanggan atau calon reservasi acara baru.</div>
    </div>
    <a href="{{ route('crm.leads.index') }}" class="btn btn-outline">
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

<form method="POST" action="{{ route('crm.leads.store') }}">
    @csrf
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        {{-- Sisi Kiri: Form Utama --}}
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informasi Prospek</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        {{-- Name --}}
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Nama Lengkap / Kontak <span style="color:#DC2626">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name') }}" required placeholder="Nama kontak utama prospek">
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Perusahaan / Organisasi</label>
                            <input type="text" name="company_name" class="form-input" value="{{ old('company_name') }}" placeholder="PT / CV / Instansi">
                            @error('company_name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontak Person</label>
                            <input type="text" name="contact_person" class="form-input" value="{{ old('contact_person') }}" placeholder="Perwakilan yang dapat dihubungi">
                            @error('contact_person')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="kontak@perusahaan.com">
                            @error('email')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" placeholder="+62 8xx xxxx xxxx">
                            @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hubungkan Pelanggan Terdaftar</label>
                            <select name="customer_id" class="form-input" style="appearance:none">
                                <option value="">— Belum Terdaftar sebagai Pelanggan —</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}" {{ old('customer_id') == $customer->contact_id ? 'selected' : '' }}>
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
                                <option value="{{ $src }}" {{ old('lead_source') == $src ? 'selected' : '' }}>{{ $src }}</option>
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
                                <option value="{{ $s }}" {{ old('lead_status', 'New') == $s ? 'selected' : '' }}>{{ $statusNames[$s] ?? $s }}</option>
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
                                <option value="{{ $stage }}" {{ old('stage', 'Lead') == $stage ? 'selected' : '' }}>{{ $stageNames[$stage] ?? $stage }}</option>
                                @endforeach
                            </select>
                            @error('stage')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estimasi Nilai Pendapatan (Rp)</label>
                            <input type="number" name="expected_revenue" class="form-input" value="{{ old('expected_revenue') }}" placeholder="0" min="0">
                            @error('expected_revenue')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Probabilitas Kemenangan (%)</label>
                            <input type="number" name="probability" class="form-input" value="{{ old('probability') }}" placeholder="0" min="0" max="100">
                            <div class="form-hint">Masukkan nilai antara 0 sampai 100.</div>
                            @error('probability')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Target Tanggal Penutupan</label>
                            <input type="date" name="closing_date" class="form-input" value="{{ old('closing_date') }}">
                            @error('closing_date')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Penanggung Jawab (Staf)</label>
                            <select name="assigned_to" class="form-input" style="appearance:none">
                                <option value="">— Belum Ditugaskan —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ $user->role ? ' ('.$user->role.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Informasi tambahan mengenai preferensi prospek atau kebutuhan acara...">{{ old('notes') }}</textarea>
                            @error('notes')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-top:20px;display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">Simpan Prospek</button>
                        <a href="{{ route('crm.leads.index') }}" class="btn btn-outline">Batal</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan: Panduan --}}
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Petunjuk Pengisian</span></div>
                <div class="card-body" style="font-size:12.5px;color:#6B6560;line-height:1.6">
                    <p style="margin-bottom:10px"><strong>Prospek</strong> mencatat calon pelanggan baru atau pihak yang tertarik melakukan reservasi rombongan / banquet.</p>
                    <p style="margin-bottom:10px">Setelah prospek memenuhi kualifikasi, Anda dapat mengonversinya menjadi <strong>Peluang Penjualan (Opportunity)</strong> untuk mulai menerbitkan Surat Penawaran.</p>
                    <p>Estimasi nilai dan probabilitas kemenangan akan otomatis dihitung dalam visualisasi <strong>Sales Pipeline</strong> restoran.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
