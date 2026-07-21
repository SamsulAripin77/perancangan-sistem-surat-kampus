<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Perhitungan hari kerja untuk SLA permohonan (D-004). Hanya melewati
 * Sabtu/Minggu — TANPA kalender hari libur nasional (SLA = estimasi/alert
 * visual, bukan kontrak). Dipakai dashboard admin (M1-T12) & alur permohonan
 * (M4) untuk menghitung deadline.
 */
final class WorkingDays
{
    /**
     * Deadline = tanggal mulai + N hari kerja (skip Sabtu/Minggu). Menghitung
     * maju dari hari setelah $start; N=0 mengembalikan $start (tengah hari
     * dinormalkan ke awal hari). Contoh D-004: Kamis + 3 hari kerja → Selasa.
     */
    public static function deadline(CarbonInterface $start, int $workingDays): CarbonImmutable
    {
        $date = CarbonImmutable::instance($start)->startOfDay();

        for ($counted = 0; $counted < $workingDays;) {
            $date = $date->addDay();

            if (! $date->isWeekend()) {
                $counted++;
            }
        }

        return $date;
    }

    /**
     * Apakah deadline sudah terlewat dibanding acuan (default: hari ini).
     */
    public static function isOverdue(CarbonInterface $deadline, ?CarbonInterface $now = null): bool
    {
        $today = CarbonImmutable::instance($now ?? CarbonImmutable::now())->startOfDay();

        return CarbonImmutable::instance($deadline)->startOfDay()->lessThan($today);
    }

    /**
     * Apakah deadline mendekati (H-1 hingga hari-H, belum lewat) — untuk
     * penanda kuning di dashboard.
     */
    public static function isApproaching(CarbonInterface $deadline, ?CarbonInterface $now = null): bool
    {
        $today = CarbonImmutable::instance($now ?? CarbonImmutable::now())->startOfDay();
        $due = CarbonImmutable::instance($deadline)->startOfDay();
        $diff = $today->diffInDays($due, false);

        return $diff >= 0 && $diff <= 1;
    }
}
