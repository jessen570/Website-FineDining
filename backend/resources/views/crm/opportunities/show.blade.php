@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.opportunities.index') }}">Peluang</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $opp->opportunity_id ?? $opp->lead_id ?? $opp->id }}</span>
@endsection

@section('content')
@php
    $stageMap   = ['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red'];
    $stageLabel = ['Lead'=>'Prospek','Qualified'=>'Terkualifikasi','Proposition'=>'Penawaran','Negotiation'=>'Negosiasi','Won'=>'Berhasil Menang','Lost'=>'Gagal'];
    $oppIdentifier = $opp->opportunity_id ?? $opp->lead_id ?? $opp->id;
@endphp
<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $opp->name }}</div>
            <span class="badge {{ $stageMap[$opp->stage] ?? 'b-gray' }}">{{ $stageLabel[$opp->stage] ?? $opp->stage }}</span>
        </div>
        <div class="page-desc" style="margin-top:4px">Nomor Peluang: {{ $oppIdentifier }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('crm.opportunities.edit', $oppIdentifier) }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Ubah
        </a>
        @if($opp->stage !== 'Won')
        <form method="POST" action="{{ route('crm.opportunities.won', $oppIdentifier) }}" onsubmit="return confirm('Tandai peluang ini sebagai Berhasil Menang (Won)?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Tandai Menang
            </button>
        </form>
        @endif
        @if($opp->stage !== 'Lost')
        <form method="POST" action="{{ route('crm.opportunities.lost', $oppIdentifier) }}" onsubmit="return confirm('Tandai peluang ini sebagai Gagal (Lost)?')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Tandai Gagal
            </button>
        </form>
        @endif
        <a href="{{ route('crm.opportunities.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Sisi Kiri --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Detail Peluang --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Detail Peluang</span></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    <div>
                        <div class="section-title" style="margin-bottom:2px">ID Peluang</div>
                        <div style="font-size:13.5px;color:#1E1C1A;font-family:monospace">{{ $oppIdentifier }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Nama Peluang</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $opp->name }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Perusahaan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $opp->company_name ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Pelanggan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">
                            @if($opp->customer)
                            <a href="{{ route('sales.customers.show', $opp->customer_id) }}" style="color:#18382E;text-decoration:none;font-weight:600">{{ $opp->customer->name }}</a>
                            @else — @endif
                        </div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Tahapan</div>
                        <span class="badge {{ $stageMap[$opp->stage] ?? 'b-gray' }}">{{ $stageLabel[$opp->stage] ?? $opp->stage }}</span>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Estimasi Nilai</div>
                        <div style="font-size:13.5px;font-weight:700;color:#1E1C1A">Rp {{ number_format($opp->expected_revenue ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Probabilitas</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $opp->probability !== null ? $opp->probability.'%' : '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Target Penutupan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $opp->closing_date ? \Carbon\Carbon::parse($opp->closing_date)->translatedFormat('d F Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Penanggung Jawab</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $opp->assignedUser?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="section-title" style="margin-bottom:2px">Tanggal Dibuat</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($opp->created_at)->translatedFormat('d F Y, H:i') }}</div>
                    </div>
                    @if($opp->notes)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Catatan Tambahan</div>
                        <div style="font-size:13px;color:#4D4944;line-height:1.6;white-space:pre-line">{{ $opp->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Dokumen Terkait --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Dokumen Terkait</span>
                <a href="{{ route('sales.quotations.create', ['lead_id' => $oppIdentifier]) }}" class="btn btn-outline btn-xs">+ Buat Penawaran</a>
            </div>
            <div class="card-body">
                @if($opp->linkedQuotation || $opp->linkedSalesOrder)
                <div style="display:flex;flex-direction:column;gap:10px">
                    @if($opp->linkedQuotation)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">Surat Penawaran</div>
                            <a href="{{ route('sales.quotations.show', $opp->linkedQuotation->quotation_id ?? $opp->linkedQuotation->id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $opp->linkedQuotation->quotation_id }}</a>
                        </div>
                        <div style="text-align:right">
                            <span class="badge b-gold">{{ $opp->linkedQuotation->status }}</span>
                            <div style="font-size:12.5px;color:#4D4944;margin-top:4px">Rp {{ number_format($opp->linkedQuotation->gross_amount ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    @endif
                    @if($opp->linkedSalesOrder)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">Pesanan Penjualan (SO)</div>
                            <a href="{{ route('sales.orders.show', $opp->linkedSalesOrder->order_id ?? $opp->linkedSalesOrder->id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $opp->linkedSalesOrder->order_id }}</a>
                        </div>
                        <div style="text-align:right">
                            <span class="badge b-blue">{{ $opp->linkedSalesOrder->status }}</span>
                            <div style="font-size:12.5px;color:#4D4944;margin-top:4px">Rp {{ number_format($opp->linkedSalesOrder->grand_total ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div style="font-size:12.5px;color:#8A8478">Belum ada dokumen yang terhubung.</div>
                @endif
            </div>
        </div>

        {{-- Log Riwayat Aktivitas --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Log Riwayat Aktivitas</span>
                <span style="font-size:11.5px;color:#8A8478">{{ count($auditLogs) }} entri</span>
            </div>
            <div class="card-body">
                @if(count($auditLogs))
                <div style="display:flex;flex-direction:column;gap:12px">
                    @foreach($auditLogs as $log)
                    <div style="display:flex;gap:12px;align-items:flex-start">
                        <div style="width:28px;height:28px;border-radius:50%;background:#F0EEE8;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="badge b-blue" style="font-size:10px">{{ $log->action }}</span>
                                <span style="font-size:12.5px;font-weight:600;color:#343130">{{ $log->user?->name ?? 'Sistem' }}</span>
                                <span style="font-size:11.5px;color:#B8B2A6">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                            @if($log->description)
                            <div style="font-size:12px;color:#6B6560;margin-top:3px">{{ $log->description }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="font-size:12.5px;color:#8A8478">Belum ada aktivitas yang tercatat.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sisi Kanan --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        {{-- Progres Tahapan --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Progres Tahapan</span></div>
            <div class="card-body">
                @php
                    $allStages = ['Lead','Qualified','Proposition','Negotiation'];
                    $currentIdx = array_search($opp->stage, $allStages);
                    $isWon  = $opp->stage === 'Won';
                    $isLost = $opp->stage === 'Lost';
                @endphp
                <div style="display:flex;flex-direction:column;gap:0">
                    @foreach($allStages as $idx => $stageName)
                    @php
                        $isDone    = $isWon || ($idx < $currentIdx && !$isLost);
                        $isCurrent = !$isWon && !$isLost && ($stageName === $opp->stage);
                    @endphp
                    <div style="display:flex;align-items:center;gap:12px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid #F7F5F0' : '' }}">
                        <div style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                            {{ $isDone ? 'background:#059669;' : ($isCurrent ? 'background:#C6A76B;' : 'background:#F0EEE8;border:2px solid #E8E4DA;') }}">
                            @if($isDone)
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            @elseif($isCurrent)
                            <div style="width:6px;height:6px;border-radius:50%;background:white"></div>
                            @endif
                        </div>
                        <div style="font-size:13px;font-weight:{{ $isCurrent ? '700' : '500' }};color:{{ $isCurrent ? '#C6A76B' : ($isDone ? '#059669' : '#8A8478') }}">{{ $stageLabel[$stageName] ?? $stageName }}</div>
                    </div>
                    @endforeach
                    {{-- Won / Lost --}}
                    <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-top:2px solid #E8E4DA;margin-top:4px">
                        <div style="width:18px;height:18px;border-radius:50%;background:{{ $isWon ? '#059669' : '#F0EEE8' }};border:{{ !$isWon ? '2px solid #E8E4DA' : 'none' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            @if($isWon)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>@endif
                        </div>
                        <div style="font-size:13px;font-weight:{{ $isWon ? '700' : '400' }};color:{{ $isWon ? '#059669' : '#B8B2A6' }}">Berhasil Menang (Won)</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:8px 0">
                        <div style="width:18px;height:18px;border-radius:50%;background:{{ $isLost ? '#DC2626' : '#F0EEE8' }};border:{{ !$isLost ? '2px solid #E8E4DA' : 'none' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            @if($isLost)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>@endif
                        </div>
                        <div style="font-size:13px;font-weight:{{ $isLost ? '700' : '400' }};color:{{ $isLost ? '#DC2626' : '#B8B2A6' }}">Gagal (Lost)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tindakan Cepat --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Tindakan</span></div>
            <div class="card-body">
                {{-- Update Stage --}}
                <form method="POST" action="{{ route('crm.opportunities.stage', $oppIdentifier) }}" style="margin-bottom:14px">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label class="form-label">Ubah Tahapan</label>
                        <select name="stage" class="form-input" style="appearance:none">
                            @foreach($stages as $stage)
                            <option value="{{ $stage }}" {{ $opp->stage === $stage ? 'selected' : '' }}>{{ $stageLabel[$stage] ?? $stage }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center">Perbarui Tahapan</button>
                </form>
                <div style="border-top:1px solid #F0EEE8;padding-top:14px">
                    <a href="{{ route('sales.quotations.create', ['lead_id' => $oppIdentifier]) }}" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                        Buat Surat Penawaran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
