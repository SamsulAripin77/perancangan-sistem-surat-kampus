# UX SPEC — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Dokumen** | Spesifikasi UI/UX per Route (wireframe + flow) |
| **Versi** | 0.1 (Draft — bertahap) |
| **Tanggal** | 19 Juli 2026 |
| **Basis** | `FEATURE_MAP.md` (route), `ERD.md` (data), `PRD.md` (fitur), `perancangan-murni.md`, `ARCHITECTURE.md` (UI §10-§11, §17) |
| **Aturan data** | Wireframe **hanya** menampilkan field yang datanya ada di ERD. Elemen UI yang disiratkan PRD tapi datanya belum ada di ERD → **⚠️ PERLU KONFIRMASI**. |

---

## §0. Konvensi & Legenda

### Simbol wireframe
```
┌─ ┐ └ ┘ │ ─   : bingkai panel/kartu
[ Teks ]        : tombol
[______]        : input teks
[ v ]           : dropdown / Select2
( ) / (•)       : radio (kosong / terpilih)
[x] / [ ]       : checkbox
▸                : baris tabel / item
⌕                : kotak pencarian
≡                : ikon menu / drag handle
⬆                : upload (FilePond)
★                : badge status
```

### Notasi state (dibahas di tiap route)
`∅ Empty` (belum ada data) · `⌛ Loading` (AJAX/DataTables) · `✖ Error` (validasi/gagal) · `✔ Success` (aksi berhasil) · `⚠️` (butuh konfirmasi data/desain)

### Kerangka layout (shell) — dipakai semua halaman admin
AdminLTE: **sidebar kiri** (menu per role) + **topbar** (breadcrumb, user) + **area konten**. Untuk menghemat ruang, wireframe berikutnya fokus ke **area konten** saja; shell diasumsikan sama.
```
┌──────────┬──────────────────────────────────────────────┐
│ SIDEBAR  │ TOPBAR: Breadcrumb ............ [User ▾]      │
│ (menu    ├──────────────────────────────────────────────┤
│  per     │                                              │
│  role)   │            AREA KONTEN (fokus wireframe)     │
│          │                                              │
└──────────┴──────────────────────────────────────────────┘
```

### Komponen reusable (ARCHITECTURE §11, §17) yang dirujuk berulang
- **`x-ui.datatable`** — tabel server-side (search + sort + pagination + kolom Aksi).
- **`x-ui.filter`** — bar filter (dropdown Select2 + rentang tanggal) di atas tabel (standar §17).
- **`x-form.*`** — input/select(Select2)/file(FilePond) berlabel.
- **`x-ui.button`** — tombol compact `btn-sm` berwarna aksi.
- **`x-ui.badge-status`** — badge warna untuk status.

---

## §0.1. Persona Pengguna

| # | Persona | Siapa | Karakter & Kebutuhan UX |
|---|---|---|---|
| **P1** | **Super Admin** | IT / Koordinator sistem | Jarang login, aksi berdampak luas (kelola user, kamus placeholder, konfigurasi). Butuh kontrol jelas + peringatan sebelum aksi destruktif. |
| **P2** | **Admin Surat** | Staf BAA / Tata Usaha | Pengguna harian, volume tinggi. Butuh **cepat & padat**: tabel compact, filter kuat, minim klik, alur proses permohonan → cetak yang mulus. |
| **P3** | **Mahasiswa** | Mahasiswa aktif | Pengguna sesekali, kemungkinan dari HP. Butuh **alur terpandu & sederhana**: jelas apa yang diisi, status transparan, pesan error ramah. |

*(Phase 2: Admin Unit & Pejabat — di luar cakupan spec Phase 1.)*

---

## §0.2. Peta Ketergantungan & Rencana Tahap

Urutan penjelasan mengikuti **dependensi** (fitur prasyarat dijelaskan lebih dulu, walau beda role) — sesuai ARCHITECTURE §18:

```
Fondasi/Master  →  Template  →  Permohonan → Approval  →  Generate → Arsip  →  Surat Masuk/Keluar
(Tahap 1-2)        (Tahap 3)    (Tahap 4)     (Tahap 5)             (Tahap 6)   (Tahap 7)
```

| Tahap | Cakupan | Modul (FEATURE_MAP) | Status |
|---|---|---|---|
| **1** | Fondasi: Auth, Dashboard, Konfigurasi Sistem | M-AUTH, M-DASH, M-CONFIG | ✅ **di dokumen ini** |
| 2 | Master data: User, Kamus Placeholder, Kategori, Persyaratan | M-USER, M-KAMUS, M-KATEGORI, M-SYARAT | ☐ berikutnya |
| 3 | Template Surat (keluarga lengkap) | M-TEMPLATE | ☐ |
| 4 | Permohonan Mahasiswa (ajukan, dokumen, riwayat, profil) | M-MHS-* | ☐ |
| 5 | Review/Approval + Generate & Cetak | M-PERM-ADM, M-GENERATE | ☐ |
| 6 | Arsip Surat + Verifikasi Publik | M-ARSIP, M-VERIFIKASI | ☐ |
| 7 | Surat Masuk (+disposisi) & Surat Keluar | M-MASUK, M-KELUAR | ☐ |
| — | Cross-cutting: Upload (M-MEDIA), Notifikasi (M-NOTIF) | dijelaskan menyatu di tiap fitur | — |

---

# TAHAP 1 — FONDASI

---

## 1.A — Autentikasi (M-AUTH)

**Keluarga route**: `login` (form) · `login.store` (proses) · `logout` · `password.*` (reset). Semua pintu masuk sistem; berlaku untuk **semua persona**.

### 1.A.1 Halaman Login — `login` / `login.store`

**Wireframe** (layout `auth`, tanpa sidebar — halaman berdiri sendiri):
```
        ┌────────────────────────────────────┐
        │            [ LOGO KAMPUS ]          │   ← settings.logo_kampus
        │      Sistem Surat — [Nama Univ]     │   ← settings.nama_universitas
        ├────────────────────────────────────┤
        │  Email                              │
        │  [________________________]        │   ← users.email
        │  Password                           │
        │  [________________________]        │   ← users.password
        │  [ ] Ingat saya                     │
        │                                     │
        │         [   Masuk   ]               │
        │        Lupa password?               │   → password.request
        └────────────────────────────────────┘
```

**UI — komponen & fungsi**:
- Logo + nama kampus (dari `settings`) → identitas & kepercayaan.
- `x-form.input` email & password → kredensial (ERD `users.email`, `users.password`).
- Checkbox "Ingat saya" (fitur Fortify, bukan kolom data).
- Tombol Masuk (`x-ui.button` primary) + link "Lupa password".

**UX & State**:
- `✖ Error` — kredensial salah: alert merah di atas form ("Email atau password salah"), input tidak dikosongkan (kecuali password).
- `✖ Error` — **user nonaktif** (`users.is_active=false`): pesan khusus "Akun Anda nonaktif, hubungi admin" (PRD F1).
- `⌛ Loading` — tombol disable + spinner saat submit.
- `✔ Success` — redirect sesuai role (lihat Flow).

**Persona**: ketiganya. P3 (mahasiswa) kemungkinan dari HP → form harus responsif 1 kolom.

**Flow**:
1. User buka `/login` → isi email + password → submit (`login.store`).
2. Validasi: email required+format, password required. Gagal → kembali dengan `✖`.
3. Fortify cek kredensial + `is_active`. Nonaktif → tolak dengan pesan khusus.
4. `✔` → **redirect by role**: `super_admin`/`admin_surat` → `admin.dashboard`; `mahasiswa` → `mahasiswa.beranda`.
5. Login/logout tercatat di `activity_log`.

**⚠️ PERLU KONFIRMASI**: mekanisme kredensial awal mahasiswa berasal dari import SIAKAD (password snapshot) — apakah ada paksaan ganti password saat login pertama? ERD tidak punya kolom `must_change_password`. Bila diinginkan, perlu tambah kolom.

### 1.A.2 Reset Password — `password.*`
Form standar Fortify: input email → kirim link → form password baru. Field mengacu `users.email`/`users.password`. State: `✔` "Link terkirim", `✖` "Email tidak terdaftar". Tidak digambar detail (standar bawaan).

---

## 1.B — Dashboard (M-DASH)

Dua varian **berbeda besar** per role → wireframe dipisah.

### 1.B.1 Dashboard Admin — `admin.dashboard`

**Persona**: P2 (Admin Surat) utama, P1 juga. Tujuan: sekali lihat tahu **apa yang perlu dikerjakan hari ini**.

**Wireframe** (area konten):
```
Dashboard
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Pending     │ │ Mendekati    │ │  Overdue     │ │ Disposisi    │
│    12        │ │ deadline  3  │ │    2         │ │ belum TL  5  │
│ (permohonan) │ │ (kuning)     │ │  (merah)     │ │ (surat masuk)│
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
      │                                                    │
  → admin.permohonan.index                     → admin.surat-masuk.agenda

┌─ Permohonan Terbaru (ringkas) ───────────────────────────┐
│ ▸ [★status]  Jenis Surat   Nama/NIM       Tgl Ajukan     │  ← permohonan_surat
│ ▸ ...                                                     │     + mahasiswa
│ [ Lihat semua → ]                                         │
└──────────────────────────────────────────────────────────┘
```

**UI**:
- 4 **stat card** (`x-ui.card` warna): angka dihitung dari `permohonan_surat.status` (pending), deadline = `created_at` + `templates.sla_hari_kerja` (mendekati/overdue), dan `disposisi_surat_masuk.status` (belum ditindaklanjuti).
- Tabel ringkas permohonan terbaru (subset kolom) → link ke index penuh.

**UX & State**:
- Warna kartu = urgensi (kuning H-1, merah overdue) — PRD F6.
- Kartu **klik → langsung ke daftar terfilter** (mis. kartu Overdue → `admin.permohonan.index?status=...&overdue`).
- `∅ Empty` — belum ada permohonan: kartu tampil `0`, tabel tampil "Belum ada permohonan masuk".

**Flow**: login (admin) → mendarat di dashboard → klik kartu/tabel → menuju modul terkait. Tidak ada form di sini (read-only).

**✅ KEPUTUSAN (SLA anchor)**: pakai **`created_at` yang sudah ada** — **tidak menambah `submitted_at`**. Alasan: SLA hanya **estimasi/alert visual** (bukan kontrak, tanpa penalti — `perancangan-kasar.md` §4), jadi ketidaktepatan kecil bila draft lama baru diajukan dapat diterima. Agar drift minimal, **dashboard SLA hanya menghitung permohonan status ≥ `pending`** (draft belum masuk antrean, jadi tak dihitung). Bila kelak butuh SLA presisi (Phase 2), `submitted_at` bisa ditambah tanpa refactor.

### 1.B.2 Beranda Mahasiswa — `mahasiswa.beranda`

**Persona**: P3. Tujuan: tahu status permohonan miliknya + akses cepat ajukan surat.

**Wireframe**:
```
Halo, [Nama Mahasiswa]                         ← mahasiswa.nama
┌──────────────────────────┐  ┌──────────────────────────┐
│  [ + Ajukan Surat ]      │  │  Permohonan Aktif         │
│  (tombol besar utama)    │  │  ▸ [★status] Jenis surat  │  ← permohonan_surat
└──────────────────────────┘  │  ▸ ...                    │     (WHERE milik sendiri)
                              │  [ Riwayat lengkap → ]    │
                              └──────────────────────────┘
```

