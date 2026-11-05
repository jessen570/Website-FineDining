@extends('layouts.app')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item">{{ $category ?? 'Modul Sistem' }}</span>
    <span class="breadcrumb-sep">›</span>
    <span class="breadcrumb-item current">{{ $title ?? 'Pengembangan' }}</span>
@endsection

@section('content')
<div style="max-width: 820px; margin: 40px auto 0 auto; text-align: center;">
    {{-- Card Container with subtle luxury Maison Aster styling --}}
    <div style="background: #FFFFFF; border: 1px solid #E8E4DA; border-radius: 16px; padding: 56px 40px; box-shadow: 0 4px 24px rgba(24, 56, 46, 0.05); position: relative; overflow: hidden;">
        
        {{-- Elegant subtle gold decorative bar at the top --}}
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #18382E 0%, #C6A76B 50%, #18382E 100%);"></div>

        {{-- Icon container --}}
        <div style="width: 76px; height: 76px; border-radius: 50%; background: #F7F5F0; border: 1.5px solid #E8E4DA; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#18382E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>

        {{-- Category Badge --}}
        <div style="margin-bottom: 16px;">
            <span class="badge" style="background: #FFFBEB; color: #78350F; border-color: #FDE68A; font-size: 11px; padding: 4px 14px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700;">
                {{ $category ?? 'Divisi Terkait' }}
            </span>
        </div>

        {{-- Main Heading --}}
        <h1 style="font-family: 'Playfair Display', Georgia, serif; font-size: 28px; font-weight: 700; color: #18382E; margin: 0 0 12px 0;">
            {{ $title ?? 'Modul ERP' }}
        </h1>

        {{-- Prominent Required Message --}}
        <div style="display: inline-block; background: rgba(198,167,107,0.12); border: 1px solid rgba(198,167,107,0.4); border-radius: 8px; padding: 10px 20px; margin-bottom: 20px;">
            <p style="font-size: 15.5px; font-weight: 700; color: #18382E; margin: 0; letter-spacing: 0.01em;">
                Modul ini sedang dalam tahap pengembangan.
            </p>
        </div>

        <p style="font-size: 13.5px; color: #6B6560; max-width: 520px; margin: 0 auto 36px auto; line-height: 1.65;">
            Halaman fungsional, formulir input, dan tabel transaksi untuk modul ini dialokasikan untuk anggota tim terkait pada tahap pengembangan selanjutnya.
        </p>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard') }}" class="btn btn-outline" style="padding: 9px 18px; font-size: 13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Kembali ke Dashboard
            </a>
            <a href="{{ route('crm.leads.index') }}" class="btn btn-primary" style="padding: 9px 18px; font-size: 13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Buka CRM & Prospek
            </a>
            <a href="{{ route('sales.orders.index') }}" class="btn btn-outline" style="padding: 9px 18px; font-size: 13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                Buka Pesanan Penjualan
            </a>
        </div>
    </div>
</div>
@endsection

