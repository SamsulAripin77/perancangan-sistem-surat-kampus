# Analisa Aplikasi: [Nama Aplikasi]

## 1. Overview
- Stack teknologi (Laravel versi, DB, auth library, dll)
- Satu paragraf: aplikasi ini untuk apa, siapa penggunanya

## 2. Aktor & Role
Tabel: Role | Deskripsi | Middleware/Guard

## 3. Skema Database
- ERD relasi kunci (teks/ASCII, fokus pada relasi bukan semua kolom)
- Tabel per entitas: Nama | Kolom Penting | Keterangan

## 4. Peta Fitur (dari routes)
Tabel: Method | URI | Controller@Action | Role | Deskripsi Singkat

## 5. Flow per Aktor
Per role → alur utama dari login sampai output
Gunakan diagram teks, bukan narasi panjang

## 6. Modul Detail
Per modul/grup fitur:
- Deskripsi
- Input (form fields)
- Proses (logika penting di controller/model)
- Output (response, file, redirect, notifikasi)
- Business rules (validasi, kondisi khusus)

## 7. UI/UX — Halaman Kunci
Wireframe ASCII untuk 3-5 halaman paling penting saja

## 8. Catatan Teknis
- Hal menarik/tidak umum ditemukan selama analisa
- Potensi masalah atau debt teknis
