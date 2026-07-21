<?php

namespace Database\Factories;

use App\Models\RefSyaratSurat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefSyaratSurat>
 */
class RefSyaratSuratFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => ucwords(fake()->unique()->words(3, true)),
            'deskripsi' => fake()->optional()->sentence(),
            'accepted_types' => 'pdf,jpg',
            'max_size_mb' => 5,
        ];
    }
}
