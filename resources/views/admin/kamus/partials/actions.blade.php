@php
    $fields = [
        'name' => $item->name,
        'kelompok' => $item->kelompok,
        'input_type' => $item->input_type,
        'source' => $item->source,
        'is_overridable' => $item->is_overridable,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#kamusModal"
        data-action="{{ route('admin.kamus.update', $item) }}"
        data-method="PUT"
        data-title="{{ __('kamus.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    <form method="POST" action="{{ route('admin.kamus.destroy', $item) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger js-confirm"
            data-confirm="{{ __('kamus.confirm_delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
