<?php

namespace Database\Seeders;

use App\Models\PlaceholderDefinition;
use Illuminate\Database\Seeder;

/**
 * Kamus placeholder default (ERD §8, §13, murni §4.4) — seeder PRODUKSI.
 * Kelompok `ttd` TIDAK di-seed (deteksi via regex, ERD §8/§9). Placeholder
 * `fakultas` DIHAPUS (keputusan D-001). Idempoten via firstOrCreate pada `name`.
 */
class PlaceholderDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            PlaceholderDefinition::query()->firstOrCreate(
                ['name' => $row['name']],
                $row,
            );
        }
    }

    /**
     * @return list<array{name:string, kelompok:string, input_type:string, source:?string}>
     */
    private function rows(): array
    {
        return [
            // Profil mahasiswa (dari relasi User→Mahasiswa)
            ['name' => 'nama_mahasiswa', 'kelompok' => 'profil', 'input_type' => 'text', 'source' => 'mahasiswa.nama'],
            ['name' => 'nim', 'kelompok' => 'profil', 'input_type' => 'text', 'source' => 'mahasiswa.nim'],
            ['name' => 'prodi', 'kelompok' => 'profil', 'input_type' => 'text', 'source' => 'mahasiswa.prodi'],

            // Sistem (dari settings)
            ['name' => 'nama_universitas', 'kelompok' => 'sistem', 'input_type' => 'text', 'source' => 'settings.nama_universitas'],
            ['name' => 'kode_universitas', 'kelompok' => 'sistem', 'input_type' => 'text', 'source' => 'settings.kode_universitas'],
            ['name' => 'logo_kampus', 'kelompok' => 'sistem', 'input_type' => 'image', 'source' => 'settings.logo_kampus'],
            ['name' => 'tahun_akademik', 'kelompok' => 'sistem', 'input_type' => 'text', 'source' => 'settings.tahun_akademik_aktif'],

            // Waktu (dihitung saat generate)
            ['name' => 'tanggal_surat', 'kelompok' => 'waktu', 'input_type' => 'date', 'source' => 'generate.tanggal'],
            ['name' => 'bulan_surat', 'kelompok' => 'waktu', 'input_type' => 'text', 'source' => 'generate.bulan'],
            ['name' => 'tahun_surat', 'kelompok' => 'waktu', 'input_type' => 'text', 'source' => 'generate.tahun'],

            // Counter (nomor surat)
            ['name' => 'nomor_surat', 'kelompok' => 'counter', 'input_type' => 'text', 'source' => 'generate.nomor'],
        ];
    }
}
