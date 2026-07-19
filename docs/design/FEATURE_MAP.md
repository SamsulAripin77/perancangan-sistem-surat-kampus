# FEATURE MAP — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Dokumen** | Peta Route/Halaman × Modul × Tabel ERD |
| **Versi** | 1.0 (Draft) |
| **Tanggal** | 19 Juli 2026 |
| **Basis** | `PRD.md` (F1-F13), `ERD.md` (§1-§21), `ARCHITECTURE.md`, `perancangan-murni.md` §10 |
| **Tujuan** | Memetakan semua halaman/route ke modul pemilik & tabel ERD yang disentuh — **untuk mendeteksi tumpang tindih antar modul** (§4) |

---

## §1. Konvensi

**Legenda akses tabel**: `R` = read, `W` = write (insert/update/delete). `R/W` = keduanya.

**Kode modul** (satu modul = satu area kepemilikan fitur):

| Kode | Modul | PRD | Sisi |
|---|---|---|---|
| M-AUTH | Autentikasi | F1 | Publik/semua |
| M-DASH | Dashboard | F6/F9 | Admin & Mahasiswa |
| M-CONFIG | Konfigurasi Sistem | F2 | Admin |
| M-USER | Manajemen User | F1 | Super Admin |
| M-KAMUS | Master Kamus Placeholder | F13 | Super Admin |
| M-KATEGORI | Master Kategori Surat | F12 | Admin |
| M-SYARAT | Master Persyaratan | F4 | Admin |
| M-TEMPLATE | Master Template Surat | F3 | Admin |
| M-PERM-ADM | Review & Approval Permohonan | F6 | Admin |
| M-GENERATE | Generate & Cetak Surat | F7 | Admin |
| M-ARSIP | Arsip Surat Tercetak | F8 | Admin |
| M-MASUK | Surat Masuk + Disposisi | F9 | Admin |
| M-KELUAR | Buku Agenda Surat Keluar | F10 | Admin |
| M-NOTIF | Notifikasi Email | F11 | Cross-cutting |
| M-MHS-AJUKAN | Ajukan Surat (mahasiswa) | F5.1 | Mahasiswa |
| M-MHS-RIWAYAT | Riwayat & Resubmit | F5.3/5.4 | Mahasiswa |
| M-MHS-DOKUMEN | Dokumen Saya | F5.2 | Mahasiswa |
| M-MHS-PROFIL | Profil Mahasiswa | — | Mahasiswa |
| M-MEDIA | Upload File Generik | K4 | Cross-cutting |
| M-VERIFIKASI | Verifikasi Publik ⏸️ *Phase 2* | F8 | Publik |

> **Cross-cutting** = dipakai lintas modul; lihat §5 (shared services) & §4 (tumpang tindih).
> **M-VERIFIKASI** ditandai Phase 2 (keputusan D-002, `docs/decisions/DECISIONS.md`) — rute `verify.show` di §2 di bawah tetap didokumentasikan sebagai referensi, tapi tidak dieksekusi di Phase 1.

---

## §2. Peta Route / Halaman

> **Standar list/index**: semua route `*.index` (Daftar/Buku Agenda/Riwayat) **wajib** mendukung advanced filter + global search + sort + pagination via pola reusable — lihat **ARCHITECTURE §17** (Query Filter + `x-ui.filter` + Yajra). Filter minimum per halaman ada di tabel ARCHITECTURE §17.

### 2.1 Autentikasi & Dashboard

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `login` | GET | Form login | M-AUTH | — |
| `login.store` | POST | Autentikasi (cek `is_active`) | M-AUTH | users(R), activity_log(W) |
| `logout` | POST | Logout | M-AUTH | activity_log(W) |
| `password.*` | GET/POST | Reset password | M-AUTH | users(R/W) |
| `admin.dashboard` | GET | Ringkasan pending/deadline/overdue + counter disposisi | M-DASH | permohonan_surat(R), disposisi_surat_masuk(R) |
| `mahasiswa.beranda` | GET | Ringkasan status permohonan | M-DASH | permohonan_surat(R) |

