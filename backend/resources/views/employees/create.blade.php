@extends('layouts.app')

@section('title', 'Tambah Karyawan — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('employees.index') }}">Karyawan</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Tambah</span>
@endsection

@section('content')
<div style="max-width:820px">

<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Karyawan Baru</h1>
        <p class="page-desc">Data kontak privat hanya terlihat oleh Owner dan HR (SR-14).</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline">← Kembali</a>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
    <div><strong>Validasi gagal:</strong>
        <ul class="mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('employees.store') }}">
@csrf
<div class="card mb-5">
    <div class="card-header"><span class="card-title">Informasi Jabatan</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
            <div class="form-group">
                <label class="form-label">ID Karyawan * <span class="form-hint" style="display:inline">(E-01 s.d. E-XX)</span></label>
                <input type="text" name="employee_id" class="form-input {{ $errors->has('employee_id') ? 'is-error' : '' }}"
                    value="{{ old('employee_id') }}" placeholder="E-10" required>
                @error('employee_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                    value="{{ old('name') }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Jabatan *</label>
                <input type="text" name="job_position" class="form-input {{ $errors->has('job_position') ? 'is-error' : '' }}"
                    value="{{ old('job_position') }}" placeholder="Server, Head Chef, …" required>
                @error('job_position')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Departemen *</label>
                <select name="department" class="form-select {{ $errors->has('department') ? 'is-error' : '' }}" required>
                    <option value="">— Pilih Departemen —</option>
                    @foreach(['Management','Restaurant','Kitchen','Finance','Warehouse','Procurement','HR'] as $dep)
                        <option value="{{ $dep }}" {{ old('department') == $dep ? 'selected' : '' }}>{{ $dep }}</option>
                    @endforeach
                </select>
                @error('department')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Status *</label>
                <div style="display:flex;gap:20px;margin-top:4px">
                    @foreach(['Aktif','Tidak Aktif'] as $st)
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                        <input type="radio" name="status" value="{{ $st }}" {{ old('status', 'Aktif') == $st ? 'checked' : '' }}
                            style="accent-color:#18382E">
                        {{ $st }}
                    </label>
                    @endforeach
                </div>
                @error('status')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-5">
    <div class="card-header">
        <span class="card-title">Kontak Privat</span>
        <span class="badge b-amber" style="font-size:10px">SR-14: Hanya Owner/HR</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
            <div class="form-group">
                <label class="form-label">No. HP Pribadi</label>
                <input type="text" name="private_phone" class="form-input" value="{{ old('private_phone') }}" placeholder="08XXXXXXXXXX">
            </div>
            <div class="form-group">
                <label class="form-label">Email Pribadi</label>
                <input type="email" name="private_email" class="form-input" value="{{ old('private_email') }}" placeholder="nama@email.com">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Alamat Rumah</label>
                <textarea name="private_address" class="form-input form-textarea">{{ old('private_address') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="{{ route('employees.index') }}" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
</div>
</form>
</div>
@endsection
