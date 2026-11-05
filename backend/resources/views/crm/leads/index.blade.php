@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Prospek</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Prospek (Lead)</div>
        <div class="page-desc">Kelola dan kualifikasi prospek pelanggan masuk.</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('crm.leads.create') }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Lead
        </a>
    </div>
</div>

{{-- KPI Cards --}}
@php
    $coll = $leads->getCollection();
    $countNew = $coll->where('lead_status','New')->count();
    $countContacted = $coll->where('lead_status','Contacted')->count();
    $countQualified = $coll->where('lead_status','Qualified')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px" class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        </div>
        <div class="kpi-label">Total Prospek</div>
        <div class="kpi-value">{{ $leads->total() }}</div>
        <div class="kpi-sub">Semua prospek terdaftar</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#F9FAFB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <div class="kpi-label">Baru</div>
        <div class="kpi-value">{{ $countNew }}</div>
        <div class="kpi-sub">Menunggu tindakan</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.74h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.1a16 16 0 0 0 6 6l1.27-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17.42v-.5z"/></svg>
        </div>
        <div class="kpi-label">Dihubungi</div>
        <div class="kpi-value">{{ $countContacted }}</div>
        <div class="kpi-sub">Dalam komunikasi</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Terkualifikasi</div>
        <div class="kpi-value">{{ $countQualified }}</div>
        <div class="kpi-sub">Siap menjadi peluang</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('crm.leads.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search lead..." value="{{ request('search') }}">
        </div>
        <select name="status" class="toolbar-select">
            <option value="">Semua Status</option>
            @foreach($statuses as $s)
                @php
                    $sLabel = match($s) {
                        'New' => 'Baru (New)',
                        'Contacted' => 'Dihubungi (Contacted)',
                        'Qualified' => 'Terkualifikasi (Qualified)',
                        'Unqualified' => 'Gugur (Unqualified)',
                        default => $s
                    };
                @endphp
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $sLabel }}</option>
            @endforeach
        </select>
        <select name="source" class="toolbar-select">
            <option value="">Semua Sumber</option>
            @foreach($sources as $src)
                <option value="{{ $src }}" {{ request('source') == $src ? 'selected' : '' }}>{{ $src }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('crm.leads.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($leads->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:80px">ID Prospek</th>
                    <th>Nama / Perusahaan</th>
                    <th>Kontak</th>
                    <th>Sumber</th>
                    <th>Status</th>
                    <th>Tahapan</th>
                    <th>Dibuat</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leads as $lead)
                <tr>
                    <td>
                        <span style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $lead->lead_id }}</span>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:13.5px;color:#1E1C1A">
                            <a href="{{ route('crm.leads.show', $lead->lead_id) }}" style="color:#18382E;text-decoration:none" onmouseover="this.style.color='#C6A76B'" onmouseout="this.style.color='#18382E'">{{ $lead->name }}</a>
                        </div>
                        @if($lead->company_name)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ $lead->company_name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($lead->email)
                        <div style="font-size:12.5px;color:#4D4944">{{ $lead->email }}</div>
                        @endif
                        @if($lead->phone)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ $lead->phone }}</div>
                        @endif
                    </td>
                    <td>
                        @if($lead->lead_source)
                        <span class="badge b-gold">{{ $lead->lead_source }}</span>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusMap = ['New'=>'b-gray','Contacted'=>'b-blue','Qualified'=>'b-green','Unqualified'=>'b-red'];
                            $statusClass = $statusMap[$lead->lead_status] ?? 'b-gray';
                            $statusLabel = match($lead->lead_status) {
                                'New' => 'Baru',
                                'Contacted' => 'Dihubungi',
                                'Qualified' => 'Terkualifikasi',
                                'Unqualified' => 'Gugur',
                                default => $lead->lead_status
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>
                        @php
                            $stageMap = ['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red'];
                            $stageClass = $stageMap[$lead->stage] ?? 'b-gray';
                            $stageLabel = match($lead->stage) {
                                'Lead' => 'Prospek',
                                'Qualified' => 'Kualifikasi',
                                'Proposition' => 'Penawaran',
                                'Negotiation' => 'Negosiasi',
                                'Won' => 'Berhasil (Won)',
                                'Lost' => 'Gagal (Lost)',
                                default => $lead->stage
                            };
                        @endphp
                        <span class="badge {{ $stageClass }}">{{ $stageLabel }}</span>
                    </td>
                    <td style="font-size:12.5px;color:#6B6560">
                        {{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y') }}
                    </td>
                    <td>
                        <a href="{{ route('crm.leads.show', $lead->lead_id) }}" class="btn btn-outline btn-xs">Lihat</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <div class="empty-title">Tidak ada prospek ditemukan</div>
            <div class="empty-text">Tambahkan prospek pertama Anda untuk memulai pipeline penjualan.</div>
            <a href="{{ route('crm.leads.create') }}" class="btn btn-primary btn-sm">Tambah Prospek</a>
        </div>
        @endif
    </div>
    @if($leads->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $leads->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
