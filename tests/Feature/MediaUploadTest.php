<?php

use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Model sekadar untuk menguji MediaService (memakai tabel users yang ada). */
class TestMediaSubject extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'users';

    protected $guarded = [];
}

function makeSubject(): TestMediaSubject
{
    return TestMediaSubject::create([
        'name' => 'Subjek',
        'email' => uniqid().'@example.test',
        'password' => 'secret',
    ]);
}

it('attaches an uploaded file to a media collection via MediaService', function () {
    Storage::fake('public');

    $media = app(MediaService::class)->attach(
        makeSubject(),
        UploadedFile::fake()->image('Foto Profil.jpg'),
        'berkas',
    );

    expect($media->collection_name)->toBe('berkas')
        ->and($media->file_name)->toBe('foto-profil.jpg');
});

it('stores a temporary file then attaches it from temp, cleaning up', function () {
    Storage::fake('private');
    Storage::fake('public');
    $service = app(MediaService::class);

    $token = $service->storeTemporary(UploadedFile::fake()->create('Berkas Uji.pdf', 20));
    expect($service->temporaryFilePath($token))->not->toBeNull();

    $media = $service->attachFromTemporary(makeSubject(), $token, 'berkas');

    expect($media)->not->toBeNull()
        ->and($media->collection_name)->toBe('berkas')
        ->and($service->temporaryFilePath($token))->toBeNull();
});

it('ignores an invalid temp token (no path traversal)', function () {
    $service = app(MediaService::class);

    $service->deleteTemporary('../../etc'); // must not throw / must be a no-op

    expect($service->temporaryFilePath('../../etc/passwd'))->toBeNull();
});

it('rejects an unauthenticated upload', function () {
    $this->post('/upload')->assertRedirect(route('login'));
});

it('stores a temp file via the process endpoint and reverts it', function () {
    Storage::fake('private');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/upload', [
        'file' => UploadedFile::fake()->create('doc.pdf', 15),
    ]);

    $response->assertOk();
    $token = $response->getContent();
    expect(app(MediaService::class)->temporaryFilePath($token))->not->toBeNull();

    $this->actingAs($user)
        ->call('DELETE', '/upload', [], [], [], [], $token)
        ->assertNoContent();

    expect(app(MediaService::class)->temporaryFilePath($token))->toBeNull();
});

it('returns 422 from the process endpoint when no file is provided', function () {
    $this->actingAs(User::factory()->create())
        ->post('/upload')
        ->assertStatus(422);
});
