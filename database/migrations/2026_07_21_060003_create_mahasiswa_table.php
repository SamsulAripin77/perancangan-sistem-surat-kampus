<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil akademik mahasiswa, 1—1 dengan users via user_id UNIQUE (ERD §3).
 * Tanpa kolom `fakultas` (keputusan D-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('nim', 20)->unique();
            $table->string('nama', 150);
            $table->string('prodi', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
