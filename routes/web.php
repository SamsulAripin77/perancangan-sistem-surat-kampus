<?php

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

// Upload AJAX generik FilePond (ARCHITECTURE §9) — endpoint dipakai bersama
// semua komponen x-form.file / hook js-upload.
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('upload', [MediaUploadController::class, 'process'])->name('media.upload.process');
    Route::delete('upload', [MediaUploadController::class, 'revert'])->name('media.upload.revert');
});

// Dashboard admin (super_admin/admin_surat). Placeholder — diisi M1-T12.
Route::middleware(['auth', 'password.changed', 'role:super_admin|admin_surat'])->group(function () {
    Route::view('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
});

// Beranda mahasiswa. Placeholder — diisi M1-T12.
Route::middleware(['auth', 'password.changed', 'role:mahasiswa'])->group(function () {
    Route::view('mahasiswa/beranda', 'mahasiswa.beranda')->name('mahasiswa.beranda');
});
