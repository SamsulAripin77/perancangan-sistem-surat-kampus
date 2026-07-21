@extends('layouts.app', ['title' => __('kamus.title')])

@php
    use App\Models\PlaceholderDefinition;
@endphp

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#kamusModal"
                data-action="{{ route('admin.kamus.store') }}"
                data-method="POST"
                data-title="{{ __('kamus.create_title') }}">
                <i class="fas fa-plus"></i> {{ __('kamus.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#kamusTable">
            <div class="col-sm-3">
                <select name="kelompok" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('kamus.kelompok_all') }}</option>
                    @foreach (PlaceholderDefinition::KELOMPOK as $kelompok)
                        <option value="{{ $kelompok }}">{{ __("kamus.kelompok_{$kelompok}") }}</option>
                    @endforeach
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="kamusTable" :ajax="route('admin.kamus.index')" :columns="[
            ['data' => 'name', 'label' => __('kamus.name')],
            ['data' => 'kelompok', 'label' => __('kamus.kelompok')],
            ['data' => 'input_type', 'label' => __('kamus.input_type')],
            ['data' => 'source', 'label' => __('kamus.source'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_overridable', 'label' => __('kamus.overridable'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit. --}}
    <div class="modal fade" id="kamusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.kamus.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.kamus.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('kamus.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">{{ __('kamus.impact_warning') }}</div>

                    <x-form.input name="name" :label="__('kamus.name')" required />
                    <div class="form-text mb-3">{{ __('kamus.name_hint') }}</div>

                    <div class="mb-3 app-form-group">
                        <label for="kelompok" class="form-label">{{ __('kamus.kelompok') }} <span class="text-danger">*</span></label>
                        <select name="kelompok" id="kelompok" class="form-select form-select-sm">
                            @foreach (PlaceholderDefinition::KELOMPOK as $kelompok)
                                <option value="{{ $kelompok }}">{{ __("kamus.kelompok_{$kelompok}") }}</option>
                            @endforeach
                        </select>
                        @error('kelompok')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 app-form-group">
                        <label for="input_type" class="form-label">{{ __('kamus.input_type') }} <span class="text-danger">*</span></label>
                        <select name="input_type" id="input_type" class="form-select form-select-sm">
                            @foreach (PlaceholderDefinition::INPUT_TYPES as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('input_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <x-form.input name="source" :label="__('kamus.source')" />

                    <div class="form-check form-switch mt-2">
                        <input type="checkbox" name="is_overridable" id="is_overridable" value="1"
                            class="form-check-input" checked>
                        <label for="is_overridable" class="form-check-label">{{ __('kamus.overridable_label') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        {{ __('common.tutup') }}
                    </button>
                    <x-ui.button type="submit" variant="primary" icon="save">{{ __('common.simpan') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            window.__reopenModal = {
                selector: '#kamusModal',
                action: @json(old('form_action', route('admin.kamus.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    name: @json(old('name')),
                    kelompok: @json(old('kelompok')),
                    input_type: @json(old('input_type')),
                    source: @json(old('source')),
                    is_overridable: @json((bool) old('is_overridable')),
                },
            };
        </script>
    @endif
@endsection
