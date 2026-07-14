# Analisa Aplikasi: Sistem Manajemen Surat (referensi-surat-2)

## 1. Overview

### Stack Teknologi
- **Framework**: Laravel 9.x (PHP ^8.0.2)
- **Auth**: Laravel Fortify 1.14 (session-based, login custom dengan cek `is_active`)
- **API Token**: Laravel Sanctum 3.0 (terpasang tapi hanya digunakan pada route `/api/user` default, tidak dipakai aktif)
- **Database**: MySQL/MariaDB (berdasarkan migrasi standar Laravel)
- **Frontend**: Blade templating + Bootstrap 5 (tema Sneat dari ThemeSelection) + ApexCharts (dashboard) + SweetAlert2 (konfirmasi delete)
- **Build Tool**: Vite (package.json ada)
- **Testing**: PHPUnit 9.x

### Deskripsi Aplikasi
Aplikasi ini adalah sistem administrasi surat berbasis web untuk institusi (kemungkinan kampus/lembaga pendidikan) yang mengelola surat masuk (*incoming*) dan surat keluar (*outgoing*). Fitur utama meliputi pencatatan surat, disposisi surat masuk, agenda (laporan berbasis rentang tanggal), galeri lampiran, serta manajemen pengguna dan konfigurasi institusi. Ada dua peran pengguna: **admin** (manajemen penuh) dan **staff** (operasional surat).

---

## 2. Aktor & Role

| Role  | Deskripsi                                                                         | Middleware/Guard        |
|-------|-----------------------------------------------------------------------------------|-------------------------|
| admin | Superuser: CRUD pengguna, konfigurasi sistem, akses semua fitur termasuk referensi | `auth` + `role:admin`  |
| staff | Pengguna operasional: CRUD surat, disposisi, galeri, profil sendiri               | `auth` (default)        |

**Catatan penting:**
- Login hanya bisa dilakukan oleh user dengan `is_active = true` (dicek di `FortifyServiceProvider`)
- Staff bisa menonaktifkan akunnya sendiri (`profile.deactivate`), namun admin tidak bisa menonaktifkan akunnya sendiri lewat route yang sama (route `profile.deactivate` hanya diberi middleware `role:staff`)
- Middleware `Role` menggunakan `abort(403)` jika role tidak cocok

---

## 3. Skema Database

### ERD Relasi Kunci (format teks)

```
users (1) ──────────────────────── (*) letters
                                         |
                                         | (1) --- (*) dispositions
                                         | (1) --- (*) attachments
                                         |
                                         +-- (belongs to) classifications [via classification_code]

users (1) ---- (*) dispositions
users (1) ---- (*) attachments

letter_statuses (1) ---- (*) dispositions [via letter_status FK]

configs: tabel konfigurasi global (tidak berelasi ke entitas lain)
```

### Per Entitas

| Nama Tabel               | Kolom Penting                                                                                                        | Keterangan                                                    |
|--------------------------|----------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------|
| `users`                  | id, name, email (unique), password, phone, role (default:'staff'), is_active (bool), profile_picture, two_factor_*  | Role enum: admin/staff; avatar fallback ke ui-avatars.com    |
| `letters`                | id, reference_number (unique), agenda_number, from, to, letter_date, received_date, description, note, type, classification_code (FK), user_id (FK) | type: 'incoming'/'outgoing'; from wajib jika incoming; to wajib jika outgoing |
| `dispositions`           | id, to, due_date, content, note, letter_status (FK->letter_statuses), letter_id (FK), user_id (FK)                  | Hanya untuk surat masuk; cascadeOnDelete dari letters         |
| `attachments`            | id, path (nullable), filename, extension (default:'pdf'), letter_id (FK), user_id (FK)                              | Ekstensi yang diizinkan: png, jpg, jpeg, pdf; cascadeOnDelete dari letters |
| `classifications`        | id, code (unique), type, description (nullable)                                                                      | Digunakan sebagai kategori/klasifikasi surat                  |
| `letter_statuses`        | id, status                                                                                                           | Status disposisi (contoh: Tindak Lanjut, Selesai, dll)        |
| `configs`                | id, code (unique), value                                                                                             | Konfigurasi global: default_password, page_size, app_name, institution_name, institution_address, institution_phone, institution_email, language, pic |
| `password_resets`        | email, token, created_at                                                                                             | Standar Laravel                                               |
| `personal_access_tokens` | id, tokenable_*, name, token, abilities, last_used_at, expires_at                                                    | Sanctum; tidak aktif digunakan dalam aplikasi ini             |
| `failed_jobs`            | id, uuid, connection, queue, payload, exception, failed_at                                                           | Standar Laravel queue                                         |

