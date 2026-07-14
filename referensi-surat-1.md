# Analisa Aplikasi: Aplikasi Manajemen Surat

---

## 1. Overview

**Stack Teknologi:**
- Laravel 5.7.* (PHP ^7.1.3)
- Database: MySQL (konvensi Laravel default)
- Auth: Laravel built-in `Auth::attempt()` + `Illuminate\Foundation\Auth\User as Authenticatable`
- PDF: `barryvdh/laravel-dompdf` ^0.8.5
- Excel: `maatwebsite/excel` ~2.1.0 (versi 2.x — API lama)
- Frontend: AdminLTE 3 (Bootstrap 4), jQuery DataTables, Ekko Lightbox, Filterizr
- Build tool: Laravel Mix (webpack.mix.js)
- Template engine: Blade

**Deskripsi Aplikasi:**

Aplikasi ini adalah sistem manajemen surat berbasis web untuk instansi/lembaga (di footer disebut "Teknik Informatika Unirow Tuban"). Fungsinya mencatat surat masuk dan surat keluar, mengelola disposisi surat, mengklasifikasikan surat berdasarkan kode klasifikasi, serta menghasilkan laporan dalam format PDF dan Excel. Pengguna dibagi menjadi dua level: admin (akses penuh termasuk manajemen pengguna dan instansi) dan petugas (akses operasional surat).

---

## 2. Aktor & Role

| Role    | Deskripsi                                                                                  | Middleware/Guard                          |
|---------|--------------------------------------------------------------------------------------------|-------------------------------------------|
| admin   | Akses penuh: CRUD surat masuk/keluar, disposisi, klasifikasi, manajemen pengguna & instansi | `auth` + `checkRole:admin,petugas` (umum) + `checkRole:admin` (instansi, pengguna) |
| petugas | Akses operasional: CRUD surat masuk/keluar, disposisi, klasifikasi. Tidak bisa hapus surat | `auth` + `checkRole:admin,petugas`        |
| guest   | Hanya akses halaman login                                                                   | tidak perlu auth                          |

**Catatan role di UI:**
- Tombol "Hapus" di daftar surat masuk/keluar hanya muncul jika `auth()->user()->role == 'admin'`
- Menu "Pengaturan" (Instansi, Manajemen User) hanya tampil di sidebar untuk admin
- Statistik pengguna di dashboard hanya muncul untuk admin

---

## 3. Skema Database

### ERD Relasi Kunci (teks)

```
users (1) ──────────────── (N) suratmasuk
  |                              |
  |                              | (1)
  |                              |
  └──── (N) suratkeluar    (N) disposisis
  |
  └──── (N) disposisis

klasifikasi  ──── (referensi kode, bukan FK formal) ──── suratmasuk.kode
                                                    └──── suratkeluar.kode

instansis ──── (lookup mandiri, dipakai untuk header PDF cetak) ──── tidak ada FK
```

**Relasi formal (via foreign key constraint):**
- `suratmasuk.users_id` -> `users.id` (RESTRICT delete/update)
- `suratkeluar.users_id` -> `users.id` (RESTRICT delete/update)
- `disposisis.users_id` -> `users.id` (RESTRICT delete/update)
- `disposisis.suratmasuk_id` -> `suratmasuk.id` (CASCADE delete/update)

### Detail Tabel

| Nama Tabel   | Kolom Penting                                                                                                     | Keterangan                                                              |
|--------------|-------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| users        | id, name, email (unique), password, role, remember_token, timestamps                                             | Role berisi string: `admin` atau `petugas`. Tidak ada tabel roles terpisah. |
| suratmasuk   | id, no_surat (unique), asal_surat, isi (text), kode (char), tgl_surat (date), tgl_terima (date), filemasuk, keterangan (text), users_id, timestamps | `kode` mengacu ke `klasifikasi.kode` secara logis (tanpa FK). File disimpan di `/datasuratmasuk/` di public. |
| suratkeluar  | id, no_surat (unique), tujuan_surat, isi, kode, tgl_surat (date), tgl_catat (date), filekeluar, keterangan, users_id, timestamps | Struktur paralel dengan suratmasuk. File disimpan di `/datasuratkeluar/`. |
| klasifikasi  | id, nama, kode, uraian (text), timestamps                                                                         | Master data klasifikasi/kategori surat. Kode diisi manual (max 2 karakter). |
| instansis    | id, nama, alamat, pimpinan, email, file (path logo), timestamps                                                   | Profil instansi; `Instansi::first()` dipakai sebagai header PDF cetak. Hanya satu record yang aktif digunakan. |
| disposisis   | id, tujuan, isi, sifat, batas_waktu (date), catatan, users_id, suratmasuk_id, timestamps                         | Disposisi terkait surat masuk. Bisa banyak disposisi per surat masuk. Bisa dicetak PDF per item. |
| password_resets | email, token, created_at                                                                                      | Tabel reset password bawaan Laravel (belum terpakai di fitur UI).       |

