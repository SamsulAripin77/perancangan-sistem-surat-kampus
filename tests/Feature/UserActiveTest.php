<?php

use App\Models\User;

// Kolom is_active ditarik maju dari M1-T1 (M0-T8). Penegakan is_active saat
// login adalah M1-T2 — di sini hanya verifikasi skema & cast tersedia.
it('casts is_active to boolean and defaults to active', function () {
    $user = User::factory()->create();

    expect($user->fresh()->is_active)->toBeTrue();
});

it('can store an inactive user', function () {
    $user = User::factory()->create(['is_active' => false]);

    expect($user->fresh()->is_active)->toBeFalse();
});
