<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// AC1
it('updates the password with a correct current password and flashes success', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $response = $this->actingAs($user)->from(route('password.edit'))->put(route('user-password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertRedirect(route('password.edit'));
    $response->assertSessionHas('success', __('auth.password_updated'));
    expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
});

// AC2
it('rejects a wrong current password with a localized error and leaves the password unchanged', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $response = $this->actingAs($user)->from(route('password.edit'))->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertSessionHasErrors(['current_password'], null, 'updatePassword');
    expect(session('errors')->getBag('updatePassword')->first('current_password'))
        ->toBe(__('auth.current_password_wrong'));
    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('renders the self-service ubah password page for an authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    $this->actingAs($user)->withoutVite()->get(route('password.edit'))
        ->assertOk()
        ->assertSee(__('auth.ubah_password_title'));
});
