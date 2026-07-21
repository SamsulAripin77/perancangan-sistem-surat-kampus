<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi unggah file import mahasiswa (UX_SPEC 2.A.3). Terima `.xlsx/.csv`
 * (CSV kerap ber-mime text/plain). Khusus Super Admin (gating route).
 */
class MahasiswaImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
        ];
    }
}
