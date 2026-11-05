@extends('layouts.app')

@section('title', 'Edit Lead — ' . $lead->name)
@section('breadcrumb')
    <a href="{{ route('crm.index') }}" class="text-gray-500 hover:text-gray-700">CRM</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <a href="{{ route('crm.show', $lead->lead_id) }}" class="text-gray-500 hover:text-gray-700">{{ $lead->name }}</a>
    <i data-lucide="chevron-right" class="w-3 h-3 mx-1 text-gray-400"></i>
    <span class="text-gray-600">Edit</span>
@endsection

@section('content')

<div class="mb-6">
    <h1 class="text-xl font-semibold text-gray-800">Edit Lead: {{ $lead->name }}</h1>
    <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $lead->lead_id }}</p>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
    <div class="flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('crm.update', $lead->lead_id) }}">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-surface-border shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Informasi Lead</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lead / Peluang <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $lead->name) }}"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-400 @enderror">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pelanggan <span class="text-red-500">*</span></label>
                        <select name="customer_id"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-400 @enderror">
                            <option value="">— Pilih Pelanggan —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->contact_id }}" {{ old('customer_id', $lead->customer_id) == $customer->contact_id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stage <span class="text-red-500">*</span></label>
                        <select name="stage"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('stage') border-red-400 @enderror">
                            @foreach($stages as $stage)
                                <option value="{{ $stage }}" {{ old('stage', $lead->stage) === $stage ? 'selected' : '' }}>
                                    {{ $stage }}
                                </option>
                            @endforeach
                        </select>
                        @error('stage')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Estimasi Revenue (Rp)</label>
                        <input type="number" name="expected_revenue" value="{{ old('expected_revenue', $lead->expected_revenue) }}"
                            min="0" step="10000"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                        <textarea name="notes" rows="4"
                            class="w-full px-3 py-2 border border-surface-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('notes', $lead->notes) }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <p class="text-xs text-amber-700">Untuk mengubah stage lead, gunakan form di halaman detail atau pilih stage di sini.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Perubahan
                </button>
                <a href="{{ route('crm.show', $lead->lead_id) }}" class="btn-outline w-full justify-center">
                    Batal
                </a>
            </div>
        </div>

    </div>
</form>

@endsection
