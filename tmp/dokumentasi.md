# Dokumentasi Sistem Surat OpenSID

Dokumen ini menjelaskan arsitektur, desain database, sub-fitur, flow bisnis, dan hubungan antar modul dari sistem surat pada aplikasi OpenSID (Sistem Informasi Desa). Target pembaca: developer yang akan memahami, memodifikasi, atau mengembangkan ulang fitur ini.

---

## 1. Gambaran Umum

Sistem surat di OpenSID terdiri dari **enam sub-modul** yang saling terhubung:

| Sub-modul | Controller | URL | Fungsi Inti |
|---|---|---|---|
| **Surat Master** | `Surat_master.php` | `/surat_master` | Kelola template surat (format/nama/file RTF) |
| **Cetak Surat** | `Surat.php` | `/surat` | Pilih warga + isi form + generate dokumen RTF |
| **Arsip Layanan** | `Keluar.php` | `/keluar` | Lihat semua surat yang pernah dicetak (log) |
| **Surat Masuk** | `Surat_masuk.php` | `/surat_masuk` | Buku agenda surat masuk dari instansi luar |
| **Surat Keluar** | `Surat_keluar.php` | `/surat_keluar` | Buku agenda surat keluar ke instansi luar |
| **Permohonan Surat** | `Surat_mohon.php` | `/surat_mohon` | Kelola syarat permohonan surat layanan mandiri |

Semua controller mewarisi `Admin_Controller`, sehingga otomatis memerlukan autentikasi sesi dan memeriksa hak akses berdasarkan `modul_ini` dan `sub_modul_ini`.

---

## 2. Arsitektur Sistem

### 2.1 Stack Teknologi

- **Framework**: CodeIgniter 3 (CI3), pola MVC
- **Backend**: PHP 7.4
- **Database**: MariaDB / MySQL
- **Template surat**: File `.rtf` (Rich Text Format) — bukan HTML
- **Konversi PDF** (opsional): LibreOffice headless (`--convert-to pdf`)
- **Lampiran PDF**: Library `html2pdf` untuk lampiran berbasis PHP form
- **Upload**: CI3 Upload library, file disimpan ke `desa/arsip/`

### 2.2 Struktur File

```
donjo-app/
├── controllers/
│   ├── Surat.php                      # Cetak surat (layanan ke warga)
│   ├── Surat_master.php               # Manajemen template surat
│   ├── Surat_mohon.php                # Permohonan surat mandiri
│   ├── Keluar.php                     # Arsip layanan surat (log)
│   └── buku_umum/
│       ├── Surat_masuk.php            # Agenda surat masuk
│       └── Surat_keluar.php           # Agenda surat keluar
├── models/
│   ├── Surat_model.php                # Logic cetak + data penduduk
│   ├── Surat_master_model.php         # CRUD template + import filesystem
│   ├── Keluar_model.php               # Query log_surat + arsip
│   ├── Surat_masuk_model.php          # CRUD surat_masuk + disposisi
│   ├── Surat_keluar_model.php         # CRUD surat_keluar + ekspedisi
│   ├── Lapor_model.php                # CRUD ref_syarat_surat
│   └── Penomoran_surat_model.php      # Generator nomor surat
└── views/
    ├── surat/                         # Views cetak surat & arsip layanan
    │   ├── format_surat.php           # Halaman index — pilih jenis surat
    │   ├── form_surat.php             # Form isian surat per warga
    │   ├── form/                      # Partial view per jenis surat
    │   ├── surat_keluar.php           # Tabel arsip layanan
    │   ├── surat_keluar_perorangan.php # Riwayat per warga
    │   ├── surat_keluar_graph.php     # Grafik statistik
    │   ├── panduan.php                # Panduan penggunaan
    │   └── cetak.php                  # Template cetak rekap
    ├── surat_master/                  # Views template & permohonan surat
    │   ├── table.php                  # Tabel master surat
    │   ├── form.php                   # Form tambah/edit surat
    │   ├── kode_isian.php             # Tampil kode isian form
    │   ├── surat_mohon_table.php      # Tabel syarat surat
    │   └── surat_mohon_form.php       # Form syarat surat
    ├── surat_masuk/                   # Views agenda surat masuk
    │   ├── table.php
    │   ├── form.php
    │   ├── disposisi.php
    │   ├── surat_masuk_print.php
    │   └── surat_masuk_excel.php
    └── surat_keluar/                  # Views agenda surat keluar
        ├── table.php
        ├── form.php
        ├── surat_keluar_print.php
        └── surat_keluar_excel.php

template-surat/
├── raw/                               # File template kosong (form.raw, template.rtf)
├── surat_ket_pengantar/               # Folder per jenis surat sistem
│   ├── surat_ket_pengantar.rtf        # Template RTF dengan kode isian [nama], dll
│   ├── data_form_*.php                # Data PHP yang di-include saat render form
│   └── data_rtf_*.php                 # Data PHP yang di-include saat generate RTF
└── [30+ folder jenis surat lainnya]

desa/
├── arsip/                             # Hasil cetak surat (file .rtf / .pdf)
├── template-surat/                    # Override template surat per-desa (LOKASI_SURAT_DESA)
│   └── surat_ket_penduduk/            # Contoh override desa
└── session/                           # Session PHP disimpan di sini
```

### 2.3 Konstanta Penting

| Konstanta | Nilai Contoh | Keterangan |
|---|---|---|
| `LOKASI_ARSIP` | `desa/arsip/` | Tempat hasil surat tersimpan |
| `LOKASI_SURAT_DESA` | `desa/template-surat/` | Override template surat per-desa |
| `LOKASI_LOGO_DESA` | `desa/upload/logo/` | Logo desa untuk sisipan RTF |
| `LOKASI_USER_PICT` | `desa/upload/user_pict/` | Foto penduduk untuk sisipan RTF |

---

## 3. Desain Database

### 3.1 Tabel-tabel Surat

#### `tweb_surat_format` — Master template surat

Tabel utama yang mendefinisikan semua jenis surat yang tersedia di sistem.

```sql
CREATE TABLE `tweb_surat_format` (
  `id`                  int(11)      PK AUTO_INCREMENT,
  `nama`                varchar(100) -- Nama tampil, e.g. "Keterangan Pengantar"
  `url_surat`           varchar(100) UNIQUE  -- Slug URL + nama folder, e.g. "surat_ket_pengantar"
  `kode_surat`          varchar(10)  -- Kode resmi, e.g. "S-01"
  `lampiran`            varchar(100) -- File PHP lampiran (CSV jika >1), e.g. "f-1.08.php,f-1.25.php"
  `kunci`               tinyint(1)   -- 0=aktif, 1=dikunci (tidak bisa digunakan)
  `favorit`             tinyint(1)   -- 1=tampil di list favorit
  `jenis`               tinyint(2)   -- 1=surat sistem bawaan, 2=surat custom desa
  `mandiri`             tinyint(1)   -- 1=tersedia untuk layanan mandiri warga
  `masa_berlaku`        int(3)       -- Angka masa berlaku
  `satuan_masa_berlaku` varchar(15)  -- 'H'=hari, 'M'=minggu, 'B'=bulan, 'T'=tahun
)
```

Data sistem berisi 40+ jenis surat (S-01 s/d S-49) ditambah beberapa surat non-warga.

**Catatan penting:**
- `url_surat` berfungsi sebagai identifier universal — nama folder template, nama file RTF, dan referensi di `log_surat` semua menggunakan nilai ini.
- `jenis=1`: surat sistem (RTF default di `template-surat/{url_surat}/`). Desa bisa override ke `desa/template-surat/{url_surat}/`.
- `jenis=2`: surat custom desa, hanya ada di `desa/template-surat/{url_surat}/`.
- Saat insert surat baru, sistem otomatis membuat folder `desa/template-surat/{url_surat}/` dan menyalin file template dari `template-surat/raw/`.

---

#### `log_surat` — Log surat yang dicetak (Arsip Layanan)

Setiap kali staf mencetak/mengekspor surat lewat modul Cetak Surat, satu baris dicatat di sini.

```sql
CREATE TABLE `log_surat` (
  `id`               int(11)       PK AUTO_INCREMENT,
  `id_format_surat`  int(3)        -- FK ke tweb_surat_format.id
  `id_pend`          int(11)       -- FK ke tweb_penduduk.id (NULL jika non-warga)
  `id_pamong`        int(4)        -- FK ke tweb_desa_pamong.pamong_id (penandatangan)
  `id_user`          int(4)        -- FK ke user.id (yang mencetak)
  `tanggal`          timestamp     -- Waktu cetak
  `bulan`            varchar(2)    -- Bulan cetak (redundan dengan tanggal)
  `tahun`            varchar(4)    -- Tahun cetak (redundan)
  `no_surat`         varchar(20)   -- Nomor surat yang diberikan
  `nama_surat`       varchar(100)  -- Nama file arsip, e.g. "surat_ket_pengantar_320240...rtf"
  `lampiran`         varchar(100)  -- Nama file lampiran PDF (jika ada)
  `nik_non_warga`    decimal(16,0) -- NIK non-warga (jika bukan penduduk terdaftar)
  `nama_non_warga`   varchar(100)  -- Nama non-warga
  `keterangan`       varchar(200)  -- Keperluan/keterangan surat
)
```

**Catatan penting:**
- Nama file `nama_surat` diformat: `{url_surat}_{nik}_{tanggal}_{nomor_surat}.rtf` — unik per kombinasi tersebut.
- Jika log dengan `nama_surat` yang sama sudah ada, sistem melakukan UPDATE (bukan INSERT baru) — setiap versi file arsip hanya punya satu entri log.
- Tidak ada FK constraint eksplisit di DDL, join dilakukan di query.

