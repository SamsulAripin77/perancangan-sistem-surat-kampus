<?php

namespace App\Models;

use Database\Factories\KategoriSuratFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master kategori/pengelompokan template surat (ERD §6, PRD F12). Dipakai
 * sebagai dropdown & filter template (F3). 1—n `templates`; kategori yang masih
 * dirujuk template tidak boleh dihapus (nonaktifkan).
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

    /** @return HasMany<Template, $this> */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'kategori_id');
    }

    /**
     * Jumlah template yang memakai kategori ini.
     */
    public function templatesCount(): int
    {
        return $this->templates()->count();
    }

    /**
     * Kategori sedang dipakai template aktif → hapus ditolak (ERD §6 FK
     * restrict).
     */
    public function isInUse(): bool
    {
        return $this->templatesCount() > 0;
    }
}
