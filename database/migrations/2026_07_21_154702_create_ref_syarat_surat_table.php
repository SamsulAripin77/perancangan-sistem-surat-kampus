<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Master persyaratan dokumen reusable lintas template (ERD §10). File contoh
// via Media Library (collection `template`, disk private) — bukan kolom.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_syarat_surat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->string('accepted_types', 100);
            $table->tinyInteger('max_size_mb')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_syarat_surat');
    }
};