---

## 4. Peta Fitur (dari routes)

| Method         | URI                                           | Controller@Action                           | Role              | Deskripsi Singkat                               |
|----------------|-----------------------------------------------|---------------------------------------------|-------------------|-------------------------------------------------|
| GET            | `/`                                           | PageController@index                        | auth              | Dashboard dengan statistik hari ini             |
| GET            | `/profile`                                    | PageController@profile                      | auth              | Halaman profil pengguna sendiri                 |
| PUT            | `/profile`                                    | PageController@profileUpdate                | auth              | Update nama, email, phone, foto profil          |
| PUT            | `/profile/deactivate`                         | PageController@deactivate                   | auth + role:staff | Staff menonaktifkan akun sendiri lalu logout    |
| GET            | `/settings`                                   | PageController@settings                     | auth + role:admin | Halaman konfigurasi sistem                      |
| PUT            | `/settings`                                   | PageController@settingsUpdate               | auth + role:admin | Update konfigurasi (institution_name, dll)      |
| DELETE         | `/attachment`                                 | PageController@removeAttachment             | auth              | Hapus lampiran surat (by id di query)           |
| GET            | `/user`                                       | UserController@index                        | auth + role:admin | Daftar staff                                    |
| POST           | `/user`                                       | UserController@store                        | auth + role:admin | Tambah staff baru (password = default)          |
| PUT            | `/user/{user}`                                | UserController@update                       | auth + role:admin | Edit data/aktifasi/reset password staff         |
| DELETE         | `/user/{user}`                                | UserController@destroy                      | auth + role:admin | Hapus staff                                     |
| GET            | `/transaction/incoming`                       | IncomingLetterController@index              | auth              | Daftar surat masuk (card view)                  |
| GET            | `/transaction/incoming/create`                | IncomingLetterController@create             | auth              | Form tambah surat masuk                         |
| POST           | `/transaction/incoming`                       | IncomingLetterController@store              | auth              | Simpan surat masuk + upload lampiran            |
| GET            | `/transaction/incoming/{incoming}`            | IncomingLetterController@show               | auth              | Detail surat masuk                              |
| GET            | `/transaction/incoming/{incoming}/edit`       | IncomingLetterController@edit               | auth              | Form edit surat masuk                           |
| PUT            | `/transaction/incoming/{incoming}`            | IncomingLetterController@update             | auth              | Update surat masuk + upload lampiran baru       |
| DELETE         | `/transaction/incoming/{incoming}`            | IncomingLetterController@destroy            | auth              | Hapus surat masuk (cascade disposisi+lampiran)  |
| GET            | `/transaction/outgoing`                       | OutgoingLetterController@index              | auth              | Daftar surat keluar                             |
| GET            | `/transaction/outgoing/create`                | OutgoingLetterController@create             | auth              | Form tambah surat keluar                        |
| POST           | `/transaction/outgoing`                       | OutgoingLetterController@store              | auth              | Simpan surat keluar + upload lampiran           |
| GET            | `/transaction/outgoing/{outgoing}`            | OutgoingLetterController@show               | auth              | Detail surat keluar                             |
| GET            | `/transaction/outgoing/{outgoing}/edit`       | OutgoingLetterController@edit               | auth              | Form edit surat keluar                          |
| PUT            | `/transaction/outgoing/{outgoing}`            | OutgoingLetterController@update             | auth              | Update surat keluar                             |
| DELETE         | `/transaction/outgoing/{outgoing}`            | OutgoingLetterController@destroy            | auth              | Hapus surat keluar                              |
| GET            | `/transaction/{letter}/disposition`           | DispositionController@index                 | auth              | Daftar disposisi untuk 1 surat                  |
| GET            | `/transaction/{letter}/disposition/create`    | DispositionController@create                | auth              | Form tambah disposisi                           |
| POST           | `/transaction/{letter}/disposition`           | DispositionController@store                 | auth              | Simpan disposisi                                |
| GET            | `/transaction/{letter}/disposition/{d}/edit`  | DispositionController@edit                  | auth              | Form edit disposisi                             |
| PUT            | `/transaction/{letter}/disposition/{d}`       | DispositionController@update                | auth              | Update disposisi                                |
| DELETE         | `/transaction/{letter}/disposition/{d}`       | DispositionController@destroy               | auth              | Hapus disposisi                                 |
| GET            | `/agenda/incoming`                            | IncomingLetterController@agenda             | auth              | Agenda surat masuk (filter tanggal)             |
| GET            | `/agenda/incoming/print`                      | IncomingLetterController@print              | auth              | Cetak agenda surat masuk (auto window.print())  |
| GET            | `/agenda/outgoing`                            | OutgoingLetterController@agenda             | auth              | Agenda surat keluar (filter tanggal)            |
| GET            | `/agenda/outgoing/print`                      | OutgoingLetterController@print              | auth              | Cetak agenda surat keluar                       |
| GET            | `/gallery/incoming`                           | LetterGalleryController@incoming            | auth              | Galeri lampiran surat masuk                     |
| GET            | `/gallery/outgoing`                           | LetterGalleryController@outgoing            | auth              | Galeri lampiran surat keluar                    |
| GET            | `/reference/classification`                   | ClassificationController@index              | auth + role:admin | Daftar klasifikasi surat                        |
| POST           | `/reference/classification`                   | ClassificationController@store              | auth + role:admin | Tambah klasifikasi                              |
| PUT            | `/reference/classification/{classification}`  | ClassificationController@update             | auth + role:admin | Update klasifikasi                              |
| DELETE         | `/reference/classification/{classification}`  | ClassificationController@destroy            | auth + role:admin | Hapus klasifikasi                               |
| GET            | `/reference/status`                           | LetterStatusController@index                | auth + role:admin | Daftar status disposisi                         |
| POST           | `/reference/status`                           | LetterStatusController@store                | auth + role:admin | Tambah status disposisi                         |
| PUT            | `/reference/status/{status}`                  | LetterStatusController@update               | auth + role:admin | Update status disposisi                         |
| DELETE         | `/reference/status/{status}`                  | LetterStatusController@destroy              | auth + role:admin | Hapus status disposisi                          |
| GET (Fortify)  | `/login`                                      | (Fortify built-in)                          | guest             | Halaman login                                   |
| POST (Fortify) | `/login`                                      | (Fortify built-in)                          | guest             | Proses login                                    |
| POST (Fortify) | `/logout`                                     | (Fortify built-in)                          | auth              | Logout                                          |
| GET (API)      | `/api/user`                                   | (Sanctum closure)                           | auth:sanctum      | Mengembalikan user saat ini (tidak dipakai aktif) |

