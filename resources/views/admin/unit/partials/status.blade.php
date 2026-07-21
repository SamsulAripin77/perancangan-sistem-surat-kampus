<x-ui.badge-status :status="$unit->is_active ? 'aktif' : 'nonaktif'">
    {{ $unit->is_active ? __('unit.active') : __('unit.inactive') }}
</x-ui.badge-status>
