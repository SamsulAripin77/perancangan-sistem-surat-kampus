<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * SSOT baca/tulis konfigurasi sistem (ERD §5.1, ARCHITECTURE §9). Nilai dibaca
 * dari peta ter-cache `rememberForever` (satu query untuk semua key) yang
 * di-bust setiap kali ada penulisan. Nilai `encrypted` (mis. password SMTP)
 * disimpan terenkripsi dan didekripsi saat dibaca; `integer`/`boolean` di-cast
 * sesuai `settings.type`.
 */
class SettingService
{
    private const CACHE_KEY = 'settings.map';

    /**
     * Ambil nilai setting (sudah ter-cast) berdasarkan key, atau $default bila
     * key tidak ada / bernilai null.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Peta seluruh setting `key => value` ter-cast, di-cache selamanya sampai
     * ada penulisan yang mem-bust-nya.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->load());
    }

    /**
     * Simpan satu nilai setting lalu bust cache. Tipe `encrypted` dienkripsi
     * sebelum disimpan. Diam-diam no-op bila key tidak dikenal.
     */
    public function set(string $key, mixed $value): void
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            return;
        }

        $setting->update([
            'value' => $setting->type === 'encrypted' && $value !== null
                ? Crypt::encryptString((string) $value)
                : $value,
        ]);

        $this->flush();
    }

    /**
     * URL publik logo kampus (collection `file` pada baris key `logo_kampus`),
     * atau null bila belum diunggah.
     */
    public function logoUrl(): ?string
    {
        return Setting::query()->where('key', 'logo_kampus')->first()
            ?->getFirstMediaUrl('file') ?: null;
    }

    /**
     * Buang cache peta setting agar dibangun ulang pada baca berikutnya.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Bangun peta `key => value` ter-cast dari basis data.
     *
     * @return array<string, mixed>
     */
    private function load(): array
    {
        return Setting::query()->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $this->castValue($setting)])
            ->all();
    }

    /**
     * Cast nilai mentah sesuai `settings.type` (dekripsi encrypted, integer,
     * boolean); tipe `media` tidak menyimpan nilai skalar (akses via logoUrl()).
     */
    private function castValue(Setting $setting): mixed
    {
        $value = $setting->value;

        if ($value === null) {
            return null;
        }

        return match ($setting->type) {
            'encrypted' => Crypt::decryptString($value),
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            default => $value,
        };
    }
}
