# BACKLOG — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Dokumen** | Backlog Implementasi (Milestone → Task) |
| **Versi** | 0.3 (M1-M10 terdetail & **bebas blocker**; M0 masih daftar) |
| **Tanggal** | 19 Juli 2026 |
| **Basis** | `PRD.md` (F1-F13), `ERD.md`, `ARCHITECTURE.md` (§0 bootstrap, §18 urutan fitur), `FEATURE_MAP.md`, `UX_SPEC.md`, `docs/decisions/DECISIONS.md` |
| **Status dokumen** | **M1–M10 sudah detail & tanpa item ⚠️ mengikat** (semua diputuskan, D-001 s/d D-006 di `DECISIONS.md`). **M0 (Bootstrap) masih daftar ringkas** — detailkan bila diperlukan sebelum eksekusi. |

---

## §0. Konvensi

### Skema ID
`M{milestone}-T{urut}` — mis. `M2-T4`. Referensi lintas dokumen memakai ID ini.

### Status task
`☐ TODO` · `◐ WIP` · `✅ DONE` · `⚠️` (menyentuh item terbuka — lihat UX_SPEC rekap ⚠️).

### Urutan
Task diurutkan **sesuai dependensi**: migration & model → service → controller/route → view → test. Task lintas-milestone bergantung ke milestone sebelumnya (fondasi → fitur hilir).

### Template detail per task (diisi nanti — belum di tahap ini)
Saat detail, **setiap** task memakai template tetap:
```
### {ID} — {Judul}                                    [status]
- **Tujuan**: …
- **Depends on**: {ID lain / dokumen}
- **Acceptance Criteria** (Given–When–Then, testable):
    - Given … When … Then …
- **Batasan/Guardrail**: …
- **Definition of Done**: … termasuk perintah test spesifik (mis. `php artisan test --filter=XxxTest`)
```
**Aturan ukuran**: bila satu task tak muat dijelaskan dalam AC ringkas → dipecah lagi saat detailing.

> **Standar tambahan (setiap task yang punya langkah View)**: teks statis FE (label/tombol/judul kolom/pesan) **wajib** via `lang/id/*.php` (ARCHITECTURE §11.5) — tidak hardcode di Blade/JS. Berlaku sejak M0-T10 selesai.

### Legenda ⚠️ (konflik/keterbukaan dengan dokumen lain)
Task bertanda ⚠️ bergantung pada keputusan yang **belum final** (rujuk nomor item di UX_SPEC "Rekap ⚠️"). Harus diputuskan sebelum task di-detail/di-kerjakan.

---

## MILESTONE 0 — Project Bootstrap
> Fondasi teknis (ARCHITECTURE §0). **Gate**: tidak ada fitur bisnis dimulai sebelum M0 selesai + smoke test hijau.

| ID | Task | Dep |
|---|---|---|
| M0-T1 | Inisialisasi Laravel + git + `.env` (MySQL, disk private) + config dasar (timezone/locale, `config/surat.php`) | — |
| M0-T2 | Install & konfigurasi library backend (Spatie Permission/Media/ActivityLog, Yajra, PHPWord, mPDF, Fortify) + publish & migrate bawaan paket | M0-T1 |
| M0-T3 | Setup tooling kualitas (Pint, Larastan, Pest,laravel boost make sure boost mcp work in claude) | M0-T1 |
| M0-T4 | Install & konfigurasi frontend npm+Vite (AdminLTE, Bootstrap, jQuery, DataTables, Select2, FilePond, SweetAlert2, FontAwesome) | M0-T1 |
| M0-T5 | Master layout AdminLTE (app/mahasiswa/auth/guest) + partial sidebar/topbar/breadcrumb/flash | M0-T4 |
| M0-T6 | Blade components inti (`x-ui.*`, `x-form.*`) + tema `app.css` + konvensi class `app-*`/`js-*` | M0-T5 |
| M0-T7 | Global JS init (DataTables, Select2, FilePond, SweetAlert `js-flash`/`js-confirm`) | M0-T4 |
| M0-T8 | Fondasi auth Fortify + middleware role + `MediaService` + `MediaUploadController` generik | M0-T2, M0-T5 |
| M0-T9 | Seeder inti produksi (RolePermission, Setting, PlaceholderDefinition) + smoke test fondasi | M0-T8 |
| M0-T10 | Setup lokalisasi FE — struktur `lang/id/common.php` + `lang/id/table.php` + konvensi key & pemakaian `__()` (ARCHITECTURE §11.5); SSOT teks statis wajib sejak fitur pertama | M0-T6 |

---

## MILESTONE 1 — Fondasi Data & Master (F1, F2, F4, F12, F13) — **DETAIL**
> Master data prasyarat Template (M2). Skema core dulu, lalu auth, lalu master. Peta ringkas ada di §2.

---

### M1-T1 — Migration & Model Core  ☐
- **Referensi**: ERD §1 (`units`), §2 (`users`), §3 (`mahasiswa`), §4 + §4.1 (`pejabat`, `pejabat_unit`), §5.1 (`settings`); ARCHITECTURE §0 (konvensi ENUM→string, softDeletes), §3 (layering), §9-K4 (file via Media Library).
- **Depends on**: M0-T2 (migrasi paket Spatie).
- **Tujuan**: Menyediakan skema DB + model Eloquent entitas inti sebagai fondasi seluruh master & fitur.
- **TODO**:
    - Migration: `units`, `users` (tambah `unit_id`, `is_active`, **`must_change_password` boolean default false**, softDeletes; **tanpa** kolom `role`), `mahasiswa`, `pejabat`, `pejabat_unit`, `settings`.
    - Model + relasi: `Unit` (self `parent`), `User` (Spatie `HasRoles`, 1-1 `mahasiswa`, `unit`), `Mahasiswa` (belongsTo `user`), `Pejabat` (`HasMedia` collection `ttd`, belongsToMany `units`), `Setting` (`HasMedia` collection `file`).
    - Cast/fillable; nilai enum sebagai `string` + validasi aplikasi.
    - Factory: `UnitFactory`, `UserFactory` (+state `mahasiswa`), `PejabatFactory`, `SettingFactory`.
- **Batasan/Guardrail**: role **tidak** jadi kolom (Spatie); file **tidak** jadi kolom path (Media Library, K4); `unique` pada `users.email`, `mahasiswa.nim`, `units.kode`.
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then semua tabel core terbentuk dengan kolom & unique sesuai ERD.
    - Given model, When buat `User`+`Mahasiswa`, Then relasi 1-1 `user->mahasiswa` bekerja dua arah.
    - Given `Pejabat` di-attach 2 `unit`, When `pejabat->units`, Then mengembalikan 2 (n-n).
- **Definition of Done**: `migrate:fresh` tanpa error; factory valid; test relasi hijau (`php artisan test --filter=CoreSchemaTest`); Pint + Larastan pass.

---

### M1-T2 — F1 Autentikasi (Login/Logout + Paksa Ganti Password)  ☐
- **Referensi**: PRD F1; UX_SPEC 1.A.1 (keputusan ✅ `must_change_password`); ARCHITECTURE §8 (Fortify + Spatie), §2 (Fortify, ActivityLog).
- **Depends on**: M0-T8 (Fortify + middleware role), M1-T1 (`users` + kolom `must_change_password`).
- **Tujuan**: Login email+password dengan redirect per role, tolak user nonaktif, paksa ganti password bila `must_change_password=true`, catat aktivitas.
- **TODO**:
    - Konfigurasi Fortify `authenticate` (verifikasi kredensial + cek `is_active`).
    - Middleware/redirect: setelah login sukses, cek `must_change_password` → **true** arahkan ke halaman ganti password wajib (form password baru+konfirmasi, tanpa password lama) sebelum bisa akses halaman lain; **false** → lanjut redirect by role (`super_admin`/`admin_surat` → `admin.dashboard`, `mahasiswa` → `mahasiswa.beranda`).
    - Setelah ganti password sukses → set `must_change_password=false` → lanjut redirect by role.
    - Log `login`/`logout` ke ActivityLog; error via SweetAlert/inline.
- **Batasan/Guardrail**: role via Spatie; `is_active=false` ditolak dengan pesan khusus; tidak ada registrasi publik; pesan error tidak membocorkan field mana yang salah; user dengan `must_change_password=true` **tidak bisa** mengakses halaman lain sebelum ganti password (middleware guard).
- **Acceptance Criteria**:
    - Given user aktif `admin_surat`, When login benar, Then redirect `admin.dashboard` + tercatat 'login'.
    - Given user `is_active=false`, When login benar, Then ditolak "akun nonaktif" & tidak terautentikasi.
    - Given kredensial salah, When submit, Then kembali dengan error umum.
    - Given user `must_change_password=true`, When login benar, Then diarahkan ke halaman ganti password wajib (bukan dashboard); mencoba akses halaman lain → dialihkan kembali ke halaman itu.
    - Given user berhasil ganti password di halaman wajib, When selesai, Then `must_change_password=false` & lanjut redirect by role.
- **Definition of Done**: feature test 5 skenario hijau (`php artisan test --filter=AuthLoginTest`); Pint/Larastan pass.

---

### M1-T3 — F1 Ubah Password  ☐
- **Referensi**: UX_SPEC 4.G (keputusan ✅ Fortify `update-password`); ERD §24 "Sudah Diputuskan".
- **Depends on**: M1-T2.
- **Tujuan**: Semua user (mahasiswa & admin) dapat mengubah password sendiri.
- **TODO**: aktifkan Fortify `update-password`; section "Ubah Password" di halaman Profil (mahasiswa) & pengaturan akun (admin); validasi password lama + konfirmasi.
- **Batasan/Guardrail**: pakai fitur bawaan Fortify (tanpa mekanisme kustom); tidak ada perubahan skema.
- **Acceptance Criteria**:
    - Given user login, When isi password lama benar + baru valid + konfirmasi cocok, Then password terganti + notifikasi sukses.
    - Given password lama salah, When submit, Then `✖` "password lama salah", password tak berubah.
- **Definition of Done**: feature test 2 skenario (`php artisan test --filter=UpdatePasswordTest`).

---

### M1-T4 — F2 Konfigurasi Sistem (Settings)  ☐
- **Referensi**: PRD F2; ERD §5.1 (`settings`); UX_SPEC 1.C.1; ARCHITECTURE §9 (media logo), §2 (mail dari settings).
- **Depends on**: M1-T1.
- **Tujuan**: Admin mengelola konfigurasi kampus (identitas, tahun akademik, SMTP, helper nomor) dari satu form bergrup.
- **TODO**:
    - `SettingService` (SSOT baca/tulis + cache `rememberForever`, bust saat simpan).
    - Form bergrup (Umum/Akademik/Penomoran/SMTP) render per `type` (text/textarea/media/encrypted).
    - Logo via Media Library (disk public); password SMTP cast `encrypted`.
    - ServiceProvider override `config('mail.*')` dari settings.
