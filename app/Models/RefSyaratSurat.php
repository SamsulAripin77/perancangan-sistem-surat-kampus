<?php

namespace App\Models;

use Database\Factories\RefSyaratSuratFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Master persyaratan dokumen reusable lintas template (ERD §10, PRD F4). File
 * contoh (opsional) via Media Library collection `template` disk private (§9) —
 * mahasiswa dapat mengunduhnya (F5 Lapisan 4). n—n `templates` via
 * `syarat_surat`; persyaratan yang masih dipakai template tidak boleh dihapus
 * (guard menyusul saat tabel templates tersedia, M3).
 */
class RefSyaratSurat extends Model implements HasMedia
{
    /** @use HasFactory<RefSyaratSuratFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'ref_syarat_surat';

    protected $fillable = [
        'nama',
        'deskripsi',
        'accepted_types',
        'max_size_mb',
    ];

    protected function casts(): array
    {
        return [
            'max_size_mb' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('template')
            ->singleFile()
            ->useDisk('private');
    }

    /** Apakah punya file contoh untuk diunduh. */
    public function hasTemplateFile(): bool
    {
        return $this->getFirstMedia('template') !== null;
    }

    /** Jumlah template yang memakai persyaratan ini (0 hingga modul template ada). */
    public function templatesCount(): int
    {
        return 0;
    }

    /**
     * Persyaratan sedang dipakai template → hapus ditolak (PRD F4). Selalu false
     * hingga tabel templates/syarat_surat tersedia (M3).
     */
    public function isInUse(): bool
    {
        return $this->templatesCount() > 0;
    }
}
