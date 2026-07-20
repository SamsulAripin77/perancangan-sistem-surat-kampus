<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Unit '.fake()->unique()->word(),
            'kode' => strtoupper(Str::random(6)),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
