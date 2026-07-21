<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form Pengaturan Sistem (UX_SPEC 1.C.1). Field mengikuti baris
 * `settings`; `mail_password` boleh kosong (= tidak diubah) dan `logo_token`
 * adalah server id FilePond (uuid) hasil upload sementara.
 */
class UpdateKonfigurasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_surat']) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // Umum
            'nama_universitas' => ['required', 'string', 'max:150'],
            'kode_universitas' => ['required', 'string', 'max:50'],
            'alamat_kampus' => ['nullable', 'string', 'max:500'],
            'logo_token' => ['nullable', 'string', 'uuid'],

            // Akademik
            'tahun_akademik_aktif' => ['nullable', 'string', 'max:20'],

            // Penomoran
            'format_nomor_helper' => ['nullable', 'string', 'max:150'],

            // Dokumen
            'libreoffice_path' => ['nullable', 'string', 'max:255'],

            // SMTP
            'mail_host' => ['nullable', 'string', 'max:150'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:150'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
            'mail_from_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
