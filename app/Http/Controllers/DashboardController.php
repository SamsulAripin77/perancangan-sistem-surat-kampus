<?php

namespace App\Http\Controllers;

use App\Services\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing per role (F6/F9, UX_SPEC 1.B). Read-only: dashboard admin (stat card
 * ringkasan) & beranda mahasiswa (status permohonan + ajakan ajukan). Angka
 * permohonan/disposisi menyusul saat M4/M7 (tampil 0 hingga saat itu).
 */
class DashboardController extends Controller
{
    public function admin(DashboardStats $stats): View
    {
        return view('admin.dashboard', ['stats' => $stats->admin()]);
    }

    public function mahasiswa(Request $request): View
    {
        $user = $request->user();

        return view('mahasiswa.beranda', [
            'nama' => optional($user->mahasiswa)->nama ?? $user->name,
            // Permohonan aktif menyusul (M4); kosong → beranda tampil ajakan.
            'permohonan' => collect(),
        ]);
    }
}