---

## 4. Peta Fitur (dari routes)

### Route Publik (tanpa auth)

| Method | URI           | Controller@Action        | Role  | Deskripsi                    |
|--------|---------------|--------------------------|-------|------------------------------|
| GET    | /login        | AuthController@login     | guest | Tampil form login            |
| POST   | /postlogin    | AuthController@postlogin | guest | Proses autentikasi           |
| GET    | /logout       | AuthController@logout    | semua | Logout dan redirect ke login |

### Route Terproteksi: auth + checkRole:admin,petugas

| Method | URI                                     | Controller@Action                              | Role           | Deskripsi                              |
|--------|-----------------------------------------|------------------------------------------------|----------------|----------------------------------------|
| GET    | /                                       | (closure)                                      | admin, petugas | Redirect ke dashboard                  |
| GET    | /dashboard                              | DashboardController@index                      | admin, petugas | Beranda dengan statistik               |
| GET    | /suratmasuk                             | SuratMasukController@index                     | admin, petugas | Daftar semua surat masuk               |
| GET    | /suratmasuk/index                       | SuratMasukController@index                     | admin, petugas | Sama dengan di atas (duplikat route)   |
| GET    | /suratmasuk/create                      | SuratMasukController@create                    | admin, petugas | Form tambah surat masuk                |
| POST   | /suratmasuk/tambah                      | SuratMasukController@tambah                    | admin, petugas | Simpan surat masuk baru                |
| GET    | /suratmasuk/{id}/tampil                 | SuratMasukController@tampil                    | admin, petugas | Detail/tampil file surat masuk         |
| GET    | /suratmasuk/{id}/edit                   | SuratMasukController@edit                      | admin, petugas | Form edit surat masuk                  |
| POST   | /suratmasuk/{id}/update                 | SuratMasukController@update                    | admin, petugas | Update data surat masuk                |
| GET    | /suratmasuk/{id}/delete                 | SuratMasukController@delete                    | admin (UI)     | Hapus surat masuk (route terbuka, proteksi hanya di UI) |
| GET    | /suratmasuk/agenda                      | SuratMasukController@agenda                    | admin, petugas | Buku agenda surat masuk                |
| GET    | /suratmasuk/agendamasukcetak_pdf        | SuratMasukController@agendamasukcetak_pdf      | admin, petugas | Cetak agenda surat masuk ke PDF        |
| GET    | /suratmasuk.agendamasukdownload_excel   | SuratMasukController@agendamasukdownload_excel | admin, petugas | Download agenda surat masuk ke Excel   |
| GET    | /suratmasuk/galeri                      | SuratMasukController@galeri                    | admin, petugas | Galeri file lampiran surat masuk       |
| GET    | /suratkeluar                            | SuratKeluarController@index                    | admin, petugas | Daftar semua surat keluar              |
| GET    | /suratkeluar/index                      | SuratKeluarController@index                    | admin, petugas | Sama dengan di atas                    |
| GET    | /suratkeluar/create                     | SuratKeluarController@create                   | admin, petugas | Form tambah surat keluar               |
| POST   | /suratkeluar/tambah                     | SuratKeluarController@tambah                   | admin, petugas | Simpan surat keluar baru               |
| GET    | /suratkeluar/{id}/tampil                | SuratKeluarController@tampil                   | admin, petugas | Detail/tampil file surat keluar        |
| GET    | /suratkeluar/{id}/edit                  | SuratKeluarController@edit                     | admin, petugas | Form edit surat keluar                 |
| POST   | /suratkeluar/{id}/update                | SuratKeluarController@update                   | admin, petugas | Update data surat keluar               |
| GET    | /suratkeluar/{id}/delete                | SuratKeluarController@delete                   | admin (UI)     | Hapus surat keluar                     |
| GET    | /suratkeluar/agenda                     | SuratKeluarController@agenda                   | admin, petugas | Buku agenda surat keluar               |
| GET    | /suratkeluar/agendakeluarcetak_pdf      | SuratKeluarController@agendakeluarcetak_pdf    | admin, petugas | Cetak agenda surat keluar ke PDF       |
| GET    | /suratkeluar.agendakeluardownload_excel | SuratKeluarController@agendakeluardownload_excel | admin, petugas | Download agenda surat keluar ke Excel |
| GET    | /suratkeluar/galeri                     | SuratKeluarController@galeri                   | admin, petugas | Galeri file lampiran surat keluar      |
| GET    | /klasifikasi                            | KlasifikasiController@index                    | admin, petugas | Daftar klasifikasi surat               |
| GET    | /klasifikasi/index                      | KlasifikasiController@index                    | admin, petugas | Sama dengan di atas                    |
| GET    | /klasifikasi/create                     | KlasifikasiController@create                   | admin, petugas | Form tambah klasifikasi                |
| POST   | /klasifikasi/tambah                     | KlasifikasiController@tambah                   | admin, petugas | Simpan klasifikasi baru                |
| GET    | /klasifikasi/{id}/edit                  | KlasifikasiController@edit                     | admin, petugas | Form edit klasifikasi                  |
| POST   | /klasifikasi/{id}/update                | KlasifikasiController@update                   | admin, petugas | Update klasifikasi                     |
| GET    | /klasifikasi/{id}/delete                | KlasifikasiController@delete                   | admin, petugas | Hapus klasifikasi                      |
| POST   | /klasifikasi.import                     | KlasifikasiController@import                   | admin, petugas | Import klasifikasi dari Excel          |
| GET    | /disposisi/{suratmasuk}                 | DisposisiController@index                      | admin, petugas | Daftar disposisi untuk surat masuk     |
| POST   | /disposisi/{suratmasuk}                 | DisposisiController@store                      | admin, petugas | Simpan disposisi baru                  |
| GET    | /disposisi/{suratmasuk}/create          | DisposisiController@create                     | admin, petugas | Form tambah disposisi                  |
| GET    | /disposisi/{suratmasuk}/{id}/edit       | DisposisiController@edit                       | admin, petugas | Form edit disposisi                    |
| GET    | /disposisi/{suratmasuk}/{id}            | DisposisiController@update                     | admin, petugas | Update disposisi (GET dipakai untuk update — bug desain) |
| DELETE | /disposisi/{suratmasuk}/{id}            | DisposisiController@destroy                    | admin (UI)     | Hapus disposisi                        |
| GET    | /disposisi/{suratmasuk}/{id}/download   | DisposisiController@download                   | admin, petugas | Cetak disposisi ke PDF                 |