**UI**: sapaan nama (dari `mahasiswa`), tombol utama "Ajukan Surat" → `mahasiswa.ajukan.index`, daftar ringkas permohonan aktif (status berwarna) → `mahasiswa.permohonan.index`.

**UX & State**:
- `∅ Empty` — belum pernah mengajukan: tampil ajakan "Belum ada permohonan. Mulai ajukan surat pertama Anda." + tombol.
- Status berwarna (`x-ui.badge-status`) supaya mahasiswa langsung paham posisi (menunggu/disetujui/selesai/ditolak).

**Flow**: login (mahasiswa) → beranda → klik "Ajukan Surat" (ke Tahap 4) atau lihat status.

---

## 1.C — Konfigurasi Sistem (M-CONFIG)

**Persona**: P1 (Super Admin) & P2 (Admin Surat) — konfigurasi awal & pemeliharaan master. Prasyarat hampir semua fitur (identitas kampus, unit, pejabat dipakai template & generate).

**Keluarga route**: `admin.konfigurasi.edit/update` (settings) · `admin.unit.*` (CRUD) · `admin.pejabat.*` (CRUD). Dijelaskan berurutan.

### 1.C.1 Pengaturan Sistem — `admin.konfigurasi.edit` / `.update`

**Wireframe** (form bergrup — mengikuti `settings.group`):
```
Pengaturan Sistem                                    [ Simpan ]
┌─ Umum ───────────────────────────────────────────────────┐
│ Nama Universitas   [__________________________]          │ ← settings key=nama_universitas
│ Kode Universitas   [__________]                          │ ← kode_universitas
│ Alamat             [__________________________]          │ ← alamat_kampus (text)
│ Logo Kampus        [ ⬆ pilih file ]  [preview]           │ ← logo (media, type=media)
├─ Akademik ───────────────────────────────────────────────┤
│ Tahun Akademik Aktif [ 2025/2026 ]                       │ ← tahun_akademik_aktif
├─ Penomoran ──────────────────────────────────────────────┤
│ Format Nomor (helper) [ {urut}/{unit}/{kode}/{bln}/{thn}]│ ← format_nomor_helper
├─ SMTP ───────────────────────────────────────────────────┤
│ Host [______]  Port [___]  From [______________]         │ ← mail_host/port/from_*
│ Username [______]  Password [•••••••]                    │ ← mail_username/password(encrypted)
└──────────────────────────────────────────────────────────┘
```

**UI**: satu form dibagi **section per grup** (`settings.group`), tiap baris = satu `settings` row dirender sesuai `type` (text/text-area/media→FilePond/encrypted→password). Label dari `settings.label`.

**UX & State**:
- Grup memudahkan scan (Umum/Akademik/Penomoran/SMTP) — bukan satu daftar panjang.
- Logo: `⌛` saat upload, `preview` setelah sukses; disimpan via Media Library (K4).
- Format nomor = **helper teks** (bukan mesin) → diberi contoh hasil di bawah field.
- `✔ Success` — "Pengaturan tersimpan" (redirect balik ke form). `✖` — validasi per field (mis. port angka, email from valid).
- SMTP password field `password` (tersembunyi); kosongkan = tidak mengubah.

**Flow**: buka Pengaturan → ubah nilai → Simpan → validasi → `✔` tersimpan (cache settings di-bust). Nilai ini kemudian dibaca saat generate surat (placeholder sistem) & kirim email.

### 1.C.2 Manajemen Unit — `admin.unit.*`

**Wireframe — Index**:
```
Unit Penerbit                                   [ + Tambah Unit ]
┌─ Filter (§17) ───────────────────────────────────────────┐
│ ⌕ [cari nama/kode]   Status [ Semua v ]                   │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Nama Unit     │ Kode │ Induk        │ Aktif │ Aksi        │  ← units
│ BAA           │ BAA  │ —            │  ✔    │ [✎][🗑]      │
│ Fakultas Tek. │ FT   │ —            │  ✔    │ [✎][🗑]      │
│ Prodi Inform. │ IF   │ Fakultas Tek.│  ✔    │ [✎][🗑]      │  ← parent_id
└──────────────────────────────────────────────────────────┘
```

**Wireframe — Form (tambah/edit, bisa modal)**:
```
┌─ Tambah/Edit Unit ───────────────────────┐
│ Nama   [____________________]            │ ← units.nama
│ Kode   [__________]                      │ ← units.kode (unique)
│ Induk  [ (tidak ada) v ]                 │ ← parent_id → units (Select2)
│ [x] Aktif                                │ ← is_active
│                 [ Batal ] [ Simpan ]     │
└──────────────────────────────────────────┘
```

**UI**: `x-ui.datatable` + `x-ui.filter` (search + status). Form: nama, kode (unik), induk (Select2 ke units lain), toggle aktif.

**UX & State**:
- Kode unik → `✖` inline "Kode sudah dipakai".
- Hapus: konfirmasi (`js-confirm-delete`). Jika unit dipakai (template/permohonan) → tolak/nonaktifkan saja (integritas).
- `∅ Empty` — "Belum ada unit, tambah unit pertama".

**Flow**: index → Tambah/Edit (modal) → validasi (nama, kode unik) → `✔` tersimpan, tabel refresh (DataTables reload).

### 1.C.3 Manajemen Pejabat — `admin.pejabat.*`

**Wireframe — Index**:
```
Pejabat Penandatangan                          [ + Tambah Pejabat ]
┌─ Filter ─────────────────────────────────────────────────┐
│ ⌕ [cari nama/jabatan]   Unit [ Semua v ]  Status [ v ]    │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Nama        │ Jabatan       │ Unit(s)   │ TTD │ Aktif│Aksi│  ← pejabat (+pejabat_unit)
│ Dr. Ahmad   │ Kaprodi IF    │ Prodi IF  │ ✔   │  ✔  │[✎][🗑]│  ← file_ttd (media): ada
│ Dr. Siti    │ Dekan FT      │ Fak.Teknik│ —   │  ✔  │[✎][🗑]│  ← TTD basah (media kosong)
└──────────────────────────────────────────────────────────┘
```

**Wireframe — Form**:
```
┌─ Tambah/Edit Pejabat ─────────────────────────────┐
│ Nama       [__________________________]           │ ← nama
│ Jabatan    [__________________________]           │ ← jabatan
│ NIP/NIDN   [__________________]                   │ ← nip_nidn (nullable)
│ Email      [__________________]                   │ ← email (nullable)
│ Unit       [ Prodi IF ×][ + ]  (multi, Select2)   │ ← pejabat_unit (n-n)
│ File TTD   [ ⬆ pilih PNG ] [preview]  (opsional)  │ ← media collection 'ttd'
│ [x] Aktif                                         │ ← is_active
│                        [ Batal ] [ Simpan ]       │
└───────────────────────────────────────────────────┘
```

**UI**: tabel + filter (search, unit, status). Kolom "TTD" menunjukkan **ada/tidak** file tanda tangan (✔/—) — penting karena menentukan saran metode pengambilan saat generate (Tahap 5). Form: data pejabat + **multi-unit** (Select2 tags → `pejabat_unit`) + upload TTD (FilePond, opsional).

**UX & State**:
- TTD **opsional**: kosong = pejabat pakai tanda tangan basah (sah, PRD F7). Beri hint "Kosongkan jika tanda tangan basah".
- `⌛` saat upload TTD; disimpan disk **private** (ARCHITECTURE §9).
- Hapus pejabat yang sudah dipakai di arsip → **cegah** (arsip menyimpan snapshot, tapi master sebaiknya dinonaktifkan, bukan dihapus). `✖`/nonaktifkan.
- `∅ Empty` — "Belum ada pejabat".

**Flow**: index → Tambah/Edit → isi data + pilih unit (≥1) + (opsional) upload TTD → validasi → `✔` tersimpan. Data ini dipakai sebagai dropdown penandatangan di Generate (Tahap 5) & pemberi persetujuan di Approval (Tahap 5).

**Variasi perilaku**: baris pejabat dengan TTD vs tanpa TTD — perbedaan kecil (ikon kolom TTD), tidak perlu wireframe terpisah; dampak besarnya muncul di Generate (Tahap 5) di mana absennya TTD memicu peringatan & saran "Ambil di Kampus".

---

# TAHAP 2 — MASTER DATA

> Semua di tahap ini adalah **prasyarat Template (Tahap 3)**: Kamus mendefinisikan perilaku placeholder, Kategori & Persyaratan dipilih saat membuat template. User adalah fondasi akun. Urutan: User → Kamus → Kategori → Persyaratan.

---

## 2.A — Manajemen User (M-USER) — **khusus Super Admin (P1)**

**Keluarga route**: `admin.user.*` (CRUD) · `admin.user.import` (import SIAKAD). Mengelola akun `users` + role (Spatie) + profil `mahasiswa`.

### 2.A.1 Daftar User — `admin.user.index`

**Wireframe**:
```
Manajemen User                        [ ⬆ Import Mahasiswa ] [ + Tambah User ]
┌─ Filter (§17) ───────────────────────────────────────────────┐
│ ⌕ [cari nama/email/NIM]  Role [ Semua v ]  Unit [ v ]  Status[v]│
└──────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────┐
│ Nama      │ Email          │ Role       │ NIM     │Aktif│ Aksi │
│ Dewi M.   │ dewi@..        │ Admin Surat│ —       │ ✔  │[✎][⏻]│  ← users + role(spatie)
│ Budi S.   │ budi@..        │ Mahasiswa  │20210001 │ ✔  │[✎][⏻]│  ← users + mahasiswa.nim
└──────────────────────────────────────────────────────────────┘
```

**UI**: `x-ui.datatable` + `x-ui.filter` (search, role, unit, status). Kolom NIM hanya terisi untuk user ber-role mahasiswa (join `mahasiswa`). Aksi: edit (✎), aktif/nonaktif (⏻ toggle `is_active`).

**UX & State**:
- Toggle aktif/nonaktif langsung (tanpa form) → `✔` inline; user nonaktif tak bisa login (efek di 1.A).
- Tidak ada "hapus" permanen (audit) → hanya nonaktif. ⚠️ *bila hapus permanen diinginkan, konfirmasikan (ERD `users` punya soft delete)*.

### 2.A.2 Form User — `admin.user.create/edit`

**Wireframe** (field profil mahasiswa muncul kondisional):
```
┌─ Tambah/Edit User ────────────────────────────────┐
│ Nama    [__________________________]              │ ← users.nama
│ Email   [__________________________]              │ ← users.email (unique)
│ Password[__________] (kosong=tak diubah saat edit)│ ← users.password
│ Role    ( ) Super Admin ( ) Admin Surat (•) Mhs   │ ← spatie role
│ Unit    [ BAA v ]                                  │ ← users.unit_id (Select2)
│ ─ Jika Role = Mahasiswa ──────────────────────────│
│   NIM      [____________]                          │ ← mahasiswa.nim (unique)
│   Prodi    [____________]                          │ ← mahasiswa.prodi
│   Fakultas [____________]                          │ ← mahasiswa.fakultas
│ [x] Aktif                                          │ ← is_active
│                        [ Batal ] [ Simpan ]        │
└────────────────────────────────────────────────────┘
```

**UI/UX**:
- **Field `mahasiswa` (NIM/prodi/fakultas) muncul hanya bila Role = Mahasiswa** → menghindari form penuh kolom tak relevan (perilaku dinamis via `js-*`).
- Email & NIM unik → `✖` inline. Password wajib saat create, opsional saat edit.
- `✔` → redirect ke index, baris baru muncul.

