@props([
    'status' => '',
    'type' => null,
    'dot' => false,
])

@php
    $map = [
        'Draft'       => ['class' => 'b-gray',    'label' => 'Draft'],
        'Sent'        => ['class' => 'b-blue',    'label' => 'Terkirim'],
        'Accepted'    => ['class' => 'b-green',   'label' => 'Diterima'],
        'Converted'   => ['class' => 'b-purple',  'label' => 'Dikonversi'],
        'Confirmed'   => ['class' => 'b-green',   'label' => 'Dikonfirmasi'],
        'Done'        => ['class' => 'b-blue',    'label' => 'Selesai'],
        'Posted'      => ['class' => 'b-green',   'label' => 'Posted'],
        'Paid'        => ['class' => 'b-emerald', 'label' => 'Lunas'],
        'Pending'     => ['class' => 'b-amber',   'label' => 'Menunggu'],
        'Approved'    => ['class' => 'b-green',   'label' => 'Disetujui'],
        'Rejected'    => ['class' => 'b-red',     'label' => 'Ditolak'],
        'Cancelled'   => ['class' => 'b-red',     'label' => 'Dibatalkan'],
        'To Invoice'  => ['class' => 'b-amber',   'label' => 'Belum Difaktur'],
        'Invoiced'    => ['class' => 'b-green',   'label' => 'Sudah Difaktur'],
        'Partial'     => ['class' => 'b-gold',    'label' => 'Sebagian'],
        'Active'      => ['class' => 'b-green',   'label' => 'Aktif'],
        'Inactive'    => ['class' => 'b-gray',    'label' => 'Nonaktif'],
    ];
    $cfg = $map[$status] ?? ['class' => $type ?? 'b-gray', 'label' => $status];
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . ($type ?? $cfg['class'])]) }}>
    @if($dot)
        <span class="badge-dot" style="background:currentColor"></span>
    @endif
    {{ $slot->isNotEmpty() ? $slot : $cfg['label'] }}
</span>

