<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed inti PRODUKSI (ARCHITECTURE §13). Data sample (unit, kategori,
     * pejabat, mahasiswa contoh) di-seed oleh seeder fiturnya masing-masing.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
            PlaceholderDefinitionSeeder::class,
        ]);
    }
}
