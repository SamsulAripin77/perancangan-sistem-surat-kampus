<?php

namespace Tests\Feature;

use App\Enums\TemplateStatus;
use App\Enums\TipePemohon;
use App\Models\RefSyaratSurat;
use App\Models\Template;
use App\Models\TemplateDataTambahanField;
use App\Models\TemplatePlaceholderConfig;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TemplateSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_template_tables_on_migrate(): void
    {
        foreach ([
            'templates',
            'template_unit',
            'template_placeholder_config',
            'syarat_surat',
            'template_data_tambahan_fields',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumns('templates', [
            'nama',
            'kategori_id',
            'deskripsi',
            'tipe_pemohon',
            'sla_hari_kerja',
            'is_permohonan_mandiri',
            'status',
            'created_by',
            'deleted_at',
        ]));

        $this->assertFalse(Schema::hasColumn('templates', 'unit_id'));
    }

    public function test_template_resolves_units_syarat_and_child_configs(): void
    {
        $template = Template::factory()->create();
        $units = Unit::factory()->count(2)->create();
        $syarat = RefSyaratSurat::factory()->count(2)->create();

        $template->units()->attach($units->pluck('id')->all());
        $template->refSyarat()->attach([
            $syarat[0]->id => ['is_required' => true, 'urutan' => 1],
            $syarat[1]->id => ['is_required' => false, 'urutan' => 2],
        ]);

        $placeholderConfig = $template->placeholderConfigs()->create([
            'placeholder_name' => 'nama_mahasiswa',
            'label_mahasiswa' => 'Nama Mahasiswa',
            'tipe_input' => 'text',
            'filled_by' => 'mahasiswa',
            'is_required' => true,
            'urutan' => 1,
        ]);
        $dataTambahanField = $template->dataTambahanFields()->create([
            'label' => 'Nomor HP',
            'field_key' => 'nomor_hp',
            'tipe_input' => 'text',
            'is_required' => true,
            'urutan' => 1,
        ]);

        $template->load(['units', 'refSyarat', 'placeholderConfigs', 'dataTambahanFields']);
        $optionalSyarat = $template->refSyarat->firstWhere('id', $syarat[1]->id);

        $this->assertCount(2, $template->units);
        $this->assertCount(2, $template->refSyarat);
        $this->assertFalse((bool) $optionalSyarat->pivot->is_required);
        $this->assertTrue($placeholderConfig->template->is($template));
        $this->assertTrue($dataTambahanField->template->is($template));
        $this->assertSame(TipePemohon::Umum, $template->tipe_pemohon);
        $this->assertSame(TemplateStatus::Draft, $template->status);
        $this->assertInstanceOf(TemplatePlaceholderConfig::class, $template->placeholderConfigs->first());
        $this->assertInstanceOf(TemplateDataTambahanField::class, $template->dataTambahanFields->first());
    }
}
