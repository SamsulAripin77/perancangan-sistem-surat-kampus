<?php

namespace Database\Seeders;

use App\Models\Pejabat;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Data SAMPLE untuk dev/demo (ARCHITECTURE §13) — memakai factory. TIDAK
 * dipanggil DatabaseSeeder (produksi); jalankan manual:
 *
 *   php artisan db:seed --class=DevSampleSeeder
 *
 * Catatan: seeder sample per-fitur (UnitSeeder, PejabatSeeder, UserSeeder)
 * dibuat bersama milestone-nya (M1-T5/T6/T7). Ini agregat sementara untuk
 * mengisi modal data awal. Semua akun berpassword "password",
 * must_change_password=false (siap login langsung di dev).
 */
class DevSampleSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role inti ada (bila belum di-seed produksi).
        foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Unit + hierarki (Prodi di bawah Fakultas).
        $baa = Unit::factory()->create(['nama' => 'Biro Administrasi Akademik', 'kode' => 'BAA']);
        $fakultas = Unit::factory()->create(['nama' => 'Fakultas Teknik', 'kode' => 'FT']);
        $prodi = Unit::factory()->create(['nama' => 'Prodi Informatika', 'kode' => 'IF', 'parent_id' => $fakultas->id]);

        // Akun admin dengan kredensial dev yang mudah diingat.
        User::factory()->create(['name' => 'Super Admin', 'email' => 'superadmin@nusaputra.test', 'unit_id' => $baa->id])
            ->assignRole('super_admin');

        User::factory()->create(['name' => 'Admin Surat', 'email' => 'admin@nusaputra.test', 'unit_id' => $baa->id])
            ->assignRole('admin_surat');

        // Mahasiswa: 1 akun kredensial tetap + beberapa acak (semua punya profil).
        User::factory()->mahasiswa()->create(['name' => 'Mahasiswa Uji', 'email' => 'mahasiswa@nusaputra.test'])
            ->assignRole('mahasiswa');

        User::factory()->count(5)->mahasiswa()->create()
            ->each(fn (User $user) => $user->assignRole('mahasiswa'));

        // Pejabat penandatangan + assign ke unit (tanpa TTD = TTD basah).
        Pejabat::factory()->count(3)->create()
            ->each(fn (Pejabat $pejabat) => $pejabat->units()->attach([$fakultas->id, $prodi->id]));
    }
}
