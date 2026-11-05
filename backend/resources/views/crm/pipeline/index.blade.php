@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Pipeline</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px">
            <div class="page-title">Sales Pipeline Restoran</div>
            <span class="badge b-gold" style="font-size:12px;padding:4px 12px">
                Rp {{ number_format($grandTotal/1000000, 1, ',', '.') }} jt total
            </span>
        </div>
        <div class="page-desc">Ringkasan visual alur proses kesepakatan dan peluang penjualan.</div>
    </div>
    <a href="{{ route('crm.opportunities.create') }}" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Peluang
    </a>
</div>

{{-- Kanban Board --}}
@php
$stageColors = [
    'Lead'        => '#6B7280',
    'Qualified'   => '#1E40AF',
    'Proposition' => '#D97706',
    'Negotiation' => '#7C3AED',
    'Won'         => '#059669',
    'Lost'        => '#DC2626',
];
$stageLabel = [
    'Lead'        => 'Prospek',
    'Qualified'   => 'Terkualifikasi',
    'Proposition' => 'Penawaran',
    'Negotiation' => 'Negosiasi',
    'Won'         => 'Menang (Won)',
    'Lost'        => 'Gagal (Lost)',
];
$stageBgs = [
    'Won'  => '#F0FDF4',
    'Lost' => '#FFF5F5',
];
@endphp

<div style="overflow-x:auto;margin-bottom:24px">
    <div style="display:flex;gap:14px;min-width:max-content;padding-bottom:16px">
        @foreach($stages as $stage)
        @php
            $stageOpps  = $kanban[$stage] ?? collect();
            $stageTotalArr = $stageTotals[$stage] ?? ['count' => 0, 'revenue' => 0];
            $stageTotal = is_array($stageTotalArr) ? ($stageTotalArr['revenue'] ?? 0) : (float)$stageTotalArr;
            $color      = $stageColors[$stage] ?? '#6B7280';
            $bgCol      = $stageBgs[$stage] ?? '#FAFAF9';
        @endphp
        <div style="width:280px;flex-shrink:0">
            {{-- Column Header --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;padding:0 2px">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $color }};flex-shrink:0"></div>
                <div style="font-size:12.5px;font-weight:700;color:#343130;flex:1">{{ $stageLabel[$stage] ?? $stage }}</div>
                <span class="badge b-gray" style="font-size:10.5px">{{ $stageOpps->count() }}</span>
                <div style="font-size:11px;color:#8A8478;margin-left:4px">Rp {{ number_format($stageTotal/1000000, 1, ',', '.') }} jt</div>
            </div>

            {{-- Cards --}}
            <div style="display:flex;flex-direction:column;gap:8px;padding:10px;background:{{ $bgCol }};border-radius:10px;min-height:200px;{{ $stageOpps->count() === 0 ? 'border:2px dashed #E8E4DA;' : 'border:1px solid #E8E4DA;' }}">
                @if($stageOpps->count())
                @foreach($stageOpps as $opp)
                @php
                    $prob = (int)($opp->probability ?? 0);
                    $probColor = $prob >= 70 ? '#059669' : ($prob >= 40 ? '#D97706' : '#DC2626');
                    $closing = $opp->closing_date ? \Carbon\Carbon::parse($opp->closing_date) : null;
                    $closingColor = '#8A8478';
                    if($closing && $closing->isPast()) $closingColor = '#DC2626';
                    elseif($closing && $closing->diffInDays(now()) <= 7) $closingColor = '#D97706';
                    $initials = strtoupper(substr($opp->assignedUser?->name ?? '?', 0, 2));
                @endphp
                <div style="background:white;border:1px solid #E8E4DA;border-radius:8px;padding:12px 14px;cursor:pointer;transition:box-shadow 0.12s"
                    onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';this.style.borderColor='#C6A76B'"
                    onmouseout="this.style.boxShadow='none';this.style.borderColor='#E8E4DA'">
                    {{-- Row 1: Name --}}
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px">
                        <a href="{{ route('crm.opportunities.show', $opp->lead_id ?? $opp->id) }}" style="font-size:13px;font-weight:600;color:#1E1C1A;text-decoration:none;line-height:1.4;flex:1">{{ $opp->name }}</a>
                    </div>
                    {{-- Row 2: Customer/Company --}}
                    @if($opp->customer?->name || $opp->company_name)
                    <div style="font-size:11.5px;color:#8A8478;margin-bottom:8px">{{ $opp->customer?->name ?? $opp->company_name }}</div>
                    @endif
                    {{-- Row 3: Revenue + Probability --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <div style="font-size:13px;font-weight:700;color:#1E1C1A">Rp {{ number_format($opp->expected_revenue ?? 0, 0, ',', '.') }}</div>
                        <div style="font-size:11.5px;font-weight:700;color:{{ $probColor }}">{{ $prob }}%</div>
                    </div>
                    {{-- Row 4: Closing date + Avatar --}}
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        @if($closing)
                        <div style="display:flex;align-items:center;gap:4px">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="{{ $closingColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span style="font-size:11px;color:{{ $closingColor }};font-weight:{{ $closingColor !== '#8A8478' ? '600' : '400' }}">{{ $closing->format('d M') }}</span>
                        </div>
                        @else
                        <span></span>
                        @endif
                        @if($opp->assignedUser)
                        <div style="width:22px;height:22px;border-radius:50%;background:#18382E;display:flex;align-items:center;justify-content:center">
                            <span style="font-size:9px;font-weight:700;color:white">{{ $initials }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                @else
                <div style="display:flex;align-items:center;justify-content:center;height:80px">
                    <span style="font-size:12px;color:#B8B2A6">Tidak ada peluang di tahap ini</span>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Ringkasan Pipeline --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Ringkasan Pipeline</span>
        <span style="font-size:12px;color:#8A8478">Ikhtisar semua tahapan</span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tahapan</th>
                    <th style="text-align:center">Jumlah Peluang</th>
                    <th style="text-align:right">Total Nilai</th>
                    <th style="text-align:right">Rata-rata Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stages as $stage)
                @php
                    $stageOpps  = $kanban[$stage] ?? collect();
                    $stageTotalArr = $stageTotals[$stage] ?? ['count' => 0, 'revenue' => 0];
                    $stageTotal = is_array($stageTotalArr) ? ($stageTotalArr['revenue'] ?? 0) : (float)$stageTotalArr;
                    $count      = $stageOpps->count();
                    $avg        = $count > 0 ? $stageTotal / $count : 0;
                    $stageMap   = ['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red'];
                @endphp
                <tr>
                    <td><span class="badge {{ $stageMap[$stage] ?? 'b-gray' }}">{{ $stageLabel[$stage] ?? $stage }}</span></td>
                    <td style="text-align:center;font-weight:700;color:#343130">{{ $count }}</td>
                    <td style="text-align:right;font-weight:700;color:#1E1C1A">Rp {{ number_format($stageTotal, 0, ',', '.') }}</td>
                    <td style="text-align:right;color:#6B6560">Rp {{ number_format($avg, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#FAFAF9;border-top:2px solid #E8E4DA">
                    <td style="font-weight:700;font-size:12.5px;color:#343130;padding:12px 16px">Total Keseluruhan</td>
                    <td style="text-align:center;font-weight:700;color:#343130;padding:12px 16px">{{ collect($kanban)->flatten()->count() }}</td>
                    <td style="text-align:right;font-weight:700;font-size:14px;color:#18382E;padding:12px 16px">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    <td style="padding:12px 16px"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