---

#### `permohonan_surat` — Permohonan surat dari layanan mandiri

```sql
CREATE TABLE `permohonan_surat` (
  `id`          int(11)     PK AUTO_INCREMENT,
  `id_pemohon`  int(11)     -- FK ke tweb_penduduk.id
  `id_surat`    int(11)     -- FK ke tweb_surat_format.id
  `isian_form`  text        -- Isian form surat (format teks/JSON)
  `status`      tinyint(1)  -- 0=menunggu, 1=diproses, 2=menunggu ttd, 3=selesai
  `keterangan`  text        -- Catatan dari staf desa
  `no_hp_aktif` varchar(50) -- Nomor HP pemohon
  `syarat`      text        -- Dokumen syarat yang dilampirkan (daftar nama file)
  `created_at`  timestamp
  `updated_at`  timestamp
)
```

---

#### `surat_masuk` — Buku agenda surat masuk

```sql
CREATE TABLE `surat_masuk` (
  `id`                 int(11)       PK AUTO_INCREMENT,
  `nomor_urut`         smallint(5)   -- Nomor urut agenda (sequential per tahun)
  `tanggal_penerimaan` date          -- Tanggal surat diterima desa
  `nomor_surat`        varchar(35)   -- Nomor surat dari pengirim
  `kode_surat`         varchar(10)   -- Kode klasifikasi surat
  `tanggal_surat`      date          -- Tanggal surat dibuat oleh pengirim
  `pengirim`           varchar(100)  -- Nama instansi/orang pengirim
  `isi_singkat`        varchar(200)  -- Ringkasan isi surat
  `isi_disposisi`      varchar(200)  -- Catatan disposisi dari kepala desa
  `berkas_scan`        varchar(100)  -- Nama file scan (tersimpan di LOKASI_ARSIP)
)
```

---

#### `disposisi_surat_masuk` — Penerima disposisi surat masuk

```sql
CREATE TABLE `disposisi_surat_masuk` (
  `id`              int(11)       PK AUTO_INCREMENT,
  `id_surat_masuk`  int(11)       -- FK ke surat_masuk.id
  `id_desa_pamong`  int(11)       -- FK ke tweb_desa_pamong.pamong_id
  `disposisi_ke`    varchar(255)  -- Jabatan tujuan disposisi (teks)
)
```

Satu surat masuk bisa didisposisikan ke beberapa pamong (relasi 1-to-many).

---

#### `surat_keluar` — Buku agenda surat keluar

```sql
CREATE TABLE `surat_keluar` (
  `id`                 int(11)      PK AUTO_INCREMENT,
  `nomor_urut`         smallint(5)  -- Nomor urut agenda
  `nomor_surat`        varchar(35)  -- Nomor surat resmi
  `kode_surat`         varchar(10)  -- Kode klasifikasi
  `tanggal_surat`      date         -- Tanggal surat dibuat
  `tanggal_catat`      timestamp    -- Waktu pencatatan di sistem
  `tujuan`             varchar(100) -- Nama instansi/orang tujuan
  `isi_singkat`        varchar(200) -- Ringkasan isi surat
  `berkas_scan`        varchar(100) -- Nama file scan
  `ekspedisi`          tinyint(1)   -- 0=belum dikirim, 1=sudah masuk ekspedisi
  `tanggal_pengiriman` date         -- Tanggal pengiriman
  `tanda_terima`       varchar(200) -- Keterangan tanda terima
  `keterangan`         varchar(500)
  `created_at`         timestamp
  `created_by`         int(11)      -- FK ke user.id
  `updated_at`         timestamp
  `updated_by`         int(11)      -- FK ke user.id
)
```

---

#### `ref_syarat_surat` — Referensi jenis syarat surat

```sql
CREATE TABLE `ref_syarat_surat` (
  `ref_syarat_id`    int(1) unsigned  PK AUTO_INCREMENT,
  `ref_syarat_nama`  varchar(255)     -- e.g. "Fotokopi KK", "Surat Pengantar RT/RW"
)
```

Data awal berisi 12 jenis syarat standar. Dikelola melalui modul Surat Mohon.

---

#### `syarat_surat` — Mapping syarat per jenis surat

```sql
CREATE TABLE `syarat_surat` (
  `id`              int(10)  PK AUTO_INCREMENT,
  `surat_format_id` int(10)  -- FK ke tweb_surat_format.id (ON DELETE CASCADE)
  `ref_syarat_id`   int(10)  -- FK ke ref_syarat_surat.ref_syarat_id
)
```

Tabel pivot many-to-many: satu jenis surat bisa punya banyak syarat. CASCADE DELETE: jika template surat dihapus, syaratnya ikut terhapus.

---

#### `klasifikasi_surat` — Kode klasifikasi arsip

```sql
CREATE TABLE `klasifikasi_surat` (
  `id`      int(4)       PK AUTO_INCREMENT,
  `kode`    varchar(50)  -- e.g. "000", "001", "001.1"
  `nama`    varchar(250) -- e.g. "UMUM", "Lambang"
  `uraian`  mediumtext
  `enabled` int(2)       DEFAULT 1
)
```

Digunakan di surat masuk dan surat keluar sebagai dropdown `kode_surat`. Bukan FK constraint — hanya disimpan sebagai teks di `surat_masuk.kode_surat`.

---

### 3.2 Diagram Relasi Antar Tabel

```
tweb_surat_format
  id (PK)
  url_surat (UNIQUE)
     │
     ├──(id_format_surat)── log_surat
     │                         ├─ id_pend ──── tweb_penduduk
     │                         ├─ id_pamong ── tweb_desa_pamong
     │                         └─ id_user ──── user
     │
     ├──(surat_format_id)── syarat_surat ──(ref_syarat_id)── ref_syarat_surat
     │
     └──(id_surat)── permohonan_surat ──(id_pemohon)── tweb_penduduk

surat_masuk
  id (PK)
  └──(id_surat_masuk)── disposisi_surat_masuk ──(id_desa_pamong)── tweb_desa_pamong

surat_keluar  [berdiri sendiri, tidak ada FK ke modul surat lain]
  ├─ created_by ── user
  └─ updated_by ── user

klasifikasi_surat  [digunakan via lookup teks, bukan FK]
```

---

## 4. Sub-fitur Detail

### 4.1 Surat Master

**Controller**: `donjo-app/controllers/Surat_master.php`
**Model**: `donjo-app/models/Surat_master_model.php`
**Views**: `donjo-app/views/surat_master/`
**URL prefix**: `/surat_master`
**Modul**: `modul_ini=4`, `sub_modul_ini=30`

#### Fungsi

