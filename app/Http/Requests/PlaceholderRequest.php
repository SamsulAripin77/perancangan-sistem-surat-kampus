<?php

namespace App\Http\Requests;

use App\Models\PlaceholderDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi CRUD Kamus Placeholder (UX_SPEC 2.B). `name` unik + snake_case
 * (huruf kecil, angka, underscore; diawali huruf); `kelompok`/`input_type`
 * dibatasi enum SSOT model. Khusus Super Admin (gating route).
 */
class PlaceholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('kamus')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('placeholder_definitions', 'name')->ignore($id),
            ],
            'kelompok' => ['required', Rule::in(PlaceholderDefinition::KELOMPOK)],
            'input_type' => ['required', Rule::in(PlaceholderDefinition::INPUT_TYPES)],
            'source' => ['nullable', 'string', 'max:100'],
            'is_overridable' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_overridable' => $this->boolean('is_overridable'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => __('kamus.name_snake_error'),
        ];
    }
}
