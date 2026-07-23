<?php

namespace Tests\Feature;

use App\Enums\TemplateStatus;
use App\Models\KategoriSurat;
use App\Models\Template;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemplateIndexTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin_surat', 'web'));

        return $user;
    }

    public function test_admin_can_render_template_index(): void
    {
        KategoriSurat::factory()->create();
        Unit::factory()->create();

        $this->actingAs($this->admin())
            ->withoutVite()
            ->get(route('admin.template.index'))
            ->assertOk()
            ->assertSee(__('template.title'))
            ->assertSee('templateTable');
    }

    public function test_filters_templates_by_active_status(): void
    {
        Template::factory()->create([
            'nama' => 'Surat Aktif',
            'status' => TemplateStatus::Aktif->value,
        ]);
        Template::factory()->create([
            'nama' => 'Surat Draft',
            'status' => TemplateStatus::Draft->value,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.template.index', ['status' => TemplateStatus::Aktif->value]), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJsonFragment(['nama' => 'Surat Aktif'])
            ->assertJsonMissing(['nama' => 'Surat Draft']);
    }

    public function test_filters_templates_by_name_search(): void
    {
        Template::factory()->create(['nama' => 'Rekomendasi Magang']);
        Template::factory()->create(['nama' => 'Nota Dinas']);

        $this->actingAs($this->admin())
            ->get(route('admin.template.index', ['q' => 'Rekomendasi']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJsonFragment(['nama' => 'Rekomendasi Magang'])
            ->assertJsonMissing(['nama' => 'Nota Dinas']);
    }

    public function test_filters_templates_by_kategori_and_unit(): void
    {
        $kategori = KategoriSurat::factory()->create(['nama' => 'Layanan Mahasiswa']);
        $otherKategori = KategoriSurat::factory()->create(['nama' => 'Administrasi Internal']);
        $unit = Unit::factory()->create(['nama' => 'BAA']);
        $otherUnit = Unit::factory()->create(['nama' => 'LPPM']);

        $visible = Template::factory()->create(['nama' => 'Surat BAA', 'kategori_id' => $kategori->id]);
        $visible->units()->attach($unit);
        $hidden = Template::factory()->create(['nama' => 'Surat LPPM', 'kategori_id' => $otherKategori->id]);
        $hidden->units()->attach($otherUnit);

        $this->actingAs($this->admin())
            ->get(route('admin.template.index', [
                'kategori_id' => $kategori->id,
                'unit_id' => $unit->id,
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonFragment(['nama' => 'Surat BAA'])
            ->assertJsonMissing(['nama' => 'Surat LPPM']);
    }

    public function test_forbids_non_admin_users_from_template_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('mahasiswa', 'web'));

        $this->actingAs($user)->get(route('admin.template.index'))->assertForbidden();
    }
}
