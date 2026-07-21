<?php

use App\Http\Controllers\Admin\KamusController;
use App\Http\Controllers\Admin\KonfigurasiController;
use App\Http\Controllers\Admin\MahasiswaImportController;
use App\Http\Controllers\Admin\PejabatController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\MediaUploadController;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->to(AuthRedirect::url(Auth::user()))
        : redirect()->route('login');
});

// Paksa ganti password login pertama (UX_SPEC 1.A.1). Auth tapi TANPA
// password.changed agar tidak infinite-redirect.
Route::middleware('auth')->group(function () {
    Route::get('password/force', [ForcePasswordChangeController::class, 'show'])->name('password.force');
    Route::post('password/force', [ForcePasswordChangeController::class, 'update'])->name('password.force.update');
});

// Ubah password mandiri semua user (M1-T3). Submit ditangani Fortify
// user-password.update. Halaman Profil penuh (mahasiswa 4.G) menyusul M3-T8.
Route::view('akun/ubah-password', 'account.password')
    ->middleware(['auth', 'password.changed'])
    ->name('password.edit');

// Upload AJAX generik FilePond (ARCHITECTURE §9) — endpoint dipakai bersama
// semua komponen x-form.file / hook js-upload.
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('upload', [MediaUploadController::class, 'process'])->name('media.upload.process');
    Route::delete('upload', [MediaUploadController::class, 'revert'])->name('media.upload.revert');
});

// Dashboard admin (super_admin/admin_surat). Placeholder — diisi M1-T12.
Route::middleware(['auth', 'password.changed', 'role:super_admin|admin_surat'])->group(function () {
    Route::view('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');

    // Pengaturan Sistem / Konfigurasi (F2, UX_SPEC 1.C.1).
    Route::get('admin/konfigurasi', [KonfigurasiController::class, 'edit'])->name('admin.konfigurasi.edit');
    Route::post('admin/konfigurasi', [KonfigurasiController::class, 'update'])->name('admin.konfigurasi.update');

    // Manajemen Unit (F2, UX_SPEC 1.C.2). Hapus dijaga guard "sedang dipakai";
    // toggle untuk nonaktif/aktif.
    Route::patch('admin/unit/{unit}/toggle', [UnitController::class, 'toggle'])->name('admin.unit.toggle');
    Route::resource('admin/unit', UnitController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('admin.unit')
        ->parameters(['unit' => 'unit']);

    // Manajemen Pejabat (F2, UX_SPEC 1.C.3). Multi-unit + TTD private; hapus
    // dijaga guard + toggle aktif/nonaktif.
    Route::patch('admin/pejabat/{pejabat}/toggle', [PejabatController::class, 'toggle'])->name('admin.pejabat.toggle');
    Route::resource('admin/pejabat', PejabatController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('admin.pejabat')
        ->parameters(['pejabat' => 'pejabat']);
});

// Manajemen User (F1, UX_SPEC 2.A) — KHUSUS Super Admin (ARCHITECTURE §8).
// Tanpa hard delete (D-005): hanya toggle is_active, tidak ada route destroy.
Route::middleware(['auth', 'password.changed', 'role:super_admin'])->group(function () {
    // Import Mahasiswa SIAKAD (F1, UX_SPEC 2.A.3) — sebelum resource agar tidak
    // tertangkap {user}.
    Route::get('admin/user/import', [MahasiswaImportController::class, 'form'])->name('admin.user.import.form');
    Route::get('admin/user/import/template', [MahasiswaImportController::class, 'template'])->name('admin.user.import.template');
    Route::post('admin/user/import/preview', [MahasiswaImportController::class, 'preview'])->name('admin.user.import.preview');
    Route::post('admin/user/import', [MahasiswaImportController::class, 'store'])->name('admin.user.import.store');

    Route::patch('admin/user/{user}/toggle', [UserController::class, 'toggle'])->name('admin.user.toggle');
    Route::resource('admin/user', UserController::class)
        ->only(['index', 'store', 'update'])
        ->names('admin.user')
        ->parameters(['user' => 'user']);

    // Master Kamus Placeholder (F13, UX_SPEC 2.B) — perubahan berdampak semua
    // template → konfirmasi di UI.
    Route::resource('admin/kamus', KamusController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('admin.kamus')
        ->parameters(['kamus' => 'kamus']);
});

// Beranda mahasiswa. Placeholder — diisi M1-T12.
Route::middleware(['auth', 'password.changed', 'role:mahasiswa'])->group(function () {
    Route::view('mahasiswa/beranda', 'mahasiswa.beranda')->name('mahasiswa.beranda');
});
