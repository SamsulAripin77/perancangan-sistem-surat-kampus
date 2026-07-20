<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ditarik maju dari M1-T9 (keputusan M0-T9) agar PlaceholderDefinitionSeeder
 * produksi jalan saat bootstrap. M1-T9 nanti TIDAK boleh membuat ulang tabel.
 * Skema mengikuti ERD §8 (tanpa timestamps, sesuai ERD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placeholder_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('kelompok', 20);
            $table->string('input_type', 20);
            $table->string('source', 100)->nullable();
            $table->boolean('is_overridable')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placeholder_definitions');
    }
};
