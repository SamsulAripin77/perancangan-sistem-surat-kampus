<?php

use App\Models\RefSyaratSurat;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function syaratAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

it('creates a persyaratan with a unique name', function () {
    $response = $this->actingAs(syaratAdmin())
        ->from(route('admin.persyaratan.index'))
        ->post(route('admin.persyaratan.store'), [
            'nama' => 'Fotokopi KHS',
            'deskripsi' => 'KHS terakhir',
            'accepted_types' => 'pdf,jpg',
            'max_size_mb' => 5,
        ]);

    $response->assertRedirect(route('admin.persyaratan.index'));
    $response->assertSessionHas('success', __('syarat.created'));
    $this->assertDatabaseHas('ref_syarat_surat', ['nama' => 'Fotokopi KHS', 'accepted_types' => 'pdf,jpg']);
});

it('rejects a duplicate name', function () {
    RefSyaratSurat::factory()->create(['nama' => 'Fotokopi KTM']);

    $this->actingAs(syaratAdmin())
        ->from(route('admin.persyaratan.index'))
        ->post(route('admin.persyaratan.store'), [
            'nama' => 'Fotokopi KTM', 'accepted_types' => 'pdf', 'max_size_mb' => 5,
        ])
        ->assertSessionHasErrors('nama');
});

it('rejects an invalid accepted_types format', function () {
    $this->actingAs(syaratAdmin())
        ->from(route('admin.persyaratan.index'))
        ->post(route('admin.persyaratan.store'), [
            'nama' => 'Sesuatu', 'accepted_types' => 'pdf; jpg!', 'max_size_mb' => 5,
        ])
        ->assertSessionHasErrors('accepted_types');
});

// AC1 — persyaratan + file contoh → file tersimpan (collection template private)
it('attaches an example file to the private template collection', function () {
    Storage::fake('private');
    $token = app(MediaService::class)->storeTemporary(UploadedFile::fake()->create('contoh.pdf', 20));

    $this->actingAs(syaratAdmin())->post(route('admin.persyaratan.store'), [
        'nama' => 'Surat Pernyataan',
        'accepted_types' => 'pdf',
        'max_size_mb' => 5,
        'template_token' => $token,
    ])->assertSessionHasNoErrors();

    $syarat = RefSyaratSurat::query()->where('nama', 'Surat Pernyataan')->firstOrFail();
    $media = $syarat->getFirstMedia('template');

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('private')
        ->and($syarat->hasTemplateFile())->toBeTrue();
});

// AC1 — tombol unduh tersedia (route download menyajikan file)
it('serves the example file via the download route', function () {
    Storage::fake('private');
    $syarat = RefSyaratSurat::factory()->create();
    app(MediaService::class); // ensure bound
    $syarat->addMedia(UploadedFile::fake()->create('contoh.pdf', 20))->toMediaCollection('template');

    $this->actingAs(syaratAdmin())
        ->get(route('admin.persyaratan.download', $syarat))
        ->assertOk()
        ->assertDownload('contoh.pdf');
});

it('returns 404 when downloading a persyaratan without a file', function () {
    $syarat = RefSyaratSurat::factory()->create();

    $this->actingAs(syaratAdmin())
        ->get(route('admin.persyaratan.download', $syarat))
        ->assertNotFound();
});

it('deletes a persyaratan that is not in use', function () {
    $syarat = RefSyaratSurat::factory()->create();

    $this->actingAs(syaratAdmin())
        ->delete(route('admin.persyaratan.destroy', $syarat))
        ->assertSessionHas('success', __('syarat.deleted'));

    $this->assertDatabaseMissing('ref_syarat_surat', ['id' => $syarat->id]);
});

it('updates a persyaratan keeping its own name', function () {
    $syarat = RefSyaratSurat::factory()->create(['nama' => 'Fotokopi KHS', 'max_size_mb' => 5]);

    $this->actingAs(syaratAdmin())->put(route('admin.persyaratan.update', $syarat), [
        'nama' => 'Fotokopi KHS',
        'accepted_types' => 'pdf',
        'max_size_mb' => 10,
    ])->assertSessionHasNoErrors();

    expect($syarat->fresh()->max_size_mb)->toBe(10);
});

it('renders the index for an admin', function () {
    $this->actingAs(syaratAdmin())->withoutVite()->get(route('admin.persyaratan.index'))
        ->assertOk()
        ->assertSee(__('syarat.title'));
});

it('forbids non-admin users from managing persyaratan', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    $this->actingAs($user)->get(route('admin.persyaratan.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.persyaratan.store'), [
        'nama' => 'X', 'accepted_types' => 'pdf', 'max_size_mb' => 5,
    ])->assertForbidden();
});
