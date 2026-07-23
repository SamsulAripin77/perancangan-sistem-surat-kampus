@extends('layouts.app', ['title' => __('template.edit_title')])

@section('content')
    <x-ui.card>
        <section id="template_handoff_header" class="template-handoff-header-section mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">{{ $template->nama }}</h4>
                    <div class="text-muted">{{ __('template.handoff_title') }}</div>
                </div>
                <div class="template-handoff-status">
                    @include('admin.template.partials.status', ['template' => $template])
                </div>
            </div>
        </section>

        <section id="template_handoff_metadata" class="template-handoff-metadata-section mb-4">
            <h5 class="mb-3">{{ __('template.metadata_section') }}</h5>
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('template.kategori') }}</dt>
                <dd class="col-sm-9">{{ $template->kategori->nama }}</dd>

                <dt class="col-sm-3">{{ __('template.unit_penerbit') }}</dt>
                <dd class="col-sm-9">{{ $template->units->pluck('nama')->join(', ') ?: '—' }}</dd>

                <dt class="col-sm-3">{{ __('template.tipe_pemohon') }}</dt>
                <dd class="col-sm-9">{{ __('template.tipe_pemohon_values.'.$template->tipePemohonValue()) }}</dd>

                <dt class="col-sm-3">{{ __('template.sla_hari_kerja') }}</dt>
                <dd class="col-sm-9">{{ $template->sla_hari_kerja ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('template.mandiri') }}</dt>
                <dd class="col-sm-9">{{ $template->is_permohonan_mandiri ? __('template.yes') : __('template.no') }}</dd>

                <dt class="col-sm-3">{{ __('template.deskripsi') }}</dt>
                <dd class="col-sm-9">{{ $template->deskripsi ?: '—' }}</dd>
            </dl>
        </section>

        <section id="template_handoff_docx" class="template-handoff-docx-section mb-4">
            <h5 class="mb-3">{{ __('template.docx_section') }}</h5>
            <p class="mb-0">
                {{ $template->hasDocx() ? __('template.docx_ready') : __('template.docx_missing') }}
            </p>
        </section>

        <section id="template_handoff_next" class="template-handoff-next-section alert alert-info mb-4">
            {{ __('template.handoff_note') }}
        </section>

        <div class="template-handoff-actions d-flex justify-content-end">
            <a href="{{ route('admin.template.index') }}" class="btn btn-secondary btn-sm app-btn">
                {{ __('common.kembali') }}
            </a>
        </div>
    </x-ui.card>
@endsection
