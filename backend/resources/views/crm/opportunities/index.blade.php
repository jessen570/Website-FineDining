@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Peluang</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Peluang Penjualan (Opportunities)</div>
        <div class="page-desc">Pantau dan kelola prospek bisnis serta kesepakatan aktif restoran.</div>
    </div>
    <a href="{{ route('crm.opportunities.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Peluang
    </a>
</div>

{{-- KPI Cards --}}
@php
    $coll = $opportunities->getCollection();
    $activePipeline = $coll->whereNotIn('stage', ['Won','Lost'])->count();
    $pipelineValue  = $coll->whereNotIn('stage', ['Won','Lost'])->sum('expected_revenue');
    $wonCount       = $coll->where('stage','Won')->count();
    $stageLabel = ['Lead'=>'Prospek','Qualified'=>'Terkualifikasi','Proposition'=>'Penawaran','Negotiation'=>'Negosiasi','Won'=>'Berhasil Menang','Lost'=>'Gagal'];
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-icon" style="background:#EFF6FF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E40AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="kpi-label">Pipeline Aktif</div>
        <div class="kpi-value">{{ $activePipeline }}</div>
        <div class="kpi-sub">Peluang yang sedang berjalan</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#FFFBEB">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="kpi-label">Total Nilai Pipeline</div>
        <div class="kpi-value" style="font-size:1.3rem">Rp {{ number_format($pipelineValue/1000000, 1, ',', '.') }} jt</div>
        <div class="kpi-sub">Estimasi pendapatan potensial</div>
    </div>
    <div class="kpi">
        <div class="kpi-icon" style="background:#ECFDF5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-label">Peluang Berhasil (Won)</div>
        <div class="kpi-value">{{ $wonCount }}</div>
        <div class="kpi-sub">Kesepakatan sukses ditutup</div>
    </div>
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('crm.opportunities.index') }}">
    <div class="toolbar" style="margin-bottom:16px">
        <div class="toolbar-search" style="flex:1;min-width:220px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" placeholder="Search peluang atau perusahaan..." value="{{ request('search') }}">
        </div>
        <select name="stage" class="toolbar-select">
            <option value="">Semua Tahapan</option>
            @foreach($stages as $stage)
            <option value="{{ $stage }}" {{ request('stage') == $stage ? 'selected' : '' }}>{{ $stageLabel[$stage] ?? $stage }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('crm.opportunities.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($opportunities->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:80px">ID</th>
                    <th>Peluang / Perusahaan</th>
                    <th>Pelanggan</th>
                    <th>Tahapan</th>
                    <th style="text-align:right">Estimasi Nilai</th>
                    <th style="width:120px">Probabilitas</th>
                    <th>Target Penutupan</th>
                    <th>Penanggung Jawab</th>
                    <th style="width:140px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($opportunities as $opp)
                @php
                    $stageMap = ['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red'];
                    $prob = (int)($opp->probability ?? 0);
                    $probColor = $prob >= 70 ? '#059669' : ($prob >= 40 ? '#D97706' : '#DC2626');
                    $closing = $opp->closing_date ? \Carbon\Carbon::parse($opp->closing_date) : null;
                    $closingColor = '#8A8478';
                    if($closing) {
                        if($closing->isPast()) $closingColor = '#DC2626';
                        elseif($closing->diffInDays(now()) <= 7) $closingColor = '#D97706';
                    }
                @endphp
                <tr>
                    <td><span style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $opp->opportunity_id ?? $opp->lead_id ?? $opp->id }}</span></td>
                    <td>
                        <div style="font-weight:600;font-size:13.5px;color:#1E1C1A">{{ $opp->name }}</div>
                        @if($opp->company_name)
                        <div style="font-size:11.5px;color:#8A8478;margin-top:1px">{{ $opp->company_name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($opp->customer_id && $opp->customer)
                        <a href="{{ route('sales.customers.show', $opp->customer_id) }}" style="font-size:13px;color:#18382E;text-decoration:none;font-weight:500">{{ $opp->customer->name }}</a>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $stageMap[$opp->stage] ?? 'b-gray' }}">{{ $stageLabel[$opp->stage] ?? $opp->stage }}</span>
                    </td>
                    <td style="text-align:right;font-weight:700;font-size:13px;color:#1E1C1A">
                        Rp {{ number_format($opp->expected_revenue ?? 0, 0, ',', '.') }}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <div style="flex:1;height:4px;background:#F0EEE8;border-radius:100px;overflow:hidden;min-width:50px">
                                <div style="width:{{ $prob }}%;height:100%;background:{{ $probColor }};border-radius:100px"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;color:{{ $probColor }};width:30px;text-align:right">{{ $prob }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($closing)
                        <span style="font-size:12.5px;color:{{ $closingColor }};font-weight:{{ $closingColor != '#8A8478' ? '600' : '400' }}">
                            {{ $closing->translatedFormat('d M Y') }}
                        </span>
                        @else
                        <span style="color:#B8B2A6;font-size:12px">—</span>
                        @endif
                    </td>
                    <td style="font-size:12.5px;color:#4D4944">{{ $opp->assignedUser?->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="{{ route('crm.opportunities.show', $opp->lead_id ?? $opp->id) }}" class="btn btn-outline btn-xs">Lihat</a>
                            @if(!in_array($opp->stage, ['Won','Lost']))
                            <form method="POST" action="{{ route('crm.opportunities.won', $opp->lead_id ?? $opp->id) }}" onsubmit="return confirm('Tandai peluang ini sebagai Berhasil (Won)?')">
                                @csrf
                                <button type="submit" class="btn btn-xs" style="background:#ECFDF5;color:#065F46;border-color:#A7F3D0">Won</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="empty-title">Belum ada peluang penjualan</div>
            <div class="empty-text">Konversikan prospek yang ada atau buat peluang penjualan baru langsung.</div>
            <a href="{{ route('crm.opportunities.create') }}" class="btn btn-primary btn-sm">Tambah Peluang Baru</a>
        </div>
        @endif
    </div>
    @if($opportunities->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $opportunities->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
