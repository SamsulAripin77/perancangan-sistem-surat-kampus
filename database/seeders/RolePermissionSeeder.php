<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * SSOT role Phase 1 (ERD §5, ARCHITECTURE §8.2). Seeder PRODUKSI — dijalankan
 * saat instalasi. Permission ditambahkan per-fitur (F1-F13) seiring Policy /
 * middleware fitur mendefinisikannya; di sini hanya role inti dibuat.
 */
class RolePermissionSeeder extends Seeder
{
    /** Role Phase 1 (guard 'web' — konsisten config/fortify.php). */
    public const ROLES = ['super_admin', 'admin_surat', 'mahasiswa'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
