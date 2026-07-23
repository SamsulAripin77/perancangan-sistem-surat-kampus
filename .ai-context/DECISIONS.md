# DECISIONS — Log Resolusi Item Terbuka

| | |
|---|---|
| **Fungsi** | Satu-satunya tempat mencatat keputusan atas item ⚠️ (rujuk PRD-INDEX §4). Dokumen lain (BACKLOG/ERD/UX_SPEC) menandai status resolved dan menunjuk balik ke sini. |
| **Format** | Satu entri `D-NNN` per keputusan, urut kronologis. Jangan hapus entri lama — kalau keputusan berubah, tambah entri baru yang mereferensikan entri lama. |

---

## D-001 — Kolom `fakultas` Mahasiswa & Placeholder `{{fakultas}}`

**Konteks**: Kolom `fakultas` tidak ikut diimport dari SIAKAD (kolom import hanya `nim,nama,email,prodi`), tapi `mahasiswa.fakultas` & placeholder `{{fakultas}}` (kelompok profil) sudah ada di skema — berisiko selalu kosong di surat.

**Keputusan**: **Hapus** placeholder `{{fakultas}}` dari kamus **dan** kolom `mahasiswa.fakultas` dari skema. Tidak ada lagi jejak fakultas di sistem Phase 1.

**Alasan**: Field ini tidak esensial untuk isi surat; mempertahankan kolom yang selalu `NULL` (atau menambah mapping prodi→fakultas) menambah kompleksitas tanpa manfaat sepadan. Lebih simpel dihapus daripada dipertahankan setengah-jalan.

**Dampak** (dokumen yang diperbarui): ERD §3 (`mahasiswa`), ERD §8 (seed kamus), ERD §23 (sample), UX_SPEC 2.A.2/3.G/4.A.2/4.G, BACKLOG M3-T8, ARCHITECTURE §13 (contoh seeder).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-002 — Fase Halaman Verifikasi Publik (QR)

**Konteks**: `PRD.md` F8 (Phase 1) menyebut halaman verifikasi publik, tapi bagian Fitur Tambahan Phase 2 di PRD yang sama **juga** mencantumkan "Portal Verifikasi Publik via QR" — kontradiksi internal.

**Keputusan**: Halaman verifikasi publik (`verify/{qr_hash}`) adalah **Phase 2**. **QR code tetap dibuat di Phase 1** (saat generate surat, M5-T2) — hanya halaman yang membacanya ditunda.

**Alasan**: Konsisten dengan daftar Fitur Tambahan Phase 2 di PRD §5.2 yang eksplisit menyebut ini.

**Dampak**: PRD.md F8 (hapus bullet halaman verifikasi dari Phase 1), BACKLOG M6-T5 (dipindah keluar dari eksekusi Phase 1, dicatat sebagai Phase 2), UX_SPEC 6.B (dilabeli ulang "Phase 2 — disimpan sebagai referensi desain"), FEATURE_MAP (modul M-VERIFIKASI ditandai Phase 2).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-003 — Master Klasifikasi Surat

**Konteks**: `kode_klasifikasi` di Surat Masuk (F9) & Surat Keluar (F10) berupa teks bebas. Ada opsi bikin master data `klasifikasi_surat` (dropdown terkontrol).

**Keputusan**: **Tidak dibuat** master `klasifikasi_surat` untuk Phase 1. Tetap teks bebas seperti sekarang. Dipertimbangkan lagi di Phase 2 kalau ada kebutuhan nyata.

**Alasan**: Menghindari CRUD tambahan untuk masalah yang belum tentu terjadi (inkonsistensi ejaan antar-admin belum terbukti jadi masalah nyata di lapangan).

**Dampak**: BACKLOG M7-T2 (hapus tanda ⚠️, guardrail jadi definitif), ERD §24 (item ditandai resolved-for-now).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-004 — Perhitungan SLA & Hari Libur

**Konteks**: Rumus SLA asli menyebut "skip Sabtu/Minggu **dan hari libur nasional**". BACKLOG (M1-T12) belum menjelaskan cara hitungnya.

**Keputusan**: Phase 1 **hanya skip Sabtu/Minggu** (hari kerja standar). **Tidak** membangun master kalender hari libur nasional.

