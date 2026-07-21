<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Services\MediaService;
use App\Services\SettingService;

/**
 * Orkestrasi penyimpanan Pengaturan Sistem (M1-T4): tulis nilai skalar per
 * baris `settings` lewat SettingService, dan attach logo baru (bila diunggah)
 * ke collection `file` baris `logo_kampus` lewat MediaService. Nilai `encrypted`
 * yang dikirim kosong dilewati agar password lama tidak tertimpa.
 */
class UpdateSystemSettings
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly MediaService $media,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari form.
     */
    public function handle(array $data, ?string $logoToken = null): void
    {
        Setting::query()->where('type', '!=', 'media')->get()
            ->each(function (Setting $setting) use ($data): void {
                if (! array_key_exists($setting->key, $data)) {
                    return;
                }

                $value = $data[$setting->key];

                // Kosong pada encrypted = tidak diubah (UX_SPEC 1.C.1).
                if ($setting->type === 'encrypted' && ($value === null || $value === '')) {
                    return;
                }

                $this->settings->set($setting->key, $value);
            });

        if ($logoToken !== null && $logoToken !== '') {
            $this->attachLogo($logoToken);
        }

        $this->settings->flush();
    }

    /**
     * Ganti logo kampus dengan file dari upload sementara FilePond.
     */
    private function attachLogo(string $token): void
    {
        $logo = Setting::query()->where('key', 'logo_kampus')->first();

        if ($logo === null) {
            return;
        }

        $logo->clearMediaCollection('file');
        $this->media->attachFromTemporary($logo, $token, 'file');
    }
}
