@props(['status'])
@php
    // Pemetaan warna generik lintas entitas (§11.3). Status spesifik per
    // domain (mis. permohonan_surat) dapat menambah kasus saat dibutuhkan.
    $variant = match (strtolower((string) $status)) {
        'aktif', 'disetujui', 'selesai' => 'success',
        'pending', 'diverifikasi', 'draft' => 'secondary',
        'nonaktif', 'ditolak', 'dibatalkan' => 'danger',
        'digantikan' => 'warning',
        default => 'secondary',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge bg-$variant app-badge-status"]) }}>
    {{ $slot }}
</span>
