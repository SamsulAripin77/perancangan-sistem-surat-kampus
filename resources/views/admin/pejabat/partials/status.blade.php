<x-ui.badge-status :status="$pejabat->is_active ? 'aktif' : 'nonaktif'">
    {{ $pejabat->is_active ? __('pejabat.active') : __('pejabat.inactive') }}
</x-ui.badge-status>
