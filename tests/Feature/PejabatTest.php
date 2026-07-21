<?php

use App\Models\Pejabat;
use App\Models\Unit;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function pejabatAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

// AC1 — pejabat + 2 unit → pejabat_unit berisi 2 baris
it('creates a pejabat assigned to two units', function () {
    $units = Unit::factory()->count(2)->create();

    $response = $this->actingAs(pejabatAdmin())
        ->from(route('admin.pejabat.index'))
        ->post(route('admin.pejabat.store'), [
            'nama' => 'Dr. Ahmad',
            'jabatan' => 'Kaprodi Informatika',
            'unit_ids' => $units->pluck('id')->all(),
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.pejabat.index'));
    $response->assertSessionHas('success', __('pejabat.created'));

    $pejabat = Pejabat::query()->where('nama', 'Dr. Ahmad')->firstOrFail();
    expect($pejabat->units)->toHaveCount(2);
    $this->assertDatabaseCount('pejabat_unit', 2);
});

// AC2 — tanpa TTD valid; indikator TTD "—"
it('creates a pejabat without a TTD (valid, indicator dash)', function () {
    $unit = Unit::factory()->create();

    $this->actingAs(pejabatAdmin())->post(route('admin.pejabat.store'), [
        'nama' => 'Dr. Siti',
        'jabatan' => 'Dekan',
        'unit_ids' => [$unit->id],
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $pejabat = Pejabat::query()->where('nama', 'Dr. Siti')->firstOrFail();
    expect($pejabat->hasTtd())->toBeFalse();
});

// AC3 — upload TTD → collection 'ttd' disk private
it('attaches the uploaded TTD to the private ttd collection', function () {
    Storage::fake('private');
    $unit = Unit::factory()->create();
    $token = app(MediaService::class)->storeTemporary(UploadedFile::fake()->image('ttd.png'));

    $this->actingAs(pejabatAdmin())->post(route('admin.pejabat.store'), [
        'nama' => 'Dr. Budi',
        'jabatan' => 'Kepala BAA',
        'unit_ids' => [$unit->id],
        'ttd_token' => $token,
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $pejabat = Pejabat::query()->where('nama', 'Dr. Budi')->firstOrFail();
    $media = $pejabat->getFirstMedia('ttd');

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('private')
        ->and($pejabat->hasTtd())->toBeTrue();
});

it('requires at least one unit', function () {
    $this->actingAs(pejabatAdmin())
        ->from(route('admin.pejabat.index'))
        ->post(route('admin.pejabat.store'), [
            'nama' => 'Tanpa Unit',
            'jabatan' => 'X',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('unit_ids');
});

it('rejects an invalid email', function () {
    $unit = Unit::factory()->create();

    $this->actingAs(pejabatAdmin())
        ->from(route('admin.pejabat.index'))
        ->post(route('admin.pejabat.store'), [
            'nama' => 'Salah Email',
            'jabatan' => 'X',
            'email' => 'bukan-email',
            'unit_ids' => [$unit->id],
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('email');
});

it('syncs units on update (replaces the set)', function () {
    $pejabat = Pejabat::factory()->create();
    $old = Unit::factory()->create();
    $pejabat->units()->attach($old);
    $new = Unit::factory()->count(2)->create();

    $this->actingAs(pejabatAdmin())->put(route('admin.pejabat.update', $pejabat), [
        'nama' => $pejabat->nama,
        'jabatan' => $pejabat->jabatan,
        'unit_ids' => $new->pluck('id')->all(),
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect($pejabat->fresh()->units->pluck('id')->sort()->values()->all())
        ->toBe($new->pluck('id')->sort()->values()->all());
});

it('deletes a pejabat and detaches its units', function () {
    $pejabat = Pejabat::factory()->create();
    $pejabat->units()->attach(Unit::factory()->create());

    $this->actingAs(pejabatAdmin())
        ->delete(route('admin.pejabat.destroy', $pejabat))
        ->assertSessionHas('success', __('pejabat.deleted'));

    $this->assertDatabaseMissing('pejabat', ['id' => $pejabat->id]);
    $this->assertDatabaseCount('pejabat_unit', 0);
});

it('deactivates and reactivates a pejabat via toggle', function () {
    $pejabat = Pejabat::factory()->create(['is_active' => true]);

    $this->actingAs(pejabatAdmin())->patch(route('admin.pejabat.toggle', $pejabat))
        ->assertSessionHas('success', __('pejabat.deactivated'));
    expect($pejabat->fresh()->is_active)->toBeFalse();

    $this->actingAs(pejabatAdmin())->patch(route('admin.pejabat.toggle', $pejabat))
        ->assertSessionHas('success', __('pejabat.activated'));
    expect($pejabat->fresh()->is_active)->toBeTrue();
});

it('returns filtered DataTables JSON on an ajax request', function () {
    $unitA = Unit::factory()->create();
    $unitB = Unit::factory()->create();
    Pejabat::factory()->create(['nama' => 'Ahmad'])->units()->attach($unitA);
    Pejabat::factory()->create(['nama' => 'Siti'])->units()->attach($unitB);

    $this->actingAs(pejabatAdmin())->get(
        route('admin.pejabat.index', ['unit' => $unitA->id]),
        ['X-Requested-With' => 'XMLHttpRequest'],
    )
        ->assertOk()
        ->assertJsonFragment(['nama' => 'Ahmad'])
        ->assertJsonMissing(['nama' => 'Siti']);
});

it('renders the index for an admin', function () {
    $this->actingAs(pejabatAdmin())->withoutVite()->get(route('admin.pejabat.index'))
        ->assertOk()
        ->assertSee(__('pejabat.title'));
});

it('forbids non-admin users from managing pejabat', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    $this->actingAs($user)->get(route('admin.pejabat.index'))->assertForbidden();
});
