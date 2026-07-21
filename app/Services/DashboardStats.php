<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ringkasan angka dashboard admin (M1-T12, UX_SPEC 1.B.1). Sumber data —
 * `permohonan_surat` (M4) & `disposisi_surat_masuk` (M7) — belum tentu ada di
 * fase awal; setiap hitung dijaga `Schema::hasTable` sehingga aman tampil 0
 * sampai modul terkait dibangun (keputusan BACKLOG M1-T12 "boleh tampil 0").
 * Deadline (mendekati/overdue) memakai WorkingDays (D-004) dan diisi saat M4/M5
 * menautkan `templates.sla_hari_kerja` — sengaja 0 hingga skema itu tersedia.
 */
class DashboardStats
{
    /**
     * @return array{pending:int, mendekati:int, overdue:int, disposisi:int}
     */
    public function admin(): array
    {
        return [
            'pending' => $this->pendingCount(),
            // Deadline (H-1 / overdue) butuh join templates.sla_hari_kerja (M4/M5).
            'mendekati' => 0,
            'overdue' => 0,
            'disposisi' => $this->disposisiCount(),
        ];
    }

    /**
     * Jumlah permohonan berstatus `pending` (draft dikecualikan, SLA anchor
     * `created_at` — D-004/UX 1.B.1). 0 bila tabel belum ada.
     */
    private function pendingCount(): int
    {
        if (! Schema::hasTable('permohonan_surat')) {
            return 0;
        }

        return DB::table('permohonan_surat')->where('status', 'pending')->count();
    }

    /**
     * Jumlah disposisi surat masuk belum ditindaklanjuti. 0 bila tabel belum ada.
     */
    private function disposisiCount(): int
    {
        if (! Schema::hasTable('disposisi_surat_masuk')) {
            return 0;
        }

        return DB::table('disposisi_surat_masuk')->where('status', 'pending')->count();
    }
}
