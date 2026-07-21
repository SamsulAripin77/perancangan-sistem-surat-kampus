<?php

namespace Database\Factories;

use App\Models\PlaceholderDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlaceholderDefinition>
 */
class PlaceholderDefinitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::snake(fake()->unique()->words(2, true)),
            'kelompok' => fake()->randomElement(PlaceholderDefinition::KELOMPOK),
            'input_type' => fake()->randomElement(PlaceholderDefinition::INPUT_TYPES),
            'source' => null,
            'is_overridable' => true,
        ];
    }
}