**Flow**: index → Tambah/Edit → pilih role (form menyesuaikan) → isi → validasi (unik email/NIM) → simpan → buat/update `users` + assign role + (jika mhs) buat/update `mahasiswa` 1—1.

### 2.A.3 Import Mahasiswa (SIAKAD) — `admin.user.import`

**Wireframe** (modal/halaman upload — proses, bukan tabel):
```
┌─ Import Mahasiswa dari SIAKAD ─────────────────────┐
│ Unduh format contoh: [ ⬇ template.xlsx ]          │
│ File  [ ⬆ pilih .xlsx/.csv ]                       │
│ Kolom: NIM, Nama, Email, Password(hashed), Prodi   │ ← mahasiswa + users
│                                                    │
│  [Preview]  → tabel pratinjau baris + validasi     │  ← ⌛ parsing
│  ▸ 20210001  budi@..  ✔ valid                      │
│  ▸ 20210002  (email kosong) ✖ error baris 2        │
│                         [ Batal ] [ Import ]        │
└────────────────────────────────────────────────────┘
```

**UI/UX & State**:
- Unduh template + upload file → **pratinjau** sebelum commit (cegah salah import massal).
- `✖` per baris (validasi NIM/email) → tampil baris bermasalah; user bisa batal & perbaiki.
- `⌛` saat parsing/import (banyak baris → queue + progress).
- `✔` — ringkasan "N mahasiswa diimport, M dilewati (duplikat/error)".
- Import = buat `users`(role mhs) + `mahasiswa`. Duplikat (NIM/email ada) → update atau skip.

**Persona**: P1. **Flow**: buka Import → unduh template → isi di luar → upload → preview → Import → ringkasan hasil.

**✅ KEPUTUSAN (Import SIAKAD)**:
- **Kolom file**: `nim, nama, email, password, prodi`.
- **Password**: **sudah di-hash** dari SIAKAD → disimpan apa adanya (tidak di-hash ulang).
- **Duplikat**: **skip** baris bila `email` **atau** `nim` sudah ada (keduanya unik).

**⚠️ Masih perlu dipastikan (turunan keputusan di atas)**:
- **(a) `fakultas` tidak diimport**, padahal `mahasiswa.fakultas` & placeholder `{{fakultas}}` (§4.4) memakainya → akan kosong di surat. Opsi: (1) turunkan fakultas dari `prodi` via mapping, (2) `fakultas` nullable & diisi admin manual, (3) hapus `{{fakultas}}` & kolom `mahasiswa.fakultas`, atau (4) tambahkan ke import. **Perlu keputusan.**
- **(b) Algoritma hash password** harus **kompatibel Laravel (bcrypt)**. Bila SIAKAD memakai hash lain (MD5/SHA/dll), verifikasi login gagal → butuh strategi (rehash saat login pertama / custom hasher). **Perlu konfirmasi algoritma.**

---

## 2.B — Master Kamus Placeholder (M-KAMUS) — **khusus Super Admin (P1)**

**Route**: `admin.kamus.*`. Mendefinisikan perilaku placeholder yang dikenal sistem (dipakai saat scan template, Tahap 3). Perubahan berdampak **semua template** → hanya P1.

**Wireframe — Index**:
```
Kamus Placeholder                              [ + Tambah Placeholder ]
┌─ Filter ─────────────────────────────────────────────────┐
│ ⌕ [cari name]   Kelompok [ Semua v ]                      │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Name              │ Kelompok │ Tipe Input │ Override?│Aksi │  ← placeholder_definitions
│ nama_mahasiswa    │ profil   │ text       │  —       │[✎][🗑]│
│ tanggal_surat     │ waktu    │ date       │  ✔       │[✎][🗑]│
│ nomor_surat       │ counter  │ text       │  ✔       │[✎][🗑]│
└──────────────────────────────────────────────────────────┘
```

**Wireframe — Form**:
```
┌─ Tambah/Edit Placeholder ─────────────────────┐
│ Name        [__________________]  (snake_case)│ ← name (unique)
│ Kelompok    [ profil v ]                       │ ← kelompok (enum: profil/waktu/sistem/counter/ttd)
│ Tipe Input  [ text v ]                         │ ← input_type
│ Sumber      [__________________] (opsional)    │ ← source (dokumentasi)
│ [x] Bisa di-override admin                     │ ← is_overridable
│                       [ Batal ] [ Simpan ]     │
└────────────────────────────────────────────────┘
```

**UI/UX & State**:
- `name` unik + snake_case → `✖` bila melanggar.
- **Peringatan saat edit/hapus** entri yang sudah dipakai template aktif: "Mengubah ini memengaruhi semua template yang memakainya. Lanjut?" (destruktif → konfirmasi, sesuai ERD §8/Fitur 13).
- `∅ Empty` tak akan terjadi (di-seed produksi), tapi tetap ada "Tambah".

**Flow**: index → Tambah/Edit → isi → (jika terpakai) konfirmasi dampak → simpan.

---

## 2.C — Master Kategori Surat (M-KATEGORI) — Admin (P2)

**Route**: `admin.kategori.*`. Dropdown & filter saat membuat template (Tahap 3).

**Wireframe — Index + Form ringkas**:
```
Kategori Surat                                 [ + Tambah Kategori ]
┌──────────────────────────────────────────────────────────┐
│ Nama                 │ Dipakai │ Aktif │ Aksi             │  ← kategori_surat
│ Layanan Mahasiswa    │ 8 tmpl  │  ✔    │ [✎][🗑]           │  ← (count templates)
│ Administrasi Internal│ 3 tmpl  │  ✔    │ [✎][🗑]           │
└──────────────────────────────────────────────────────────┘

┌─ Form (modal) ─────────────────┐
│ Nama  [________________]       │ ← kategori_surat.nama
│ [x] Aktif                      │ ← is_active
│         [ Batal ] [ Simpan ]   │
└────────────────────────────────┘
```

**UI/UX**:
- Kolom "Dipakai" = jumlah template memakai (computed) → beri konteks sebelum hapus.
- **Hapus dicegah bila masih dipakai** template aktif (FK restrict, Fitur 12) → `✖` "Tidak bisa dihapus, dipakai N template. Nonaktifkan saja."
- Form sederhana (modal), `✔` reload tabel.

**Flow**: index → Tambah/Edit (modal) → validasi nama → simpan.

---

## 2.D — Master Persyaratan Surat (M-SYARAT) — Admin (P2)

**Route**: `admin.persyaratan.*`. Dokumen syarat reusable; dipilih/dibuat saat menyusun template (Tahap 3, pola FindOrCreate).

**Wireframe — Index**:
```
Persyaratan Surat                              [ + Tambah Persyaratan ]
┌─ Filter ─────────────────────────────────────────────────┐
│ ⌕ [cari nama]                                             │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Nama          │ Tipe File │ Max │ Template? │ Dipakai │Aksi│  ← ref_syarat_surat
│ Fotokopi KHS  │ pdf,jpg   │ 5MB │  —        │ 4 tmpl  │[✎][🗑]│
│ Surat Pernyata│ pdf       │ 2MB │ ⬇ ada     │ 1 tmpl  │[✎][🗑]│  ← template_file (media)
└──────────────────────────────────────────────────────────┘
```

**Wireframe — Form**:
```
┌─ Tambah/Edit Persyaratan ─────────────────────────┐
│ Nama            [__________________________]      │ ← nama
│ Deskripsi       [__________________________]      │ ← deskripsi (nullable)
│ Tipe file       [ pdf, jpg, png ]                 │ ← accepted_types
│ Ukuran max (MB) [ 5 ]                             │ ← max_size_mb
│ File Template   [ ⬆ pilih ] (opsional, utk diunduh│ ← template_file (media 'template')
│                  mahasiswa)                        │
│                        [ Batal ] [ Simpan ]        │
└────────────────────────────────────────────────────┘
```

**UI/UX & State**:
- "Template?" = apakah ada file contoh untuk diunduh mahasiswa (tombol ⬇ di form permohonan, Tahap 4).
- "Dipakai" = jumlah template memakai (via `syarat_surat`) → **hapus dicegah bila dipakai** template aktif (Fitur 4) → `✖`/nonaktif.
- Upload template contoh via FilePond (opsional).

**Flow**: index → Tambah/Edit → isi (nama, tipe/ukuran file, opsional file contoh) → validasi → simpan.

**Variasi penting (dijelaskan penuh di Tahap 3)**: persyaratan ini juga bisa dibuat **inline (FindOrCreate)** dari halaman Template tanpa pindah halaman — modal ringkas yang menyimpan ke `ref_syarat_surat` **dan** langsung me-link ke template. Wireframe modal inline dibahas di Tahap 3 (Template) karena konteksnya di sana.

---

# TAHAP 3 — TEMPLATE SURAT (M-TEMPLATE)

> **Inti sistem** & prasyarat Permohonan (Tahap 4) dan Generate (Tahap 5). Persona: **P2 Admin Surat** (P1 juga). Alur besar: Daftar → Buat (upload+scan) → **Halaman Detail/Edit** (hub: review placeholder, persyaratan, data tambahan, coba) → Aktifkan. Dijelaskan berurutan.

Prasyarat yang sudah ada dari Tahap 2: Kategori (3.B), Persyaratan (3.D), Kamus Placeholder (3.C review & 3.G panduan).

---

## 3.A — Daftar Template — `admin.template.index`

**Wireframe**:
```
Template Surat                                       [ + Tambah Template ]
┌─ Filter (§17) ───────────────────────────────────────────────┐
│ ⌕ [cari nama]  Kategori [ v ]  Unit [ v ]  Status [ v ]        │
└──────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────┐
│ Nama            │ Kategori   │ Pemohon │ Mandiri │Status│ Aksi │  ← templates
│ Rekomendasi Mgg │ Lyn Mhs    │ Mahasiswa│  ✔     │aktif │[✎][🗑]│
│ Nota Dinas      │ Adm Intern │ Umum     │  —     │draft │[✎][🗑]│
└──────────────────────────────────────────────────────────────┘
[ 📖 Panduan Placeholder ]  ← link ke 3.G
```

**UI**: `x-ui.datatable` + `x-ui.filter` (kategori, unit, status). Kolom: nama, kategori, `tipe_pemohon`, `is_permohonan_mandiri` (✔/—), `status` (badge). Aksi: edit (ke detail), hapus.

**UX & State**:
- Badge status: draft (abu) / aktif (hijau) / nonaktif (merah).
- `∅ Empty` — "Belum ada template. Tambah template pertama."
- Hapus: **dicegah bila template sudah punya permohonan** → `✖` "Tidak bisa dihapus, sudah dipakai. Nonaktifkan saja." (Fitur 3, soft delete).
- Link Panduan Placeholder selalu tampak (dibutuhkan saat menyusun file Word).

**Flow**: index → "+ Tambah" (3.B) atau ✎ (ke 3.C hub).

---

## 3.B — Buat Template: Upload + Metadata — `admin.template.create` / `store`

