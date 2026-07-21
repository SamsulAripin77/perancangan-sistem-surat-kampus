<?php

namespace App\Actions\Pejabat;

use App\Models\Pejabat;
use App\Services\MediaService;

/**
 * Orkestrasi simpan Pejabat (M1-T6): tulis atribut, sync unit (n—n
 * `pejabat_unit`), dan attach TTD baru (opsional) ke collection `ttd` disk
 * private lewat MediaService. Dipakai store & update controller.
 */
class SavePejabat
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari PejabatRequest.
     */
    public function handle(array $data, ?Pejabat $pejabat = null): Pejabat
    {
        $pejabat ??= new Pejabat;

        $pejabat->fill([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'nip_nidn' => $data['nip_nidn'] ?? null,
            'email' => $data['email'] ?? null,
            'is_active' => $data['is_active'],
        ])->save();

        $pejabat->units()->sync($data['unit_ids']);

        $token = $data['ttd_token'] ?? null;
        if ($token !== null && $token !== '') {
            $pejabat->clearMediaCollection('ttd');
            $this->media->attachFromTemporary($pejabat, $token, 'ttd');
        }

        return $pejabat;
    }
}