### Route Terproteksi: auth + checkRole:admin (khusus admin)

| Method    | URI              | Controller@Action          | Role  | Deskripsi                          |
|-----------|------------------|----------------------------|-------|------------------------------------|
| GET       | /instansi        | InstansiController@index   | admin | Daftar instansi                    |
| GET       | /instansi/create | InstansiController@create  | admin | Form tambah instansi               |
| POST      | /instansi        | InstansiController@store   | admin | Simpan instansi baru               |
| GET       | /instansi/{id}/edit | InstansiController@edit | admin | Form edit instansi                 |
| PUT/PATCH | /instansi/{id}   | InstansiController@update  | admin | Update instansi                    |
| DELETE    | /instansi/{id}   | InstansiController@destroy | admin | Hapus instansi (belum diimplementasi) |
| GET       | /pengguna        | PenggunaController@index   | admin | Daftar pengguna                    |
| GET       | /pengguna/create | PenggunaController@create  | admin | Form tambah pengguna               |
| POST      | /pengguna        | PenggunaController@store   | admin | Simpan pengguna baru               |
| GET       | /pengguna/{id}/edit | PenggunaController@edit | admin | Form edit pengguna                 |
| PUT/PATCH | /pengguna/{id}   | PenggunaController@update  | admin | Update pengguna                    |
| DELETE    | /pengguna/{id}   | PenggunaController@destroy | admin | Hapus pengguna                     |

---

## 5. Flow per Aktor

### Admin

```
[/login] --> isi email+password --> POST /postlogin --> Auth::attempt()
    |
    +-- gagal --> redirect back + flash "Email atau Password Salah!"
    +-- sukses --> redirect /dashboard
                    |
                    +-- [Beranda] --> lihat statistik (count suratmasuk, suratkeluar, klasifikasi, users)
                    |
                    +-- [Transaksi Surat]
                    |     +-- Surat Masuk --> list --> [Tambah | Edit | Disposisi | Hapus]
                    |     |       +-- Tambah: isi form --> upload file --> simpan ke /datasuratmasuk/
                    |     |       +-- Disposisi: buka list disposisi --> [Tambah | Edit | Cetak PDF | Hapus]
                    |     +-- Surat Keluar --> list --> [Tambah | Edit | Hapus]
                    |             +-- Tambah: isi form --> upload file --> simpan ke /datasuratkeluar/
                    |
                    +-- [Buku Agenda]
                    |     +-- Agenda Surat Masuk --> tabel --> [Download Excel | Cetak PDF]
                    |     +-- Agenda Surat Keluar --> tabel --> [Download Excel | Cetak PDF]
                    |
                    +-- [Galeri File]
                    |     +-- File Surat Masuk --> grid thumbnail --> klik --> detail file
                    |     +-- File Surat Keluar --> grid thumbnail
                    |
                    +-- [Klasifikasi] --> list --> [Tambah | Edit | Hapus | Import Excel]
                    |
                    +-- [Pengaturan] (hanya admin)
                          +-- Manajemen Instansi --> [Tambah | Edit]
                          +-- Manajemen User --> [Tambah | Edit | Hapus]

[/logout] --> Auth::logout() --> redirect /login
```