### 2.2 Konfigurasi & Master Data (Admin)

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `admin.konfigurasi.edit` | GET | Profil kampus, tahun akademik, SMTP, format nomor | M-CONFIG | settings(R) — ERD §5.1 |
| `admin.konfigurasi.update` | POST | Simpan konfigurasi | M-CONFIG | settings(R/W), media(W:`file` logo) — ERD §5.1 |
| `admin.unit.*` | resource | CRUD unit | M-CONFIG | units(R/W) |
| `admin.pejabat.*` | resource | CRUD pejabat + assign unit + upload TTD | M-CONFIG | pejabat(R/W), pejabat_unit(W), media(W:`ttd`) |
| `admin.user.*` | resource | CRUD user, activate/role | M-USER | users(R/W), mahasiswa(R/W), model_has_roles(W) |
| `admin.user.import` | POST | Import mahasiswa SIAKAD | M-USER | users(W), mahasiswa(W) |
| `admin.kamus.*` | resource | CRUD kamus placeholder | M-KAMUS | placeholder_definitions(R/W) |
| `admin.kategori.*` | resource | CRUD kategori | M-KATEGORI | kategori_surat(R/W), templates(R:cek pakai) |
| `admin.persyaratan.*` | resource | CRUD persyaratan | M-SYARAT | ref_syarat_surat(R/W), media(W:`template`), syarat_surat(R:cek pakai) |

### 2.3 Template Surat (Admin) — M-TEMPLATE

| Route (name) | HTTP | Halaman/Aksi | Tabel ERD (R/W) |
|---|---|---|---|
| `admin.template.index` | GET | Daftar template (filter kategori/unit/status) | templates(R), kategori_surat(R) |
| `admin.template.create/store` | GET/POST | Upload `.docx` + set metadata | templates(W), template_unit(W), media(W:`docx`), kategori_surat(R), units(R) |
| `admin.template.scan` | (internal saat store) | Deteksi placeholder | template_placeholder_config(W), placeholder_definitions(R) |
| `admin.template.edit/update` | GET/POST | Edit metadata + review placeholder | templates(R/W), template_placeholder_config(R/W), template_unit(W) |
| `admin.template.syarat.store` | POST | Link/buat persyaratan (FindOrCreate) | syarat_surat(W), **ref_syarat_surat(W)** ⚠️ (§4) |
| `admin.template.data-tambahan.*` | POST | CRUD field data tambahan | template_data_tambahan_fields(R/W) |
| `admin.template.coba` | POST | Coba Template (ephemeral) | templates(R), template_placeholder_config(R) — *tanpa W* |
| `admin.template.panduan` | GET | Panduan Placeholder | placeholder_definitions(R) |
| `admin.template.destroy` | DELETE | Nonaktif/hapus (jika tak berpermohonan) | templates(R/W soft) |

