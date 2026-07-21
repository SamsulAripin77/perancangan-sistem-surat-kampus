@extends('layouts.mahasiswa', ['title' => __('common.beranda')])

@section('content')
    <h4 class="mb-3">{{ __('dashboard.greeting', ['nama' => $nama]) }}</h4>

    <div class="row g-3">
        <div class="col-md-5">
            <x-ui.card>
                <a href="{{ Route::has('mahasiswa.ajukan.index') ? route('mahasiswa.ajukan.index') : '#' }}"
                    class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-plus"></i> {{ __('dashboard.ajukan_surat') }}
                </a>
            </x-ui.card>
        </div>

        <div class="col-md-7">
            <x-ui.card :title="__('dashboard.permohonan_aktif')">
                @forelse ($permohonan as $item)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                        <span>{{ $item->jenis ?? '' }}</span>
                        <x-ui.badge-status :status="$item->status ?? ''">{{ $item->status ?? '' }}</x-ui.badge-status>
                    </div>
                @empty
                    <p class="text-body-secondary mb-2">{{ __('dashboard.empty_mahasiswa') }}</p>
                    <a href="{{ Route::has('mahasiswa.ajukan.index') ? route('mahasiswa.ajukan.index') : '#' }}"
                        class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus"></i> {{ __('dashboard.ajukan_pertama') }}
                    </a>
                @endforelse
            </x-ui.card>
        </div>
    </div>
@endsection
