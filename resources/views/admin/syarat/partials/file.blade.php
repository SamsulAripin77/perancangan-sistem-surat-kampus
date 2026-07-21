@if ($syarat->hasTemplateFile())
    <a href="{{ route('admin.persyaratan.download', $syarat) }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-download"></i> {{ __('syarat.download') }}
    </a>
@else
    <span class="text-body-secondary">—</span>
@endif