### Petugas

```
[/login] --> sukses --> redirect /dashboard
                    |
                    +-- [Beranda] --> lihat statistik (TANPA counter pengguna)
                    |
                    +-- [Transaksi Surat]
                    |     +-- Surat Masuk --> list --> [Tambah | Edit | Disposisi]  (TANPA tombol Hapus)
                    |     +-- Surat Keluar --> list --> [Tambah | Edit]             (TANPA tombol Hapus)
                    |
                    +-- [Buku Agenda] --> sama seperti admin
                    +-- [Galeri File] --> sama seperti admin
                    +-- [Klasifikasi] --> sama seperti admin

Menu "Pengaturan" TIDAK muncul di sidebar.
Route /instansi dan /pengguna akan redirect ke / jika diakses langsung (CheckRole middleware).
```

---

## 6. Modul Detail

### 6.1 Autentikasi

- **Deskripsi:** Login manual menggunakan email dan password. Tidak ada registrasi publik, reset password via UI, atau verifikasi email.
- **Input:** email, password
- **Proses:** `Auth::attempt(['email'=>..., 'password'=>...])`. Jika berhasil redirect ke `/dashboard`. Jika gagal, flash message menggunakan key `sukses` (nama variable menyesatkan — dipakai untuk pesan error juga).
- **Output:** Redirect ke `/dashboard` atau kembali ke form login dengan pesan error.
- **Business rules:**
  - Tidak ada throttling login
  - Tidak ada 2FA
  - Session menggunakan Laravel default session driver

### 6.2 Surat Masuk

- **Deskripsi:** Pencatatan surat yang diterima dari pihak luar. Setiap surat masuk wajib disertai file lampiran.
- **Input (form tambah):**
  - `no_surat` — nomor surat (text, unique, min 5 karakter)
  - `asal_surat` — asal/pengirim surat (text)
  - `isi` — isi ringkas surat (textarea, min 5 karakter)
  - `kode` — kode klasifikasi (select dari tabel klasifikasi)
  - `tgl_surat` — tanggal surat (date)
  - `tgl_terima` — tanggal diterima (date)
  - `keterangan` — keterangan tambahan (text, min 5 karakter)
  - `filemasuk` — file lampiran (file upload: jpg/jpeg/png/doc/docx/pdf)
- **Proses:**
  - Validasi input
  - Upload file ke `/public/datasuratmasuk/` dengan nama prefix `suratMasuk-` + nama file asli
  - Simpan record dengan `users_id = Auth::id()` (pencatat otomatis)
- **Output:** Redirect ke `/suratmasuk/index` dengan flash sukses.
- **Business rules:**
  - `no_surat` harus unik di tabel suratmasuk
  - File wajib diupload saat create (required di view)
  - Saat update, file bersifat opsional (hanya diupdate jika ada file baru)
  - Hapus data tidak menghapus file fisik dari disk

### 6.3 Surat Keluar

- **Deskripsi:** Pencatatan surat yang dikirim ke pihak luar. Struktur paralel dengan surat masuk.
- **Input (form tambah):**
  - `no_surat` — nomor surat (text, unique, min 5 karakter)
  - `tujuan_surat` — tujuan/penerima surat (text)
  - `isi` — isi ringkas surat (textarea, min 5 karakter)
  - `kode` — kode klasifikasi (select)
  - `tgl_surat` — tanggal surat (date)
  - `tgl_catat` — tanggal pencatatan (date) — berbeda dengan surat masuk yang pakai `tgl_terima`
  - `keterangan` — keterangan (text, min 5 karakter)
  - `filekeluar` — file lampiran (file upload: jpg/jpeg/png/doc/docx/pdf)
- **Proses:** Sama dengan surat masuk, file disimpan ke `/public/datasuratkeluar/`
- **Output:** Redirect ke `/suratkeluar/index`
- **Business rules:** Sama dengan surat masuk

### 6.4 Disposisi

