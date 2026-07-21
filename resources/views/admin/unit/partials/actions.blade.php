@php
    $fields = [
        'nama' => $unit->nama,
        'kode' => $unit->kode,
        'parent_id' => $unit->parent_id,
        'is_active' => $unit->is_active,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#unitModal"
        data-action="{{ route('admin.unit.update', $unit) }}"
        data-method="PUT"
        data-title="{{ __('unit.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    <form method="POST" action="{{ route('admin.unit.toggle', $unit) }}" class="d-inline">
        @csrf
        @method('PATCH')
        <button type="submit"
            class="btn btn-outline-{{ $unit->is_active ? 'warning' : 'success' }} js-confirm"
            data-confirm="{{ $unit->is_active ? __('unit.confirm_deactivate') : __('unit.confirm_activate') }}">
            <i class="fas fa-{{ $unit->is_active ? 'ban' : 'check' }}"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('admin.unit.destroy', $unit) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger js-confirm"
            data-confirm="{{ __('unit.confirm_delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