### 2.4 Permohonan, Generate, Arsip (Admin)

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `admin.permohonan.index` | GET | Daftar permohonan (filter) | M-PERM-ADM | permohonan_surat(R), mahasiswa(R), templates(R) |
| `admin.permohonan.show` | GET | Detail 4 lapisan (auto→diverifikasi) | M-PERM-ADM | **permohonan_surat(R/W:status)** ⚠️, permohonan_data_tambahan_values(R), permohonan_syarat(R), media(R) |
| `admin.permohonan.approve` | POST | Setujui (pilih pejabat + catatan) | M-PERM-ADM | **permohonan_surat(W)** ⚠️, pejabat(R), activity_log(W) |
| `admin.permohonan.reject` | POST | Tolak (alasan) | M-PERM-ADM | **permohonan_surat(W)** ⚠️, activity_log(W) |
| `admin.permohonan.generate` | GET/POST | Generate dari permohonan (sub-flow A) | M-GENERATE | **permohonan_surat(R/W:selesai)** ⚠️, **surat_tercetak(W)** ⚠️, surat_penandatangan(W), templates(R), template_placeholder_config(R), pejabat(R), media(R/W) |
| `admin.generate.create` | GET | Generate Langsung — pilih template (sub-flow B) | M-GENERATE | templates(R) |
| `admin.generate.cari-mahasiswa` | GET(AJAX) | Cari mahasiswa (Tom Select) | M-GENERATE | mahasiswa(R), users(R) |
| `admin.generate.store` | POST | Generate langsung | M-GENERATE | **surat_tercetak(W)** ⚠️, surat_penandatangan(W), templates(R), pejabat(R), media(R/W) |
| `admin.generate.cek-nomor` | POST(AJAX) | Validasi nomor duplikat | M-GENERATE | surat_tercetak(R) |
| `admin.arsip.index` | GET | Daftar arsip (search) | M-ARSIP | surat_tercetak(R), templates(R), mahasiswa(R) |
| `admin.arsip.show` | GET | Detail arsip + snapshot | M-ARSIP | surat_tercetak(R), surat_penandatangan(R) |
| `admin.arsip.cetak-ulang` | POST | Cetak ulang (lama→digantikan) | M-ARSIP | **surat_tercetak(R/W:status+baru)** ⚠️, surat_penandatangan(W) |
| `admin.arsip.export` | GET | Export Excel | M-ARSIP | surat_tercetak(R) |
| `verify.show` | GET | Verifikasi publik via QR | M-VERIFIKASI | surat_tercetak(R) |

### 2.5 Surat Masuk & Keluar (Admin)

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `admin.surat-masuk.*` | resource | CRUD surat masuk + upload scan | M-MASUK | surat_masuk(R/W), media(W:`scan`,`lampiran`) |
| `admin.surat-masuk.disposisi.*` | resource | CRUD disposisi + update status | M-MASUK | disposisi_surat_masuk(R/W), pejabat(R) |
| `admin.surat-masuk.disposisi.cetak` | GET | Cetak lembar disposisi PDF | M-MASUK | disposisi_surat_masuk(R), surat_masuk(R) |
| `admin.surat-masuk.agenda` | GET | Buku agenda masuk (view+filter) | M-MASUK | surat_masuk(R) |
| `admin.surat-masuk.export` | GET | Export Excel/PDF | M-MASUK | surat_masuk(R) |
| `admin.surat-keluar.*` | resource | CRUD buku agenda keluar (manual) | M-KELUAR | surat_keluar(R/W), media(W:`scan`) |
| `admin.surat-keluar.cek-nomor` | POST(AJAX) | Validasi nomor duplikat | M-KELUAR | surat_keluar(R) |
| `admin.surat-keluar.export` | GET | Export Excel/PDF | M-KELUAR | surat_keluar(R) |

