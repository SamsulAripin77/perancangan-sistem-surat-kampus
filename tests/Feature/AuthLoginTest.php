<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }
});

// AC1
it('logs in an active admin, redirects to the admin dashboard, and records the activity', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('admin_surat');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(Activity::where('description', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();
});

// AC2
it('rejects an inactive user with a specific message and does not authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('admin_surat');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.inactive'));
    $this->assertGuest();
});

// AC3
it('returns a generic error on wrong credentials without authenticating', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
    $this->assertGuest();
});

// AC4
it('forces a must-change-password user to the change page and blocks other pages', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    $user->assignRole('admin_surat');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('password.force'));

    // Mencoba akses halaman lain → dialihkan kembali ke halaman paksa ganti.
    $this->actingAs($user->fresh())
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('password.force'));
});

// AC5
it('clears must_change_password after the forced change and redirects by role', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    $user->assignRole('mahasiswa');

    $response = $this->actingAs($user)->post(route('password.force.update'), [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertRedirect(route('mahasiswa.beranda'));
    $fresh = $user->fresh();
    expect($fresh->must_change_password)->toBeFalse()
        ->and(Hash::check('new-secure-password', $fresh->password))->toBeTrue();
});

it('records a logout activity', function () {
    $user = User::factory()->create();
    $user->assignRole('admin_surat');

    $this->actingAs($user)->post('/logout')->assertRedirect();

    expect(Activity::where('description', 'logout')->where('causer_id', $user->id)->exists())->toBeTrue();
});
