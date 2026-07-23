<?php

namespace App\Actions\Template;

use App\Enums\TemplateStatus;
use App\Models\Template;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;

/**
 * Simpan tahap awal Template Surat (M2-T3): metadata, unit penerbit, dan file
 * `.docx` collection private. Scan placeholder dikerjakan task M2-T4.
 */
class SaveTemplate
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * @param  array{nama:string,kategori_id:int|string,unit_ids:array<int, int|string>,deskripsi?:string|null,tipe_pemohon:string,sla_hari_kerja?:int|string|null,is_permohonan_mandiri:bool,docx_token:string}  $data
     */
    public function handle(array $data, int $createdBy): Template
    {
        return DB::transaction(function () use ($data, $createdBy): Template {
            $template = Template::create([
                'nama' => $data['nama'],
                'kategori_id' => $data['kategori_id'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'tipe_pemohon' => $data['tipe_pemohon'],
                'sla_hari_kerja' => $data['sla_hari_kerja'] ?? null,
                'is_permohonan_mandiri' => $data['is_permohonan_mandiri'],
                'status' => TemplateStatus::Draft->value,
                'created_by' => $createdBy,
            ]);

            $template->units()->sync($data['unit_ids']);
            $this->media->attachFromTemporary($template, $data['docx_token'], 'docx');

            return $template->refresh();
        });
    }
}
