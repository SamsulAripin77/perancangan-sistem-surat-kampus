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
        Schema::create('syarat_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('syarat_id')->constrained('ref_syarat_surat')->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedTinyInteger('urutan')->default(0);

            $table->unique(['template_id', 'syarat_id']);
            $table->index(['template_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syarat_surat');
    }
};
