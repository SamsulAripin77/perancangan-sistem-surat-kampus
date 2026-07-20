<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nim' => (string) fake()->unique()->numerify('##########'),
            'nama' => fake()->name(),
            'prodi' => fake()->randomElement(['Informatika', 'Teknik Industri', 'Manajemen', 'Akuntansi']),
            'is_active' => true,
        ];
    }
}
