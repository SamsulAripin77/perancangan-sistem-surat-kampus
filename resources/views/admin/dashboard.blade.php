@extends('layouts.app', ['title' => __('common.dashboard')])

@php
    $cards = [
        ['key' => 'pending', 'label' => __('dashboard.pending'), 'variant' => 'primary', 'icon' => 'inbox', 'route' => 'admin.permohonan.index'],
        ['key' => 'mendekati', 'label' => __('dashboard.mendekati'), 'variant' => 'warning', 'icon' => 'clock', 'route' => 'admin.permohonan.index'],
        ['key' => 'overdue', 'label' => __('dashboard.overdue'), 'variant' => 'danger', 'icon' => 'triangle-exclamation', 'route' => 'admin.permohonan.index'],
        ['key' => 'disposisi', 'label' => __('dashboard.disposisi'), 'variant' => 'info', 'icon' => 'share-nodes', 'route' => 'admin.surat-masuk.agenda'],
    ];
@endphp

@section('content')
    <div class="row g-3">
        @foreach ($cards as $card)
            @php($href = Route::has($card['route']) ? route($card['route']) : '#')
            <div class="col-sm-6 col-xl-3">
                <a href="{{ $href }}" class="text-decoration-none">
                    <div class="card app-card text-bg-{{ $card['variant'] }}">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-3 fw-semibold">{{ $stats[$card['key']] }}</div>
                                <div class="small">{{ $card['label'] }}</div>
                            </div>
                            <i class="fas fa-{{ $card['icon'] }} fa-2x opacity-50"></i>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <x-ui.card :title="__('dashboard.permohonan_terbaru')" class="mt-3">
        <p class="text-body-secondary mb-0">{{ __('dashboard.empty_permohonan') }}</p>
    </x-ui.card>
@endsection
