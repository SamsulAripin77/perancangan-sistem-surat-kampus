<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unit penerbit surat dengan hierarki self-reference (ERD §1).
 */
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'kode',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Unit, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Unit, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return BelongsToMany<Template, $this> */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_unit');
    }

    /**
     * Unit sedang dipakai bila punya sub-unit atau user terhubung — hapus
     * ditolak, gunakan nonaktifkan (UX_SPEC 1.C.2 guardrail). Referensi
     * permohonan ditambahkan saat modul terkait tersedia.
     */
    public function isInUse(): bool
    {
        return $this->children()->exists()
            || $this->users()->exists()
            || $this->templates()->exists();
    }
}
