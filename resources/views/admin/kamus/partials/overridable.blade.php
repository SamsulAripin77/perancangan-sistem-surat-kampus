@if ($item->is_overridable)
    <span class="text-success" title="{{ __('kamus.overridable_yes') }}"><i class="fas fa-check"></i></span>
@else
    <span class="text-body-secondary" title="{{ __('kamus.overridable_no') }}">—</span>
@endif
