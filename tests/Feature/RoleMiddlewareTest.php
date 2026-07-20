<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:admin_surat'])
        ->get('/_test/admin-area', fn () => response('ok'));
});

it('registers the spatie role middleware alias and allows a user with the role', function () {
    Role::create(['name' => 'admin_surat']);
    $user = User::factory()->create();
    $user->assignRole('admin_surat');

    $this->actingAs($user)->get('/_test/admin-area')->assertOk();
});

it('forbids a user without the required role', function () {
    Role::create(['name' => 'admin_surat']);
    Role::create(['name' => 'mahasiswa']);
    $user = User::factory()->create();
    $user->assignRole('mahasiswa');

    $this->actingAs($user)->get('/_test/admin-area')->assertForbidden();
});