**Wireframe** (form langkah-1):
```
Tambah Template
┌──────────────────────────────────────────────────────────┐
│ Nama            [__________________________]             │ ← templates.nama
│ Kategori        [ Layanan Mahasiswa v ]                  │ ← kategori_id (Select2, master 2.C)
│ Unit Penerbit   [ BAA ×][ + ] (multi)                    │ ← template_unit (n-n, Select2)
│ Deskripsi       [__________________________]             │ ← deskripsi (nullable)
│ Tipe Pemohon    (•) Mahasiswa   ( ) Umum                 │ ← tipe_pemohon
│ SLA (hari kerja)[ 3 ]                                     │ ← sla_hari_kerja (nullable)
│ [ ] Sediakan untuk permohonan mandiri mahasiswa          │ ← is_permohonan_mandiri
│ File Template   [ ⬆ pilih .docx ]  (maks 10MB)           │ ← media 'docx'
│                              [ Batal ] [ Simpan & Scan ] │
└──────────────────────────────────────────────────────────┘
```

**UI/UX & State**:
- Semua field metadata `templates` + multi-unit (`template_unit`) + upload `.docx` (FilePond).
- Validasi: nama required, kategori exists, file mimes:docx max 10MB → `✖` inline.
- `Simpan & Scan` → status default `draft`, `created_by` = admin login.
- `⌛` — proses scan placeholder berjalan (bisa 1-2 dtk) → spinner "Menganalisis placeholder…".
- `✔` → **redirect ke Halaman Detail/Edit (3.C)** menampilkan hasil deteksi.
- Jika file **tidak mengandung `{{...}}`** → `⚠️` banner "Tidak ada placeholder terdeteksi — pastikan template memakai format `{{nama}}`." (tetap tersimpan draft).

**Flow**: index → create → isi metadata + upload → Simpan & Scan → sistem scan → redirect detail.

---

## 3.C — Halaman Detail/Edit Template (HUB) — `admin.template.edit` / `update`

Halaman hub berbentuk **section** (bukan pindah-pindah halaman). Header + 4 section + tombol aksi.

**Wireframe — kerangka hub**:
```
Template: Rekomendasi Magang            [ Coba Template ] [ Aktifkan ]
Status: draft                                            ← templates.status
├─ [Informasi] ───────────────────────────────────────────────
│  (form metadata sama seperti 3.B — bisa diedit)
├─ [Placeholder Terdeteksi] ──────────────────────────────────  (3.C.1)
├─ [Persyaratan] ─────────────────────────────────────────────  (3.D)
└─ [Data Tambahan] ───────────────────────────────────────────  (3.E)
```

### 3.C.1 Review Placeholder Terdeteksi

**Wireframe**:
```
Placeholder Terdeteksi (hasil scan)
┌──────────────────────────────────────────────────────────────────┐
│ Placeholder      │Kelompok│ Diisi oleh │ Tipe   │ Label (mhs)  │Wajib│
│ nama_mahasiswa   │ profil │[sistem  v] │[text v]│ (—)          │ [ ] │  ← template_placeholder_config
│ tanggal_surat    │ waktu  │[admin   v] │[date v]│ (—)          │ [ ] │     (+kelompok dari
│ nama_perusahaan  │ bebas  │[mahasiswa v│[text v]│[Nama Perush.]│ [x] │      placeholder_definitions)
│ ttd_1 (Slot 1)   │ ttd    │[admin]     │[image] │ (—)          │  —  │
│ nama_ttd_1       │ ttd    │[admin]     │[text]  │ (—)          │  —  │
│ ≡ drag utk urutan                                                  │  ← urutan
└──────────────────────────────────────────────────────────────────┘
⚠️ (kondisional) Template ditandai "Umum" tapi ada placeholder profil →
   filled_by otomatis diubah ke "admin". [info non-blocking]
```

**UI**: tabel editable — tiap baris = satu `template_placeholder_config`. Kolom editable: **Diisi oleh** (`filled_by` dropdown), **Tipe** (`tipe_input` dropdown), **Label** (`label_mahasiswa`), **Wajib** (`is_required`), urutan (drag `≡`). Kolom **Kelompok** read-only (info dari kamus). Slot TTD dikelompokkan visual.

**UX & State**:
- Nilai sudah **pre-filled otomatis** dari hasil scan (admin cuma koreksi bila salah) — mengurangi kerja.
- **Auto-remediasi (BR-04)**: bila `tipe_pemohon=umum` + placeholder `profil` → banner info + `filled_by` sudah otomatis jadi `admin` (bukan dialog ya/tidak).
- Label hanya relevan untuk `filled_by=mahasiswa`/`admin` (field yang tampil di form) → untuk `sistem`/`ttd` kolom label dinonaktifkan.
- Auto-save per perubahan atau tombol "Simpan Placeholder" → `✔`.

### 3.C.2 Aktifkan Template

**UX**: tombol **Aktifkan** ubah `status` draft→aktif. Sebelum aktif, sistem bisa mengingatkan bila ada placeholder `filled_by=mahasiswa` tanpa label (`label_mahasiswa` kosong) → `⚠️` "Beri label agar ramah mahasiswa." `✔` → template muncul di daftar mahasiswa (jika `is_permohonan_mandiri`) & bisa di-generate.

---

## 3.D — Setup Persyaratan (FindOrCreate) — `admin.template.syarat.store`

Bagian dalam hub 3.C. Menautkan `ref_syarat_surat` (master 2.D) ke template via `syarat_surat`.

**Wireframe — section + modal inline**:
```
Persyaratan
┌──────────────────────────────────────────────────────────┐
│ Tambah: [ ⌕ cari persyaratan…            v ]  (Select2)   │  ← cari ref_syarat_surat
│         └ tidak ketemu → [ + Buat Persyaratan Baru ]      │
├──────────────────────────────────────────────────────────┤
│ ≡ Fotokopi KHS         Wajib [x]        [🗑]              │  ← syarat_surat (is_required, urutan)
│ ≡ Surat Pernyataan     Wajib [ ]        [🗑]              │
└──────────────────────────────────────────────────────────┘

┌─ Modal: Buat Persyaratan Baru (inline) ───────────┐   ← FindOrCreate (dijanjikan Tahap 2)
│ Nama          [__________________]                │ ← ref_syarat_surat.nama
│ Deskripsi     [__________________]                │ ← deskripsi
│ Tipe file     [ pdf, jpg ]                        │ ← accepted_types
│ Ukuran max MB [ 5 ]                               │ ← max_size_mb
│ File contoh   [ ⬆ ] (opsional)                    │ ← template_file (media)
│                    [ Batal ] [ Simpan & Tautkan ] │
└───────────────────────────────────────────────────┘
```

**UI/UX**:
- **Select2 searchable** ke master persyaratan → pilih yang ada. Bila tidak ada → "+ Buat Baru" buka **modal inline** (tanpa keluar halaman) → simpan ke `ref_syarat_surat` **DAN** langsung ter-link.
- Item ter-link: toggle **Wajib** (`syarat_surat.is_required`) + **drag** urutan (`urutan`) + hapus link.
- `✔` inline saat menamb/mengubah. `∅` — "Belum ada persyaratan" (opsional, boleh kosong).

**Flow**: di hub → cari persyaratan → pilih/buat → set wajib & urutan. (Menjelaskan wireframe modal FindOrCreate yang dijanjikan di 2.D.)

---

## 3.E — Setup Data Tambahan — `admin.template.data-tambahan.*`

Field yang diisi mahasiswa tapi **tidak** masuk isi surat (mis. No. HP). Tersimpan di `template_data_tambahan_fields`.

**Wireframe**:
```
Data Tambahan                                   [ + Tambah Field ]
┌──────────────────────────────────────────────────────────┐
│ ≡ Label          │ Key         │ Tipe │ Wajib │ Aksi      │  ← template_data_tambahan_fields
│ ≡ No. HP Aktif   │ no_hp_aktif │ text │  [x]  │ [✎][🗑]   │
│ ≡ Alamat Domisili│ alamat_dom  │ text │  [ ]  │ [✎][🗑]   │
└──────────────────────────────────────────────────────────┘

┌─ Form field (modal) ───────────────────────┐
│ Label     [________________]               │ ← label
│ Key       [ no_hp_aktif ] (auto dari label)│ ← field_key (editable)
│ Tipe      [ text v ]                        │ ← tipe_input (text/date/number)
│ Helper    [________________] (opsional)     │ ← helper_text
│ [x] Wajib                                   │ ← is_required
│                  [ Batal ] [ Simpan ]       │
└─────────────────────────────────────────────┘
```

**UI/UX**:
- `field_key` **auto-generate dari label** (bisa diedit) → konsistensi key.
- Drag urutan (`urutan`). Hapus = **soft delete**; bila field sudah punya nilai dari permohonan → hard delete di-RESTRICT → `✖`/soft only.
- `∅` — "Belum ada data tambahan" (opsional).

**Flow**: hub → Tambah Field (modal) → label→key auto → tipe/helper/wajib → simpan.

---

## 3.F — Coba Template (Preview) — `admin.template.coba`

Uji generate tanpa menyimpan arsip (ephemeral).

**Wireframe — modal**:
```
┌─ Coba Template: Rekomendasi Magang ───────────────┐
│ (flat form SEMUA placeholder template)            │  ← template_placeholder_config
│ nama_mahasiswa   [__________]                     │
│ tanggal_surat    [ 11/07/2026 ]                   │
│ nama_perusahaan  [__________]                     │
│ ttd_1 (pejabat)  [ pilih pejabat v ]              │  ← (opsional untuk uji)
│ ...                                               │
│  Tanpa validasi wajib — mode percobaan            │
│              [ Batal ] [ Generate & Download ]    │
└───────────────────────────────────────────────────┘
```

**UI/UX & State**:
- Semua placeholder jadi input datar (tanpa validasi required) → cepat uji.
- `Generate & Download` → hasil **`.docx`** (Phase 1) berheader "PREVIEW — [nama] — [tanggal]", **tidak** tersimpan ke `surat_tercetak`.
- `⌛` saat generate; `✔` file terunduh. Berguna memastikan placeholder & layout benar sebelum aktivasi.

**Flow**: hub → Coba Template → isi contoh → Generate & Download → periksa hasil → tutup.

---

## 3.G — Panduan Placeholder — `admin.template.panduan`

Halaman referensi (read-only) agar admin tahu placeholder apa yang bisa dipakai saat menyusun file Word.

**Wireframe**:
```
Panduan Placeholder — salin & tempel ke Word: {{nama}}
┌─ Data Mahasiswa (otomatis) ──────────────────────────────┐
│ {{nama_mahasiswa}}  {{nim}}  {{prodi}}  {{fakultas}}     │  ← placeholder_definitions
├─ Waktu (bisa diubah) ────────────────────────────────────┤     (kelompok profil/waktu/…)
│ {{tanggal_surat}}  {{bulan_surat}}  {{tahun_surat}}      │
├─ Institusi ──────────────────────────────────────────────┤
│ {{nama_universitas}}  {{kode_universitas}}  {{logo_kampus}}│
├─ Nomor Surat ────────────────────────────────────────────┤
│ {{nomor_surat}}                                          │
├─ Tanda Tangan (ganti angka slot) ────────────────────────┤
│ {{ttd_1}} {{nama_ttd_1}} {{jabatan_ttd_1}} {{nip_ttd_1}} │
├─ Placeholder Bebas (tipe dideteksi dari nama) ───────────┤
│ awali upload_ → file · tanggal_ → date · _keterangan → textarea │
└──────────────────────────────────────────────────────────┘
```

**UI/UX**: daftar dari `placeholder_definitions` dikelompokkan per `kelompok` + konvensi slot TTD (regex) + aturan inferensi placeholder bebas. Tiap item ada tombol **copy**. Read-only; dibuka di tab baru saat menyusun template.

