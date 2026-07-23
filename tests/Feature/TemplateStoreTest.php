<?php

namespace Tests\Feature;

use App\Enums\TemplateStatus;
use App\Enums\TipePemohon;
use App\Models\KategoriSurat;
use App\Models\Template;
use App\Models\Unit;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemplateStoreTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

        return $user;
    }

    private function tempUpload(string $name, int $sizeInKilobytes, string $mimeType): string
    {
        return app(MediaService::class)->storeTemporary(
            UploadedFile::fake()->create($name, $sizeInKilobytes, $mimeType),
        );
    }

    public function test_admin_can_store_template_draft_with_docx_and_redirect_to_handoff(): void
    {
        Storage::fake('private');
        $kategori = KategoriSurat::factory()->create();
        $units = Unit::factory()->count(2)->create();
        $token = $this->tempUpload(
            'Rekomendasi Magang.docx',
            20,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );

        $response = $this->actingAs($this->admin())->post(route('admin.template.store'), [
            'nama' => 'Rekomendasi Magang',
            'kategori_id' => $kategori->id,
            'unit_ids' => $units->pluck('id')->all(),
            'deskripsi' => 'Template rekomendasi magang mahasiswa.',
            'tipe_pemohon' => TipePemohon::Mahasiswa->value,
            'sla_hari_kerja' => 3,
            'is_permohonan_mandiri' => '1',
            'docx_token' => $token,
        ]);

        $template = Template::query()->with('units')->firstOrFail();

        $response->assertRedirect(route('admin.template.edit', $template));
        $this->assertSame(TemplateStatus::Draft->value, $template->statusValue());
        $this->assertSame('Rekomendasi Magang', $template->nama);
        $this->assertSame($kategori->id, $template->kategori_id);
        $this->assertTrue($template->is_permohonan_mandiri);
        $this->assertEqualsCanonicalizing($units->pluck('id')->all(), $template->units->pluck('id')->all());
        $this->assertNotNull($template->getFirstMedia('docx'));
        $this->assertSame('docx', $template->getFirstMedia('docx')?->collection_name);
        $this->assertNull(app(MediaService::class)->temporaryFilePath($token));
    }

    public function test_rejects_non_docx_file_and_does_not_create_template(): void
    {
        Storage::fake('private');
        $kategori = KategoriSurat::factory()->create();
        $unit = Unit::factory()->create();
        $token = $this->tempUpload('Surat.pdf', 20, 'application/pdf');

        $this->actingAs($this->admin())
            ->from(route('admin.template.create'))
            ->post(route('admin.template.store'), [
                'nama' => 'Template PDF',
                'kategori_id' => $kategori->id,
                'unit_ids' => [$unit->id],
                'tipe_pemohon' => TipePemohon::Umum->value,
                'is_permohonan_mandiri' => '0',
                'docx_token' => $token,
            ])
            ->assertRedirect(route('admin.template.create'))
            ->assertSessionHasErrors('docx_token');

        $this->assertSame(0, Template::query()->count());
        $this->assertNotNull(app(MediaService::class)->temporaryFilePath($token));
    }

    public function test_rejects_docx_larger_than_ten_megabytes_and_does_not_create_template(): void
    {
        Storage::fake('private');
        $kategori = KategoriSurat::factory()->create();
        $unit = Unit::factory()->create();
        $token = $this->tempUpload(
            'Template Besar.docx',
            10_241,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
        $path = app(MediaService::class)->temporaryFilePath($token);
        $this->assertNotNull($path);
        Storage::disk('private')->put($path, str_repeat('x', (10 * 1024 * 1024) + 1));

        $this->actingAs($this->admin())
            ->from(route('admin.template.create'))
            ->post(route('admin.template.store'), [
                'nama' => 'Template Besar',
                'kategori_id' => $kategori->id,
                'unit_ids' => [$unit->id],
                'tipe_pemohon' => TipePemohon::Umum->value,
                'is_permohonan_mandiri' => '0',
                'docx_token' => $token,
            ])
            ->assertRedirect(route('admin.template.create'))
            ->assertSessionHasErrors('docx_token');

        $this->assertSame(0, Template::query()->count());
        $this->assertNotNull(app(MediaService::class)->temporaryFilePath($token));
    }

    public function test_forbids_non_admin_users_from_creating_template(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

        $this->actingAs($user)
            ->withoutVite()
            ->get(route('admin.template.create'))
            ->assertForbidden();
    }
}
