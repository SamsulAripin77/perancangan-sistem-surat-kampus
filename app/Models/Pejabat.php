<?php

namespace App\Models;

use Database\Factories\PejabatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Master penandatangan (ERD §4). TTD master via Media Library collection 'ttd'
 * (disk private, K4) — kosong = TTD basah. Menjabat di banyak unit (n—n).
 */
class Pejabat extends Model implements HasMedia
{
    /** @use HasFactory<PejabatFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'pejabat';

    protected $fillable = [
        'nama',
        'email',
        'nip_nidn',
        'jabatan',
        'is_active',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ttd')
            ->singleFile()
            ->useDisk('private');
    }

    /** @return BelongsToMany<Unit, $this> */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'pejabat_unit')->withTimestamps();
    }

    /**
     * Pejabat sedang dipakai bila sudah tertaut arsip surat (snapshot
     * penandatangan) — hapus ditolak, gunakan nonaktifkan (UX_SPEC 1.C.3).
     * Relasi arsip ditambahkan saat modul generate/arsip (M5+) tersedia; kini
     * belum ada referensi sehingga selalu false.
     */
    public function isInUse(): bool
    {
        return false;
    }

    /**
     * Apakah pejabat punya file TTD master (collection 'ttd') — menentukan
     * indikator kolom TTD & saran metode pengambilan saat generate.
     */
    public function hasTtd(): bool
    {
        return $this->getFirstMedia('ttd') !== null;
    }
}
