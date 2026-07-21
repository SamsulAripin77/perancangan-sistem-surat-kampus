@extends('layouts.app', ['title' => __('unit.title')])

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#unitModal"
                data-action="{{ route('admin.unit.store') }}"
                data-method="POST"
                data-title="{{ __('unit.create_title') }}"
                data-reset="1">
                <i class="fas fa-plus"></i> {{ __('unit.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#unitTable">
            <div class="col-sm-3">
                <select name="status" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('unit.status_all') }}</option>
                    <option value="1">{{ __('unit.active') }}</option>
                    <option value="0">{{ __('unit.inactive') }}</option>
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="unitTable" :ajax="route('admin.unit.index')" :columns="[
            ['data' => 'nama', 'label' => __('unit.nama')],
            ['data' => 'kode', 'label' => __('unit.kode')],
            ['data' => 'parent_nama', 'label' => __('unit.parent'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_active', 'label' => __('unit.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit (diisi hook js-modal-open dari tombol tabel). --}}
    <div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.unit.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.unit.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('unit.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <x-form.input name="nama" :label="__('unit.nama')" required />
                    <x-form.input name="kode" :label="__('unit.kode')" required />
                    <div class="mb-3 app-form-group">
                        <label for="parent_id" class="form-label">{{ __('unit.parent') }}</label>
                        <select name="parent_id" id="parent_id" class="form-select form-select-sm">
                            <option value="">{{ __('unit.no_parent') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->nama }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            class="form-check-input" checked>
                        <label for="is_active" class="form-check-label">{{ __('unit.active') }}</label>
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
        {{-- Validasi gagal: buka kembali modal dengan nilai lama & mode semula. --}}
        <script>
            window.__reopenUnitModal = {
                action: @json(old('form_action', route('admin.unit.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    nama: @json(old('nama')),
                    kode: @json(old('kode')),
                    parent_id: @json(old('parent_id')),
                    is_active: @json((bool) old('is_active')),
                },
            };
        </script>
    @endif
@endsection
