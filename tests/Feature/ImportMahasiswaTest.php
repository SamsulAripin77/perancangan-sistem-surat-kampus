<?php

use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\MahasiswaImportService;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function ensureRoles(): void
{
    foreach (['super_admin', 'admin_surat', 'mahasiswa'] as $role) {
        Role::findOrCreate($role, 'web');
    }
}

function superAdminImport(): User
{
    ensureRoles();
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

/**
 * @param  list<string>  $lines
 */
function csvPath(array $lines): string
{
    $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
    file_put_contents($path, implode("\n", $lines));

    return $path;
}

/**
 * @param  list<string>  $lines
 */
function csvUpload(array $lines): UploadedFile
{
    return new UploadedFile(csvPath($lines), 'import.csv', 'text/csv', null, true);
}

const HEADER = 'nim,nama,email,prodi';

// AC1 — pratinjau menandai baris valid/invalid sebelum commit
it('marks each row valid or invalid on analyze', function () {
    ensureRoles();
    $service = app(MahasiswaImportService::class);

    $rows = $service->rows(csvPath([
        HEADER,
        '20210001,Budi Santoso,budi@example.ac.id,Informatika',
        '20210002,Tanpa Email,,Manajemen',          // invalid: email kosong
        '20210003,Email Salah,bukan-email,Akuntansi', // invalid: email tak valid
    ]));

    $analysis = $service->analyze($rows);

    expect($analysis['valid'])->toBe(1)
        ->and($analysis['skipped'])->toBe(2)
        ->and($analysis['rows'][0]['status'])->toBe('valid')
        ->and($analysis['rows'][1]['status'])->toBe('invalid')
        ->and($analysis['rows'][2]['status'])->toBe('invalid');
});

// AC2 — baris dengan email/nim sudah ada di-skip (bukan update)
it('skips rows whose email or nim already exists and leaves them untouched', function () {
    ensureRoles();
    $existing = User::factory()->create(['email' => 'ada@example.ac.id', 'name' => 'Lama']);
    Mahasiswa::factory()->for($existing)->create(['nim' => '20200001']);
    $originalPassword = $existing->password;

    $service = app(MahasiswaImportService::class);
    $result = $service->import(csvPath([
        HEADER,
        '20200001,Duplikat NIM,beda@example.ac.id,Informatika', // nim dup
        '20219999,Duplikat Email,ada@example.ac.id,Manajemen',  // email dup
        '20210050,Baru Valid,baru@example.ac.id,Akuntansi',     // valid
    ]));

    expect($result['imported'])->toBe(1)
        ->and($result['skipped'])->toBe(2)
        ->and($existing->fresh()->password)->toBe($originalPassword)
        ->and($existing->fresh()->name)->toBe('Lama');

    $this->assertDatabaseHas('users', ['email' => 'baru@example.ac.id']);
    $this->assertDatabaseMissing('users', ['email' => 'beda@example.ac.id']);
});

// AC3 — mahasiswa baru: must_change_password=true + password bcrypt; tanpa expose
it('creates mahasiswa with a hashed random password and forced change', function () {
    ensureRoles();
    $service = app(MahasiswaImportService::class);

    $result = $service->import(csvPath([
        HEADER,
        '20210001,Budi Santoso,budi@example.ac.id,Informatika',
    ]));

    expect($result['imported'])->toBe(1);

    $user = User::query()->where('email', 'budi@example.ac.id')->firstOrFail();
    expect($user->hasRole('mahasiswa'))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->mahasiswa->nim)->toBe('20210001')
        ->and($user->mahasiswa->prodi)->toBe('Informatika')
        ->and(str_starts_with($user->password, '$2y$'))->toBeTrue();
});

it('renders a preview with per-row status via the preview endpoint', function () {
    Storage::fake('private');

    $this->actingAs(superAdminImport())
        ->post(route('admin.user.import.preview'), [
            'file' => csvUpload([
                HEADER,
                '20210001,Budi,budi@example.ac.id,Informatika',
                '20210002,Tanpa Email,,Manajemen',
            ]),
        ])
        ->assertOk()
        ->assertSee(__('import.status_valid'))
        ->assertSee(__('import.status_invalid'));
});

it('commits the import via the store endpoint and reports a summary', function () {
    Storage::fake('private');
    $admin = superAdminImport();

    $token = app(MediaService::class)->storeTemporary(csvUpload([
        HEADER,
        '20210001,Budi Santoso,budi@example.ac.id,Informatika',
        '20210002,Ani Lestari,ani@example.ac.id,Manajemen',
    ]));

    $response = $this->actingAs($admin)->post(route('admin.user.import.store'), ['token' => $token]);

    $response->assertRedirect(route('admin.user.index'));
    $response->assertSessionHas('success', __('import.summary', ['imported' => 2, 'skipped' => 0]));

    $this->assertDatabaseCount('mahasiswa', 2);
    $this->assertDatabaseHas('users', ['email' => 'budi@example.ac.id', 'must_change_password' => true]);
});

it('downloads a CSV template with the required columns', function () {
    $response = $this->actingAs(superAdminImport())->get(route('admin.user.import.template'));

    $response->assertOk();
    expect($response->streamedContent())->toContain('nim,nama,email,prodi');
});

it('forbids non-super-admin users from importing', function () {
    ensureRoles();
    $adminSurat = User::factory()->create();
    $adminSurat->assignRole('admin_surat');

    $this->actingAs($adminSurat)->get(route('admin.user.import.form'))->assertForbidden();
    $this->actingAs($adminSurat)->post(route('admin.user.import.preview'), [
        'file' => csvUpload([HEADER, '20210001,Budi,budi@example.ac.id,Informatika']),
    ])->assertForbidden();
});
