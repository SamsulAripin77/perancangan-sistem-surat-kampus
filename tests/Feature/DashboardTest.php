<?php

use App\Models\User;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;

function dashAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

function dashMahasiswa(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    return $user;
}

// AC2 — deadline hari kerja: Kamis + 3 hari kerja → Selasa (skip Sabtu/Minggu)
it('computes the working-day deadline skipping weekends', function () {
    $thursday = CarbonImmutable::parse('2024-01-04'); // Kamis
    expect($thursday->isThursday())->toBeTrue();

    $deadline = WorkingDays::deadline($thursday, 3);

    expect($deadline->toDateString())->toBe('2024-01-09') // Selasa
        ->and($deadline->isTuesday())->toBeTrue();
});

it('skips the weekend for a Friday + 1 working day', function () {
    $friday = CarbonImmutable::parse('2024-01-05'); // Jumat
    expect($friday->isFriday())->toBeTrue();

    expect(WorkingDays::deadline($friday, 1)->toDateString())->toBe('2024-01-08'); // Senin
});

it('returns the same day for zero working days', function () {
    $monday = CarbonImmutable::parse('2024-01-08');

    expect(WorkingDays::deadline($monday, 0)->toDateString())->toBe('2024-01-08');
});

it('flags overdue and approaching deadlines', function () {
    $today = CarbonImmutable::parse('2024-01-10');

    expect(WorkingDays::isOverdue(CarbonImmutable::parse('2024-01-09'), $today))->toBeTrue()
        ->and(WorkingDays::isOverdue(CarbonImmutable::parse('2024-01-11'), $today))->toBeFalse()
        ->and(WorkingDays::isApproaching(CarbonImmutable::parse('2024-01-11'), $today))->toBeTrue()
        ->and(WorkingDays::isApproaching(CarbonImmutable::parse('2024-01-15'), $today))->toBeFalse();
});

// AC1 (parsial) — dashboard admin render + kartu tampil 0 (tabel sumber belum ada)
it('renders the admin dashboard with zero stat cards', function () {
    $this->actingAs(dashAdmin())->withoutVite()->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.pending'))
        ->assertSee(__('dashboard.overdue'));
});

// AC3 — mahasiswa tanpa permohonan → ajakan "Ajukan surat pertama"
it('shows the empty state on the mahasiswa beranda', function () {
    $this->actingAs(dashMahasiswa())->withoutVite()->get(route('mahasiswa.beranda'))
        ->assertOk()
        ->assertSee(__('dashboard.empty_mahasiswa'))
        ->assertSee(__('dashboard.ajukan_pertama'));
});

it('gates dashboards by role', function () {
    $this->actingAs(dashMahasiswa())->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(dashAdmin())->get(route('mahasiswa.beranda'))->assertForbidden();
});