- **Deskripsi:** Catatan tindak lanjut/instruksi dari pimpinan atas sebuah surat masuk. Satu surat masuk bisa memiliki banyak disposisi. Disposisi dapat dicetak sebagai PDF.
- **Input (form tambah):**
  - `tujuan` — kepada siapa disposisi ditujukan (text, required)
  - `isi` — isi disposisi/instruksi (text, required)
  - `sifat` — sifat disposisi (text, required) — tidak ada pilihan baku, bebas isi
  - `batas_waktu` — batas waktu penyelesaian (date, required)
  - `catatan` — catatan tambahan (text, required)
- **Proses:**
  - `suratmasuk_id` diambil dari route parameter `{suratmasuk}`
  - `users_id` dari `Auth::id()`
  - Disimpan via `Disposisi::create()`
- **Output:** Redirect ke list disposisi surat masuk tersebut.
- **Business rules:**
  - Disposisi hanya bisa dibuat untuk surat masuk (tidak ada disposisi surat keluar)
  - Hapus disposisi menggunakan cascade (jika surat masuk dihapus, disposisinya ikut terhapus)
  - Cetak disposisi mengambil data instansi via `Instansi::first()` untuk header PDF
  - Update disposisi menggunakan method GET (bukan PUT/PATCH) — bug desain

### 6.5 Klasifikasi

- **Deskripsi:** Master data kategori/klasifikasi surat yang digunakan sebagai referensi kode pada surat masuk dan keluar.
- **Input (form tambah):**
  - `nama` — nama klasifikasi (text, unique, min 5 karakter)
  - `kode` — kode klasifikasi (text, unique, max 2 karakter)
  - `uraian` — uraian/penjelasan (text, min 5 karakter)
- **Proses:** Simpan ke tabel `klasifikasi`. Bisa juga diimport dari file Excel.
- **Output:** Redirect ke `/klasifikasi/index`
- **Business rules:**
  - Kode dan nama harus unik
  - Kode max 2 karakter
  - Import Excel menggunakan class `KlasifikasiImport`, namun implementasi di controller memiliki kode yang di-comment — status fitur import tidak jelas

### 6.6 Instansi (Profil Lembaga)

- **Deskripsi:** Data profil instansi/lembaga yang digunakan sebagai kop/header dokumen PDF. Dirancang untuk satu instansi aktif.
- **Input (form):**
  - `nama` — nama instansi (text, required)
  - `alamat` — alamat instansi (textarea)
  - `pimpinan` — nama pimpinan (text, required)
  - `email` — email instansi (email, required saat update)
  - `file` — logo instansi (file: jpeg/png, max 2MB)
- **Proses:** Upload logo ke `/public/uploads/logo/` dengan prefix timestamp.
- **Output:** Redirect ke `/instansi`
- **Business rules:**
  - Hanya admin yang bisa mengakses
  - Method `destroy()` ada di controller tapi body kosong — hapus instansi tidak berfungsi
  - Aplikasi mengambil `Instansi::first()` saat generate PDF — asumsi hanya ada satu data instansi

### 6.7 Manajemen Pengguna

- **Deskripsi:** Admin dapat menambah, mengubah, dan menghapus akun pengguna sistem.
- **Input (form tambah):**
  - `name` — nama pengguna (text, required, unique, min 5)
  - `email` — email (email, required, unique)
  - `password` — password (password, required) — tidak ada konfirmasi password
  - `role` — level (select: `admin` atau `petugas`)
- **Proses:** Password di-hash menggunakan `Hash::make()`.
- **Output:** Redirect ke `/pengguna`
- **Business rules:**
  - Hanya admin yang bisa mengakses
  - Hapus pengguna dilindungi oleh FK constraint: jika user masih memiliki surat masuk/keluar/disposisi, hapus akan gagal dan menampilkan flash message
  - Saat update, password selalu di-hash ulang dari input — jika field password dikosongkan, password menjadi hash dari string kosong (bug)

### 6.8 Buku Agenda & Laporan

- **Deskripsi:** Tampilan tabular semua surat masuk/keluar dalam format buku agenda. Bisa diekspor ke PDF atau Excel.
- **Input:** Tidak ada filter — menampilkan semua data.
- **Proses:**
  - PDF: `PDF::loadview('suratmasuk.cetakagendaPDF', ...)` mengambil data instansi + semua surat
  - Excel: `Excel::create()` dengan `$sheet->fromArray()` — API versi lama Maatwebsite 2.x
- **Output:** Stream PDF ke browser atau download file `.xls`
- **Business rules:**
  - Tidak ada filter rentang tanggal — tampil semua data sekaligus
  - Kolom "Penerima" di agenda surat masuk menampilkan nama user pencatat (`$suratmasuk->users->name`) — potensi N+1 query karena tidak pakai eager loading

### 6.9 Galeri File

