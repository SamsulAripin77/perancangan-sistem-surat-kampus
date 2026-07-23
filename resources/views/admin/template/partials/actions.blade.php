<div class="btn-group btn-group-sm" role="group">
    <a href="{{ Route::has('admin.template.edit') ? route('admin.template.edit', $template) : '#' }}"
        class="btn btn-outline-primary {{ Route::has('admin.template.edit') ? '' : 'disabled' }}"
        @unless (Route::has('admin.template.edit')) aria-disabled="true" @endunless>
        <i class="fas fa-pen"></i>
    </a>
    <button type="button" class="btn btn-outline-danger" disabled aria-disabled="true">
        <i class="fas fa-trash"></i>
    </button>
</div>