---

## 5. Flow per Aktor

### Admin

```
[Login] --> [Dashboard]
              |
              +-> [Surat Masuk] --> CRUD + Upload Lampiran
              |       +-> [Disposisi Surat] --> CRUD
              |
              +-> [Surat Keluar] --> CRUD + Upload Lampiran
              |
              +-> [Agenda Masuk/Keluar] --> Filter tanggal --> Print (browser)
              |
              +-> [Galeri Masuk/Keluar] --> Preview lampiran
              |
              +-> [Manajemen User] --> CRUD Staff + Reset Password
              |
              +-> [Referensi Klasifikasi] --> CRUD
              +-> [Referensi Status Disposisi] --> CRUD
              |
              +-> [Profil] --> Update nama/email/phone/foto
              +-> [Settings] --> Update konfigurasi institusi
```

### Staff

```
[Login] --> [Dashboard]
              |
              +-> [Surat Masuk] --> CRUD + Upload Lampiran
              |       +-> [Disposisi Surat] --> CRUD
              |
              +-> [Surat Keluar] --> CRUD + Upload Lampiran
              |
              +-> [Agenda Masuk/Keluar] --> Filter tanggal --> Print (browser)
              |
              +-> [Galeri Masuk/Keluar] --> Preview lampiran
              |
              +-> [Profil] --> Update data diri + Nonaktifkan Akun (logout otomatis)
```