- **Batasan/Guardrail**: nilai kunci sebagai baris `settings` (bukan kolom tetap); logo lewat Media Library; password tak pernah ditampilkan (kosong = tak diubah).
- **Acceptance Criteria**:
    - Given form settings, When simpan nilai valid, Then tersimpan + cache ter-bust + `✔`.
    - Given `Settings::get('nama_universitas')`, When dipanggil setelah simpan, Then mengembalikan nilai baru (dari cache).
    - Given upload logo, When simpan, Then file di collection `file` (disk public) & tampil preview.
- **Definition of Done**: feature test simpan+baca+logo (`php artisan test --filter=SettingTest`); `SettingSeeder` tersedia.

---

### M1-T5 — Manajemen Unit (CRUD)  ☐
- **Referensi**: PRD F2; ERD §1; UX_SPEC 1.C.2; ARCHITECTURE §17 (filter), §11.4 (SweetAlert konfirmasi).
- **Depends on**: M1-T1.
- **Tujuan**: CRUD unit penerbit (dengan hierarki `parent_id`) + filter/search.
- **TODO**: FormRequest (nama, kode unik, parent, is_active); Controller resource tipis; index DataTables server-side (Yajra) + `x-ui.filter`; form modal; hapus via `js-confirm` (SweetAlert).
- **Batasan/Guardrail**: `kode` unik; unit yang dipakai (template/permohonan) tidak dihapus — nonaktifkan (guard); index wajib filter §17.
- **Acceptance Criteria**:
    - Given form unit, When simpan kode unik, Then tersimpan; When kode duplikat, Then `✖` inline.
    - Given unit dipakai template, When hapus, Then ditolak dengan pesan; opsi nonaktif tersedia.
- **Definition of Done**: feature test CRUD + guard hapus (`php artisan test --filter=UnitTest`).

---

### M1-T6 — Manajemen Pejabat (CRUD + Multi-Unit + TTD)  ☐
- **Referensi**: PRD F2; ERD §4 + §4.1; UX_SPEC 1.C.3; ARCHITECTURE §9 (TTD private).
- **Depends on**: M1-T1.
- **Tujuan**: CRUD pejabat penandatangan + assign banyak unit + upload TTD opsional.
- **TODO**: FormRequest; multi-unit Select2 → sync `pejabat_unit`; upload TTD (FilePond → media `ttd`, disk private); index + filter (unit/status) dengan indikator ada/tidaknya TTD.
- **Batasan/Guardrail**: TTD **opsional** (kosong=basah); file TTD disk **private**; pejabat dipakai arsip → nonaktifkan, bukan hapus (snapshot arsip aman).
- **Acceptance Criteria**:
    - Given pejabat + 2 unit, When simpan, Then `pejabat_unit` berisi 2 baris.
    - Given pejabat tanpa TTD, When simpan, Then valid; indikator kolom TTD = "—".
    - Given upload TTD, When simpan, Then file di collection `ttd` disk private (tidak diakses via URL publik).
- **Definition of Done**: feature test CRUD+multiunit+TTD (`php artisan test --filter=PejabatTest`).

---

### M1-T7 — F1 Manajemen User (CRUD + Role)  ☐
- **Referensi**: PRD F1; ERD §2, §3, §5, §24 (D-005); UX_SPEC 2.A.1/2.A.2; ARCHITECTURE §8; `docs/decisions/DECISIONS.md` (D-005).
- **Depends on**: M1-T1.
- **Tujuan**: Super Admin mengelola user + assign role + profil mahasiswa (kondisional).
- **TODO**: FormRequest (email/nim unik, role, unit; field mahasiswa muncul bila role=mahasiswa); assign role Spatie; buat/update `mahasiswa` 1-1 bila role mahasiswa; toggle `is_active`; index + filter (role/unit/status). **Tidak ada aksi hapus permanen di UI** (D-005) — hanya toggle nonaktif.
- **Batasan/Guardrail**: **khusus Super Admin** (Policy/middleware); **tidak ada hard delete** (D-005, final) — nonaktifkan (`is_active=false`) satu-satunya cara; password wajib saat create, opsional saat edit.
- **Acceptance Criteria**:
    - Given role=mahasiswa, When simpan, Then `users` + `mahasiswa` (1-1) terbuat + role ter-assign.
    - Given email/nim duplikat, When simpan, Then `✖` inline.
    - Given non-super-admin, When akses menu user, Then 403.
- **Definition of Done**: feature test CRUD + otorisasi (`php artisan test --filter=UserManagementTest`).

---

### M1-T8 — F1 Import Mahasiswa SIAKAD  ☐
- **Referensi**: PRD §4.7; UX_SPEC 2.A.3 & 1.A.1 (keputusan ✅ password acak + must_change_password); ERD §2 (`must_change_password`), §3 (tanpa `fakultas`, D-001); `docs/decisions/DECISIONS.md` (D-001).
- **Depends on**: M1-T7, M1-T1 (kolom `must_change_password`).
- **Tujuan**: Import massal akun mahasiswa dari file SIAKAD dengan pratinjau & aman terhadap duplikat, tanpa mengimpor password.
- **TODO**: unduh template; upload `.xlsx/.csv`; parser + **pratinjau** dengan validasi per baris; import (queue bila besar) buat `users`(role mhs, **password acak `Hash::make(Str::random())`**, `must_change_password=true`)+`mahasiswa`; ringkasan hasil (tanpa menampilkan password acak — mahasiswa ganti sendiri di login pertama, M1-T2).
- **Batasan/Guardrail**: kolom impor `nim, nama, email, prodi` (**password TIDAK ada di file import**; **`fakultas` tidak ada** — dihapus dari skema, D-001); password digenerate sistem (bcrypt, konsisten Laravel) + wajib ganti saat login pertama; **duplikat di-skip** bila `email`/`nim` sudah ada (user existing tidak disentuh, termasuk passwordnya).
- **Acceptance Criteria**:
    - Given file valid (kolom `nim,nama,email,prodi` saja), When preview, Then baris valid/invalid ditandai sebelum commit.
    - Given baris dengan email/nim sudah ada, When import, Then baris tersebut di-skip (bukan update) & dilaporkan.
    - Given import sukses, When selesai, Then tiap mahasiswa baru punya `must_change_password=true` + password ter-hash bcrypt; ringkasan "N diimport, M dilewati" (tanpa expose password).
- **Definition of Done**: feature test preview+skip duplikat+password acak+must_change_password (`php artisan test --filter=ImportMahasiswaTest`).

---

### M1-T9 — F13 Master Kamus Placeholder  ☐
- **Referensi**: PRD F13; ERD §8 (`placeholder_definitions`); UX_SPEC 2.B; ARCHITECTURE §11.4.
- **Depends on**: M1-T1.
- **Tujuan**: Super Admin mengelola kamus placeholder (perilaku deteksi template).
- **TODO**: CRUD (name unik/snake_case, kelompok, input_type, is_overridable); peringatan (SweetAlert) saat edit/hapus entri yang dipakai template aktif; index + filter kelompok.
- **Batasan/Guardrail**: **khusus Super Admin**; kelompok `ttd` tidak perlu didaftarkan (regex); perubahan berdampak semua template → konfirmasi.
- **Acceptance Criteria**:
    - Given entri baru name unik, When simpan, Then tersimpan; When name duplikat/non-snake, Then `✖`.
    - Given entri dipakai template, When edit, Then muncul konfirmasi dampak sebelum simpan.
- **Definition of Done**: feature test CRUD + otorisasi (`php artisan test --filter=PlaceholderDefinitionTest`); seeder produksi tersedia (dari M0-T9).

---

### M1-T10 — F12 Master Kategori Surat  ☐
- **Referensi**: PRD F12; ERD §6 (`kategori_surat`); UX_SPEC 2.C.
- **Depends on**: M1-T1.
- **Tujuan**: CRUD kategori template + guard hapus bila dipakai.
- **TODO**: CRUD (nama, is_active); kolom "dipakai N template" (count); guard hapus (FK restrict) → nonaktifkan; index + search.
- **Batasan/Guardrail**: tidak bisa dihapus bila dirujuk template aktif.
- **Acceptance Criteria**:
    - Given kategori dipakai template, When hapus, Then ditolak; nonaktif tersedia.
    - Given kategori baru, When simpan, Then muncul di dropdown form template (M2).
- **Definition of Done**: feature test CRUD + guard (`php artisan test --filter=KategoriSuratTest`).

---

### M1-T11 — F4 Master Persyaratan Surat  ☐
- **Referensi**: PRD F4; ERD §10 (`ref_syarat_surat`); UX_SPEC 2.D; ARCHITECTURE §9.
- **Depends on**: M0-T8 (MediaService).
- **Tujuan**: CRUD persyaratan reusable + file contoh opsional (untuk diunduh mahasiswa).
- **TODO**: CRUD (nama, deskripsi, accepted_types, max_size_mb); upload `template_file` (media `template`, opsional); kolom "dipakai N template"; guard hapus bila dipakai; index + search.
- **Batasan/Guardrail**: file contoh via Media Library; tidak bisa dihapus bila dipakai template aktif; pola **FindOrCreate inline** (wireframe di UX 3.D) diimplementasikan saat M2-T6.
- **Acceptance Criteria**:
    - Given persyaratan + file contoh, When simpan, Then file tersimpan & tombol unduh tersedia.
    - Given persyaratan dipakai template, When hapus, Then ditolak.
- **Definition of Done**: feature test CRUD + file + guard (`php artisan test --filter=RefSyaratSuratTest`).

---

### M1-T12 — Dashboard Admin & Beranda Mahasiswa  ☐
- **Referensi**: PRD F6/F9; UX_SPEC 1.B.1 & 1.B.2 (keputusan SLA `created_at`); ERD §12, §19; `docs/decisions/DECISIONS.md` (D-004, rumus SLA).
- **Depends on**: M1-T2. *(Angka permohonan/disposisi bergantung M3/M4/M7 — Phase awal boleh tampil 0.)*
- **Tujuan**: Halaman ringkasan per role: admin (pending/deadline/overdue + disposisi), mahasiswa (status permohonan + tombol ajukan).
- **TODO**: stat card admin dari `permohonan_surat.status`; hitung deadline = `created_at` + `sla_hari_kerja` **hari kerja** (skip Sabtu/Minggu, **tanpa** hari libur nasional — D-004) → bandingkan ke hari ini untuk kelompok mendekati(H-1)/overdue; `disposisi_surat_masuk.status`; kartu klik → index terfilter; beranda mahasiswa (permohonan aktif + tombol Ajukan).
- **Batasan/Guardrail**: **SLA pakai `created_at`**, hanya hitung status ≥ `pending` (draft dikecualikan); perhitungan hari kerja **skip Sabtu/Minggu saja** (D-004, bukan kalender hari kalender penuh, tanpa tabel hari libur); read-only (tanpa form); kartu kosong tampil 0.
- **Acceptance Criteria**:
    - Given permohonan pending, When buka dashboard admin, Then kartu Pending menghitung benar & klik menuju index terfilter.
    - Given permohonan dengan `sla_hari_kerja=3` diajukan Kamis, When dihitung, Then deadline jatuh Selasa depan (Sabtu/Minggu di-skip, bukan Minggu depan).
    - Given mahasiswa tanpa permohonan, When buka beranda, Then tampil ajakan "Ajukan surat pertama".
