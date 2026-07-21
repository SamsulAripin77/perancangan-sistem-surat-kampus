<?php

namespace App\Support\Facades;

use App\Services\SettingService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade konfigurasi sistem — akses SSOT via `Settings::get('nama_universitas')`
 * (ARCHITECTURE §9). Membungkus SettingService yang di-bind singleton di
 * AppServiceProvider agar cache peta setting dibagi satu instance.
 *
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array<string, mixed> all()
 * @method static void set(string $key, mixed $value)
 * @method static string|null logoUrl()
 * @method static void flush()
 *
 * @see SettingService
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingService::class;
    }
}