**Perbedaan staff vs admin:**
- Staff tidak bisa akses: /user, /reference/*, /settings
- Staff bisa menonaktifkan akun sendiri (admin tidak)
- Dashboard identik untuk keduanya

---

## 6. Modul Detail

### 6.1 Autentikasi (Laravel Fortify)

**Deskripsi:** Login menggunakan email + password dengan validasi aktif akun.

**Input:** email, password

**Proses (FortifyServiceProvider):**
- `User::where('email', $email)->where('is_active', true)->first()`
- `Hash::check($password, $user->password)`
- Jika user tidak aktif, login gagal meski password benar
- Rate limit: 5 kali/menit per email+IP

**Output:** Redirect ke `/` (dashboard) jika berhasil; kembali ke form login dengan error jika gagal.

**Business rules:**
- Hanya user aktif (`is_active = true`) yang bisa login
- Tidak ada fitur registrasi publik; user dibuat oleh admin

---

### 6.2 Surat Masuk (IncomingLetterController)

**Deskripsi:** Pencatatan surat yang diterima dari pihak luar.

**Input (form create/edit):**

| Field               | Tipe     | Wajib | Keterangan                            |
|---------------------|----------|-------|---------------------------------------|
| reference_number    | text     | Ya    | Nomor surat, harus unik               |
| agenda_number       | text     | Ya    | Nomor agenda internal                 |
| from                | text     | Ya    | Pengirim surat                        |
| letter_date         | date     | Ya    | Tanggal surat                         |
| received_date       | date     | Ya    | Tanggal diterima                      |
| description         | textarea | Ya    | Perihal/isi surat                     |
| classification_code | select   | Ya    | Klasifikasi (dari tabel classifications) |
| note                | text     | Tidak | Catatan tambahan                      |
| attachments[]       | file     | Tidak | Multi-file: png, jpg, jpeg, pdf       |

**Proses (store):**
1. Validasi via `StoreLetterRequest`
2. Tambah `user_id` dari `auth()->user()->id`
3. `Letter::create(...)` dengan `type = 'incoming'`
4. Jika ada file: loop tiap file, cek ekstensi whitelist, buat nama `time()-originalname`, simpan ke `storage/app/public/attachments/`, buat record `Attachment`

**Output:** Redirect ke `/transaction/incoming` dengan flash `success`; jika gagal `back()` dengan flash `error`.

**Business rules:**
- `reference_number` harus unik di seluruh tabel `letters` (semua tipe)
- `type` di-hardcode sebagai `incoming` di form (input hidden), controller memvalidasi: jika `type != incoming` throw Exception
- Ekstensi file tidak pada whitelist (png/jpg/jpeg/pdf) dilewati dengan `continue` tanpa notifikasi ke user

---

### 6.3 Surat Keluar (OutgoingLetterController)

**Deskripsi:** Pencatatan surat yang dikirim ke pihak luar. Identik dengan surat masuk kecuali beberapa field.

**Input (perbedaan dari surat masuk):**

| Field         | Tipe | Wajib | Keterangan                        |
|---------------|------|-------|-----------------------------------|
| to            | text | Ya    | Tujuan surat (bukan from)         |
| from          | text | Tidak | Tidak wajib untuk outgoing        |
| received_date | date | Tidak | Tidak wajib untuk outgoing        |

**Proses:** Sama dengan surat masuk; `type` di-hardcode `outgoing`.

**Business rules:** Tidak ada fitur disposisi untuk surat keluar (tombol dispose hanya muncul di card `type == 'incoming'`).

---

### 6.4 Disposisi Surat (DispositionController)

**Deskripsi:** Tindak lanjut/instruksi yang diberikan pada surat masuk tertentu.

**Input (form create):**

| Field         | Tipe     | Wajib | Keterangan                              |
|---------------|----------|-------|-----------------------------------------|
| to            | text     | Ya    | Kepada siapa disposisi ditujukan        |
| due_date      | date     | Ya    | Batas waktu tindak lanjut               |
| content       | textarea | Ya    | Isi instruksi disposisi                 |
| letter_status | select   | Ya    | Status dari tabel `letter_statuses`     |
| note          | text     | Tidak | Catatan                                 |

**Proses (store):**
1. Validasi via `StoreDispositionRequest`
2. Tambah `user_id` (auth user) dan `letter_id` (dari URL parameter `{letter}`)
3. `Disposition::create(...)`

**Output:** Redirect ke `transaction.disposition.index` dengan parameter `letter`.

**Business rules:**
- Disposisi terikat ke satu surat masuk (lewat `letter_id`)
- Menghapus surat induk akan cascade menghapus semua disposisi terkait
- Tidak ada batasan role: semua user yang login bisa membuat disposisi

---

### 6.5 Agenda Surat

**Deskripsi:** Laporan daftar surat berdasarkan rentang tanggal, bisa dicetak.

**Input (filter form):**

| Field  | Tipe   | Keterangan                                                     |
|--------|--------|----------------------------------------------------------------|
| since  | date   | Tanggal awal rentang                                           |
| until  | date   | Tanggal akhir rentang                                          |
| filter | select | Kolom yang difilter: letter_date / received_date / created_at  |
| search | text   | Pencarian tambahan (reference_number, agenda_number, from/to)  |

**Proses (scopeAgenda di Letter model):**
```php
$query->whereBetween(DB::raw('DATE(' . $filter . ')'), [$since, $until]);
```
Nilai `$filter` diinterpolasi langsung ke SQL raw — lihat Catatan Teknis 8.1.

**Output:**
- Tampilan: tabel paginated dengan link ke detail surat
- Print: halaman terpisah dengan `onload="window.print()"`, menampilkan kop institusi dari `configs`

---

### 6.6 Galeri Lampiran (LetterGalleryController)

**Deskripsi:** Tampilan kartu semua file lampiran surat, dikelompokkan per tipe surat.

**Proses:** `Attachment::incoming()->render($search)` — scope `incoming` memfilter lewat relasi `letter` dengan `where('type', 'incoming')`.

**Output:** Grid card dengan ikon file (PDF/JPG/PNG) dan link ke file asli (di storage atau URL eksternal).

---

### 6.7 Manajemen User (UserController) — Admin Only

**Deskripsi:** CRUD akun staff.

**Input (create modal):** name, email, phone

**Input (edit modal):** name, email, phone, is_active (checkbox), reset_password (checkbox)

**Proses (store):**
- Password baru dibuat dari `Config::getValueByCode(ConfigEnum::DEFAULT_PASSWORD)` yang di-hash
- `User::render($search)` hanya menampilkan user dengan `role = staff` (admin tidak tampil di daftar)

**Proses (update):**
- `is_active = isset($newUser['is_active'])` — jika checkbox tidak dicentang, field tidak ada di request
- Jika `reset_password` dicentang, password direset ke default dari konfigurasi

**Business rules:**
- Password default diambil dari konfigurasi database, bukan dari `.env`
- Admin tidak muncul di daftar user (hanya staff yang tampil)
- Tidak ada route untuk admin menghapus dirinya sendiri

---

### 6.8 Referensi (ClassificationController, LetterStatusController) — Admin Only

**Deskripsi:** Master data: klasifikasi surat dan status disposisi.

**Klasifikasi (input):** code (unique), type, description

**Status (input):** status

**Proses:** Standar CRUD; menggunakan modal Bootstrap di halaman yang sama (resource route kecuali show, create, edit — artinya tidak ada halaman terpisah untuk form).

---

### 6.9 Profil & Settings (PageController)

**Profil (semua role):**
- Update: name, email, phone, profile_picture (jpg/png)
- Upload foto: hapus file lama (jika ada di `/storage/avatars/`), simpan baru
- Staff bisa menonaktifkan akun: set `is_active = false` lalu logout

**Settings (admin only):**
- Update semua config kecuali `language` (field ini di-skip di view)
- Menggunakan `DB::transaction` untuk update massal semua config sekaligus

---

## 7. UI/UX — Halaman Kunci

### 7.1 Halaman Login

```
+-------------------------------------+
|          [Logo Aplikasi]            |
|                                     |
|  Email                              |
|  +-----------------------------+    |
|  | user@example.com            |    |
|  +-----------------------------+    |
|                                     |
|  Password                           |
|  +-----------------------------+    |
|  | ........                    |    |
|  +-----------------------------+    |
|                                     |
|  +-----------------------------+    |
|  |         Sign In             |    |
|  +-----------------------------+    |
+-------------------------------------+
```
Tidak ada link "lupa password" di halaman login meski Fortify mendukung fitur password reset.

---

### 7.2 Dashboard

```
+--------------------------------------------------------------+
| [Sidebar]  | Halo, [Nama]!           [Tanggal Hari Ini]     |
|  Home      | *) Laporan hari ini     [Gambar ilustrasi]     |
|  Surat     |                                                 |
|  Masuk     |  Grafik Bar         [Card: Surat Masuk    N]   |
|  Surat     |  +----------------+ [Card: Surat Keluar   N]   |
|  Keluar    |  | Today: N total | [Card: Disposisi       N]   |
|  Agenda    |  | [+X% kemarin]  | [Card: User Aktif      N]   |
|  Galeri    |  | [Bar chart]    |                             |
|  Ref       |  +----------------+                             |
|  Settings  |                                                 |
+--------------------------------------------------------------+
```
Persentase perubahan dihitung vs hari kemarin menggunakan `GeneralHelper::calculateChangePercentage()`.

---

### 7.3 Daftar Surat Masuk/Keluar

```
+--------------------------------------------------+
| [Breadcrumb: Transaksi > Surat Masuk]  [+ Buat]  |
|                                                  |
| +----------------------------------------------+ |
| | Nomor Surat: 001/SK/2024               [menu]| |
| | Dari: Univ X | Agenda: 001 | Kategori: Umum  | |
| | -------------------------------------------- | |
| | Perihal: Lorem ipsum...                       | |
| |                          [Catatan] [PDF icon] | |
| | [Disposisi (3)]  [Lihat | Edit | Hapus]       | |
| +----------------------------------------------+ |
| (card surat berikutnya...)                       |
| [Pagination]                                     |
+--------------------------------------------------+
```
Tampilan card (bukan tabel). Tombol "Disposisi (N)" hanya muncul untuk surat masuk.

---

### 7.4 Form Tambah Surat Masuk

```
+--------------------------------------------------------+
| [Breadcrumb: Transaksi > Surat Masuk > Tambah]        |
|                                                        |
| +----------------------------------------------------+ |
| | [Nomor Surat  ] [Dari          ] [No. Agenda     ] | |
| |                                                    | |
| | [Tgl. Surat        ]  [Tgl. Diterima            ] | |
| |                                                    | |
| | [Perihal / Deskripsi (full width textarea)       ] | |
| |                                                    | |
| | [Klasifikasi v] [Catatan      ] [Lampiran(multi)] | |
| |                                                    | |
| |                    [Simpan]                        | |
| +----------------------------------------------------+ |
+--------------------------------------------------------+
```
Input hidden: `type=incoming`. Klasifikasi berupa select dari tabel classifications.

---

### 7.5 Form Disposisi

```
+--------------------------------------------------------+
| [Breadcrumb: Transaksi > {Nomor Surat} > Disposisi]   |
|                                                        |
| [Alert] Disposisi untuk surat 001/SK/2024 [Lihat]     |
|                                                        |
| +----------------------------------------------------+ |
| | [Kepada              ] [Batas Waktu (date)       ] | |
| |                                                    | |
| | [Isi Instruksi (full width textarea)             ] | |
| |                                                    | |
| | [Status Disposisi v  ] [Catatan                ] | |
| |                                                    | |
| |                    [Simpan]                        | |
| +----------------------------------------------------+ |
+--------------------------------------------------------+
```

---

### 7.6 Agenda Surat (dengan Filter & Cetak)

```
+----------------------------------------------------------------+
| [Breadcrumb: Agenda > Surat Masuk]                            |
|                                                                |
| +------------------------------------------------------------+ |
| | [Tgl Mulai  ] [Tgl Akhir  ] [Filter By v] [Filter] [Print]| |
| +------------------------------------------------------------+ |
|                                                                |
| +------------------------------------------------------------+ |
| | No. Agenda | Nomor Surat   | Dari       | Tgl. Surat      | |
| | 001        | 001/SK/2024   | Univ X     | Senin, 1 Jan... | |
| +------------------------------------------------------------+ |
| [Pagination]                                                   |
+----------------------------------------------------------------+
```
Filter By: letter_date / received_date / created_at. Tombol Print membuka tab baru dan langsung memanggil `window.print()` dengan kop institusi dari configs.

---

### 7.7 Halaman Manajemen User (Admin)

```
+----------------------------------------------------------------+
| [Breadcrumb: Users]                           [+ Tambah]      |
|                                                                |
| +------------------------------------------------------------+ |
| | Nama      | Email          | Telepon | Status | Aksi       | |
| | Budi S.   | budi@email.com | 08xx    | Aktif  | Edit Delete| |
| +------------------------------------------------------------+ |
| [Pagination]                                                   |
|                                                                |
| [Modal Tambah]: Nama, Email, Telepon                           |
| [Modal Edit]:   Nama, Email, Telepon, [v Aktif], [v Reset PW] |
+----------------------------------------------------------------+
```

---

### 7.8 Halaman Profil

```
+----------------------------------------------------------------+
| [Tab: Profil]  [Tab: Settings] (tab Settings hanya admin)     |
|                                                                |
| [Avatar 100x100] [Upload Foto] [Reset]   < 800K (JPG, PNG)   |
| ──────────────────────────────────────────────────────────    |
| [Nama (full width)]                                           |
| [Email               ]  [Telepon             ]                |
|                 [Update] [Batal]                              |
|                                                                |
| (staff saja) ──── Nonaktifkan Akun ────                       |
| [Alert Warning: yakin ingin nonaktifkan?]                     |
| [v Saya yakin ingin menonaktifkan akun]                        |
| [Nonaktifkan Akun] (disabled sampai checkbox dicentang)       |
+----------------------------------------------------------------+
```

---

### 7.9 Halaman Settings (Admin)

```
+----------------------------------------------------------------+
| [Tab: Profil]  [Tab: Settings]                                |
|                                                                |
| +------------------------------------------------------------+ |
| | [default_password    ]  [page_size             ]           | |
| | [app_name            ]  [institution_name      ]           | |
| | [institution_address ]  [institution_phone     ]           | |
| | [institution_email   ]  [pic                   ]           | |
| | (field 'language' sengaja disembunyikan di view)           | |
| |                    [Update] [Batal]                        | |
| +------------------------------------------------------------+ |
+----------------------------------------------------------------+
```

---

### 7.10 Halaman Referensi Klasifikasi (Admin)

```
+----------------------------------------------------------------+
| [Breadcrumb: Referensi > Klasifikasi]         [+ Tambah]      |
|                                                                |
| +------------------------------------------------------------+ |
| | Kode   | Tipe              | Deskripsi       | Aksi        | |
| | 001    | Surat Umum        | ...             | Edit Delete | |
| +------------------------------------------------------------+ |
| [Pagination]                                                   |
|                                                                |
| [Modal Tambah/Edit]: Kode, Tipe, Deskripsi                    |
+----------------------------------------------------------------+
```

---

## 8. Catatan Teknis

### 8.1 Potensi SQL Injection pada scopeAgenda (KRITIS)

**Lokasi:** `app/Models/Letter.php`, method `scopeAgenda`

```php
$query->whereBetween(DB::raw('DATE(' . $filter . ')'), [$since, $until]);
```

Nilai `$filter` berasal langsung dari `$request->filter` tanpa sanitasi atau whitelist di model. Meski di view hanya ada 3 pilihan di `<select>`, user bisa mengirim nilai apapun via request langsung (Postman, curl, dll). Ini adalah potensi SQL injection karena nilai diinterpolasi ke dalam raw SQL.

**Rekomendasi:** Tambahkan validasi whitelist di controller atau model:
```php
if (!in_array($filter, ['letter_date', 'received_date', 'created_at'])) {
    return $query;
}
```

---

### 8.2 Silent File Rejection saat Upload

**Lokasi:** `IncomingLetterController@store` dan `OutgoingLetterController@store`

File dengan ekstensi di luar whitelist di-`continue` tanpa notifikasi ke user. Jika user mengupload file `.docx`, tidak ada error — file diabaikan begitu saja dan user tidak mengetahuinya.

---

### 8.3 Redirect Bermasalah setelah Deactivate Akun

**Lokasi:** `PageController@deactivate`

```php
Auth::logout();
return back()->with('success', ...);
```

Setelah logout, `back()` mengembalikan ke halaman sebelumnya yang dilindungi middleware `auth`, sehingga terjadi redirect ke `/login`. Flash message `success` kemungkinan hilang karena redirect berganda. Lebih tepat menggunakan `redirect()->route('login')->with('success', ...)`.

---

### 8.4 Sanctum Terpasang tapi Tidak Dipakai

Laravel Sanctum terinstall dan tabel `personal_access_tokens` dibuat, namun hanya digunakan pada route `/api/user` default yang tidak terhubung ke fitur apapun. Ini adalah overhead yang tidak diperlukan jika tidak ada rencana pengembangan API.

---

### 8.5 Field `language` dalam Config — Fitur Belum Selesai

Tabel `configs` memiliki entry `language`, namun di halaman Settings field ini sengaja di-skip dengan `@continue($config->code == 'language')`. Tidak ada kode dalam aplikasi yang membaca config `language` untuk menerapkan locale (`App::setLocale()`). Fitur multi-bahasa tampaknya setengah jadi — ada data di DB dan enum, tapi tidak ada implementasi switching.

---

### 8.6 Tidak Ada Pembatasan Kepemilikan saat Delete Surat

Semua user yang terautentikasi (staff dan admin) bisa menghapus surat apapun, termasuk surat yang dibuat user lain. Tidak ada pengecekan `user_id == auth()->id()` di controller destroy. Ini bisa menjadi masalah jika ada banyak staff dalam satu institusi.

---

### 8.7 Dua Controller Hampir Identik (Code Duplication)

`IncomingLetterController` dan `OutgoingLetterController` memiliki kode yang hampir identik — perbedaan hanya pada nilai `LetterType` dan nama route. Kandidat refactor ke satu `LetterController` dengan parameter type, atau menggunakan parent controller dengan method yang bisa di-override.

---

### 8.8 Route Model Binding Disposition Tidak Divalidasi ke Letter

Route `/transaction/{letter}/disposition/{disposition}` memungkinkan akses disposisi dari surat yang berbeda. Misalnya, `/transaction/1/disposition/99` akan berhasil mengakses disposisi ID 99 meski disposisi itu milik letter ID 5. Tidak ada validasi bahwa disposition memang milik letter yang ada di URL.

---

### 8.9 Tema Sneat — Lisensi Komersial

Layout menggunakan tema komersial **Sneat Bootstrap Admin Template** dari ThemeSelection dengan komentar eksplisit: *"You must have a valid license purchased in order to legally use the theme for your project."* Pastikan lisensi dimiliki sebelum deployment produksi.

---

### 8.10 Dua Factor Authentication Tersedia tapi Tidak Dikonfigurasi

Migrasi `add_two_factor_columns_to_users_table` menambahkan kolom 2FA ke tabel users (dari Fortify). Namun tidak ada konfigurasi eksplisit untuk mengaktifkan 2FA di `FortifyServiceProvider`. Kolom ada di database tapi fitur tidak aktif.