- **Definition of Done**: feature test render + angka + perhitungan hari kerja (`php artisan test --filter=DashboardTest`).

---

## MILESTONE 2 — Template Surat (F3) — **DETAIL**
> Inti sistem. Prasyarat Permohonan (M3) & Generate (M5). Peta ringkas ada di §2.

---

### M2-T1 — Migration & Model Template  ☐
- **Referensi**: ERD §7 (`templates`), §7.1 (`template_unit`), §9 (`template_placeholder_config`), §10.1 (`syarat_surat`), §13 (`template_data_tambahan_fields`); ARCHITECTURE §0 (konvensi), §9-K4 (docx via Media Library).
- **Depends on**: M1-T1, M1-T10 (`kategori_surat`), M1-T11 (`ref_syarat_surat`).
- **Tujuan**: Skema + model untuk template dan tabel turunannya (unit, placeholder config, syarat, data tambahan).
- **TODO**:
    - Migration: `templates` (`kategori_id` FK restrict, `tipe_pemohon`/`status` string, softDeletes; **tanpa** `unit_id`), `template_unit`, `template_placeholder_config`, `syarat_surat`, `template_data_tambahan_fields` (softDeletes).
    - Model + relasi: `Template` (`HasMedia` collection `docx`, belongsTo `kategori`, belongsToMany `units`, hasMany `placeholderConfigs`/`dataTambahanFields`, belongsToMany `refSyarat` via `syarat_surat`), pivot models bila perlu.
    - Enum PHP `TipePemohon` (`mahasiswa`/`umum`) & `TemplateStatus` (`draft`/`aktif`/`nonaktif`) sebagai SSOT.
    - Factory.
- **Batasan/Guardrail**: `templates` **tanpa** kolom `unit_id` (n-n via `template_unit`); docx via Media Library (K4); `syarat_surat.is_required` di **pivot**.
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then semua tabel template terbentuk sesuai ERD.
    - Given `Template` di-attach 2 unit + 2 syarat, When query relasi, Then n-n bekerja & `is_required` terbaca dari pivot.
- **Definition of Done**: `migrate:fresh` OK; test relasi hijau (`php artisan test --filter=TemplateSchemaTest`); Pint/Larastan pass.

---

### M2-T2 — Daftar Template (Index + Filter)  ☐
- **Referensi**: PRD F3; UX_SPEC 3.A; ARCHITECTURE §17 (filter reusable).
- **Depends on**: M2-T1.
- **Tujuan**: Menampilkan daftar template dengan filter kategori/unit/status + search, sebagai pintu masuk kelola template.
- **TODO**: index DataTables server-side (Yajra); `x-ui.filter` (kategori/unit/status + search nama); kolom `tipe_pemohon`, `is_permohonan_mandiri`, badge `status`; link Panduan Placeholder & tombol Tambah.
- **Batasan/Guardrail**: filter §17 wajib; badge status konsisten tema §11.3.
- **Acceptance Criteria**:
    - Given template beragam status, When filter `status=aktif`, Then hanya template aktif tampil.
    - Given search nama, When ketik, Then hasil terfilter server-side.
- **Definition of Done**: feature test index+filter (`php artisan test --filter=TemplateIndexTest`).

---

### M2-T3 — Buat Template: Upload `.docx` + Metadata  ☐
- **Referensi**: PRD F3; ERD §7, §7.1; UX_SPEC 3.B; ARCHITECTURE §7 (FormRequest), §9 (upload docx).
- **Depends on**: M2-T1, M0-T8 (MediaService).
- **Tujuan**: Admin membuat template baru (metadata + file `.docx`) berstatus draft, lalu lanjut ke scan.
- **TODO**: `StoreTemplateRequest` (nama, kategori exists, unit multi, tipe_pemohon, sla nullable, is_permohonan_mandiri, file mimes:docx max 10MB); `Template::create` (`created_by`, status=draft) + attach `template_unit` + media `docx`; redirect ke hub (M2-T5) memicu scan (M2-T4).
- **Batasan/Guardrail**: hanya `.docx` ≤10MB (`✖` selain itu); status awal `draft`; unit boleh >1 (n-n).
- **Acceptance Criteria**:
    - Given form valid + file docx, When simpan, Then template draft terbuat + file di collection `docx` + redirect hub.
    - Given file non-docx / >10MB, When simpan, Then `✖` validasi, tidak tersimpan.
- **Definition of Done**: feature test simpan + validasi file (`php artisan test --filter=TemplateStoreTest`).

---

### M2-T4 — Scan & Deteksi Placeholder  ☐
- **Referensi**: perancangan-murni §4.4 (urutan deteksi, kamus, regex TTD, inferensi); PRD F3; ERD §8 (baca), §9 (tulis); UX_SPEC 3.C.1; ARCHITECTURE §6 (Action).
- **Depends on**: M2-T3, M1-T9 (`placeholder_definitions`).
- **Tujuan**: Mendeteksi semua `{{placeholder}}` dari `.docx` dan menyimpan konfigurasi awal tiap placeholder.
- **TODO**:
    - `ScanPlaceholderAction`: ekstrak `{{...}}` dari seluruh bagian docx (paragraf, tabel, header, footer, textbox).
    - Urutan per placeholder: regex Slot TTD → exact-match kamus → placeholder bebas (inferensi tipe dari nama).
    - Tulis baris `template_placeholder_config` (placeholder_name, filled_by default, tipe_input, label auto-transform, urutan).
    - **Auto-remediasi (BR-04)**: `tipe_pemohon=umum` + placeholder profil → `filled_by=admin` + info non-blocking.
- **Batasan/Guardrail**: kelompok `ttd` via regex (bukan lookup kamus); `label` auto = Title Case dari nama; scan idempoten (re-scan mengganti config, tapi jangan hapus override admin bila memungkinkan — atau tandai).
- **Acceptance Criteria**:
    - Given docx berisi `{{nama_mahasiswa}}`,`{{nama_perusahaan}}`,`{{ttd_1}}`, When scan, Then 3 baris config: profil→sistem, bebas→mahasiswa(text), ttd_1→admin(image).
    - Given `tipe_pemohon=umum` + `{{nama_mahasiswa}}`, When scan, Then `filled_by` untuk profil = `admin` (auto-remediasi) + flag info.
    - Given docx tanpa `{{...}}`, When scan, Then 0 baris + peringatan.
- **Definition of Done**: unit+feature test deteksi & remediasi (`php artisan test --filter=ScanPlaceholderTest`).

---

### M2-T5 — Hub Template & Review Placeholder  ☐
- **Referensi**: PRD F3; ERD §9; UX_SPEC 3.C (hub), 3.C.1 (review).
- **Depends on**: M2-T4.
- **Tujuan**: Halaman detail/edit template (hub section) + tabel review placeholder yang bisa dikoreksi admin.
- **TODO**: halaman hub (section Informasi/Placeholder/Persyaratan/Data Tambahan); tabel editable `template_placeholder_config` (override `filled_by`/`tipe_input`/`label`/`is_required`/urutan drag); simpan per perubahan/tombol; edit metadata (reuse M2-T3 request).
- **Batasan/Guardrail**: kolom `label` dinonaktifkan untuk `filled_by=sistem`/`ttd`; slot TTD dikelompokkan visual; perubahan tak mengubah file docx.
- **Acceptance Criteria**:
    - Given baris placeholder, When ubah `filled_by`+label & simpan, Then tersimpan di `template_placeholder_config`.
    - Given auto-remediasi sebelumnya, When admin kembalikan ke `sistem` manual, Then override diterima.
- **Definition of Done**: feature test review+override (`php artisan test --filter=TemplateReviewTest`).

---

### M2-T6 — Setup Persyaratan (FindOrCreate Inline)  ☐
- **Referensi**: PRD F3/F4; ERD §10, §10.1; UX_SPEC 3.D (modal inline); ARCHITECTURE §6.
- **Depends on**: M2-T1, M1-T11.
- **Tujuan**: Menautkan persyaratan ke template; bila belum ada, buat baru inline tanpa pindah halaman.
- **TODO**: Select2 cari `ref_syarat_surat` → link ke `syarat_surat`; modal "Buat Baru" (`FindOrCreateSyaratAction` milik master M-SYARAT) simpan `ref_syarat_surat` + langsung link; toggle `is_required` + drag urutan; unlink.
- **Batasan/Guardrail**: create hanya via Action milik M-SYARAT (owner tabel — FEATURE_MAP O4); `is_required`/`urutan` di pivot; nama syarat unik.
- **Acceptance Criteria**:
    - Given persyaratan ada, When pilih, Then ter-link (`syarat_surat`).
    - Given nama baru, When "Buat Baru", Then `ref_syarat_surat` terbuat + langsung ter-link.
    - Given item ter-link, When toggle wajib/drag, Then `is_required`/`urutan` tersimpan.
- **Definition of Done**: feature test link + findorcreate (`php artisan test --filter=TemplateSyaratTest`).

---

### M2-T7 — Setup Data Tambahan  ☐
- **Referensi**: PRD F3; ERD §13 (`template_data_tambahan_fields`); UX_SPEC 3.E.
- **Depends on**: M2-T1.
- **Tujuan**: Admin mendefinisikan field data tambahan (diisi mahasiswa, tak masuk isi surat).
- **TODO**: CRUD field (label, `field_key` auto dari label, tipe_input, is_required, helper_text, urutan drag); soft delete.
- **Batasan/Guardrail**: `field_key` auto-generate (bisa diedit, unik per template); hard delete di-RESTRICT bila sudah ada nilai (soft delete).
- **Acceptance Criteria**:
    - Given label "No. HP Aktif", When tambah, Then `field_key`=`no_hp_aktif` otomatis.
    - Given field punya nilai permohonan, When hapus permanen, Then dicegah (soft delete saja).
- **Definition of Done**: feature test CRUD + key auto + guard (`php artisan test --filter=DataTambahanTest`).

---

### M2-T8 — Coba Template (Preview Ephemeral)  ☐
- **Referensi**: PRD F3; UX_SPEC 3.F; ARCHITECTURE §8 (TemplateSubstitutionService), §2.1 (docx).
- **Depends on**: M2-T5.
- **Tujuan**: Uji generate template ke `.docx` tanpa menyimpan arsip.
- **TODO**: modal flat form semua placeholder (tanpa validasi wajib); `TemplateSubstitutionService` **versi awal** (substitusi teks docx saja — TTD/QR menyusul M5-T2); header "PREVIEW"; download; **tidak** menyentuh `surat_tercetak`.
- **Batasan/Guardrail**: ephemeral (nol tulis DB); output `.docx` Phase 1; tidak butuh LibreOffice (docx langsung).
- **Acceptance Criteria**:
    - Given isi contoh, When Generate & Download, Then file `.docx` ter-substitusi + berheader PREVIEW.
    - Given proses selesai, When cek DB, Then tidak ada entri `surat_tercetak`.