- **Deskripsi:** Tampilan grid/thumbnail untuk file lampiran surat masuk dan keluar.
- **Input:** Tidak ada — menampilkan semua file.
- **Proses:** Looping data, membangun URL file dari path di DB menggunakan `URL::to('/')/datasuratmasuk/filename`.
- **Output:** Grid gambar dengan lightbox (Ekko Lightbox). Klik item untuk perbesar.
- **Business rules:**
  - Hanya gambar (jpg/png) yang bisa ditampilkan sebagai thumbnail
  - File doc/docx/pdf tetap muncul di grid tapi tidak bisa tampil sebagai gambar

---

## 7. UI/UX — Halaman Kunci

### 7.1 Halaman Login

```
+------------------------------------------+
|          [Logo SVG]                       |
|                                           |
|      Aplikasi                             |
|      Manajemen Surat                      |
|                                           |
|  [! Email atau Password Salah!]           |  <- flash error (jika gagal)
|                                           |
|  [name@example.com ...............]       |  <- input email
|  [password ........................]       |  <- input password
|                                           |
|  [          LOGIN          ]              |  <- tombol submit (merah)
+------------------------------------------+
Background: gradient merah-orange
```

### 7.2 Dashboard (Beranda)

```
+====================================================================+
| APLIKASI MANAJEMEN SURAT          [Nama User v]  [Logout]         |  <- navbar
+====================================================================+
| Sidebar                 | Content Wrapper                         |
| +-----------------+     |  SELAMAT DATANG DI BERANDA ...          |
| | Beranda         |     |                                         |
| | Transaksi >     |     |  +----------+ +----------+ +----------+ |
| |   Surat Masuk   |     |  |    42    | |    18    | |    7     | |
| |   Surat Keluar  |     |  | Surat    | | Surat    | | Klasifi- | |
| | Buku Agenda >   |     |  | Masuk    | | Keluar   | | kasi     | |
| |   Agenda Masuk  |     |  | [Detail] | | [Detail] | | [Detail] | |
| |   Agenda Keluar |     |  +----------+ +----------+ +----------+ |
| | Galeri File >   |     |                                         |
| | Klasifikasi     |     |  +----------+  <- kotak ke-4 hanya      |
| | Pengaturan *    |     |  |    5     |     untuk admin            |
| |   Instansi *    |     |  | Pengguna |                           |
| |   User *        |     |  | [Detail] |                           |
| +-----------------+     |  +----------+                           |
| *hanya untuk admin      |                                         |
+========================+=========================================+
Footer: Copyright 2020 | Teknik Informatika Unirow Tuban
```

### 7.3 Daftar Surat Masuk (/suratmasuk/index)

```
+------------------------------------------------------------------+
| Surat Masuk                                                      |
| ---------------------------------------------------------------- |
| [+ Tambah Data]                                                  |
|                                                                  |
| Search: [______]                          [Show 10 entries v]    |
| +----+---------+------+--------+------+-------+-------+--------+ |
| |No. |Isi Rkgs |File  |Asal    |Kode  |No.Sur |Tgl.Sur|Aksi    | |
| +----+---------+------+--------+------+-------+-------+--------+ |
| | 1  |Undangan |f.pdf |Diknas  |SU    |001/.. |2024-01|[Edit]  | |
| |    |         |      |        |      |       |       |[Disp]  | |
| |    |         |      |        |      |       |       |[Hapus]*| |
| +----+---------+------+--------+------+-------+-------+--------+ |
| *tombol Hapus hanya muncul untuk role admin                      |
| Pagination dan search menggunakan jQuery DataTables (client side)|
+------------------------------------------------------------------+
```

### 7.4 Form Tambah Surat Masuk (/suratmasuk/create)

```
+-------------------------------------------------------------+
| Tambah Data Surat Masuk                                     |
| ----------------------------------------------------------- |
|  Kolom Kiri                  | Kolom Kanan                 |
|  Nomor Surat                 | Tanggal Surat               |
|  [________________________]  | [____-__-__]                |
|                              |                             |
|  Asal Surat                  | Tanggal Diterima            |
|  [________________________]  | [____-__-__]                |
|                              |                             |
|  Isi Ringkas                 | Keterangan                  |
|  [                        ]  | [________________________]  |
|  [                        ]  |                             |
|                              | File                        |
|  Kode Klasifikasi            | [Choose File]               |
|  [-- Pilih Klasifikasi v  ]  | * (jpg,jpeg,png,doc,pdf)    |
| ----------------------------------------------------------- |
|  [SIMPAN]   [BATAL]                                         |
+-------------------------------------------------------------+
```

### 7.5 Form Tambah Disposisi

