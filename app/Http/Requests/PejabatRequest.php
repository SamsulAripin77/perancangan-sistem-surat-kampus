<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi CRUD Pejabat (UX_SPEC 1.C.3). Wajib punya ≥1 unit (n—n); TTD
 * opsional (kosong = tanda tangan basah). `ttd_token` = server id FilePond
 * (uuid) hasil upload sementara.
 */
class PejabatRequest extends FormRequest
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
            'jabatan' => ['required', 'string', 'max:150'],
            'nip_nidn' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => [Rule::exists('units', 'id')],
            'ttd_token' => ['nullable', 'string', 'uuid'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => __('pejabat.nama'),
            'jabatan' => __('pejabat.jabatan'),
            'unit_ids' => __('pejabat.units'),
        ];
    }
}