- **Definition of Done**: feature test preview + no-persist (`php artisan test --filter=CobaTemplateTest`).

---

### M2-T9 — Panduan Placeholder  ☐
- **Referensi**: PRD F3; ERD §8; UX_SPEC 3.G; perancangan-murni §4.4.
- **Depends on**: M1-T9.
- **Tujuan**: Halaman referensi placeholder (read-only) + konvensi, agar admin mudah menyusun file Word.
- **TODO**: render `placeholder_definitions` dikelompokkan per `kelompok` + konvensi slot TTD + aturan inferensi bebas; tombol copy per item.
- **Batasan/Guardrail**: read-only; sumber dari kamus (bukan hardcode).
- **Acceptance Criteria**:
    - Given kamus terisi, When buka panduan, Then placeholder tampil per kelompok dengan tombol copy.
- **Definition of Done**: feature test render (`php artisan test --filter=PanduanPlaceholderTest`).

---

### M2-T10 — Aktivasi & Guard Hapus Template  ☐
- **Referensi**: PRD F3; UX_SPEC 3.A (guard hapus), 3.C.2 (aktivasi); ERD §7.
- **Depends on**: M2-T5.
- **Tujuan**: Mengubah status draft→aktif (siap dipakai) dan mencegah hapus template yang sudah berpermohonan.
- **TODO**: aksi Aktifkan (draft→aktif) dengan pengingat bila placeholder mahasiswa tanpa label; guard hapus → soft delete/nonaktif bila ada permohonan/arsip terkait.
- **Batasan/Guardrail**: template aktif berpermohonan **tidak** bisa dihapus (nonaktifkan); hanya aktif+`is_permohonan_mandiri` yang muncul ke mahasiswa (M3-T3).
- **Acceptance Criteria**:
    - Given template draft valid, When Aktifkan, Then status=aktif & muncul di daftar mahasiswa (bila mandiri).
    - Given template punya permohonan, When hapus, Then dicegah + opsi nonaktif.
- **Definition of Done**: feature test aktivasi + guard (`php artisan test --filter=TemplateActivationTest`).

---

## MILESTONE 3 — Permohonan Mahasiswa (F5) — **DETAIL**
> Butuh template aktif (M2). Persona mahasiswa (P3). Peta ringkas ada di §2.

---

### M3-T1 — Migration & Model Permohonan  ☐
- **Referensi**: ERD §11 (`dokumen_mahasiswa`), §12 (`permohonan_surat`), §14 (`permohonan_data_tambahan_values`), §15 (`permohonan_syarat`); ARCHITECTURE §0.
- **Depends on**: M2-T1.
- **Tujuan**: Skema + model untuk permohonan, nilai data tambahan (EAV), file persyaratan, dan dokumen mahasiswa.
- **TODO**:
    - Migration: `permohonan_surat` (self FK `parent_permohonan_id`, `status` string, `isian_form` JSON, `approved_by`/`pejabat_id` nullable, softDeletes), `permohonan_data_tambahan_values` (CASCADE dari permohonan, RESTRICT dari field), `permohonan_syarat` (CASCADE), `dokumen_mahasiswa` (`HasMedia`, softDeletes).
    - Model + relasi: `Permohonan` (belongsTo mahasiswa/template/unit/pejabat, hasMany values/syarat, self `parent`), `DokumenMahasiswa` (`HasMedia` collection `dokumen`).
    - Enum PHP `PermohonanStatus` (`draft`/`pending`/`diverifikasi`/`disetujui`/`ditolak`/`dibatalkan`/`selesai`) — **SSOT transisi** (FEATURE_MAP O1).
    - Factory + state per status.
- **Batasan/Guardrail**: `isian_form` JSON (key=placeholder_name); `unit_id` nullable (dipilih eksplisit — lihat M3-T4); CASCADE/RESTRICT sesuai ERD §14.
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then tabel permohonan terbentuk sesuai ERD.
    - Given permohonan + values, When hapus permohonan, Then values ikut terhapus (CASCADE); When hapus field yang punya value, Then dicegah (RESTRICT).
- **Definition of Done**: `migrate:fresh` OK; test relasi+cascade (`php artisan test --filter=PermohonanSchemaTest`).

---

### M3-T2 — Dokumen Saya (Media Library Mahasiswa)  ☐
- **Referensi**: PRD F5.2; ERD §11; UX_SPEC 4.B; ARCHITECTURE §9.
- **Depends on**: M3-T1, M0-T8 (MediaService).
- **Tujuan**: Mahasiswa mengelola file yang bisa dipakai ulang lintas permohonan.
- **TODO**: list `dokumen_mahasiswa` (nama, kategori syarat, dipakai N); upload (FilePond → media `dokumen`); hapus terjaga; Policy (hanya milik sendiri).
- **Batasan/Guardrail**: hapus **dicegah** bila dipakai permohonan aktif; mahasiswa hanya lihat miliknya (`WHERE mahasiswa_id=self`); file via Media Library.
- **Acceptance Criteria**:
    - Given upload dokumen, When simpan, Then tersimpan di media `dokumen` milik user.
    - Given dokumen dipakai permohonan aktif, When hapus, Then dicegah.
    - Given mahasiswa lain, When akses dokumen bukan miliknya, Then 403.
- **Definition of Done**: feature test upload+guard+otorisasi (`php artisan test --filter=DokumenMahasiswaTest`).

---

### M3-T3 — Ajukan: List Jenis Surat  ☐
- **Referensi**: PRD F5.1; ERD §7, §10.1; UX_SPEC 4.A.1.
- **Depends on**: M3-T1, M2-T10 (template aktif).
- **Tujuan**: Menampilkan daftar template yang bisa diajukan mahasiswa beserta estimasi & jumlah syarat.
- **TODO**: list template `WHERE is_permohonan_mandiri=true AND status=aktif`; tiap item: nama, deskripsi, estimasi `sla_hari_kerja`, jumlah `syarat_surat`; tombol Ajukan; search.
- **Batasan/Guardrail**: hanya template mandiri+aktif; read-only.
- **Acceptance Criteria**:
    - Given campuran template, When buka list, Then hanya `is_permohonan_mandiri=true & aktif` tampil.
    - Given tak ada template mandiri, When buka, Then `∅` "belum ada jenis surat".
- **Definition of Done**: feature test filter list (`php artisan test --filter=AjukanListTest`).

---

### M3-T4 — Ajukan: Form 4 Lapisan + Draft/Submit  ☐
- **Referensi**: PRD F5.1; ERD §7.1, §9, §12, §13, §14, §15 (keputusan ✅ unit tujuan); UX_SPEC 4.A.2; perancangan-murni Fitur 5.1 & §4.1 (Phase 1 satu unit aktif).
- **Depends on**: M3-T3, M3-T2.
- **✅ Unit tujuan (resolved)**: **mahasiswa tidak memilih unit** — tidak ada field unit di form. `SubmitPermohonanAction` **auto-derive** `permohonan_surat.unit_id`: bila `template->units->count()===1` → isi otomatis unit itu; bila 0/>1 (ambigu) → NULL. Unit penerbit sebenarnya dipilih admin nanti di Form Generate (M5-T4).
- **Tujuan**: Mahasiswa mengisi & mengajukan permohonan (4 lapisan diturunkan dari template).
- **TODO**:
    - L1 profil read-only (`mahasiswa`); L2 field `filled_by=mahasiswa` (render per `tipe_input`, label dari `label_mahasiswa`) → `isian_form` JSON; L3 `template_data_tambahan_fields` → `permohonan_data_tambahan_values`; L4 `syarat_surat` (⬇ template / upload / pilih Dokumen Saya) → `permohonan_syarat` (+auto simpan `dokumen_mahasiswa`).
    - `SubmitPermohonanAction`: auto-derive `unit_id` (lihat di atas) + Draft (`status=draft`) vs Ajukan (`status=pending`) + email konfirmasi (M9).
- **Batasan/Guardrail**: field/syarat wajib divalidasi sebelum Ajukan; upload baru otomatis masuk Dokumen Saya; L1 tak bisa diedit (BR-06); transisi status via Action (O1); **tidak ada UI pemilihan unit** oleh mahasiswa.
- **Acceptance Criteria**:
    - Given form lengkap, When Ajukan, Then `permohonan_surat` (pending) + values + syarat tersimpan + email terkirim.
    - Given template terhubung ke tepat 1 unit, When submit, Then `permohonan_surat.unit_id` terisi otomatis unit tersebut.
    - Given template terhubung ke 0 atau >1 unit, When submit, Then `permohonan_surat.unit_id` = NULL (tidak memblokir submit).
    - Given syarat wajib kosong, When Ajukan, Then `✖` blok submit.
    - Given Simpan Draft, When simpan, Then `status=draft`, tidak masuk antrean admin.
- **Definition of Done**: feature test submit+draft+validasi+auto-derive unit (`php artisan test --filter=AjukanPermohonanTest`).

---

