<?php

use App\Models\PlaceholderDefinition;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

// Smoke test fondasi (M0-T9, gate ARCHITECTURE §0): seeder produksi jalan &
// role gating berfungsi. Login-as-each-role penuh menyusul di M1-T2.

it('seeds the three Phase 1 roles', function () {
    $this->seed();

    expect(Role::pluck('name')->all())
        ->toContain('super_admin', 'admin_surat', 'mahasiswa');
});

it('seeds core settings with public identity values', function () {
    $this->seed();

    $nama = Setting::where('key', 'nama_universitas')->first();

    expect($nama)->not->toBeNull()
        ->and($nama->value)->toBe('Universitas Nusa Putra')
        ->and($nama->is_public)->toBeTrue()
        ->and(Setting::where('key', 'libreoffice_path')->value('value'))->toBeNull();
});

it('seeds the placeholder dictionary without the removed fakultas key (D-001)', function () {
    $this->seed();

    expect(PlaceholderDefinition::where('name', 'nama_mahasiswa')->exists())->toBeTrue()
        ->and(PlaceholderDefinition::where('name', 'nomor_surat')->value('kelompok'))->toBe('counter')
        ->and(PlaceholderDefinition::where('name', 'fakultas')->exists())->toBeFalse();
});

it('is idempotent — reseeding does not duplicate production rows', function () {
    $this->seed();
    $this->seed();

    expect(Role::where('name', 'super_admin')->count())->toBe(1)
        ->and(Setting::where('key', 'nama_universitas')->count())->toBe(1)
        ->and(PlaceholderDefinition::where('name', 'nama_mahasiswa')->count())->toBe(1);
});

it('gates a role-protected route using the seeded roles', function () {
    $this->seed();
    Route::middleware(['web', 'auth', 'role:super_admin'])
        ->get('/_smoke/admin', fn () => response('ok'));

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $mahasiswa = User::factory()->create();
    $mahasiswa->assignRole('mahasiswa');

    $this->actingAs($admin)->get('/_smoke/admin')->assertOk();
    $this->actingAs($mahasiswa)->get('/_smoke/admin')->assertForbidden();
});