**Persona**: P2. **Flow**: buka dari daftar template / hub → salin nama placeholder → tempel ke Word.

---

## Ringkasan Ketergantungan Tahap 3 → 4

Template `aktif` dengan `is_permohonan_mandiri=✔` **baru** muncul di "Ajukan Surat" mahasiswa (Tahap 4). Placeholder `filled_by=mahasiswa` → jadi **Lapisan 2** form permohonan; `template_data_tambahan_fields` → **Lapisan 3**; `syarat_surat` → **Lapisan 4**. Jadi struktur form mahasiswa di Tahap 4 **diturunkan langsung** dari konfigurasi template di sini.

---

# TAHAP 4 — PERMOHONAN MAHASISWA (M-MHS-*)

> Persona utama **P3 (Mahasiswa)** — pengguna sesekali, kemungkinan dari HP → **alur terpandu, 1 kolom, status transparan**. Prasyarat: template `aktif` + `is_permohonan_mandiri` (Tahap 3). Layout `mahasiswa` (sidebar ringkas). Alur: Ajukan → (proses admin Tahap 5) → Riwayat/Download.

---

## 4.A — Ajukan Surat (M-MHS-AJUKAN)

**Keluarga**: `mahasiswa.ajukan.index` (pilih jenis) → `mahasiswa.ajukan.create` (form 4 lapisan) → `store`.

### 4.A.1 Pilih Jenis Surat — `mahasiswa.ajukan.index`

**Wireframe** (kartu/daftar):
```
Ajukan Surat
┌─ Filter ─────────────────────────────────────────────────┐
│ ⌕ [cari jenis surat]                                      │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Surat Keterangan Aktif                                   │  ← templates (WHERE
│ Keterangan singkat…            Estimasi: 1 hari kerja    │     is_permohonan_mandiri
│ 1 persyaratan                        [ Ajukan → ]        │     & status=aktif)
├──────────────────────────────────────────────────────────┤
│ Surat Rekomendasi Magang                                 │  ← deskripsi, sla_hari_kerja,
│ Keterangan…                    Estimasi: 3 hari kerja    │     jml syarat (syarat_surat)
│ 3 persyaratan                        [ Ajukan → ]        │
└──────────────────────────────────────────────────────────┘
```

**UI**: kartu per template (`nama`, `deskripsi`, estimasi `sla_hari_kerja`, jumlah persyaratan dari `syarat_surat`). Tombol "Ajukan →".

**UX & State**:
- Hanya menampilkan template `is_permohonan_mandiri=✔ AND status=aktif` (F5.1).
- `∅ Empty` — "Belum ada jenis surat yang tersedia untuk diajukan online."
- Estimasi SLA menetapkan ekspektasi mahasiswa sejak awal.

**Flow**: beranda → Ajukan Surat → pilih jenis → ke form (4.A.2).

### 4.A.2 Form Permohonan (4 Lapisan) — `mahasiswa.ajukan.create` / `store`

Struktur **diturunkan dari template** (Tahap 3). Wireframe:
```
Ajukan: Surat Rekomendasi Magang            Estimasi selesai: 3 hari kerja
┌─ Lapisan 1 · Data Anda (otomatis) ───────────────────────┐
│ Nama [Budi S.] NIM [20210001] Prodi [IF] Fakultas [FT]   │  ← mahasiswa (read-only)
├─ Lapisan 2 · Isian Surat ────────────────────────────────┤
│ Nama Perusahaan   [__________________]                   │  ← template_placeholder_config
│ Tanggal Mulai     [ 01/08/2026 ]  (date)                 │     WHERE filled_by=mahasiswa
│ Keperluan         [ textarea…            ]               │     (label dari label_mahasiswa)
├─ Lapisan 3 · Data Tambahan ──────────────────────────────┤
│ No. HP Aktif      [__________]                           │  ← template_data_tambahan_fields
│ Alamat Domisili   [__________]                           │
├─ Lapisan 4 · Persyaratan ────────────────────────────────┤
│ Fotokopi KHS *   [ ⬆ Upload ] [ Pilih dari Dokumen Saya ]│  ← syarat_surat + ref_syarat_surat
│ Proposal Magang * [ ⬇ template ] [ ⬆ Upload ] [ Pilih… ] │     (⬇ jika ada template_file)
└──────────────────────────────────────────────────────────┘
                       [ Simpan Draft ]   [ Ajukan Permohonan ]
```

**UI — komponen per lapisan**:
- **L1** read-only chip data profil (`mahasiswa`) — tak bisa diedit (BR-06).
- **L2** field dinamis sesuai `tipe_input` (text/date/number/textarea), label dari `label_mahasiswa`. Nilai → `isian_form` JSON.
- **L3** field `template_data_tambahan_fields` + `helper_text` di bawah field. Nilai → `permohonan_data_tambahan_values`.
- **L4** per persyaratan: `⬇` unduh template (jika `ref_syarat_surat.template_file`), lalu **dua opsi**: "Upload" (FilePond) atau "Pilih dari Dokumen Saya" (modal ke `dokumen_mahasiswa`). Wajib (`*`) dari `syarat_surat.is_required`.

**UX & State**:
- `✖` — field/persyaratan wajib kosong: blok submit, tandai field merah + pesan.
- Upload baru → **otomatis tersimpan ke Dokumen Saya** (`dokumen_mahasiswa`) sekaligus (reusable) — F5.1.
- `⌛` saat upload; `✔` file tercentang hijau.
- **Simpan Draft** → `status=draft`, tak masuk antrean admin; **Ajukan** → validasi penuh → `status=pending` → `✔` "Permohonan diajukan" + email konfirmasi → redirect ke Riwayat.

**Flow**: pilih jenis → isi L2/L3, lengkapi L4 → Simpan Draft (opsional) / Ajukan → validasi → simpan `permohonan_surat`(+values+syarat) → redirect Riwayat.

**Variasi**: "Pilih dari Dokumen Saya" (modal list dokumen) vs "Upload" baru — perbedaan kecil (satu modal pemilih), tidak perlu wireframe terpisah.

**⚠️ PERLU KONFIRMASI**: pemilihan **unit tujuan** (`permohonan_surat.unit_id`). Karena template bisa n-n unit (§7.1), unit tidak otomatis. Bila template terhubung >1 unit, mahasiswa perlu memilih unit tujuan — **field ini belum ada di wireframe** karena PRD tidak jelas. Konfirmasi: tampilkan dropdown unit bila template punya >1 unit?

---

## 4.B — Dokumen Saya (M-MHS-DOKUMEN) — `mahasiswa.dokumen.*`

Media library pribadi agar file dipakai ulang lintas permohonan.

**Wireframe**:
```
Dokumen Saya                                     [ + Upload Dokumen ]
┌──────────────────────────────────────────────────────────┐
│ Nama          │ Kategori Syarat│ Tgl Upload │Dipakai│ Aksi│  ← dokumen_mahasiswa
│ KHS Smt 5     │ Fotokopi KHS   │ 10/07/2026 │ 2 pmh │ [🗑] │  ← (dipakai: count
│ KTP           │ (umum)         │ 05/07/2026 │ 0     │ [🗑] │     permohonan_syarat)
└──────────────────────────────────────────────────────────┘

┌─ Upload Dokumen (modal) ───────────────────┐
│ Nama       [________________]              │ ← nama
│ Kategori   [ Fotokopi KHS v ] (opsional)   │ ← syarat_id (Select2, nullable)
│ File       [ ⬆ pilih ]                     │ ← media
│                 [ Batal ] [ Simpan ]       │
└────────────────────────────────────────────┘
```

**UI/UX & State**:
- Kolom "Dipakai" = jumlah permohonan memakai (via `permohonan_syarat`).
- **Hapus dicegah** bila dipakai permohonan aktif (pending/diproses) → `✖` "Sedang dipakai permohonan aktif."
- `∅ Empty` — "Belum ada dokumen. Upload akan otomatis tersimpan saat mengajukan surat."

**Flow**: buka Dokumen Saya → Upload (modal) atau hapus. Juga terisi otomatis dari upload di form permohonan (4.A.2).

---

## 4.C — Riwayat Permохonan (M-MHS-RIWAYAT) — `mahasiswa.permohonan.index` / `show`

### 4.C.1 Daftar Riwayat — `index`

**Wireframe**:
```
Riwayat Permohonan
┌─ Filter (§17) ───────────────────────────────────────────┐
│ ⌕ [cari]   Status [ Semua v ]   Jenis [ v ]               │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│ Jenis Surat      │ Tgl Ajukan │ Status        │ Aksi      │  ← permohonan_surat
│ Rekomendasi Mgg  │ 04/07/2026 │ ★ Menunggu    │ [Detail]  │     (WHERE mahasiswa_id=self)
│ Ket. Aktif       │ 01/07/2026 │ ★ Selesai     │ [Detail][⬇]│
│ Dispensasi       │ 28/06/2026 │ ★ Ditolak     │ [Detail][↻]│
└──────────────────────────────────────────────────────────┘
```

**UI/UX**: `x-ui.datatable` + filter status/jenis. Badge status berwarna (`x-ui.badge-status`). **Aksi kondisional per status** (lihat 4.D-4.F). `∅` — "Belum ada permohonan."

### 4.C.2 Detail Permohonan — `show`

**Wireframe** (area aksi berubah menurut status):
```
Detail: Surat Rekomendasi Magang        Status: ★ [status]
┌─ Data Permohonan (4 lapisan, read-only) ─────────────────┐
│ L1 Profil · L2 Isian (isian_form) · L3 Data tambahan     │  ← permohonan_surat +
│ L4 File persyaratan [⬇ per file]                          │     values + syarat
├─ Informasi Status ───────────────────────────────────────┤
│ (jika ditolak)  Alasan: "KHS buram"        [ Ajukan Ulang]│  ← catatan_penolakan
│ (jika resubmit) "Pengajuan ulang dari #12"  → link        │  ← parent_permohonan_id
│ (jika selesai)  [ ⬇ Download Surat ] / 📍 Ambil di Kampus │  ← surat_tercetak.metode_pengambilan
├─ Aksi (jika pending) ────────────────────────────────────┤
│ [ Edit ]   [ Batalkan ]                                   │
└──────────────────────────────────────────────────────────┘
```

**UI/UX**: menampilkan 4 lapisan yang disubmit (read-only). Blok "Informasi Status" & "Aksi" **kondisional** (4.D-4.F). `catatan_penolakan` tampil bila ditolak. Label "Pengajuan ulang dari #X" bila `parent_permohonan_id` ada.

**✅ KEPUTUSAN (status tracker)** — **tidak ada tabel/kolom baru**:
- **Mahasiswa**: tampilkan **badge status** (`x-ui.badge-status`, sudah ada) + info yang sudah ada (`approved_at`, `catatan_penolakan`). Opsional dirender sebagai **stepper** (Diajukan → Diverifikasi → Disetujui → Selesai) — murni view dari kolom `status`, nol biaya backend, sisi admin tak berubah.
- **Audit admin (Super Admin)**: pakai **Spatie ActivityLog** (sudah di stack, ARCHITECTURE §2) — aksi approve/tolak/generate memanggil `activity()->log()`. Ini untuk audit, **bukan** ditampilkan ke mahasiswa.
- **Tidak** membuat tabel `permohonan_status_log`. PRD F5.3 "status history" dibaca sebagai **status tracker**, bukan log transisi.