```
+-------------------------------------------------------------+
| Tambah Disposisi                                            |
| ----------------------------------------------------------- |
|  Tujuan                                                     |
|  [________________________]                                 |
|                                                             |
|  Isi                                                        |
|  [________________________]                                 |
|                                                             |
|  Sifat                                                      |
|  [________________________]                                 |
|                                                             |
|  Batas Waktu                                                |
|  [____-__-__]                                               |
|                                                             |
|  Catatan                                                    |
|  [________________________]                                 |
| ----------------------------------------------------------- |
|  [SIMPAN]   [BATAL]                                         |
+-------------------------------------------------------------+
```

### 7.6 Daftar Disposisi Surat Masuk

```
+------------------------------------------------------------------+
| Disposisi                                                        |
| [Kembali]  [+ Tambah Data]                                       |
| +----+--------+----------+-------+-----------+--------+--------+ |
| |No. |Tujuan  |Isi Disp  |Sifat  |Batas Waktu|Catatan |Aksi    | |
| +----+--------+----------+-------+-----------+--------+--------+ |
| | 1  |Kabag   |Tindak    |Penting|2024-02-15 |Segera  |[Edit]  | |
| |    |Umum    |lanjuti   |       |           |        |[Cetak] | |
| |    |        |          |       |           |        |[Hapus]*| |
| +----+--------+----------+-------+-----------+--------+--------+ |
| *[Hapus] hanya untuk admin                                       |
| [Cetak] menghasilkan PDF disposisi dengan kop instansi           |
+------------------------------------------------------------------+
```

### 7.7 Buku Agenda Surat Masuk

```
+------------------------------------------------------------------+
| Agenda Surat Masuk                                               |
|                    [Download Excel]  [Cetak PDF]                 |
| +----+---------+--------+------+--------+-------+-------+------+ |
| |No. |Isi      |Asal    |Kode  |No.Surat|Tgl Str|Tgl Trm|Pnrm  | |
| +----+---------+--------+------+--------+-------+-------+------+ |
| | 1  |...      |...     |SU    |001/... |2024-01|2024-01|Budi  | |
| +----+---------+--------+------+--------+-------+-------+------+ |
|  "Penerima" = nama user yang menginput (bukan penerima surat)    |
|  Tidak ada filter tanggal -- semua data tampil sekaligus         |
+------------------------------------------------------------------+
```

### 7.8 Form Tambah Pengguna (admin only)

```
+-------------------------------------------------------------+
| Tambah Data Pengguna                                        |
| ----------------------------------------------------------- |
|  Kolom Kiri              | Kolom Kanan                     |
|  Nama                    | Password                        |
|  [__________________]    | [__________________]            |
|                          |                                 |
|  Email                   | Level                           |
|  [__________________]    | [Administrator v]               |
|                          |  -> opsi: Administrator/Petugas |
| ----------------------------------------------------------- |
|  [SIMPAN]   [BATAL]                                         |
|  Catatan: tidak ada field konfirmasi password               |
+-------------------------------------------------------------+
```

### 7.9 Galeri File Surat Masuk

```
+------------------------------------------------------------+
| Galeri Surat Masuk                                         |
| ---------------------------------------------------------- |
| +--------+ +--------+ +--------+ +--------+ +--------+     |
| |[img 1] | |[img 2] | |[img 3] | |[img 4] | |[img 5] |     |
| |100x150 | |100x150 | | (doc:  | |        | |        |     |
| |        | |        | |  tidak | |        | |        |     |
| |        | |        | | tampil)| |        | |        |     |
| +--------+ +--------+ +--------+ +--------+ +--------+     |
| |[Detail]| |[Detail]| |[Detail]| |[Detail]| |[Detail]|     |
| +--------+ +--------+ +--------+ +--------+ +--------+     |
|                                                            |
| * File .doc/.docx/.pdf tidak dapat ditampilkan,            |
|   klik "Lihat Detail File" untuk mengaksesnya              |
| Klik gambar -> Ekko Lightbox popup (hanya untuk gambar)    |
+------------------------------------------------------------+
```

---

## 8. Catatan Teknis

### Bug & Masalah

1. **Update disposisi via GET:** Route `disposisi/{suratmasuk}/{id}` untuk update menggunakan method GET, bukan PUT/PATCH. Operasi update bisa dipanggil hanya dengan mengetik URL di browser, dan CSRF protection tidak berlaku untuk GET request. Ini inkonsistensi desain yang berpotensi menjadi celah keamanan.

2. **Hapus tidak menghapus file fisik:** Method `delete()` di SuratMasukController dan SuratKeluarController hanya menghapus record DB. File yang terupload di `/datasuratmasuk/` dan `/datasuratkeluar/` tidak ikut dihapus — menyebabkan penumpukan file orphan di disk.

