<?php

use App\Models\PlaceholderDefinition;
use App\Models\User;
use Spatie\Permission\Models\Role;

function kamusAdmin(): User
{
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

// AC1 — name unik + snake_case tersimpan
it('creates a placeholder with a unique snake_case name', function () {
    $response = $this->actingAs(kamusAdmin())
        ->from(route('admin.kamus.index'))
        ->post(route('admin.kamus.store'), [
            'name' => 'nama_pembimbing',
            'kelompok' => 'profil',
            'input_type' => 'text',
            'source' => 'mahasiswa.pembimbing',
            'is_overridable' => '1',
        ]);

    $response->assertRedirect(route('admin.kamus.index'));
    $response->assertSessionHas('success', __('kamus.created'));
    $this->assertDatabaseHas('placeholder_definitions', [
        'name' => 'nama_pembimbing', 'kelompok' => 'profil', 'is_overridable' => true,
    ]);
});

// AC1 — name duplikat ditolak
it('rejects a duplicate name', function () {
    PlaceholderDefinition::factory()->create(['name' => 'nama_mahasiswa']);

    $this->actingAs(kamusAdmin())
        ->from(route('admin.kamus.index'))
        ->post(route('admin.kamus.store'), [
            'name' => 'nama_mahasiswa', 'kelompok' => 'profil', 'input_type' => 'text', 'is_overridable' => '1',
        ])
        ->assertSessionHasErrors('name');
});

// AC1 — name non-snake_case ditolak
it('rejects a non snake_case name', function () {
    $this->actingAs(kamusAdmin())
        ->from(route('admin.kamus.index'))
        ->post(route('admin.kamus.store'), [
            'name' => 'NamaMahasiswa', 'kelompok' => 'profil', 'input_type' => 'text', 'is_overridable' => '1',
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs(kamusAdmin())
        ->from(route('admin.kamus.index'))
        ->post(route('admin.kamus.store'), [
            'name' => 'nama mahasiswa', 'kelompok' => 'profil', 'input_type' => 'text', 'is_overridable' => '1',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects an invalid kelompok or input_type', function () {
    $this->actingAs(kamusAdmin())
        ->from(route('admin.kamus.index'))
        ->post(route('admin.kamus.store'), [
            'name' => 'valid_name', 'kelompok' => 'ngawur', 'input_type' => 'xyz', 'is_overridable' => '1',
        ])
        ->assertSessionHasErrors(['kelompok', 'input_type']);
});

it('updates a placeholder keeping its own name (unique ignores self)', function () {
    $item = PlaceholderDefinition::factory()->create(['name' => 'tahun_surat', 'input_type' => 'text']);

    $this->actingAs(kamusAdmin())->put(route('admin.kamus.update', $item), [
        'name' => 'tahun_surat',
        'kelompok' => 'waktu',
        'input_type' => 'date',
        'is_overridable' => '0',
    ])->assertSessionHasNoErrors();

    expect($item->fresh()->input_type)->toBe('date')
        ->and($item->fresh()->is_overridable)->toBeFalse();
});

it('deletes a placeholder', function () {
    $item = PlaceholderDefinition::factory()->create();

    $this->actingAs(kamusAdmin())
        ->delete(route('admin.kamus.destroy', $item))
        ->assertSessionHas('success', __('kamus.deleted'));

    $this->assertDatabaseMissing('placeholder_definitions', ['id' => $item->id]);
});

it('renders the index for a super admin', function () {
    $this->actingAs(kamusAdmin())->withoutVite()->get(route('admin.kamus.index'))
        ->assertOk()
        ->assertSee(__('kamus.title'));
});

it('returns filtered DataTables JSON on an ajax request', function () {
    PlaceholderDefinition::factory()->create(['name' => 'nama_mahasiswa', 'kelompok' => 'profil']);
    PlaceholderDefinition::factory()->create(['name' => 'tanggal_surat', 'kelompok' => 'waktu']);

    $this->actingAs(kamusAdmin())->get(
        route('admin.kamus.index', ['kelompok' => 'profil']),
        ['X-Requested-With' => 'XMLHttpRequest'],
    )
        ->assertOk()
        ->assertJsonFragment(['name' => 'nama_mahasiswa'])
        ->assertJsonMissing(['name' => 'tanggal_surat']);
});

// AC (guardrail) — khusus Super Admin
it('forbids non-super-admin users from managing the kamus', function () {
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $adminSurat = User::factory()->create();
    $adminSurat->assignRole('admin_surat');

    $this->actingAs($adminSurat)->get(route('admin.kamus.index'))->assertForbidden();
    $this->actingAs($adminSurat)->post(route('admin.kamus.store'), [
        'name' => 'x_name', 'kelompok' => 'profil', 'input_type' => 'text', 'is_overridable' => '1',
    ])->assertForbidden();
});
