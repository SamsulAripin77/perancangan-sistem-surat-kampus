<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lengkapi users sesuai ERD §2: unit_id (FK units), must_change_password,
 * softDeletes. Kolom is_active sudah ditambahkan di M0-T8. Tidak ada kolom
 * `role` (dikelola Spatie, ERD §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('is_active')->constrained('units');
            $table->boolean('must_change_password')->default(false)->after('unit_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['must_change_password', 'deleted_at']);
        });
    }
};