### 2.6 Mahasiswa

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `mahasiswa.ajukan.index` | GET | List jenis surat mandiri | M-MHS-AJUKAN | templates(R), syarat_surat(R) |
| `mahasiswa.ajukan.create` | GET | Form 4 lapisan | M-MHS-AJUKAN | templates(R), template_placeholder_config(R), template_data_tambahan_fields(R), syarat_surat(R), ref_syarat_surat(R), dokumen_mahasiswa(R) |
| `mahasiswa.ajukan.store` | POST | Submit permohonan | M-MHS-AJUKAN | **permohonan_surat(W)** ⚠️, permohonan_data_tambahan_values(W), permohonan_syarat(W), **dokumen_mahasiswa(W)** ⚠️, media(W) |
| `mahasiswa.permohonan.index` | GET | Riwayat permohonan | M-MHS-RIWAYAT | permohonan_surat(R) |
| `mahasiswa.permohonan.show` | GET | Detail + status + download | M-MHS-RIWAYAT | permohonan_surat(R), permohonan_data_tambahan_values(R), permohonan_syarat(R), surat_tercetak(R) |
| `mahasiswa.permohonan.edit/update` | GET/POST | Edit (status pending) | M-MHS-RIWAYAT | **permohonan_surat(W)** ⚠️, permohonan_data_tambahan_values(W), permohonan_syarat(W) |
| `mahasiswa.permohonan.cancel` | POST | Batalkan (pending) | M-MHS-RIWAYAT | **permohonan_surat(W:dibatalkan)** ⚠️ |
| `mahasiswa.permohonan.resubmit` | GET/POST | Ajukan ulang (parent link) | M-MHS-RIWAYAT | **permohonan_surat(W)** ⚠️, permohonan_syarat(W) |
| `mahasiswa.permohonan.download` | GET | Unduh surat (jika `metode=download`) | M-MHS-RIWAYAT | surat_tercetak(R), media(R) |
| `mahasiswa.dokumen.*` | resource | Media library "Dokumen Saya" | M-MHS-DOKUMEN | **dokumen_mahasiswa(R/W)** ⚠️, media(R/W) |
| `mahasiswa.profil.show` | GET | Profil read-only | M-MHS-PROFIL | mahasiswa(R), users(R) |

### 2.7 Cross-cutting

| Route (name) | HTTP | Halaman/Aksi | Modul | Tabel ERD (R/W) |
|---|---|---|---|---|
| `media.upload` | POST(AJAX) | Upload file generik (semua fitur) | M-MEDIA | media(W) |
| `media.show` | GET | Serve file disk private (via Policy) | M-MEDIA | media(R) |
| *(no route)* | queue | Kirim email peristiwa | M-NOTIF | permohonan_surat(R), users/mahasiswa(R) |

---

## §3. Matriks Tabel ERD × Modul

Baris = tabel ERD; kolom = modul yang menyentuhnya. `W` menandai penulis (paling penting untuk deteksi tumpang tindih).

| Tabel ERD | Penulis (W) | Pembaca (R) |
|---|---|---|
| users (§2) | M-USER, M-AUTH | M-AUTH, M-PERM-ADM, M-GENERATE, M-MHS-PROFIL, M-NOTIF |
| mahasiswa (§3) | M-USER | M-PERM-ADM, M-GENERATE, M-ARSIP, M-MHS-PROFIL, M-MHS-AJUKAN |
| units (§1) | M-CONFIG | M-TEMPLATE, M-CONFIG |
| pejabat (§4) + pejabat_unit | M-CONFIG | M-GENERATE, M-PERM-ADM, M-MASUK |
| kategori_surat (§6) | M-KATEGORI | M-TEMPLATE |
| placeholder_definitions (§8) | M-KAMUS | M-TEMPLATE |
| templates (§7) + template_unit | M-TEMPLATE | M-KATEGORI, M-PERM-ADM, M-GENERATE, M-ARSIP, M-MHS-AJUKAN |
| template_placeholder_config (§9) | M-TEMPLATE | M-GENERATE, M-MHS-AJUKAN |
| template_data_tambahan_fields (§13) | M-TEMPLATE | M-MHS-AJUKAN |
| ref_syarat_surat (§10) | **M-SYARAT, M-TEMPLATE** ⚠️ | M-MHS-AJUKAN |
| syarat_surat (§10.1) | M-TEMPLATE | M-MHS-AJUKAN, M-KATEGORI |
| dokumen_mahasiswa (§11) | **M-MHS-DOKUMEN, M-MHS-AJUKAN** ⚠️ | M-MHS-RIWAYAT |
| permohonan_surat (§12) | **M-MHS-AJUKAN, M-MHS-RIWAYAT, M-PERM-ADM, M-GENERATE** ⚠️⚠️ | M-DASH, M-NOTIF, M-ARSIP |
| permohonan_data_tambahan_values (§14) | M-MHS-AJUKAN, M-MHS-RIWAYAT | M-PERM-ADM |
| permohonan_syarat (§15) | M-MHS-AJUKAN, M-MHS-RIWAYAT | M-PERM-ADM |
| surat_tercetak (§16) | **M-GENERATE, M-ARSIP** ⚠️ | M-MHS-RIWAYAT, M-VERIFIKASI, M-DASH |
| surat_penandatangan (§17) | M-GENERATE, M-ARSIP | M-ARSIP |
| surat_masuk (§18) | M-MASUK | M-DASH |
| disposisi_surat_masuk (§19) | M-MASUK | M-DASH |
| surat_keluar (§21) | M-KELUAR | — |
| settings (§5.1) | M-CONFIG | M-GENERATE, M-VERIFIKASI, M-NOTIF, M-MASUK, M-KELUAR |
| media (Spatie, K4) | **semua via M-MEDIA** | semua |
| activity_log (Spatie) | semua (cross-cutting) | M-USER, Super Admin |

