# ERD — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Dokumen** | Entity Relationship Diagram & Kamus Data |
| **Versi** | 1.0 (Draft) |
| **Tanggal** | 19 Juli 2026 |
| **Basis** | `perancangan-murni.md` §9 (skema), `PRD.md` (fitur), `perancangan-kasar.md` (konteks) |
| **Target** | Laravel 10/11 + MySQL 8.0 (siap migration & Eloquent) |

Dokumen ini menerjemahkan skema di `perancangan-murni.md` menjadi definisi tabel yang siap diimplementasikan sebagai Laravel migration. Setiap bagian (§) dapat dirujuk dokumen lain. Untuk tiap tabel disertakan: fungsi, kolom + tipe + constraint, relasi, **implikasi terhadap PRD (kenapa dirancang begini untuk mengatasi kasus apa)**, dan sampel data bila relevan.

---

## §0. Konvensi Umum (Wajib Dibaca Dulu)

Konvensi ini berlaku di semua tabel agar konsisten dengan Laravel & migration:

| Aspek | Keputusan | Alasan |
|---|---|---|
| **Primary Key** | `id` → `$table->id()` (BIGINT UNSIGNED AUTO_INCREMENT) | Default Laravel; aman untuk volume besar |
| **Foreign Key** | `$table->foreignId('x_id')->constrained()` (BIGINT UNSIGNED) | Tipe FK **wajib sama** dengan PK yang dirujuk |
| **Timestamps** | `$table->timestamps()` → `created_at`, `updated_at` | Standar Eloquent; menggantikan `created_at` manual di skema asli |
| **Soft Delete** | `$table->softDeletes()` → `deleted_at` | Untuk tabel yang butuh "hapus tapi tetap terarsip" (lihat PRD F8, F9) |
| **ENUM → string** | Semua `ENUM(...)` ditulis `string()` + `CHECK`/validasi aplikasi; nilai valid didokumentasikan | Sesuai keputusan `perancangan-murni.md` §9 — tambah nilai baru tanpa migrasi ubah kolom |
| **Uang/kode** | `string()` dengan panjang eksplisit | — |
| **JSON** | `$table->json('x')` | Untuk snapshot & isian dinamis (lihat §12, §16) |
| **Role/Permission** | TIDAK dibuat manual — pakai package **Spatie Laravel Permission** (lihat §5) | Sesuai PRD F1 & perancangan-murni.md |
| **File/Upload** | Via **Spatie Media Library** (tabel `media` polymorphic) — SSOT penyimpanan | Keputusan K4 (ARCHITECTURE.md §9) — lihat aturan di bawah |

> **Catatan penomoran**: nomor kolom `created_at`/`updated_at` diasumsikan ada di setiap tabel yang memakai `timestamps()` walau tidak selalu ditulis eksplisit di daftar kolom, agar tabel tidak berulang.

> **Aturan file (K4 — penting)**: penyimpanan file memakai **Spatie Media Library** sebagai SSOT. Ada dua pola nilai di DB:
> - **File hidup** (bisa berubah/dihapus): TIDAK menyimpan kolom path eksplisit — dikaitkan lewat relasi `media` (collection). Model memakai trait `HasMedia`. Berlaku untuk: `pejabat` TTD master (§4), `templates` file `.docx` (§7), `dokumen_mahasiswa` (§11), `surat_masuk.berkas_scan` (§18), `lampiran_surat_masuk` (§20), `surat_keluar.berkas_scan` (§21), `ref_syarat_surat.template_file` (§10).
> - **File arsip immutable** (snapshot, tak boleh berubah): TETAP menyimpan **kolom path string beku** — `surat_tercetak.file_pdf_path`/`file_docx_path` (§16), `surat_penandatangan.file_ttd_path` (§17). Alasan: ERD §16 arsip immutable; nilai harus bertahan walau record media master dihapus.
>
> Di bawah, kolom file yang berlabel *(→ Media Library)* berarti path fisik TIDAK disimpan sebagai kolom — cukup relasi `media`. Kolom tanpa label tetap disimpan seperti tertulis.

---

## §1. `units` — Unit Penerbit Surat

**Fungsi**: master unit organisasi (BAA, Fakultas Teknik, Prodi Informatika, LPPM). Menjadi tulang punggung arsitektur *unit-aware* (PRD G6) — meski Phase 1 hanya satu unit aktif, struktur sudah siap multi-unit di Phase 2 tanpa refactor.

| Kolom | Tipe Laravel | Null | Default | Unique | Keterangan |
|---|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK | |
| nama | `string('nama', 150)` | ✗ | — | | Nama unit, mis. "BAA" |
| kode | `string('kode', 20)` | ✗ | — | ✓ | Kode singkat, dipakai di format nomor surat |
| parent_id | `foreignId('parent_id')->nullable()->constrained('units')` | ✓ | null | | Unit induk (hierarki: Prodi di bawah Fakultas) |
| is_active | `boolean('is_active')` | ✗ | `true` | | Nonaktif = tidak muncul di pilihan |

**Relasi**:
- `units` **1—n** `units` (self-reference via `parent_id`) → hierarki unit
- `units` **n—n** `templates` (via `template_unit`, §7.1)
- `units` **n—n** `pejabat` (via `pejabat_unit`, §4.1)
- `units` **1—n** `permohonan_surat`, `surat_tercetak`, `surat_masuk`, `surat_keluar`, `users`

**Implikasi PRD**: `kode` dipakai membangun nomor surat (`{urut}/{unit}/{kode_univ}/...`, PRD F2). Self-reference `parent_id` menyiapkan skenario Phase 2 di mana Admin Unit fakultas melihat data seluruh prodi di bawahnya.

---

## §2. `users` — Akun Pengguna (Generik)

**Fungsi**: entitas autentikasi untuk **semua** jenis pengguna (super admin, admin surat, mahasiswa, dan kelak pejabat). Sengaja dibuat generik — **tanpa** kolom khusus mahasiswa dan **tanpa** kolom `role` — agar bisa direlasikan ke peran apapun di masa depan.

| Kolom | Tipe Laravel | Null | Default | Unique | Keterangan |
|---|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK | |
| nama | `string('nama', 150)` | ✗ | — | | Nama tampil semua jenis user |
| email | `string('email', 150)` | ✗ | — | ✓ | Untuk login |
| password | `string('password')` | ✗ | — | | Hash bcrypt |
| unit_id | `foreignId('unit_id')->nullable()->constrained('units')` | ✓ | null | | Unit tempat bertugas (staf/admin) |
| is_active | `boolean('is_active')` | ✗ | `true` | | Nonaktif tidak bisa login (PRD F1) |
| | `timestamps()` + `softDeletes()` | | | | |

**Relasi**:
- `users` **1—1** `mahasiswa` (§3)
- `users` **1—n** `permohonan_surat` (sebagai pemohon), `surat_tercetak` (sebagai digenerate_oleh), dll
- `users` **n—n** roles (via Spatie, §5)

**Implikasi PRD / kenapa dirancang begini**:
- **Kasus yang diatasi**: PRD menyebut Phase 2 akan menambah relasi user ke jabatan/pejabat. Jika data mahasiswa (NIM, prodi) ditaruh langsung di `users`, tabel jadi "berbau mahasiswa" dan menyulitkan saat pejabat/dosen juga butuh akun. Dengan memisah ke §3, `users` tetap netral.
- **Role dipisah ke Spatie** (§5): PRD F1 minta role fleksibel + Phase 2 "teams per unit". Kolom `role` enum tunggal tidak bisa menampung satu user dengan role berbeda di unit berbeda — Spatie bisa.

---

## §3. `mahasiswa` — Profil Akademik (1—1 dengan users)

