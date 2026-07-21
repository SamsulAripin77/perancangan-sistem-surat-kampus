<?php

return [
    'title' => 'Import Mahasiswa',
    'intro' => 'Unggah file SIAKAD (.xlsx/.csv) berisi kolom: NIM, Nama, Email, Prodi. Password tidak diimpor — sistem membuat password acak dan mahasiswa wajib menggantinya saat login pertama.',
    'download_template' => 'Unduh Template',
    'file_label' => 'File Import',
    'file_hint' => 'Format .xlsx atau .csv, maksimal 5MB. Kolom: nim, nama, email, prodi.',
    'preview_btn' => 'Pratinjau',
    'preview_title' => 'Pratinjau Import',
    'preview_summary' => ':valid baris valid akan diimpor, :skipped baris dilewati (duplikat/error).',
    'preview_title_short' => 'Pratinjau',
    'row_status' => 'Status',
    'status_valid' => 'Valid',
    'status_duplicate' => 'Dilewati (duplikat)',
    'status_invalid' => 'Error',
    'dup_reason' => 'Email atau NIM sudah terdaftar.',
    'cancel' => 'Batal',
    'commit_btn' => 'Import :valid Mahasiswa',
    'summary' => ':imported mahasiswa diimpor, :skipped dilewati (duplikat/error).',
    'expired' => 'File pratinjau sudah tidak tersedia. Silakan unggah ulang.',
];
