@if ($template->is_permohonan_mandiri)
    <x-ui.badge-status status="aktif">{{ __('template.yes') }}</x-ui.badge-status>
@else
    <span class="text-body-secondary">{{ __('template.no') }}</span>
@endif
