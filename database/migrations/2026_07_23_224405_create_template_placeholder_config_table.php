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
        Schema::create('template_placeholder_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('placeholder_name', 100);
            $table->string('label_mahasiswa', 255)->nullable();
            $table->string('tipe_input', 20);
            $table->string('filled_by', 20);
            $table->boolean('is_required')->default(true);
            $table->unsignedTinyInteger('urutan')->default(0);

            $table->unique(['template_id', 'placeholder_name']);
            $table->index(['template_id', 'filled_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_placeholder_config');
    }
};