---

## 4.D — Edit & Batalkan (status `pending`)

**Variasi perilaku** — hanya muncul saat `status=pending`:
- **Edit** — buka ulang form 4.A.2 dengan data pre-fill (`isian_form`, values, syarat) → submit update record **sama**, status tetap `pending`. Setelah admin membuka (→`diverifikasi`), tombol Edit **hilang** (BR-07).
- **Batalkan** — dialog konfirmasi (`js-confirm`) → `status=dibatalkan` (final, tak bisa diurungkan) → `✔`.

**State**: bila status sudah bukan `pending` saat aksi ditekan (mis. admin baru memproses) → `✖` "Permohonan sudah diproses, tidak bisa diubah."

---

## 4.E — Ajukan Ulang / Resubmit (status `ditolak`) — `mahasiswa.permohonan.resubmit`

**Flow & UI**:
- Tombol **Ajukan Ulang** (hanya saat `ditolak`) → buka form baru **pre-filled** dari permohonan lama (`isian_form`, `permohonan_data_tambahan_values`, file `dokumen_mahasiswa` yang sama).
- Membuat **`permohonan_surat` baru** dengan `parent_permohonan_id` = id lama → jejak rantai.
- Mahasiswa boleh ubah/tambah sebelum submit → `status=pending` lagi.
- Detail permohonan baru menampilkan "Pengajuan ulang dari #[lama]".

---

## 4.F — Download Surat (status `selesai`)

**Variasi besar berdasarkan `surat_tercetak.metode_pengambilan`** (BR-12):

| Kondisi | UI |
|---|---|
| `metode_pengambilan = download` | Tombol **[ ⬇ Download Surat ]** aktif → unduh file (PDF jika ada, else DOCX) |
| `metode_pengambilan = ambil_di_kampus` | **Tanpa tombol download** — badge info **📍 "Surat siap diambil di kampus"** |

**UX**: mahasiswa yang harus ambil fisik tidak melihat tombol unduh sama sekali (mencegah kebingungan). `download` route dilindungi Policy (hanya pemilik).

---

## 4.G — Profil Saya — `mahasiswa.profil.show`

**Wireframe**:
```
Profil Saya
┌──────────────────────────────────────────┐
│ Nama      : Budi Setiawan                │  ← mahasiswa.nama
│ NIM       : 20210001                     │  ← nim
│ Prodi     : Teknik Informatika           │  ← prodi
│ Fakultas  : Fakultas Teknik              │  ← fakultas
│ Email     : budi@…                       │  ← users.email
│  (read-only — snapshot dari SIAKAD)      │
├─ Ubah Password (Fortify) ────────────────┤
│ Password Lama   [__________]             │  ← users.password (verifikasi)
│ Password Baru   [__________]             │
│ Konfirmasi      [__________]             │
│                       [ Simpan ]         │
└──────────────────────────────────────────┘
```

**UI/UX**: data profil **read-only** (snapshot SIAKAD, PRD §4.7). Section **Ubah Password** memakai Fortify `update-password` (password lama + baru + konfirmasi) → `✔` "Password diperbarui" / `✖` "Password lama salah". `∅` tak berlaku.

**✅ KEPUTUSAN (Ubah Password)**: mahasiswa **boleh** ganti password sendiri di profil. Pakai **fitur bawaan Laravel Fortify** (`update-password` — form password lama + baru + konfirmasi), tanpa mekanisme kustom. Tambah section "Ubah Password" di halaman Profil (dan berlaku juga untuk admin). Tidak ada perubahan skema (`users.password` sudah ada).

---

# TAHAP 5 — REVIEW/APPROVAL & GENERATE (M-PERM-ADM, M-GENERATE)

> Persona **P2 (Admin Surat)**. Pasangan hilir Tahap 4: memproses `permohonan_surat` (verifikasi → setujui/tolak) lalu **Generate** surat. Alur: Daftar → Detail (auto-verifikasi) → Setujui/Tolak → Generate. Sub-flow A (dari permohonan) & B (langsung admin) berbagi form generate.

---

## 5.A — Review & Approval (M-PERM-ADM)

### 5.A.1 Daftar Permohonan — `admin.permohonan.index`

**Wireframe**:
```
Permohonan Masuk
┌─ Filter (§17) ───────────────────────────────────────────────┐
│ ⌕ [nama/NIM]  Status [ v ]  Jenis [ v ]  Tgl [dari]–[sampai]  │
└──────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────┐
│ Pemohon (NIM) │ Jenis Surat   │ Tgl Ajukan │ Status       │Aksi│  ← permohonan_surat
│ Budi (2021…)  │ Rekomendasi Mg│ 04/07/2026 │ ★ Pending    │[▸] │     + mahasiswa + templates
│ Ani (2021…)   │ Ket. Aktif    │ 03/07/2026 │ ★ Diverifikasi│[▸]│
└──────────────────────────────────────────────────────────────┘
```

**UI/UX**: tabel + filter (status, jenis, **rentang tanggal**, search nama/NIM — §17). Badge status. Baris klik → detail. `∅` — "Belum ada permohonan."

### 5.A.2 Detail Permohonan — `admin.permohonan.show`

**Wireframe**:
```
Detail Permohonan #88            Status: ★ Diverifikasi
Pemohon: Budi Setiawan (20210001) · Rekomendasi Magang
(jika resubmit) "Pengajuan ulang dari #12" → link          ← parent_permohonan_id
┌─ Data (4 lapisan, read-only) ────────────────────────────┐
│ L1 Profil · L2 Isian (isian_form) · L3 Data tambahan     │  ← permohonan_surat + values
│ L4 Persyaratan  [⬇ KHS] [⬇ Proposal]                     │  ← permohonan_syarat [download]
└──────────────────────────────────────────────────────────┘
        [ ✔ Setujui ]        [ ✖ Tolak ]
```

**UX & State**:
- **Membuka detail `pending` → otomatis `status=diverifikasi`** (idempotent, F6) → kunci Edit mahasiswa (BR-07).
- Admin unduh tiap file persyaratan untuk verifikasi.
- Tombol Setujui/Tolak hanya saat status `diverifikasi`/`pending`. Setelah `disetujui`/`ditolak` → diganti info + (jika disetujui) tombol Generate.

### 5.A.3 Setujui (Proxy Approval) — `admin.permohonan.approve`

**Wireframe — modal**:
```
┌─ Setujui Permohonan ──────────────────────────┐
│ Atas persetujuan pejabat [ Dr. Ahmad—Kaprodi v]│ ← pejabat_id (Select2, master pejabat)
│ Keterangan (log internal) [________________]  │ ← catatan_approval (wajib)
│                    [ Batal ] [ Konfirmasi ]   │
└───────────────────────────────────────────────┘
```
**UX**: wajib pilih pejabat + isi keterangan (proxy approval, PRD 4.3). `✔` → `status=disetujui`, isi `approved_by`(admin)/`pejabat_id`/`catatan_approval`/`approved_at` → **status langsung terlihat di Riwayat mahasiswa** + email. **Belum ada file** (BR-12) → muncul tombol "Generate Surat".

### 5.A.4 Tolak — `admin.permohonan.reject`

**Wireframe — modal**:
```
┌─ Tolak Permohonan ────────────────────────────┐
│ Alasan (ditampilkan ke mahasiswa) [_________] │ ← catatan_penolakan (wajib)
│                    [ Batal ] [ Tolak ]        │
└───────────────────────────────────────────────┘
```
**UX**: alasan wajib → `status=ditolak`, `catatan_penolakan` tampil ke mahasiswa (4.C.2) + email → mahasiswa bisa Resubmit (4.E).

**✅ KEPUTUSAN (un-approve)**: **tidak diperlukan** di Phase 1. Permohonan yang sudah `disetujui` bersifat maju (lanjut ke Generate). Tidak ada aksi un-approve, tidak ada kolom/mekanisme tambahan. (Bila kelak ada kebutuhan koreksi, bahas di Phase 2.)

---

## 5.B — Generate & Cetak Surat (M-GENERATE)

Dua titik masuk berbeda, **form generate sama**.

### 5.B.1 Sub-flow A — Generate dari Permohonan — `admin.permohonan.generate`

Tombol "Generate Surat" pada permohonan `disetujui` → buka **Form Generate** (5.B.3), **pre-filled** dari permohonan (`isian_form` + profil mahasiswa). `surat_tercetak.permohonan_id` = id permohonan.

### 5.B.2 Sub-flow B — Generate Langsung — `admin.generate.create` / `store`

**Wireframe — langkah pemilihan**:
```
Generate Langsung
Pilih Template [ Rekomendasi Magang v ]            ← templates (aktif)
├─ jika tipe_pemohon = "mahasiswa" ───────────────
│  Cari Mahasiswa [ ⌕ ketik NIM/nama…      v ]     ← cari-mahasiswa AJAX (mahasiswa)
│    → dipilih → autofill nama/nim/prodi/fakultas
└─ jika tipe_pemohon = "umum" → langsung Form Generate
```
**UX**: `tipe_pemohon=mahasiswa` → **langkah "Cari Mahasiswa"** (Select2 AJAX) dulu → autofill profil. `tipe_pemohon=umum` → langsung form, placeholder diisi manual. `surat_tercetak.permohonan_id = NULL`.

### 5.B.3 Form Generate (dipakai kedua sub-flow)

**Wireframe**:
```
Generate Surat: Rekomendasi Magang
┌─ Data Surat ─────────────────────────────────────────────┐
│ Unit Penerbit  [ BAA v ]                                 │ ← surat_tercetak.unit_id (Select2)
│ Tanggal Surat  [ 19/07/2026 ]                            │ ← waktu (prefill hari ini)
│ Nomor Surat *  [ 005/BAA/UNsP/VII/2026 ]                 │ ← nomor_surat (prefill suggestion)
│                ✓ belum dipakai  /  ⚠ sudah dipakai       │ ← AJAX cek-nomor (surat_tercetak)
├─ Isian Surat ────────────────────────────────────────────┤
│ Nama Perusahaan [ PT Telkom ]  (A: prefill · B: manual)  │ ← filled_by=admin + bebas
│ … (placeholder lain sesuai template)                     │   → data_placeholder JSON
├─ Penandatangan ──────────────────────────────────────────┤
│ Slot 1 [ Dr. Ahmad — Kaprodi v ]   ✓ TTD tersedia        │ ← surat_penandatangan
│ Slot 2 [ Dr. Siti — Dekan v ]      ⚠ TTD basah           │   (pejabat_id → snapshot
│                                                          │    nama/jabatan/nip/file_ttd)
├─ Metode Pengambilan ─────────────────────────────────────┤
│ ( ) Download   (•) Ambil di Kampus                       │ ← metode_pengambilan
│   Saran: "Ambil di Kampus" (ada slot TTD basah)          │   (saran otomatis BR-10)
└──────────────────────────────────────────────────────────┘
        [ 👁 Preview Draft ]        [ ✔ Generate Final ]
```

**UI — komponen**:
- **Data Surat**: Unit penerbit (`surat_tercetak.unit_id`), tanggal (prefill hari ini), **Nomor Surat** dengan **saran otomatis** dari arsip + **AJAX cek duplikat** realtime.
- **Isian**: field `filled_by=admin` + placeholder bebas; di sub-flow A ter-prefill dari `isian_form`, sub-flow B manual.
- **Penandatangan**: satu **dropdown pejabat per slot** (dari master pejabat) → mengisi snapshot nama/jabatan/NIP + `file_ttd_path`. Indikator `✓ TTD tersedia` / `⚠ TTD basah` per slot.
- **Metode Pengambilan**: radio dengan **saran default** berdasarkan kelengkapan TTD (BR-10).

