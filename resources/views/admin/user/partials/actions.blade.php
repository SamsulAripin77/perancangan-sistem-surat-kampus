@php
    $fields = [
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->roles->pluck('name')->first(),
        'unit_id' => $user->unit_id,
        'nim' => $user->mahasiswa?->nim,
        'prodi' => $user->mahasiswa?->prodi,
        'is_active' => $user->is_active,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#userModal"
        data-action="{{ route('admin.user.update', $user) }}"
        data-method="PUT"
        data-title="{{ __('user.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    @unless ($user->is(auth()->user()))
        <form method="POST" action="{{ route('admin.user.toggle', $user) }}" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit"
                class="btn btn-outline-{{ $user->is_active ? 'warning' : 'success' }} js-confirm"
                data-confirm="{{ $user->is_active ? __('user.confirm_deactivate') : __('user.confirm_activate') }}">
                <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
            </button>
        </form>
    @endunless
</div>
