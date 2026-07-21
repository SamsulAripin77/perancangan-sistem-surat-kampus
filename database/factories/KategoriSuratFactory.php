<?php

namespace Database\Factories;

use App\Models\KategoriSurat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriSurat>
 */
class KategoriSuratFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => ucwords(fake()->unique()->words(2, true)),
            'is_active' => true,
        ];
    }
}
