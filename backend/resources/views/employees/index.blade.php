@extends('layouts.app')

@section('title', 'Karyawan — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Karyawan</span>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Karyawan</h1>
        <p class="page-desc">Data karyawan — hanya Employee Aktif yang dapat ditugaskan (SR-13). Data privat hanya terlihat oleh Owner/HR (SR-14).</p>
    </div>
    @if(in_array($role, ['owner','hr']))
    <a href="{{ route('employees.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Karyawan
    </a>
    @endif
</div>

@if(session('success'))
    <div class="flash-success mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- ── KPIs ─────────────────────────────────────────────────────────────── --}}
@php
    $totalEmp  = \App\Models\Employee::count();
    $activeEmp = \App\Models\Employee::where('status','Aktif')->count();
    $inactiveEmp = $totalEmp - $activeEmp;
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
    <div class="kpi">
        <div class="kpi-label">Total Karyawan</div>
        <div class="kpi-value">{{ $totalEmp }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Aktif</div>
        <div class="kpi-value" style="color:#059669">{{ $activeEmp }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Tidak Aktif</div>
        <div class="kpi-value" style="color:#DC2626">{{ $inactiveEmp }}</div>
        @if($inactiveEmp > 0)
            <div class="kpi-sub">SR-13: tidak bisa ditugaskan</div>
        @endif
    </div>
</div>

{{-- ── Toolbar ─────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('employees.index') }}">
<div class="toolbar mb-5">
    <div class="toolbar-search flex-1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Cari nama, ID, departemen…" value="{{ request('search') }}">
    </div>
    <select name="status" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="Aktif" {{ request('status') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
        <option value="Tidak Aktif" {{ request('status') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
    </select>
    <select name="department" class="toolbar-select" onchange="this.form.submit()">
        <option value="">Semua Departemen</option>
        @foreach($departments as $dep)
            <option value="{{ $dep }}" {{ request('department') == $dep ? 'selected' : '' }}>{{ $dep }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    @if(request()->hasAny(['search','status','department']))
        <a href="{{ route('employees.index') }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</div>
</form>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    @if(in_array($role, ['owner','hr']))
                        <th>Kontak Privat</th>
                    @endif
                    <th class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td class="col-id">{{ $emp->employee_id }}</td>
                    <td class="col-name">
                        <a href="{{ route('employees.show', $emp->employee_id) }}">{{ $emp->name }}</a>
                    </td>
                    <td class="text-xs">{{ $emp->job_position }}</td>
                    <td>
                        <span class="badge b-forest" style="font-size:10px">{{ $emp->department }}</span>
                    </td>
                    <td>
                        @if($emp->status === 'Aktif')
                            <span class="badge b-emerald">
                                <span class="badge-dot" style="background:#059669"></span> Aktif
                            </span>
                        @else
                            <span class="badge b-red">
                                <span class="badge-dot" style="background:#DC2626"></span> Tidak Aktif
                            </span>
                        @endif
                    </td>
                    @if(in_array($role, ['owner','hr']))
                    <td class="text-xs text-stone-500">
                        {{ $emp->private_phone ?? '—' }}
                    </td>
                    @endif
                    <td class="col-actions">
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('employees.show', $emp->employee_id) }}" class="btn btn-outline btn-xs">Detail</a>
                            @if(in_array($role, ['owner','hr']))
                                <a href="{{ route('employees.edit', $emp->employee_id) }}" class="btn btn-ghost btn-xs">Edit</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ in_array($role,['owner','hr']) ? 7 : 6 }}">
                        <div class="empty-state">
                            <div class="empty-title">Tidak ada karyawan ditemukan</div>
                            <div class="empty-text">Coba ubah filter pencarian.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8;display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:#8A8478">
        <span>Menampilkan {{ $employees->firstItem() }}–{{ $employees->lastItem() }} dari {{ $employees->total() }}</span>
        {{ $employees->withQueryString()->links('pagination::simple-tailwind') }}
    </div>
    @endif
</div>

@endsection