Manajemen template surat — menambah, mengubah, menghapus, mengunci, dan mengatur jenis surat yang tersedia di sistem.

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index()` | GET | `/surat_master` | Tabel semua template surat dengan filter/paginasi |
| `form($url)` | GET | `/surat_master/form/{url}` | Form tambah/edit template |
| `insert()` | POST | `/surat_master/insert` | Simpan template baru |
| `update($url)` | POST | `/surat_master/update/{url}` | Update template |
| `upload($url)` | POST | `/surat_master/upload/{url}` | Upload file RTF custom desa |
| `delete($url)` | GET | `/surat_master/delete/{url}` | Hapus template dari DB + folder |
| `delete_all()` | POST | `/surat_master/delete_all` | Hapus banyak template sekaligus |
| `kode_isian($url)` | GET | `/surat_master/kode_isian/{url}` | Tampilkan kode isian dari form surat |
| `lock($id, $val)` | GET | `/surat_master/lock/{id}/{val}` | Toggle kunci surat (aktif/nonaktif) |
| `favorit($id, $val)` | GET | `/surat_master/favorit/{id}/{val}` | Toggle favorit |
| `p_insert()` | POST | `/surat_master/p_insert` | Tambah syarat surat |
| `p_update()` | POST | `/surat_master/p_update` | Update syarat surat |
| `p_delete()` | POST | `/surat_master/p_delete` | Hapus syarat surat |
| `search()` | POST | `/surat_master/search` | Set session pencarian |
| `filter()` | POST | `/surat_master/filter` | Set session filter jenis |

#### Logika Insert Surat Baru

Proses di `Surat_master_model::insert()` saat operator menambah jenis surat baru:

1. Validasi nama unik, buat `url_surat` = `surat_` + nama lowercase (hapus karakter non-alfanumerik)
2. Buat folder di `LOKASI_SURAT_DESA/{url_surat}/`
3. Tentukan template sumber berdasarkan jenis pemohon:
   - `pemohon_surat = warga` → copy `template-surat/raw/template.rtf` dan `form.raw`
   - non-warga → copy `template-surat/raw/template_non_warga.rtf` dan `form_non_warga.raw`
4. Insert record ke `tweb_surat_format` dengan `jenis=2`

#### Logika Kode Isian

`Surat_master_model::get_kode_isian()` membaca file `data_form_{url}.php` menggunakan `simple_html_dom`, mengekstrak semua elemen input (name, type, id). Berguna bagi operator untuk mengetahui variabel apa yang bisa dipakai di template RTF.

#### Penjelasan Field Form Tambah Surat

**Sediakan di Layanan Mandiri** (`mandiri` tinyint 0/1)

Toggle apakah jenis surat ini bisa diajukan sendiri oleh warga lewat portal layanan mandiri (website publik desa) tanpa harus datang langsung ke kantor. Jika dicentang (`mandiri=1`), surat ini muncul di daftar pilihan portal mandiri dan warga bisa mengisi formulirnya secara online. Jika tidak dicentang, surat hanya bisa dibuat oleh operator dari dalam admin panel.

---

**Masa Berlaku Default** (disimpan di dua kolom)

- `masa_berlaku` — angkanya (int), misal: `1`, `3`, `30`
- `satuan_masa_berlaku` — satuannya (varchar): `'H'` = hari, `'M'` = minggu, `'B'` = bulan, `'T'` = tahun

Menunjukkan berapa lama surat yang diterbitkan masih berlaku. Nilai ini dibaca kembali di form cetak surat via `Surat_model::masa_berlaku_surat()` sebagai informasi bagi operator. Nilai ini **tidak otomatis dicetak** di dokumen RTF — harus secara eksplisit ditambahkan ke template RTF jika dikehendaki.

---

**Pemohon Surat** (warga vs bukan warga)

Pilihan ini menentukan file template mana yang di-copy ke folder surat baru saat insert:

| Pilihan | Template RTF | Template Form |
|---|---|---|
| Warga | `template-surat/raw/template.rtf` | `template-surat/raw/form.raw` |
| Bukan Warga | `template-surat/raw/template_non_warga.rtf` | `template-surat/raw/form_non_warga.raw` |

Dampak ke UX cetak surat: form **warga** menyediakan pencarian NIK (lookup ke `tweb_penduduk`), form **bukan warga** menyediakan input manual nama dan NIK. Di `log_surat`, surat bukan warga mengisi kolom `nik_non_warga` dan `nama_non_warga` — bukan `id_pend`.

---

### 4.2 Cetak Surat (Layanan Surat)

**Controller**: `donjo-app/controllers/Surat.php`
**Model**: `donjo-app/models/Surat_model.php`
**Views**: `donjo-app/views/surat/`
**URL prefix**: `/surat`
**Modul**: `modul_ini=4`, `sub_modul_ini=31`

#### Fungsi

Proses utama penerbitan surat layanan ke warga: pilih warga, isi form, generate dokumen, simpan arsip, catat log.

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index()` | GET | `/surat` | Halaman pilih jenis surat (daftar + favorit) |
| `form($url)` | GET/POST | `/surat/form/{url}` | Form isian surat untuk NIK tertentu |
| `doc($url)` | POST | `/surat/doc/{url}` | Generate dan download surat |
| `periksa_doc($id, $url)` | POST | `/surat/periksa_doc/{id}/{url}` | Update status permohonan → cetak surat |
| `search()` | POST | `/surat/search` | Redirect ke form dengan NIK tertentu |
| `panduan()` | GET | `/surat/panduan` | Halaman panduan penggunaan |
| `favorit($id, $k)` | GET | `/surat/favorit/{id}/{k}` | Toggle favorit (delegasi ke Surat_master_model) |
| `format_nomor_surat()` | POST (AJAX) | `/surat/format_nomor_surat` | Preview format nomor surat |
| `list_penduduk_ajax()` | GET (AJAX) | `/surat/list_penduduk_ajax` | Dropdown Select2 pencarian penduduk |
| `list_penduduk_bersurat_ajax()` | GET (AJAX) | `/surat/list_penduduk_bersurat_ajax` | Dropdown hanya penduduk yang pernah bersurat |
| `nomor_surat_duplikat()` | POST (AJAX) | `/surat/nomor_surat_duplikat` | Validasi nomor surat tidak duplikat |

#### Flow Generate Surat

```
POST /surat/doc/{url}
  │
  ├─ cetak_doc($url)
  │    ├─ Tentukan id_pend dari $_POST atau $_SESSION (untuk surat khusus):
  │    │   surat_ket_kelahiran → id_ibu atau id_bayi
  │    │   surat_ket_nikah → id_pria atau id_wanita
  │    │   surat_kuasa → id_pemberi_kuasa atau id_penerima_kuasa
  │    │
  │    ├─ Surat_model::buat_surat($url, &$nama_surat, &$lampiran)
  │    │    ├─ get_data_untuk_surat($url)
  │    │    │    ├─ get_surat($url) → data dari tweb_surat_format
  │    │    │    ├─ get_data_surat($id_pend) → data lengkap penduduk (20+ join)
  │    │    │    ├─ get_data_ayah($id) dan get_data_ibu($id)
  │    │    │    └─ format_penomoran_surat() → format nomor resmi
  │    │    │
  │    │    ├─ lampiran($data, ...) → jika ada lampiran PHP:
  │    │    │    ├─ include get_data_lampiran.php
  │    │    │    ├─ render PHP template ke HTML (output buffer)
  │    │    │    └─ html2pdf → simpan ke LOKASI_ARSIP/{nama}_lampiran.pdf
  │    │    │
  │    │    └─ surat_utama($data, &$nama_surat)
  │    │         ├─ surat_rtf($data) → buffer RTF dengan substitusi penuh
  │    │         ├─ Tulis ke LOKASI_ARSIP/{nama_surat}.rtf
  │    │         └─  --convert-to pdf) → ganti .rtf dengan .pdf jika berhasil
  │    │
  │    ├─ Keluar_model::log_surat($log_surat) → INSERT atau UPDATE log_surat
  │    │
  │    └─ Kirim file ke browser:
  │         Jika ada lampiran → ZIP kedua file → download ZIP
  │         Jika tidak → header Location ke file arsip
```

#### Mekanisme Substitusi RTF (Surat_model::surat_rtf)

File `.rtf` dibaca sebagai string, lalu kode isian `[nama_kode]` diganti dengan nilai sebenarnya:

1. **Bersihkan kode isian** — `bersihkan_kode_isian()`: Word/LibreOffice terkadang menyisipkan karakter RTF di tengah `[kode_isian]`, fungsi ini membersihkannya dengan state machine character-by-character + regex.

2. **Sisipkan kop surat** — ganti `[kop_surat]` dengan konten `template-surat/raw/kop_surat_auto.rtf`.

3. **Sisipkan logo** — ganti placeholder PNG hex dalam RTF dengan hex-encoded bytes logo desa yang sebenarnya (lokasi logo dikenali dari byte signature PNG akhiran yang spesifik).

4. **Sisipkan foto** — ganti placeholder foto dengan foto penduduk (opsional, berdasarkan checkbox `tampil_foto`).

5. **Data khusus** — include `data_rtf_{url}.php` untuk menambahkan data spesifik per jenis surat.

6. **Substitusi massal via `str_replace`**:
   - Data surat: `[kode_surat]`, `[judul_surat]`, `[tgl_surat]`, `[nomor_surat]`, `[format_nomor_surat]`
   - Data desa: `[nama_des]`, `[alamat_desa]`, `[nama_kab]`, `[nama_kecamatan]`, `[telepon_desa]`, dll (20+ kode)
   - Data penduduk: `[nama]`, `[no_ktp]`, `[alamat]`, `[tempatlahir]`, `[tanggallahir]`, `[ttl]`, `[sex]`, `[agama]`, `[pekerjaan]`, `[pendidikan]`, `[status]`, `[no_kk]`, `[rt]`, `[rw]`, `[dusun]`, `[gol_darah]`, `[usia]`, dll

7. **Substitusi case-sensitive** via `case_replace()`:
   - `[SEBUTAN_DESA]` → `KAMPUNG` (semua huruf besar)
   - `[Sebutan_desa]` → `Kampung` (huruf pertama besar)
   - `[sebutan_desa]` → `kampung` (semua huruf kecil)

8. **Substitusi input form** — setiap `$_POST[key]` juga dicoba sebagai `[form_key]` dan `[key]`. Khusus field tanggal (berlaku_dari, tanggal_lahir, tgl_nikah, dll), nilai diformat ulang ke format Indonesia.

#### Resolusi Lokasi Template (Prioritas)

Saat mencari file RTF dan form PHP:
1. `desa/template-surat/{url}/` (override per-desa) — dipakai jika ada
2. `template-surat/{url}/` (bawaan sistem) — fallback

Fungsi pembantu: `SuratExportDesa($url)` mengembalikan path file RTF di folder desa jika ada.

---

### 4.3 Arsip Layanan Surat

**Controller**: `donjo-app/controllers/Keluar.php`
**Model**: `donjo-app/models/Keluar_model.php`
**Views**: `donjo-app/views/surat/surat_keluar.php`, `surat_keluar_perorangan.php`, `surat_keluar_graph.php`
**URL prefix**: `/keluar`
**Modul**: `modul_ini=4`, `sub_modul_ini=32`

#### Fungsi

Menampilkan semua riwayat surat yang pernah dicetak melalui modul Cetak Surat. Fungsi sebagai arsip/riwayat layanan desa ke warga.

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index($p, $o)` | GET | `/keluar` | Daftar semua log surat (filter tahun/jenis) |
| `perorangan($nik)` | GET/POST | `/keluar/perorangan/{nik}` | Riwayat surat per warga |
| `edit_keterangan($id)` | GET | `/keluar/edit_keterangan/{id}` | Form edit keterangan (AJAX modal) |
| `update_keterangan($id)` | POST | `/keluar/update_keterangan/{id}` | Simpan perubahan keterangan |
| `delete($p, $o, $id)` | GET | `/keluar/delete/{p}/{o}/{id}` | Hapus log + file arsip dari disk |
| `cetak_surat_keluar($id)` | GET | `/keluar/cetak_surat_keluar/{id}` | Download ulang file RTF/PDF dari arsip |
| `unduh_lampiran($id)` | GET | `/keluar/unduh_lampiran/{id}` | Download lampiran PDF |
| `graph()` | GET | `/keluar/graph` | Grafik jumlah surat per jenis |
| `search()` | POST | `/keluar/search` | Set session pencarian |
| `filter()` | POST | `/keluar/filter` | Filter by tahun |
| `jenis()` | POST | `/keluar/jenis` | Filter by jenis surat |
| `cetak($aksi)` | POST | `/keluar/cetak/{aksi}` | Cetak/unduh rekap layanan |
| `dialog_cetak($aksi)` | GET | `/keluar/dialog_cetak/{aksi}` | Dialog pilih penandatangan |

#### Query Utama

```sql
SELECT u.*, n.nama AS nama, w.nama AS nama_user, n.nik AS nik,
       k.nama AS format, k.url_surat AS berkas, k.kode_surat,
       s.pamong_nama AS pamong, p.nama AS nama_pamong_desa
