<?php

namespace App\Actions\Syarat;

use App\Models\RefSyaratSurat;
use App\Services\MediaService;

/**
 * Orkestrasi simpan Master Persyaratan (M1-T11): tulis atribut + attach file
 * contoh baru (opsional) ke collection `template` disk private lewat
 * MediaService. Dipakai store & update controller.
 */
class SaveRefSyarat
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari SyaratRequest.
     */
    public function handle(array $data, ?RefSyaratSurat $syarat = null): RefSyaratSurat
    {
        $syarat ??= new RefSyaratSurat;

        $syarat->fill([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'accepted_types' => $data['accepted_types'],
            'max_size_mb' => $data['max_size_mb'],
        ])->save();

        $token = $data['template_token'] ?? null;
        if ($token !== null && $token !== '') {
            $syarat->clearMediaCollection('template');
            $this->media->attachFromTemporary($syarat, $token, 'template');
        }

        return $syarat;
    }
}