---

## §4. Deteksi Tumpang Tindih (Overlap) & Aturan Kepemilikan

Tabel yang ditulis **lebih dari satu modul** = titik rawan konflik. Aturan: setiap tabel punya **satu modul pemilik** (owner) yang mendefinisikan skema & aturan bisnisnya; modul lain menulis hanya lewat **kontrak yang jelas** (Action/Service milik owner), bukan query bebas.

| # | Tabel | Modul penulis | Jenis overlap | Rekomendasi |
|---|---|---|---|---|
| O1 | **permohonan_surat** | M-MHS-AJUKAN, M-MHS-RIWAYAT, M-PERM-ADM, M-GENERATE | **Tinggi** — 4 modul menulis kolom berbeda pada baris yang sama sepanjang lifecycle | **Partisi berdasarkan status (state machine)**. Setiap transisi status via Action khusus (mis. `SubmitPermohonanAction`, `ApprovePermohonanAction`, `FinalizeSuratAction`). Tidak ada modul yang update sembarang kolom. Owner skema = **M-PERM-ADM** (lifecycle). Enum `PermohonanStatus` sebagai SSOT transisi. Lihat catatan bawah. |
| O2 | **surat_tercetak** | M-GENERATE (create), M-ARSIP (cetak ulang → status) | **Sedang** — arsip immutable, tapi 2 modul menulis | M-GENERATE **owner** (satu-satunya yang INSERT). M-ARSIP hanya boleh mengubah `status`→`digantikan` + `replaced_by_id` lewat Action milik M-GENERATE (`SupersedeSuratAction`), tidak UPDATE kolom lain (jaga immutability, ERD §16). |
| O3 | **dokumen_mahasiswa** | M-MHS-DOKUMEN (owner), M-MHS-AJUKAN (create saat upload) | **Rendah** | M-MHS-DOKUMEN **owner**. M-MHS-AJUKAN membuat dokumen lewat `MediaService`+Action milik M-MHS-DOKUMEN (upload di form permohonan = "simpan ke Dokumen Saya sekaligus"). Satu jalur tulis. |
| O4 | **ref_syarat_surat** | M-SYARAT (owner), M-TEMPLATE (FindOrCreate) | **Rendah** | M-SYARAT **owner**. M-TEMPLATE hanya boleh **create** via Action FindOrCreate milik M-SYARAT (`FindOrCreateSyaratAction`) — tidak update/hapus. Cegah duplikasi nama (validasi unik). |
| O5 | **media** | Semua modul | **By design (aman)** | Semua tulis lewat **satu `MediaService` + `MediaUploadController`** (K4, ARCHITECTURE §9). Bukan overlap berbahaya selama tidak ada modul yang menulis tabel `media` langsung. |
| O6 | **users / mahasiswa** | M-USER (owner), M-AUTH (last login) | **Rendah** | M-USER **owner** CRUD. M-AUTH hanya update kolom sesi/login (mis. `last_login` bila ditambah) via Fortify — tidak menyentuh data profil. |
| O7 | **surat_penandatangan** | M-GENERATE, M-ARSIP | **Rendah** | Ikut owner O2 (M-GENERATE). Selalu di-INSERT bersama `surat_tercetak` dalam satu transaksi; tidak diedit terpisah. |

