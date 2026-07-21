@php
    $fields = [
        'nama' => $pejabat->nama,
        'jabatan' => $pejabat->jabatan,
        'nip_nidn' => $pejabat->nip_nidn,
        'email' => $pejabat->email,
        'unit_ids' => $pejabat->units->pluck('id')->all(),
        'is_active' => $pejabat->is_active,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#pejabatModal"
        data-action="{{ route('admin.pejabat.update', $pejabat) }}"
        data-method="PUT"
        data-title="{{ __('pejabat.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    <form method="POST" action="{{ route('admin.pejabat.toggle', $pejabat) }}" class="d-inline">
        @csrf
        @method('PATCH')
        <button type="submit"
            class="btn btn-outline-{{ $pejabat->is_active ? 'warning' : 'success' }} js-confirm"
            data-confirm="{{ $pejabat->is_active ? __('pejabat.confirm_deactivate') : __('pejabat.confirm_activate') }}">
            <i class="fas fa-{{ $pejabat->is_active ? 'ban' : 'check' }}"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('admin.pejabat.destroy', $pejabat) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger js-confirm"
            data-confirm="{{ __('pejabat.confirm_delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
