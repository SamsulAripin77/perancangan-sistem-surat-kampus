<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Konfigurasi sistem awal (ERD §5.1) — seeder PRODUKSI. Nilai default/kosong;
 * admin melengkapi lewat form Settings (M1-T4). Idempoten via updateOrCreate
 * pada `key` agar aman dijalankan ulang tanpa menimpa nilai yang sudah diubah
 * admin.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            Setting::query()->firstOrCreate(
                ['key' => $row['key']],
                $row,
            );
        }
    }

    /**
     * @return list<array{group:string, key:string, type:string, label:string, value:?string, is_public:bool}>
     */
    private function rows(): array
    {
        return [
            // Umum
            ['group' => 'umum', 'key' => 'nama_universitas', 'type' => 'string', 'label' => 'Nama Universitas', 'value' => 'Universitas Nusa Putra', 'is_public' => true],
            ['group' => 'umum', 'key' => 'kode_universitas', 'type' => 'string', 'label' => 'Kode Universitas', 'value' => 'UNsP', 'is_public' => true],
            ['group' => 'umum', 'key' => 'alamat_kampus', 'type' => 'text', 'label' => 'Alamat Kampus', 'value' => null, 'is_public' => true],
            ['group' => 'umum', 'key' => 'logo_kampus', 'type' => 'media', 'label' => 'Logo Kampus', 'value' => null, 'is_public' => true],

            // Akademik
            ['group' => 'akademik', 'key' => 'tahun_akademik_aktif', 'type' => 'string', 'label' => 'Tahun Akademik Aktif', 'value' => null, 'is_public' => false],

            // Penomoran
            ['group' => 'penomoran', 'key' => 'format_nomor_helper', 'type' => 'string', 'label' => 'Format Nomor Surat', 'value' => '{urut}/{unit}/{kode_univ}/{bulan_romawi}/{tahun}', 'is_public' => false],

            // Dokumen — kosong = konversi PDF dimatikan (ARCHITECTURE §2.1)
            ['group' => 'dokumen', 'key' => 'libreoffice_path', 'type' => 'string', 'label' => 'Path LibreOffice', 'value' => null, 'is_public' => false],

            // SMTP — kosong; dilengkapi admin
            ['group' => 'smtp', 'key' => 'mail_host', 'type' => 'string', 'label' => 'SMTP Host', 'value' => null, 'is_public' => false],
            ['group' => 'smtp', 'key' => 'mail_port', 'type' => 'integer', 'label' => 'SMTP Port', 'value' => '587', 'is_public' => false],
            ['group' => 'smtp', 'key' => 'mail_username', 'type' => 'string', 'label' => 'SMTP Username', 'value' => null, 'is_public' => false],
            ['group' => 'smtp', 'key' => 'mail_password', 'type' => 'encrypted', 'label' => 'SMTP Password', 'value' => null, 'is_public' => false],
            ['group' => 'smtp', 'key' => 'mail_from_address', 'type' => 'string', 'label' => 'Email Pengirim', 'value' => null, 'is_public' => false],
            ['group' => 'smtp', 'key' => 'mail_from_name', 'type' => 'string', 'label' => 'Nama Pengirim', 'value' => 'Sistem Surat UNsP', 'is_public' => false],
        ];
    }
}
