<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi CRUD Unit (UX_SPEC 1.C.2). `kode` unik (abaikan diri sendiri saat
 * update); `parent_id` opsional tapi wajib unit lain yang ada (bukan diri
 * sendiri agar tak membentuk siklus 1 tingkat).
 */
class UnitRequest extends FormRequest
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
        $unitId = $this->route('unit')?->id;

        return [
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('units', 'kode')->ignore($unitId)],
            'parent_id' => [
                'nullable',
                Rule::exists('units', 'id'),
                Rule::notIn($unitId !== null ? [$unitId] : []),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Checkbox/toggle → boolean pasti; default aktif bila tak dikirim.
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
            'nama' => __('unit.nama'),
            'kode' => __('unit.kode'),
            'parent_id' => __('unit.parent'),
        ];
    }
}
