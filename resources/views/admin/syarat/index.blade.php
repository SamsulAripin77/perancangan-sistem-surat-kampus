@extends('layouts.app', ['title' => __('syarat.title')])

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#syaratModal"
                data-action="{{ route('admin.persyaratan.store') }}"
                data-method="POST"
                data-title="{{ __('syarat.create_title') }}">
                <i class="fas fa-plus"></i> {{ __('syarat.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#syaratTable" />

        <x-ui.datatable id="syaratTable" :ajax="route('admin.persyaratan.index')" :columns="[
            ['data' => 'nama', 'label' => __('syarat.nama')],
            ['data' => 'accepted_types', 'label' => __('syarat.accepted_types'), 'orderable' => false, 'searchable' => false],
            ['data' => 'max_size_mb', 'label' => __('syarat.max_size'), 'orderable' => false, 'searchable' => false],
            ['data' => 'file', 'label' => __('syarat.file'), 'orderable' => false, 'searchable' => false],
            ['data' => 'dipakai', 'label' => __('syarat.dipakai'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit. --}}
    <div class="modal fade" id="syaratModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.persyaratan.store') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.persyaratan.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('syarat.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <x-form.input name="nama" :label="__('syarat.nama')" required />

                    <div class="mb-3 app-form-group">
                        <label for="deskripsi" class="form-label">{{ __('syarat.deskripsi') }}</label>
                        <textarea name="deskripsi" id="deskripsi" rows="2"
                            class="form-control form-control-sm app-input"></textarea>
                        @error('deskripsi')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-sm-7">
                            <x-form.input name="accepted_types" :label="__('syarat.accepted_types')"
                                value="pdf,jpg" required />
                            <div class="form-text mb-3 mt-n2">{{ __('syarat.accepted_types_hint') }}</div>
                        </div>
                        <div class="col-sm-5">
                            <x-form.input name="max_size_mb" type="number" :label="__('syarat.max_size')"
                                value="5" required />
                        </div>
                    </div>

                    <div class="mb-3 app-form-group">
                        <label class="form-label">{{ __('syarat.file_contoh') }}</label>
                        <x-form.file name="template_token" accept="application/pdf,image/jpeg,image/png" :maxSize="'5MB'" />
                        <div class="form-text">{{ __('syarat.file_hint') }}</div>
                        @error('template_token')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
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
                selector: '#syaratModal',
                action: @json(old('form_action', route('admin.persyaratan.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    nama: @json(old('nama')),
                    deskripsi: @json(old('deskripsi')),
                    accepted_types: @json(old('accepted_types')),
                    max_size_mb: @json(old('max_size_mb')),
                },
            };
        </script>
    @endif
@endsection
