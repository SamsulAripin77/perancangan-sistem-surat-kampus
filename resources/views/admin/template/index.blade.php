@extends('layouts.app', ['title' => __('template.title')])

@section('content')
    <x-ui.card>
        <x-slot:tools>
            <div class="btn-group btn-group-sm template-index-tools" role="group">
                <a href="{{ Route::has('admin.template.panduan') ? route('admin.template.panduan') : '#' }}"
                    class="btn btn-outline-secondary app-btn {{ Route::has('admin.template.panduan') ? '' : 'disabled' }}"
                    @unless (Route::has('admin.template.panduan')) aria-disabled="true" @endunless>
                    <i class="fas fa-book-open"></i> {{ __('template.guide') }}
                </a>
                <a href="{{ Route::has('admin.template.create') ? route('admin.template.create') : '#' }}"
                    class="btn btn-primary app-btn {{ Route::has('admin.template.create') ? '' : 'disabled' }}"
                    @unless (Route::has('admin.template.create')) aria-disabled="true" @endunless>
                    <i class="fas fa-plus"></i> {{ __('template.create_title') }}
                </a>
            </div>
        </x-slot:tools>

        <x-ui.filter table="#templateTable">
            <div class="col-sm-3 template-filter-kategori">
                <select id="template_filter_kategori" name="kategori_id"
                    class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('template.kategori_all') }}</option>
                    @foreach ($kategoriOptions as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3 template-filter-unit">
                <select id="template_filter_unit" name="unit_id" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('template.unit_all') }}</option>
                    @foreach ($unitOptions as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 template-filter-status">
                <select id="template_filter_status" name="status" class="form-select form-select-sm js-filter-input">
                    <option value="">{{ __('template.status_all') }}</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ __('template.status_values.'.$status->value) }}</option>
                    @endforeach
                </select>
            </div>
        </x-ui.filter>

        <x-ui.datatable id="templateTable" :ajax="route('admin.template.index')" :columns="[
            ['data' => 'nama', 'label' => __('template.nama')],
            ['data' => 'kategori', 'label' => __('template.kategori'), 'orderable' => false, 'searchable' => false],
            ['data' => 'unit', 'label' => __('template.unit'), 'orderable' => false, 'searchable' => false],
            ['data' => 'tipe_pemohon', 'label' => __('template.tipe_pemohon'), 'orderable' => false, 'searchable' => false],
            ['data' => 'is_permohonan_mandiri', 'label' => __('template.mandiri'), 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'label' => __('template.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'aksi', 'label' => __('common.aksi'), 'orderable' => false, 'searchable' => false],
        ]" />
    </x-ui.card>
@endsection
