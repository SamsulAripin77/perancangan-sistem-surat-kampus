<?php

namespace App\Models;

use Database\Factories\KategoriSuratFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master kategori/pengelompokan template surat (ERD §6, PRD F12). Dipakai
 * sebagai dropdown & filter template (F3). 1—n `templates`; kategori yang masih
 * dirujuk template tidak boleh dihapus (nonaktifkan) — guard menyusul saat
 * tabel templates tersedia (M2/M3).
 */
class KategoriSurat extends Model
{
    /** @use HasFactory<KategoriSuratFactory> */
    use HasFactory;

    protected $table = 'kategori_surat';

    protected $fillable = [
        'nama',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Jumlah template yang memakai kategori ini (0 hingga modul template ada).
     */
    public function templatesCount(): int
    {
        return 0;
    }

    /**
     * Kategori sedang dipakai template aktif → hapus ditolak (ERD §6 FK
     * restrict). Selalu false hingga tabel templates tersedia (M2/M3).
     */
    public function isInUse(): bool
    {
        return $this->templatesCount() > 0;
    }
}