FROM log_surat u
LEFT JOIN tweb_penduduk n ON u.id_pend = n.id
LEFT JOIN tweb_surat_format k ON u.id_format_surat = k.id
LEFT JOIN tweb_desa_pamong s ON u.id_pamong = s.pamong_id
LEFT JOIN tweb_penduduk p ON s.id_pend = p.id
LEFT JOIN user w ON u.id_user = w.id
WHERE 1 [+ filter pencarian + tahun + jenis]
```

#### Logika Pencatatan Log (Keluar_model::log_surat)

Upsert cerdas untuk menghindari duplikasi:
- Jika `nama_surat` tidak kosong → cari log yang sama berdasarkan `nama_surat`
- Jika `nama_surat` kosong (mode cetak) → cari berdasarkan `id_format_surat + id_pend + no_surat + tanggal`
- Jika ditemukan → UPDATE, jika tidak → INSERT

---

### 4.4 Surat Masuk

**Controller**: `donjo-app/controllers/buku_umum/Surat_masuk.php`
**Model**: `donjo-app/models/Surat_masuk_model.php`
**Views**: `donjo-app/views/surat_masuk/`
**URL prefix**: `/surat_masuk`
**Modul**: `modul_ini=301`, `sub_modul_ini=302`

#### Fungsi

Buku agenda surat masuk dari instansi/pihak luar ke desa. Terpisah dari `log_surat` — ini adalah surat yang *diterima* desa, bukan yang *dikeluarkan* ke warga.

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index($p, $o)` | GET | `/surat_masuk` | Daftar surat masuk |
| `form($p, $o, $id)` | GET | `/surat_masuk/form/{p}/{o}/{id}` | Form tambah/edit |
| `insert()` | POST | `/surat_masuk/insert` | Simpan surat masuk baru |
| `update($p, $o, $id)` | POST | `/surat_masuk/update/{p}/{o}/{id}` | Update surat masuk |
| `upload($p, $o, $url)` | POST | `/surat_masuk/upload` | Upload berkas scan |
| `delete($p, $o, $id)` | GET | `/surat_masuk/delete/{p}/{o}/{id}` | Hapus surat + file scan |
| `delete_all($p, $o)` | POST | `/surat_masuk/delete_all` | Hapus banyak sekaligus |
| `search()` | POST | `/surat_masuk/search` | Set session pencarian |
| `filter()` | POST | `/surat_masuk/filter` | Filter by tahun penerimaan |
| `dialog_disposisi($o, $id)` | GET | `/surat_masuk/dialog_disposisi/{o}/{id}` | Dialog cetak lembar disposisi |
| `dialog_cetak($o)` | GET | `/surat_masuk/dialog_cetak` | Dialog cetak buku agenda |
| `dialog_unduh($o)` | GET | `/surat_masuk/dialog_unduh` | Dialog unduh Excel |
| `cetak($o)` | POST | `/surat_masuk/cetak/{o}` | Cetak buku agenda |
| `unduh($o)` | POST | `/surat_masuk/unduh/{o}` | Unduh Excel |
| `disposisi($id)` | POST | `/surat_masuk/disposisi/{id}` | Cetak lembar disposisi |
| `unduh_berkas_scan($id)` | GET | `/surat_masuk/unduh_berkas_scan/{id}` | Download berkas scan |
| `nomor_surat_duplikat()` | POST (AJAX) | `/surat_masuk/nomor_surat_duplikat` | Validasi nomor urut |

#### Fitur Disposisi

Saat insert/update, field `disposisi_kepada` (array jabatan dari form multi-select) diproses:
- Dipisah dari data surat masuk sebelum INSERT ke `surat_masuk`
- Untuk setiap jabatan: cari `pamong_id` dari `tweb_desa_pamong` berdasarkan jabatan, INSERT ke `disposisi_surat_masuk`
- Saat update: DELETE semua disposisi lama untuk id tersebut, INSERT ulang

Daftar ref disposisi yang tersedia: Sekretaris Desa, Kasi Pemerintahan, Kasi Kesejahteraan, Kasi Pelayanan, Kaur Keuangan, Kaur Tata Usaha dan Umum, Kaur Perencanaan, dan Kadus per dusun yang ada.

#### Keamanan File Upload

- Tipe yang diizinkan: `gif|jpg|jpeg|png|pdf`
- Ukuran maksimal: `max_upload() * 1024` KB
- Nama file diobfuskasi: suffix `__sid__` + unique ID ditambahkan sebelum ekstensi
- Konten PHP dicek via `isPHP()` sebelum upload diterima

---

### 4.5 Surat Keluar

**Controller**: `donjo-app/controllers/buku_umum/Surat_keluar.php`
**Model**: `donjo-app/models/Surat_keluar_model.php`
**Views**: `donjo-app/views/surat_keluar/`
**URL prefix**: `/surat_keluar`
**Modul**: `modul_ini=301`, `sub_modul_ini=302`

#### Fungsi

Buku agenda surat keluar dari desa ke instansi/pihak luar. Berbeda dari `log_surat` (arsip layanan ke warga) — ini adalah surat dinas korespondensi dengan pihak luar, dicatat manual oleh staf.

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index($p, $o)` | GET | `/surat_keluar` | Daftar surat keluar |
| `form($p, $o, $id)` | GET | `/surat_keluar/form/{p}/{o}/{id}` | Form tambah/edit |
| `insert()` | POST | `/surat_keluar/insert` | Simpan surat keluar baru |
| `update($p, $o, $id)` | POST | `/surat_keluar/update/{p}/{o}/{id}` | Update surat keluar |
| `upload($p, $o, $url)` | POST | `/surat_keluar/upload` | Upload berkas scan |
| `delete($p, $o, $id)` | GET | `/surat_keluar/delete/{p}/{o}/{id}` | Hapus surat + file scan |
| `delete_all($p, $o)` | POST | `/surat_keluar/delete_all` | Hapus banyak sekaligus |
| `search()` | POST | `/surat_keluar/search` | Set session pencarian |
| `filter()` | POST | `/surat_keluar/filter` | Filter by tahun surat |
| `dialog_cetak($o)` | GET | `/surat_keluar/dialog_cetak` | Dialog cetak buku agenda |
| `dialog_unduh($o)` | GET | `/surat_keluar/dialog_unduh` | Dialog unduh Excel |
| `cetak($o)` | POST | `/surat_keluar/cetak/{o}` | Cetak buku agenda |
| `unduh($o)` | POST | `/surat_keluar/unduh/{o}` | Unduh Excel |
| `unduh_berkas_scan($id)` | GET | `/surat_keluar/unduh_berkas_scan/{id}` | Download berkas scan |
| `untuk_ekspedisi($p, $o, $id)` | GET | `/surat_keluar/untuk_ekspedisi/{p}/{o}/{id}` | Tandai masuk ekspedisi, redirect ke `/ekspedisi` |
| `nomor_surat_duplikat()` | POST (AJAX) | `/surat_keluar/nomor_surat_duplikat` | Validasi nomor urut |

#### Fitur Khusus

- **Ekspedisi**: Surat keluar bisa ditandai "sudah dikirim" via `untuk_ekspedisi()` yang set `ekspedisi=1` di DB lalu redirect ke modul `/ekspedisi`.
- **Audit trail**: Kolom `created_by`, `updated_by` menyimpan id user, plus timestamp `created_at` dan `updated_at`.
- **Normalisasi nomor surat**: Fungsi `nomor_surat_keputusan()` dipanggil saat insert/update untuk membersihkan format nomor.

---

### 4.6 Permohonan Surat (Surat Mohon)

**Controller**: `donjo-app/controllers/Surat_mohon.php`
**Model**: `donjo-app/models/Lapor_model.php`
**Views**: `donjo-app/views/surat_master/surat_mohon_table.php`, `surat_mohon_form.php`
**URL prefix**: `/surat_mohon`
**Modul**: `modul_ini=4`, `sub_modul_ini=97`

#### Fungsi

Mengelola **daftar referensi syarat** yang diperlukan untuk mengajukan surat via layanan mandiri. Ini bukan permohonan yang diajukan warga — ini adalah konfigurasi master "jenis syarat apa yang ada di sistem".

**Catatan penting tentang naming**: Nama controller "Surat_mohon" agak menyesatkan. Model yang digunakan adalah `Lapor_model` yang mengoperasikan tabel `ref_syarat_surat`. Ini adalah master data syarat (e.g., "Fotokopi KK"), bukan tabel permohonan aktual warga (`permohonan_surat`).

#### Daftar Actions

| Action | Method | URL | Keterangan |
|---|---|---|---|
| `index($p, $o)` | GET | `/surat_mohon` | Tabel daftar syarat surat |
| `form($p, $o, $id)` | GET | `/surat_mohon/form/{id}` | Form tambah/edit syarat |
| `insert()` | POST | `/surat_mohon/insert` | Simpan syarat baru ke ref_syarat_surat |
| `update($p, $o, $id)` | POST | `/surat_mohon/update/{id}` | Update syarat |
| `delete($p, $o, $id)` | GET | `/surat_mohon/delete/{id}` | Hapus syarat |
| `delete_all($p, $o)` | POST | `/surat_mohon/delete_all` | Hapus banyak |
| `search()` | POST | `/surat_mohon/search` | Pencarian |
| `filter()` | POST | `/surat_mohon/filter` | Filter |
| `user_lock($id)` | GET | `/surat_mohon/user_lock/{id}` | Nonaktifkan syarat |
| `user_unlock($id)` | GET | `/surat_mohon/user_unlock/{id}` | Aktifkan syarat |

---

## 5. Flow Bisnis End-to-End

### Flow A: Menerbitkan Surat Layanan ke Warga

```
[Operator Desa]

