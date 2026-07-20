<?php

namespace Database\Factories;

use App\Models\Pejabat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pejabat>
 */
class PejabatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'nip_nidn' => fake()->optional()->numerify('################'),
            'jabatan' => fake()->randomElement(['Kaprodi Informatika', 'Dekan', 'Kepala BAA', 'Ketua LPPM']),
            'is_active' => true,
            'user_id' => null,
        ];
    }
}
