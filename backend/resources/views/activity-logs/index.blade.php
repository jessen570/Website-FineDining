@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem — Maison Aster ERP')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">Manajemen</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">Log Aktivitas</span>
@endsection

@section('content')
<x-page-header
    title="Log Aktivitas Pengguna"
    description="Pantau seluruh aktivitas pengguna: membuka halaman, menambah data, mengubah data, hingga percobaan akses."
>
    <x-slot:badge>
        <span class="badge b-forest">Khusus Owner</span>
    </x-slot:badge>
</x-page-header>

{{-- KPI Cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <x-kpi-card
        title="Total Aktivitas"
        :value="number_format($summary['total'] ?? 0)"
        sub="Seluruh jejak audit tersimpan"
        iconBg="#F7F5F0"
        iconColor="#18382E"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Aktivitas Hari Ini"
        :value="number_format($summary['today'] ?? 0)"
        sub="{{ now()->locale('id')->translatedFormat('d M Y') }}"
        iconBg="#EFF6FF"
        iconColor="#1E40AF"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Perubahan Data"
        :value="number_format($summary['mutations'] ?? 0)"
        sub="Tambah, ubah, konfirmasi, validasi"
        iconBg="#ECFDF5"
        iconColor="#059669"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Buka & Keamanan"
        :value="number_format($summary['views'] ?? 0) . ' / ' . number_format($summary['security'] ?? 0)"
        sub="Buka halaman (VIEW) & blokir akses"
        iconBg="#FEF2F2"
        iconColor="#DC2626"
    >
        <x-slot:icon>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

{{-- Ringkasan Pengguna Aktif --}}
@if($actors->count())
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">Aktivitas per Pengguna (Klik untuk Filter)</span>
        @if(request('actor'))
        <a href="{{ route('activity-logs.index', request()->except('actor', 'page')) }}" style="font-size:12px;color:#18382E;font-weight:600;text-decoration:none">Tampilkan Semua Pengguna</a>
        @endif
    </div>
    <div class="card-body" style="padding:14px 16px">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px">
            @foreach($actors as $act)
            @php
                $isSelected = request('actor') === $act->actor_name;
                $initials   = strtoupper(substr($act->actor_name ?? 'S', 0, 2));
                $url        = route('activity-logs.index', array_merge(request()->except('page'), ['actor' => $isSelected ? null : $act->actor_name]));
            @endphp
            <a href="{{ $url }}"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;border:1px solid {{ $isSelected ? '#18382E' : '#E8E4DA' }};background:{{ $isSelected ? '#18382E' : '#FAFAF9' }};color:{{ $isSelected ? '#FFFFFF' : '#1E1C1A' }};transition:all 0.15s;min-width:0">
                <span style="width:28px;height:28px;border-radius:50%;background:{{ $isSelected ? '#C6A76B' : '#18382E' }};color:{{ $isSelected ? '#18382E' : '#FFFFFF' }};font-size:10.5px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    {{ $initials }}
                </span>
                <div style="line-height:1.3;min-width:0;flex:1">
                    <div style="font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $act->actor_name }}</div>
                    <div style="font-size:11px;opacity:0.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        {{ ucfirst($act->actor_role) }} · {{ $act->total_actions }} aksi · {{ \Carbon\Carbon::parse($act->last_active)->locale('id')->diffForHumans() }}
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Filter Toolbar --}}
<form method="GET" action="{{ route('activity-logs.index') }}">
    <div class="card" style="padding:12px 16px;margin-bottom:16px">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:10px;margin-bottom:10px">
            <div class="toolbar-search" style="width:100%">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" placeholder="Cari pengguna, ID dokumen, atau keterangan..." value="{{ request('search') }}">
            </div>

            <select name="category" class="toolbar-select" style="width:100%">
                <option value="">Semua Jenis Aktivitas</option>
                <option value="view"     {{ request('category') === 'view'     ? 'selected' : '' }}>Membuka / Melihat (View)</option>
                <option value="create"   {{ request('category') === 'create'   ? 'selected' : '' }}>Menambah Data (Create)</option>
                <option value="update"   {{ request('category') === 'update'   ? 'selected' : '' }}>Mengubah / Proses Data</option>
                <option value="delete"   {{ request('category') === 'delete'   ? 'selected' : '' }}>Hapus / Batal</option>
                <option value="session"  {{ request('category') === 'session'  ? 'selected' : '' }}>Login / Logout</option>
                <option value="security" {{ request('category') === 'security' ? 'selected' : '' }}>Peringatan Keamanan</option>
            </select>

            <select name="actor" class="toolbar-select" style="width:100%">
                <option value="">Semua Pengguna</option>
                @foreach($actors as $act)
                <option value="{{ $act->actor_name }}" {{ request('actor') === $act->actor_name ? 'selected' : '' }}>
                    {{ $act->actor_name }} ({{ $act->actor_role }})
                </option>
                @endforeach
            </select>

            <select name="doc_type" class="toolbar-select" style="width:100%">
                <option value="">Semua Modul</option>
                @foreach($docTypes as $dt)
                <option value="{{ $dt }}" {{ request('doc_type') === $dt ? 'selected' : '' }}>{{ $dt }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-size:12px;color:#6B6560;font-weight:500">Rentang Tanggal:</span>
                <input type="date" name="date_from" class="toolbar-select" value="{{ request('date_from') }}" title="Dari tanggal">
                <span style="font-size:12px;color:#8A8478">s/d</span>
                <input type="date" name="date_to" class="toolbar-select" value="{{ request('date_to') }}" title="Sampai tanggal">
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

{{-- Activity Table --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($logs->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:165px">Waktu</th>
                    <th style="width:175px">Pengguna &amp; Peran</th>
                    <th style="width:165px">Aksi</th>
                    <th style="width:175px">Modul / Dokumen</th>
                    <th>Keterangan &amp; Perubahan Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $actionClass = match (true) {
                        $log->action === 'VIEW' => 'b-blue',
                        in_array($log->action, ['CREATE', 'APPROVE_DISCOUNT', 'CONFIRM', 'MARK_WON']) => 'b-green',
                        in_array($log->action, ['LOGIN', 'LOGOUT']) => 'b-purple',
                        in_array($log->action, ['DELETE', 'CANCEL', 'REJECT_DISCOUNT', 'MARK_LOST', 'ACCESS_DENIED', 'ATTEMPT_PRICE_EDIT', 'CREDIT_BLOCK']) => 'b-red',
                        default => 'b-amber',
                    };
                    $actionLabel = match ($log->action) {
                        'VIEW'                   => 'Membuka Halaman',
                        'CREATE'                 => 'Tambah Data',
                        'UPDATE'                 => 'Ubah Data',
                        'DELETE'                 => 'Hapus Data',
                        'CONFIRM'                => 'Konfirmasi',
                        'CANCEL'                 => 'Batalkan',
                        'VALIDATE'               => 'Validasi',
                        'POST'                   => 'Posting',
                        'PAYMENT'                => 'Pembayaran',
                        'APPROVE_DISCOUNT'       => 'Setujui Diskon',
                        'REJECT_DISCOUNT'        => 'Tolak Diskon',
                        'CONVERT_TO_OPPORTUNITY' => 'Konversi Peluang',
                        'CONVERT_TO_SO'          => 'Konversi ke SO',
                        'ACCESS_DENIED'          => 'Akses Ditolak',
                        'ATTEMPT_PRICE_EDIT'     => 'Edit Terkunci',
                        'CREDIT_BLOCK'           => 'Blokir Kredit',
                        default                  => $log->action,
                    };
                    $initials = strtoupper(substr($log->actor_name ?? 'S', 0, 2));
                @endphp
                <tr>
                    <td>
                        <div style="font-size:12.5px;font-weight:600;color:#1E1C1A;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($log->created_at)->locale('id')->translatedFormat('d M Y') }}
                        </div>
                        <div style="font-size:11px;color:#8A8478;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }} · {{ \Carbon\Carbon::parse($log->created_at)->locale('id')->diffForHumans() }}
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;border-radius:50%;background:#18382E;color:#C6A76B;font-size:10.5px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                {{ $initials }}
                            </div>
                            <div>
                                <div style="font-size:12.5px;font-weight:600;color:#1E1C1A">{{ $log->actor_name }}</div>
                                <div style="font-size:10.5px;color:#8A8478;text-transform:uppercase;letter-spacing:0.04em">{{ $log->actor_role }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $actionClass }}" style="white-space:nowrap">{{ $actionLabel }}</span>
                    </td>
                    <td>
                        <div style="font-size:12px;font-weight:600;color:#343130">{{ $log->document_type }}</div>
                        <div style="font-family:monospace;font-size:11.5px;color:#6B6560">{{ $log->document_id }}</div>
                        @if($log->reference_code)
                        <div style="font-family:monospace;font-size:10.5px;color:#8A8478">Ref: {{ $log->reference_code }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:12.5px;color:#343130;line-height:1.45">{{ $log->message ?: '—' }}</div>
                        @if($log->before_payload || $log->after_payload)
                        <details style="margin-top:6px">
                            <summary style="font-size:11.5px;color:#A88C58;font-weight:600;cursor:pointer;outline:none">
                                Lihat rincian perubahan data
                            </summary>
                            <div style="display:grid;grid-template-columns:{{ ($log->before_payload && $log->after_payload) ? '1fr 1fr' : '1fr' }};gap:8px;margin-top:6px">
                                @if($log->before_payload)
                                <div>
                                    <div style="font-size:10px;font-weight:700;color:#991B1B;text-transform:uppercase;margin-bottom:2px">Sebelum</div>
                                    <pre style="font-size:11px;background:#FEF2F2;padding:8px;border-radius:6px;margin:0;overflow-x:auto;color:#7F1D1D">{{ json_encode($log->before_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                                @if($log->after_payload)
                                <div>
                                    <div style="font-size:10px;font-weight:700;color:#065F46;text-transform:uppercase;margin-bottom:2px">Sesudah / Data Baru</div>
                                    <pre style="font-size:11px;background:#ECFDF5;padding:8px;border-radius:6px;margin:0;overflow-x:auto;color:#064E3B">{{ json_encode($log->after_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                            </div>
                        </details>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <x-empty-state
            title="Belum ada log aktivitas"
            message="Aktivitas pengguna saat membuka, menambah, atau mengubah data akan tampil secara otomatis di sini."
        />
        @endif
    </div>
    @if($logs->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #F0EEE8">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