1. Buka /surat
   → Tampil daftar surat: favorit (kunci=0, favorit=1) + semua (kunci=0)
   → Data dari tweb_surat_format

2. Pilih jenis surat → buka /surat/form/{url}
   → Cari NIK warga via Select2 AJAX (/surat/list_penduduk_ajax?q=...)
   → Masukkan NIK → POST ke /surat/form/{url}
   → Surat_model::get_penduduk(NIK) ambil data dasar
   → include data_form_{url}.php → tambahkan data khusus ke tampilan form
   → Tampilkan form isian spesifik + sugesti nomor surat berikutnya

3. Isi form + submit → POST /surat/doc/{url}
   → Kumpulkan semua data dari DB (penduduk, ayah, ibu, desa, pamong)
   → Generate lampiran PDF (jika ada) via html2pdf
   → Generate file RTF via substitusi kode isian
   → Konversi ke PDF via LibreOffice (jika tersedia)
   → Simpan ke desa/arsip/
   → Catat ke log_surat (INSERT atau UPDATE)
   → Kirim file ke browser (download langsung)

4. Warga menerima surat (file RTF atau PDF)
```

### Flow B: Merekam Surat Masuk

```
[Staf Tata Usaha]

1. Terima surat fisik dari instansi luar
2. Buka /surat_masuk → Tambah Baru
   → Nomor urut agenda disugesti otomatis
3. Isi form: nomor urut, tanggal terima, nomor surat, pengirim,
   isi singkat, disposisi kepada (multi-select), upload scan
4. Submit → POST /surat_masuk/insert
   → Validasi + normalisasi tanggal
   → Upload berkas scan → rename dengan suffix unik
   → db.trans_start()
   → INSERT ke surat_masuk
   → INSERT ke disposisi_surat_masuk (per jabatan penerima)
   → db.trans_complete()
5. Surat tersimpan di buku agenda

[Cetak Lembar Disposisi]
6. Klik dialog disposisi → POST /surat_masuk/disposisi/{id}
   → Tampilkan view lembar disposisi dengan data pamong yang dituju
   → Operator print dari browser
```

### Flow C: Merekam Surat Keluar Dinas

```
[Staf Tata Usaha]

1. Buat surat dinas di luar sistem (Word, dsb)
2. Buka /surat_keluar → Tambah Baru
3. Isi form: nomor surat, tanggal, tujuan, isi singkat, upload scan
4. Submit → INSERT ke surat_keluar
5. Setelah dikirim → klik tombol Ekspedisi
   → SET ekspedisi=1 → redirect ke modul /ekspedisi
```

### Flow D: Permohonan Surat via Layanan Mandiri

```
[Warga - Portal Mandiri]
1. Login ke portal layanan mandiri
2. Pilih jenis surat (dari tweb_surat_format WHERE mandiri=1)
3. Lihat syarat yang diperlukan (dari syarat_surat + ref_syarat_surat)
4. Isi form + upload dokumen syarat
5. Submit → INSERT ke permohonan_surat (status=0, menunggu)

[Operator Desa]
6. Buka daftar permohonan masuk → lihat yang pending
7. Verifikasi kelengkapan dokumen → update status
8. Buka /surat/periksa_doc/{id}/{url}
   → Update status permohonan → status=2 (Menunggu Tandatangan)
   → Lanjut ke proses cetak surat (Flow A langkah 3)
9. Warga datang mengambil surat
```

---

## 6. Hubungan Antar Sub-modul

```
┌──────────────────────┐  mendefinisikan template  ┌────────────────────────┐
│    SURAT MASTER      │──────────────────────────▶│    CETAK SURAT         │
│    /surat_master     │                           │    /surat              │
│    tweb_surat_format │◀── toggle favorit ─────── │                        │
└──────────┬───────────┘                           └────────────┬───────────┘
           │                                                    │ cetak → INSERT log_surat
           │ syarat per surat                                   │
           ▼                                                    ▼
┌──────────────────────┐                        ┌──────────────────────────┐
│   SURAT MOHON        │ verifikasi             │   ARSIP LAYANAN          │
│   /surat_mohon       │──── lalu cetak ───────▶│   /keluar                │
│   ref_syarat_surat   │                        │   log_surat              │
└──────────────────────┘                        └──────────────────────────┘

┌──────────────────────┐                        ┌──────────────────────────┐
│   SURAT MASUK        │   ─── independen ───   │   SURAT KELUAR           │
│   /surat_masuk       │                        │   /surat_keluar          │
│   surat_masuk        │                        │   surat_keluar           │
└──────────┬───────────┘                        └─────────────┬────────────┘
           │ disposisi                                        │ ekspedisi
           ▼                                                  ▼