### Prinsip Anti-Overlap (wajib)
1. **Single owner per tabel** — kolom "Owner" di atas. Perubahan skema hanya oleh owner.
2. **Tulis lewat Action/Service owner**, bukan Eloquent bebas dari modul lain (konsisten ARCHITECTURE §6).
3. **Status sebagai kontrak** — `permohonan_surat.status` & `surat_tercetak.status` adalah Enum SSOT; transisi tervalidasi (cegah dua modul menaruh status tak konsisten).
4. **Immutable dijaga di level Action** — M-ARSIP tak boleh UPDATE arsip selain jalur "digantikan".

---

## §5. Shared Services (Cross-cutting) & Pemakainya

Reusable service (ARCHITECTURE §6, §9) yang dipakai lintas modul — titik SSOT logika, bukan tumpang tindih data:

| Service | Fungsi | Modul pemakai |
|---|---|---|
| `TemplateSubstitutionService` | Substitusi `.docx` | M-TEMPLATE (coba), M-GENERATE (sub-flow A & B) |
| `NomorSuratService` | Saran & validasi nomor surat | M-GENERATE, (referensi pola juga M-KELUAR untuk cek duplikat) |
| `MediaService` + `MediaUploadController` | Upload file (SSOT, K4) | M-CONFIG, M-SYARAT, M-TEMPLATE, M-MASUK, M-KELUAR, M-MHS-AJUKAN, M-MHS-DOKUMEN, M-GENERATE |
| `PdfService` | Konversi PDF | M-GENERATE, M-MASUK (lembar disposisi), M-ARSIP/M-KELUAR (export) |
| Spatie ActivityLog | Audit | semua modul yang mengubah data penting |
| Spatie Permission (Gate/Policy) | Otorisasi | semua route (middleware/Policy) |

---

## §6. Temuan & Rekomendasi (untuk dokumen lain)

Peta ini memunculkan beberapa hal terkait cakupan ERD/PRD:

| # | Temuan | Dampak | Status / Rekomendasi |
|---|---|---|---|
| C1 | **Tabel `settings`/konfigurasi** untuk M-CONFIG (PRD F2): nama kampus, kode, logo, alamat, tahun akademik, SMTP, format nomor | M-CONFIG butuh tabel target | ✅ **RESOLVED — tabel `settings` ditambahkan di ERD §5.1** (key-value bergrup + `HasMedia` untuk logo, SSOT via `SettingService`+cache) |
| C2 | **`kode_klasifikasi`** (surat masuk/keluar) masih string bebas, dipakai 2 modul (M-MASUK, M-KELUAR) | Potensi inkonsistensi kode | Terbuka — sinkron dengan ERD §24: pertimbangkan master `klasifikasi_surat` (dipakai bersama 2 modul) |
| C3 | **`tahun_akademik`** untuk nomor agenda per tahun (M-MASUK, M-KELUAR) & tahun surat | Dipakai lintas modul | ✅ **RESOLVED — disimpan di `settings` (ERD §5.1, key `tahun_akademik_aktif`)** sebagai SSOT, tidak hardcode |
| C4 | **`permohonan_surat` ditulis 4 modul (O1)** | Risiko konflik tertinggi | Terbuka — formalkan **state machine** transisi status di satu tempat (Enum + Action) sebelum implementasi |

---

*FEATURE_MAP ini selaras dengan `PRD.md`, `ERD.md`, dan `ARCHITECTURE.md`. Jika route/tabel berubah, perbarui peta ini agar deteksi tumpang tindih tetap akurat. Titik `⚠️` menandai penulisan tabel bersama — rujuk §4 sebelum menambah penulis baru.*
