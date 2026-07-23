<?php

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Enums\TipePemohon;
use App\Models\KategoriSurat;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => ucwords(fake()->unique()->words(3, true)),
            'kategori_id' => KategoriSurat::factory(),
            'deskripsi' => fake()->optional()->sentence(),
            'tipe_pemohon' => TipePemohon::Umum->value,
            'sla_hari_kerja' => fake()->optional()->numberBetween(1, 14),
            'is_permohonan_mandiri' => false,
            'status' => TemplateStatus::Draft->value,
            'created_by' => User::factory(),
        ];
    }
}
