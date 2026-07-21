@extends('layouts.app', ['title' => __('import.title')])

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <x-ui.card :title="__('import.title')">
                <p class="text-body-secondary">{{ __('import.intro') }}</p>

                <a href="{{ route('admin.user.import.template') }}" class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="fas fa-download"></i> {{ __('import.download_template') }}
                </a>

                <form method="POST" action="{{ route('admin.user.import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3 app-form-group">
                        <label for="file" class="form-label">{{ __('import.file_label') }} <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="file" accept=".xlsx,.csv"
                            class="form-control form-control-sm" required>
                        <div class="form-text">{{ __('import.file_hint') }}</div>
                        @error('file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.user.index') }}" class="btn btn-secondary btn-sm">{{ __('common.tutup') }}</a>
                        <x-ui.button type="submit" variant="primary" icon="eye">{{ __('import.preview_btn') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection
