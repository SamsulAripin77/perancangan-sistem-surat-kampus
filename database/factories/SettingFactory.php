<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => 'umum',
            'key' => 'key_'.Str::random(8),
            'value' => fake()->word(),
            'type' => 'string',
            'label' => fake()->words(2, true),
            'is_public' => false,
        ];
    }
}