**UX & State**:
- `⚠ Nomor sudah dipakai` — peringatan realtime; UNIQUE `(nomor_surat, unit_id)` sebagai hard guard di simpan → `✖` jika tetap.
- `⚠ TTD basah` pada slot → sistem menyarankan "Ambil di Kampus".
- **Preview Draft** → `.docx`/PDF berheader "DRAFT", **tidak disimpan**.
- **Generate Final** → substitusi semua placeholder → simpan `surat_tercetak` (`data_placeholder` snapshot, `file_docx_path`, `file_pdf_path` bila LibreOffice ada, `qr_hash`) + `surat_penandatangan` per slot → (sub-flow A) `permohonan_surat.status=selesai` + email sesuai metode.
- `✔` → redirect ke Arsip (Tahap 6) / unduh.

**Variasi terangkum**:
| Aspek | Sub-flow A (dari permohonan) | Sub-flow B (langsung) |
|---|---|---|
| Titik masuk | tombol pada permohonan disetujui | menu Generate Langsung |
| Data mahasiswa | prefill dari permohonan | cari mahasiswa (jika tipe=mahasiswa) / tanpa (umum) |
| Isian surat | prefill `isian_form` | manual |
| `permohonan_id` | terisi | NULL |
| Form generate | **sama** | **sama** |

**⚠️ PERLU KONFIRMASI**: (a) **PDF opsional** — bila LibreOffice absen, output DOCX saja; QR yang diletakkan di PDF perlu penanganan (verifikasi tetap via nomor). (b) Draft "DRAFT" watermark — mekanisme render (docx vs pdf) tergantung ketersediaan LibreOffice.

---

# TAHAP 6 — ARSIP SURAT & VERIFIKASI PUBLIK (M-ARSIP, M-VERIFIKASI)

> Hasil Generate (Tahap 5) mendarat di sini. **Arsip immutable** (P2 Admin) + **Verifikasi publik** via QR (tanpa login, siapa saja). Menekankan **anti-fraud**: arsip tak bisa diubah, cetak ulang membuat rantai "digantikan" yang terlihat di halaman verifikasi.

---

## 6.A — Arsip Surat Tercetak (M-ARSIP) — Admin (P2)

### 6.A.1 Daftar Arsip — `admin.arsip.index`

**Wireframe**:
```
Arsip Surat Tercetak                                     [ ⬇ Export ]
┌─ Filter (§17) ───────────────────────────────────────────────┐
│ ⌕ [nomor/nama/NIM]  Jenis [ v ]  Tgl [dari]–[sampai]          │
└──────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────┐
│ Nomor Surat        │ Jenis     │ Penerima   │ Tgl  │ Status  │ Aksi   │  ← surat_tercetak
│ 005/BAA/UNsP/VII/26│ Rekom. Mag│ Budi (2021)│19/07 │ ★ aktif │[▸][⬇] │   +template +mahasiswa
│ 004/BAA/UNsP/VII/26│ Ket. Aktif│ Ani (2021) │18/07 │digantikan│[▸][⬇]│   (via data_placeholder
│ 003/…              │ Nota Dinas│ — (umum)   │15/07 │ ★ aktif │[▸][⬇] │    utk umum: tanpa penerima)
└──────────────────────────────────────────────────────────────┘
```

**UI/UX**: tabel + filter (jenis, **rentang tanggal**, search nomor/nama/NIM — §17). Badge status: aktif/digantikan/dibatalkan. Penerima kosong untuk surat `umum` (tanpa mahasiswa). `∅` — "Belum ada surat tercetak."
- **Aksi `[⬇] Download`** (wajib) — unduh file surat tercetak **langsung dari list** tanpa buka detail: `file_pdf_path` (bila ada) atau `file_docx_path`. Bila keduanya ada, tombol bisa dropdown (PDF / DOCX).

### 6.A.2 Detail Arsip — `admin.arsip.show`

**Wireframe**:
```
Arsip: 005/BAA/UNsP/VII/2026            Status: ★ Aktif
Jenis: Rekomendasi Magang · Penerima: Budi Setiawan (20210001)
Digenerate: Dewi (Admin) — 19/07/2026                  ← digenerate_oleh / digenerate_at
┌─ File ───────────────────────────────────────────────────┐
│ [ ⬇ PDF ]  [ ⬇ DOCX ]                                    │ ← file_pdf_path / file_docx_path
├─ Penandatangan (snapshot) ───────────────────────────────┤
│ 1. Dr. Ahmad — Kaprodi IF   (TTD gambar)                 │ ← surat_penandatangan
│ 2. Dr. Siti — Dekan FT      (TTD basah)                  │   (nama/jabatan/nip snapshot)
├─ Data Snapshot ──────────────────────────────────────────┤
│ nama_mahasiswa: Budi… · nomor_surat: 005… · dst          │ ← data_placeholder (JSON, read-only)
├─ Verifikasi ─────────────────────────────────────────────┤
│ QR [▦]   URL: surat.kampus.ac.id/verify/abc123…          │ ← qr_hash
└──────────────────────────────────────────────────────────┘
                       [ 🔁 Cetak Ulang ]
(jika digantikan) ► "Digantikan oleh 006/…" → link         ← replaced_by_id / replaced_reason
(jika dibatalkan) ► "Dibatalkan: [alasan]"
```

**UI/UX & State**:
- **Read-only sepenuhnya** — tidak ada tombol "Edit" di manapun (arsip immutable, F8/BR-11).
- Menampilkan snapshot `data_placeholder` + penandatangan snapshot → nilai persis saat generate (imun perubahan master).
- Bila `status=digantikan` → banner + link ke pengganti (`replaced_by_id`); bila `dibatalkan` → tampil alasan.

### 6.A.3 Cetak Ulang — `admin.arsip.cetak-ulang`

**Wireframe — modal**:
```
┌─ Cetak Ulang Surat ───────────────────────────────┐
│ ⚠ Entri lama akan ditandai "DIGANTIKAN" (permanen)│
│ Alasan cetak ulang * [__________________]         │ ← replaced_reason (wajib)
│                    [ Batal ] [ Lanjut Generate ]  │
└───────────────────────────────────────────────────┘
```

**UX & anti-fraud (penting)**:
- Cetak ulang **tidak mengubah** entri lama — ia **membuat entri baru** (nomor baru via Form Generate 5.B.3, prefilled dari `data_placeholder` lama). Setelah final:
  - entri baru: `status=aktif`
  - entri lama: `status=digantikan` + `replaced_by_id`=baru + `replaced_reason`
- **Kenapa begitu (cegah fraud)**: karena arsip tak pernah di-overwrite, tiap versi punya jejak. Jika salinan lama beredar, **halaman verifikasi (6.B) menampilkan "DIGANTIKAN"** → penerima bisa tahu dokumen itu bukan versi sah terkini. Rantai `replaced_by_id` bisa ditelusuri Super Admin.
- `✔` → redirect ke detail arsip baru.

**⚠️ PERLU KONFIRMASI**: apakah cetak ulang boleh mempertahankan **nomor sama** atau **wajib nomor baru**? UNIQUE `(nomor_surat, unit_id)` memaksa nomor baru. Bila kebijakan administratif ingin nomor sama untuk revisi, perlu penyesuaian constraint. (Default aman: nomor baru.)

### 6.A.4 Export — `admin.arsip.export`
Tombol Export → unduh **Excel** arsip sesuai filter aktif (kolom dari `surat_tercetak`). `⌛` saat menyiapkan; `✔` file terunduh.

---

## 6.B — Verifikasi Publik (M-VERIFIKASI) — `verify.show` — **tanpa login**

Halaman publik (layout `guest`) diakses via scan QR / URL `verify/{qr_hash}`. Persona: **siapa saja** (penerima surat, instansi).

**Wireframe — status VALID**:
```
        ┌──────── Verifikasi Keaslian Surat ────────┐
        │ [ LOGO ]  [Nama Universitas]              │ ← settings (is_public)
        ├───────────────────────────────────────────┤
        │            ✔ SURAT VALID                  │ ← surat_tercetak.status=aktif
        │ Jenis   : Surat Rekomendasi Magang        │ ← template
        │ Penerima: Budi S.                         │ ← nama depan+inisial (data_placeholder) ⚠
        │ Nomor   : 005/BAA/UNsP/VII/2026           │ ← nomor_surat
        │ Terbit  : 19 Juli 2026                    │ ← digenerate_at
        └───────────────────────────────────────────┘
```

**Variasi status (perbedaan pada banner + baris tambahan)**:
| `status` | Banner | Tambahan |
|---|---|---|
| `aktif` | ✔ **SURAT VALID** | data surat lengkap |
| `digantikan` | ⚠ **TELAH DIGANTIKAN** | "Versi sah: nomor [pengganti]" (dari `replaced_by_id`) |
| `dibatalkan` | ✖ **SURAT DIBATALKAN** | tanpa detail lanjut |
| hash tidak ditemukan | ✖ **SURAT TIDAK DITEMUKAN** | — |

**UI/UX & State**:
- **Tanpa login**, minimalis, mobile-friendly (di-scan dari HP).
- **Privasi**: hanya **nama depan + inisial** (mis. "Budi S."), bukan nama penuh/NIM (PRD F8, ARCHITECTURE QR). Untuk surat `umum` (tanpa mahasiswa) → baris Penerima disembunyikan.
- `qr_hash` acak (HMAC) → tak bisa ditebak; halaman hanya membaca, tak mengekspos data sensitif.

**Flow**: scan QR / buka URL → sistem cari `surat_tercetak` by `qr_hash` → render status. Tidak ada aksi (murni informasi).

**⚠️ PERLU KONFIRMASI (fase)**: PRD F8 menaruh "halaman verifikasi publik" di Phase 1, tetapi `perancangan-murni.md` §6 mencantumkan **"QR + Portal Verifikasi Publik" sebagai Phase 2** (QR di-generate Phase 1). Perlu keputusan: portal verifikasi masuk Phase 1 atau 2? (Spec ini siap dipakai kapan pun diaktifkan.)

---

# TAHAP 7 — SURAT MASUK & SURAT KELUAR (M-MASUK, M-KELUAR)

> **Independen** dari alur permohonan (arsitektur 3 Kamar — Kamar 3). Persona **P2 (Staf TU)**. Pencatatan surat fisik: masuk (+disposisi) dan keluar (manual). Buku agenda = *view* dari tabel, bukan tabel terpisah.

---

## 7.A — Surat Masuk (M-MASUK)

### 7.A.1 Buku Agenda Masuk / Daftar — `admin.surat-masuk.index` & `.agenda`

**Wireframe**:
```
Surat Masuk / Buku Agenda           [ ⬇ Export ] [ + Catat Surat Masuk ]
┌─ Filter (§17) ───────────────────────────────────────────────────┐
│ ⌕ [pengirim/perihal/no]  Tahun[v]  Tgl[dari]–[sampai]  Disposisi[v]│
└──────────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────┐
│No.Ag│Tgl Terima│No.Surat│Pengirim│Perihal │Disposisi ke│Status│ Aksi   │  ← surat_masuk
│ 12  │15/07/26  │123/…   │Dikti   │Undangan│WR I        │belum │[▸][✎][⬇]│  (+disposisi)
└──────────────────────────────────────────────────────────────────┘
```
**UI/UX**: kolom buku agenda (F9.3) + filter tahun/rentang tanggal/pengirim/status disposisi (§17). "Disposisi ke" & "Status" ringkas dari `disposisi_surat_masuk`. `∅` — "Belum ada surat masuk." Export Excel/PDF sesuai filter.
- **Aksi `[⬇] Download`** — unduh **berkas scan** surat (`surat_masuk.berkas_scan`, media `scan`) langsung dari list.

