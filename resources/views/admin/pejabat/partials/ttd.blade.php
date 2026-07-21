@if ($pejabat->hasTtd())
    <span class="text-success" title="{{ __('pejabat.ttd_ada') }}"><i class="fas fa-check"></i></span>
@else
    <span class="text-body-secondary" title="{{ __('pejabat.ttd_basah') }}">—</span>
@endif
