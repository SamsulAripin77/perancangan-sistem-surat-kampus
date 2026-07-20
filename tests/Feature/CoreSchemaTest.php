<?php

use App\Models\Mahasiswa;
use App\Models\Pejabat;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('creates all core tables on migrate', function () {
    foreach (['units', 'users', 'mahasiswa', 'pejabat', 'pejabat_unit'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::hasColumns('users', ['unit_id', 'is_active', 'must_change_password', 'deleted_at']))->toBeTrue()
        ->and(Schema::hasColumn('users', 'role'))->toBeFalse();
});

it('links User and Mahasiswa one-to-one both ways', function () {
    $user = User::factory()->mahasiswa()->create();

    expect($user->mahasiswa)->toBeInstanceOf(Mahasiswa::class)
        ->and($user->mahasiswa->user->is($user))->toBeTrue();
});

it('attaches a pejabat to multiple units (n-n)', function () {
    $pejabat = Pejabat::factory()->create();
    $units = Unit::factory()->count(2)->create();

    $pejabat->units()->attach($units);

    expect($pejabat->units)->toHaveCount(2);
});

it('resolves the unit self-reference hierarchy', function () {
    $parent = Unit::factory()->create();
    $child = Unit::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->pluck('id'))->toContain($child->id);
});

it('enforces unique constraints on kode, nim, and email', function () {
    $unit = Unit::factory()->create(['kode' => 'BAA']);
    expect(fn () => Unit::factory()->create(['kode' => 'BAA']))->toThrow(QueryException::class);

    $mhs = Mahasiswa::factory()->create(['nim' => '2021001']);
    expect(fn () => Mahasiswa::factory()->create(['nim' => '2021001']))->toThrow(QueryException::class);

    User::factory()->create(['email' => 'dup@example.test']);
    expect(fn () => User::factory()->create(['email' => 'dup@example.test']))->toThrow(QueryException::class);
});

it('enforces the one-to-one user_id uniqueness on mahasiswa', function () {
    $user = User::factory()->create();
    Mahasiswa::factory()->create(['user_id' => $user->id]);

    expect(fn () => Mahasiswa::factory()->create(['user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

it('soft deletes a user', function () {
    $user = User::factory()->create();
    $user->delete();

    expect(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(User::find($user->id))->toBeNull();
});

it('produces valid records from every core factory', function () {
    expect(Unit::factory()->create())->toBeInstanceOf(Unit::class)
        ->and(User::factory()->create())->toBeInstanceOf(User::class)
        ->and(Mahasiswa::factory()->create())->toBeInstanceOf(Mahasiswa::class)
        ->and(Pejabat::factory()->create())->toBeInstanceOf(Pejabat::class);
});
