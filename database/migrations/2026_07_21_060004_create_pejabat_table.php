<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master penandatangan (ERD §4). Phase 1 pejabat tanpa akun (proxy approval);
 * user_id nullable disiapkan untuk Phase 2. File TTD via Media Library
 * (collection 'ttd', disk private) — bukan kolom path (K4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pejabat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('email', 150)->nullable();
            $table->string('nip_nidn', 30)->nullable();
            $table->string('jabatan', 150);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pejabat');
    }
};