### 7.A.2 Form Surat Masuk — `admin.surat-masuk.create/edit`

**Wireframe**:
```
┌─ Catat Surat Masuk ──────────────────────────────────┐
│ No. Agenda   [ (auto setelah simpan) ]              │ ← nomor_agenda (auto per tahun, R/O)
│ Tgl Terima * [ 15/07/2026 ]                         │ ← tanggal_terima
│ No. Surat *  [__________]  (dari pengirim)          │ ← nomor_surat
│ Tgl Surat    [ 10/07/2026 ]                         │ ← tanggal_surat (nullable)
│ Pengirim *   [__________________]                   │ ← pengirim
│ Perihal *    [__________________]                   │ ← perihal
│ Kode Klasif. [__________]  ⚠                         │ ← kode_klasifikasi (teks bebas)
│ Keterangan   [__________________]                   │ ← keterangan (nullable)
│ Berkas Scan *[ ⬆ PDF/JPG ]                          │ ← media 'scan' (wajib)
│ Lampiran     [ ⬆ multi ] (opsional)                 │ ← media 'lampiran'
│                        [ Batal ] [ Simpan ]         │
└──────────────────────────────────────────────────────┘
```
**UI/UX & State**: `nomor_agenda` **di-generate sistem** (per tahun), tampil read-only setelah simpan (bukan input, F9.1). Scan wajib; lampiran multi opsional (FilePond → media). Wajib: tgl terima, no surat, pengirim, perihal → `✖` bila kosong. Soft delete.

### 7.A.3 Disposisi — `admin.surat-masuk.disposisi.*`

Satu surat masuk → banyak disposisi (one-to-many). Ada di detail surat.

**Wireframe — section + modal**:
```
Disposisi Surat #12                        [ + Tambah Disposisi ]
┌──────────────────────────────────────────────────────────┐
│ Tujuan │ Instruksi     │Sifat │Batas │ Status         │Aksi│  ← disposisi_surat_masuk
│ WR I   │ Tindaklanjuti…│segera│18/07 │ belum          │[✎] │
│ LPPM   │ Koordinasi…   │biasa │ —    │ sudah (Dewi)   │[✎] │  ← ditindaklanjuti_oleh/at
└──────────────────────────────────────────────────────────┘
                            [ 🖨 Cetak Lembar Disposisi ]

┌─ Form Disposisi (modal) ────────────────────┐
│ Tujuan     [__________] (teks bebas, Ph1)   │ ← tujuan
│ Instruksi  [__________________]             │ ← isi_instruksi
│ Sifat      (•)biasa ( )segera ( )rahasia    │ ← sifat
│ Batas Waktu[ 18/07/2026 ] (opsional)        │ ← batas_waktu
│ Status     ( )belum  ( )sudah ditindaklanjuti│ ← status
│  └(jika sudah) Catatan TL [__________]      │ ← catatan_tindaklanjut
│                   [ Batal ] [ Simpan ]      │
└─────────────────────────────────────────────┘
```
**UI/UX & State**:
- Tujuan **teks bebas** (Phase 1, bukan FK akun pejabat — F9.2).
- Set status → `sudah`: sistem otomatis isi `ditindaklanjuti_oleh`(admin) + `ditindaklanjuti_at`(now).
- Batas waktu terlewat → highlight (dashboard counter, 1.B.1).
- `∅` — "Belum ada disposisi."

### 7.A.4 Cetak Lembar Disposisi — `admin.surat-masuk.disposisi.cetak`
Tombol → generate **PDF lembar disposisi** (HTML→PDF via mPDF) untuk diserahkan fisik ke pejabat. Data dari `surat_masuk` + `disposisi_surat_masuk`. Buka di tab baru / unduh.

---

## 7.B — Surat Keluar / Buku Agenda Keluar (M-KELUAR)

100% manual entry (Kamar 3) — **tidak** ada baris otomatis dari Generate (Tahap 5).

### 7.B.1 Buku Agenda Keluar — `admin.surat-keluar.index`

**Wireframe**:
```
Surat Keluar / Buku Agenda Keluar    [ ⬇ Export ] [ + Catat Surat Keluar ]
┌─ Filter (§17) ───────────────────────────────────────────┐
│ ⌕ [tujuan/perihal]  Tahun [ v ]  Tgl [dari]–[sampai]      │
└──────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────┐
│No.Ag│No.Surat│Tgl Surat│Tujuan │Perihal  │Ket│ Aksi          │  ← surat_keluar
│ 8   │045/…   │12/07/26 │PT ABC │Kerjasama│…  │[✎][🗑][⬇]     │
└──────────────────────────────────────────────────────────┘
```
**UI/UX**: filter tahun/rentang tanggal/tujuan/perihal (§17). **Aksi `[⬇] Download`** — unduh **berkas scan** surat keluar (`surat_keluar.berkas_scan`, media `scan`) langsung dari list; tombol dinonaktifkan/disembunyikan bila scan tidak ada (opsional).

### 7.B.2 Form Surat Keluar — `admin.surat-keluar.create/edit`

**Wireframe**:
```
┌─ Catat Surat Keluar ─────────────────────────┐
│ No. Agenda  [ (auto) ]                       │ ← nomor_agenda (auto per tahun, R/O)
│ No. Surat * [__________]                     │ ← nomor_surat
│             ✓ belum dipakai / ⚠ duplikat     │ ← AJAX cek-nomor (surat_keluar)
│ Tgl Surat * [ 12/07/2026 ]                   │ ← tanggal_surat
│ Tujuan *    [__________________]             │ ← tujuan
│ Perihal *   [__________________]             │ ← perihal
│ Kode Klasif.[__________]  ⚠                   │ ← kode_klasifikasi
│ Keterangan  [__________________]             │ ← keterangan (nullable)
│ Berkas Scan [ ⬆ ] (opsional)                 │ ← media 'scan'
│                      [ Batal ] [ Simpan ]    │
└──────────────────────────────────────────────┘
```
**UI/UX & State**: `nomor_agenda` auto per tahun (R/O). **AJAX cek duplikat** `nomor_surat` sebelum simpan. Scan opsional. Soft delete. Export Excel/PDF. `∅` — "Belum ada surat keluar."

---

# APPENDIX — Cross-cutting

## A. Upload File (M-MEDIA) — `media.upload` / `media.show`

**Bukan halaman** — perilaku komponen. `x-form.file` (FilePond) → POST `media.upload` (AJAX, temporary upload) → di-attach ke `media` saat form induk disubmit. `media.show` menyajikan file **disk private** via Policy (ARCHITECTURE §9). Dipakai lintas fitur: logo (1.C.1), TTD pejabat (1.C.3), file contoh syarat (2.D), `.docx` template (3.B), dokumen mahasiswa (4.A.2/4.B), scan & lampiran surat masuk/keluar (7.A.2/7.B.2). **State**: `⌛` progress bar, `✔` thumbnail/nama, `✖` tipe/ukuran ditolak.

## B. Notifikasi Email (M-NOTIF)

**Bukan halaman** — trigger event (queue). Dikirim saat: permohonan diajukan (konfirmasi ke mahasiswa), **disetujui**, **ditolak** (+alasan), **surat siap** (teks dibedakan `metode_pengambilan`: "silakan download" vs "silakan ambil di kampus"). Template HTML; retry otomatis bila SMTP gagal (ARCHITECTURE §2). Konfigurasi SMTP dari `settings` (1.C.1).

---

# APPENDIX — Rekap ⚠️ PERLU KONFIRMASI

Semua titik yang datanya/perilakunya belum pasti (dikumpulkan dari seluruh tahap):

| # | Tahap | Item | Dampak |
|---|---|---|---|
| 1 | 1.A | Login pertama mahasiswa — paksa ganti password? | Perlu kolom `users.must_change_password` bila ya |
| 2 | 1.B / SLA | ✅ **RESOLVED** — pakai `created_at` (SLA = estimasi, tanpa penalti; dashboard hitung status ≥ pending). Tidak menambah kolom. |
| 3 | 2.A.1 | Hapus user permanen vs soft delete saja | Kebijakan + UI |
| 4 | 2.A.3 | ✅ **RESOLVED (sebagian)** — kolom `nim,nama,email,password,prodi`; password sudah di-hash; duplikat **skip** (email/nim unik). ⚠️ *Sisa*: (a) `fakultas` tak diimport tapi dipakai `{{fakultas}}`; (b) algoritma hash harus kompatibel bcrypt. |
| 5 | 4.A.2 | Unit tujuan permohonan (template n-n unit) — dropdown bila >1 unit | Field baru di form (unit_id sudah ada di ERD) |
| 6 | 4.C.2 | ✅ **RESOLVED** — status tracker (badge/stepper dari kolom `status`) + ActivityLog untuk audit admin. Tidak ada tabel `permohonan_status_log`. |
| 7 | 4.G | ✅ **RESOLVED** — mahasiswa (& admin) boleh ganti password via **Fortify `update-password`** bawaan. Tanpa skema baru. |
| 8 | 5.A.4 | ✅ **RESOLVED** — un-approve **tidak diperlukan** Phase 1. Tanpa mekanisme/kolom. |
| 9 | 5.B.3 | PDF opsional (LibreOffice) → QR di PDF & draft watermark | Verifikasi via nomor bila DOCX-only |
| 10 | 6.A.3 | Cetak ulang: nomor sama atau baru? | UNIQUE memaksa baru; konfirmasi kebijakan |
| 11 | 6.B | Fase portal verifikasi publik — Phase 1 (PRD F8) vs Phase 2 (murni §6) | Keputusan fase |
| 12 | 7.A.2 / 7.B.2 | `kode_klasifikasi` teks bebas — perlu master `klasifikasi_surat`? | ERD §24 item terbuka; dipakai 2 modul (FEATURE_MAP C2) |

---

## Status Cakupan

✅ **Semua route utama di FEATURE_MAP tercakup** (M-AUTH, M-DASH, M-CONFIG, M-USER, M-KAMUS, M-KATEGORI, M-SYARAT, M-TEMPLATE, M-MHS-*, M-PERM-ADM, M-GENERATE, M-ARSIP, M-VERIFIKASI, M-MASUK, M-KELUAR) + cross-cutting (M-MEDIA, M-NOTIF).

Urutan mengikuti dependensi (fondasi → template → permohonan → approval/generate → arsip/verifikasi → surat masuk/keluar). Semua wireframe terikat kolom ERD; 12 titik di luar ERD ditandai ⚠️ (rekap di atas) untuk ditindaklanjuti sebelum implementasi.

---

*Dokumen UX_SPEC selesai untuk Phase 1. Perbarui seiring keputusan atas item ⚠️ dan perubahan FEATURE_MAP/ERD.*

---

*Dokumen bertahap — setiap tahap menambah route sesuai urutan dependensi. Wireframe terikat kolom ERD; elemen di luar ERD ditandai ⚠️ PERLU KONFIRMASI.*
