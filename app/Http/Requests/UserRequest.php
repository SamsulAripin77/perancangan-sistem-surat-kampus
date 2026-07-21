<?php

namespace App\Http\Requests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi CRUD User (UX_SPEC 2.A.2). Email unik; password wajib saat create
 * dan opsional saat edit (kosong = tak diubah). Bila role = mahasiswa, field
 * profil (NIM unik + prodi) menjadi wajib (1—1 `mahasiswa`).
 */
class UserRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;
        $mahasiswaId = $user instanceof User ? $user->mahasiswa?->id : null;
        $isMahasiswa = $this->input('role') === 'mahasiswa';

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$userId === null ? 'required' : 'nullable', Password::defaults()],
            'role' => ['required', Rule::in(RolePermissionSeeder::ROLES)],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],
            'is_active' => ['required', 'boolean'],

            // Profil mahasiswa — wajib hanya bila role = mahasiswa.
            'nim' => [
                Rule::requiredIf($isMahasiswa),
                'nullable', 'string', 'max:20',
                Rule::unique('mahasiswa', 'nim')->ignore($mahasiswaId),
            ],
            'prodi' => [Rule::requiredIf($isMahasiswa), 'nullable', 'string', 'max:100'],
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
            'name' => __('user.nama'),
            'nim' => __('user.nim'),
            'prodi' => __('user.prodi'),
        ];
    }
}
