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
        Schema::create('template_data_tambahan_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('label', 255);
            $table->string('field_key', 100);
            $table->string('tipe_input', 20);
            $table->boolean('is_required')->default(true);
            $table->string('helper_text', 255)->nullable();
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->softDeletes();

            $table->unique(['template_id', 'field_key']);
            $table->index(['template_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_data_tambahan_fields');
    }
};
