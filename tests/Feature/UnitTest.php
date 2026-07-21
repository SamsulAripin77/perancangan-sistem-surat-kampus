<?php

use App\Models\Unit;
use App\Models\User;
use Spatie\Permission\Models\Role;

function unitAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

// AC1 — simpan kode unik tersimpan
it('creates a unit with a unique code', function () {
    $response = $this->actingAs(unitAdmin())
        ->from(route('admin.unit.index'))
        ->post(route('admin.unit.store'), [
            'nama' => 'Akademik',
            'kode' => 'AKD',
            'parent_id' => '',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.unit.index'));
    $response->assertSessionHas('success', __('unit.created'));
    $this->assertDatabaseHas('units', ['kode' => 'AKD', 'nama' => 'Akademik', 'is_active' => true]);
});

// AC1 — kode duplikat ditolak inline
it('rejects a duplicate code', function () {
    Unit::factory()->create(['kode' => 'AKD']);

    $this->actingAs(unitAdmin())
        ->from(route('admin.unit.index'))
        ->post(route('admin.unit.store'), ['nama' => 'Lain', 'kode' => 'AKD', 'is_active' => '1'])
        ->assertSessionHasErrors('kode');
});

it('allows updating a unit keeping its own code (unique ignores self)', function () {
    $unit = Unit::factory()->create(['kode' => 'AKD', 'nama' => 'Akademik']);

    $this->actingAs(unitAdmin())
        ->put(route('admin.unit.update', $unit), [
            'nama' => 'Akademik & Kemahasiswaan',
            'kode' => 'AKD',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.unit.index'))
        ->assertSessionHasNoErrors();

    expect($unit->fresh()->nama)->toBe('Akademik & Kemahasiswaan');
});

it('rejects setting a unit as its own parent', function () {
    $unit = Unit::factory()->create();

    $this->actingAs(unitAdmin())
        ->put(route('admin.unit.update', $unit), [
            'nama' => $unit->nama,
            'kode' => $unit->kode,
            'parent_id' => $unit->id,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('parent_id');
});

it('deletes a unit that is not in use', function () {
    $unit = Unit::factory()->create();

    $this->actingAs(unitAdmin())
        ->delete(route('admin.unit.destroy', $unit))
        ->assertSessionHas('success', __('unit.deleted'));

    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
});

// AC2 — unit dipakai (punya sub-unit) tidak boleh dihapus
it('blocks deleting a unit that has sub-units', function () {
    $parent = Unit::factory()->create();
    Unit::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs(unitAdmin())
        ->delete(route('admin.unit.destroy', $parent))
        ->assertSessionHas('error', __('unit.delete_blocked'));

    $this->assertDatabaseHas('units', ['id' => $parent->id]);
});

// AC2 — unit dipakai (punya user) tidak boleh dihapus
it('blocks deleting a unit that has users', function () {
    $unit = Unit::factory()->create();
    User::factory()->create(['unit_id' => $unit->id]);

    $this->actingAs(unitAdmin())
        ->delete(route('admin.unit.destroy', $unit))
        ->assertSessionHas('error', __('unit.delete_blocked'));

    $this->assertDatabaseHas('units', ['id' => $unit->id]);
});

// AC2 — opsi nonaktif tersedia (toggle)
it('deactivates and reactivates a unit via toggle', function () {
    $unit = Unit::factory()->create(['is_active' => true]);

    $this->actingAs(unitAdmin())->patch(route('admin.unit.toggle', $unit))
        ->assertSessionHas('success', __('unit.deactivated'));
    expect($unit->fresh()->is_active)->toBeFalse();

    $this->actingAs(unitAdmin())->patch(route('admin.unit.toggle', $unit))
        ->assertSessionHas('success', __('unit.activated'));
    expect($unit->fresh()->is_active)->toBeTrue();
});

it('renders the index for an admin', function () {
    Unit::factory()->create(['nama' => 'Akademik']);

    $this->actingAs(unitAdmin())->withoutVite()->get(route('admin.unit.index'))
        ->assertOk()
        ->assertSee(__('unit.title'));
});

it('returns filtered DataTables JSON on an ajax request', function () {
    Unit::factory()->create(['nama' => 'Akademik', 'kode' => 'AKD']);
    Unit::factory()->create(['nama' => 'Keuangan', 'kode' => 'KEU']);

    $response = $this->actingAs(unitAdmin())->get(
        route('admin.unit.index', ['q' => 'Akad']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    );

    $response->assertOk()
        ->assertJsonFragment(['kode' => 'AKD'])
        ->assertJsonMissing(['kode' => 'KEU']);
});

it('forbids non-admin users from managing units', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    $this->actingAs($user)->get(route('admin.unit.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.unit.store'), [
        'nama' => 'X', 'kode' => 'X1', 'is_active' => '1',
    ])->assertForbidden();
});
