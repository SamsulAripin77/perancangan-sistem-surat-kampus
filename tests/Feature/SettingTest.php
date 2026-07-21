<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaService;
use App\Services\SettingService;
use App\Support\Facades\Settings;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function admin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

    return $user;
}

beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

// AC1 — simpan nilai valid tersimpan + flash sukses
it('saves valid settings and flashes success', function () {
    $response = $this->actingAs(admin())
        ->from(route('admin.konfigurasi.edit'))
        ->post(route('admin.konfigurasi.update'), [
            'nama_universitas' => 'Universitas Baru',
            'kode_universitas' => 'UB',
            'tahun_akademik_aktif' => '2025/2026',
        ]);

    $response->assertRedirect(route('admin.konfigurasi.edit'));
    $response->assertSessionHas('success', __('konfigurasi.saved'));

    expect(Setting::query()->where('key', 'nama_universitas')->value('value'))->toBe('Universitas Baru')
        ->and(Setting::query()->where('key', 'tahun_akademik_aktif')->value('value'))->toBe('2025/2026');
});

// AC2 — Settings::get mengembalikan nilai baru (cache ter-bust setelah simpan)
it('returns the fresh value via the Settings facade after saving', function () {
    // Prime cache dengan nilai lama.
    expect(Settings::get('nama_universitas'))->toBe('Universitas Nusa Putra');

    $this->actingAs(admin())->post(route('admin.konfigurasi.update'), [
        'nama_universitas' => 'Universitas Terbaru',
        'kode_universitas' => 'UNsP',
    ]);

    expect(Settings::get('nama_universitas'))->toBe('Universitas Terbaru');
});

// AC2 (perilaku cache) — nilai dibaca dari cache sampai di-bust
it('reads from cache until flushed', function () {
    $service = app(SettingService::class);

    expect($service->get('kode_universitas'))->toBe('UNsP');

    // Ubah langsung di DB tanpa lewat service → cache belum tahu.
    Setting::query()->where('key', 'kode_universitas')->update(['value' => 'XXX']);
    expect($service->get('kode_universitas'))->toBe('UNsP');

    $service->flush();
    expect($service->get('kode_universitas'))->toBe('XXX');
});

// AC3 — upload logo tersimpan di collection 'file' & tampil preview
it('attaches the uploaded logo to the file collection on the setting row', function () {
    Storage::fake('private');
    Storage::fake('public');

    $token = app(MediaService::class)->storeTemporary(UploadedFile::fake()->image('logo.png'));

    $this->actingAs(admin())->post(route('admin.konfigurasi.update'), [
        'nama_universitas' => 'Universitas Nusa Putra',
        'kode_universitas' => 'UNsP',
        'logo_token' => $token,
    ]);

    $logo = Setting::query()->where('key', 'logo_kampus')->first();
    expect($logo->getFirstMedia('file'))->not->toBeNull()
        ->and(app(SettingService::class)->logoUrl())->not->toBeNull();
});

// Edge — password SMTP disimpan terenkripsi, kosong = tidak diubah
it('encrypts the smtp password and skips it when left blank', function () {
    $service = app(SettingService::class);

    $this->actingAs(admin())->post(route('admin.konfigurasi.update'), [
        'nama_universitas' => 'Universitas Nusa Putra',
        'kode_universitas' => 'UNsP',
        'mail_password' => 'rahasia123',
    ]);

    $stored = Setting::query()->where('key', 'mail_password')->value('value');
    expect($stored)->not->toBe('rahasia123')
        ->and(Crypt::decryptString($stored))->toBe('rahasia123')
        ->and($service->get('mail_password'))->toBe('rahasia123');

    // Submit ulang tanpa mengisi password → nilai lama dipertahankan.
    $this->actingAs(admin())->post(route('admin.konfigurasi.update'), [
        'nama_universitas' => 'Universitas Nusa Putra',
        'kode_universitas' => 'UNsP',
        'mail_password' => '',
    ]);

    expect(Setting::query()->where('key', 'mail_password')->value('value'))->toBe($stored)
        ->and(app(SettingService::class)->get('mail_password'))->toBe('rahasia123');
});

// Guard — mahasiswa tidak boleh mengakses form konfigurasi
it('forbids non-admin users from the settings form', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

    $this->actingAs($user)->get(route('admin.konfigurasi.edit'))->assertForbidden();
});

// Render — form bergrup tampil untuk admin
it('renders the grouped settings form for an admin', function () {
    $this->actingAs(admin())->withoutVite()->get(route('admin.konfigurasi.edit'))
        ->assertOk()
        ->assertSee(__('konfigurasi.title'))
        ->assertSee(__('konfigurasi.group_smtp'));
});