**Fungsi**: menyimpan atribut akademik mahasiswa hasil snapshot import SIAKAD (PRD §4.7, A2). Relasi one-to-one ke `users`.

| Kolom | Tipe Laravel | Null | Default | Unique | Keterangan |
|---|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK | |
| user_id | `foreignId('user_id')->constrained()` | ✗ | — | ✓ | 1—1 ke `users`; UNIQUE menjamin satu profil per akun |
| nim | `string('nim', 20)` | ✗ | — | ✓ | Nomor Induk Mahasiswa |
| nama | `string('nama', 150)` | ✗ | — | | Snapshot nama dari SIAKAD |
| prodi | `string('prodi', 100)` | ✗ | — | | Program studi |
| is_active | `boolean('is_active')` | ✗ | `true` | | Kontrol akses independen dari status akademik |

**Relasi**: `mahasiswa` **1—1** `users` (via `user_id UNIQUE`).

**Implikasi PRD**:
- **UNIQUE pada `user_id`** menegakkan aturan 1—1 di level DB, bukan cuma aplikasi — mencegah data korup (satu akun punya dua profil).
- **`is_active` terpisah** dari `users.is_active`: PRD A2 mengizinkan mahasiswa DO/cuti tetap bisa request selama diaktifkan admin — pemisahan ini membedakan "akun bisa login" (users) vs "boleh mengajukan surat" (mahasiswa).
- **Kenapa `nim`/`nama`/`prodi` disimpan (snapshot), bukan tarik realtime**: PRD Batasan §10.2 — integrasi SIAKAD realtime baru Phase 3. Snapshot menerima risiko data usang demi kesederhanaan Phase 1.
- **Tidak ada kolom `fakultas`** (keputusan D-001, `DECISIONS.md`) — dihapus bersama placeholder `{{fakultas}}` karena tidak diimport SIAKAD & tidak esensial ke isi surat.

> **Keputusan yang perlu dikonfirmasi** `[PERLU VALIDASI]`: FK pemohon di `permohonan_surat`/`dokumen_mahasiswa` menunjuk ke `users.id` (auth principal), bukan `mahasiswa.id`. Join ke `mahasiswa` dilakukan saat butuh NIM/prodi. Ini konsisten pola Laravel `auth()->id()`.

---

## §4. `pejabat` — Master Penandatangan

**Fungsi**: daftar pejabat yang bisa dipilih sebagai penandatangan surat / pemberi persetujuan (PRD F2, F6, F7). Terpisah dari `users` karena di Phase 1 pejabat **tidak** punya akun (model *proxy approval*, PRD A4).

| Kolom | Tipe Laravel | Null | Default | Unique | Keterangan |
|---|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK | |
| nama | `string('nama', 150)` | ✗ | — | | |
| email | `string('email', 150)->nullable()` | ✓ | null | | Notifikasi Phase 2 (magic link) |
| nip_nidn | `string('nip_nidn', 30)->nullable()` | ✓ | null | | |
| jabatan | `string('jabatan', 150)` | ✗ | — | | Mis. "Kaprodi Informatika" |
| ~~file_ttd_path~~ | *(→ Media Library)* | | | | Gambar TTD master via collection `ttd`, disk **private** (K4). Bukan kolom. |
| is_active | `boolean('is_active')` | ✗ | `true` | | |
| user_id | `foreignId('user_id')->nullable()->constrained()` | ✓ | null | | Phase 2: diisi jika pejabat punya akun |

**Relasi**:
- `pejabat` **n—n** `units` (via `pejabat_unit`, §4.1)
- `pejabat` **1—n** `permohonan_surat` (sebagai pemberi persetujuan), `surat_penandatangan`, `disposisi_surat_masuk`
- `pejabat` **1—1 (opsional)** `users` (via `user_id`, Phase 2)

**Implikasi PRD**:
- **File TTD via Media Library (K4)**: gambar TTD master disimpan di collection `ttd` (disk private), bukan kolom path. "Pejabat tidak punya TTD" = collection `ttd` kosong → PRD F7 & kasar §F membedakan TTD basah vs image: kosong = TTD basah → sistem menyarankan metode "Ambil di Kampus" (BR-10). Saat generate, path di-snapshot ke `surat_penandatangan.file_ttd_path` (§17).
- **`user_id` nullable disiapkan sejak Phase 1**: transisi ke Phase 2 (pejabat punya akun) tidak butuh migrasi tambah kolom — sudah ada, tinggal diisi.

### §4.1. `pejabat_unit` — Pivot (pejabat n—n units)

**Fungsi**: satu pejabat dapat menjabat di lebih dari satu unit (mis. Dekan yang juga Ketua LPPM), dan satu unit punya banyak pejabat.

| Kolom | Tipe Laravel | Null | FK |
|---|---|---|---|
| id | `id()` | ✗ | PK |
| pejabat_id | `foreignId('pejabat_id')->constrained()` | ✗ | → pejabat |
| unit_id | `foreignId('unit_id')->constrained()` | ✗ | → units |

**Constraint tambahan**: `unique(['pejabat_id','unit_id'])` — cegah duplikat pasangan.

**Implikasi PRD**: sebelumnya `pejabat` punya `unit_id` tunggal. Diubah n—n karena realita kampus: pejabat sering merangkap. Tanpa pivot ini, satu pejabat harus diduplikasi per unit → data tidak konsisten.

---

## §5. Roles & Permissions (Spatie Laravel Permission)

**Fungsi**: RBAC untuk PRD F1. **Tidak dibuat manual** — package Spatie menghasilkan tabel: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

- `model_has_roles` menghubungkan `users` (morph) **n—n** `roles`.
- Role Phase 1: `super_admin`, `admin_surat`, `mahasiswa`.
- Phase 2 mengaktifkan fitur **teams** (`team_id = unit_id`) agar satu user punya role berbeda per unit.

**Implikasi PRD**: memenuhi F1 (kelola role) + kesiapan Phase 2 multi-unit tanpa mengubah tabel `users`. Detail kolom mengikuti dokumentasi package (di luar cakupan skema kustom).

---

## §5.1. `settings` — Konfigurasi Sistem (Master)

> Tabel **core mandiri** (bukan bagian Spatie); ditempatkan di sini untuk pengelompokan konfigurasi. Menutup gap **FEATURE_MAP C1** — sebelumnya M-CONFIG (PRD F2) tidak punya tabel target.

**Fungsi**: menyimpan konfigurasi sistem yang dapat diubah admin (PRD F2): identitas kampus, tahun akademik, helper format nomor, SMTP. Pola **key-value bergrup** agar setting baru bisa ditambah tanpa migrasi.

| Kolom | Tipe Laravel | Null | Default | Unique | Keterangan |
|---|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK | |
| group | `string('group', 50)` | ✗ | — | | Pengelompokan form: `umum`/`akademik`/`penomoran`/`smtp` |
| key | `string('key', 100)` | ✗ | — | ✓ | Identifier unik, mis. `nama_universitas` |
| value | `text('value')->nullable()` | ✓ | null | | Nilai tersimpan (string; di-cast sesuai `type`) |
| type | `string('type', 20)` | ✗ | `'string'` | | Cara cast: `string`/`text`/`integer`/`boolean`/`json`/`media`/`encrypted` |
| label | `string('label', 150)` | ✗ | — | | Label di form admin |
| is_public | `boolean('is_public')` | ✗ | `false` | | Boleh dibaca tanpa login (mis. nama kampus di halaman verifikasi §16) |
| | `timestamps()` | | | | |

**Model**: `Setting implements HasMedia` — untuk `type='media'` (logo), file dilampirkan ke baris via collection `file` (disk **public**, karena tampil di kop surat & halaman publik). `type='encrypted'` (password SMTP) memakai cast `encrypted`.

**Relasi**:
- `settings` **morphMany** `media` — hanya baris `type='media'` (logo). K4.
- Tidak ada FK keluar — dibaca secara **logis** oleh banyak modul (lihat implikasi).

**Implikasi PRD / kenapa dirancang begini**:
- **Key-value bergrup, bukan kolom tetap**: setting baru (mis. toggle Phase 2) tinggal seed satu baris — tanpa migrasi ubah skema. KISS + fleksibel.
- **SSOT via `SettingService` + cache**: dibaca di banyak request (layout, generate). Cache seluruh map (`rememberForever`), di-bust saat admin simpan → performa aman.
- **Sumber placeholder kelompok `sistem`** (§8, murni §4.4): `{{nama_universitas}}`, `{{kode_universitas}}`, `{{logo_kampus}}`, `{{tahun_akademik}}` di-resolve M-GENERATE dari tabel ini saat generate surat.
- **SMTP runtime**: sebuah ServiceProvider meng-override `config('mail.*')` dari settings saat boot agar Mailer (M-NOTIF) memakainya. Kredensial disimpan `encrypted`. *(Alternatif: kredensial di `.env`, sisanya di settings — pilihan ops.)*
- **Owner**: M-CONFIG (R/W). **Pembaca**: M-GENERATE (placeholder sistem), M-VERIFIKASI (`is_public`), M-NOTIF (from name), M-MASUK/M-KELUAR (`tahun_akademik` → nomor agenda, menutup FEATURE_MAP C3).

**Sample seed** (di-seed produksi via `SettingSeeder` — nilai default/kosong):

| group | key | type | contoh value |
|---|---|---|---|
| umum | nama_universitas | string | "Universitas Nusa Putra" |
| umum | kode_universitas | string | "UNsP" |
| umum | alamat_kampus | text | "Jl. …" |
| umum | logo_kampus | media | *(file via Media Library)* |
| akademik | tahun_akademik_aktif | string | "2025/2026" |
| penomoran | format_nomor_helper | string | "{urut}/{unit}/{kode_univ}/{bulan_romawi}/{tahun}" |
| dokumen | libreoffice_path | string | path binari LibreOffice; **kosong = PDF dimatikan**, output DOCX saja (pola OpenSID — ARCHITECTURE §2.1) |
| smtp | mail_host | string | "smtp.kampus.ac.id" |
| smtp | mail_port | integer | 587 |
| smtp | mail_username | string | — |
| smtp | mail_password | encrypted | *(terenkripsi)* |
| smtp | mail_from_address | string | "no-reply@kampus.ac.id" |
| smtp | mail_from_name | string | "Sistem Surat UNsP" |

---

## §6. `kategori_surat` — Master Kategori Template

**Fungsi**: mengelompokkan template (Layanan Mahasiswa, Administrasi Internal, SK, dll) — PRD F12. Dipakai sebagai dropdown & filter di F3.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| nama | `string('nama', 100)` | ✗ | — | Mis. "Layanan Mahasiswa" |
| is_active | `boolean('is_active')` | ✗ | `true` | |

**Relasi**: `kategori_surat` **1—n** `templates`.

**Implikasi PRD**: F12 minta kategori "tidak bisa dihapus jika dipakai". Diimplementasikan via FK `templates.kategori_id` dengan `onDelete('restrict')` — DB menolak hapus kategori yang masih dirujuk. Dropdown, bukan teks bebas → mencegah inkonsistensi ejaan kategori.

---

## §7. `templates` — Master Template Surat

**Fungsi**: inti sistem (PRD F3). Menyimpan file `.docx` + metadata + perilaku permohonan.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| nama | `string('nama', 150)` | ✗ | — | |
| kategori_id | `foreignId('kategori_id')->constrained('kategori_surat')` | ✗ | — | → kategori_surat (restrict) |
| deskripsi | `text('deskripsi')->nullable()` | ✓ | null | |
| ~~file_path~~ | *(→ Media Library)* | | | File `.docx` via collection `docx` (`singleFile`), disk private (K4). Bukan kolom. |
| tipe_pemohon | `string('tipe_pemohon', 20)` | ✗ | `'umum'` | Nilai: `mahasiswa` / `umum` (§4.9 murni) |
| sla_hari_kerja | `tinyInteger('sla_hari_kerja')->nullable()` | ✓ | null | Estimasi hari kerja (SLA) |
| is_permohonan_mandiri | `boolean('is_permohonan_mandiri')` | ✗ | `false` | true = tampil di list mahasiswa |
| status | `string('status', 20)` | ✗ | `'draft'` | Nilai: `draft`/`aktif`/`nonaktif` |
| created_by | `foreignId('created_by')->constrained('users')` | ✗ | — | Admin pembuat |
| | `timestamps()` + `softDeletes()` | | | |

**Relasi**:
- `templates` **n—1** `kategori_surat`
- `templates` **n—n** `units` (via `template_unit`, §7.1)
- `templates` **1—n** `template_placeholder_config` (§9), `template_data_tambahan_fields` (§13)
- `templates` **n—n** `ref_syarat_surat` (via `syarat_surat`, §10.1)
- `templates` **1—n** `permohonan_surat`, `surat_tercetak`

**Implikasi PRD / kenapa dirancang begini**:
- **`tipe_pemohon` sebagai kolom eksplisit** (bukan hasil deteksi): PRD F7 & §4.9 murni — menentukan apakah form generate langsung admin butuh langkah "Cari Mahasiswa". Kolom eksplisit → filter/list template sederhana (`WHERE tipe_pemohon=...`), didukung auto-remediasi saat deteksi placeholder (BR-04).
- **`is_permohonan_mandiri`**: memisahkan template yang boleh diajukan mahasiswa (F5) dari yang hanya untuk admin (Nota Dinas, SK). Kasus yang diatasi: mahasiswa tidak boleh melihat/mengajukan SK Rektor.
- **`softDeletes`**: F3 minta "template berpermohonan tidak bisa dihapus, hanya nonaktif". Soft delete + status `nonaktif` menjaga integritas arsip lama yang merujuk template ini.
- **`status` string, bukan enum**: memudahkan menambah status baru (mis. `arsip`) tanpa migrasi.

### §7.1. `template_unit` — Pivot (templates n—n units, nullable)

**Fungsi**: satu template bisa diterbitkan oleh beberapa unit; satu unit punya banyak template. **Nullable secara relasi** — template boleh belum di-assign unit manapun (0 baris valid).

| Kolom | Tipe Laravel | Null | FK |
|---|---|---|---|
| id | `id()` | ✗ | PK |
| template_id | `foreignId('template_id')->constrained()` | ✗ | → templates |
| unit_id | `foreignId('unit_id')->constrained()` | ✗ | → units |

**Constraint**: `unique(['template_id','unit_id'])`.

**Implikasi PRD / konsekuensi penting**: karena template kini n—n unit, `unit_id` tidak lagi otomatis pasti tunggal. **Keputusan (resolve ⚠️#5)**:
- **`permohonan_surat.unit_id`** — **mahasiswa TIDAK memilih unit** saat mengajukan. Sistem **auto-derive**: bila template terhubung ke **tepat 1** unit → isi otomatis; bila 0 atau >1 unit (ambigu) → biarkan **NULL** (field administratif untuk filter/laporan, bukan blocker alur). Konsisten dengan `perancangan-murni.md` §4.1 — Phase 1 operasional terpusat (hanya 1 unit aktif), jadi kasus ambigu praktis jarang terjadi.
- **`surat_tercetak.unit_id`** — tetap **dipilih eksplisit oleh admin** di Form Generate (sudah ada di alur generate, lihat §16) — ini yang jadi sumber kebenaran "unit penerbit" surat, bukan `permohonan_surat.unit_id`.

---

## §8. `placeholder_definitions` — Kamus Placeholder (Master)

**Fungsi**: kamus master yang mendefinisikan perilaku placeholder yang **dikenal** sistem (PRD F13). Di-seed saat instalasi, dikelola Super Admin.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| name | `string('name', 100)` | ✗ | — | Dicocokkan exact-match saat scan; sebaiknya UNIQUE |
| kelompok | `string('kelompok', 20)` | ✗ | — | Nilai: `profil`/`waktu`/`sistem`/`counter`/`ttd` |
| input_type | `string('input_type', 20)` | ✗ | — | `text`/`date`/`number`/`textarea`/`file`/`image` |
| source | `string('source', 100)->nullable()` | ✓ | null | Keterangan sumber data (dokumentasi) |
| is_overridable | `boolean('is_overridable')` | ✗ | `true` | Bisa di-override admin atau tidak |

**Relasi**: tidak ada FK keluar — dirujuk secara **logis** (exact-match `name`) oleh proses scan, bukan FK fisik ke `template_placeholder_config`.

**Implikasi PRD / kenapa begini**:
- **`kelompok` di-assign manual** (bukan analisis otomatis) — PRD F3/§4.4 murni: saat scan template, sistem hanya lookup exact-match lalu baca `kelompok` yang sudah tersimpan. Ini kamus, bukan AI.
- **Bukan hardcode di kode** → Super Admin bisa tambah placeholder baru tanpa deploy (F13). Kasus yang diatasi: kebutuhan placeholder baru muncul terus, tidak realistis ubah kode tiap kali.
- **Kelompok `ttd` tidak di-seed sebagai baris**: deteksi TTD via regex (angka slot tak terbatas), bukan lookup tabel — lihat §9.

**Sample seed**:

| name | kelompok | input_type |
|---|---|---|
| nama_mahasiswa | profil | text |
| nim | profil | text |
| nama_universitas | sistem | text |
| logo_kampus | sistem | image |
| tanggal_surat | waktu | date |
| nomor_surat | counter | text |

---

## §9. `template_placeholder_config` — Placeholder Hasil Scan per Template

**Fungsi**: hasil scan placeholder untuk **satu template tertentu** — satu baris = satu placeholder di satu template (PRD F3). Terisi otomatis saat upload, bisa dikoreksi admin.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| template_id | `foreignId('template_id')->constrained()->cascadeOnDelete()` | ✗ | — | → templates |
| placeholder_name | `string('placeholder_name', 100)` | ✗ | — | Nama hasil scan, belum tentu ada di kamus |
| label_mahasiswa | `string('label_mahasiswa', 255)->nullable()` | ✓ | null | Label ramah, auto-transform dari nama |
| tipe_input | `string('tipe_input', 20)` | ✗ | — | `text`/`date`/`number`/`textarea` |
| filled_by | `string('filled_by', 20)` | ✗ | — | `sistem`/`mahasiswa`/`admin` |
| is_required | `boolean('is_required')` | ✗ | `true` | |
| urutan | `tinyInteger('urutan')` | ✗ | 0 | Urutan tampil di form |

**Relasi**: `template_placeholder_config` **n—1** `templates` (cascade delete — hapus template hapus confignya).

**Implikasi PRD / kenapa tabel, bukan JSON**:
- **Kasus yang diatasi**: perlu di-*query* ("apakah template X punya placeholder kelompok profil?" → menentukan langkah Cari Mahasiswa, §4.9) dan tiap baris perlu diedit terpisah (override `filled_by`/tipe/label). JSON blob menyulitkan query & edit per-item.
- **`filled_by`** = dimensi kunci yang mengatur di form mana placeholder muncul: `mahasiswa` → form permohonan (F5 Lapisan 2), `admin` → form generate (F7), `sistem` → tidak muncul (auto-fill).
- **`label_mahasiswa` nullable + auto-transform**: nilai default dari transformasi nama (`nama_perusahaan` → "Nama Perusahaan"), admin bisa perhalus.

> **Catatan penamaan** `[PERLU VALIDASI]`: nama `label_mahasiswa` sempit — field `filled_by=admin` juga butuh label. Pertimbangkan rename → `label` generik (belum diputuskan).

---

## §10. `ref_syarat_surat` — Master Persyaratan

**Fungsi**: master persyaratan dokumen reusable lintas template (PRD F4).

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| nama | `string('nama', 255)` | ✗ | — | Mis. "Fotokopi KHS" |
| deskripsi | `text('deskripsi')->nullable()` | ✓ | null | |
| ~~template_file~~ | *(→ Media Library)* | | File contoh untuk diunduh mahasiswa, collection `template` (nullable — 0/1 file) (K4). Bukan kolom. |
| accepted_types | `string('accepted_types', 100)` | ✗ | — | Mis. "pdf,jpg" |
| max_size_mb | `tinyInteger('max_size_mb')` | ✗ | 5 | Batas ukuran unggah |

**Relasi**:
- `ref_syarat_surat` **n—n** `templates` (via `syarat_surat`, §10.1)
- `ref_syarat_surat` **1—n** `dokumen_mahasiswa`, `permohonan_syarat`

**Implikasi PRD**: F4 "satu persyaratan dipakai banyak template" → wajib n—n (§10.1), bukan kolom di template. `template_file` nullable memenuhi F5 Lapisan 4: jika ada, mahasiswa dapat tombol "Download Template" untuk diisi lalu diunggah balik.

### §10.1. `syarat_surat` — Pivot (templates n—n ref_syarat_surat)

| Kolom | Tipe Laravel | Null | Default | FK |
|---|---|---|---|---|
| id | `id()` | ✗ | — | PK |
| template_id | `foreignId('template_id')->constrained()->cascadeOnDelete()` | ✗ | — | → templates |
| syarat_id | `foreignId('syarat_id')->constrained('ref_syarat_surat')` | ✗ | — | → ref_syarat_surat |
| is_required | `boolean('is_required')` | ✗ | `true` | Wajib/opsional per template |
| urutan | `tinyInteger('urutan')` | ✗ | 0 | |

**Implikasi**: `is_required` ada di **pivot**, bukan di master — karena persyaratan yang sama bisa wajib di satu template, opsional di template lain. Cascade delete: hapus template → putuskan link, tapi master `ref_syarat_surat` tetap utuh untuk template lain.

---

## §11. `dokumen_mahasiswa` — Media Library Mahasiswa

**Fungsi**: menyimpan file yang pernah diunggah mahasiswa agar dapat dipakai ulang (PRD F5.2 "Dokumen Saya").

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| mahasiswa_id | `foreignId('mahasiswa_id')->constrained('users')` | ✗ | — | FK ke `users` (auth principal) |
| nama | `string('nama', 255)` | ✗ | — | Nama tampil dokumen |
| syarat_id | `foreignId('syarat_id')->nullable()->constrained('ref_syarat_surat')` | ✓ | null | Kategori syarat (opsional) |
| ~~filename / path / file_size~~ | *(→ Media Library)* | | File via collection `dokumen` (K4); metadata (nama file, ukuran, mime) diambil dari record `media`. Bukan kolom. |
| | `timestamps()` + `softDeletes()` | | | |

**Relasi**:
- `dokumen_mahasiswa` **n—1** `users`
- `dokumen_mahasiswa` **1—n** `permohonan_syarat` (satu dokumen dipakai di banyak permohonan)

**Implikasi PRD**: F5.2 "upload sekali, pakai berulang" + "tidak bisa dihapus jika dipakai permohonan aktif" → soft delete + pengecekan aplikasi terhadap `permohonan_syarat`. Menghemat effort mahasiswa yang sering butuh syarat sama (KTM, KHS) di banyak permohonan.

---

## §12. `permohonan_surat` — Permohonan (Kamar 1)

**Fungsi**: entitas permohonan mahasiswa (PRD F5, F6). Kamar 1 dari arsitektur 3 Kamar.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| parent_permohonan_id | `foreignId('parent_permohonan_id')->nullable()->constrained('permohonan_surat')` | ✓ | null | Diisi bila hasil "Ajukan Ulang" (F5.4) |
| mahasiswa_id | `foreignId('mahasiswa_id')->constrained('users')` | ✗ | — | Pemohon (auth principal) |
| template_id | `foreignId('template_id')->constrained()` | ✗ | — | → templates |
| unit_id | `foreignId('unit_id')->nullable()->constrained('units')` | ✓ | null | Auto-derive bila template n—1 unit; NULL bila ambigu (mahasiswa tak memilih — lihat §7.1) |
| status | `string('status', 20)` | ✗ | `'pending'` | Lihat daftar status di bawah |
| isian_form | `json('isian_form')->nullable()` | ✓ | null | Nilai Lapisan 2, key = placeholder_name |
| catatan_penolakan | `text('catatan_penolakan')->nullable()` | ✓ | null | Wajib bila ditolak; tampil ke mahasiswa |
| approved_by | `foreignId('approved_by')->nullable()->constrained('users')` | ✓ | null | Admin yang klik Setujui/Tolak |
| pejabat_id | `foreignId('pejabat_id')->nullable()->constrained('pejabat')` | ✓ | null | Pemberi persetujuan offline (proxy approval) |
| catatan_approval | `text('catatan_approval')->nullable()` | ✓ | null | Catatan internal |
| approved_at | `timestamp('approved_at')->nullable()` | ✓ | null | |
| | `timestamps()` + `softDeletes()` | | | |

**Nilai `status`**: `draft` / `pending` / `diverifikasi` / `disetujui` / `ditolak` / `dibatalkan` / `selesai`.

**Relasi**:
- `permohonan_surat` **n—1** `users` (pemohon), `templates`, `units`, `pejabat`
- `permohonan_surat` **1—n** `permohonan_data_tambahan_values` (§14), `permohonan_syarat` (§15)
- `permohonan_surat` **1—n** `surat_tercetak` (§16)
- `permohonan_surat` **1—1 (self)** via `parent_permohonan_id` (rantai resubmit)

**Implikasi PRD / kenapa dirancang begini**:
- **`isian_form` JSON**: Lapisan 2 berisi placeholder yang berbeda-beda per template — tidak mungkin satu skema kolom. JSON fleksibel (BR di brd). Key memakai `placeholder_name` agar mudah dipetakan saat generate.
- **`parent_permohonan_id` self-FK**: F5.4 resubmit — menjaga rantai riwayat "pengajuan ulang dari #X" tanpa menghapus yang lama.
- **`approved_by` (users) vs `pejabat_id` (pejabat) dipisah**: inti *proxy approval* (PRD A4) — admin yang mengeksekusi (`approved_by`) berbeda dari pejabat yang menyetujui secara offline (`pejabat_id`). Audit jelas: siapa mengklik, atas persetujuan siapa.
- **`status` belum `disetujui` ≠ ada file**: file surat baru lahir di §16 saat generate. Status `disetujui` hanya membuka tombol Generate (BR-12).

---

## §13. `template_data_tambahan_fields` — Definisi Field Data Tambahan

**Fungsi**: field yang diisi mahasiswa tapi **tidak** masuk isi surat (mis. No. HP, alamat) — PRD F3 & F5 Lapisan 3. Skema EAV (definisi).

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| template_id | `foreignId('template_id')->constrained()->cascadeOnDelete()` | ✗ | — | → templates |
| label | `string('label', 255)` | ✗ | — | |
| field_key | `string('field_key', 100)` | ✗ | — | Auto dari label, key EAV |
| tipe_input | `string('tipe_input', 20)` | ✗ | — | `text`/`date`/`number` |
| is_required | `boolean('is_required')` | ✗ | `true` | |
| helper_text | `string('helper_text', 255)->nullable()` | ✓ | null | |
| urutan | `tinyInteger('urutan')` | ✗ | 0 | |
| | `softDeletes()` | | | Hard delete di-RESTRICT bila ada nilai |

**Relasi**: **1—n** `permohonan_data_tambahan_values` (§14).

**Implikasi PRD**: EAV dipilih karena field beda-beda per template & bisa berubah. Soft delete + RESTRICT (§14) menjaga nilai historis: field yang dihapus admin tetap tampil di permohonan lama.

---

## §14. `permohonan_data_tambahan_values` — Nilai Data Tambahan (EAV)

**Fungsi**: nilai aktual Lapisan 3 per permohonan.

| Kolom | Tipe Laravel | Null | FK / Aturan |
|---|---|---|---|
| id | `id()` | ✗ | PK |
| permohonan_id | `foreignId('permohonan_id')->constrained('permohonan_surat')->cascadeOnDelete()` | ✗ | hapus permohonan → hapus nilai |
| field_id | `foreignId('field_id')->constrained('template_data_tambahan_fields')->restrictOnDelete()` | ✗ | RESTRICT: field tak bisa hard-delete bila ada nilai |
| nilai | `text('nilai')` | ✗ | Tipe asli mengikuti `field_id.tipe_input` |

**Relasi**: **n—1** `permohonan_surat`, **n—1** `template_data_tambahan_fields`.

**Implikasi PRD**: kombinasi **CASCADE** (dari permohonan) + **RESTRICT** (dari field) adalah desain sengaja — nilai ikut terhapus bila permohonannya dihapus, tapi definisi field dilindungi selama masih ada nilai yang merujuknya (F3).

---

## §15. `permohonan_syarat` — File Persyaratan per Permohonan

**Fungsi**: mengaitkan file persyaratan (unggahan/dari media library) ke satu permohonan (PRD F5 Lapisan 4).

| Kolom | Tipe Laravel | Null | FK |
|---|---|---|---|
| id | `id()` | ✗ | PK |
| permohonan_id | `foreignId('permohonan_id')->constrained('permohonan_surat')->cascadeOnDelete()` | ✗ | → permohonan_surat |
| syarat_id | `foreignId('syarat_id')->constrained('ref_syarat_surat')` | ✗ | → ref_syarat_surat |
| dokumen_id | `foreignId('dokumen_id')->nullable()->constrained('dokumen_mahasiswa')` | ✓ | Referensi dokumen sumber (Media Library) di "Dokumen Saya" |
| filename | `string('filename')` | ✗ | Snapshot nama file saat diajukan (untuk tampilan) |
| uploaded_at | `timestamp('uploaded_at')` | ✗ | |

**Relasi**: **n—1** `permohonan_surat`, `ref_syarat_surat`, `dokumen_mahasiswa` (opsional).

**Implikasi PRD (K4)**: file fisik hidup di `dokumen_mahasiswa` (Media Library, §11). `permohonan_syarat` cukup menunjuk `dokumen_id` + menyimpan `filename` sebagai **snapshot nama** untuk tampilan riwayat (tetap terbaca walau dokumen sumber di-soft-delete). Kolom `path` fisik **dilepas** — akses file lewat relasi `dokumen_mahasiswa`→`media`. Dua cara F5 Lapisan 4: "Upload File" baru (buat `dokumen_mahasiswa` + media, lalu link) atau "Pilih dari Dokumen Saya" (`dokumen_id` menunjuk yang sudah ada).

---

## §16. `surat_tercetak` — Arsip Surat (Kamar 2)

**Fungsi**: arsip **immutable** setiap surat yang digenerate final (PRD F7, F8). Kamar 2 dari arsitektur 3 Kamar.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| permohonan_id | `foreignId('permohonan_id')->nullable()->constrained('permohonan_surat')` | ✓ | null | NULL = Generate Langsung (sub-flow B) |
| template_id | `foreignId('template_id')->constrained()` | ✗ | — | → templates |
| unit_id | `foreignId('unit_id')->nullable()->constrained('units')` | ✓ | null | Unit penerbit, dipilih eksplisit |
| nomor_surat | `string('nomor_surat', 100)` | ✗ | — | String lengkap (Mode A) |
| digenerate_oleh | `foreignId('digenerate_oleh')->constrained('users')` | ✗ | — | |
| digenerate_at | `timestamp('digenerate_at')` | ✗ | — | |
| data_placeholder | `json('data_placeholder')` | ✗ | — | Snapshot semua nilai saat generate |
| file_pdf_path | `string('file_pdf_path')` | ✗ | — | **Kolom path beku (snapshot)** — arsip immutable, disimpan walau K4 (lihat catatan) |
| file_docx_path | `string('file_docx_path')` | ✗ | — | **Kolom path beku (snapshot)** — sama |
| qr_hash | `string('qr_hash', 64)` | ✗ | — | Untuk URL verifikasi publik |
| metode_pengambilan | `string('metode_pengambilan', 20)->nullable()` | ✓ | null | `download` / `ambil_di_kampus` |
| status | `string('status', 20)` | ✗ | `'aktif'` | `aktif`/`digantikan`/`dibatalkan` |
| replaced_by_id | `foreignId('replaced_by_id')->nullable()->constrained('surat_tercetak')` | ✓ | null | Diisi bila dicetak ulang |
| replaced_reason | `text('replaced_reason')->nullable()` | ✓ | null | |
| | `timestamps()` | | | **Tanpa** softDeletes — arsip tak dihapus |

**Constraint**: `unique(['nomor_surat','unit_id'])` — hard guard duplikat nomor per unit.

**Relasi**:
- `surat_tercetak` **n—1** `permohonan_surat` (opsional), `templates`, `units`, `users`
- `surat_tercetak` **1—n** `surat_penandatangan` (§17)
- `surat_tercetak` **1—1 (self)** via `replaced_by_id` (rantai cetak ulang)

**Implikasi PRD / kenapa dirancang begini**:
- **`permohonan_id` nullable** = jantung dua sub-flow F7: terisi (dari permohonan, sub-flow A) atau NULL (generate langsung admin, sub-flow B). Satu tabel melayani keduanya.
- **`data_placeholder` JSON snapshot** (PRD §4.8): arsip bisa dibaca ulang walau data mahasiswa/pejabat berubah di masa depan — memenuhi F8 "arsip valid selamanya".
- **UNIQUE (nomor_surat, unit_id)**: PRD G4 & F7 jaminan tanpa duplikat di level DB, bukan cuma AJAX check. Per-unit karena tiap unit punya seri nomor sendiri.
- **Immutable = tanpa softDeletes + tanpa UPDATE**: F8 "arsip tak bisa diubah". Koreksi via **cetak ulang** → baris baru, baris lama `status=digantikan` + `replaced_by_id` → self-reference membentuk rantai versi. QR nomor lama bisa menampilkan "telah digantikan oleh X".
- **Kolom path beku, bukan Media Library (pengecualian K4)**: `file_pdf_path`/`file_docx_path` disimpan sebagai string beku (di-generate lewat `MediaService` lalu path-nya di-snapshot). File tetap boleh disimpan di disk via Media Library, tapi **nilai path di arsip tidak boleh berubah/hilang** walau record media dihapus — inilah alasan kolom dipertahankan (berbeda dari file hidup yang cukup relasi `media`).

---

## §17. `surat_penandatangan` — Slot TTD per Surat

**Fungsi**: menyimpan penandatangan (bisa >1 slot) untuk satu surat, dengan snapshot data pejabat (PRD F7, kasar §H).

| Kolom | Tipe Laravel | Null | Keterangan |
|---|---|---|---|
| id | `id()` | ✗ | PK |
| surat_tercetak_id | `foreignId('surat_tercetak_id')->constrained()->cascadeOnDelete()` | ✗ | → surat_tercetak |
| urutan | `tinyInteger('urutan')` | ✗ | Slot 1, 2, dst |
| label | `string('label', 100)->nullable()` | ✓ | Label statis, mis. "Menyetujui" |
| pejabat_id | `foreignId('pejabat_id')->nullable()->constrained('pejabat')` | ✓ | NULL bila diisi manual (tamu/dosen luar) |
| nama_snapshot | `string('nama_snapshot', 255)` | ✗ | Snapshot nama saat generate |
| jabatan_snapshot | `string('jabatan_snapshot', 255)` | ✗ | |
| nip_snapshot | `string('nip_snapshot', 50)->nullable()` | ✓ | |
| file_ttd_path | `string('file_ttd_path')->nullable()` | ✓ | **Kolom path beku (snapshot)** dari TTD master saat generate; kosong = TTD basah. Dipertahankan sbg arsip immutable (K4) |

**Relasi**: **n—1** `surat_tercetak`, **n—1** `pejabat` (opsional).

**Implikasi PRD**:
- **Snapshot nama/jabatan/NIP**, bukan hanya `pejabat_id`: bila pejabat pindah jabatan, arsip lama tetap menampilkan jabatan saat surat dibuat (konsisten §4.8).
- **`pejabat_id` nullable + snapshot terisi**: mendukung edge case kasar §H — penandatangan tamu/dosen luar diisi manual tanpa ada di master.
- **`file_ttd_path` per slot** (bukan cuma ambil dari master): satu surat bisa campur — Slot 1 TTD image, Slot 2 TTD basah → memicu saran metode pengambilan (BR-10).

---

## §18. `surat_masuk` — Agenda Surat Masuk

**Fungsi**: pencatatan surat fisik masuk + scan (PRD F9). Buku agenda masuk adalah *view* dari tabel ini (bukan tabel terpisah).

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| nomor_agenda | `smallInteger('nomor_agenda')->unsigned()` | ✗ | — | Auto-increment per tahun (aplikasi) |
| tahun_agenda | `smallInteger('tahun_agenda')` | ✗ | — | |
| nomor_surat | `string('nomor_surat', 50)` | ✗ | — | Nomor asli dari pengirim |
| tanggal_surat | `date('tanggal_surat')->nullable()` | ✓ | null | |
| tanggal_terima | `date('tanggal_terima')` | ✗ | — | |
| pengirim | `string('pengirim', 150)` | ✗ | — | |
| perihal | `string('perihal', 255)` | ✗ | — | |
| kode_klasifikasi | `string('kode_klasifikasi', 20)->nullable()` | ✓ | null | Lihat §Item Terbuka |
| keterangan | `text('keterangan')->nullable()` | ✓ | null | |
| ~~berkas_scan~~ | *(→ Media Library)* | | File scan utama via collection `scan` (`singleFile`) (K4). Bukan kolom. |
| dicatat_oleh | `foreignId('dicatat_oleh')->constrained('users')` | ✗ | — | |
| unit_id | `foreignId('unit_id')->constrained('units')` | ✗ | — | |
| | `timestamps()` + `softDeletes()` | | | |

**Relasi**:
- `surat_masuk` **1—n** `disposisi_surat_masuk` (§19)
- `surat_masuk` **morphMany** `media` — collection `scan` (utama) & `lampiran` (multi, §20)
- `surat_masuk` **n—1** `users`, `units`

**Implikasi PRD**:
- **`nomor_agenda` per tahun, bukan input admin** (F9.1): sistem generate — beda dari `nomor_surat` (dari pengirim). Reset per tahun via kombinasi `tahun_agenda`.
- **Buku agenda = view**: menghindari double-entry — satu sumber data, ditampilkan dengan filter (F9.3).

### §19. `disposisi_surat_masuk` — Disposisi (1—n dari surat_masuk)

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| surat_masuk_id | `foreignId('surat_masuk_id')->constrained()->cascadeOnDelete()` | ✗ | — | → surat_masuk |
| tujuan | `string('tujuan', 200)` | ✗ | — | Teks bebas Phase 1 (mis. "WR I") |
| isi_instruksi | `text('isi_instruksi')` | ✗ | — | |
| sifat | `string('sifat', 20)` | ✗ | `'biasa'` | `segera`/`biasa`/`rahasia` |
| batas_waktu | `date('batas_waktu')->nullable()` | ✓ | null | |
| status | `string('status', 30)` | ✗ | `'belum_ditindaklanjuti'` | / `sudah_ditindaklanjuti` |
| catatan_tindaklanjut | `text('catatan_tindaklanjut')->nullable()` | ✓ | null | |
| dicatat_oleh | `foreignId('dicatat_oleh')->constrained('users')` | ✗ | — | |
| ditindaklanjuti_oleh | `foreignId('ditindaklanjuti_oleh')->nullable()->constrained('users')` | ✓ | null | |
| ditindaklanjuti_at | `timestamp('ditindaklanjuti_at')->nullable()` | ✓ | null | |
| pejabat_id | `foreignId('pejabat_id')->nullable()->constrained('pejabat')` | ✓ | null | Phase 2: bila dari master |

**Relasi**: **n—1** `surat_masuk`, `users`, `pejabat` (opsional).

**Implikasi PRD**: **`tujuan` teks bebas + `pejabat_id` nullable** = strategi Phase 1→2 (F9.2). Phase 1 disposisi tanpa akun pejabat (teks); Phase 2 tinggal isi `pejabat_id` + email tanpa migrasi. One-to-many (banyak disposisi per surat) sesuai realita satu surat didisposisikan ke beberapa pihak.

### §20. Lampiran Surat Masuk — via Media Library (K4)

**Keputusan K4**: lampiran multi-file (PRD F9.1) **tidak perlu tabel terpisah** — cukup **collection `lampiran`** pada model `SuratMasuk` (Media Library mendukung banyak file per collection). Tabel `lampiran_surat_masuk` **dihapus dari skema**.

- Simpan: `$suratMasuk->addMedia($file)->toMediaCollection('lampiran')`
- Ambil: `$suratMasuk->getMedia('lampiran')` → daftar file + metadata (nama, ukuran, uploader via `custom_properties`)

**Implikasi**: menghilangkan satu tabel + logika upload duplikat (KISS, SSOT). `berkas_scan` (§18, collection `scan` singleFile) dan `lampiran` (multi) adalah dua collection berbeda pada model yang sama.

---

## §21. `surat_keluar` — Buku Agenda Surat Keluar (Kamar 3)

**Fungsi**: pencatatan **manual** korespondensi keluar (PRD F10). Kamar 3 — **tanpa FK** ke `surat_tercetak` (Kamar 2), sesuai keputusan arsitektur 3 Kamar.

| Kolom | Tipe Laravel | Null | Default | Keterangan |
|---|---|---|---|---|
| id | `id()` | ✗ | auto | PK |
| nomor_agenda | `smallInteger('nomor_agenda')->unsigned()` | ✗ | — | Auto per tahun |
| tahun_agenda | `smallInteger('tahun_agenda')` | ✗ | — | |
| nomor_surat | `string('nomor_surat', 50)` | ✗ | — | |
| kode_klasifikasi | `string('kode_klasifikasi', 20)->nullable()` | ✓ | null | |
| tanggal_surat | `date('tanggal_surat')` | ✗ | — | |
| tanggal_catat | `timestamp('tanggal_catat')->useCurrent()` | ✗ | now | |
| tujuan | `string('tujuan', 150)` | ✗ | — | |
| perihal | `string('perihal', 255)` | ✗ | — | |
| keterangan | `string('keterangan', 500)->nullable()` | ✓ | null | |
| ~~berkas_scan~~ | *(→ Media Library)* | | Scan opsional via collection `scan` (`singleFile`) (K4). Bukan kolom. |
| dicatat_oleh | `foreignId('dicatat_oleh')->constrained('users')` | ✗ | — | |
| unit_id | `foreignId('unit_id')->constrained('units')` | ✗ | — | |
| | `timestamps()` + `softDeletes()` | | | |

**Phase 2 (komentar migrasi, belum dibuat)**: `ekspedisi`, `tanggal_pengiriman`, `tanda_terima`.

**Relasi**: **n—1** `users`, `units`. **Sengaja tidak ada** relasi ke `surat_tercetak`.

**Implikasi PRD / kenapa tanpa FK ke Kamar 2**:
- **Kasus yang diatasi**: surat yang digenerate via template (Kamar 2) **tidak** otomatis masuk buku agenda keluar (Kamar 3). Keduanya beda tujuan — Kamar 2 = arsip layanan, Kamar 3 = buku agenda korespondensi dinas manual. Memaksakan FK akan mencampur dua konsep berbeda (pola OpenSID). Jika admin ingin surat generate tercatat di agenda, ia input manual — *double entry* yang disengaja.

---

## §22. Peta Relasi Keseluruhan

```
                       ┌────────── Spatie roles/permissions (§5)
                       │  (model_has_roles: users n—n roles)
                       │
   units (§1) ─┬─ n—n ─ templates (§7) ── n—1 ── kategori_surat (§6)
        │ (self)│           │
        │       │           ├─ 1—n ── template_placeholder_config (§9)
        │       │           ├─ 1—n ── template_data_tambahan_fields (§13)
        │       │           └─ n—n ── ref_syarat_surat (§10) via syarat_surat (§10.1)
        │       │
        ├─ n—n ─ pejabat (§4) via pejabat_unit (§4.1)
        │           │
   users (§2) ─ 1—1 ─ mahasiswa (§3)
        │  │
        │  ├─ 1—n ── dokumen_mahasiswa (§11) ── n—1 ── ref_syarat_surat
        │  │
        │  └─ 1—n ── permohonan_surat (§12) ─┬─ 1—n ── permohonan_data_tambahan_values (§14) ── n—1 ── template_data_tambahan_fields
        │              │ (self: parent)      ├─ 1—n ── permohonan_syarat (§15) ── n—1 ── dokumen_mahasiswa
        │              │                      └─ 1—n ── surat_tercetak (§16)
        │              │                                     │ (self: replaced_by)
        │              └── n—1 ── pejabat (approver)         └─ 1—n ── surat_penandatangan (§17) ── n—1 ── pejabat
        │
        ├─ 1—n ── surat_masuk (§18) ──── 1—n ── disposisi_surat_masuk (§19) ── n—1 ── pejabat
        │
        └─ 1—n ── surat_keluar (§21)   [Kamar 3 — TANPA FK ke surat_tercetak]

   media (Spatie Media Library) ── morphMany ── {pejabat, templates, dokumen_mahasiswa,
        surat_masuk, surat_keluar, ref_syarat_surat, settings(logo)}   [SSOT file hidup — K4]
        (lampiran surat_masuk = collection 'lampiran' → §20 tabel dihapus)

   settings (§5.1) ── dibaca oleh ── {M-GENERATE (placeholder sistem), M-VERIFIKASI,
        M-NOTIF, M-MASUK, M-KELUAR}   [config SSOT — owner M-CONFIG]
```

---

## §23. Sampel Data End-to-End (Ilustrasi Relasi)

Skenario: mahasiswa Budi mengajukan **Surat Rekomendasi Magang**, disetujui, lalu dicetak dengan 2 penandatangan (Kaprodi TTD image, Dekan TTD basah).

**§1 units**
```
{id:1, nama:"BAA", kode:"BAA"}
{id:2, nama:"Fakultas Teknik", kode:"FT", parent_id:null}
{id:3, nama:"Prodi Informatika", kode:"IF", parent_id:2}
```

**§2 users + §3 mahasiswa** (1—1)
```
users:     {id:10, nama:"Budi Setiawan", email:"budi@...", unit_id:null}
mahasiswa: {id:5, user_id:10, nim:"20210001", prodi:"Informatika"}
users:     {id:2, nama:"Dewi (Admin)", unit_id:1}   // role admin_surat via Spatie
```

**§4 pejabat + §4.1 pejabat_unit** (n—n)
```
pejabat: {id:1, nama:"Dr. Ahmad", jabatan:"Kaprodi IF", file_ttd_path:"ttd/ahmad.png"}
pejabat: {id:2, nama:"Dr. Siti",  jabatan:"Dekan FT",   file_ttd_path:null}  // TTD basah
pejabat_unit: {pejabat_id:1, unit_id:3}, {pejabat_id:2, unit_id:2}
```

**§7 templates + §9 config** (deteksi placeholder)
```
templates: {id:8, nama:"Rekomendasi Magang", kategori_id:1, tipe_pemohon:"mahasiswa",
            is_permohonan_mandiri:true, sla_hari_kerja:3, status:"aktif"}
template_unit: {template_id:8, unit_id:1}
template_placeholder_config:
  {template_id:8, placeholder_name:"nama_mahasiswa", filled_by:"sistem", tipe_input:"text"}
  {template_id:8, placeholder_name:"nama_perusahaan", filled_by:"mahasiswa", label_mahasiswa:"Nama Perusahaan"}
  {template_id:8, placeholder_name:"ttd_1", filled_by:"admin", tipe_input:"image"}
```

**§12 permohonan_surat** (Kamar 1) — Budi submit
```
{id:88, mahasiswa_id:10, template_id:8, unit_id:1, status:"pending",
 isian_form:{"nama_perusahaan":"PT Telkom"}, parent_permohonan_id:null}
```
→ Admin buka detail → `status:"diverifikasi"` → Setujui:
```
{id:88, status:"disetujui", approved_by:2, pejabat_id:1,
 catatan_approval:"Syarat lengkap", approved_at:"2026-07-19 10:00"}
```

**§15 permohonan_syarat** (Lapisan 4)
```
{permohonan_id:88, syarat_id:1(KHS), dokumen_id:31, filename:"khs_budi.pdf"}
```

**§16 surat_tercetak** (Kamar 2) — admin generate
```
{id:200, permohonan_id:88, template_id:8, unit_id:1,
 nomor_surat:"005/BAA/UNsP/VII/2026", digenerate_oleh:2,
 data_placeholder:{"nama_mahasiswa":"Budi Setiawan","nama_perusahaan":"PT Telkom",...},
 metode_pengambilan:"ambil_di_kampus",  // karena ada slot TTD basah → saran BR-10
 status:"aktif", replaced_by_id:null}
```

**§17 surat_penandatangan** (2 slot)
```
{surat_tercetak_id:200, urutan:1, pejabat_id:1, nama_snapshot:"Dr. Ahmad",
 jabatan_snapshot:"Kaprodi IF", file_ttd_path:"ttd/ahmad.png"}   // TTD image
{surat_tercetak_id:200, urutan:2, pejabat_id:2, nama_snapshot:"Dr. Siti",
 jabatan_snapshot:"Dekan FT", file_ttd_path:null}                 // TTD basah
```

**Alur relasi yang terlihat dari sampel**:
- `permohonan(88).mahasiswa_id → users(10) → mahasiswa(5)` : join untuk ambil NIM saat generate.
- `permohonan(88).approved_by → users(2)` (yang klik) **≠** `permohonan(88).pejabat_id → pejabat(1)` (yang menyetujui) : bukti proxy approval.
- `surat_tercetak(200).permohonan_id → permohonan(88)` : jejak Kamar 1 → Kamar 2 (sub-flow A). Jika generate langsung, kolom ini `null`.
- `surat_tercetak(200)` UNIQUE(nomor_surat, unit_id) : nomor "005/BAA/..." tak bisa dipakai dua kali di unit 1.
- Slot TTD 2 `file_ttd_path:null` → sistem menyarankan `metode_pengambilan:"ambil_di_kampus"` → mahasiswa lihat badge "Ambil di Kampus", bukan tombol download (BR-12).

---

## §24. Item Terbuka (Belum Diputuskan)

| # | Item | Dampak Skema |
|---|---|---|
| 1 | **Rename `label_mahasiswa` → `label`** (§9) | Rename kolom bila disepakati |
| 2 | **FK pemohon**: `users.id` vs `mahasiswa.id` (§3) | Keputusan arah FK di §11, §12 |
| 3 | **Versioning template** (perbaikan template terpakai) | Kemungkinan tabel `template_versions` — belum dirancang |

### Sudah Diputuskan

| Item | Keputusan |
|---|---|
| **K4 — Penyimpanan file** | ✅ **Spatie Media Library = SSOT**. File hidup (§4, §7, §10, §11, §15, §18, §20, §21) → relasi `media`, kolom path dilepas. File arsip immutable (§16, §17) → kolom path beku dipertahankan sebagai snapshot. Tabel `lampiran_surat_masuk` (§20) dihapus, diganti collection `lampiran`. Lihat ARCHITECTURE.md §9. |
| **Tabel `settings`** | ✅ **Ditambahkan (§5.1)** — key-value bergrup + model `HasMedia` (logo). Menutup FEATURE_MAP C1 (M-CONFIG punya tabel target) & C3 (`tahun_akademik` SSOT). Owner M-CONFIG. |
| **SLA anchor** | ✅ **Pakai `created_at`** (tidak menambah `submitted_at`). SLA = estimasi/alert (tanpa penalti), dashboard hitung status ≥ `pending` sehingga draft tak memengaruhi. Lihat UX_SPEC 1.B.1. |
| **Status history** | ✅ **Tidak ada tabel `permohonan_status_log`.** Mahasiswa lihat status tracker (badge/stepper dari kolom `status`); audit admin via Spatie ActivityLog. Lihat UX_SPEC 4.C.2. |
| **Un-approve** | ✅ **Tidak didukung Phase 1** — tanpa kolom/mekanisme. |
| **Ubah password** | ✅ Via **Fortify `update-password`** bawaan (mahasiswa & admin). Tanpa skema baru (`users.password` sudah ada). |
| **Kolom `fakultas`** | ✅ **Dihapus** (D-001) — bersama placeholder `{{fakultas}}`. Lihat §3. |
| **Fase Verifikasi Publik** | ✅ **Phase 2** (D-002) — QR tetap dibuat Phase 1 (`qr_hash` §16), halaman baca ditunda. |
| **Master Klasifikasi Surat** | ✅ **Tidak dibuat Phase 1** (D-003) — `kode_klasifikasi` tetap teks bebas di §18/§21. |
| **SLA — hari libur** | ✅ **Skip Sabtu/Minggu saja** (D-004) — tanpa master kalender libur nasional Phase 1. |
| **Hapus user** | ✅ **Tidak ada hard delete** (D-005) — nonaktifkan (`is_active=false`) satu-satunya cara di UI Phase 1. |
| **Cetak ulang nomor** | ✅ **Nomor baru wajib** (D-006) — tidak ada pengecualian pakai nomor sama; konsisten UNIQUE §16. |

> Detail alasan tiap keputusan D-001 s/d D-006 ada di `.ai-context/DECISIONS.md`.

---

*ERD ini selaras dengan `PRD.md`, `ARCHITECTURE.md`, `BACKLOG.md`, dan `.ai-context/DECISIONS.md`. Setiap perubahan skema harus diperbarui agar tetap konsisten lintas dokumen.*
