@php
    $fields = [
        'nama' => $kategori->nama,
        'is_active' => $kategori->is_active,
    ];
@endphp
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary js-modal-open"
        data-modal="#kategoriModal"
        data-action="{{ route('admin.kategori.update', $kategori) }}"
        data-method="PUT"
        data-title="{{ __('kategori.edit_title') }}"
        data-fields='@json($fields)'>
        <i class="fas fa-pen"></i>
    </button>

    <form method="POST" action="{{ route('admin.kategori.toggle', $kategori) }}" class="d-inline">
        @csrf
        @method('PATCH')
        <button type="submit"
            class="btn btn-outline-{{ $kategori->is_active ? 'warning' : 'success' }} js-confirm"
            data-confirm="{{ $kategori->is_active ? __('kategori.confirm_deactivate') : __('kategori.confirm_activate') }}">
            <i class="fas fa-{{ $kategori->is_active ? 'ban' : 'check' }}"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('admin.kategori.destroy', $kategori) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger js-confirm"
            data-confirm="{{ __('kategori.confirm_delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
