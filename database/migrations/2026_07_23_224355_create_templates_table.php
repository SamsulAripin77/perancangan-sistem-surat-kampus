<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->foreignId('kategori_id')->constrained('kategori_surat')->restrictOnDelete();
            $table->text('deskripsi')->nullable();
            $table->string('tipe_pemohon', 20)->default('umum')->index();
            $table->tinyInteger('sla_hari_kerja')->nullable();
            $table->boolean('is_permohonan_mandiri')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
