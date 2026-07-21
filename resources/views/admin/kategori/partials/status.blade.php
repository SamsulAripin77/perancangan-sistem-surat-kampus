<x-ui.badge-status :status="$kategori->is_active ? 'aktif' : 'nonaktif'">
    {{ $kategori->is_active ? __('kategori.active') : __('kategori.inactive') }}
</x-ui.badge-status>
