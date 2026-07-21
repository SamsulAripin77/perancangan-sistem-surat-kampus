<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi CRUD Master Persyaratan (UX_SPEC 2.D). `nama` unik; `accepted_types`
 * daftar ekstensi (mis. "pdf,jpg"); `template_token` = server id FilePond (uuid)
 * file contoh opsional. Khusus Admin (gating route).
 */
class SyaratRequest extends FormRequest
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
        $id = $this->route('persyaratan')?->id;

        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('ref_syarat_surat', 'nama')->ignore($id)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'accepted_types' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(,[a-z0-9]+)*$/'],
            'max_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'template_token' => ['nullable', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalisasi accepted_types: huruf kecil, tanpa spasi.
        if ($this->has('accepted_types')) {
            $this->merge([
                'accepted_types' => strtolower(str_replace(' ', '', (string) $this->input('accepted_types'))),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accepted_types.regex' => __('syarat.accepted_types_error'),
        ];
    }
}
