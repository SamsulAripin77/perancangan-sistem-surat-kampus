<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Master kategori/pengelompokan template surat (ERD §6). 1—n templates.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_surat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_surat');
    }
};
