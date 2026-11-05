@extends('layouts.app')
@section('title', 'Dashboard — Maison Aster ERP')
@section('breadcrumb')<span class="breadcrumb-item current">Dashboard</span>@endsection

@section('content')

{{-- 1. Page Header & Greeting --}}
<div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 bg-gradient-to-r from-white via-white to-stone-50/80 p-4 sm:p-6 rounded-2xl border border-[#E8E4DA] shadow-xs">
    <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 min-w-0">
        <img src="{{ asset('images/logo-icon.jpg') }}" alt="Maison Aster" class="w-12 h-12 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl object-cover border border-[#C6A76B]/40 shadow-sm flex-shrink-0">
        <div class="min-w-0 flex-1">
            @php
                $hour = (int) now()->format('H');
                $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
                $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $dateIndo = $days[now()->dayOfWeek] . ', ' . now()->format('d') . ' ' . $months[now()->month] . ' ' . now()->format('Y');
            @endphp
            <div class="mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-[#18382E]/10 text-[#18382E] border border-[#18382E]/20 mb-1">
                    Ringkasan Eksekutif
                </span>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 tracking-tight leading-tight" style="font-family:'Playfair Display',serif">
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-600 flex flex-wrap items-center gap-1.5 sm:gap-2">
                <span>Ringkasan Kinerja Penjualan & CRM</span>
                <span class="text-gray-300 hidden sm:inline">•</span>
                <span class="text-gray-500 block sm:inline">{{ $dateIndo }}</span>
            </p>
        </div>
    </div>

    {{-- Quick Action Buttons --}}
    <div class="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-gray-100 w-full lg:w-auto">
        <a href="{{ route('crm.leads.create') }}" class="btn btn-outline justify-center text-xs sm:text-sm py-2 px-3 inline-flex items-center gap-1.5 rounded-lg font-medium shadow-xs hover:border-[#C6A76B] transition-colors">
            <i data-lucide="user-plus" class="w-4 h-4 text-[#18382E]"></i>
            <span>Tambah Lead</span>
        </a>
        <a href="{{ route('sales.quotations.create') }}" class="btn btn-outline justify-center text-xs sm:text-sm py-2 px-3 inline-flex items-center gap-1.5 rounded-lg font-medium shadow-xs hover:border-[#C6A76B] transition-colors">
            <i data-lucide="file-text" class="w-4 h-4 text-[#C6A76B]"></i>
            <span>Tambah Quotation</span>
        </a>
        <a href="{{ route('sales.orders.create') }}" class="btn btn-primary justify-center text-xs sm:text-sm py-2 px-3.5 inline-flex items-center gap-1.5 rounded-lg font-medium shadow-sm bg-[#18382E] hover:bg-[#244A3D] text-white transition-colors">
            <i data-lucide="plus-circle" class="w-4 h-4 text-[#C6A76B]"></i>
            <span>Tambah Sales Order</span>
        </a>
    </div>
</div>

{{-- 2. Five Primary KPI Cards --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-3.5">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Indikator Kinerja Utama (KPI)</h2>
        <span class="text-xs text-gray-400 hidden sm:inline">Pembaruan otomatis saat data berubah</span>
    </div>

    {{-- Responsive 5-Column Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-5">

        {{-- Card 1: Total Leads --}}
        <div class="bg-white rounded-xl border border-[#E8E4DA] p-5 sm:p-6 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Prospek</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1.5 tracking-tight">{{ number_format($totalLeads) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-emerald-700 font-semibold flex items-center gap-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                    {{ $activeLeads }} Aktif
                </span>
                <a href="{{ route('crm.leads.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-medium inline-flex items-center gap-0.5">
                    Detail <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        {{-- Card 2: Opportunities --}}
        <div class="bg-white rounded-xl border border-[#E8E4DA] p-5 sm:p-6 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peluang Aktif</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1.5 tracking-tight">{{ number_format($opportunities) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-blue-700 font-semibold flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-blue-600"></i>
                    {{ $wonOpps }} Berhasil (Won)
                </span>
                <a href="{{ route('crm.opportunities.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-medium inline-flex items-center gap-0.5">
                    Detail <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        {{-- Card 3: Sales Orders --}}
        <div class="bg-white rounded-xl border border-[#E8E4DA] p-5 sm:p-6 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pesanan Penjualan</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1.5 tracking-tight">{{ number_format($confirmedSO) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-green-50 text-green-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-600 font-medium">
                    Bln Ini: <strong class="text-green-800">Rp {{ number_format($totalSalesValue, 0, ',', '.') }}</strong>
                </span>
                <a href="{{ route('sales.orders.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-medium inline-flex items-center gap-0.5">
                    Detail <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        {{-- Card 4: Customers --}}
        <div class="bg-white rounded-xl border border-[#E8E4DA] p-5 sm:p-6 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Pelanggan</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1.5 tracking-tight">{{ number_format($totalCustomers) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                @if($overCreditLimit > 0)
                    <span class="text-red-600 font-semibold flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        {{ $overCreditLimit }} Melebihi Kredit
                    </span>
                @else
                    <span class="text-emerald-700 font-medium flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        Kredit Aman
                    </span>
                @endif
                <a href="{{ route('sales.customers.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-medium inline-flex items-center gap-0.5">
                    Detail <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        {{-- Card 5: Pipeline Value --}}
        <div class="bg-white rounded-xl border border-[#E8E4DA] p-5 sm:p-6 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nilai Pipeline</span>
                    <h3 class="text-2xl sm:text-3xl font-bold text-[#18382E] mt-1.5 tracking-tight">
                        Rp {{ number_format($pipelineValue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-[#18382E]/10 text-[#18382E] flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                    <i data-lucide="coins" class="w-5 h-5 text-[#C6A76B]"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-600 font-medium">
                    Konversi: <strong class="text-gray-900">{{ $conversionRate }}%</strong>
                </span>
                <a href="{{ route('crm.pipeline') }}" class="text-[#C6A76B] hover:text-[#96703A] font-medium inline-flex items-center gap-0.5">
                    Pipeline <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

    </div>
</div>

{{-- 3. Analytics Section: Sales Performance Chart & Pipeline Stages --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8 items-stretch">

    {{-- 3A: Sales Performance (Chart) --}}
    <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-xl border border-[#E8E4DA] shadow-xs flex flex-col justify-between overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-[#F0EEE8] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-gray-900">Kinerja Penjualan</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                        6 Bulan Terakhir
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Realisasi total nominal pesanan penjualan yang telah dikonfirmasi dan selesai</p>
            </div>
            <div class="text-left sm:text-right flex-shrink-0">
                <span class="text-2xs uppercase tracking-wider text-gray-400 block font-semibold">Total Pendapatan Kumulatif</span>
                <span class="text-lg font-bold text-[#18382E]">Rp {{ number_format($totalSalesAllTime, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="p-5 sm:p-6 flex-1 flex flex-col justify-center">
            <div class="relative w-full h-[260px] sm:h-[300px]">
                <canvas id="salesPerformanceChart"></canvas>
            </div>
        </div>

        <div class="px-5 py-3.5 bg-gray-50/70 border-t border-[#F0EEE8] flex flex-wrap items-center justify-between gap-3 text-xs text-gray-600">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-[#18382E]"></span>
                    Realisasi Penjualan Bulanan
                </span>
                <span class="flex items-center gap-1.5 text-gray-500">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    Termasuk PBJT 10%
                </span>
            </div>
            <a href="{{ route('sales.reports.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-semibold inline-flex items-center gap-1">
                Laporan Lengkap <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </div>

    {{-- 3B: Pipeline Stages Breakdown --}}
    <div class="lg:col-span-5 xl:col-span-4 bg-white rounded-xl border border-[#E8E4DA] shadow-xs flex flex-col justify-between overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-[#F0EEE8] flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Tahapan Pipeline</h2>
                <p class="text-xs text-gray-500 mt-0.5">Distribusi peluang aktif & estimasi nilai</p>
            </div>
            <a href="{{ route('crm.pipeline') }}" class="text-xs font-semibold text-[#C6A76B] hover:text-[#96703A] inline-flex items-center gap-1">
                Buka Pipeline <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="p-5 sm:p-6 flex-1 space-y-4">
            @php
                $stageTranslations = [
                    'Lead' => 'Prospek Baru (Lead)',
                    'Qualified' => 'Kualifikasi (Qualified)',
                    'Proposition' => 'Pengajuan Penawaran (Proposition)',
                    'Negotiation' => 'Negosiasi (Negotiation)',
                    'Won' => 'Berhasil (Won)',
                    'Lost' => 'Gagal (Lost)',
                ];
                $totalPipelineDeals = $pipelineChart->sum('count') ?: 1;
                $stageColors = [
                    'Lead' => 'bg-gray-400',
                    'Qualified' => 'bg-blue-500',
                    'Proposition' => 'bg-amber-500',
                    'Negotiation' => 'bg-purple-600',
                    'Won' => 'bg-emerald-600',
                    'Lost' => 'bg-rose-400',
                ];
            @endphp

            @foreach($pipelineChart as $item)
                @php
                    $stageName = $item['stage'];
                    $translatedName = $stageTranslations[$stageName] ?? $stageName;
                    $count = $item['count'];
                    $revenue = $item['revenue'];
                    $percentage = round(($count / $totalPipelineDeals) * 100);
                    $color = $stageColors[$stageName] ?? 'bg-[#C6A76B]';
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-semibold text-gray-800 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $color }}"></span>
                            {{ $translatedName }}
                        </span>
                        <div class="text-right">
                            <span class="font-bold text-gray-900">{{ $count }}</span>
                            <span class="text-gray-400 font-normal">({{ $percentage }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden flex">
                        <div class="{{ $color }} h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                    </div>
                    <div class="text-right mt-1">
                        <span class="text-[11px] text-gray-500">Nilai: <strong>Rp {{ number_format($revenue, 0, ',', '.') }}</strong></span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="px-5 py-3.5 bg-gray-50/70 border-t border-[#F0EEE8] flex items-center justify-between text-xs text-gray-600">
            <span>Total Peluang Terbuka: <strong class="text-gray-900">{{ $pipelineChart->whereNotIn('stage', ['Won','Lost'])->sum('count') }}</strong></span>
            <span class="font-bold text-[#18382E]">Rp {{ number_format($pipelineValue, 0, ',', '.') }}</span>
        </div>
    </div>

</div>

{{-- 4. Activity & Actions Section (3 Columns on Desktop, Responsive Stacking) --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    {{-- 4A: Recent Sales Orders --}}
    <div class="bg-white rounded-xl border border-[#E8E4DA] shadow-xs flex flex-col justify-between overflow-hidden">
        <div class="p-5 border-b border-[#F0EEE8] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-green-50 text-green-700 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Pesanan Penjualan Terbaru</h3>
                    <p class="text-2xs text-gray-500">Transaksi terakhir yang dicatat</p>
                </div>
            </div>
            <a href="{{ route('sales.orders.index') }}" class="text-xs font-semibold text-[#C6A76B] hover:text-[#96703A]">
                Lihat Semua →
            </a>
        </div>

        <div class="divide-y divide-gray-100 flex-1">
            @forelse($recentSO as $so)
                <a href="{{ route('sales.orders.show', $so->order_id) }}" class="p-4 hover:bg-gray-50/80 transition-colors flex items-center justify-between block group">
                    <div class="min-w-0 pr-3">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#18382E] group-hover:underline">{{ $so->order_id }}</span>
                            @php
                                $statusBadge = match($so->status) {
                                    'Confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Done' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Draft' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'Cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-gray-50 text-gray-600 border-gray-200'
                                };
                                $statusLabel = match($so->status) {
                                    'Confirmed' => 'Dikonfirmasi',
                                    'Done' => 'Selesai',
                                    'Draft' => 'Draf',
                                    'Cancelled' => 'Dibatalkan',
                                    default => $so->status
                                };
                            @endphp
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-800 font-medium truncate mt-1">{{ $so->customer?->name ?? 'Pelanggan Walk-in' }}</p>
                        <p class="text-2xs text-gray-400 mt-0.5">{{ $so->order_date ? $so->order_date->translatedFormat('d M Y') : '—' }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs font-bold text-gray-900 block">Rp {{ number_format($so->gross_amount, 0, ',', '.') }}</span>
                        <span class="text-2xs text-gray-400">Total Kotor</span>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="text-xs">Belum ada pesanan penjualan.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- 4B: Recent CRM Leads --}}
    <div class="bg-white rounded-xl border border-[#E8E4DA] shadow-xs flex flex-col justify-between overflow-hidden">
        <div class="p-5 border-b border-[#F0EEE8] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Prospek Terbaru</h3>
                    <p class="text-2xs text-gray-500">Kontak prospek masuk</p>
                </div>
            </div>
            <a href="{{ route('crm.leads.index') }}" class="text-xs font-semibold text-[#C6A76B] hover:text-[#96703A]">
                Lihat Semua →
            </a>
        </div>

        <div class="divide-y divide-gray-100 flex-1">
            @forelse($recentLeads as $lead)
                <a href="{{ route('crm.leads.show', $lead->lead_id) }}" class="p-4 hover:bg-gray-50/80 transition-colors flex items-center justify-between block group">
                    <div class="flex items-center gap-3 min-w-0 pr-3">
                        <div class="w-9 h-9 rounded-lg bg-[#18382E] text-[#C6A76B] font-bold text-sm flex items-center justify-center flex-shrink-0">
                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-900 truncate group-hover:text-[#18382E] group-hover:underline">
                                {{ $lead->name }}
                            </p>
                            <p class="text-2xs text-gray-500 truncate mt-0.5">
                                {{ $lead->customer?->name ?? $lead->company_name ?? 'Individu' }}
                                @if($lead->lead_source) • {{ $lead->lead_source }} @endif
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        @php
                            $leadBadge = match($lead->stage) {
                                'Won' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Lost' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'Qualified' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'Proposition' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-gray-50 text-gray-600 border-gray-200'
                            };
                            $stageLabel = match($lead->stage) {
                                'Won' => 'Berhasil (Won)',
                                'Lost' => 'Gugur (Lost)',
                                'Qualified' => 'Kualifikasi',
                                'Proposition' => 'Penawaran',
                                default => $lead->stage
                            };
                        @endphp
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border {{ $leadBadge }}">
                            {{ $stageLabel }}
                        </span>
                        <span class="text-2xs text-gray-400 block mt-1">{{ $lead->created_at ? $lead->created_at->diffForHumans() : '' }}</span>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <i data-lucide="user-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="text-xs">Belum ada prospek tercatat.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- 4C: Pending Actions & Alerts --}}
    <div class="bg-white rounded-xl border border-[#E8E4DA] shadow-xs flex flex-col justify-between overflow-hidden">
        <div class="p-5 border-b border-[#F0EEE8] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Tindakan Tertunda & Peringatan</h3>
                    <p class="text-2xs text-gray-500">Perhatian manajerial yang diperlukan</p>
                </div>
            </div>
            @php $totalAlerts = $pendingApprovals + $overCreditLimit + $draftQuotations; @endphp
            @if($totalAlerts > 0)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ $totalAlerts }} Perhatian
                </span>
            @endif
        </div>

        <div class="p-5 flex-1 space-y-3">

            {{-- 1. Pending Discount Approvals --}}
            @if($pendingApprovals > 0)
                <div class="p-3.5 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="percent" class="w-4 h-4 text-amber-700 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h4 class="text-xs font-bold text-amber-900">Persetujuan Diskon Tertunda</h4>
                            <p class="text-2xs text-amber-700 mt-0.5">{{ $pendingApprovals }} permohonan diskon memerlukan tinjauan persetujuan manajer.</p>
                        </div>
                    </div>
                    <a href="{{ route('sales.discounts.index') }}?status=Pending" class="text-xs font-bold text-amber-800 hover:underline flex-shrink-0">
                        Tinjau →
                    </a>
                </div>
            @endif

            {{-- 2. Over Credit Limit Warning --}}
            @if($overCreditLimit > 0)
                <div class="p-3.5 bg-rose-50/70 border border-rose-200/80 rounded-xl flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-700 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h4 class="text-xs font-bold text-rose-900">Melebihi Batas Kredit</h4>
                            <p class="text-2xs text-rose-700 mt-0.5">{{ $overCreditLimit }} pelanggan telah melampaui batas kredit yang ditentukan.</p>
                        </div>
                    </div>
                    <a href="{{ route('sales.credit.index') }}" class="text-xs font-bold text-rose-800 hover:underline flex-shrink-0">
                        Lihat →
                    </a>
                </div>
            @endif

            {{-- 3. Draft Quotations --}}
            @if($draftQuotations > 0)
                <div class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-xl flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="file-clock" class="w-4 h-4 text-blue-700 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h4 class="text-xs font-bold text-blue-900">Surat Penawaran Draf</h4>
                            <p class="text-2xs text-blue-700 mt-0.5">{{ $draftQuotations }} penawaran masih berstatus draf dan belum dikirimkan.</p>
                        </div>
                    </div>
                    <a href="{{ route('sales.quotations.index') }}?status=Draft" class="text-xs font-bold text-blue-800 hover:underline flex-shrink-0">
                        Buka →
                    </a>
                </div>
            @endif

            {{-- When all is clear --}}
            @if($pendingApprovals === 0 && $overCreditLimit === 0 && $draftQuotations === 0)
                <div class="p-6 text-center text-gray-500 bg-gray-50/60 rounded-xl border border-dashed border-gray-200">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2.5">
                        <i data-lucide="check-check" class="w-5 h-5"></i>
                    </div>
                    <h4 class="text-xs font-bold text-gray-800">Semua Terkendali</h4>
                    <p class="text-2xs text-gray-500 mt-1 max-w-xs mx-auto">Tidak ada persetujuan yang tertunda atau pelanggaran batas kredit saat ini.</p>
                </div>
            @endif

        </div>

        <div class="px-5 py-3.5 bg-gray-50/70 border-t border-[#F0EEE8] flex items-center justify-between text-xs text-gray-500">
            <span>Kontrol Risiko Kredit & Otomasi</span>
            <a href="{{ route('sales.credit.index') }}" class="text-[#C6A76B] hover:text-[#96703A] font-semibold">
                Pusat Kontrol Kredit →
            </a>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ─── Render Sales Performance Chart (Chart.js) ─────────────────────
    const ctx = document.getElementById('salesPerformanceChart');
    if (ctx && typeof Chart !== 'undefined') {
        const salesData = @json($salesChart);
        const labels = salesData.map(d => d.label);
        const values = salesData.map(d => d.value);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Realisasi Penjualan (Rp)',
                    data: values,
                    backgroundColor: 'rgba(24, 56, 46, 0.88)',
                    borderColor: '#C6A76B',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: '#244A3D',
                    maxBarThickness: 48,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#18382E',
                        titleColor: '#C6A76B',
                        titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                        bodyColor: '#FFFFFF',
                        bodyFont: { family: 'Inter', size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Total: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6B6560',
                            font: { family: 'Inter', size: 11, weight: '500' }
                        }
                    },
                    y: {
                        grid: {
                            color: '#F0EEE8',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#8A8478',
                            font: { family: 'Inter', size: 11 },
                            callback: function(value) {
                                if (value >= 1000000000) {
                                    return (value / 1000000000).toFixed(1) + ' M';
                                } else if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + ' jt';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + ' rb';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush