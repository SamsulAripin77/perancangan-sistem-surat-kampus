# ARCHITECTURE / SRS — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Dokumen** | Software Requirement Specification — Standar Arsitektur & Konvensi |
| **Versi** | 1.0 (Draft) |
| **Tanggal** | 19 Juli 2026 |
| **Basis** | `PRD.md`, `ERD.md`, `perancangan-murni.md`, `perancangan-kasar.md` |
| **Sifat** | Standardisasi teknis — **bukan** instruksi kode baris-per-baris. Menetapkan *bagaimana* kode ditulis agar konsisten, reusable, KISS, dan minim konflik. |

**Prinsip pemandu** (dipakai untuk memutuskan setiap standar di bawah):
1. **KISS** — Keep It Stupid Simple. Jangan buat abstraksi sebelum dibutuhkan.
2. **Single Source of Truth** — satu konsep hidup di satu tempat (satu service, satu komponen Blade, satu config).
3. **Reusable by default** — komponen UI, upload, tabel, validasi dibuat sekali, dipakai berulang.
4. **Thin controller** — controller hanya routing + delegasi; logika di Action/Service.
5. **Konsisten dengan ERD** — semua otorisasi, relasi, dan penamaan mengikuti `ERD.md`.

---

## §0. Fase Bootstrap Proyek (Setup Wajib Sebelum Fitur Pertama)

**Prinsip**: fondasi proyek — instalasi, konfigurasi, library, layout admin, dan komponen reusable — **harus selesai dan teruji lebih dulu** sebelum satu fitur bisnis pun ditulis. Ini mencegah setiap fitur memasang library/layout-nya sendiri (sumber konflik & duplikasi). Urutan berikut adalah **Definition of Ready** pengembangan.

**Tahap B0 — Inisialisasi & Konfigurasi**
1. `composer create-project laravel/laravel` (versi per §1 / §2.2), init git.
2. Set `.env`: MySQL, `APP_*`, mail (sementara), `FILESYSTEM_DISK`, disk `private` (config `filesystems.php`).
3. Config dasar: timezone `Asia/Jakarta`, locale `id`, `config/surat.php` (SSOT config aplikasi).

**Tahap B1 — Install Library Backend (composer)**
4. Install semua paket composer §2 sekaligus (Spatie Permission/MediaLibrary/ActivityLog, Yajra, PHPWord, mPDF, simple-qrcode, Fortify).
5. Publish & jalankan migration bawaan paket (Permission, MediaLibrary, ActivityLog, Fortify). Pastikan LibreOffice tersedia di server (cek `soffice`).
6. Setup Pint, Larastan, Pest (tooling §15) + konfigurasinya.

**Tahap B2 — Install Library Frontend (npm + Vite)**
7. `npm install` semua paket frontend §2 (admin-lte, bootstrap, jquery, datatables.net-bs5, select2 + theme BS5, filepond + plugin, sweetalert2, fontawesome).
8. Konfigurasi **Vite** (`vite.config.js`) + `resources/js/app.js` & `resources/css/app.css` untuk meng-import & inisialisasi semuanya (bukan CDN). Satu titik inisialisasi global untuk hook `js-*` (§11.2).

**Tahap B3 — Layout & Template Admin**
9. Bangun master layout AdminLTE: `layouts/app.blade.php` (admin), `layouts/mahasiswa.blade.php`, `layouts/auth.blade.php` (login), `layouts/guest.blade.php` (verifikasi publik).
10. Partial: sidebar menu (per role), topbar, breadcrumb, flash message, footer. Menu dirender dari config/permission (§8).

**Tahap B4 — Sistem Desain & Komponen Reusable (§11)**
11. Buat Blade Components inti: `x-ui.button`, `x-ui.card`, `x-ui.datatable`, `x-ui.badge-status`, `x-form.input`, `x-form.select` (Select2), `x-form.file` (FilePond).
12. Definisikan token tema (warna aksi, ukuran compact) di `app.css`; tetapkan konvensi class `app-*`/`js-*`.
13. Halaman contoh/showcase komponen (opsional, untuk verifikasi visual).

**Tahap B5 — Fondasi Auth, Role, Upload, Seeder**
14. Setup Fortify + view login AdminLTE; middleware role (Spatie).
15. `MediaService` + `MediaUploadController` generik (§9) + endpoint FilePond.
16. Seeder inti **produksi**: `RolePermissionSeeder`, `SettingSeeder`, `PlaceholderDefinitionSeeder` + sample master (§13). Jalankan `migrate --seed`.
17. **Smoke test**: login sebagai tiap role, render satu tabel DataTables dummy, satu form dengan Select2 + FilePond upload → pastikan fondasi jalan.

> **Gate**: fitur bisnis (F1-F13) **baru boleh** dimulai setelah B0-B5 selesai & smoke test hijau. Semua fitur mewarisi layout, komponen, dan service yang sama — tidak ada fitur yang memasang library sendiri.

---

## §1. Technology Stack

| Lapisan | Pilihan | Catatan |
|---|---|---|
| **Framework** | **Laravel 12 (default) / 13 (bersyarat)** | Pakai **12** kecuali checklist kompatibilitas §2.2 lolos untuk **13**. Lihat §2.2. |
| **Bahasa** | **PHP 8.2+** (8.3+ bila Laravel 13) | Laravel 12 butuh PHP ≥ 8.2; Laravel 13 kemungkinan ≥ 8.3 — verifikasi §2.2. |
| **Database** | **MySQL 8.0+** | Wajib (permintaan). |
| **Mode komunikasi** | **SSR — Classic MVC (Blade)** | Server-rendered penuh; tidak ada SPA. AJAX hanya untuk potongan interaktif (DataTables server-side, Select2, cek nomor duplikat, upload). |
| **API** | Minimal/internal | Endpoint JSON hanya untuk konsumsi frontend sendiri (DataTables, Select2, validasi realtime). Bukan API publik (itu Phase 3+, PRD §5.3). |
| **Aset frontend** | **npm + Vite** | Semua library JS/CSS admin (AdminLTE, Bootstrap, DataTables, Select2, FilePond, Font Awesome) di-install via **npm** dan di-bundle Vite — **bukan CDN**. Lihat §0 & §10. |
| **Web server** | Nginx/Apache + PHP-FPM | — |
| **Queue** | Database driver (awal) | Untuk email notifikasi & job berat (PRD F11). |

**Arsitektur runtime**: monolith Laravel klasik. Satu aplikasi, satu database, render Blade. Tidak ada microservice, tidak ada frontend terpisah.

---

## §2. Rekomendasi Library

Semua library berikut mapan & terawat. Kolom "Kenapa" mengaitkan ke kebutuhan PRD/ERD.

**Backend (composer)**

| Library | Fungsi | Kenapa untuk proyek ini |
|---|---|---|
| **spatie/laravel-permission** | Role & Permission (RBAC) | Diputuskan di ERD §5 & PRD F1; siap "teams per unit" Phase 2 |
| **spatie/laravel-medialibrary** | Upload file/gambar reusable — **SSOT penyimpanan file** | Permintaan eksplisit; satu mekanisme upload untuk semua fitur (§9) |
| **yajra/laravel-datatables** | DataTables server-side | Tabel padat + pagination + search + sort seperti `ciengang` (§10); server-side agar performa aman untuk arsip besar |
| **phpoffice/phpword** | Substitusi placeholder `.docx` | Inti F3/F7 — parsing & isi template Word (`perancangan-murni.md` §8) |
| **LibreOffice headless** *(binari server, bukan composer)* | Konversi **DOCX → PDF** surat utama | Fidelitas layout Word terjaga — lihat perbandingan §2.1 |
| **mpdf/mpdf** | Generate **HTML → PDF** (lembar disposisi, buku agenda, export) | Tabel + karakter Indonesia tanpa Node/Chrome — lihat §2.1 |
| **simplesoftwareio/simple-qrcode** | QR code verifikasi | PRD F7/F8 QR keaslian surat |
| **spatie/laravel-activitylog** | Audit trail | PRD §7.1 jejak audit wajib; log approve/generate/login |
| **laravel/fortify** | Backend autentikasi (headless) | Auth klasik tanpa memaksa Tailwind (beda dari Breeze) — view login pakai Blade AdminLTE (§8) |
| **laravel/pint** | Code style PSR-12 | Konsistensi format otomatis (§15) |
| **larastan/larastan** | Static analysis | Cegah bug tipe lebih awal (§15) |
| **pestphp/pest** *(atau PHPUnit)* | Testing | Feature/integration test per fitur (§14). PHPUnit tetap valid bila tim lebih familiar. |

**Frontend (npm — di-bundle Vite, bukan CDN; lihat §0)**

| Library | Fungsi | Kenapa untuk proyek ini |
|---|---|---|
| **admin-lte** (+ **bootstrap**) | Theme admin compact | Gaya compact seperti `ciengang`, versi terbaru; di-install via npm & di-import di Vite (§0, §10, §16-K2) |
| **datatables.net-bs5** (+ **jquery**) | Render tabel di klien | Dipasang bersama Yajra; styling Bootstrap 5 |
| **select2** (+ **select2-bootstrap-5-theme**) | Dropdown pencarian | Cari mahasiswa/template (PRD F7). Memakai jQuery yang sudah ada untuk DataTables (§10) |
| **filepond** (+ plugin preview & validasi) | UI upload file | Paling kompatibel dengan pola Media Library (temporary upload → attach) — lihat §9 |
| **sweetalert2** | Notifikasi (sukses/error) & konfirmasi | **Standar wajib** semua notifikasi & dialog konfirmasi — lihat §11.4 |
| **@fortawesome/fontawesome-free** | Ikon | Konsisten referensi |

> **Catatan**: hindari menambah library di luar daftar ini tanpa alasan kuat — setiap dependensi menambah beban maintenance (prinsip KISS).

---

## §2.1. Generator PDF

**Dua kebutuhan berbeda:**

1. **Surat utama: DOCX → PDF.** Template `.docx` diisi PHPWord → hasil `.docx`. Konversi ke PDF dengan layout utuh **hanya andal via LibreOffice** (`--convert-to pdf`). Tidak ada library PHP murni yang layak (PHPWord PDF-writer hasilnya berantakan). DomPDF/mPDF/Spatie PDF semua render HTML, **bukan** `.docx` → tidak relevan untuk surat.
2. **HTML → PDF** (lembar disposisi, buku agenda, export): pakai **mPDF** (PHP murni, tabel + karakter Indonesia baik, tanpa infra tambahan).
   - DomPDF ❌ lemah tabel/Unicode. Spatie Laravel PDF ❌ butuh Node+Chromium (berat) & tetap tak bisa `.docx`.

**PDF = opsional & graceful** (pola OpenSID, terbukti di `ciengang` `Surat_model.php:1085`):
- Output **dijamin = DOCX**. PDF hanya dibuat bila setting `libreoffice_path` (ERD §5.1) terisi **dan** konversi sukses; kalau tidak → kirim DOCX. Sistem tidak pernah rusak karena LibreOffice absen.
- Phase 1 boleh **DOCX-only** dulu. *(Catatan: PRD F7 "PDF+DOCX" perlu direvisi jadi "DOCX wajib, PDF opsional" — lihat §16.)*

**Setup server (VPS):**
```bash
apt install -y libreoffice-writer --no-install-recommends
apt install -y fonts-liberation ttf-mscorefonts-installer   # font kop surat
```
- Gotcha: beri `HOME` writable untuk `www-data` (profil LibreOffice); pastikan `exec()` tidak diblok (shared hosting biasanya blok → DOCX-only).

**Di Laravel:** `DocxToPdfConverter` via `Process`, gated `Settings::get('libreoffice_path')`, dengan `HOME` + `-env:UserInstallation` unik (aman paralel), **dijalankan di queue** (spin-up ~1-2 dtk). Return `null` bila gagal → fallback DOCX.

---

## §2.2. Kompatibilitas Laravel 13 (Checklist Wajib Sebelum Memilih)

Laravel 13 boleh dipakai **hanya jika SEMUA** paket di §2 sudah merilis versi kompatibel. Paket Spatie & Laravel first-party biasanya cepat menyusul; yang **berisiko lag** adalah paket komunitas. Verifikasi checklist ini (cek `composer.json` masing-masing untuk constraint `illuminate/*` atau tag rilis):

| Paket | Risiko lag | Status (isi saat evaluasi) |
|---|---|---|
| spatie/laravel-permission | Rendah | ☐ |
| spatie/laravel-medialibrary | Rendah | ☐ |
| spatie/laravel-activitylog | Rendah | ☐ |
| laravel/fortify | Rendah (first-party) | ☐ |
| **yajra/laravel-datatables** | **Sedang-tinggi** (sering lag) | ☐ |
| **simplesoftwareio/simple-qrcode** | **Sedang** (paket kecil) | ☐ |
| phpoffice/phpword, mpdf/mpdf | Rendah (tak terikat versi Laravel) | ✅ (independen) |
| larastan/larastan, pestphp/pest, laravel/pint | Rendah | ☐ |

**Aturan keputusan**:
- **Semua ☐ → ✅** (ada rilis mendukung Laravel 13 + PHP target) → boleh **Laravel 13**.
- **Ada satu saja belum** (terutama Yajra atau simple-qrcode) → **tetap Laravel 12** sampai paket menyusul. Laravel 12 stabil dan seluruh paket sudah mendukung — tidak ada risiko delivery.

> Rekomendasi jujur: **default Laravel 12** untuk memulai (nol risiko kompatibilitas), naikkan ke 13 saat checklist lolos. Jangan menahan mulai proyek hanya demi versi terbaru.

---

## §3. Struktur Folder & Layering

Pola: **Controller tipis → Form Request (validasi) → Action/Service (logika) → Model (data)**. Setiap lapisan punya satu tanggung jawab.

```
app/
├── Actions/                  # Logika bisnis satu-tujuan (single responsibility)
│   ├── Template/
│   │   ├── UploadTemplateAction.php
│   │   └── ScanPlaceholderAction.php
│   ├── Permohonan/
│   │   ├── SubmitPermohonanAction.php
│   │   └── ApprovePermohonanAction.php
│   └── Surat/
│       └── GenerateSuratAction.php
├── Services/                 # Logika lintas-fitur yang dipakai banyak Action
│   ├── TemplateSubstitutionService.php   # SSOT substitusi .docx (PRD §8)
│   ├── NomorSuratService.php             # SSOT saran & validasi nomor (PRD 4.5)
│   ├── MediaService.php                  # SSOT upload (§9)
│   └── PdfService.php                    # SSOT konversi PDF
├── Http/
│   ├── Controllers/          # TIPIS — hanya delegasi
│   │   ├── Admin/
│   │   └── Mahasiswa/
│   ├── Requests/             # Form Request — SSOT validasi
│   └── Middleware/
├── Models/                   # Eloquent + relasi (mirror ERD)
├── Policies/                 # Otorisasi per-model (§8)
├── View/Components/          # Komponen Blade reusable (§11)
└── Enums/                    # Konstanta status (PHP 8.1 enum) — SSOT nilai status

database/
├── migrations/               # 1 tabel = 1 migration (mirror ERD)
├── seeders/                  # Master data + sample (§13)
└── factories/                # Untuk test (§14)

resources/
├── views/
│   ├── layouts/              # Master layout AdminLTE
│   ├── components/           # Blade components (x-ui.*, x-form.*)
│   ├── admin/                # Halaman sisi admin
│   └── mahasiswa/            # Halaman sisi mahasiswa
├── css/app.css              # Kustom di atas AdminLTE (§11)
└── js/app.js                # Inisialisasi DataTables, Select2, upload (§11)
```

**Aturan layering**:
- Controller **tidak boleh** memuat query kompleks atau logika bisnis → lempar ke Action/Service.
- Action memanggil Service bila butuh kapabilitas reusable (upload, substitusi, nomor).
- Model **tidak** memuat logika bisnis berat; hanya relasi, scope, accessor sederhana.

---

## §4. Alur Satu Request (Contoh Referensi)

Contoh: mahasiswa submit permohonan (PRD F5).

```
Route (web.php)
  → PermohonanController@store          (tipis)
      → SubmitPermohonanRequest          (validasi — §7)
      → SubmitPermohonanAction::execute  (logika — §6)
          → MediaService (simpan file syarat — §9)
          → Permohonan::create + relasi (isian_form JSON, dll — ERD §12)
          → event/email (queue — F11)
      → redirect()->back()->with('success', ...)   (SSR)
```

Satu request = satu jalur jelas. Tidak ada logika tersebar di view atau model.

---

## §5. Konvensi Controller (Tipis)

- **Resource controller** untuk CRUD master (`php artisan make:controller X --resource`).
- Method maksimum ~10 baris: validasi (via Form Request) → panggil Action → return response.
- Tidak ada `DB::` atau query builder panjang di controller.

**Contoh singkat**:
```php
public function store(SubmitPermohonanRequest $request, SubmitPermohonanAction $action)
{
    $permohonan = $action->execute($request->validated(), $request->user());

    return redirect()
        ->route('mahasiswa.permohonan.show', $permohonan)
        ->with('success', 'Permohonan berhasil diajukan.');
}
```

---

## §6. Pola Action / Service

**Action** = satu operasi bisnis (verb + noun). **Service** = kapabilitas reusable lintas fitur.

**Kapan Action, kapan Service?**
- Dipakai di **satu** alur fitur → **Action**.
- Dipakai **banyak** fitur (upload, substitusi docx, nomor surat) → **Service** (Single Source of Truth).

**Contoh Action singkat**:
```php
class ApprovePermohonanAction
{
    public function execute(Permohonan $permohonan, array $data, User $admin): void
    {
        // proxy approval — ERD §12: approved_by (admin) ≠ pejabat_id (pemberi setuju)
        $permohonan->update([
            'status'          => PermohonanStatus::DISETUJUI->value,
            'approved_by'     => $admin->id,
            'pejabat_id'      => $data['pejabat_id'],
            'catatan_approval'=> $data['catatan'],
            'approved_at'     => now(),
        ]);

        activity()->performedOn($permohonan)->log('approve'); // audit — §2
    }
}
```

**Contoh Service reusable (SSOT)**: `TemplateSubstitutionService` dipakai di 3 konteks (preview F3, generate sub-flow A & B F7) — sesuai `perancangan-murni.md` §8. Jangan menduplikasi logika substitusi di tempat lain.

---

## §7. Standar Validasi (Form Request)

- **Semua** input tervalidasi lewat **Form Request** (bukan di controller). SSOT aturan validasi per aksi.
- Method `authorize()` di Form Request boleh dipakai untuk cek izin ringan; otorisasi berbasis data pakai Policy (§8).
- Pesan error dalam Bahasa Indonesia via `lang/id/validation.php` — SSOT sama dengan seluruh teks statis FE (§11.5), bukan string literal di `rules()`/`messages()`.

**Contoh**:
```php
class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Template::class); // Policy — §8
    }

    public function rules(): array
    {
        return [
            'nama'          => ['required', 'string', 'max:150'],
            'kategori_id'   => ['required', 'exists:kategori_surat,id'],
            'tipe_pemohon'  => ['required', Rule::in(['mahasiswa', 'umum'])], // ERD §7
            'file'          => ['required', 'file', 'mimes:docx', 'max:10240'], // PRD F3: 10MB
            'sla_hari_kerja'=> ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

> **SSOT nilai enum**: nilai seperti `mahasiswa`/`umum`, status permohonan, dsb didefinisikan di **PHP Enum** (`app/Enums`) dan dirujuk di Form Request, Model cast, dan Blade — bukan string literal tersebar.

---

## §8. Autentikasi & Otorisasi (Konsisten ERD)

### 8.1 Autentikasi
- **Laravel Fortify** menangani login/logout/reset password (backend), view pakai Blade AdminLTE.
- Login **email + password** (PRD F1). User `is_active = false` ditolak (cek di pipeline Fortify).
- Model auth = `User` (ERD §2). Profil mahasiswa via relasi 1—1 `User→Mahasiswa` (ERD §3).

### 8.2 Otorisasi — Dua Lapis
1. **Role & Permission (Spatie)** — otorisasi kasar per menu/aksi. ERD §5.
   ```php
   $user->assignRole('admin_surat');
   $user->can('template.create'); // permission
   ```
2. **Policy** — otorisasi halus berbasis data (kepemilikan record).
   ```php
   // PermohonanPolicy — mahasiswa hanya lihat miliknya (PRD §3)
   public function view(User $user, Permohonan $p): bool
   {
       return $user->hasRole('admin_surat')
           || $p->mahasiswa_id === $user->id;
   }
   ```

**Aturan konsistensi ERD**:
- Role Phase 1: `super_admin`, `admin_surat`, `mahasiswa` (ERD §5).
- Aksi khusus Super Admin (kelola user, kamus placeholder — PRD F13) dilindungi permission + Policy.
- Middleware route: `->middleware(['auth', 'role:admin_surat|super_admin'])`.

---

## §9. Upload File Reusable (Jawaban Spesifik)

**Pertanyaan**: "bisakah satu controller reusable dengan Spatie Media Library dipakai semua fitur?"

**Jawaban & rekomendasi**: Ya, tapi cara terbaik **bukan** satu controller besar, melainkan:

**Pola yang direkomendasikan (SSOT upload)**:
1. **Trait `HasMedia`** (dari Spatie Media Library) dipasang di model yang butuh file: `Template`, `DokumenMahasiswa`, `Pejabat` (file TTD), `SuratMasuk`, dll.
2. **`MediaService`** — satu service pusat untuk aturan umum: validasi tipe/ukuran, penamaan aman, penyimpanan disk (private vs public), pembuatan konversi thumbnail.
3. **Satu `MediaUploadController` tipis** khusus untuk upload **AJAX generik** (dipakai komponen upload di frontend) — memanggil `MediaService`. Ini yang "reusable controller" yang dimaksud.
4. **UI upload = FilePond** (frontend). Dipilih karena **paling kompatibel dengan pola Media Library**: FilePond memakai model *temporary upload* (server API `process`/`revert`) yang cocok dengan alur "unggah dulu ke disk sementara → attach ke `media` saat form disubmit". Dropzone bisa juga, tapi FilePond lebih pas untuk pola attach-on-submit + preview + validasi klien. Komponen `x-form.file` (§0 Tahap B4) membungkus FilePond + hook `js-upload` → endpoint `MediaUploadController`.

```php
// Model — deklaratif, tiap fitur cukup daftarkan "collection"
class Template extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('docx')
             ->singleFile()                       // satu template = satu file
             ->useDisk('private');                // aman, tidak di public
    }
}
```

```php
// MediaService — SSOT aturan upload
class MediaService
{
    public function attach(HasMedia $model, UploadedFile $file, string $collection): Media
    {
        return $model->addMedia($file)
            ->sanitizingFileName(fn ($name) => Str::slug($name)) // nama aman (anti guessing)
            ->toMediaCollection($collection);
    }
}
```

**Kenapa begini (bukan satu controller monolit)**:
- **Reusable & DRY**: aturan upload hidup di satu `MediaService`; setiap fitur cukup deklarasi `registerMediaCollections()`.
- **Keamanan** (PRD §7.4): file TTD & syarat masuk disk **private**, akses via controller ber-Policy — bukan URL publik.
- **Performa**: konversi gambar (thumbnail) dijalankan lewat **queue** (`->withResponsiveImages()` hanya bila perlu; hindari untuk PDF/docx). Batasi ukuran di Form Request. Jangan generate turunan gambar yang tidak dipakai.

**Keputusan penyimpanan (K4 — final)**: **Spatie Media Library (tabel `media` polymorphic) adalah Single Source of Truth penyimpanan file** untuk semua fitur. `MediaService` + satu controller upload AJAX generik **dipakai bersama** — tidak ada logika upload duplikat di fitur manapun. Aturan penyimpanan nilai di DB:

| Konteks | Cara simpan | Alasan |
|---|---|---|
| **File hidup / bisa berubah** (`dokumen_mahasiswa`, `lampiran_surat_masuk`, `surat_masuk.berkas_scan`, `template .docx`, `pejabat` TTD master) | Via **Media Library** (relasi `media`) — kolom path eksplisit di ERD **dilepas**, diganti collection | Satu tempat, konsisten, dukung konversi/thumbnail & manajemen disk |
| **File arsip / immutable snapshot** (`surat_tercetak.file_pdf_path`, `file_docx_path`, `surat_penandatangan.file_ttd_path`) | **Salin ke path beku + simpan string di kolom** (tetap lewat `MediaService` saat generate) | ERD §16 arsip immutable — nilai tidak boleh berubah/hilang walau record media dihapus. Snapshot melindungi keabsahan arsip. |

Prinsipnya: **upload selalu lewat service+controller Spatie yang sama**; hanya *cara mencatat nilai* yang dibedakan — relasi `media` untuk file hidup, string beku untuk arsip. Ini pilihan terbaik untuk arsitektur & performa jangka panjang (konsistensi + integritas arsip). Lihat §16-K4.

---

## §10. Stack UI / Frontend

Mengikuti gaya `ciengang` (AdminLTE + Bootstrap) & `perancangan-murni.md` §8, versi terbaru. **Semua aset di-install via npm & di-bundle Vite** (§0 Tahap B2) — bukan CDN.

| Komponen | Pilihan | Alasan |
|---|---|---|
| **Admin theme** | **AdminLTE 4** (npm `admin-lte`, latest) | Versi **terbaru** (Bootstrap 5); di-import di Vite, diintegrasikan di master layout `layouts/app.blade.php` — tanpa wrapper package (KISS). Lihat §16-K2 |
| **CSS framework** | **Bootstrap 5.3** (npm) | Utility lengkap, grid stabil |
| **Tabel** | **DataTables 2.x** (`datatables.net-bs5`, server-side via Yajra) | Compact, pagination/search/sort; jQuery sebagai dependency DataTables |
| **Dropdown pencarian** | **Select2** (+ `select2-bootstrap-5-theme`) | Cari mahasiswa/template (PRD F7 "Cari Mahasiswa"); memakai jQuery yang sudah dimuat untuk DataTables (satu ekosistem, matang) |
| **Upload UI** | **FilePond** (npm) | Kompatibel pola Media Library (temporary upload → attach), preview + validasi klien (§9) |
| **Ikon** | **Font Awesome** (npm) | Konsisten referensi |
| **Interaktivitas** | jQuery (untuk DataTables & Select2) + JS modul via hook `js-*` | Classic MVC SSR; tanpa SPA |

**Catatan jQuery**: DataTables & Select2 memakai jQuery — jadi jQuery **dimuat sekali** (via Vite) dan dipakai bersama keduanya (satu ekosistem konsisten, matang, dan sama seperti `ciengang`). Behavior kustom lain tetap ditulis rapi via hook `js-*` (§11.2).

**Catatan performa DataTables**: gunakan **server-side processing** (Yajra) untuk tabel yang bisa besar (Arsip Surat F8, Permohonan F6, Surat Masuk/Keluar). Untuk tabel master kecil (kategori, unit) boleh client-side.

---

## §11. Design System & Komponen Reusable

Tujuan: UI **compact** (tabel & tombol kecil, informasi padat — sesuai screenshot referensi) yang **konsisten** dan **reusable**.

### 11.1 Komponen Blade (SSOT tampilan)
Bungkus elemen berulang jadi Blade Component agar tidak menyalin HTML:

```
resources/views/components/
├── ui/
│   ├── button.blade.php        → <x-ui.button variant="primary" size="sm">
│   ├── card.blade.php          → <x-ui.card title="...">
│   ├── datatable.blade.php     → <x-ui.datatable :columns="..." ajax="...">
│   └── badge-status.blade.php  → <x-ui.badge-status :status="$p->status">
└── form/
    ├── input.blade.php         → <x-form.input name="nama" label="Nama">
    ├── select.blade.php        → <x-form.select ... > (auto Select2)
    └── file.blade.php          → <x-form.file name="berkas"> (hook upload)
```

**Contoh komponen tombol compact** (K3 — pendekatan terpilih: **satu komponen Blade + class bermakna + hook behavior via `data-*`/`js-*`**, paling simpel & fleksibel):
```blade
{{-- x-ui.button — semua tombol lewat sini agar seragam, kecil, & bisa dipasangi behavior --}}
@props(['variant' => 'secondary', 'size' => 'sm', 'icon' => null, 'type' => 'button'])
<button type="{{ $type }}"
        {{ $attributes->merge(['class' => "btn btn-$variant btn-$size app-btn"]) }}>
    @if($icon)<i class="fas fa-{{ $icon }}"></i>@endif
    {{ $slot }}
</button>

{{-- Pemakaian — behavior menempel lewat atribut, bukan varian komponen baru: --}}
<x-ui.button variant="danger" icon="trash" class="js-confirm-delete" data-url="{{ ... }}">Hapus</x-ui.button>
```
**Kenapa pola ini (K3)**: satu komponen menutup semua kebutuhan tombol; ukuran default `btn-sm` (compact, Bootstrap 5); behavior (konfirmasi hapus, submit AJAX) ditambahkan lewat `js-*` + `data-*` **tanpa** membuat komponen turunan — simpel, fleksibel, dan mendukung interaksi JS.

### 11.2 Konvensi Class CSS/JS (Wajib — untuk selector yang bermakna)
Setiap elemen esensial (form, input, tombol, tabel) **wajib** punya class bermakna:

| Prefix | Fungsi | Contoh |
|---|---|---|
| `app-*` | Penanda komponen desain (styling) | `app-btn`, `app-table`, `app-form` |
| `js-*` | **Hook JavaScript** (jangan dipakai untuk styling) | `js-datatable`, `js-select2`, `js-upload`, `js-confirm`, `js-flash` (SweetAlert, §11.4) |
| `data-*` | Konfigurasi via atribut | `data-url`, `data-confirm`, `data-max-size` |

**Aturan**: JS hanya menyeleksi `.js-*`; CSS hanya menyeleksi `.app-*`/Bootstrap. Ini mencegah styling & behaviour saling merusak saat refactor.

```blade
<table class="table table-bordered table-striped table-hover app-table js-datatable"
       data-url="{{ route('admin.arsip.data') }}">
```
```js
// app.js — inisialisasi global, satu tempat
$('.js-datatable').each(function () {
    $(this).DataTable({ serverSide: true, ajax: $(this).data('url'), pageLength: 20 });
});
$('.js-select2').select2();
```

### 11.3 Gaya Compact
- Tombol: `btn-sm` (atau ukuran lebih kecil via utility custom) — kecil, padat.
- Tabel: `table-sm` + `table-bordered table-striped table-hover`, header `table-light` (Bootstrap 5).
- Warna aksi konsisten (sesuai `perancangan-murni.md` §8): hijau/olive = tambah/positif, kuning = edit, merah/maroon = hapus/tolak, aqua/biru = info/aksi utama.
- Definisikan variabel warna & spacing di `resources/css/app.css` (SSOT tema) — jangan inline style.

### 11.4 Notifikasi & Konfirmasi (SweetAlert — WAJIB)

**Aturan wajib**: semua **notifikasi** (sukses/error) dan **dialog konfirmasi** memakai **SweetAlert2** — tidak boleh `alert()` bawaan browser, tidak boleh alert Bootstrap statis untuk feedback aksi. Satu inisialisasi global (SSOT) di `resources/js/app.js`.

**1. Notifikasi sukses/error (SSR → flash → SweetAlert)**
Alur classic MVC: controller set **flash session** (`->with('success', ...)` / `->with('error', ...)`), master layout mengekspornya, JS menampilkan **SweetAlert toast** saat load:
```blade
{{-- layouts/app.blade.php --}}
@if(session('success')) <span class="js-flash" data-type="success" data-msg="{{ session('success') }}"></span> @endif
@if(session('error'))   <span class="js-flash" data-type="error"   data-msg="{{ session('error') }}"></span> @endif
```
```js
// app.js — SSOT feedback
document.querySelectorAll('.js-flash').forEach(el => {
    Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false,
        icon: el.dataset.type, title: el.dataset.msg });
});
```

**2. Konfirmasi aksi destruktif (hapus/tolak/batalkan)**
Elemen ber-hook `js-confirm` (mengganti `js-confirm-delete` di §11.2) → intercept → SweetAlert confirm → lanjut submit/redirect saat dikonfirmasi:
```blade
<x-ui.button variant="danger" icon="trash" class="js-confirm"
    data-confirm="Hapus data ini? Tindakan tidak bisa dibatalkan."
    data-url="{{ route('admin.unit.destroy', $unit) }}">Hapus</x-ui.button>
```
```js
document.querySelectorAll('.js-confirm').forEach(el => el.addEventListener('click', async e => {
    e.preventDefault();
    const r = await Swal.fire({ icon: 'warning', text: el.dataset.confirm,
        showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal' });
    if (r.isConfirmed) { /* submit form tersembunyi / follow data-url */ }
}));
```

**Konsistensi**: warna tombol SweetAlert mengikuti tema aksi §11.3 (merah untuk destruktif). Semua modul memakai hook `js-flash`/`js-confirm` yang sama — tidak ada implementasi feedback per fitur.

### 11.5 Lokalisasi Teks Statis FE — `lang/id` (SSOT WAJIB)

**Aturan wajib**: **semua** teks statis yang tampil di FE — label field, judul kolom tabel, teks tombol, judul halaman/breadcrumb, placeholder input, helper text, pesan empty-state, pesan konfirmasi (§11.4), pesan flash sukses/error, dan pesan validasi (§7) — **tidak boleh** hardcode sebagai string literal di Blade/Controller/JS. Semua **wajib** diambil dari file bahasa Laravel `lang/id/*.php` via helper `__('group.key')` (atau `@lang(...)` di Blade).

**Kenapa (prinsip #2 — Single Source of Truth)**: satu label hidup di satu tempat; ganti istilah ("Simpan" → "Rekam") cukup satu baris, bukan cari-ganti lintas puluhan view; menyiapkan lokalisasi ke locale lain tanpa refactor besar.

**Struktur file** (per domain — bukan satu file raksasa, agar tetap mudah dipelihara sesuai KISS):

```
lang/id/
├── common.php       # label & tombol generik lintas fitur: simpan, batal, hapus, edit,
│                     #   tambah, cari, filter, reset, unduh, tidak_ada_data, dll.
├── table.php        # header kolom tabel generik: no, aksi, status, tanggal_dibuat, dll.
│                     #   — dipakai x-ui.datatable (§11.1) & bar filter (§17)
├── validation.php    # custom attribute names & pesan (§7) — lengkapi file bawaan Laravel
└── {modul}.php       # per fitur/domain: template.php, permohonan.php, arsip.php,
                       #   surat_masuk.php, dst. — mengikuti modul FEATURE_MAP §1 (M-XXX)
```

**Konvensi key**: `snake_case`, deskriptif per konteks, nested array untuk mengelompokkan (`permohonan.status.pending`, bukan singkatan ambigu).

**Pemakaian**:
```blade
{{-- Blade --}}
<x-ui.button variant="primary">{{ __('common.simpan') }}</x-ui.button>
<th>{{ __('table.status') }}</th>
```
```blade
{{-- Teks yang harus tersedia di sisi JS (mis. SweetAlert data-confirm §11.4) tetap
     dirender dari Blade — JS tidak menyimpan string Bahasa Indonesia sendiri --}}
<x-ui.button class="js-confirm" data-confirm="{{ __('permohonan.confirm_hapus') }}">
    {{ __('common.hapus') }}
</x-ui.button>
```

**Larangan tegas** (tambahan anti-pattern §15): string UI Bahasa Indonesia hardcode di Blade/Controller/JS — mis. `>Simpan<` atau `'Data berhasil disimpan'` literal di luar `lang/id/*.php`. Header/placeholder kolom `x-ui.datatable`/`x-ui.filter` (§11.1, §17) juga wajib lewat lang, bukan string di konfigurasi kolom Yajra.

**Cakupan**: berlaku sejak Fase Bootstrap — `lang/id/common.php` & `table.php` disiapkan di §0 Tahap B4 bersamaan komponen inti (lihat BACKLOG M0-T10). Setiap fitur baru (F1-F13) wajib patuh sejak awal; audit menyeluruh untuk menutup celah dilakukan di hardening (BACKLOG M10-T5).

---

## §12. Konvensi Penamaan

| Objek | Konvensi | Contoh |
|---|---|---|
| **Tabel DB** | snake_case jamak | `permohonan_surat`, `surat_tercetak` |
| **Kolom** | snake_case | `tipe_pemohon`, `approved_at` |
| **Model** | PascalCase tunggal | `PermohonanSurat`, `SuratTercetak` |
| **Controller** | PascalCase + `Controller` | `PermohonanController` |
| **Action** | Verb + Noun + `Action` | `GenerateSuratAction` |
| **Service** | Noun + `Service` | `NomorSuratService` |
| **Form Request** | Verb + Noun + `Request` | `StoreTemplateRequest` |
| **Policy** | Model + `Policy` | `PermohonanPolicy` |
| **Blade view** | kebab/snake, per fitur | `admin/template/index.blade.php` |
| **Route name** | dot notation | `admin.permohonan.approve` |
| **Blade component** | dot namespace | `x-ui.button`, `x-form.input` |
| **Enum** | PascalCase | `PermohonanStatus`, `TipePemohon` |
| **CSS/JS hook** | `app-*` / `js-*` | `app-btn`, `js-datatable` |
| **Lang key** (§11.5) | `snake_case`, nested per grup | `common.simpan`, `table.status`, `permohonan.status.pending` |

---

## §13. Seeders & Master Data

Semua master data & role/permission **wajib** punya seeder (permintaan). Struktur:

```
DatabaseSeeder
├── RolePermissionSeeder     # SSOT role & permission (Spatie) — WAJIB
├── SettingSeeder            # konfigurasi sistem: identitas kampus, tahun akademik, SMTP, format nomor (ERD §5.1) — WAJIB
├── UserSeeder               # akun awal: 1 super admin, ≥1 admin surat, sample mahasiswa
├── UnitSeeder               # BAA, Fakultas, Prodi (ERD §1) — sample
├── PejabatSeeder            # sample pejabat + pejabat_unit (ERD §4) — sample
├── KategoriSuratSeeder      # Layanan Mahasiswa, Adm Internal, SK, dll (ERD §6)
├── PlaceholderDefinitionSeeder  # kamus placeholder default (ERD §8, murni §4.4) — WAJIB
├── RefSyaratSuratSeeder     # syarat umum (Fotokopi KHS, KTM, dll) — sample
└── TemplateSeeder           # 1-2 template contoh (opsional, untuk demo/test)
```

**Aturan**:
- **Data operasional inti** (role, permission, kamus placeholder, **settings**) = seeder **produksi** (dijalankan saat instalasi, bukan sekadar dummy).
- **Data sample** (unit, kategori, pejabat, mahasiswa contoh) = untuk demo/dev; ditandai jelas agar bisa dibersihkan sebelum go-live.
- Role & permission harus cocok dengan yang dicek di Policy/Middleware (§8) — SSOT di `RolePermissionSeeder`.

**Contoh isi `PlaceholderDefinitionSeeder`** (mirror ERD §8): `nama_mahasiswa`, `nim`, `prodi` (profil); `nama_universitas`, `kode_universitas`, `logo_kampus`, `tahun_akademik` (sistem); `tanggal_surat`, `bulan_surat`, `tahun_surat` (waktu); `nomor_surat` (counter). *(`fakultas` dihapus — keputusan D-001, `docs/decisions/DECISIONS.md`.)*

---

## §14. Standar Testing

**Pendekatan**: **Feature Test = Integration Test per fitur** (permintaan). Setiap fitur PRD (F1-F13) punya minimal satu feature test yang menjalankan alur end-to-end lewat HTTP.

| Aspek | Standar |
|---|---|
| **Jenis utama** | Feature test (HTTP kernel penuh: route → controller → action → DB) |
| **Unit test** | Hanya untuk logika murni kompleks (mis. `NomorSuratService` regex increment, hitung SLA) |
| **Database** | `RefreshDatabase` + MySQL test (atau SQLite in-memory bila kompatibel) |
| **Data** | Factory per model (`database/factories`) |
| **Cakupan minimal** | Happy path + minimal 1 skenario gagal/otorisasi per fitur |
| **Runner** | Pest (disarankan) atau PHPUnit |

**Contoh feature test (integration) untuk F6 Approval**:
```php
it('admin menyetujui permohonan dan status berubah', function () {
    $admin = User::factory()->create()->assignRole('admin_surat');
    $permohonan = Permohonan::factory()->pending()->create();
    $pejabat = Pejabat::factory()->create();

    actingAs($admin)
        ->post(route('admin.permohonan.approve', $permohonan), [
            'pejabat_id' => $pejabat->id,
            'catatan'    => 'Syarat lengkap',
        ])
        ->assertRedirect();

    expect($permohonan->fresh())
        ->status->toBe(PermohonanStatus::DISETUJUI->value)
        ->approved_by->toBe($admin->id)   // proxy approval — ERD §12
        ->pejabat_id->toBe($pejabat->id);
});
```

**Aturan penulisan test**:
- Nama test deskriptif dalam Bahasa Indonesia (perilaku bisnis, bukan teknis).
- Uji **aturan bisnis** dari BRD (`brd-alur-template-surat.md` BR-01..BR-12), mis. BR-12 "status disetujui belum buka download".
- Test otorisasi: mahasiswa tidak bisa akses permohonan orang lain (PRD §3).

---

## §15. Kualitas Kode & Tooling

| Tool | Fungsi | Aturan |
|---|---|---|
| **Laravel Pint** | Auto-format PSR-12 | Jalan sebelum commit (pre-commit hook opsional) |
| **Larastan** (PHPStan) | Static analysis | Minimal level 5; naikkan bertahap |
| **Enum PHP** | SSOT nilai status/tipe | Semua nilai `string` di ERD yang berupa daftar → Enum |
| **Config, bukan hardcode** | Nilai kampus, path storage, dll | `config/surat.php` — SSOT konfigurasi |

**Anti-pattern yang dilarang** (menjaga kode pendek & minim konflik):
- Logika bisnis di controller atau Blade.
- Query mentah `DB::` tersebar (pakai Eloquent + relasi ERD).
- String status literal (`'disetujui'`) tersebar — pakai Enum.
- Menyalin HTML tabel/form (pakai Blade Component §11).
- Menduplikasi logika upload/substitusi/nomor (pakai Service §6, §9).
- Teks statis FE (label/tombol/heading/pesan) hardcode di Blade/Controller/JS — pakai `lang/id/*.php` via `__()` (§11.5).

---

## §16. Konflik dengan Dokumen Lain & Rekomendasi Update

Status konflik setelah keputusan pemangku kepentingan:

| # | Konflik | Dokumen | Keputusan (Final) |
|---|---|---|---|
| K1 | `perancangan-murni.md` §8 menyebut **Laravel 10/11** & **PHP 8.1+** | murni §8 | ✅ **RESOLVED — Laravel 12 + PHP 8.2+**. `perancangan-murni.md` §8 perlu diperbarui. |
| K2 | `perancangan-murni.md` §8 menyebut **AdminLTE (Bootstrap 3)** | murni §8 | ✅ **RESOLVED — AdminLTE 4 + Bootstrap 5.3 (terbaru)**, di-install via npm & di-bundle Vite, diintegrasikan di master layout (tanpa wrapper package). Dropdown pakai **Select2** (+ theme BS5). `perancangan-murni.md` §8 perlu diperbarui. |
| K3 | Class contoh `btn btn-flat btn-sm` (BS3) | murni §8 | ✅ **RESOLVED — pendekatan terpilih**: satu komponen `x-ui.button` (default `btn-sm`, Bootstrap 5) + behavior lewat hook `js-*`/`data-*` (§11.1). Paling simpel, fleksibel, mendukung behavior. |
| K4 | Kolom file eksplisit vs tabel `media` Spatie | ERD §4/§11/§18 | ✅ **RESOLVED — Spatie Media Library = SSOT penyimpanan**, `MediaService` + controller upload dipakai bersama semua fitur. File hidup → relasi `media` (kolom path dilepas); file arsip immutable → path beku disimpan sebagai string (snapshot). Detail di §9. **ERD perlu diperbarui** (lihat catatan bawah). |
| K5 | ERD §3 FK pemohon = `users.id`, tapi ada tabel `mahasiswa` terpisah | ERD §3, §11, §12 | Konsisten dgn auth Laravel (`auth()->id()` = users.id). Pertahankan; join ke `mahasiswa` untuk NIM/prodi. Sudah dicatat di ERD §24. |
| K6 | Auth: `perancangan-*` tidak menyebut Fortify/Breeze | semua | **Rekomendasi Fortify** (headless) agar bebas dari Tailwind (Breeze default) dan tetap Blade AdminLTE. |
| K7 | PRD F7 menulis output **"PDF + DOCX"** seolah dua-duanya wajib | PRD F7 | **Revisi jadi "DOCX wajib, PDF opsional"** (graceful, pola OpenSID §2.1) — PDF hanya bila `libreoffice_path` terisi & konversi sukses. QR/watermark yang mengandalkan PDF jadi kondisional. |

> **Tindak lanjut update dokumen** (agar seluruh perancangan konsisten):
> - `perancangan-murni.md` §8 → Laravel 12 (default; 13 bersyarat §2.2), PHP 8.2+, AdminLTE 4/Bootstrap 5.3, Select2, FilePond, aset via npm/Vite (K1-K3). ✅ *sudah diperbarui.*
> - `ERD.md` → file **hidup** via Media Library (kolom path dilepas), file **arsip** path beku snapshot (K4). ✅ *sudah diperbarui.* Ditambah key `libreoffice_path` di registry settings §5.1 (K7). ✅
> - `PRD.md` F7 → DOCX wajib, PDF opsional (K7). ✅ *sudah diperbarui* (F7, Pertimbangan Teknis, Ketergantungan).

---

## §17. Standar Halaman List/Index (Filter & Search)

**Aturan wajib**: **setiap** halaman list/index (semua route `*.index`) harus mendukung — via **satu pola reusable**, bukan implementasi ad-hoc per fitur:

1. **Global search** — kotak cari bebas (beberapa kolom sekaligus).
2. **Advanced filter kontekstual** — sesuai domain: kategori, status, unit, **rentang tanggal** (dari–sampai), tahun, dll.
3. **Sort per kolom** + **pagination** — server-side via **Yajra DataTables** (performa aman untuk data besar).

### Pola Reusable (SSOT logika filter)
- **Query Filter class per entitas** (`app/Filters/`) — enkapsulasi logika filter, satu tempat. Controller tinggal terapkan ke query Yajra.
- **`x-ui.filter`** (komponen Blade bar filter) + **`x-ui.datatable`** — UI seragam; input filter pakai hook `js-filter` + `data-*`.

```php
// app/Filters/PermohonanFilter.php — SSOT filter satu entitas
class PermohonanFilter
{
    public function apply(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['status']   ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['template'] ?? null, fn ($q, $v) => $q->where('template_id', $v))
            ->when($f['dari']     ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['sampai']   ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($f['q']        ?? null, fn ($q, $v) => $q->whereHas('mahasiswa',
                fn ($m) => $m->where('nama', 'like', "%$v%")->orWhere('nim', 'like', "%$v%")));
    }
}
```

### Filter minimum per halaman (acuan)

| Halaman (index) | Filter wajib |
|---|---|
| Template (`admin.template.index`) | kategori, unit, status, search nama |
| Permohonan (`admin.permohonan.index`) | status, jenis surat, rentang tanggal, search nama/NIM |
| Arsip Surat (`admin.arsip.index`) | jenis surat, rentang tanggal, search nomor/nama/NIM |
| Surat Masuk (agenda) | tahun, bulan/rentang tanggal, pengirim, status disposisi |
| Surat Keluar (agenda) | tahun, rentang tanggal, tujuan, search perihal |
| Persyaratan / Kategori / Unit / Kamus | search nama, status aktif |
| User | role, status aktif, unit, search nama/email/NIM |
| Dokumen Saya (mahasiswa) | kategori syarat, search nama |
| Riwayat Permohonan (mahasiswa) | status, jenis surat, search |

> Konsistensi: filter tanggal selalu **rentang** (`dari`/`sampai`); dropdown filter pakai **Select2**; reset filter membersihkan query & kembali ke default.

---

## §18. Alur Pengembangan per Fitur (Vertical Slice)

**Prinsip**: setelah Fase Bootstrap (§0) selesai, fitur dikerjakan **satu per satu sebagai potongan vertikal utuh** (bukan per-lapisan horizontal). Satu fitur/sub-fitur = satu unit fokus = idealnya satu branch/PR → **minim konflik** (lihat FEATURE_MAP §4 overlap).

### Definition of Done per fitur (checklist berurutan)
Untuk tiap fitur/sub-fitur, kerjakan tuntas satu slice:
1. **Migration** (tabel + FK sesuai ERD) → 2. **Model** (relasi, cast, Enum status) → 3. **Factory** (untuk test) → 4. **Seeder** (jika master data) → 5. **Route** → 6. **Form Request** (validasi) → 7. **Policy** (jika ada otorisasi data) → 8. **Controller tipis** → 9. **Action/Service** (logika) → 10. **View** (index+filter §17 / form + komponen §11; teks statis **wajib** via `lang/id` — §11.5, tidak ada string hardcode) → 11. **Feature test** (integration: happy path + 1 skenario gagal/otorisasi, §14) → 12. **Pint + Larastan** hijau.

> Fitur besar dipecah jadi **sub-slice**: mis. F5 → 5.1 Ajukan, 5.2 Dokumen Saya, 5.3 Riwayat, 5.4 Resubmit — masing-masing satu slice.

### Urutan Fitur Disarankan (mengikuti dependensi FEATURE_MAP)
```
0. Bootstrap (§0)
1. Fondasi & master   : F1 auth · F2 config/settings · F13 kamus · F12 kategori · F4 syarat · (unit, pejabat)
2. Template           : F3 (butuh master di atas)
3. Layanan mahasiswa  : F5 permohonan → F6 review/approval
4. Penerbitan         : F7 generate → F8 arsip
5. Buku agenda        : F9 surat masuk · F10 surat keluar
6. Cross-cutting      : F11 notifikasi email (disisipkan saat F6/F7 sudah ada)
```
Aturan: **jangan mulai fitur hilir sebelum dependensinya jadi** (mis. F7 butuh F3 & F6). Setiap fitur mewarisi layout, komponen, service, dan pola filter (§17) yang sudah ada — tidak membangun ulang.

---

## §19. Ringkasan Keputusan Arsitektur

1. **Laravel 12 (default) / 13 bersyarat §2.2 + PHP 8.2+ + MySQL 8**, SSR Blade classic MVC, tanpa SPA.
2. **Fase Bootstrap dulu (§0)**: setup, library, layout admin, komponen, auth, seeder harus selesai & smoke-test hijau **sebelum** fitur pertama.
3. **Layering**: Controller tipis → Form Request → Action/Service → Model. SSOT logika di Action/Service.
4. **Auth**: Fortify + Spatie Permission (role) + Policy (data) — konsisten ERD §5.
5. **Upload reusable**: **Spatie Media Library = SSOT penyimpanan** + `MediaService` + 1 controller AJAX generik dipakai bersama; UI **FilePond**. File hidup → relasi `media`; file arsip immutable → path beku (snapshot). Disk private untuk file sensitif.
6. **PDF**: **LibreOffice** untuk DOCX→PDF surat utama, **mPDF** untuk HTML→PDF (disposisi/agenda/export) — bukan DomPDF/Spatie PDF (§2.1).
7. **UI**: **AdminLTE 4 (Bootstrap 5.3)** + DataTables (Yajra) + **Select2** + FilePond + Font Awesome, **semua via npm/Vite** — gaya compact seperti `ciengang`.
8. **Design system**: Blade Components (`x-ui.*`, `x-form.*`) + konvensi class `app-*` (style) & `js-*` (hook) — reusable, selector bermakna. **Semua teks statis FE wajib via `lang/id/*.php`** (§11.5) — tidak ada string hardcode, SSOT sama untuk label/tabel/tombol/pesan/validasi.
9. **Seeder wajib** untuk role/permission/kamus placeholder/settings (produksi) + sample master (unit, kategori, pejabat, mahasiswa).
10. **Testing**: Feature test = integration per fitur (F1-F13), memvalidasi aturan bisnis BRD.
11. **Kualitas**: Pint + Larastan + Enum (SSOT status) + config (bukan hardcode).
12. **List/Index (§17)**: semua halaman list wajib advanced filter + search + sort + pagination via pola reusable (Query Filter + `x-ui.filter` + Yajra).
13. **Pengembangan per fitur (§18)**: vertical slice (migration→…→test) satu fitur/sub-fitur per unit fokus, mengikuti urutan dependensi.
14. **Konflik dokumen** (§16) diangkat dengan rekomendasi update ke `perancangan-murni.md` §8.

---

*Dokumen SRS ini adalah acuan standar pengembangan. Perubahan standar harus diperbarui di sini sebagai Single Source of Truth arsitektur, dan diselaraskan dengan `perancangan-murni.md`, `ERD.md`, dan `PRD.md`.*
