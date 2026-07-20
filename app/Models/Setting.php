<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Konfigurasi sistem key-value bergrup (ERD §5.1). Logo (type='media') dilampirkan
 * ke baris via collection 'file' pada disk public (tampil di kop surat & halaman
 * publik). SSOT baca/tulis + cache dikelola SettingService (M1-T4).
 */
class Setting extends Model implements HasMedia
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->useDisk('public');
    }
}
