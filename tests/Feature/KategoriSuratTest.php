<?php

use App\Models\KategoriSurat;
use App\Models\User;
use Spatie\Permission\Models\Role;

function kategoriAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

// AC — kategori baru tersimpan (muncul untuk dropdown template M2)
it('creates a kategori with a unique name', function () {
    $response = $this->actingAs(kategoriAdmin())
        ->from(route('admin.kategori.index'))
        ->post(route('admin.kategori.store'), [
            'nama' => 'Layanan Mahasiswa',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.kategori.index'));
    $response->assertSessionHas('success', __('kategori.created'));
    $this->assertDatabaseHas('kategori_surat', ['nama' => 'Layanan Mahasiswa', 'is_active' => true]);
});

it('rejects a duplicate name', function () {
    KategoriSurat::factory()->create(['nama' => 'Administrasi Internal']);

    $this->actingAs(kategoriAdmin())
        ->from(route('admin.kategori.index'))
        ->post(route('admin.kategori.store'), ['nama' => 'Administrasi Internal', 'is_active' => '1'])
        ->assertSessionHasErrors('nama');
});

it('updates a kategori keeping its own name (unique ignores self)', function () {
    $kategori = KategoriSurat::factory()->create(['nama' => 'SK', 'is_active' => true]);

    $this->actingAs(kategoriAdmin())->put(route('admin.kategori.update', $kategori), [
        'nama' => 'SK',
        'is_active' => '0',
    ])->assertSessionHasNoErrors();

    expect($kategori->fresh()->is_active)->toBeFalse();
});

// AC — hapus kategori yang tidak dipakai
it('deletes a kategori that is not in use', function () {
    $kategori = KategoriSurat::factory()->create();

    $this->actingAs(kategoriAdmin())
        ->delete(route('admin.kategori.destroy', $kategori))
        ->assertSessionHas('success', __('kategori.deleted'));

    $this->assertDatabaseMissing('kategori_surat', ['id' => $kategori->id]);
});

// AC — opsi nonaktif tersedia (toggle)
it('deactivates and reactivates a kategori via toggle', function () {
    $kategori = KategoriSurat::factory()->create(['is_active' => true]);

    $this->actingAs(kategoriAdmin())->patch(route('admin.kategori.toggle', $kategori))
        ->assertSessionHas('success', __('kategori.deactivated'));
    expect($kategori->fresh()->is_active)->toBeFalse();

    $this->actingAs(kategoriAdmin())->patch(route('admin.kategori.toggle', $kategori))
        ->assertSessionHas('success', __('kategori.activated'));
    expect($kategori->fresh()->is_active)->toBeTrue();
});

it('renders the index for an admin', function () {
    $this->actingAs(kategoriAdmin())->withoutVite()->get(route('admin.kategori.index'))
        ->assertOk()
        ->assertSee(__('kategori.title'));
});

it('returns filtered DataTables JSON on an ajax request', function () {
    KategoriSurat::factory()->create(['nama' => 'Layanan Mahasiswa', 'is_active' => true]);
    KategoriSurat::factory()->create(['nama' => 'Arsip Lama', 'is_active' => false]);

    $this->actingAs(kategoriAdmin())->get(
        route('admin.kategori.index', ['status' => '1']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    )
        ->assertOk()
        ->assertJsonFragment(['nama' => 'Layanan Mahasiswa'])
        ->assertJsonMissing(['nama' => 'Arsip Lama']);
});

it('forbids non-admin users from managing kategori', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    $this->actingAs($user)->get(route('admin.kategori.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.kategori.store'), [
        'nama' => 'X', 'is_active' => '1',
    ])->assertForbidden();
});