**Alasan**: SLA cuma estimasi/alert visual, bukan kontrak dengan penalti (sudah disepakati sebelumnya) — meleset beberapa hari karena libur nasional dianggap wajar diterima.

**Dampak**: BACKLOG M1-T12 (tambah kejelasan rumus skip-weekend), ERD §24 (item kalender libur ditandai resolved-for-now, bisa dipertimbangkan Phase 2).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-005 — Hapus User (Hard Delete vs Soft Delete)

**Konteks**: Manajemen User (M1-T7) menonaktifkan user (`is_active=false`), bukan menghapus. Perlu dipastikan apakah hard delete tetap diperlukan.

**Keputusan**: **Tidak ada hard delete** di UI Phase 1. Nonaktifkan (`is_active=false`) adalah satu-satunya cara "menghapus" akses user.

**Alasan**: Konsisten dengan audit trail wajib (PRD §7.1) dan integritas relasi — user tetap dirujuk oleh `permohonan_surat`, `surat_tercetak.digenerate_oleh`, dll. Hard delete akan merusak jejak arsip/audit.

**Dampak**: BACKLOG M1-T7 (guardrail dipertegas), ERD §24 (item ditutup).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-006 — Cetak Ulang: Nomor Surat Baru vs Sama

**Konteks**: `surat_tercetak` punya `UNIQUE(nomor_surat, unit_id)`. Saat cetak ulang (M6-T3), constraint ini otomatis memaksa nomor baru — perlu dipastikan ini memang kebijakan yang diinginkan.

**Keputusan**: **Nomor baru wajib** setiap cetak ulang. Tidak ada pengecualian/override untuk memakai nomor sama.

**Alasan**: Konsisten dengan prinsip "gap nomor adalah wajar" yang sudah disepakati sebelumnya, dan menjaga jejak audit — nomor lama secara jelas menandai "versi lama", nomor baru menandai "versi berlaku".

**Dampak**: BACKLOG M6-T3 (guardrail dipertegas, hapus tanda ⚠️), ERD §24 (item ditutup).

**Status**: ✅ RESOLVED — 19 Juli 2026.

---

## D-007 — Standarisasi Teks Statis FE via `lang/id` (SSOT)

**Konteks**: Belum ada aturan baku untuk teks statis (label, judul kolom tabel, tombol, pesan konfirmasi/flash, pesan validasi) di Blade/Controller/JS — risiko hardcode tersebar & duplikasi, menyulitkan perubahan istilah dan lokalisasi ke depan.

**Keputusan**: Semua teks statis FE **wajib** diambil dari file bahasa Laravel `lang/id/*.php` (dikelompokkan per domain: `common.php`, `table.php`, `validation.php`, per-modul) via `__('group.key')` — bukan string literal di Blade/Controller/JS. Berlaku sejak M0 (task baru `M0-T10` menyiapkan struktur & konvensi); audit menutup celah pada halaman yang sudah dibangun dilakukan di hardening (task baru `M10-T5`).

