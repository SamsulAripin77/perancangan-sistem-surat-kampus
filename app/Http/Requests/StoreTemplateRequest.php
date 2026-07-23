<?php

namespace App\Http\Requests;

use App\Enums\TipePemohon;
use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi tahap awal Template Surat (M2-T3). Upload `.docx` memakai token
 * FilePond temporary dari MediaService, lalu di-attach saat store.
 */
class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_surat']) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', Rule::exists('kategori_surat', 'id')],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => [Rule::exists('units', 'id')],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'tipe_pemohon' => ['required', Rule::enum(TipePemohon::class)],
            'sla_hari_kerja' => ['nullable', 'integer', 'min:0', 'max:127'],
            'is_permohonan_mandiri' => ['required', 'boolean'],
            'docx_token' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $unitIds = collect($this->input('unit_ids', []))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'unit_ids' => $unitIds,
            'is_permohonan_mandiri' => $this->boolean('is_permohonan_mandiri'),
        ]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('docx_token')) {
                    return;
                }

                $token = (string) $this->input('docx_token');
                $path = app(MediaService::class)->temporaryFilePath($token);

                if ($path === null) {
                    $validator->errors()->add('docx_token', __('template.docx_invalid'));

                    return;
                }

                $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
                if ($extension !== 'docx') {
                    $validator->errors()->add('docx_token', __('template.docx_must_be_docx'));
                }

                if (Storage::disk('private')->size($path) > 10 * 1024 * 1024) {
                    $validator->errors()->add('docx_token', __('template.docx_max'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => __('template.nama'),
            'kategori_id' => __('template.kategori'),
            'unit_ids' => __('template.unit_penerbit'),
            'deskripsi' => __('template.deskripsi'),
            'tipe_pemohon' => __('template.tipe_pemohon'),
            'sla_hari_kerja' => __('template.sla_hari_kerja'),
            'is_permohonan_mandiri' => __('template.mandiri'),
            'docx_token' => __('template.file_docx'),
        ];
    }
}
