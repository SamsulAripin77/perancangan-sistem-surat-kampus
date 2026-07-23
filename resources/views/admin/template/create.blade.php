@extends('layouts.app', ['title' => __('template.create_title')])

@section('content')
    <x-ui.card>
        <form id="template_create_form" class="template-create-form" method="POST"
            action="{{ route('admin.template.store') }}" enctype="multipart/form-data">
            @csrf

            <section id="template_create_metadata" class="template-create-metadata-section">
                <h5 class="mb-3">{{ __('template.metadata_section') }}</h5>

                <x-form.input name="nama" :label="__('template.nama')" class="template-nama-input" required />

                <x-form.select name="kategori_id" :label="__('template.kategori')" :options="$kategoriOptions"
                    class="template-kategori-select" required />

                <div class="mb-3 app-form-group template-unit-field">
                    <label for="template_unit_ids" class="form-label">
                        {{ __('template.unit_penerbit') }} <span class="text-danger">*</span>
                    </label>
                    <select name="unit_ids[]" id="template_unit_ids"
                        class="form-select form-select-sm app-select js-select2 template-unit-select" multiple required>
                        @foreach ($unitOptions as $unitId => $unitNama)
                            <option value="{{ $unitId }}" @selected(in_array((string) $unitId, old('unit_ids', []), true))>
                                {{ $unitNama }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 app-form-group template-deskripsi-field">
                    <label for="template_deskripsi" class="form-label">{{ __('template.deskripsi') }}</label>
                    <textarea name="deskripsi" id="template_deskripsi" rows="3"
                        class="form-control form-control-sm app-input template-deskripsi-input">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <x-form.select name="tipe_pemohon" :label="__('template.tipe_pemohon')" :options="$tipePemohonOptions"
                    :value="\App\Enums\TipePemohon::Mahasiswa->value" class="template-tipe-pemohon-select" required />

                <x-form.input name="sla_hari_kerja" type="number" :label="__('template.sla_hari_kerja')"
                    class="template-sla-input" min="0" max="127" />

                <div class="form-check form-switch mb-3 template-mandiri-field">
                    <input type="checkbox" name="is_permohonan_mandiri" id="template_is_permohonan_mandiri"
                        value="1" class="form-check-input template-mandiri-input" @checked(old('is_permohonan_mandiri'))>
                    <label for="template_is_permohonan_mandiri" class="form-check-label">
                        {{ __('template.mandiri_label') }}
                    </label>
                </div>
            </section>

            <section id="template_create_docx" class="template-create-docx-section">
                <label class="form-label">{{ __('template.file_docx') }} <span class="text-danger">*</span></label>
                <x-form.file name="docx_token"
                    accept="application/vnd.openxmlformats-officedocument.wordprocessingml.document" :maxSize="'10MB'"
                    class="template-docx-upload" required />
                <div class="form-text">{{ __('template.docx_hint') }}</div>
            </section>

            <div class="template-create-actions d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.template.index') }}" class="btn btn-secondary btn-sm app-btn">
                    {{ __('common.batal') }}
                </a>
                <x-ui.button type="submit" variant="primary" icon="save">
                    {{ __('template.save_and_scan') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
