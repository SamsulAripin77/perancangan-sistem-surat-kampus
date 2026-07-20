<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot pejabat n—n units (ERD §4.1): satu pejabat bisa menjabat di banyak unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pejabat_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pejabat_id')->constrained('pejabat');
            $table->foreignId('unit_id')->constrained('units');
            $table->timestamps();
            $table->unique(['pejabat_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pejabat_unit');
    }
};
