<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi CRUD Kategori Surat (UX_SPEC 2.C). `nama` unik (dropdown, bukan teks
 * bebas — cegah inkonsistensi ejaan, ERD §6). Khusus Admin (gating route).
 */
class KategoriRequest extends FormRequest
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
        $id = $this->route('kategori')?->id;

        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('kategori_surat', 'nama')->ignore($id)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
