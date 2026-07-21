<x-ui.badge-status :status="$user->is_active ? 'aktif' : 'nonaktif'">
    {{ $user->is_active ? __('user.active') : __('user.inactive') }}
</x-ui.badge-status>
