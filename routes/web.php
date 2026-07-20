<?php

use App\Http\Controllers\MediaUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Upload AJAX generik FilePond (ARCHITECTURE §9) — endpoint dipakai bersama
// semua komponen x-form.file / hook js-upload.
Route::middleware('auth')->group(function () {
    Route::post('upload', [MediaUploadController::class, 'process'])->name('media.upload.process');
    Route::delete('upload', [MediaUploadController::class, 'revert'])->name('media.upload.revert');
});