### M3-T5 — Riwayat Permohonan (Index + Detail + Status Tracker)  ☐
- **Referensi**: PRD F5.3; ERD §12; UX_SPEC 4.C.1/4.C.2 (keputusan status tracker); ARCHITECTURE §17.
- **Depends on**: M3-T1.
- **Tujuan**: Mahasiswa melihat daftar & detail permohonan miliknya dengan status tracker.
- **TODO**: index (filter status/jenis + search) `WHERE mahasiswa_id=self`; detail 4 lapisan read-only; **status tracker** (badge/stepper dari kolom `status`); tampil `catatan_penolakan` bila ditolak; label "Pengajuan ulang dari #X" bila `parent`.
- **Batasan/Guardrail**: hanya milik sendiri (Policy); **tanpa** tabel log (tracker dari `status` — keputusan ⚠️#6); aksi kondisional per status (M3-T6/7 + download M5).
- **Acceptance Criteria**:
    - Given permohonan milik A, When B mengakses, Then 403.
    - Given status `ditolak`, When buka detail, Then alasan tampil + tombol Ajukan Ulang.
    - Given filter status, When pilih, Then daftar terfilter.
- **Definition of Done**: feature test index+detail+otorisasi (`php artisan test --filter=RiwayatPermohonanTest`).

---

### M3-T6 — Edit & Batalkan (Status Pending)  ☐
- **Referensi**: PRD F5.3; UX_SPEC 4.D; perancangan-murni Fitur 5.3 (BR-07).
- **Depends on**: M3-T5.
- **Tujuan**: Mahasiswa dapat mengubah/membatalkan permohonan selama masih `pending`.
- **TODO**: Edit → buka ulang form (M3-T4) pre-fill, update record sama (status tetap pending); Batalkan → konfirmasi SweetAlert → `status=dibatalkan`.
- **Batasan/Guardrail**: hanya saat `pending`; setelah `diverifikasi` tombol hilang (BR-07); batalkan final; konfirmasi via `js-confirm`.
- **Acceptance Criteria**:
    - Given status pending, When Edit & simpan, Then record ter-update, status tetap pending.
    - Given status diverifikasi, When coba edit, Then ditolak "sudah diproses".
    - Given Batalkan dikonfirmasi, When submit, Then `status=dibatalkan`.
- **Definition of Done**: feature test edit+batal+guard status (`php artisan test --filter=EditBatalPermohonanTest`).

---

### M3-T7 — Resubmit / Ajukan Ulang (Status Ditolak)  ☐
- **Referensi**: PRD F5.4; ERD §12 (`parent_permohonan_id`); UX_SPEC 4.E.
- **Depends on**: M3-T5.
- **Tujuan**: Mahasiswa mengajukan ulang permohonan yang ditolak dengan data pre-fill.
- **TODO**: tombol Ajukan Ulang (hanya `ditolak`) → form baru pre-fill (`isian_form`, values, file dokumen sama) → buat **permohonan baru** `parent_permohonan_id`=lama, status=pending.
- **Batasan/Guardrail**: hanya dari status `ditolak`; membuat record baru (bukan mengubah lama); jejak rantai via parent.
- **Acceptance Criteria**:
    - Given permohonan ditolak, When Ajukan Ulang & submit, Then permohonan baru (pending) dengan `parent_permohonan_id` terisi.
    - Given permohonan bukan ditolak, When akses resubmit, Then ditolak.
- **Definition of Done**: feature test resubmit+parent (`php artisan test --filter=ResubmitPermohonanTest`).

---

### M3-T8 — Profil Mahasiswa  ☐
- **Referensi**: PRD §4.7; ERD §3; UX_SPEC 4.G (read-only + Ubah Password).
- **Depends on**: M1-T3 (Ubah Password).
- **Tujuan**: Menampilkan profil mahasiswa (read-only) + section ubah password.
- **TODO**: tampil `mahasiswa` (nama/nim/prodi) + `users.email` read-only; section Ubah Password (reuse Fortify M1-T3).
- **Batasan/Guardrail**: data profil read-only (snapshot SIAKAD); tak ada edit profil Phase 1.
- **Acceptance Criteria**:
    - Given mahasiswa login, When buka profil, Then data read-only tampil + form ubah password tersedia.
- **Definition of Done**: feature test render profil (`php artisan test --filter=ProfilMahasiswaTest`).

---

## MILESTONE 4 — Review & Approval Permohonan (F6) — **DETAIL**
> Sisi admin (P2) memproses permohonan M3. Peta ringkas ada di §2.

---

### M4-T1 — Daftar Permohonan Admin (Index + Filter)  ☐
- **Referensi**: PRD F6; ERD §12; UX_SPEC 5.A.1; ARCHITECTURE §17 (filter).
- **Depends on**: M3-T1.
- **Tujuan**: Admin melihat semua permohonan dengan filter kuat untuk memproses antrean.
- **TODO**: index DataTables server-side; `x-ui.filter` (status, jenis surat, **rentang tanggal**, search nama/NIM); badge status; baris → detail.
- **Batasan/Guardrail**: filter §17 wajib; akses `admin_surat`/`super_admin` (middleware/Policy); tidak dibatasi milik siapa (admin lihat semua, beda dari mahasiswa).
- **Acceptance Criteria**:
    - Given permohonan beragam, When filter `status=pending`, Then hanya pending tampil.
    - Given rentang tanggal, When set dari–sampai, Then hasil sesuai rentang.
    - Given mahasiswa, When akses route ini, Then 403.
- **Definition of Done**: feature test index+filter+otorisasi (`php artisan test --filter=PermohonanAdminIndexTest`).

---

### M4-T2 — Detail Permohonan (Auto-Verifikasi + 4 Lapisan)  ☐
- **Referensi**: PRD F6; ERD §12, §14, §15; UX_SPEC 5.A.2; perancangan-murni Fitur 6 (BR-07).
- **Depends on**: M4-T1.
- **Tujuan**: Menampilkan detail permohonan (4 lapisan + unduh syarat) dan menandai `diverifikasi` otomatis saat dibuka.
- **TODO**: tampil 4 lapisan (profil, `isian_form`, values, file syarat dengan tombol unduh); **auto-trigger** `pending`→`diverifikasi` (idempotent) via Action; label "Pengajuan ulang dari #X" bila `parent`; tombol Setujui/Tolak.
- **Batasan/Guardrail**: transisi `diverifikasi` **idempotent** (buka ulang tak mengubah); mengunci Edit mahasiswa (BR-07); unduh file via controller ber-Policy (private).
- **Acceptance Criteria**:
    - Given permohonan `pending`, When admin buka detail, Then status → `diverifikasi` (sekali); buka lagi → tetap `diverifikasi`.
    - Given file syarat, When klik unduh, Then file terunduh (akses terotorisasi).
    - Given status sudah `diverifikasi`, When mahasiswa coba Edit, Then dicegah (efek BR-07 di M3-T6).
- **Definition of Done**: feature test auto-verifikasi idempotent + unduh (`php artisan test --filter=PermohonanDetailTest`).

---

### M4-T3 — Setujui (Proxy Approval)  ☐
- **Referensi**: PRD F6; ERD §12 (`approved_by`/`pejabat_id`/`catatan_approval`/`approved_at`), §4; UX_SPEC 5.A.3; perancangan-murni §4.3 (proxy approval); FEATURE_MAP O1.
- **Depends on**: M4-T2, M1-T6 (master pejabat).
- **Tujuan**: Admin menyetujui permohonan atas nama pejabat (proxy) dan membuka jalan ke Generate.
- **TODO**: modal Setujui (pilih pejabat Select2 + `catatan_approval` wajib); `ApprovePermohonanAction` set `status=disetujui`, `approved_by`(admin), `pejabat_id`, `approved_at`; log ActivityLog; email (M9); munculkan tombol "Generate Surat".
- **Batasan/Guardrail**: `pejabat_id` & catatan **wajib**; `approved_by` (admin yang klik) **≠** `pejabat_id` (pemberi persetujuan) — bukti proxy; **status disetujui BELUM membuat file** (BR-12); un-approve **tidak didukung** (⚠️#8 resolved); transisi via Action (O1).
- **Acceptance Criteria**:
    - Given detail `diverifikasi`, When Setujui dengan pejabat+catatan, Then `status=disetujui` + `approved_by`≠`pejabat_id` tercatat + tombol Generate muncul.
    - Given pejabat/catatan kosong, When submit, Then `✖` validasi.
    - Given disetujui, When cek arsip, Then belum ada `surat_tercetak` (BR-12).
- **Definition of Done**: feature test approve+proxy+no-file (`php artisan test --filter=ApprovePermohonanTest`).

---

### M4-T4 — Tolak Permohonan  ☐
- **Referensi**: PRD F6; ERD §12 (`catatan_penolakan`); UX_SPEC 5.A.4; perancangan-murni Fitur 6.
- **Depends on**: M4-T2.
- **Tujuan**: Admin menolak permohonan dengan alasan yang ditampilkan ke mahasiswa.
- **TODO**: modal Tolak (`catatan_penolakan` wajib); `RejectPermohonanAction` set `status=ditolak`; log ActivityLog; email alasan (M9); mahasiswa dapat Ajukan Ulang (M3-T7).
- **Batasan/Guardrail**: alasan **wajib** & **tampil ke mahasiswa**; transisi via Action; tidak ada un-reject khusus (mahasiswa resubmit sebagai record baru).
- **Acceptance Criteria**:
    - Given detail, When Tolak dengan alasan, Then `status=ditolak` + `catatan_penolakan` tersimpan + tampil ke mahasiswa.
    - Given alasan kosong, When submit, Then `✖` validasi.
- **Definition of Done**: feature test reject+alasan tampil (`php artisan test --filter=RejectPermohonanTest`).

---

## MILESTONE 5 — Generate & Cetak Surat (F7) — **DETAIL**
> Butuh permohonan disetujui (M4) atau template aktif (M2). Peta ringkas ada di §2.

---

### M5-T1 — Migration & Model Arsip Cetak  ☐
- **Referensi**: ERD §16 (`surat_tercetak`), §17 (`surat_penandatangan`); ARCHITECTURE §0.
- **Depends on**: M2-T1.
- **Tujuan**: Skema + model arsip surat tercetak (immutable) & penandatangan snapshot.
- **TODO**:
    - Migration: `surat_tercetak` (`permohonan_id` nullable, `unit_id` nullable, `data_placeholder` JSON, `file_pdf_path`/`file_docx_path`/`qr_hash`, `metode_pengambilan`/`status` string, self FK `replaced_by_id`, **UNIQUE (`nomor_surat`,`unit_id`)**), `surat_penandatangan` (snapshot nama/jabatan/nip + `file_ttd_path`).
    - Model + relasi (belongsTo permohonan/template/unit, hasMany penandatangan, self `replacedBy`).
    - Enum `SuratStatus` (`aktif`/`digantikan`/`dibatalkan`), `MetodePengambilan` (`download`/`ambil_di_kampus`).
- **Batasan/Guardrail**: arsip **immutable** (tanpa softDeletes, tanpa update konten); UNIQUE nomor+unit sebagai hard guard; `file_pdf_path`/`file_docx_path`/`file_ttd_path` = **kolom path beku** (snapshot, bukan relasi media — K4 pengecualian).
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then tabel arsip + UNIQUE terbentuk.
    - Given 2 surat nomor sama unit sama, When insert kedua, Then ditolak (unique).
- **Definition of Done**: `migrate:fresh` OK; test unique+relasi (`php artisan test --filter=SuratTercetakSchemaTest`).

---

### M5-T2 — TemplateSubstitutionService (final) + PdfService  ☐
- **Referensi**: ARCHITECTURE §8 (service reusable), §2.1 (PDF LibreOffice opsional); ERD §5.1 (`libreoffice_path`), §17; perancangan-murni §4.4/§4.6/§4.8; PRD F7.
- **Depends on**: M5-T1, M2-T8 (versi awal service).
- **Tujuan**: Service SSOT yang mengisi placeholder ke `.docx` (termasuk gambar TTD & QR) + konversi PDF opsional.
- **TODO**:
    - Lengkapi `TemplateSubstitutionService`: substitusi teks + **embed gambar TTD** per slot + sisip **QR** (simple-qrcode, hash HMAC).
    - `PdfService`: `DocxToPdfConverter` via `Process` (gated `Settings::get('libreoffice_path')`, `HOME`+`UserInstallation` unik, queue) → return path PDF atau `null` (fallback DOCX).
    - `qr_hash` = HMAC (id+nomor, `app.key`); URL verifikasi.
- **Batasan/Guardrail**: **DOCX dijamin, PDF opsional** (graceful — pola OpenSID); LibreOffice absen → tetap kirim DOCX; TTD file dari collection private; substitusi = SSOT (dipakai preview/A/B).
- **Acceptance Criteria**:
    - Given data lengkap + LibreOffice tersedia, When generate, Then hasil DOCX + PDF + QR ter-embed.
    - Given `libreoffice_path` kosong, When generate, Then DOCX saja (tanpa error), `file_pdf_path`=null.
    - Given slot TTD ada file, When generate, Then gambar TTD ter-embed; slot basah → tanpa gambar.
- **Definition of Done**: feature test substitusi+fallback PDF+TTD (`php artisan test --filter=SubstitutionServiceTest`).

---

### M5-T3 — NomorSuratService (Saran + Cek Duplikat)  ☐
- **Referensi**: PRD 4.5/F7; ERD §16 (UNIQUE); UX_SPEC 5.B.3; perancangan-murni §4.5 (Mode A).
- **Depends on**: M5-T1.
- **Tujuan**: Menyarankan nomor surat berikutnya dari arsip & memvalidasi duplikat realtime.
- **TODO**: `NomorSuratService::suggest(template, tahun)` (ambil nomor terakhir + regex increment); endpoint AJAX `cek-nomor` (cek `surat_tercetak` per unit+tahun).
- **Batasan/Guardrail**: **tanpa tabel counter** (derive dari arsip); reset per tahun via query; UNIQUE (nomor,unit) sebagai hard guard di simpan; Mode A (string penuh).
- **Acceptance Criteria**:
    - Given nomor terakhir "004/…", When suggest, Then pre-fill "005/…".
    - Given nomor sudah dipakai (unit+tahun), When AJAX cek, Then `⚠ duplikat`.
    - Given tak ada nomor sebelumnya, When suggest, Then kosong + hint.
- **Definition of Done**: unit+feature test suggest+cek (`php artisan test --filter=NomorSuratTest`).

---

### M5-T4 — Form Generate (Shared)  ☐
- **Referensi**: PRD F7; ERD §16, §17, §9; UX_SPEC 5.B.3; perancangan-murni §4.6 (TTD), BR-10.
- **Depends on**: M5-T2, M5-T3, M1-T6 (pejabat).
- **Tujuan**: Form generate yang dipakai kedua sub-flow (data surat, isian, penandatangan, metode pengambilan, preview).
- **TODO**: section Data Surat (unit penerbit `surat_tercetak.unit_id`, tanggal prefill, nomor via M5-T3); Isian (`filled_by=admin` + bebas); Penandatangan per slot (dropdown pejabat → snapshot + indikator TTD); Metode Pengambilan (radio + **saran otomatis BR-10**); Preview Draft (header DRAFT, ephemeral).
- **Batasan/Guardrail**: saran metode = `ambil_di_kampus` bila ada slot TTD basah, else `download` (BR-10, bisa override); preview tidak menyimpan; nomor divalidasi sebelum final.
- **Acceptance Criteria**:
    - Given slot TTD basah, When buka form, Then Metode default disarankan `ambil_di_kampus`.
    - Given pilih pejabat pada slot, When render, Then preview nama/jabatan + indikator TTD.
    - Given Preview Draft, When klik, Then file DRAFT terunduh tanpa entri arsip.
- **Definition of Done**: feature test render+saran+preview (`php artisan test --filter=FormGenerateTest`).

---

### M5-T5 — Sub-flow A: Generate dari Permohonan  ☐
- **Referensi**: PRD F7; ERD §16, §17; UX_SPEC 5.B.1; perancangan-murni Fitur 7; BR-12.
- **Depends on**: M5-T4, M4-T3 (permohonan disetujui).
- **Tujuan**: Generate final surat dari permohonan disetujui → arsip + selesaikan permohonan.
- **TODO**: tombol Generate pada permohonan `disetujui`; form prefill dari `isian_form` + profil; `GenerateSuratAction`: substitusi (M5-T2) → simpan `surat_tercetak` (`permohonan_id` terisi, `data_placeholder` snapshot, QR, metode) + `surat_penandatangan` per slot → `permohonan.status=selesai` → email per metode (M9).
- **Batasan/Guardrail**: hanya dari `disetujui`; `data_placeholder` = snapshot final (imun perubahan master); mahasiswa lihat download hanya bila `metode=download` (BR-12); satu transaksi.
- **Acceptance Criteria**:
    - Given permohonan disetujui, When Generate Final, Then `surat_tercetak`(permohonan_id) + penandatangan tersimpan + `permohonan.status=selesai` + email.
    - Given `metode=ambil_di_kampus`, When mahasiswa buka riwayat, Then badge "ambil di kampus" (tanpa tombol download).
    - Given `metode=download`, When mahasiswa buka, Then tombol download tersedia.
- **Definition of Done**: feature test generate+arsip+status+metode (`php artisan test --filter=GenerateDariPermohonanTest`).

---

### M5-T6 — Sub-flow B: Generate Langsung  ☐
- **Referensi**: PRD F7; ERD §16, §3; UX_SPEC 5.B.2; perancangan-murni §4.9 (tipe_pemohon).
- **Depends on**: M5-T4.
- **Tujuan**: Admin generate surat langsung tanpa permohonan (internal/umum atau mahasiswa manual).
- **TODO**: menu Generate Langsung → pilih template aktif; bila `tipe_pemohon=mahasiswa` → langkah **Cari Mahasiswa** (Select2 AJAX `cari-mahasiswa`) → autofill profil; bila `umum` → langsung form; `GenerateSuratAction` dengan `permohonan_id=NULL`.
- **Batasan/Guardrail**: `permohonan_id=NULL`; placeholder mahasiswa diisi manual (template non-mandiri tak punya sumber otomatis — BR-09); langkah cari mahasiswa hanya bila `tipe_pemohon=mahasiswa`.
- **Acceptance Criteria**:
    - Given template `tipe_pemohon=mahasiswa`, When pilih, Then muncul Cari Mahasiswa → autofill profil.
    - Given template `umum`, When pilih, Then langsung ke form (tanpa cari mahasiswa).
    - Given Generate Final, When simpan, Then `surat_tercetak` dengan `permohonan_id=NULL`.
- **Definition of Done**: feature test kedua tipe + null permohonan (`php artisan test --filter=GenerateLangsungTest`).

---

## MILESTONE 6 — Arsip Surat & Verifikasi Publik (F8) — **DETAIL**
> Output M5. Arsip **immutable** + verifikasi QR. Peta ringkas ada di §2.

---

### M6-T1 — Arsip: Daftar + Filter + Download  ☐
- **Referensi**: PRD F8; ERD §16; UX_SPEC 6.A.1 (+ aksi download); ARCHITECTURE §17.
- **Depends on**: M5-T1.
- **Tujuan**: Daftar arsip surat tercetak yang dapat dicari/difilter, dengan unduh langsung dari list.
- **TODO**: index DataTables server-side; `x-ui.filter` (jenis, **rentang tanggal**, search nomor/nama/NIM); badge status (aktif/digantikan/dibatalkan); penerima kosong untuk surat `umum`; **aksi `[⬇]`** unduh `file_pdf_path`/`file_docx_path` (dropdown bila keduanya ada).
- **Batasan/Guardrail**: filter §17; download via controller ber-otorisasi; penerima = nama+NIM dari `data_placeholder`/relasi (kosong bila umum).
- **Acceptance Criteria**:
    - Given arsip, When filter jenis+tanggal, Then hasil sesuai.
    - Given baris arsip, When klik `[⬇]`, Then file (PDF bila ada, else DOCX) terunduh.
    - Given surat umum, When tampil, Then kolom penerima kosong.
- **Definition of Done**: feature test index+filter+download (`php artisan test --filter=ArsipIndexTest`).

---

### M6-T2 — Arsip: Detail (Immutable + Snapshot)  ☐
- **Referensi**: PRD F8; ERD §16, §17; UX_SPEC 6.A.2; perancangan-murni §4.8 (snapshot), BR-11.
- **Depends on**: M6-T1.
- **Tujuan**: Menampilkan detail arsip lengkap (read-only) beserta snapshot data & penandatangan.
- **TODO**: tampil nomor/jenis/penerima/digenerate; file PDF/DOCX; penandatangan snapshot (nama/jabatan/nip + status TTD); `data_placeholder` (read-only); QR + URL verifikasi; banner bila `digantikan`/`dibatalkan` (link ke pengganti).
- **Batasan/Guardrail**: **read-only penuh** — tidak ada tombol Edit (immutable, BR-11); menampilkan snapshot apa adanya (imun perubahan master).
- **Acceptance Criteria**:
    - Given arsip, When buka detail, Then tampil snapshot + penandatangan + link file, tanpa opsi edit.
    - Given status `digantikan`, When buka, Then banner + link ke `replaced_by_id`.
- **Definition of Done**: feature test detail+immutable (`php artisan test --filter=ArsipDetailTest`).

---

### M6-T3 — Cetak Ulang (Rantai Digantikan)  ☐
- **Referensi**: PRD F8; ERD §16 (`status`/`replaced_by_id`/`replaced_reason`), §24 (D-006); UX_SPEC 6.A.3; BR-11; `docs/decisions/DECISIONS.md` (D-006).
- **Depends on**: M6-T2, M5-T4 (form generate).
- **Tujuan**: Membuat versi baru surat (anti-fraud) sambil menandai versi lama "digantikan".
- **TODO**: modal alasan (`replaced_reason` wajib) → buka Form Generate (M5-T4) prefilled dari `data_placeholder` lama → final: `SupersedeSuratAction` buat entri baru (`aktif`) + set lama `status=digantikan`+`replaced_by_id`+`replaced_reason` (satu transaksi).
- **Batasan/Guardrail**: entri lama **tidak** di-overwrite (immutable) — hanya status/replaced_* diubah lewat Action owner (FEATURE_MAP O2); **nomor baru wajib, final** (D-006 — tidak ada pengecualian nomor sama); alasan wajib.
- **Acceptance Criteria**:
    - Given arsip aktif, When cetak ulang + alasan + generate, Then entri baru `aktif` + lama `digantikan` menunjuk baru.
    - Given tanpa alasan, When submit, Then `✖`.
    - Given nomor sama unit sama, When final, Then ditolak (UNIQUE) → wajib nomor baru.
- **Definition of Done**: feature test cetak ulang+rantai+unique (`php artisan test --filter=CetakUlangTest`).

---

### M6-T4 — Export Arsip (Excel)  ☐
- **Referensi**: PRD F8; ERD §16; UX_SPEC 6.A.4.
- **Depends on**: M6-T1.
- **Tujuan**: Mengekspor arsip (sesuai filter) ke Excel untuk laporan.
- **TODO**: tombol Export → generate Excel dari query terfilter (kolom `surat_tercetak`); queue bila besar.
- **Batasan/Guardrail**: mengikuti filter aktif; kolom sesuai ERD (tanpa data sensitif berlebih).
- **Acceptance Criteria**:
    - Given filter aktif, When Export, Then file Excel berisi baris sesuai filter.
- **Definition of Done**: feature test export (`php artisan test --filter=ArsipExportTest`).

---

### M6-T5 — Verifikasi Publik (QR, Guest)  ⏸️ **DIPINDAH KE PHASE 2**
- **Keputusan**: D-002 (`docs/decisions/DECISIONS.md`) — halaman verifikasi publik **bukan** bagian eksekusi Phase 1. **Jangan dikerjakan** sampai proyek masuk Phase 2.
- **Referensi (untuk nanti)**: PRD §5.2 (Fitur Tambahan Phase 2); ERD §16 (`qr_hash`/`status`), §5.1 (identitas publik); UX_SPEC 6.B (wireframe tersimpan sebagai referensi desain); ARCHITECTURE (QR & Verifikasi).
- **Yang tetap dikerjakan di Phase 1**: `qr_hash` + QR code tetap dibuat & disisipkan ke surat saat generate (M5-T2) — supaya siap dipakai begitu halaman ini dibangun nanti.
- **Ringkasan tugas (Phase 2, bukan sekarang)**: route publik `verify/{qr_hash}` (tanpa login) menampilkan status surat (valid/digantikan/dibatalkan/tidak ditemukan) dengan privasi nama depan+inisial. Detail lengkap di UX_SPEC 6.B.

---

## MILESTONE 7 — Surat Masuk, Disposisi & Buku Agenda Masuk (F9) — **DETAIL**
> Independen dari alur permohonan. Peta ringkas ada di §2.

---

### M7-T1 — Migration & Model Surat Masuk  ☐
- **Referensi**: ERD §18 (`surat_masuk`), §19 (`disposisi_surat_masuk`); ARCHITECTURE §0, §9-K4 (scan/lampiran via media).
- **Depends on**: M1-T1.
- **Tujuan**: Skema + model surat masuk & disposisi (lampiran via Media Library).
- **TODO**:
    - Migration: `surat_masuk` (`nomor_agenda` smallint + `tahun_agenda`, `berkas_scan` via media, softDeletes), `disposisi_surat_masuk` (CASCADE dari surat_masuk, `sifat`/`status` string, `pejabat_id` nullable).
    - Model + relasi: `SuratMasuk` (`HasMedia` collection `scan` singleFile + `lampiran` multi, hasMany disposisi), `Disposisi` (belongsTo suratMasuk/pejabat).
    - Enum `SifatDisposisi`, `StatusDisposisi`.
- **Batasan/Guardrail**: `nomor_agenda` auto per tahun (bukan input); scan & lampiran via Media Library (tabel `lampiran_surat_masuk` **dihapus** — collection `lampiran`, ERD §20).
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then tabel terbentuk; hapus surat masuk → disposisi ikut terhapus (CASCADE).
    - Given surat masuk, When attach scan + 2 lampiran, Then media collection `scan`(1)+`lampiran`(2).
- **Definition of Done**: `migrate:fresh` OK; test relasi+media (`php artisan test --filter=SuratMasukSchemaTest`).

---

### M7-T2 — Surat Masuk CRUD  ☐
- **Referensi**: PRD F9.1; ERD §18; UX_SPEC 7.A.2 (+ aksi download); ARCHITECTURE §9, §17; `docs/decisions/DECISIONS.md` (D-003).
- **Depends on**: M7-T1, M0-T8 (MediaService).
- **Tujuan**: Mencatat surat fisik masuk (metadata + scan wajib + lampiran) dengan nomor agenda otomatis.
- **TODO**: FormRequest (tgl terima/no surat/pengirim/perihal wajib; kode_klasifikasi/keterangan opsional); generate `nomor_agenda` per tahun; upload scan (wajib) + lampiran multi (FilePond → media); index + **aksi `[⬇]`** unduh scan; soft delete.
- **Batasan/Guardrail**: `nomor_agenda` sistem (read-only setelah simpan); scan **wajib**; `kode_klasifikasi` **teks bebas — final untuk Phase 1** (D-003, tidak ada master `klasifikasi_surat`).
- **Acceptance Criteria**:
    - Given form valid + scan, When simpan, Then `surat_masuk` + `nomor_agenda` otomatis + media `scan`.
    - Given tanpa scan, When simpan, Then `✖`.
    - Given baris, When klik `[⬇]`, Then scan terunduh.
- **Definition of Done**: feature test CRUD+agenda+scan (`php artisan test --filter=SuratMasukTest`).

---

### M7-T3 — Disposisi CRUD + Update Status  ☐
- **Referensi**: PRD F9.2; ERD §19; UX_SPEC 7.A.3; perancangan-murni Fitur 9.2.
- **Depends on**: M7-T2, M1-T6 (pejabat, untuk Phase 2 opsional).
- **Tujuan**: Menambah disposisi (satu surat → banyak) & memperbarui status tindak lanjut.
- **TODO**: CRUD disposisi (tujuan teks bebas, isi_instruksi, sifat, batas_waktu, status); saat status→`sudah_ditindaklanjuti` set `ditindaklanjuti_oleh`(admin)+`ditindaklanjuti_at` otomatis + catatan.
- **Batasan/Guardrail**: tujuan **teks bebas** Phase 1 (bukan FK akun pejabat); one-to-many; batas waktu terlewat → highlight dashboard (M1-T12).
- **Acceptance Criteria**:
    - Given surat masuk, When tambah 2 disposisi, Then keduanya tersimpan (one-to-many).
    - Given status→sudah, When simpan, Then `ditindaklanjuti_oleh/at` terisi otomatis.
- **Definition of Done**: feature test disposisi+status (`php artisan test --filter=DisposisiTest`).

---

### M7-T4 — Cetak Lembar Disposisi (PDF)  ☐
- **Referensi**: PRD F9.2; ERD §18, §19; UX_SPEC 7.A.4; ARCHITECTURE §2.1 (mPDF HTML→PDF).
- **Depends on**: M7-T3.
- **Tujuan**: Menghasilkan PDF lembar disposisi untuk diserahkan fisik ke pejabat.
- **TODO**: view Blade lembar disposisi (data `surat_masuk`+`disposisi`) → render PDF via mPDF; buka tab baru/unduh.
- **Batasan/Guardrail**: HTML→PDF via **mPDF** (bukan LibreOffice); read-only.
- **Acceptance Criteria**:
    - Given surat masuk berdisposisi, When Cetak Lembar Disposisi, Then PDF berisi data disposisi ter-render.
- **Definition of Done**: feature test render PDF (`php artisan test --filter=LembarDisposisiTest`).

---

### M7-T5 — Buku Agenda Masuk (View + Filter + Export)  ☐
- **Referensi**: PRD F9.3; ERD §18; UX_SPEC 7.A.1; ARCHITECTURE §17.
- **Depends on**: M7-T2.
- **Tujuan**: Menampilkan buku agenda surat masuk (view tabel) dengan filter & export.
- **TODO**: index/agenda DataTables (kolom agenda: No.Agenda/Tgl Terima/No.Surat/Pengirim/Perihal/Disposisi ke/Status); `x-ui.filter` (tahun/rentang tanggal/pengirim/status disposisi); export Excel/PDF.
- **Batasan/Guardrail**: **view dari `surat_masuk`** (bukan tabel terpisah, F9.3); filter §17.
- **Acceptance Criteria**:
    - Given surat masuk, When filter tahun+status disposisi, Then hasil sesuai.
    - Given Export, When klik, Then file (Excel/PDF) sesuai filter.
- **Definition of Done**: feature test agenda+filter+export (`php artisan test --filter=AgendaMasukTest`).

---

## MILESTONE 8 — Surat Keluar / Buku Agenda Keluar (F10) — **DETAIL**
> 100% manual entry (Kamar 3). Peta ringkas ada di §2.

---

### M8-T1 — Migration & Model Surat Keluar  ☐
- **Referensi**: ERD §21 (`surat_keluar`); ARCHITECTURE §0, §9-K4.
- **Depends on**: M1-T1.
- **Tujuan**: Skema + model buku agenda surat keluar (manual, tanpa FK ke arsip cetak).
- **TODO**: migration `surat_keluar` (`nomor_agenda`+`tahun_agenda`, `berkas_scan` via media, softDeletes); model `SuratKeluar` (`HasMedia` collection `scan`).
- **Batasan/Guardrail**: **tidak ada FK ke `surat_tercetak`** (Kamar 3, disengaja — arsitektur 3 Kamar); `nomor_agenda` auto per tahun; scan opsional via media.
- **Acceptance Criteria**:
    - Given migrasi, When `migrate:fresh`, Then tabel `surat_keluar` terbentuk tanpa relasi ke arsip cetak.
- **Definition of Done**: `migrate:fresh` OK; test skema (`php artisan test --filter=SuratKeluarSchemaTest`).

---

### M8-T2 — Surat Keluar CRUD  ☐
- **Referensi**: PRD F10; ERD §21; UX_SPEC 7.B.2 (+ aksi download); ARCHITECTURE §9.
- **Depends on**: M8-T1.
- **Tujuan**: Mencatat surat keluar secara manual dengan nomor agenda otomatis & cek duplikat nomor.
- **TODO**: FormRequest (no surat/tgl surat/tujuan/perihal wajib; kode_klasifikasi/keterangan/scan opsional); `nomor_agenda` per tahun; AJAX **cek duplikat** `nomor_surat`; upload scan opsional (media); index + **aksi `[⬇]`** unduh scan (sembunyi bila tak ada); soft delete.
- **Batasan/Guardrail**: `nomor_agenda` sistem; AJAX cek duplikat sebelum simpan; tidak ada baris otomatis dari Generate (manual only).
- **Acceptance Criteria**:
    - Given form valid, When simpan, Then `surat_keluar` + `nomor_agenda` otomatis.
    - Given nomor duplikat, When AJAX cek, Then `⚠ duplikat`.
    - Given ada scan, When klik `[⬇]`, Then terunduh.
- **Definition of Done**: feature test CRUD+agenda+cek nomor (`php artisan test --filter=SuratKeluarTest`).

---

### M8-T3 — Buku Agenda Keluar (View + Filter + Export)  ☐
- **Referensi**: PRD F10; ERD §21; UX_SPEC 7.B.1; ARCHITECTURE §17.
- **Depends on**: M8-T2.
- **Tujuan**: Menampilkan buku agenda keluar dengan filter & export.
- **TODO**: index DataTables (No.Agenda/No.Surat/Tgl/Tujuan/Perihal/Ket); `x-ui.filter` (tahun/rentang tanggal/tujuan/perihal); export Excel/PDF.
- **Batasan/Guardrail**: filter §17; view dari `surat_keluar`.
- **Acceptance Criteria**:
    - Given surat keluar, When filter tahun+tujuan, Then hasil sesuai.
    - Given Export, When klik, Then file sesuai filter.
- **Definition of Done**: feature test agenda+filter+export (`php artisan test --filter=AgendaKeluarTest`).

---

## MILESTONE 9 — Notifikasi Email (F11) — **DETAIL**
> Cross-cutting; disisipkan setelah M4/M5 ada. Peta ringkas ada di §2.

---

### M9-T1 — Setup Mail dari Settings + Queue  ☐
- **Referensi**: PRD F11; ERD §5.1 (SMTP settings); ARCHITECTURE §2 (queue), §2.2; UX_SPEC Appendix B.
- **Depends on**: M1-T4 (settings SMTP).
- **Tujuan**: Infrastruktur email berbasis konfigurasi `settings` + antrean dengan retry.
- **TODO**: ServiceProvider override `config('mail.*')` dari `settings` (host/port/from/user/pass encrypted); queue (database driver); base Mailable HTML + layout email.
- **Batasan/Guardrail**: kredensial dari `settings` (bukan hardcode); queue-based (retry bila SMTP gagal); template HTML (bukan plain).
- **Acceptance Criteria**:
    - Given SMTP dikonfigurasi di settings, When kirim email, Then memakai konfigurasi tersebut (mail fake assert).
    - Given SMTP gagal, When job jalan, Then di-retry (bukan hilang).
- **Definition of Done**: feature test config+queue (`php artisan test --filter=MailSetupTest`).

---

### M9-T2 — Email Events Permohonan  ☐
- **Referensi**: PRD F11; UX_SPEC Appendix B; perancangan-murni Fitur 11; ERD §16 (`metode_pengambilan`).
- **Depends on**: M9-T1, M4-T3 (approve), M4-T4 (reject), M5-T5 (surat siap).
- **Tujuan**: Mengirim notifikasi email pada peristiwa kunci permohonan.
- **TODO**: Mailable + event pada: **diajukan** (konfirmasi mahasiswa), **disetujui**, **ditolak** (+alasan), **surat siap** (teks dibedakan `metode_pengambilan`: download vs ambil di kampus).
- **Batasan/Guardrail**: dispatch via queue; teks "surat siap" sesuai metode; tidak memblok alur bila email gagal (queue retry).
- **Acceptance Criteria**:
    - Given permohonan diajukan, When submit, Then email konfirmasi ter-queue ke mahasiswa.
    - Given ditolak, When reject, Then email berisi alasan.
    - Given surat siap `metode=ambil_di_kampus`, When generate final, Then email "ambil di kampus" (bukan "download").
- **Definition of Done**: feature test 4 event + teks metode (`php artisan test --filter=EmailNotifikasiTest`).

---

## MILESTONE 10 — Finalisasi & Hardening — **DETAIL**
> Konsistensi lintas fitur + kualitas. Peta ringkas ada di §2.

---

### M10-T1 — Review Standar Filter §17 (Semua Index)  ☐
- **Referensi**: ARCHITECTURE §17; FEATURE_MAP (semua `*.index`).
- **Depends on**: semua milestone dengan halaman index (M1-M8).
- **Tujuan**: Memastikan seluruh halaman list konsisten memakai pola filter reusable (Query Filter + `x-ui.filter` + Yajra).
- **TODO**: audit tiap `*.index` terhadap tabel "filter minimum per halaman" (ARCHITECTURE §17); perbaiki yang belum konsisten.
- **Batasan/Guardrail**: pola reusable tunggal (tanpa filter ad-hoc per fitur).
- **Acceptance Criteria**:
    - Given semua index, When diaudit, Then setiap halaman punya filter minimum sesuai §17 + pencarian + rentang tanggal (bila relevan).
- **Definition of Done**: checklist §17 terpenuhi semua index; test filter lintas modul hijau.

---

### M10-T2 — Audit Trail Menyeluruh (ActivityLog)  ☐
- **Referensi**: PRD §7.1; ARCHITECTURE §2 (ActivityLog); UX_SPEC (audit admin).
- **Depends on**: M4, M5, M6.
- **Tujuan**: Memastikan aksi penting tercatat untuk audit Super Admin.
- **TODO**: verifikasi log pada login/logout, approve/reject, generate, cetak ulang, penggunaan file TTD; halaman log aktivitas (Super Admin).
- **Batasan/Guardrail**: log wajib pada aksi berdampak (tanpa data sensitif berlebih); read-only.
- **Acceptance Criteria**:
    - Given aksi approve/generate/cetak ulang, When dijalankan, Then tercatat di ActivityLog (aktor, subjek, waktu).
- **Definition of Done**: feature test log tercatat (`php artisan test --filter=ActivityLogTest`).

---

### M10-T3 — Pass Kualitas (Pint + Larastan + Coverage)  ☐
- **Referensi**: ARCHITECTURE §14 (testing), §15 (kualitas), §18 (vertical slice DoD).
- **Depends on**: semua.
- **Tujuan**: Memastikan seluruh basis kode lulus standar kualitas & tiap fitur punya feature test.
- **TODO**: jalankan Pint (format), Larastan (min level 5), pastikan setiap fitur F1-F13 punya feature test hijau.
- **Batasan/Guardrail**: tidak ada anti-pattern (§15): logika di controller/Blade, string status literal, HTML/upload duplikat.
- **Acceptance Criteria**:
    - Given basis kode, When `pint --test` & `larastan`, Then tanpa pelanggaran.
    - Given suite test, When `php artisan test`, Then semua fitur hijau.
- **Definition of Done**: `./vendor/bin/pint --test` bersih; `./vendor/bin/phpstan analyse` lolos; `php artisan test` hijau menyeluruh.

---

### M10-T4 — Security Review  ☐
- **Referensi**: ARCHITECTURE §9 (file private), §8 (otorisasi); PRD §7.4; UX_SPEC 6.B (verifikasi publik).
- **Depends on**: semua.
- **Tujuan**: Meninjau keamanan sebelum rilis (upload, akses file, otorisasi, verifikasi publik).
- **TODO**: cek file sensitif disk private + akses via Policy; validasi tipe/ukuran upload; otorisasi Policy tiap resource (mahasiswa vs admin vs super admin); `qr_hash` tak bisa ditebak; tidak ada kebocoran data di verifikasi publik.
- **Batasan/Guardrail**: TTD & syarat tidak boleh akses via URL publik; permohonan hanya milik sendiri; menu Super Admin terlindungi.
- **Acceptance Criteria**:
    - Given file TTD/syarat, When akses tanpa otorisasi, Then ditolak (bukan URL publik).
    - Given mahasiswa, When akses data mahasiswa lain / menu admin, Then 403.
    - Given verifikasi publik, When dibuka, Then tidak membocorkan data sensitif (hanya nama tersamar).
- **Definition of Done**: feature test otorisasi lintas role + akses file (`php artisan test --filter=SecurityTest`); `/security-review` bersih.

---

### M10-T5 — Audit Lokalisasi Teks Statis FE (`lang/id`)  ☐
- **Referensi**: ARCHITECTURE §11.5.
- **Depends on**: M0-T10, semua milestone dengan View (M1-M9).
- **Tujuan**: Memastikan tidak ada teks statis FE yang hardcode di luar `lang/id/*.php` sebelum rilis.
- **TODO**: audit tiap Blade/JS (cari literal Bahasa Indonesia di label/tombol/heading/pesan/placeholder); pindahkan yang masih hardcode ke `lang/id/{modul}.php` yang sesuai; pastikan `x-ui.datatable`/`x-ui.filter` (§11.1, §17) mengambil header kolom & placeholder dari lang, bukan config Yajra.
- **Batasan/Guardrail**: tidak menambah SSOT baru selain `lang/id/*.php`; hanya pindah lokasi teks, tidak mengubah makna/istilah tanpa persetujuan.
- **Acceptance Criteria**:
    - Given seluruh Blade/JS FE, When diaudit, Then tidak ada string UI Bahasa Indonesia hardcode di luar `lang/id/*.php`.
    - Given halaman yang dirapikan, When dirender, Then tampilan visual identik dengan sebelum audit (hanya sumber teks berubah).
- **Definition of Done**: audit checklist bersih; spot-check render 3-5 halaman representatif tiap modul tidak berubah secara visual.

---

## §1. Peringatan Konflik / Item Terbuka (⚠️) yang Mengikat Task

Semua resolusi keputusan dicatat di **`docs/decisions/DECISIONS.md`** (SSOT — 6 keputusan, D-001 s/d D-006). **Tidak ada lagi item terbuka yang mengikat task** — seluruh tanda ⚠️ di M1-M10 sudah tuntas.

**Sudah teratasi** (lihat `DECISIONS.md` untuk detail alasan):
- Login pertama paksa ganti password + password import acak (⚠️#1)
- SLA anchor pakai `created_at`, tanpa `submitted_at` (⚠️#2)
- Kolom `fakultas` & placeholder `{{fakultas}}` **dihapus** dari sistem (D-001)
- Unit tujuan permohonan: auto-derive, tanpa pilihan mahasiswa (⚠️#5)
- Fase Verifikasi Publik → **Phase 2** (D-002) — M6-T5 dipindah keluar dari eksekusi Phase 1
- Status tracker tanpa tabel log (⚠️#6)
- Ubah password via Fortify (⚠️#7)
- Un-approve tidak didukung Phase 1 (⚠️#8)
- PDF opsional/graceful, DOCX wajib (⚠️#9)
- Master Klasifikasi Surat **tidak dibuat** Phase 1, tetap teks bebas (D-003)
- SLA hari libur: skip Sabtu/Minggu saja, tanpa kalender libur nasional (D-004)
- Hapus user: **tidak ada hard delete**, nonaktifkan saja (D-005)
- Cetak ulang: **nomor baru wajib**, tanpa pengecualian (D-006)

---

## §2. Ringkasan Cakupan

| Milestone | Fitur PRD | Jumlah Task (Phase 1) |
|---|---|---|
| M0 Bootstrap | ARCHITECTURE §0 | 9 |
| M1 Fondasi & Master | F1, F2, F4, F12, F13 | 12 |
| M2 Template | F3 | 10 |
| M3 Permohonan | F5 | 8 |
| M4 Approval | F6 | 4 |
| M5 Generate | F7 | 6 |
| M6 Arsip | F8 | 4 *(+1 dipindah Phase 2 — M6-T5, lihat D-002)* |
| M7 Surat Masuk | F9 | 5 |
| M8 Surat Keluar | F10 | 3 |
| M9 Notifikasi | F11 | 2 |
| M10 Finalisasi | cross-cutting | 4 |
| **Total** | **F1-F13** | **67 task Phase 1** *(+1 task Phase 2 dicatat sbg referensi)* |

---

*Tahap berikutnya: isi detail M0 (Bootstrap) bila diperlukan. Semua item terbuka (D-001 s/d D-006) sudah diputuskan — lihat `docs/decisions/DECISIONS.md`. M1-M10 siap dieksekusi tanpa blocker.*
