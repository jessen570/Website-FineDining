@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">CRM</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item"><a href="{{ route('crm.leads.index') }}">Prospek</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $lead->lead_id }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="page-title">{{ $lead->name }}</div>
            @php
                $statusMap = ['New'=>'b-gray','Contacted'=>'b-blue','Qualified'=>'b-green','Unqualified'=>'b-red'];
                $statusLabel = ['New'=>'Baru','Contacted'=>'Dihubungi','Qualified'=>'Terkualifikasi','Unqualified'=>'Tidak Memenuhi Syarat'];
                $stageMap  = ['Lead'=>'b-gray','Qualified'=>'b-blue','Proposition'=>'b-gold','Negotiation'=>'b-purple','Won'=>'b-green','Lost'=>'b-red'];
                $stageLabel = ['Lead'=>'Prospek','Qualified'=>'Terkualifikasi','Proposition'=>'Penawaran','Negotiation'=>'Negosiasi','Won'=>'Berhasil Menang','Lost'=>'Gagal'];
            @endphp
            <span class="badge {{ $statusMap[$lead->lead_status] ?? 'b-gray' }}">{{ $statusLabel[$lead->lead_status] ?? $lead->lead_status }}</span>
            <span class="badge {{ $stageMap[$lead->stage] ?? 'b-gray' }}">{{ $stageLabel[$lead->stage] ?? $lead->stage }}</span>
            @if($lead->is_converted)
            <span class="badge b-green">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Terkonversi ke Peluang
            </span>
            @endif
        </div>
        <div class="page-desc" style="margin-top:4px">Nomor ID: {{ $lead->lead_id }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('crm.leads.edit', $lead->lead_id ?? $lead->id) }}" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Ubah
        </a>
        @if(!$lead->is_converted)
        <form method="POST" action="{{ route('crm.leads.convert', $lead->lead_id ?? $lead->id) }}" onsubmit="return confirm('Konversi prospek ini menjadi peluang penjualan?')">
            @csrf
            <button type="submit" class="btn btn-gold btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Konversi ke Peluang
            </button>
        </form>
        @endif
        <form method="POST" action="{{ route('crm.leads.destroy', $lead->lead_id ?? $lead->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus prospek ini? Tindakan ini tidak dapat dibatalkan.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Hapus
            </button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    {{-- Sisi Kiri --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        {{-- Detail Prospek --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Detail Prospek</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 24px">
                    @php
                    $fields = [
                        'ID Prospek'        => $lead->lead_id,
                        'Nama Prospek'      => $lead->name,
                        'Perusahaan'        => $lead->company_name,
                        'Kontak Person'     => $lead->contact_person,
                        'Email'             => $lead->email,
                        'Nomor Telepon'     => $lead->phone,
                        'Sumber Prospek'    => $lead->lead_source,
                        'Status Prospek'    => $statusLabel[$lead->lead_status] ?? $lead->lead_status,
                        'Tahapan Pipeline'  => $stageLabel[$lead->stage] ?? $lead->stage,
                        'Ditugaskan Kepada' => $lead->assignedUser?->name ?? '—',
                    ];
                    @endphp
                    @foreach($fields as $label => $value)
                    <div>
                        <div class="section-title" style="margin-bottom:2px">{{ $label }}</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $value ?: '—' }}</div>
                    </div>
                    @endforeach

                    <div>
                        <div class="section-title" style="margin-bottom:2px">Estimasi Pendapatan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">
                            @if($lead->expected_revenue)
                            Rp {{ number_format($lead->expected_revenue, 0, ',', '.') }}
                            @else —
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="section-title" style="margin-bottom:2px">Probabilitas Kemenangan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $lead->probability !== null ? $lead->probability.'%' : '—' }}</div>
                    </div>

                    <div>
                        <div class="section-title" style="margin-bottom:2px">Target Penutupan</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ $lead->closing_date ? \Carbon\Carbon::parse($lead->closing_date)->translatedFormat('d F Y') : '—' }}</div>
                    </div>

                    <div>
                        <div class="section-title" style="margin-bottom:2px">Tanggal Dibuat</div>
                        <div style="font-size:13.5px;color:#1E1C1A">{{ \Carbon\Carbon::parse($lead->created_at)->translatedFormat('d F Y, H:i') }}</div>
                    </div>

                    @if($lead->notes)
                    <div style="grid-column:span 2">
                        <div class="section-title" style="margin-bottom:2px">Catatan Tambahan</div>
                        <div style="font-size:13px;color:#4D4944;line-height:1.6;white-space:pre-line">{{ $lead->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Dokumen Terkait --}}
        @if($lead->is_converted)
        <div class="card">
            <div class="card-header">
                <span class="card-title">Dokumen Terkait</span>
            </div>
            <div class="card-body">
                @if($lead->linkedSalesOrder || $lead->linkedQuotation)
                <div style="display:flex;flex-direction:column;gap:10px">
                    @if($lead->linkedSalesOrder)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">Pesanan Penjualan (SO)</div>
                            <a href="{{ route('sales.orders.show', $lead->linkedSalesOrder->order_id ?? $lead->linkedSalesOrder->id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $lead->linkedSalesOrder->order_id }}</a>
                        </div>
                        <div style="text-align:right">
                            <span class="badge b-blue">{{ $lead->linkedSalesOrder->status }}</span>
                            <div style="font-size:12.5px;color:#4D4944;margin-top:4px">Rp {{ number_format($lead->linkedSalesOrder->grand_total ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    @endif
                    @if($lead->linkedQuotation)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F7F5F0;border-radius:8px">
                        <div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8A8478;margin-bottom:3px">Surat Penawaran</div>
                            <a href="{{ route('sales.quotations.show', $lead->linkedQuotation->quotation_id ?? $lead->linkedQuotation->id) }}" style="font-size:13.5px;font-weight:600;color:#18382E;text-decoration:none">{{ $lead->linkedQuotation->quotation_id }}</a>
                        </div>
                        <div style="text-align:right">
                            <span class="badge b-gold">{{ $lead->linkedQuotation->status }}</span>
                            <div style="font-size:12.5px;color:#4D4944;margin-top:4px">Rp {{ number_format($lead->linkedQuotation->gross_amount ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div style="font-size:12.5px;color:#8A8478">Belum ada dokumen yang terhubung.</div>
                @endif
            </div>
        </div>
        @endif

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
                            @if($log->payload)
                            <details style="margin-top:6px">
                                <summary style="font-size:11.5px;color:#C6A76B;cursor:pointer;outline:none">Tampilkan rincian data</summary>
                                <pre style="font-size:11px;background:#F7F5F0;padding:8px;border-radius:6px;margin-top:6px;overflow-x:auto;color:#4D4944">{{ json_encode(json_decode($log->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
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
        {{-- Tahapan Pipeline --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Tahapan Pipeline</span>
            </div>
            <div class="card-body">
                @php
                $allStages = ['Lead','Qualified','Proposition','Negotiation','Won','Lost'];
                $currentIdx = array_search($lead->stage, $allStages);
                @endphp
                <div style="display:flex;flex-direction:column;gap:0">
                    @foreach($allStages as $idx => $stageName)
                    @php
                        $isDone    = $idx < $currentIdx;
                        $isCurrent = $stageName === $lead->stage;
                    @endphp
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid #F7F5F0' : '' }}">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:0;width:20px;flex-shrink:0">
                            <div style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                {{ $isDone ? 'background:#059669;' : ($isCurrent ? 'background:#C6A76B;border:2px solid #C6A76B;' : 'background:#F0EEE8;border:2px solid #E8E4DA;') }}">
                                @if($isDone)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                @elseif($isCurrent)
                                <div style="width:6px;height:6px;border-radius:50%;background:white"></div>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:{{ $isCurrent ? '700' : '500' }};color:{{ $isCurrent ? '#C6A76B' : ($isDone ? '#059669' : '#8A8478') }}">{{ $stageLabel[$stageName] ?? $stageName }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tindakan Cepat --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Tindakan Cepat</span>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:8px">
                    @if($lead->customer_id)
                    <a href="{{ route('sales.customers.show', $lead->customer_id) }}" class="btn btn-outline btn-sm" style="justify-content:flex-start">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Lihat Data Pelanggan
                    </a>
                    @endif
                    <a href="{{ route('sales.quotations.create', ['lead_id' => $lead->lead_id ?? $lead->id]) }}" class="btn btn-outline btn-sm" style="justify-content:flex-start">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Buat Surat Penawaran
                    </a>
                    @if(!$lead->is_converted)
                    <form method="POST" action="{{ route('crm.leads.convert', $lead->lead_id ?? $lead->id) }}" onsubmit="return confirm('Konversi prospek ini menjadi peluang penjualan?')">
                        @csrf
                        <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Konversi ke Peluang
                        </button>
                    </form>
                    @else
                    <div style="padding:10px 12px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:7px;font-size:12.5px;color:#065F46;font-weight:600">
                        ✓ Prospek ini telah dikonversi ke peluang.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
