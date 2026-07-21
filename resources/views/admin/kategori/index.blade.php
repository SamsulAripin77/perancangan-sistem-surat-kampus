@extends('layouts.app', ['title' => __('kategori.title')])

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#kategoriModal"
                data-action="{{ route('admin.kategori.store') }}"
                data-method="POST"
                data-title="{{ __('kategori.create_title') }}">
                <i class="fas fa-plus"></i> {{ __('kategori.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#kategoriTable">
            <div class="col-sm-3">
                <select name="status" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('kategori.status_all') }}</option>
                    <option value="1">{{ __('kategori.active') }}</option>
                    <option value="0">{{ __('kategori.inactive') }}</option>
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="kategoriTable" :ajax="route('admin.kategori.index')" :columns="[
            ['data' => 'nama', 'label' => __('kategori.nama')],
            ['data' => 'dipakai', 'label' => __('kategori.dipakai'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_active', 'label' => __('kategori.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit. --}}
    <div class="modal fade" id="kategoriModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.kategori.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.kategori.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('kategori.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <x-form.input name="nama" :label="__('kategori.nama')" required />
                    <div class="form-check form-switch mt-2">
                        <input type="checkbox" name="is_active" id="kategori_is_active" value="1"
                            class="form-check-input" checked>
                        <label for="kategori_is_active" class="form-check-label">{{ __('kategori.active') }}</label>
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
                selector: '#kategoriModal',
                action: @json(old('form_action', route('admin.kategori.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    nama: @json(old('nama')),
                    is_active: @json((bool) old('is_active')),
                },
            };
        </script>
    @endif
@endsection