┌──────────────────────┐                        ┌──────────────────────────┐
│ disposisi_surat_masuk│                        │   MODUL EKSPEDISI        │
│ tweb_desa_pamong     │                        │   /ekspedisi             │
└──────────────────────┘                        └──────────────────────────┘
```

### Titik Integrasi Kunci

| Titik | Modul yang Terlibat | Keterangan |
|---|---|---|
| `tweb_surat_format` | Surat Master, Cetak Surat, Arsip Layanan | Tabel master yang dirujuk semua modul. `url_surat` adalah key lintas modul. |
| `log_surat` | Cetak Surat (write), Arsip Layanan (read) | Output cetak menjadi input arsip. Satu tabel, dua controller. |
| `syarat_surat` | Surat Master (CRUD), Layanan Mandiri (read) | Master mendefinisikan, portal mandiri menampilkan. |
| `Penomoran_surat_model` | Cetak Surat, Surat Masuk, Surat Keluar | Shared service untuk nomor surat, mencegah duplikasi. |
| `tweb_desa_pamong` | Cetak Surat (penandatangan), Surat Masuk (disposisi), Arsip Layanan (tampil nama) | Entitas pamong dipakai di berbagai modul. |
| `permohonan_surat` | Layanan Mandiri (write), Cetak Surat via `periksa_doc` (update status) | Permohonan warga di-approve operator lalu langsung dilanjutkan ke cetak. |

---

## 7. UI/UX Patterns

### 7.1 Layout dan Navigasi

- Semua halaman menggunakan `Admin_Controller::render()` yang membungkus header + sidebar + content + footer CI3.
- Surat Masuk dan Surat Keluar (modul Buku Umum) menggunakan wrapper berbeda: `bumindes/umum/main` dengan `main_content` sebagai partial content view.
- Menu aktif di sidebar ditentukan via `modul_ini` dan `sub_modul_ini` yang dicocokkan dengan data hak akses di database.
- Beberapa halaman menggunakan `set_minsidebar(1)` untuk menyembunyikan sidebar saat menampilkan form surat panjang.

### 7.2 Form Cetak Surat

- **Pencarian warga**: Select2 dengan lazy loading AJAX. Query `?q=keyword` ke `/surat/list_penduduk_ajax`, hasil 25 baris per halaman dengan paginasi `more`.
- **Filter gender**: Parameter `filter_sex=perempuan` untuk surat yang hanya untuk wanita.
- **Validasi nomor surat**: AJAX real-time ke `/surat/nomor_surat_duplikat` menampilkan warning jika nomor sudah dipakai.
- **Preview format nomor**: AJAX ke `/surat/format_nomor_surat` saat nomor diubah, menampilkan format lengkap (misal: `001/S-01/VI/2026`).
- **State multi-warga** via session: Surat tertentu (nikah, kelahiran) menyimpan id pihak kedua ke session (`$_SESSION['id_pria']`, `id_wanita`, `id_ibu`, dsb). Session ini direset saat membuka `/surat`.

### 7.3 Paginasi dan Filter

- Semua tabel mendukung **paginasi server-side** via parameter URL `$p` (halaman) dan `$o` (urutan kolom).
- **Pencarian dan filter** disimpan di `$_SESSION` agar tetap aktif saat navigasi ke halaman berikutnya.
- Tombol **Clear** (misal: `/surat_masuk/clear`, `/keluar/clear`) mereset semua session filter ke kondisi awal.
- Ordering via parameter `$o` integer: misal `1` = ASC, `2` = DESC per kolom tertentu.

### 7.4 Upload Berkas

- Upload dilakukan via modal AJAX — `form_upload` action merender view upload form, submit via POST normal.
- Nama file diobfuskasi dengan suffix `__sid__` + unique ID untuk mencegah direct URL guessing di browser.
- Saat download: `ambilBerkas()` helper mereconstruct nama file asli dari format obfuskasi untuk ditampilkan ke user.

### 7.5 Cetak dan Ekspor

- **Cetak buku agenda**: Dialog modal pilih pamong penandatangan dan pengetahui → POST → render view HTML print-friendly di tab baru.
- **Unduh Excel**: Sama seperti cetak, tapi view menghasilkan output dengan `Content-Type: application/vnd.ms-excel`.
- **Disposisi**: Modal dialog pilih pamong → POST → render view HTML lembar disposisi.
- **Rekap Arsip Layanan**: Dialog modal → POST ke `/keluar/cetak/{aksi}` → render `global/format_cetak` dengan data yang sudah difilter.

---

## 8. Catatan Teknis untuk Developer

### 8.1 Surat "Sistem" vs Surat "Custom Desa"

| `jenis` | Lokasi RTF Default | Override |
|---|---|---|
| `1` (sistem) | `template-surat/{url_surat}/{url_surat}.rtf` | `desa/template-surat/{url_surat}/` (opsional) |
| `2` (custom) | Tidak ada default sistem | Hanya di `desa/template-surat/{url_surat}/` |

Saat insert surat baru via Surat Master, `jenis` selalu diset `2` (custom desa).

### 8.2 Penomoran Surat

Tiga mode penomoran dikonfigurasi via `setting.penomoran_surat`:
- `1`: Nomor urut global untuk semua surat layanan di `log_surat`
- `2`: Nomor urut per jenis surat (per `url_surat`)
- `3`: Nomor urut global mencakup `log_surat` + `surat_masuk` + `surat_keluar`

Format nomor akhir (misal: `001/S-01/VI/2026`) dihasilkan oleh `Penomoran_surat_model::format_penomoran_surat()` berdasarkan konfigurasi desa.

### 8.3 Lampiran Surat

Field `lampiran` di `tweb_surat_format` berisi nama file PHP, bisa lebih dari satu dipisah koma (misal: `f-1.08.php,f-1.25.php`). Setiap file adalah template HTML yang dirender oleh `html2pdf` menjadi PDF terpisah. Jika ada lampiran, surat utama + lampiran di-zip sebelum dikirim ke browser.

### 8.4 Kode Isian RTF — Daftar Lengkap

Kode isian ditulis di file `.rtf` dalam kurung siku `[nama_kode]`. Kategori:

**Data surat:**
`[kode_surat]`, `[judul_surat]`, `[tgl_surat]`, `[tgl_surat_hijri]`, `[tahun]`, `[bulan_romawi]`, `[nomor_surat]` (atau `[nomor_sorat]`), `[format_nomor_surat]`

**Data desa:**
`[nama_des]`, `[alamat_desa]`, `[alamat_surat]`, `[alamat_kantor]`, `[kode_desa]`, `[kode_pos]`, `[nama_kab]`, `[nama_kec]`, `[nama_provinsi]`, `[telepon_desa]`, `[website_desa]`, `[email_desa]`, `[nama_kepala_desa]`, `[nip_kepala_desa]`

**Sebutan (case-sensitive):**
`[sebutan_kabupaten]`, `[sebutan_kecamatan]`, `[sebutan_desa]`, `[sebutan_dusun]`, `[sebutan_camat]`

**Data penduduk:**
`[nama]`, `[no_ktp]`, `[no_kk]`, `[agama]`, `[pekerjaan]`, `[pendidikan]`, `[sex]`, `[status]` (kawin), `[gol_darah]`, `[usia]`, `[tempatlahir]`, `[tanggallahir]`, `[ttl]`, `[tempat_tgl_lahir]`, `[alamat]`, `[alamat_jalan]`, `[rt]`, `[rw]`, `[dusun]`, `[hubungan]`, `[kepala_kk]`, `[warga_negara]`, `[cacat]`, `[akta_lahir]`, `[akta_perkawinan]`, `[akta_perceraian]`

**Data ayah/ibu:**
`[d_nama_ayah]`, `[d_nik_ayah]`, `[d_tempatlahir_ayah]`, `[d_tanggallahir_ayah]`, `[d_pekerjaan_ayah]`, `[d_alamat_ayah]`, `[d_nama_ibu]`, `[d_nik_ibu]`, dll

**Data form isian:**
`[jabatan]`, `[nama_pamong]`, `[keterangan]`, `[keperluan]`, `[mulai_berlaku]`, `[tgl_akhir]`, `[penandatangan]` + semua `[key]` dari field form surat

**Nomor surat dengan padding:**
`[nomor_surat,3]` → menghasilkan `012` (3 digit dengan padding `0` di kiri)

### 8.5 Mengapa Surat Masuk/Keluar Terpisah dari Arsip Layanan

Dua konsep yang berbeda:

| | Arsip Layanan (`log_surat`) | Agenda Surat Masuk (`surat_masuk`) | Agenda Surat Keluar (`surat_keluar`) |
|---|---|---|---|
| **Arah** | Desa → Warga | Luar → Desa | Desa → Luar |
| **Dibuat** | Otomatis sistem saat cetak | Manual input staf | Manual input staf |
| **Isi** | Surat layanan (S-01 Pengantar, dst) | Surat dinas dari instansi lain | Surat dinas ke instansi lain |
| **Modul** | `/keluar` (read) + `/surat` (write) | `/surat_masuk` | `/surat_keluar` |

### 8.6 Dependency Model di Cetak Surat

`Surat_model` adalah model paling berat di sistem — ia meng-load banyak data dari berbagai tabel:

- `Surat_master_model` — data template
- `Penduduk_model` — format data penduduk
- `Penomoran_surat_model` — nomor surat
- `Config_model` — data konfigurasi desa
- `Pamong_model` — data penandatangan
- `Keluarga_model` — data keluarga
- `Referensi_model` — data referensi (agama, pekerjaan, dll)

Query `get_data_surat()` melakukan 10+ LEFT JOIN ke tabel referensi untuk mendapatkan data penduduk yang siap pakai di template.

---

## 9. Layanan Mandiri Warga

### 9.1 Gambaran Umum

Layanan mandiri adalah portal publik yang memungkinkan warga mengajukan permohonan surat secara online tanpa harus datang ke kantor desa. Fitur ini diaktifkan via setting `layanan_mandiri = 1` di tabel `setting_aplikasi`.

**Controller terkait:**

| Controller | URL | Fungsi |
|---|---|---|
| `Mandiri_login.php` | `/mandiri_login` | Halaman login warga |
| `Mandiri_web.php` | `/mandiri_web` | Dashboard + pengajuan permohonan warga |
| `Mandiri.php` | `/mandiri` | Panel admin — kelola akun warga mandiri |
| `Permohonan_surat.php` | `/permohonan_surat` | Warga melihat status permohonan |
| `Permohonan_surat_admin.php` | `/permohonan_surat_admin` | Admin memproses permohonan masuk |

### 9.2 Sistem Autentikasi Warga

Layanan mandiri **tidak menggunakan username/password biasa**. Warga login dengan:

- **NIK** — Nomor Induk Kependudukan
- **PIN 6 digit** — didaftarkan oleh operator desa, bukan dibuat sendiri oleh warga

PIN disimpan dalam bentuk hash di tabel `tweb_penduduk_mandiri`.

### 9.3 Tabel `tweb_penduduk_mandiri`

```sql
CREATE TABLE `tweb_penduduk_mandiri` (
  `id_pend`      int(9)    PK  -- FK ke tweb_penduduk.id
  `pin`          char(32)      -- PIN yang sudah di-hash (hash_pin())
  `last_login`   datetime      -- Waktu login terakhir
  `tanggal_buat` datetime      -- Waktu akun dibuat oleh operator
)
```

Tidak ada mekanisme self-registration — warga tidak bisa membuat akun sendiri.

### 9.4 Alur Pendaftaran Akun Warga (oleh Operator)

```
[Operator Desa — Panel Admin]

1. Buka /mandiri
2. Cari nama atau NIK warga
3. Klik Tambah → sistem generate PIN 6 digit otomatis
   (Mandiri_model::generate_pin() → rand(100000,999999) → strrev())
4. PIN ditampilkan sekali di layar → operator catat dan sampaikan ke warga secara offline
5. INSERT ke tweb_penduduk_mandiri dengan PIN yang sudah di-hash
```

### 9.5 Alur Login Warga

```
[Warga — Portal Mandiri]

1. Buka http://[domain]/mandiri_login
2. Masukkan NIK + PIN
3. Mandiri_model::siteman() → query ke tweb_penduduk_mandiri JOIN tweb_penduduk
   → cocokkan hash_pin(input) dengan pin di DB
4. Jika cocok → SET $_SESSION['mandiri'] = 1, $_SESSION['nama'], $_SESSION['nik']
5. Redirect ke /mandiri_web/mandiri

[Jika login pertama kali (first_time_login)]
→ Redirect ke /mandiri_web/ganti_pin (wajib ganti PIN)
```

### 9.6 Alur Pengajuan Permohonan Surat

```
[Warga — Sudah Login]

1. Dashboard /mandiri_web → lihat daftar surat yang tersedia
   (dari tweb_surat_format WHERE mandiri=1 AND kunci=0)
2. Pilih jenis surat → lihat syarat dokumen yang diperlukan
   (dari syarat_surat JOIN ref_syarat_surat)
3. Isi form + upload dokumen syarat
4. Submit → INSERT ke permohonan_surat (status=0, menunggu verifikasi)

[Operator Desa — Panel Admin]

5. Buka /permohonan_surat_admin → lihat permohonan yang masuk
6. Verifikasi kelengkapan dokumen → update status:
   - status=1 → sedang diproses
   - status=2 → menunggu tandatangan (surat sudah dicetak)
   - status=3 → selesai
7. Buka /surat/periksa_doc/{id}/{url}
   → update status permohonan ke 2
   → lanjut ke proses cetak surat (generate RTF + log ke log_surat)
8. Warga datang mengambil surat fisik
```

### 9.7 Catatan Penting

- Jika `layanan_mandiri = 0` di setting, semua akses ke `/mandiri_login` dan `/mandiri_web` otomatis di-redirect ke halaman utama.
- Fitur anjungan (`cek_anjungan`) memungkinkan layanan mandiri tetap bisa diakses dari kiosk/anjungan meski `layanan_mandiri = 0` untuk umum.
- Jumlah percobaan login dibatasi — setelah beberapa kali gagal, akun dikunci sementara (mirip mekanisme siteman admin).
- Tabel `tweb_penduduk_mandiri` kosong = belum ada warga yang didaftarkan = tidak ada yang bisa login ke portal mandiri.

---

## 10. Teknis Pembuatan Dokumen Surat

### 10.1 Anatomi Folder Template

Setiap jenis surat memiliki folder sendiri di `template-surat/` berisi tiga file:

```
template-surat/surat_ket_catatan_kriminal/
├── surat_ket_catatan_kriminal.rtf   ← dokumen surat dengan placeholder
├── surat_ket_catatan_kriminal.php   ← form input di panel admin
└── index.html                       ← blokir akses browsing direktori
```

### 10.2 Cara Kerja Generasi Surat — Tidak Ada Library

Sistem **tidak memakai library generator dokumen**. Cara kerjanya murni string replace:

```
1. file_get_contents('template.rtf')       ← baca file RTF mentah
2. get_data_surat(nik)                     ← query 10+ JOIN ke DB
3. str_replace('[nama]', 'SAMSUL', $rtf)   ← tempel tiap placeholder
4. file_put_contents('desa/arsip/...rtf')  ← simpan file hasil
5. force_download(...)                     ← browser unduh
```

Ini bisa dilakukan ke RTF karena RTF adalah **format berbasis teks** yang bisa dibuka dan diedit sebagai string biasa.

### 10.3 Kenapa RTF, Bukan DOCX atau PDF?

| Format | Bisa str_replace? | Alasan |
|---|---|---|
| **RTF** | ✅ Ya | Format teks biasa — placeholder langsung terganti |
| **DOCX** | ❌ Tidak aman | DOCX adalah ZIP berisi XML; placeholder bisa terpecah antar tag `<w:t>` sehingga str_replace gagal |
| **PDF** | ❌ Tidak | Format binary/final, tidak bisa diedit dengan str_replace |

**RTF dipilih** di era 2009–2015 karena:
- Tidak perlu library tambahan
- Operator desa bisa buat template sendiri di LibreOffice, Save As → `.rtf`
- `str_replace` cukup, tidak perlu parser dokumen

### 10.4 Apakah RTF Masih Didukung?

| Aplikasi | Support |
|---|---|
| LibreOffice | ✅ Full |
| Microsoft Word | ✅ Full |
| Google Docs | ✅ Bisa import |
| macOS Pages | ✅ Bisa buka |
| Smartphone | ⚠️ Perlu app tambahan |

RTF masih didukung semua office suite modern, tapi statusnya **legacy** — Microsoft tidak mengembangkan spesifikasinya sejak 2008.

### 10.5 Output Dokumen Lengkap

```
Surat utama (.rtf)
  └─ Metode: str_replace ke file RTF
  └─ Disimpan ke: desa/arsip/
  └─ Opsional → konversi PDF via: exec('libreoffice --headless --convert-to pdf ...')

Lampiran (.pdf)  ← hanya jika kolom lampiran di tweb_surat_format diisi
  └─ Metode: library html2pdf
  └─ Template: file .php yang render HTML → ob_get_clean() → html2pdf

Jika ada lampiran → kedua file dikemas dalam .zip → browser download
Jika tidak ada   → file .rtf langsung diunduh
```

### 10.6 Rekomendasi Modernisasi: DOCX + PhpWord

Alternatif yang paling kompatibel dengan workflow saat ini adalah **DOCX + PhpWord**:

```php
// template.docx berisi ${nama}, ${nik} (dibuat di LibreOffice/Word)
$template = new \PhpOffice\PhpWord\TemplateProcessor('template.docx');
$template->setValue('nama', 'SAMSUL ARIPIN');
$template->setValue('nik', '3202401806740001');
$template->saveAs('desa/arsip/output.docx');
```

**Kelebihan dibanding RTF:**
- Format lebih modern dan kompatibel
- Operator tetap buat template di LibreOffice — workflow identik
- PhpWord menangani struktur XML DOCX dengan benar, tidak ada risiko placeholder terpecah
- Output bisa langsung dibuka di Google Docs, Office 365, dll tanpa konversi

**Biaya migrasi:** Sedang — konsep identik (template + placeholder + replace), hanya ganti mekanisme `str_replace` dengan `TemplateProcessor` dan format file dari `.rtf` ke `.docx`.

---

## 11. Buku Agenda, Disposisi, dan Ekspedisi

### 11.1 Buku Agenda

**Dunia nyata:**
Di kantor desa ada buku fisik besar di meja TU. Setiap surat yang masuk atau keluar wajib dicatat dengan nomor urut, tanggal, asal/tujuan, dan perihal — sebagai jejak audit jika surat hilang atau ada pemeriksaan.

```
BUKU AGENDA SURAT MASUK 2026
No | Tgl Terima | No Surat          | Dari              | Perihal
---+------------+-------------------+-------------------+------------------
1  | 02/01/2026 | 005/Kec/I/2026   | Kecamatan Cigugur | Undangan Rapat
2  | 15/01/2026 | 012/Dinas/I/2026 | Dinas Kesehatan   | Jadwal Posyandu
```

**Implementasi di sistem:**
Tabel `surat_masuk` dan `surat_keluar` menggantikan buku fisik ini. Ditampilkan di `/surat_masuk` dan `/surat_keluar` sebagai tabel dengan fitur pencarian, filter tahun, dan pagination — setara buku agenda manual tapi bisa dicari dalam detik.

---

### 11.2 Disposisi

**Dunia nyata:**
Ketika surat masuk diterima, Kepala Desa atau Sekdes tidak langsung menangani sendiri. Mereka menulis lembar disposisi yang ditempelkan ke surat, menunjuk staf yang bertanggung jawab menindaklanjuti:

```
┌─────────────────────────────────┐
│ LEMBAR DISPOSISI                │
│ Kepada : Kaur Umum              │
│ Harap  : Ditindaklanjuti        │
│ Catatan: Segera dibalas paling  │
│          lambat 3 hari          │
│ Ttd: Sekdes _______________     │
└─────────────────────────────────┘
```

**Implementasi di sistem:**

```
Surat masuk disimpan ke tabel surat_masuk
         │
         ▼
Operator klik "Disposisi" → dialog muncul
Pilih pamong yang ditugaskan (contoh: Sekdes)
         │
         ▼
INSERT ke disposisi_surat_masuk:
  - id_surat_masuk  → surat mana yang didisposisi
  - pamong_id       → siapa yang ditugaskan
    (dicari dari tweb_desa_pamong berdasarkan jabatan)
```

Satu surat masuk bisa didisposisi ke lebih dari satu pamong. Jika disposisi diubah, sistem hapus semua disposisi lama lalu insert ulang (full replace via `delete` + `insert`).

---

### 11.3 Kirim Ekspedisi

**Dunia nyata:**
Setelah surat keluar selesai ditandatangani, ada proses pengiriman fisik — diantar kurir desa, dikirim via pos, atau dibawa langsung. Buku ekspedisi mencatat siapa yang mengantar, kapan, dan apakah sudah ada tanda terima dari penerima:

```
BUKU EKSPEDISI
No | No Surat | Tujuan           | Tgl Kirim  | Pengirim | Tanda Terima
---+----------+------------------+------------+----------+-------------
1  | 001/DS   | Kecamatan Cigugur| 02/01/2026 | Dadang   | ✓ (ttd)
2  | 002/DS   | Dinas Kesehatan  | 05/01/2026 | Asep     | (belum)
```

**Implementasi di sistem:**

```
Surat keluar sudah dicatat di tabel surat_keluar
         │
         ▼
Operator klik "Kirim ke Ekspedisi"
         │
         ▼
UPDATE surat_keluar SET ekspedisi = 1
         │
         ▼
Redirect ke modul /ekspedisi
→ tampil di daftar surat yang sedang/sudah dikirim
→ operator bisa isi tanggal_pengiriman dan tanda_terima
```

---

### 11.4 Hubungan Ketiga Fitur

```
SURAT MASUK DATANG
  → dicatat di Buku Agenda (/surat_masuk)       ← jejak administrasi
  → Disposisi ke pamong yang menangani           ← delegasi tugas

SURAT KELUAR DIBUAT
  → dicatat di Buku Agenda (/surat_keluar)       ← jejak administrasi
  → Kirim ke Ekspedisi → tracking pengiriman     ← konfirmasi sampai
```

Ketiganya adalah proses administrasi kantor yang sudah ada sebelum era digital — sistem hanya memindahkan dari buku kertas ke database yang bisa dicari, difilter, dan diarsipkan.

---

## 12. Fitur Admin vs Warga dalam Manajemen Surat

### 12.1 Apa yang Bisa Dilakukan Admin (Operator Desa)

```
┌─────────────────────────────────────────────────────────┐
│ PANEL ADMIN                                             │
├───────────────────────────┬─────────────────────────────┤
│ /surat_master             │ Kelola template surat       │
│ /surat                    │ Cetak surat untuk warga     │
│ /keluar                   │ Lihat arsip semua surat     │
│ /surat_masuk              │ Catat surat diterima        │
│ /surat_keluar             │ Catat surat dikirim         │
│ /mandiri                  │ Daftarkan akun warga + PIN  │
│ /permohonan_surat_admin   │ Proses permohonan warga     │
└───────────────────────────┴─────────────────────────────┘
```

### 12.2 Apa yang Bisa Dilakukan Warga/Pemohon

```
┌──────────────────────────────────────────────────────┐
│ PORTAL MANDIRI                                       │
├──────────────────┬───────────────────────────────────┤
│ /mandiri_login   │ Login dengan NIK + PIN             │
│ /mandiri_web     │ Lihat daftar surat yang tersedia   │
│ /mandiri_web     │ Ajukan permohonan + upload syarat  │
│ /permohonan_surat│ Pantau status permohonan           │
└──────────────────┴───────────────────────────────────┘
```

---

## 13. Cara Kerja `bersihkan_kode_isian()`

### 13.1 Masalah yang Dipecahkan

Ketika operator membuat template di **Microsoft Word** lalu Save As RTF, Word menyisipkan kode formatting RTF di dalam teks — termasuk di dalam placeholder. Yang tertulis `[nama]` di Word bisa menjadi seperti ini di dalam file RTF:

```rtf
[{\rtlch\fcs1 \af0 \ltrch\fcs0 \insrsid12345 na}{\rtlch\fcs1 \af0 ma}]
```

Jika langsung di-`str_replace('[nama]', ...)` maka placeholder tidak ditemukan dan data warga tidak terisi ke surat.

### 13.2 Algoritma

Fungsi berjalan **karakter per karakter** melalui seluruh isi RTF:

```
Baca karakter satu per satu
         │
         ├─ Bukan "[" → salin langsung ke output
         │
         └─ Ketemu "[" → masuk mode "baca kode isian"
                  │
                  ├─ Kumpulkan semua karakter sampai ketemu "]"
                  │
                  ├─ Ganti semua karakter non-alphanumerik dengan "#"
                  │  (menandai semua sampah RTF dari Word)
                  │
                  ├─ preg_replace() hapus semua pola RTF Word:
                  │    \rtlch, \cf0, \fcs, \insrsid, \lang, \b, dll
                  │    + semua karakter "#" sisa
                  │
                  └─ Hasil bersih: [nama] ← siap di-str_replace
```

### 13.3 Contoh Konkret

```
INPUT (dari Word RTF):
[{\rtlch\fcs1\af0\insrsid12345 na}{\rtlch ma}]

SETELAH ganti non-alphanumeric → '#':
[{#rtlch#fcs1#af0#insrsid12345 na}#{#rtlch ma}]

SETELAH preg_replace hapus pola RTF Word:
[nama]

OUTPUT: [nama]  ← bisa di-str_replace dengan benar
```

Fungsi ini hanya diperlukan untuk RTF dari Microsoft Word. RTF dari LibreOffice biasanya sudah bersih, tapi fungsi tetap dijalankan untuk semua template sebagai safety net.

---

## 14. Perbedaan 4 Jenis Surat

Dari tampilan menu semuanya terlihat mirip karena ada di bawah label "Surat". Perbedaannya ada pada **siapa pelakunya** dan **tujuannya**:

| | Layanan Surat (Cetak) | Pengajuan Mandiri | Surat Masuk | Surat Keluar |
|---|---|---|---|---|
| **Aktor** | Operator + warga datang langsung | Warga online | Pihak luar → Desa | Desa → Pihak luar |
| **Tabel utama** | `log_surat` | `permohonan_surat` | `surat_masuk` | `surat_keluar` |
| **Output** | File RTF/PDF diunduh langsung | Permohonan antri, surat diambil nanti | Entri agenda + disposisi | Entri agenda + ekspedisi |
| **Pakai template** | Ya (`tweb_surat_format`) | Ya (sama) | Tidak | Tidak |
| **Dunia nyata** | Warga ke loket, operator langsung cetak | Warga kirim request dari rumah | Surat dari Kecamatan masuk ke desa | Surat desa dikirim ke Dinas |

**Catatan:** Surat Masuk dan Surat Keluar adalah **korespondensi institusi desa** dengan pihak luar — bukan surat untuk warga. Layanan Surat dan Pengajuan Mandiri keduanya menghasilkan surat untuk warga, bedanya hanya pada saluran pengajuan: langsung ke loket vs online.

---

## 15. Simulasi Kasus Dunia Nyata

### Kasus A: Warga Datang Langsung ke Loket

```
AKTOR: Pak Ahmad (warga) + Bu Dewi (operator)

Senin 08.30 — Pak Ahmad datang ke kantor desa
"Bu, saya mau bikin surat keterangan domisili untuk daftar sekolah anak"

Bu Dewi:
1. Buka /surat → cari NIK Pak Ahmad
2. Pilih "Surat Keterangan Domisili"
3. Isi keterangan, pilih Pamong TTD (Kades)
4. Klik Cetak → sistem:
   a. baca template .rtf dari disk
   b. bersihkan_kode_isian() → sanitasi placeholder dari Word
   c. get_data_surat(ahmad_id) → query JOIN ambil nama, NIK, alamat
   d. str_replace semua [kode_isian] dengan data Ahmad
   e. simpan ke desa/arsip/surat_ket_domisili_3202..._2026-06-28_001.rtf
   f. log_surat() → catat di tabel log_surat
5. Browser download RTF → Bu Dewi print → Ahmad terima surat
```

---

### Kasus B: Permohonan Online (Warga Tidak Sempat ke Kantor)

```
AKTOR: Bu Rina (warga) + Pak Asep (operator)

Minggu malam — Bu Rina buka HP
1. Buka mandiri_login → masuk NIK + PIN
2. Pilih "Surat Keterangan Tidak Mampu"
3. Lihat syarat: KK, KTP, Surat RT
4. Foto & upload ketiga dokumen → Submit
   → INSERT permohonan_surat (status=0)

Senin pagi — Pak Asep buka /permohonan_surat_admin
5. Lihat permohonan Bu Rina → verifikasi foto dokumen → lengkap
6. Ubah status → 1 (diproses)
7. Buka /surat/periksa_doc → cetak surat seperti Kasus A
   → status otomatis → 2 (menunggu TTD)
8. Kades tanda tangan → status → 3 (selesai)
9. Bu Rina datang kantor → ambil surat
```

---

### Kasus C: Surat dari Kecamatan + Disposisi

```
AKTOR: Kades + Sekdes + Pak Didi (Kaur Umum)

Selasa — Kurir Kecamatan antar surat undangan rapat koordinasi

Sekdes:
1. Buka /surat_masuk → Tambah
2. Isi: No. 045/Kec/VI/2026, dari "Kecamatan Cigugur"
   Perihal: "Undangan Rapat Koordinasi Pembangunan"
3. Upload scan surat fisik
4. Klik Disposisi → pilih Pak Didi (Kaur Umum)
   → INSERT disposisi_surat_masuk

Pak Didi:
5. Terima disposisi → koordinasi kehadiran Kades
6. Kades hadir rapat Rabu

Rapat selesai — perlu balas surat:
Sekdes buka /surat_keluar → Tambah
7. Isi: No. 005/DS-CG/VI/2026, tujuan "Kecamatan Cigugur"
   Isi singkat: "Konfirmasi kehadiran rapat koordinasi"
8. Upload file surat balasan
9. Klik "Kirim ke Ekspedisi"
   → UPDATE surat_keluar SET ekspedisi=1
   → Kurir berangkat → isi tanda_terima setelah diterima
```

---

### Kasus D: Surat Nikah — Dua Pemohon + Lampiran PDF

```
AKTOR: Joko (calon pengantin) + Bu Dewi (operator)

Joko datang: "Bu, minta surat pengantar nikah"

Bu Dewi:
1. Buka /surat → cari NIK Joko
2. Pilih "Surat Pengantar Nikah"
   → sistem deteksi butuh data pasangan → muncul field NIK calon istri
3. Isi NIK calon istri (Sari) + pilih pamong TTD
4. Klik Cetak → sistem:
   a. get_data_surat() → ambil data Joko
   b. get_data_pasangan() → ambil data Sari
   c. Isi placeholder: [nama_pemohon], [nama_pasangan], [nik_pasangan] dll
   d. Ada lampiran f-2.01.php (Formulir Perkawinan):
      → include f-2.01.php → render HTML → html2pdf → lampiran.pdf
   e. Kemas: surat_pengantar_nikah.rtf + lampiran.pdf → .zip
5. Browser download .zip → Joko dapat dua dokumen sekaligus
```

---

### Kasus E: Permohonan Ditolak — Syarat Tidak Lengkap

```
AKTOR: Pak Budi (warga) + Pak Asep (operator)

Pak Budi ajukan "Surat Keterangan Usaha" via portal mandiri
Upload: hanya KTP — lupa foto tempat usaha

Pak Asep buka /permohonan_surat_admin:
1. Cek dokumen → foto tempat usaha tidak ada
2. Isi keterangan: "Mohon lengkapi foto tempat usaha"
3. Status tetap di 0 (menunggu)

Pak Budi cek portal → baca keterangan → upload ulang → submit ulang
Pak Asep verifikasi ulang → lengkap → lanjut proses cetak
```
