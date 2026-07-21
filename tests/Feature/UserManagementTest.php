<?php

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

function superAdmin(): User
{
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

// AC1 — role=mahasiswa → users + mahasiswa (1-1) + role ter-assign
it('creates a mahasiswa user with a 1-1 profile and role', function () {
    $response = $this->actingAs(superAdmin())
        ->from(route('admin.user.index'))
        ->post(route('admin.user.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => 'secret-password',
            'role' => 'mahasiswa',
            'nim' => '20210001',
            'prodi' => 'Informatika',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.user.index'));
    $response->assertSessionHas('success', __('user.created'));

    $user = User::query()->where('email', 'budi@example.test')->firstOrFail();
    expect($user->hasRole('mahasiswa'))->toBeTrue()
        ->and($user->mahasiswa)->not->toBeNull()
        ->and($user->mahasiswa->nim)->toBe('20210001')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
    $this->assertDatabaseCount('mahasiswa', 1);
});

it('creates a non-mahasiswa user without a mahasiswa profile', function () {
    $this->actingAs(superAdmin())->post(route('admin.user.store'), [
        'name' => 'Dewi Admin',
        'email' => 'dewi@example.test',
        'password' => 'secret-password',
        'role' => 'admin_surat',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'dewi@example.test')->firstOrFail();
    expect($user->hasRole('admin_surat'))->toBeTrue()
        ->and($user->mahasiswa)->toBeNull();
    $this->assertDatabaseCount('mahasiswa', 0);
});

// AC2 — email duplikat ditolak inline
it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'ada@example.test']);

    $this->actingAs(superAdmin())
        ->from(route('admin.user.index'))
        ->post(route('admin.user.store'), [
            'name' => 'X', 'email' => 'ada@example.test', 'password' => 'secret-password',
            'role' => 'admin_surat', 'is_active' => '1',
        ])
        ->assertSessionHasErrors('email');
});

// AC2 — NIM duplikat ditolak inline
it('rejects a duplicate NIM for a mahasiswa', function () {
    $existing = User::factory()->create();
    Mahasiswa::factory()->for($existing)->create(['nim' => '20210001']);

    $this->actingAs(superAdmin())
        ->from(route('admin.user.index'))
        ->post(route('admin.user.store'), [
            'name' => 'Y', 'email' => 'y@example.test', 'password' => 'secret-password',
            'role' => 'mahasiswa', 'nim' => '20210001', 'prodi' => 'Informatika', 'is_active' => '1',
        ])
        ->assertSessionHasErrors('nim');
});

it('requires nim and prodi only when the role is mahasiswa', function () {
    $this->actingAs(superAdmin())
        ->from(route('admin.user.index'))
        ->post(route('admin.user.store'), [
            'name' => 'Tanpa NIM', 'email' => 'z@example.test', 'password' => 'secret-password',
            'role' => 'mahasiswa', 'is_active' => '1',
        ])
        ->assertSessionHasErrors(['nim', 'prodi']);
});

it('requires a password on create but keeps it optional on edit', function () {
    // Create tanpa password → error.
    $this->actingAs(superAdmin())
        ->from(route('admin.user.index'))
        ->post(route('admin.user.store'), [
            'name' => 'No Pass', 'email' => 'np@example.test',
            'role' => 'admin_surat', 'is_active' => '1',
        ])
        ->assertSessionHasErrors('password');

    // Edit tanpa password → tetap valid, password lama dipertahankan.
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $user->assignRole('admin_surat');

    $this->actingAs(superAdmin())->put(route('admin.user.update', $user), [
        'name' => 'Updated', 'email' => $user->email,
        'role' => 'admin_surat', 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('has no destroy route (D-005 no hard delete)', function () {
    expect(Route::has('admin.user.destroy'))->toBeFalse();
});

it('toggles a user active state but blocks self-deactivation', function () {
    $admin = superAdmin();
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('admin_surat');

    $this->actingAs($admin)->patch(route('admin.user.toggle', $target))
        ->assertSessionHas('success', __('user.deactivated'));
    expect($target->fresh()->is_active)->toBeFalse();

    // Tidak boleh menonaktifkan diri sendiri.
    $this->actingAs($admin)->patch(route('admin.user.toggle', $admin))
        ->assertSessionHas('error', __('user.self_toggle_blocked'));
    expect($admin->fresh()->is_active)->toBeTrue();
});

// AC3 — non-super-admin → 403
it('forbids non-super-admin users from managing users', function () {
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $adminSurat = User::factory()->create();
    $adminSurat->assignRole('admin_surat');

    $this->actingAs($adminSurat)->get(route('admin.user.index'))->assertForbidden();
    $this->actingAs($adminSurat)->post(route('admin.user.store'), [
        'name' => 'X', 'email' => 'x@example.test', 'password' => 'secret-password',
        'role' => 'admin_surat', 'is_active' => '1',
    ])->assertForbidden();
});

it('returns filtered DataTables JSON on an ajax request', function () {
    $admin = superAdmin();
    $mhs = User::factory()->create(['name' => 'Mahasiswa Satu']);
    $mhs->assignRole('mahasiswa');
    Mahasiswa::factory()->for($mhs)->create(['nim' => '20219999']);

    $this->actingAs($admin)->get(
        route('admin.user.index', ['role' => 'mahasiswa']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    )
        ->assertOk()
        ->assertJsonFragment(['nim' => '20219999']);
});
