@extends('layouts.app', ['title' => __('konfigurasi.title')])

@section('content')
    <form method="POST" action="{{ route('admin.konfigurasi.update') }}" enctype="multipart/form-data">
        @csrf

        @foreach ($grouped as $group => $rows)
            <x-ui.card :title="__('konfigurasi.group_' . $group)" class="mb-3">
                @foreach ($rows as $setting)
                    @php($value = $settings->get($setting->key))
                    @switch($setting->type)
                        @case('media')
                            <div class="mb-3 app-form-group">
                                <label class="form-label">{{ $setting->label }}</label>
                                @php($logoUrl = $settings->logoUrl())
                                @if ($logoUrl)
                                    <div class="mb-2">
                                        <img src="{{ $logoUrl }}" alt="{{ $setting->label }}"
                                            class="img-thumbnail" style="max-height: 120px">
                                    </div>
                                @endif
                                <x-form.file name="logo_token" accept="image/png,image/jpeg,image/svg+xml"
                                    :maxSize="'2MB'" />
                            </div>
                            @break

                        @case('text')
                            <div class="mb-3 app-form-group">
                                <label for="{{ $setting->key }}" class="form-label">{{ $setting->label }}</label>
                                <textarea name="{{ $setting->key }}" id="{{ $setting->key }}" rows="3"
                                    class="form-control form-control-sm app-input">{{ old($setting->key, $value) }}</textarea>
                                @error($setting->key)
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            @break

                        @case('encrypted')
                            <x-form.input :name="$setting->key" type="password" :label="$setting->label"
                                autocomplete="new-password" :placeholder="__('konfigurasi.secret_unchanged')" />
                            @break

                        @case('integer')
                            <x-form.input :name="$setting->key" type="number" :label="$setting->label"
                                :value="$value" />
                            @break

                        @default
                            <x-form.input :name="$setting->key" type="text" :label="$setting->label"
                                :value="$value" />
                    @endswitch

                    @if ($setting->key === 'format_nomor_helper')
                        <p class="text-body-secondary small mt-n2 mb-3">{{ __('konfigurasi.format_nomor_hint') }}</p>
                    @endif
                @endforeach
            </x-ui.card>
        @endforeach

        <x-ui.button type="submit" variant="primary" icon="save">{{ __('common.simpan') }}</x-ui.button>
    </form>
@endsection
