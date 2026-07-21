@php
    $fields = [
        'nama' => $syarat->nama,
        'deskripsi' => $syarat->deskripsi,
        'accepted_types' => $syarat->accepted_types,
        'max_size_mb' => $syarat->max_size_mb,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#syaratModal"
        data-action="{{ route('admin.persyaratan.update', $syarat) }}"
        data-method="PUT"
        data-title="{{ __('syarat.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    <form method="POST" action="{{ route('admin.persyaratan.destroy', $syarat) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger js-confirm"
            data-confirm="{{ __('syarat.confirm_delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