**Alasan**: Single Source of Truth (prinsip #2, ARCHITECTURE §0) — satu label hidup di satu tempat; menghindari cari-ganti lintas puluhan view saat istilah berubah; menyiapkan lokalisasi tanpa refactor besar.

**Dampak** (dokumen yang diperbarui): ARCHITECTURE §11.5 (baru), §7 (cross-ref pesan validasi), §12 (konvensi lang key), §15 (anti-pattern baru), §18 (DoD langkah View), §19 (ringkasan keputusan). BACKLOG §0 (catatan standar tambahan di template task), M0-T10 (baru), M10-T5 (baru).

**Status**: ✅ RESOLVED — 20 Juli 2026.

---

## D-008 — Library Parser Import Excel/CSV

**Konteks**: M1-T8 (Import Mahasiswa SIAKAD) butuh membaca file `.xlsx/.csv` (UX_SPEC 2.A.3). ARCHITECTURE §2 tidak mencantumkan library untuk ini; PHPWord yang sudah ada tidak membawa parser spreadsheet.

**Keputusan**: Pakai `openspout/openspout` (^4.28) sebagai parser import `.xlsx/.csv`.

**Alasan**: `openspout/openspout` ringan, mendukung pembacaan streaming, mendukung xlsx + csv, dan membawa dependensi lebih minimal daripada `maatwebsite/excel`. Opsi CSV-only native ditolak karena menyimpang dari spesifikasi yang meminta xlsx/csv.

**Dampak**: Menambah satu dependensi backend di luar ARCHITECTURE §2; parsing import dilakukan streaming agar aman untuk file besar; pratinjau dan import memakai reader yang sama.

**Status**: ✅ ACCEPTED — 21 Juli 2026.

---

## D-009 — Library QR Code Verifikasi

**Konteks**: M5-T2 perlu QR code untuk `qr_hash` verifikasi surat. Rencana awal memakai `simplesoftwareio/simple-qrcode`, tetapi paket itu bergantung pada `bacon/bacon-qr-code:^2.0`, sementara `laravel/fortify` v1.37.2 pada project ini sudah menarik `bacon/bacon-qr-code:^3.0`. Composer lock saat ini memakai `bacon/bacon-qr-code` v3.1.1.

**Keputusan**: Jangan memakai `simplesoftwareio/simple-qrcode`. Gunakan `bacon/bacon-qr-code` v3 secara langsung sebagai engine QR. Untuk output yang akan di-embed ke DOCX/PDF, prioritaskan SVG (`SvgImageBackEnd`) atau PNG via `GDLibRenderer` bila raster dibutuhkan.

**Alasan**: `bacon/bacon-qr-code` sudah terpasang transitif melalui Fortify, kompatibel dengan PHP 8.2+, dan tidak menambah wrapper Laravel yang tidak terawat/tertinggal versi dependency. Opsi `endroid/qr-code` v6 membutuhkan PHP 8.4, sementara v5 kompatibel tetapi tetap hanya membungkus Bacon v3 dan menambah dependency baru. Opsi `chillerlan/php-qrcode` juga layak secara teknis, tetapi menambah dependency baru dan membutuhkan `ext-mbstring`; tidak diperlukan karena Bacon v3 sudah ada.

**Dampak**: ARCHITECTURE §2 dan BACKLOG M5-T2 memakai `bacon/bacon-qr-code` langsung. Saat implementasi M5-T2, buat service kecil milik aplikasi untuk menghasilkan QR dari URL verifikasi agar controller/service dokumen tidak bergantung langsung pada detail renderer.

**Status**: ✅ ACCEPTED — 23 Juli 2026.

---

## D-010 — Handoff Redirect M2-T3 Sebelum Hub Template Lengkap

**Konteks**: BACKLOG M2-T3 meminta setelah template `.docx` dan metadata tersimpan, pengguna diarahkan ke hub `admin.template.edit`. Namun implementasi scan placeholder ada di M2-T4 dan hub edit/review lengkap ada di M2-T5. Tanpa keputusan ini, M2-T3 berisiko melebar ke scope M2-T4/M2-T5 atau gagal memenuhi acceptance criterion redirect hub.

**Keputusan**: M2-T3 boleh menambahkan route/view `admin.template.edit` minimal sebagai handoff read-only setelah store berhasil. Stub ini hanya menampilkan ringkasan template tersimpan dan status bahwa scan placeholder/review hub akan dilanjutkan pada task berikutnya. M2-T3 tetap tidak mengimplementasikan scan placeholder, review placeholder editable, update metadata, persyaratan, data tambahan, coba template, aktivasi, atau hapus template.

**Alasan**: Menjaga AC M2-T3 tetap benar tanpa mencuri scope M2-T4/M2-T5. Route tujuan sudah stabil sejak awal sehingga tombol "Simpan & Scan" dan tombol aksi index dapat memakai named route yang sama, sementara perilaku bisnis lanjutannya tetap dikerjakan berurutan sesuai backlog.

**Dampak**: Implementasi M2-T3 mencakup create/store, attach media collection `docx`, sync `template_unit`, redirect ke `admin.template.edit`, dan handoff stub read-only. M2-T4 tetap menjadi pemilik `ScanPlaceholderAction`; M2-T5 tetap menjadi pemilik hub edit/review lengkap.

**Status**: ✅ ACCEPTED — 23 Juli 2026.

---

*Entri berikutnya ditambahkan di bawah, jangan menimpa entri lama.*
