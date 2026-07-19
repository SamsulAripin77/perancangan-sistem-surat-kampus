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

*Entri berikutnya ditambahkan di bawah, jangan menimpa entri lama.*
