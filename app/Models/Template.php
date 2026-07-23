<?php

namespace App\Models;

use App\Enums\TemplateStatus;
use App\Enums\TipePemohon;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Master template surat (ERD §7, PRD F3). File docx hidup lewat Media Library
 * collection `docx`; unit penerbit n-n via `template_unit`.
 */
class Template extends Model implements HasMedia
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'nama',
        'kategori_id',
        'deskripsi',
        'tipe_pemohon',
        'sla_hari_kerja',
        'is_permohonan_mandiri',
        'status',
        'created_by',
    ];

    protected $attributes = [
        'tipe_pemohon' => 'umum',
        'is_permohonan_mandiri' => false,
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'tipe_pemohon' => TipePemohon::class,
            'sla_hari_kerja' => 'integer',
            'is_permohonan_mandiri' => 'boolean',
            'status' => TemplateStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('docx')
            ->singleFile()
            ->useDisk('private');
    }

    /** @return BelongsTo<KategoriSurat, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSurat::class, 'kategori_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<Unit, $this> */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'template_unit');
    }

    /** @return HasMany<TemplatePlaceholderConfig, $this> */
    public function placeholderConfigs(): HasMany
    {
        return $this->hasMany(TemplatePlaceholderConfig::class);
    }

    /** @return HasMany<TemplateDataTambahanField, $this> */
    public function dataTambahanFields(): HasMany
    {
        return $this->hasMany(TemplateDataTambahanField::class);
    }

    /** @return BelongsToMany<RefSyaratSurat, $this> */
    public function refSyarat(): BelongsToMany
    {
        return $this->belongsToMany(RefSyaratSurat::class, 'syarat_surat', 'template_id', 'syarat_id')
            ->withPivot(['is_required', 'urutan']);
    }

    public function hasDocx(): bool
    {
        return $this->getFirstMedia('docx') !== null;
    }

    public function tipePemohonValue(): string
    {
        $value = $this->getRawOriginal('tipe_pemohon');

        return is_string($value) ? $value : TipePemohon::Umum->value;
    }

    public function statusValue(): string
    {
        $value = $this->getRawOriginal('status');

        return is_string($value) ? $value : TemplateStatus::Draft->value;
    }
}
