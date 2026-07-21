<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Orkestrasi simpan User (M1-T7): tulis atribut user, set password (hanya bila
 * diisi — kosong saat edit = tak diubah), sync role Spatie, dan upsert profil
 * `mahasiswa` 1—1 bila role = mahasiswa. Tanpa hard delete (D-005).
 */
class SaveUser
{
    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari UserRequest.
     */
    public function handle(array $data, ?User $user = null): User
    {
        $user ??= new User;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'unit_id' => $data['unit_id'] ?? null,
            'is_active' => $data['is_active'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        if ($data['role'] === 'mahasiswa') {
            $user->mahasiswa()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $data['nim'],
                    'nama' => $data['name'],
                    'prodi' => $data['prodi'],
                    'is_active' => $data['is_active'],
                ],
            );
        }

        return $user;
    }
}