3. **Update password jika field kosong:** Di PenggunaController@update, `Hash::make($request->input('password'))` selalu dijalankan. Jika admin mengedit pengguna tanpa mengisi password baru, password menjadi hash dari string kosong. Seharusnya ada pengecekan `if ($request->filled('password'))`.

4. **N+1 query di agenda:** View `suratmasuk/agenda.blade.php` memanggil `$suratmasuk->users->name` di dalam loop tanpa eager loading. Controller memanggil `SuratMasuk::all()` tanpa `->with('users')`. Untuk data banyak, ini membuat 1 query per baris (N+1 problem).

5. **Duplikasi route:** `/suratmasuk` dan `/suratmasuk/index` mengarah ke method yang sama. Demikian juga untuk suratkeluar dan klasifikasi. Potensi konflik dengan route yang menggunakan segment `{id}` dinamis.

6. **Route konflik `viewAlldownloadfile`:** Route ini didefinisikan dua kali (untuk suratmasuk dan suratkeluar). Laravel menggunakan route yang terakhir terdefinisi — route suratmasuk tertimpa oleh suratkeluar.

7. **Nama flash session menyesatkan:** Variabel session `sukses` digunakan untuk pesan error login (`'Email atau Password Salah!'`). Nama variable sama sekali tidak merepresentasikan error.

8. **Tidak ada proteksi hapus di route level:** Tombol hapus hanya disembunyikan di UI untuk petugas, tetapi route `GET /suratmasuk/{id}/delete` bisa diakses oleh petugas jika tahu URL-nya karena middleware hanya cek `auth + checkRole:admin,petugas`, tidak membedakan hapus dari operasi lain.

9. **Import klasifikasi kemungkinan tidak berfungsi:** Di KlasifikasiController@import, baris yang benar di-comment dan diganti dengan pemanggilan metode yang salah (`Excel::import(new KlasifikasiImport)->import(...)`). Fitur import Excel untuk klasifikasi sangat mungkin menghasilkan error saat dijalankan.

10. **`Instansi::first()` sebagai asumsi:** Semua fitur cetak PDF mengambil `Instansi::first()`. Jika tabel instansis kosong, akan menghasilkan error null pointer saat mencetak PDF agenda atau disposisi.

11. **`$suratmasuk->update($request->all())` tanpa filtering:** Pada SuratMasukController@update dan SuratKeluarController@update, `$request->all()` langsung dipass ke `update()`. Meskipun ada `$fillable` di model, ini adalah pola yang berisiko karena input file juga masuk ke `all()` sebagai UploadedFile object, bukan string nama file.

### Hal Menarik / Non-Standar

- **Tidak menggunakan Laravel Resource Controller sepenuhnya:** Surat masuk/keluar menggunakan method custom (`tambah`, `delete`) dengan route manual, bukan konvensi resource route Laravel (`store`, `destroy`). Disposisi dan instansi/pengguna menggunakan resource route.

- **AdminLTE dimuat sebagai asset statis:** File AdminLTE disimpan di `/public/adminlte/` (bukan via npm/yarn), sehingga tidak terkelola oleh webpack/mix dan tidak mendapat benefit minification/versioning.

- **Maatwebsite Excel versi 2.x:** Versi sangat lama dengan API berbeda (`Excel::create()`, `$sheet->fromArray()`). Versi 3.x mengubah API secara drastis dan tidak backward compatible.

- **Kode klasifikasi bukan FK formal:** Kolom `kode` di suratmasuk/suratkeluar menyimpan nilai kode klasifikasi sebagai string, bukan FK integer ke `klasifikasi.id`. Jika kode klasifikasi diubah, data historis surat tidak ikut terupdate secara otomatis.

- **Tidak ada fitur pencarian/filter tanggal di agenda:** Seluruh data ditampilkan sekaligus ke client, filtering hanya via jQuery DataTables di sisi browser. Untuk data besar ini tidak skalabel.

- **Footer hardcode instansi:** Footer menyebut "Teknik Informatika Unirow Tuban" di layout master secara hardcode, tidak diambil dari data tabel instansis.

- **Dashboard menggunakan `DB::table()` langsung di view:** File `dashboard.blade.php` memanggil `DB::table('suratmasuk')->count()` langsung di template, bukan melalui controller. Ini melanggar separation of concern MVC.

- **Galeri file tidak membedakan tipe file:** Semua file (gambar maupun dokumen) ditampilkan dalam grid galeri dengan tag `<img>`. Dokumen Word/PDF tidak bisa dirender sebagai gambar, sehingga ditampilkan sebagai broken image dengan alt text sebagai penjelasan.
