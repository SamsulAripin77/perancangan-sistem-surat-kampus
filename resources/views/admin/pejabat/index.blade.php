@extends('layouts.app', ['title' => __('pejabat.title')])

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <button type="button" class="btn btn-primary btn-sm app-btn js-modal-open"
                data-modal="#pejabatModal"
                data-action="{{ route('admin.pejabat.store') }}"
                data-method="POST"
                data-title="{{ __('pejabat.create_title') }}">
                <i class="fas fa-plus"></i> {{ __('pejabat.create_title') }}
            </button>
        </x-slot:tools>

        <x-ui.filter table="#pejabatTable">
            <div class="col-sm-3">
                <select name="unit" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('pejabat.unit_all') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('pejabat.status_all') }}</option>
                    <option value="1">{{ __('pejabat.active') }}</option>
                    <option value="0">{{ __('pejabat.inactive') }}</option>
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="pejabatTable" :ajax="route('admin.pejabat.index')" :columns="[
            ['data' => 'nama', 'label' => __('pejabat.nama')],
            ['data' => 'jabatan', 'label' => __('pejabat.jabatan')],
            ['data' => 'units_list', 'label' => __('pejabat.units'), 'orderable' => false, 'searchable' => false],
            ['data' => 'ttd', 'label' => __('pejabat.ttd'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_active', 'label' => __('pejabat.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>

    {{-- Modal form create/edit (diisi hook js-modal-open). --}}
    <div class="modal fade" id="pejabatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" action="{{ route('admin.pejabat.store') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="form_action" value="{{ route('admin.pejabat.store') }}">
                <div class="modal-header">
                    <h5 class="modal-title js-modal-title">{{ __('pejabat.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('common.tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <x-form.input name="nama" :label="__('pejabat.nama')" required />
                    <x-form.input name="jabatan" :label="__('pejabat.jabatan')" required />
                    <x-form.input name="nip_nidn" :label="__('pejabat.nip_nidn')" />
                    <x-form.input name="email" type="email" :label="__('pejabat.email')" />

                    <div class="mb-3 app-form-group">
                        <label for="unit_ids" class="form-label">
                            {{ __('pejabat.units') }} <span class="text-danger">*</span>
                        </label>
                        <select name="unit_ids[]" id="unit_ids" class="form-select form-select-sm js-select2"
                            multiple data-dropdown-parent="#pejabatModal">
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                        @error('unit_ids')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 app-form-group">
                        <label class="form-label">{{ __('pejabat.ttd') }}</label>
                        <x-form.file name="ttd_token" accept="image/png,image/jpeg" :maxSize="'2MB'" />
                        <div class="form-text">{{ __('pejabat.ttd_hint') }}</div>
                        @error('ttd_token')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" id="pejabat_is_active" value="1"
                            class="form-check-input" checked>
                        <label for="pejabat_is_active" class="form-check-label">{{ __('pejabat.active') }}</label>
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
                selector: '#pejabatModal',
                action: @json(old('form_action', route('admin.pejabat.store'))),
                method: @json(old('_method', 'POST')),
                fields: {
                    nama: @json(old('nama')),
                    jabatan: @json(old('jabatan')),
                    nip_nidn: @json(old('nip_nidn')),
                    email: @json(old('email')),
                    unit_ids: @json(old('unit_ids', [])),
                    is_active: @json((bool) old('is_active')),
                },
            };
        </script>
    @endif
@endsection
