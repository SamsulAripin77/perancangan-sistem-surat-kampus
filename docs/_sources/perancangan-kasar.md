# Dokumen Perancangan Kasar — Sistem Surat Kampus

> **Tujuan Dokumen**: Ini bukan dokumen desain final. Tujuannya adalah membantu mengidentifikasi pertanyaan kritis, memetakan percabangan proses bisnis yang tersembunyi, dan memberikan asumsi terarah sebelum mulai merancang. Dokumen ini akan terus diperbarui seiring informasi terkumpul.

---

## Konteks Utama & Misi Sistem

> Ini adalah paragraf paling penting. Baca ini sebelum membaca apapun, dan kembalilah ke sini setiap kali scope terasa melebar.

**Masalah nyata yang ingin diselesaikan:**
Saat ini template surat di kampus tersebar di berbagai komputer lokal masing-masing staff. Setiap kali ada mahasiswa yang butuh surat, staff harus buka file Word, edit manual, simpan, cetak. Tidak ada arsip terpusat, tidak ada standarisasi, tidak ada cara tahu surat nomor berapa yang sudah dikeluarkan. Jika staff yang biasa mengelola surat tidak ada, proses berhenti.

**Yang ingin dibangun:**
Sebuah sistem terpusat untuk:
1. **Menyimpan dan mengelola template surat** sebagai master — semua template ada di satu tempat, bisa diakses dan dicetak oleh siapapun yang berwenang
2. **Melayani permohonan surat mandiri mahasiswa** — mahasiswa request lewat sistem, isi data, upload syarat, tunggu proses → mengurangi antrian fisik
3. **Mengarsipkan setiap surat yang pernah dicetak** — riwayat lengkap, bisa dicari, bisa dicetak ulang, tidak perlu cari-cari di komputer lokal
4. **Merekam surat fisik** (scan) yang masuk maupun keluar — surat masuk/keluar dari luar kampus bisa diarsipkan secara digital

Semua fitur lain (approval berjenjang, tanda tangan digital, integrasi SIAKAD) adalah **pelengkap dari misi ini**, bukan misi utamanya. Jangan sampai fitur pelengkap ini menghambat delivery inti.

---

## Ringkasan Jawaban & Saran Balik

Bagian ini merangkum semua jawaban yang sudah masuk dari klien, dan memberikan rekomendasi untuk pertanyaan yang jawabannya masih ambigu atau membutuhkan keputusan teknis.

---

### Jawaban yang Sudah Jelas (Keputusan)

Poin-poin ini tidak perlu didiskusikan lagi — langsung jadi acuan desain:

| Topik | Keputusan |
|-------|-----------|
| **Prioritas Phase 1** | Template management + Permohonan surat mahasiswa. Internal kampus bisa masuk Phase 1 tapi tidak boleh menghambat delivery fitur utama |
| **Scope kampus** | Satu kampus, tapi sistem harus bisa di-clone dan dikonfigurasi untuk kampus lain |
| **Output format** | Keduanya: PDF dan Word (.docx) |
| **Akses mahasiswa** | Dikontrol via properti akun (status aktif/nonaktif), bukan otomatis dari SIAKAD |
| **Mahasiswa cuti/DO** | Selama akun aktif, bisa request surat — keputusan ada di admin, bukan sistem |
| **Data mahasiswa** | Import dari SIAKAD berupa NIM + email + password (snapshot, tidak realtime) |
| **Login** | Dibuat dari nol, bukan SSO |
| **Tanda tangan** | Primer: tanda tangan basah (cetak → TTD pena). Sekunder: image PNG untuk surat yang tidak terlalu formal. TTE belum dipertimbangkan |
| **Nomor surat** | Diisi manual oleh admin saat generate surat |
| **Arsip** | Disimpan selamanya, filter berdasarkan `created_at` |
| **a.n./p.t. pejabat** | Diselesaikan secara offline — sistem tidak perlu tangani ini untuk sekarang |
| **Topologi 2-5 (internal, SK, eksternal, sertifikat)** | Prioritas Phase 2 — yang penting adalah sistem templating dan cetak, flow approval/TTD/kirim email bisa diluar sistem dulu |

---

### Jawaban yang Masih Ambigu — Saran & Rekomendasi

---

#### 1. Multi-Unit vs Satu Unit (Admin Terpusat)

**Pertanyaan asal**: Apakah setiap fakultas/prodi punya admin surat sendiri, atau satu admin terpusat?

**Rekomendasi**: Arsitektur **unit-aware sejak awal, tapi operasional terpusat dulu**.

Implementasi konkret:
- Buat tabel `units` (contoh: BAA, Fakultas Teknik, LPPM, Prodi Informatika)
- Setiap **template surat** di-assign ke satu unit penerbit
- Setiap **admin surat** di-assign ke satu atau beberapa unit (many-to-many)
- Filter data (template, permohonan, arsip) berdasarkan unit akses masing-masing admin
- **Untuk Phase 1**: hanya satu unit aktif (semua template masuk "BAA" atau unit default). Operasional terpusat, satu pool admin.
- **Untuk Phase 2**: tinggal tambah unit baru dan assign admin ke unit tersebut — tanpa refactor database

**Kenapa ini penting sejak awal**: Jika dari awal tidak ada konsep unit di database, menambahkannya nanti akan memaksa migrasi data dan refactor yang mahal. Cost menambah tabel `units` di awal sangat kecil dibanding cost refactor nanti.

---

#### 2. Pembagian Role & Permission di Dunia Nyata

**Pertanyaan asal**: Bingung bagaimana pembagian permission di dunia nyata kampus.

**Rekomendasi — 4 Role Awal yang Realistis**:

| Role | Siapa di Dunia Nyata | Akses Sistem |
|------|----------------------|--------------|
| **Super Admin** | IT / Developer | Konfigurasi sistem, manage semua user, lihat semua data, backup/restore |
| **Admin Surat / Operator** | Staff BAA / Tata Usaha | Manage template, proses permohonan, generate & cetak surat, kelola arsip surat masuk/keluar, lihat buku agenda |
| **Mahasiswa** | Mahasiswa aktif | Submit permohonan, lihat status, download surat (jika diizinkan), lihat riwayat permohonan sendiri |
| **Pejabat** *(opsional, Phase 2)* | Kaprodi, Dekan | Approve/reject permohonan yang di-route ke mereka, lihat permohonan yang relevan dengan unit mereka |

**Catatan penting**: Untuk Phase 1, role "Pejabat" tidak perlu ada. Admin Surat yang menjadi single approver — tapi dengan mekanisme log approval yang menjelaskan atas persetujuan siapa (lihat saran approval di bawah).

---

#### 3. Sistem Approval yang Realistis — Solusi Dilema "Terlalu Ribet vs Tidak Kredibel"

**Pertanyaan asal**: Bingung antara approval strict (pejabat harus punya akun) yang terlalu ribet, vs tidak ada approval yang tidak kredibel. Cari solusi realistis.

**Jawab**: Solusinya adalah **"Proxy Approval dengan Log Pejabat"** — pendekatan tengah yang dipakai di banyak sistem pemerintahan dan kampus menengah di Indonesia. Ada dua versi: simple untuk Phase 1 dan advanced untuk Phase 2.

---

##### Versi Simple — Phase 1 (Proxy Approval)

Pejabat **tidak punya akun**. Admin Surat adalah satu-satunya yang bertindak di sistem. Tapi saat approve, admin **wajib mencatat nama pejabat** yang memberikan persetujuan secara offline (via WA, telpon, tatap muka).

**Alur lengkap**:

```
MAHASISWA                    ADMIN SURAT                 PEJABAT (Offline)
    │                             │                              │
    │  Submit permohonan          │                              │
    │ ─────────────────────────> │                              │
    │                             │  [Cek kelengkapan syarat]   │
    │                             │  [Buka lampiran, verifikasi]│
    │                             │                              │
    │                             │  "Pak Kaprodi, ada          │
    │                             │   permohonan magang dari    │
    │                             │   Budi, boleh disetujui?"   │
    │                             │ ─────────────────────────── >│
    │                             │                              │
    │                             │      "Boleh, silakan"       │
    │                             │ < ─────────────────────────  │
    │                             │                              │
    │                             │  [Klik SETUJUI di sistem]   │
    │                             │  Form approval muncul:      │
    │                             │  ┌──────────────────────┐   │
    │                             │  │ Atas persetujuan:    │   │
    │                             │  │ [Dr. Ahmad - Kaprodi▼│   │
    │                             │  │ Catatan (opsional):  │   │
    │                             │  │ [__________________] │   │
    │                             │  │     [KONFIRMASI]     │   │
    │                             │  └──────────────────────┘   │
    │                             │                              │
    │  ✉ "Permohonan disetujui"  │  [Generate & cetak surat]   │
    │ < ──────────────────────── │                              │
```

**Data yang perlu disiapkan**:

Tabel `pejabat` (master data, diisi saat setup sistem):
```
id | nama               | jabatan              | unit_id | file_ttd      | is_active
---|--------------------|----------------------|---------|---------------|----------
1  | Dr. Ahmad Fauzi    | Kaprodi Informatika  | 3       | ttd/ahmad.png | true
2  | Dr. Siti Rahayu    | Dekan FT             | 2       | ttd/siti.png  | true
3  | Prof. Budi Santoso | Wakil Rektor I       | 1       | null          | true
```

Kolom tambahan di `permohonan_surat` (diisi saat admin klik approve):
```
approved_by_user_id  → admin yang klik tombol approve (FK ke users)
approved_at          → timestamp approve
pejabat_id           → pejabat yang memberikan persetujuan (FK ke pejabat)
catatan_approval     → catatan dari admin (opsional)
```

Di `surat_tercetak` — snapshot data pejabat disimpan sebagai JSON agar tidak terpengaruh jika pejabat berganti jabatan di masa depan:
```json
{
  "nama": "Dr. Ahmad Fauzi, M.Kom.",
  "jabatan": "Ketua Program Studi Informatika",
  "unit": "Fakultas Teknik"
}
```

**Yang muncul di surat yang digenerate**:

Sistem otomatis mengambil data dari pejabat yang dipilih admin untuk mengisi placeholder tanda tangan:
```
                            Bandung, 5 Juli 2025
                            Nomor: 001/BAA/UN-NP/VII/2025

...isi surat...

                            Ketua Program Studi Informatika,


                            [gambar TTD ahmad.png]


                            Dr. Ahmad Fauzi, M.Kom.
                            NIDN: 0412345678
```

**Yang tersimpan di log arsip** (bisa dilihat Super Admin untuk audit):
```
Surat: 001/BAA/UN-NP/VII/2025
Permohonan: Surat Rekomendasi Magang — Budi Setiawan (NIM: 20210001)
Digenerate oleh   : Dewi Maharani (Admin Surat) — 5 Juli 2025, 10:23
Disetujui atas persetujuan: Dr. Ahmad Fauzi (Kaprodi Informatika)
Approval dicatat oleh: Dewi Maharani — 4 Juli 2025, 15:40
Catatan: Sudah dikonfirmasi via WA ke Pak Kaprodi
```

**Keuntungan**:
- Tidak perlu setiap pejabat punya akun → tidak ribet, bisa langsung jalan
- Setiap surat punya jejak "siapa yang bertanggung jawab" → tetap kredibel
- Audit trail lengkap: tanggal approve, nama admin, nama pejabat yang memberikan persetujuan

**Kelemahan yang harus diterima**:
- Admin bisa saja memilih nama pejabat tanpa benar-benar konfirmasi (sistem tidak bisa memverifikasi ini)
- Mitigasi: pastikan ada SOP internal — setiap approval harus ada bukti komunikasi (screenshot WA, dll) yang bisa diminta jika ada audit

---

##### Versi Advanced — Phase 2 (Approval per Pejabat)

Pejabat **punya akun sendiri**. Approval bisa **berjenjang** sesuai konfigurasi di template. Pejabat tidak perlu aktif login setiap hari — cukup klik link di email saat ada yang perlu di-approve.

**Konfigurasi di form edit template**:

```
Template: Surat Rekomendasi Magang
Workflow Approval:
  ( ) Tidak perlu approval khusus
  (x) Approval berjenjang:

  Step 1: Admin Surat          ─ verifikasi kelengkapan syarat
  Step 2: [Kaprodi ▼]          ─ persetujuan akademik
  Step 3: [Dekan   ▼]          ─ pengesahan fakultas

  [+ Tambah Step]

  Notifikasi: [x] Email ke pejabat saat giliran mereka
              [x] Pengingat jika belum direspons dalam [2] hari
```

**Alur berjenjang**:

```
MAHASISWA       ADMIN SURAT      KAPRODI            DEKAN
    │                │                │                 │
    │  Submit        │                │                 │
    │ ─────────────> │                │                 │
    │                │  [Verifikasi]  │                 │
    │                │  APPROVE step1 │                 │
    │                │ ─────────────> │ ✉ "Ada          │
    │                │                │  permohonan    │
    │                │                │  utk Anda"     │
    │                │                │                 │
    │                │                │  [Klik link    │
    │                │                │   di email]    │
    │                │                │  APPROVE       │
    │                │                │ ─────────────> │ ✉ "Giliran Anda"
    │                │                │                 │
    │                │                │                 │ [Klik link]
    │                │                │                 │ APPROVE
    │                │ ✉ "Semua step  │                 │
    │                │  selesai,      │                 │
    │                │  siap cetak"   │                 │
    │                │                │                 │
    │ ✉ "Disetujui" │  [Generate &   │                 │
    │ < ──────────── │   cetak surat] │                 │
```

**Tabel database tambahan untuk Phase 2**:

`approval_workflow_templates` — konfigurasi per template:
```
id | template_id | step_order | role         | pejabat_id | is_required
---|-------------|------------|--------------|------------|------------
1  | 5           | 1          | Admin Surat  | NULL       | true
2  | 5           | 2          | Pejabat      | 1 (Kaprodi)| true
3  | 5           | 3          | Pejabat      | 2 (Dekan)  | true
```

`permohonan_approval_logs` — jejak setiap langkah yang terjadi:
```
id | permohonan_id | step_order | actor_type | actor_id | action  | catatan        | created_at
---|---------------|------------|------------|----------|---------|----------------|------------------
1  | 88            | 1          | User       | 3 (admin)| approve | Syarat lengkap | 2025-07-04 09:00
2  | 88            | 2          | Pejabat    | 1        | approve | -              | 2025-07-04 14:22
3  | 88            | 3          | Pejabat    | 2        | approve | -              | 2025-07-05 08:55
```

Status permohonan yang lebih granular di Phase 2:
```
pending_admin      → menunggu verifikasi admin
pending_kaprodi    → menunggu approval kaprodi
pending_dekan      → menunggu approval dekan
ditolak            → ditolak di salah satu step (tercatat di step berapa)
disetujui_semua    → semua step approved, siap generate surat
```

**Opsi login pejabat — dua pilihan**:

Opsi A — Magic Link (paling mudah, cocok untuk pejabat yang tidak mau repot login):
```
Subject: [Sistem Surat] Permohonan membutuhkan persetujuan Anda

Ada permohonan yang membutuhkan persetujuan Anda.

Pemohon  : Budi Setiawan (20210001)
Jenis    : Surat Rekomendasi Magang
Diajukan : 4 Juli 2025

[  SETUJUI  ]    [  TOLAK  ]

(Link ini valid 48 jam)
```
Klik SETUJUI → langsung approve tanpa perlu login (signed URL dengan expiry). Paling realistis untuk pejabat yang tidak aktif di sistem.

Opsi B — Portal Login Sederhana (lebih terkontrol):
Pejabat punya akun. Login → dashboard khusus yang hanya menampilkan daftar yang perlu di-approve. Tidak ada menu lain.

---

##### Perbandingan Ringkas

| Aspek | Simple (Phase 1) | Advanced (Phase 2) |
|-------|------------------|--------------------|
| Pejabat perlu akun? | Tidak | Ya (atau magic link) |
| Siapa yang approve di sistem | Admin Surat saja | Per pejabat sesuai step |
| Koordinasi offline | Ya (WA/telpon) | Tidak perlu |
| Approval berjenjang | Tidak | Ya, dikonfigurasi per template |
| Effort setup | Rendah | Menengah (onboarding pejabat) |
| Kredibilitas | Cukup (log nama pejabat) | Tinggi (jejak digital per pejabat) |
| Risiko | Admin bisa input nama sembarangan | Lebih akurat tapi butuh pejabat aktif |
| Rekomendasi | Mulai di sini | Upgrade setelah sistem berjalan |

**Catatan transisi**: Phase 1 ke Phase 2 tidak butuh refactor database besar. Tabel `pejabat` dan kolom log approval sudah ada sejak Phase 1 — Phase 2 hanya menambahkan tabel workflow config, tabel approval logs granular, dan portal pejabat. Data lama tetap valid.

---

#### 4. SLA — Kapan dan Bagaimana Ditentukan

**Pertanyaan asal**: SLA bagus, tapi kapan ditentukan? Saat buat template atau kapan?

**Jawab**: **Di-set saat membuat/mengedit template surat** — setiap template punya field "Estimasi Selesai (hari kerja)".

Cara kerja:
- Template "Surat Keterangan Aktif" → SLA 1 hari kerja
- Template "Surat Rekomendasi Magang" → SLA 3 hari kerja
- Ketika permohonan masuk, sistem otomatis hitung: deadline = tanggal submit + SLA hari kerja (skip Sabtu/Minggu dan hari libur nasional)
- Di dashboard admin, permohonan yang mendekati deadline tampil dengan indikator warna (kuning = H-1, merah = overdue)
- Ini juga bisa ditampilkan ke mahasiswa: "Estimasi selesai: 3 hari kerja"

**Catatan**: SLA di sini adalah estimasi, bukan kontrak hukum — tidak perlu ada penalti sistem jika terlewat. Fungsinya hanya sebagai panduan dan alert visual.

---

#### 5. Mekanisme Keaslian untuk PDF/Email Digital

**Pertanyaan asal**: Surat kemungkinan diambil fisik, tapi sangat mungkin juga PDF/email. Kalau digital, mekanisme keasliannya bagaimana?

**Jawab**: 3 layer yang bisa diimplementasi secara bertahap tanpa TTE:

**Layer 1 — Nomor Surat Unik** (wajib, ada di MVP):
- Setiap surat punya nomor unik yang dicatat di arsip sistem
- Pihak penerima surat bisa konfirmasi via telepon/email ke kampus: "Surat nomor 001/BAA/UN-NP/VII/2025 — apakah valid?"
- Sederhana tapi sudah memberi dasar verifikasi

**Layer 2 — QR Code Verifikasi** (Phase 1 atau 2, mudah diimplementasi):
- QR code kecil dicetak di pojok setiap surat
- Scan QR → buka halaman web kampus → tampil: status surat (valid/dibatalkan), nama penerima surat, jenis surat, tanggal terbit
- Tidak perlu login untuk lihat halaman verifikasi
- URL verifikasi menggunakan signed hash yang tidak bisa ditebak: `surat.kampus.ac.id/verify/abc123def456`
- Data yang ditampilkan: hanya nama depan + inisial + jenis surat (pertimbangkan privasi data)

**Layer 3 — Watermark Nama Penerima** (opsional, Phase 2):
- PDF yang di-download mengandung watermark halus bertuliskan nama dan NIM penerima di setiap halaman
- Jika PDF ini dibagikan ke orang lain, terlihat jelas siapa yang dapat surat aslinya

---

#### 6. Nomor Surat — Mode A: `{{nomor_surat}}` Satu Slot

**Keputusan**: Pakai **Mode A** — satu placeholder `{{nomor_surat}}` untuk seluruh string nomor surat. Admin isi format lengkap di satu field. Ini yang paling simpel, paling aman, dan konsisten dengan cara kerja semua sistem referensi (OpenSID, referensi-surat-1, referensi-surat-2).

**UX saat generate**:

```
Nomor Surat *
┌──────────────────────────────────────────┐
│  005/SK/UNsP/VII/2025                   │  ← pre-filled, bisa diedit bebas
└──────────────────────────────────────────┘
Dari: 004/SK/UNsP/VII/2025 → 005 (template ini, 2025)
✓ Nomor ini belum pernah dipakai
```

**Mekanisme suggestion** — bukan dari counter terpisah, tapi dari data yang sudah ada:

1. Ambil `nomor_surat` terakhir untuk template yang sama di tahun yang sama
2. Ekstrak angka di awal string via regex: `"004/SK/UNsP/VII/2025"` → `"004"`
3. Increment + pertahankan padding: `004` → `005`
4. Replace angka di depan string: `"005/SK/UNsP/VII/2025"` → pre-fill di input
5. AJAX duplicate check realtime terhadap string yang sudah pre-fill

```
Edge case — belum ada surat sebelumnya (awal tahun / template baru):
  → Input kosong, muncul hint: "Belum ada surat template ini di 2025"

Edge case — format tidak dimulai angka (regex gagal ekstrak):
  → Input kosong, referensi terakhir ditampilkan sebagai teks di bawah
  → Admin isi manual

Edge case — admin override ke angka lebih besar (misal lompat ke 008):
  → Suggestion berikutnya ambil dari "008/..." → suggest "009"
  → Tidak ada counter table yang perlu diupdate
```

- Tidak perlu tabel counter terpisah — suggestion selalu dari data arsip
- Reset per tahun otomatis karena query filter `WHERE YEAR(created_at) = tahun_ini`
- Nomor beda tahun tidak dianggap duplikat karena tahun ada di dalam string

---

## Riset — Bagaimana Sistem Surat Sebenarnya Bekerja di Universitas Indonesia

---

### A. Tipologi Surat Kampus & Prioritas Phase

Surat kampus ada setidaknya 5 kategori — tapi tidak semua harus dibangun bersamaan:

| # | Kategori | Contoh | Priority |
|---|----------|--------|----------|
| 1 | **Layanan Mahasiswa** | SKMA, Surat Rekomendasi Magang, Surat Pengantar KKN, Dispensasi, Bebas Tanggungan | **Phase 1 — Must Have** |
| 2 | **Administrasi Internal** | Nota Dinas, Surat Edaran, Undangan Rapat | Phase 1 (sistem templating cukup, tanpa approval strict) |
| 3 | **Surat Keputusan (SK)** | SK Rektor, SK Dekan, SK Panitia | Phase 2 — Should Have |
| 4 | **Surat Kerjasama & Eksternal** | MOU, Surat ke Perusahaan, ke Instansi Pemerintah | Phase 2 — Should Have |
| 5 | **Sertifikat & Penghargaan** | Sertifikat Seminar, Sertifikat KKN, Piagam | Phase 2 — Could Have |

> **Catatan**: Untuk Kategori 2-5 di Phase 1, yang penting adalah **template bisa dibuat dan surat bisa dicetak**. Flow approval pejabat online, tanda tangan digital, dan pengiriman email bisa dilakukan di luar sistem dulu. Ini yang membuat Phase 1 bisa di-deliver lebih cepat.

---

### B. Disposisi Surat — Konteks Dunia Nyata

**Apa itu disposisi?**

Ketika kampus menerima surat fisik dari luar (misal: undangan dari Dikti, surat izin penelitian dari perusahaan, surat dari orang tua mahasiswa), surat tersebut diterima oleh bagian administrasi lalu diserahkan ke pimpinan yang relevan.

Pimpinan membaca surat tersebut dan menulis **disposisi** — yaitu instruksi/catatan di lembar terpisah (atau di halaman belakang surat) kepada bawahannya tentang apa yang harus dilakukan terhadap surat itu.

**Contoh disposisi nyata**:
```
Surat Masuk: Undangan Workshop Akreditasi dari BAN-PT, tanggal 10 Juli 2025
Disposisi dari Rektor kepada Dekan FT: "Mohon ditindaklanjuti, kirim perwakilan 2 orang"
Disposisi dari Dekan FT kepada Kaprodi: "Harap koordinasi dengan LPPM dan konfirmasi kehadiran sebelum 15 Juli"
```

**Alur disposisi di sistem digital**:
```
Surat fisik diterima → Admin scan & upload ke sistem (jadi "Surat Masuk")
→ Admin assign disposisi ke Pejabat A (pilih dari daftar jabatan)
→ Pejabat A melihat daftar disposisi yang masuk ke dia
→ Pejabat A bisa tulis catatan balasan atau teruskan disposisi ke pejabat lain
→ Status disposisi: Belum Dibaca → Sudah Dibaca → Sudah Ditindaklanjuti
→ Setiap langkah tercatat di riwayat disposisi surat tersebut
```

**Pertimbangan untuk implementasi**:
- Di Phase 1: disposisi bisa dibuat sederhana — satu layer (Admin → satu pejabat), tanpa chain kompleks
- Di Phase 2: disposisi berjenjang (bisa diteruskan dari pejabat satu ke pejabat lain)
- **Pejabat yang menerima disposisi tidak harus punya akun sistem** jika disposisi dikelola manual — tapi kalau mau digital, mereka perlu role "Pejabat"

> **Keresahan**: Jika disposisi diimplementasi tapi pejabat tidak punya akun, siapa yang catat status "sudah ditindaklanjuti"? Ini kembali ke dilema approval — solusinya sama: proxy via Admin Surat dengan log catatan manual.

**Keputusan Design Disposisi (dari analisis tiga sistem referensi: OpenSID, referensi-surat-1, referensi-surat-2)**:

| Pertanyaan | Keputusan Phase 1 | Catatan Phase 2 |
|------------|-------------------|-----------------|
| Penerima disposisi harus punya akun? | Tidak — field `tujuan` teks bebas ("WR I", "LPPM", "Dekan FT") | Tambah `pejabat_id FK` opsional dari master pejabat |
| Siapa yang update status disposisi? | Admin Surat selalu — pejabat tidak perlu login | Jika `pejabat_id` diisi + punya email: notifikasi email ke pejabat |
| Chain disposisi (diteruskan ke pejabat lain)? | Tidak — flat seperti semua referensi | Bisa dipertimbangkan jika ada kebutuhan nyata |
| Bisa lebih dari satu disposisi per surat? | Ya — one-to-many (`surat_masuk_id` FK di tabel disposisi) | Sama |
| Cetak lembar disposisi? | Ya — generate PDF lembar disposisi, diserahkan fisik ke pejabat | Sama |

---

### C. Buku Agenda Surat — Arsip Kronologis Wajib

**Apa itu buku agenda?**

Di kantor kampus, ada dua buku fisik yang wajib ada di meja TU:
1. **Buku Agenda Surat Masuk** — log semua surat yang masuk ke kampus, diurutkan berdasarkan tanggal terima
2. **Buku Agenda Surat Keluar** — log semua surat yang keluar dari kampus, diurutkan berdasarkan tanggal kirim

**Format buku agenda surat masuk (tiap baris)**:
| No. Agenda | Tgl Terima | No. Surat (asli) | Tgl Surat | Pengirim | Perihal | Lampiran | Disposisi ke | Ket |
|------------|-----------|-----------------|-----------|----------|---------|----------|--------------|-----|

**Format buku agenda surat keluar**:
| No. Agenda | No. Surat | Tgl Surat | Tujuan | Perihal | Pengirim (Unit) | Lampiran | Ket |
|------------|-----------|-----------|--------|---------|-----------------|----------|-----|

**Perbedaan penting**: Nomor agenda ≠ nomor surat.
- **Nomor agenda**: nomor urut penerimaan/pengiriman di sistem (1, 2, 3, 4...) — reset tiap tahun
- **Nomor surat**: nomor yang tertera di kop surat, ditulis oleh pengirim (untuk surat masuk) atau oleh kampus (untuk surat keluar)

**Fungsi buku agenda di sistem digital**:
- View tabel dengan filter: tanggal, pengirim/tujuan, perihal, status disposisi
- Export ke Excel/PDF untuk laporan bulanan
- Pencarian cepat: "surat dari Dikti bulan Juli 2025" → langsung ketemu
- Untuk surat keluar yang dibuat via sistem: otomatis masuk ke buku agenda keluar setelah generate final

> **Keresahan implementasi**: Untuk surat yang dibuat via sistem (template + generate), masuk ke buku agenda keluar otomatis adalah logis. Tapi untuk surat fisik yang di-scan dan diupload, entry buku agenda harus diisi manual oleh admin — ini adalah proses yang berbeda. Pastikan UX form surat masuk (upload scan) sekaligus mengisi buku agenda, bukan dua proses terpisah.

**Keputusan Design Buku Agenda**:

- Buku agenda surat masuk = **view dari tabel `surat_masuk`** — bukan tabel terpisah, cukup tampilkan dengan filter dan export
- `nomor_agenda` di tabel `surat_masuk` adalah `SMALLINT UNSIGNED` auto-increment per tahun — **tidak diinput admin**, sistem yang generate otomatis saat entri baru dibuat
- Buku agenda surat keluar = tabel `surat_keluar` **terpisah**, 100% manual entry — **tidak ada FK ke `surat_tercetak`** (pola OpenSID). Surat yang di-generate via template tidak otomatis masuk buku agenda keluar; jika perlu dicatat, admin input manual di Kamar 3
- Format tampilan buku agenda: tabel dengan kolom No. Agenda, Tgl Terima, No. Surat (asli), Pengirim, Perihal, Disposisi ke, Status Disposisi
- Filter: tahun, bulan, pengirim, status disposisi; Export: Excel dan PDF

---

### D. Percabangan Approval — Rekomendasi Implementasi Realistis

Di kampus nyata, alur approval tidak sama untuk semua surat:

```
[SKMA - Surat Keterangan Mahasiswa Aktif]
Mahasiswa request → Admin Surat cek data → Langsung generate & cetak
(cepat, tidak butuh approval pejabat)

[Surat Rekomendasi Magang]
Mahasiswa request → Admin Surat verifikasi data & syarat → (konfirmasi offline ke Kaprodi) → generate & cetak
(di sistem: satu langkah approval admin, log: "atas persetujuan Kaprodi [nama]")

[Nota Dinas Internal]
Admin Surat buat draft → (konfirmasi offline ke Pimpinan unit) → generate & cetak
(tidak ada flow permohonan dari mahasiswa — langsung dari admin)
```

**Rekomendasi implementasi Phase 1**: Approval di-set per template (checkbox "Perlu Approval Pejabat" di form template) dengan field log pejabat wajib diisi saat approve.

---

### E. Masalah Penomoran Surat

Format nomor surat UNsP berdasarkan sampel nyata:
```
005  / SK   / UNsP / I   / 2021      → urut/kode/univ/bulan_romawi/tahun
019  / PMB-GEL.IV / U.NsP / VIII / 2020  → dengan sub-kode
68   / RCSU / UNsP / 2024             → tanpa bulan romawi
```

Komponen bervariasi per jenis surat: bulan romawi **opsional** tergantung template. Kode berbeda per jenis surat (SK, RCSU, PMB, dll).

**Keputusan: Mode A** — admin taruh `{{nomor_surat}}` di template Word, sistem sediakan satu text field dengan pre-fill suggestion. Admin isi atau edit string lengkap.

```
Di Word:  Nomor: {{nomor_surat}}
Di form:  [005/SK/UNsP/VII/2025  ]  ← pre-filled, bisa diedit
Di DB:    nomor_surat = "005/SK/UNsP/VII/2025"  ← disimpan as-is
```

Suggestion logic (tanpa counter table terpisah):
- Ambil `nomor_surat` terakhir template ini + tahun ini dari `surat_tercetak`
- Regex ekstrak angka di depan → increment → replace → pre-fill
- AJAX check duplicate realtime
- Reset per tahun otomatis (filter `whereYear`)
- Cross-year tidak duplikat karena tahun ada di dalam string itu sendiri

✅ Tidak perlu tabel counter terpisah — derived dari data arsip yang sudah ada
✅ AJAX duplicate check pada full string
✅ UNIQUE constraint `(nomor_surat, unit_id)` di database sebagai hard guard

> **Catatan**: Gap di nomor (1, 2, 5, 6 karena admin override) adalah wajar dan diterima secara administratif. Tidak perlu dicegah — cukup dicatat.

---

### F. Tanda Tangan — Tiga Skenario

**Skenario 1: Tanda Tangan Basah** (default Phase 1)
- Surat digenerate → dicetak → diserahkan ke pejabat → TTD pena → distribusi fisik atau di-scan kembali
- Sistem tidak perlu fitur khusus untuk ini — hanya generate dokumen yang siap cetak

**Skenario 2: Tanda Tangan Image PNG** (Phase 1 untuk surat semi-formal)
- Gambar PNG tanda tangan pejabat disimpan di sistem, dimasukkan ke placeholder `{{ttd_1}}` saat generate
- **Risiko**: File PNG bisa disalahgunakan jika server diakses tidak sah
- **Mitigasi wajib**: File tanda tangan tidak boleh di folder public — akses hanya via controller dengan strict policy; setiap penggunaan tercatat di activity log
- Lihat seksi H untuk konvensi penamaan placeholder TTD lengkap

**Skenario 3: TTE Tersertifikasi** (belum dipertimbangkan — Phase 3 jika dibutuhkan)

---

### G. Kamus Placeholder & Konsep Smart Default — Inti Sistem Templating

Ini adalah konsep inti dari seluruh sistem templating. Semua keputusan tentang form generate, input type, dan penyimpanan data bermuara di sini.

**Ide dasar**: Nama placeholder bukan sekadar label — ia membawa makna semantik. Sistem menggunakan nama untuk:
1. Menentukan **dari mana nilai default diambil** (profil user, waktu, counter, konfigurasi)
2. Menentukan **jenis input form** yang ditampilkan (text, date picker, file upload, textarea)
3. Menentukan **apakah nilai bisa di-override** oleh admin sebelum generate

Semua nilai — baik auto-fill maupun yang diisi manual — bisa selalu di-override admin. Tidak ada yang benar-benar terkunci kecuali data identitas mahasiswa.

---

#### Kamus Placeholder — Tersimpan di Database, Bisa Diperluas

Sistem menyimpan kamus placeholder dalam tabel `placeholder_definitions`. **Bukan hardcode di PHP.** Super Admin bisa tambah placeholder baru ke kamus tanpa deploy ulang kode.

**Kelompok 1 — Auto-fill dari profil mahasiswa (tidak bisa di-override)**:

| Placeholder | Sumber | Keterangan |
|-------------|--------|------------|
| `{{nama_mahasiswa}}` | Profil user | Nama lengkap |
| `{{nim}}` | Profil user | Nomor Induk Mahasiswa |
| `{{prodi}}` | Profil user | Program studi |
| `{{fakultas}}` | Profil user | Nama fakultas |

**Kelompok 2 — Auto-fill dari konfigurasi sistem (tidak bisa di-override)**:

| Placeholder | Sumber | Keterangan |
|-------------|--------|------------|
| `{{nama_universitas}}` | Config sistem | Nama universitas |
| `{{kode_universitas}}` | Config sistem | "UNsP" |
| `{{logo_kampus}}` | Config sistem | Gambar logo (image placeholder) |
| `{{tahun_akademik}}` | Config sistem | "2025/2026" |

**Kelompok 3 — Smart Default berbasis waktu (auto-fill, bisa di-override)**:

| Placeholder | Default | Input Type | Override? |
|-------------|---------|------------|-----------|
| `{{tanggal_surat}}` | Hari ini | date picker | ✅ Ya |
| `{{bulan_surat}}` | Bulan romawi sekarang ("VII") | text | ✅ Ya |
| `{{tahun_surat}}` | Tahun sekarang ("2025") | text | ✅ Ya |

**Kelompok 4 — Smart Default berbasis suggestion (auto-suggest, bisa di-override)**:

| Placeholder | Default | Input Type | Keterangan |
|-------------|---------|------------|------------|
| `{{nomor_surat}}` | Suggestion dari arsip terakhir | Text field | Diisi sebagai string lengkap — lihat Seksi E |

Suggestion engine ambil `nomor_surat` terakhir template ini + tahun ini dari arsip, ekstrak angka di depan, increment, lalu pre-fill. Tidak ada tabel counter terpisah — selalu derived dari data arsip yang sudah ada.

**Kelompok 5 — Slot TTD (dropdown pejabat)**:
Dideteksi via regex `/^(ttd|nama_ttd|jabatan_ttd|nip_ttd|unit_ttd)_(\d+)$/`. Satu dropdown pilih pejabat mengisi semua field dalam satu slot sekaligus. Detail di Seksi H.

---

#### Inferensi Tipe Input dari Nama Placeholder (untuk Placeholder Bebas)

Untuk placeholder yang **tidak ada di kamus** (placeholder khusus per template), sistem menginfer tipe input dari pola nama secara otomatis:

| Pola Nama | Tipe Input yang Dirender | Contoh Placeholder |
|-----------|--------------------------|-------------------|
| Diawali `tanggal_` atau diakhiri `_tanggal` | Date picker | `{{tanggal_mulai}}`, `{{tanggal_lahir}}` |
| Diawali `upload_` atau diakhiri `_file` | File upload input | `{{upload_foto}}`, `{{ijazah_file}}` |
| Diakhiri `_keterangan`, `_catatan`, `_isi`, `_deskripsi` | Textarea | `{{keperluan_keterangan}}`, `{{catatan_isi}}` |
| Mengandung `_jumlah`, `_nilai`, `_angka`, `_sks` | Number input | `{{ipk_nilai}}`, `{{total_sks}}` |
| Semua lainnya | Text input | `{{nama_perusahaan}}`, `{{jabatan_pembimbing}}` |

**Admin bisa override tipe yang diinfer** di halaman pengaturan template — jika sistem salah tebak, admin bisa ganti ke tipe yang benar tanpa ubah nama placeholder di file Word.

---

#### Konvensi Nomor Surat — Mode A (Satu Slot)

Nomor surat menggunakan **satu placeholder `{{nomor_surat}}`** yang diisi sebagai string lengkap:

```
Di template Word:
  Nomor: {{nomor_surat}}

Di form generate:
  [005/SK/UNsP/VII/2025  ]  ← pre-filled dari suggestion, bisa diedit bebas

Di DB surat_tercetak:
  nomor_surat = "005/SK/UNsP/VII/2025"  ← VARCHAR, UNIQUE per unit
  data_placeholder JSON = { "nomor_surat": "005/SK/UNsP/VII/2025", ... }
```

`{{nomor_surat}}` ada di Kamus Placeholder sebagai special placeholder — saat ditemukan di template, sistem render sebagai text field dengan suggestion engine, bukan plain text input biasa.

Suggestion engine tidak butuh tabel counter terpisah — ambil data dari arsip yang sudah ada (lihat Seksi E untuk detail logika).

---

#### Penyimpanan: `data_placeholder` JSON sebagai Snapshot Historis

Semua nilai yang digunakan saat generate — dari manapun asalnya — disimpan dalam satu field JSON `data_placeholder` di tabel `surat_tercetak`:

```json
{
  "nim": "20210001",
  "nama_mahasiswa": "Budi Setiawan",
  "prodi": "Teknik Informatika",
  "fakultas": "Fakultas Teknik",
  "tanggal_surat": "11 Juli 2025",
  "bulan_surat": "VII",
  "tahun_surat": "2025",
  "nomor_surat": "005/SK/UNsP/VII/2025",
  "nama_perusahaan_magang": "PT. Telkom Indonesia",
  "tanggal_mulai_magang": "1 Agustus 2025",
  "upload_foto": "media/2025/07/foto_budi.jpg",
  "ttd_slots": {
    "1": {
      "pejabat_id": 2,
      "nama": "Dr. Ahmad Fauzi, M.Kom.",
      "jabatan": "Kaprodi Informatika",
      "file_ttd": "signatures/ahmad.png"
    },
    "2": {
      "pejabat_id": 5,
      "nama": "Dr. Siti Rahayu, M.T.",
      "jabatan": "Dekan Fakultas Teknik",
      "file_ttd": "signatures/siti.png"
    }
  }
}
```

**Kenapa JSON, bukan kolom terpisah**:
- Template yang berbeda punya placeholder berbeda — tidak bisa satu skema kolom untuk semua
- Snapshot historis sempurna: nilai saat generate tersimpan selamanya meski data aslinya berubah (pejabat ganti jabatan, mahasiswa update nama)
- Mudah di-render ulang: "data apa yang dipakai saat surat ini digenerate" bisa ditampilkan kapan saja
- Fleksibel ditambah tanpa migrasi DB setiap ada template baru dengan placeholder baru

---

#### Halaman Panduan Placeholder (Referensi Admin Pembuat Template)

Halaman ini tersedia di admin panel dan bisa dibuka saat membuat template Word. Admin tidak perlu hafal nama placeholder — buka halaman ini, lihat, copy-paste ke Word.

```
PANDUAN PLACEHOLDER — SISTEM SURAT
Salin nama di bawah lalu tempel ke template Word dengan format {{nama}}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. DATA MAHASISWA (otomatis, tidak bisa diubah)
   {{nama_mahasiswa}}    Nama lengkap mahasiswa
   {{nim}}               Nomor Induk Mahasiswa
   {{prodi}}             Program Studi
   {{fakultas}}          Nama Fakultas
   {{tahun_akademik}}    Tahun akademik aktif

2. WAKTU (otomatis, bisa diubah saat generate)
   {{tanggal_surat}}     Tanggal surat [default: hari ini]
   {{bulan_surat}}       Bulan romawi [default: bulan ini, misal "VII"]
   {{tahun_surat}}       Tahun [default: tahun ini, misal "2025"]

3. DATA INSTITUSI (otomatis, tidak bisa diubah)
   {{nama_universitas}}  Nama universitas
   {{kode_universitas}}  Kode universitas (misal "UNsP")
   {{logo_kampus}}       Logo universitas (gambar)

4. NOMOR SURAT (suggestion dari arsip, bisa diubah)
   {{nomor_surat}}       Nomor surat lengkap sebagai satu string
                         Sistem pre-fill dari nomor terakhir template ini tahun ini
                         Admin isi/edit string penuh: "005/SK/UNsP/VII/2025"
                         Duplicate check realtime saat admin ketik

5. TANDA TANGAN DINAMIS (pilih pejabat saat generate)
   {{ttd_1}}             Gambar tanda tangan penandatangan 1
   {{nama_ttd_1}}        Nama pejabat 1
   {{jabatan_ttd_1}}     Jabatan pejabat 1
   {{nip_ttd_1}}         NIP/NIDN pejabat 1
   → Ganti angka 1 → 2, 3 untuk penandatangan berikutnya
   → Label di atas TTD ("Menyetujui,") tulis langsung di Word, bukan placeholder

6. PLACEHOLDER BEBAS (diisi saat permohonan atau generate)
   Beri nama sesuai kebutuhan. Tipe input dideteksi otomatis:
   • Awali upload_ atau akhiri _file  → form upload file
   • Awali tanggal_ atau akhiri _tanggal → date picker
   • Akhiri _keterangan/_catatan/_isi → textarea
   Contoh: {{nama_perusahaan}}, {{tanggal_mulai}}, {{upload_surat_pernyataan}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Sistem harus**:
1. Scan semua `{{...}}` dari seluruh .docx (paragraf, tabel, header, footer, textbox)
2. Cocokkan dengan Kamus Placeholder → tentukan kelompok dan perilaku auto-fill
3. Jalankan regex deteksi slot TTD untuk placeholder yang tidak cocok kamus
4. Untuk sisa placeholder: inferensikan tipe input dari pola nama
5. Tampilkan semua di form generate dengan nilai pre-fill sesuai kelompoknya
6. Admin bisa override semua nilai sebelum klik Generate Final

---

### H. Deteksi Slot TTD Otomatis — Cara Kerja Lengkap

Ini adalah bagian paling teknis dari sistem templating. Ada dua pendekatan untuk TTD di template:

- **Pendekatan 1 (Statis)**: Nama dan jabatan penandatangan sudah ditulis langsung di Word sebagai teks biasa, tidak perlu diisi di form. Cocok jika penandatangan selalu orang yang sama.
- **Pendekatan 2 (Slot Dinamis)**: Admin menulis placeholder dengan konvensi `_ttd_N` di template. Saat generate, sistem otomatis menampilkan dropdown pilih pejabat per slot. Cocok jika penandatangan bisa berbeda-beda.

Penjelasan berikut adalah untuk **Pendekatan 2 (Slot Dinamis)**.

#### Konvensi Penamaan Slot TTD

Semua placeholder yang mengikuti pola `{{[field]_ttd_[nomor]}}` dianggap sebagai anggota slot TTD:

```
Regex deteksi: /^(ttd|nama_ttd|jabatan_ttd|nip_ttd|unit_ttd)_(\d+)$/

Contoh template Word dengan 2 penandatangan:

...isi surat...

        Bandung, {{tanggal_surat}}

Menyetujui,                    ← teks statis di Word (bukan placeholder)
Kaprodi,                       ← teks statis di Word


{{ttd_1}}                      ← gambar TTD slot 1


{{nama_ttd_1}}
{{jabatan_ttd_1}}
NIDN: {{nip_ttd_1}}


        Mengetahui,            ← teks statis di Word
        Dekan Fakultas Teknik, ← teks statis di Word


        {{ttd_2}}              ← gambar TTD slot 2


        {{nama_ttd_2}}
        {{jabatan_ttd_2}}
        NIP: {{nip_ttd_2}}
```

Label "Menyetujui," dan "Mengetahui," ditulis langsung di Word — tidak perlu placeholder. Hanya bagian yang dinamis (nama, jabatan, gambar TTD) yang menggunakan konvensi slot.

#### Alur Deteksi Saat Template Diupload

```
Admin upload .docx
       │
       ▼
Parser scan semua {{...}} dari seluruh dokumen
       │
       ▼
Untuk setiap placeholder, jalankan regex:
/^(ttd|nama_ttd|jabatan_ttd|nip_ttd|unit_ttd)_(\d+)$/
       │
       ├── COCOK → tandai sebagai TTD slot member
       │          ekstrak nomor N
       │          kelompokkan: slot_group[N].push(placeholder)
       │
       └── TIDAK COCOK → cek apakah global (auto-fill)
                         atau khusus (form input manual)
       │
       ▼
Hasil grouping disimpan ke DB:
  TTD Slots:
    Slot 1: [ttd_1, nama_ttd_1, jabatan_ttd_1, nip_ttd_1]
    Slot 2: [ttd_2, nama_ttd_2, jabatan_ttd_2, nip_ttd_2]
  Global: [tanggal_surat, nomor_surat, nim, nama_mahasiswa, ...]
  Khusus: [nama_perusahaan, tanggal_mulai, ...]
```

#### Tampilan Form Generate

Satu dropdown pejabat → otomatis mengisi semua placeholder dalam slot itu sekaligus:

```
┌─────────────────────────────────────────────────────┐
│  Generate Surat: Rekomendasi Magang                 │
├─────────────────────────────────────────────────────┤
│  Nomor Surat *  [____________________]              │
│  Nomor terakhir: 023/BAA/UN-NP/VI/2025             │
├─────────────────────────────────────────────────────┤
│  ── Data Surat ────────────────────────────────────  │
│  Tanggal Surat *      [05/07/2025     ]             │
│  Nama Perusahaan *    [______________]              │
│  Tanggal Mulai *      [______________]              │
├─────────────────────────────────────────────────────┤
│  ── Penandatangan ─────────────────────────────────  │
│                                                     │
│  Slot 1 — Menyetujui *                             │
│  [Pilih Pejabat...                            ▼]   │
│  Preview: Dr. Ahmad Fauzi, M.Kom.                  │
│           Kaprodi Informatika · ✓ Ada file TTD     │
│                                                     │
│  Slot 2 — Mengetahui *                             │
│  [Pilih Pejabat...                            ▼]   │
│  ⚠ Pejabat ini belum punya file TTD.              │
│    Surat digenerate tanpa gambar TTD.              │
├─────────────────────────────────────────────────────┤
│                    [PREVIEW]  [GENERATE FINAL]      │
└─────────────────────────────────────────────────────┘
```

#### Proses Substitusi ke Dokumen

```php
foreach ($ttd_slots as $slot_number => $pejabat_id) {
    $pejabat = Pejabat::find($pejabat_id);

    $template->setValue("nama_ttd_{$slot_number}",    $pejabat->nama);
    $template->setValue("jabatan_ttd_{$slot_number}", $pejabat->jabatan);
    $template->setValue("nip_ttd_{$slot_number}",     $pejabat->nip_nidn);
    $template->setValue("unit_ttd_{$slot_number}",    $pejabat->unit->nama);

    if ($pejabat->file_ttd) {
        $template->setImageValue("ttd_{$slot_number}", [
            'path'   => storage_path($pejabat->file_ttd),
            'width'  => 60,
            'height' => 25,
        ]);
    } else {
        $template->setValue("ttd_{$slot_number}", ''); // kosong, tidak crash
    }
}
```

#### Edge Cases yang Harus Ditangani

| Kasus | Perilaku Sistem |
|-------|-----------------|
| `{{ttd_1}}` ada tapi `{{nama_ttd_1}}` tidak ada | Tetap valid — hanya isi field yang ada |
| Pejabat dipilih tapi tidak punya file TTD | Warning di form (bukan error), `{{ttd_1}}` diganti string kosong |
| Admin mau isi penandatangan secara manual (tamu/dosen luar) | Toggle per slot: "Pilih dari daftar" vs "Isi manual" (text fields) |
| Template punya `{{ttd_1}}` tanpa `{{ttd_2}}` | Sistem hanya render 1 slot — tidak error |
| `{{ttd_1}}` ada di template tapi admin tidak pilih pejabat | Validasi: slot TTD yang terdeteksi wajib diisi sebelum generate final |

#### Konten Halaman Panduan Placeholder

Halaman ini harus tersedia di admin dan bisa dibuka saat membuat template Word:

```
PANDUAN PLACEHOLDER SISTEM SURAT

1. PLACEHOLDER GLOBAL (auto-diisi sistem)
   {{nama_mahasiswa}}   Nama lengkap mahasiswa pemohon
   {{nim}}              Nomor Induk Mahasiswa
   {{prodi}}            Program Studi
   {{fakultas}}         Nama Fakultas
   {{tanggal_surat}}    Tanggal surat digenerate
   {{nomor_surat}}      Nomor surat (diisi manual oleh admin)
   {{tahun_akademik}}   Tahun akademik aktif
   {{logo_kampus}}      Logo universitas (gambar)

2. PLACEHOLDER SLOT TTD (penandatangan dinamis)
   Gunakan pola: {{[field]_ttd_[nomor]}}

   Untuk penandatangan pertama:
   {{ttd_1}}            Gambar tanda tangan
   {{nama_ttd_1}}       Nama pejabat
   {{jabatan_ttd_1}}    Jabatan pejabat
   {{nip_ttd_1}}        NIP / NIDN

   Untuk penandatangan kedua: ganti angka 1 → 2, dst.

   CATATAN:
   • Label di atas TTD (misal "Menyetujui,") tulis langsung
     di Word sebagai teks biasa — bukan placeholder.
   • Tidak semua field harus ada. Boleh hanya pakai
     {{ttd_1}} dan {{nama_ttd_1}} tanpa yang lain.

3. PLACEHOLDER KHUSUS TEMPLATE (diisi saat generate/permohonan)
   Bebas diberi nama, contoh:
   {{nama_perusahaan}}  {{tanggal_mulai}}  {{keperluan}}
   Aturan: huruf kecil, gunakan underscore, tanpa spasi.
```

---

### I. Status Mahasiswa & Konsistensi Data

Dengan arsitektur import (bukan realtime SIAKAD):
- Data mahasiswa di sistem adalah **snapshot** dari data SIAKAD saat import dilakukan
- Import ulang diperlukan setiap semester baru atau jika ada perubahan data massal
- Untuk perubahan individual (status DO, cuti, wisuda): **admin update manual** di profil user

**Risiko yang harus diterima** dengan pendekatan ini:
- Jika admin lupa update, mahasiswa yang sudah DO bisa tetap request surat dan disetujui
- IPK/semester yang tercatat di sistem bisa tidak up-to-date jika belum re-import

**Mitigasi**:
- Di form permohonan, tampilkan data yang akan masuk ke surat + tombol "Data salah? Hubungi admin"
- Admin bisa lihat kapan terakhir data mahasiswa di-update (tampilkan di profil user)
- Buat panduan: re-import data dilakukan setiap awal semester (masukkan ke SOP kampus)

---

### J. Surat Massal (Bulk Letter)

Kasus yang pasti akan muncul di kampus:
- Undangan wisuda untuk 300+ mahasiswa
- Sertifikat seminar untuk 100 peserta
- Surat pengantar KKN untuk 50 mahasiswa satu kelompok

Pertanyaan yang belum terjawab:
- ❓ Apakah satu permohonan bisa untuk banyak penerima (batch), atau setiap individu buat permohonan sendiri?
- ❓ Apakah nomor surat per individu berbeda atau sama untuk semua?
- ❓ Output: satu PDF gabungan atau PDF individual per orang?

> **Rekomendasi**: Untuk Phase 1, bulk letter dilakukan dari sisi **admin** (bukan dari permohonan mahasiswa) — admin pilih template, upload daftar data penerima dari Excel, sistem generate PDF batch. Tidak perlu flow permohonan untuk kasus ini.

---

### K. Surat Sudah Tercetak — Revisi & Anti-Fraud

Skenario yang pasti terjadi:
- Surat sudah dicetak tapi ada typo nama atau NIM salah
- Cetak ulang → nomor baru (nomor lama ditandai "digantikan" di arsip) → ini yang benar
- Entry arsip lama tidak dihapus — ditandai `status: digantikan`, ada referensi ke entry baru
- QR code verifikasi nomor lama menampilkan: "Surat ini telah digantikan oleh nomor [XXX]"

---

### L. Verifikasi Keaslian oleh Pihak Luar

Lihat [Saran Mekanisme Keaslian](#5-mekanisme-keaslian-untuk-pdfemail-digital) di bagian Saran Balik.

---

## Asumsi Sementara (Diperbarui)

Label: ✅ Terkonfirmasi | ⚠️ Perlu validasi | ❓ Spekulatif

### Template & Dokumen
- ✅ Template berformat `.docx` — admin upload dari Word yang sudah dimiliki
- ✅ Placeholder format `{{nama_variabel}}` — double curly braces, snake_case
- ✅ Tiga tipe placeholder: teks, gambar/image, tabel-loop (`{{#items}}...{{/items}}`)
- ✅ Output surat: keduanya PDF dan .docx
- ✅ Deteksi placeholder otomatis saat upload — dicocokkan dengan Kamus Placeholder di DB
- ✅ **Kamus Placeholder tersimpan di database** — bukan hardcode; Super Admin bisa tambah tanpa deploy ulang
- ✅ **Smart Default**: placeholder yang dikenal diisi otomatis di form generate sesuai kelompoknya (profil user, waktu, counter, konfigurasi sistem)
- ✅ **Inferensi tipe input** dari nama placeholder: `tanggal_*` → date picker, `upload_*` → file upload, `*_keterangan` → textarea
- ✅ **`data_placeholder` JSON** di `surat_tercetak` — snapshot semua nilai saat generate; tidak perlu kolom baru per template baru
- ✅ Ada halaman **Panduan Placeholder** yang bisa dibuka admin saat membuat template Word
- ✅ Admin bisa override semua nilai smart default sebelum generate final

### Data Mahasiswa & Akses
- ✅ Import data dari SIAKAD: NIM + email + password (snapshot, tidak realtime)
- ✅ Login dari nol, bukan SSO, tidak ada whitelist domain (login dengan email yang diimport)
- ✅ Akses mahasiswa dikontrol manual oleh admin via properti akun (`is_active`)
- ✅ Tidak ada auto-suspend berdasarkan status akademik — admin yang mengelola

### Arsitektur Sistem
- ✅ Satu kampus, single-tenant, tapi bisa di-clone dan dikonfigurasi untuk kampus lain
- ✅ Konsep `units` ada sejak awal di database meski Phase 1 hanya satu unit aktif
- ✅ Soft delete semua tabel utama + Spatie ActivityLog
- ✅ Arsip surat disimpan selamanya

### Workflow & Approval
- ✅ Flow permohonan: **Draft → Pending → Diverifikasi → Disetujui → Siap Cetak / Ditolak**
- ✅ Approval: Admin Surat sebagai single approver, wajib log nama pejabat yang memberikan persetujuan
- ✅ Persyaratan surat: required/optional per template
- ✅ SLA di-set di level template, ditampilkan sebagai estimasi ke mahasiswa dan alert ke admin
- ⚠️ Approval berjenjang (pejabat punya akun sendiri) — Phase 2, belum konfirmasi kapan

### Permohonan Surat
- ✅ Template punya flag `is_permohonan_mandiri` — hanya template dengan flag ini yang muncul di list mahasiswa; template lain hanya bisa di-generate langsung oleh admin
- ✅ Form permohonan terdiri dari **4 lapisan**: (1) auto-fill profil, (2) isian surat → `isian_form` JSON, (3) data tambahan → EAV, (4) file persyaratan
- ✅ **Dimension `filled_by`** pada placeholder: `sistem` / `mahasiswa` / `admin` — menentukan siapa yang isi di tahap mana; default placeholder bebas = `mahasiswa`
- ✅ **Data Tambahan** (tidak masuk surat, mis. no. telepon, alamat): EAV — `template_data_tambahan_fields` (schema) + `permohonan_data_tambahan_values` (nilai, FK RESTRICT + soft delete)
- ✅ Persyaratan file: `ref_syarat_surat` (master reusable) + `syarat_surat` (pivot per template); bisa punya `template_file` nullable → mahasiswa download, isi manual, upload balik
- ✅ **FindOrCreate** untuk persyaratan dan data tambahan — inline modal di halaman edit template, tidak perlu pindah menu
- ✅ **Media library mahasiswa** (`dokumen_mahasiswa`) — Phase 1; file yang pernah diupload tersimpan, bisa dipilih ulang di permohonan berikutnya; tidak ada expiry
- ✅ **Edit & Batal oleh mahasiswa**: permohonan berstatus `pending` bisa diedit atau dibatalkan oleh mahasiswa; setelah admin membuka → status berubah ke `diverifikasi` → tidak bisa diedit/dibatalkan lagi
- ✅ **Auto-transisi status**: status otomatis berubah dari `pending` → `diverifikasi` saat admin pertama kali membuka halaman detail permohonan — bukan manual, tidak perlu tombol terpisah
- ✅ **Keterangan wajib dari admin**: baik saat menyetujui maupun menolak permohonan, admin wajib mengisi keterangan — keterangan tolak ditampilkan ke mahasiswa, keterangan setujui masuk log internal
- ✅ **Resubmit jika ditolak**: tombol "Ajukan Ulang" buat `permohonan` baru dengan `parent_permohonan_id` FK ke yang ditolak; data lama pre-fill dari library
- ✅ Riwayat permohonan: flat list Phase 1 (semua iterasi tampil sebagai baris terpisah); cetak bukti per item Phase 2
- ✅ **Delivery ke mahasiswa** — implicit: cek `surat_tercetak` linked → Download PDF; atau `file_surat_scan` terisi → Download Scan; atau hanya status selesai → "Ambil di TU"
- ✅ TTD image (PNG embed di PDF) ≠ TTE tersertifikasi BSrE — cukup untuk surat semi-formal Phase 1
- ✅ Soft delete field data tambahan: data lama tetap tersimpan, ditampilkan di history; hard delete di-RESTRICT oleh FK jika sudah ada nilai

### Tanda Tangan
- ✅ Primer: tanda tangan basah (cetak fisik)
- ✅ Sekunder: image PNG untuk surat semi-formal
- ✅ File TTD image disimpan di storage private, akses via controller dengan policy check
- ⚠️ Kapan surat pakai TTD basah vs TTD image? Butuh aturan dari klien per jenis surat

### Nomor Surat & Arsip
- ✅ Nomor surat pakai placeholder `{{nomor_surat}}` — satu field text lengkap (Mode A)
- ✅ Suggestion: ambil nomor terakhir template+tahun dari arsip → ekstrak urut depan → increment → pre-fill. Tidak ada tabel counter terpisah.
- ✅ AJAX duplicate check realtime + UNIQUE constraint DB sebagai hard guard
- ✅ Reset per tahun otomatis (suggestion query filter whereYear)
- ✅ Arsip surat immutable — cetak ulang buat entry baru, entry lama ditandai "digantikan"
- ⚠️ Format baku nomor surat belum dikonfirmasi dari klien — suggestion akan mengikuti format apapun yang sudah dipakai sebelumnya

### Surat Masuk/Keluar & Disposisi
- ✅ Surat masuk = arsip digital dari scan surat fisik yang masuk ke kampus
- ✅ Nomor agenda surat masuk: auto-increment per tahun, tidak diinput admin
- ✅ Disposisi: tujuan field teks bebas (Phase 1), flat (tidak ada chain), one-to-many per surat
- ✅ Status disposisi dikelola oleh Admin Surat — pejabat tidak perlu punya akun
- ✅ Buku agenda surat masuk = view dari tabel `surat_masuk` (bukan tabel terpisah)
- ✅ Surat keluar — arsitektur 3 kamar terpisah (seperti OpenSID): Kamar 1 `permohonan_surat`, Kamar 2 `surat_tercetak` (arsip cetak dari template), Kamar 3 `surat_keluar` (buku agenda manual)
- ✅ Kamar 2 dan Kamar 3 tidak punya FK satu sama lain — intentional, beda tujuan
- ✅ Kamar 2 support dua sub-flow: generate dari permohonan mahasiswa (`permohonan_id` terisi) dan generate langsung admin (`permohonan_id = NULL`)
- ⚠️ Disposisi ke pejabat dari master (FK + email notifikasi) — Phase 2
- ⚠️ Disposisi berjenjang (diteruskan ke pejabat berikutnya) — Phase 2, belum ada kebutuhan eksplisit

### Notifikasi
- ✅ Notifikasi email via SMTP untuk update status
- ⚠️ Notifikasi WhatsApp — Phase 2, tergantung budget dan provider

---

## Fitur Inti — Wajib Ada (MVP Phase 1)

Fitur-fitur ini adalah delivery Phase 1 yang harus selesai terlebih dahulu.

---

### 1. Autentikasi & Manajemen Role

**Deskripsi**: Login dengan email/password (dari import SIAKAD). Role-based access control.

**User Story**: Sebagai admin, saya assign role ke user agar akses terkontrol. Sebagai mahasiswa, saya login dan langsung masuk ke halaman layanan.

**Acceptance Criteria**:
- [ ] Login email + password (bukan whitelist domain — sesuai data import)
- [ ] Role awal: Super Admin, Admin Surat, Mahasiswa
- [ ] User nonaktif (`is_active = false`) tidak bisa login — redirect dengan pesan jelas
- [ ] Properti user: `is_active`, `role`, `unit_id` (untuk masa depan multi-unit)
- [ ] Super Admin bisa activate/deactivate user dan ubah role kapan saja
- [ ] Setiap login/logout tercatat di activity log

---

### 2. Konfigurasi Sistem

**Deskripsi**: Pengaturan dasar kampus yang dipakai di seluruh sistem — nama kampus, logo, unit, daftar pejabat, format nomor surat.

**Acceptance Criteria**:
- [ ] Setup: nama universitas, kode universitas, logo, alamat
- [ ] Manajemen units (nama unit, kode unit) — meski Phase 1 hanya satu aktif
- [ ] Manajemen daftar pejabat: nama, jabatan, unit, foto tanda tangan (upload PNG, simpan di storage private)
- [ ] Format nomor surat (template teks untuk helper): `{urut}/{unit}/{kode_univ}/{bulan_romawi}/{tahun}`
- [ ] Tahun akademik aktif
- [ ] Konfigurasi SMTP email

---

### 3. Master Template Surat

**Deskripsi**: Admin upload .docx, sistem deteksi placeholder, beri label, set persyaratan, data tambahan, dan SLA. Satu form terpadu — tidak perlu pindah-pindah menu.

**User Story**: Sebagai admin, saya upload template Word dan sistem langsung tahu field apa yang perlu diisi. Saya setup persyaratan dan data tambahan langsung di halaman yang sama.

**Acceptance Criteria**:

**Upload & Deteksi Placeholder**:
- [ ] Upload .docx, validasi tipe & ukuran (max 10MB)
- [ ] Deteksi otomatis semua `{{variabel}}` dari seluruh konten file (paragraf, tabel, header, footer, textbox)
- [ ] Cocokkan dengan Kamus Placeholder di DB → tentukan `filled_by` default per placeholder:
  - Kelompok profil & sistem → `filled_by = 'sistem'`
  - Kelompok waktu, nomor surat, TTD slot → `filled_by = 'admin'`
  - Placeholder bebas (tidak di kamus) → `filled_by = 'mahasiswa'` (default)
- [ ] Admin review tabel hasil deteksi: bisa override `filled_by`, tipe input, dan set `label_mahasiswa` (label ramah untuk form permohonan)
- [ ] Placeholder dengan `filled_by = 'mahasiswa'`: tampil di form permohonan mahasiswa; `filled_by = 'admin'`: hanya tampil di form generate surat

**Informasi & Konfigurasi Template**:
- [ ] Admin set: nama, kategori, unit, deskripsi, SLA (hari kerja), status (draft/aktif)
- [ ] Flag `is_permohonan_mandiri` (boolean): jika true → template muncul di list permohonan mahasiswa; jika false → hanya bisa di-generate langsung oleh admin
- [ ] Template aktif yang sudah punya permohonan tidak bisa dihapus (hanya nonaktif)
- [ ] Preview template (tampilkan isi dokumen sebelum publish)
- [ ] Halaman "Panduan Placeholder" — daftar semua placeholder kamus + konvensi penamaan + bisa di-copy admin

**Setup Persyaratan (FindOrCreate)**:
- [ ] Di halaman edit template, section "Persyaratan" dengan searchable dropdown `ref_syarat_surat`
- [ ] Ketik nama → autocomplete dari master; tidak ketemu → tombol "+ Buat Persyaratan Baru" (inline modal, tidak keluar halaman)
- [ ] Inline modal: nama, deskripsi, `template_file` (upload Word/PDF opsional untuk didownload mahasiswa), tipe file diterima, ukuran max
- [ ] Setelah simpan: masuk master `ref_syarat_surat` DAN langsung ter-link ke template
- [ ] Per persyaratan yang di-link: toggle `is_required` dan drag reorder urutan

**Setup Data Tambahan (FindOrCreate serupa)**:
- [ ] Section "Data Tambahan" di halaman edit template — field yang diisi mahasiswa tapi TIDAK masuk ke surat (mis. no. telepon, alamat)
- [ ] Admin tambah field: label, tipe (text/date/number), is_required, helper_text
- [ ] `field_key` auto-generate dari label (bisa di-edit manual)
- [ ] Drag reorder urutan field
- [ ] Soft delete field — tidak bisa hard delete jika sudah ada nilai dari permohonan

**Coba Template (Preview)**:
- [ ] Tombol "Coba Template" di halaman detail template
- [ ] Buka modal — flat form: satu input per placeholder (semua dari `template_placeholder_config`), label dari `label_mahasiswa` atau nama placeholder teknis jika belum dikonfigurasi
- [ ] Tidak ada validasi required — ini mode percobaan, boleh dikosongkan
- [ ] Tombol "Generate & Download" → substitusi nilai ke .docx → file langsung download ke browser
- [ ] **Tidak disimpan ke DB** — ephemeral, tidak ada `permohonan_id`, tidak ada entry arsip
- [ ] File diberi keterangan di header: `PREVIEW — [nama template] — [tanggal generate]` agar tidak bisa beredar sebagai surat resmi
- [ ] Output format: **.docx** (Phase 1) — lebih cepat, tidak perlu pipeline PDF; PDF opsional Phase 2

> **Catatan arsitektur**: logic generate di modal ini memakai `TemplateSubstitutionService` yang sama dengan Fitur 7 — hanya berbeda pada: tidak ada `permohonan_id`, tidak simpan file ke storage, dan tambah header PREVIEW. Form field-nya memakai Blade partial yang sama dengan form permohonan mahasiswa.

---

### 4. Master Persyaratan Surat

**Deskripsi**: Master global persyaratan dokumen yang bisa dipakai lintas template. Dikelola dari dua tempat: menu khusus Master Persyaratan, atau inline dari form edit template (FindOrCreate).

**Acceptance Criteria**:
- [ ] CRUD `ref_syarat_surat` dari menu Master Persyaratan: nama, deskripsi, `template_file` (nullable — file Word/PDF yang bisa didownload mahasiswa untuk diisi manual), tipe file diterima, ukuran max
- [ ] Persyaratan yang punya `template_file` ditampilkan dengan tombol "⬇ Download Template" di form permohonan mahasiswa
- [ ] Persyaratan bisa dipakai di banyak template sekaligus (many-to-many via `syarat_surat`)
- [ ] Dari halaman detail persyaratan: tampilkan daftar template yang menggunakannya
- [ ] Persyaratan tidak bisa dihapus jika masih dipakai oleh template aktif

---

### 5. Permohonan Surat Mandiri (Layanan Mahasiswa)

**Deskripsi**: Mahasiswa pilih jenis surat, isi form dinamis (4 lapisan), upload persyaratan, submit. Tersedia tiga menu utama: Buat Permohonan, Riwayat Permohonan, dan Dokumen Saya.

#### 5.1 Buat Permohonan

**Acceptance Criteria**:
- [ ] List template yang tersedia hanya menampilkan yang `is_permohonan_mandiri = true` dan `status = aktif`
- [ ] Setiap item di list: nama surat, deskripsi, estimasi SLA, jumlah persyaratan
- [ ] Form permohonan terdiri dari **4 lapisan**:

  **Lapisan 1 — Data Otomatis (dari profil)**
  - [ ] Tampilkan read-only: nama, NIM, prodi, fakultas — tidak bisa diedit mahasiswa

  **Lapisan 2 — Isian Surat** (placeholder `filled_by = 'mahasiswa'`)
  - [ ] Render field sesuai tipe yang dikonfigurasi: text, date, number
  - [ ] Label dari `label_mahasiswa` di `template_placeholder_config` (bukan nama placeholder teknis)
  - [ ] Field required divalidasi sebelum submit
  - [ ] Nilai disimpan ke `permohonan_surat.isian_form` JSON saat submit

  **Lapisan 3 — Data Tambahan** (dari `template_data_tambahan_fields`)
  - [ ] Render field sesuai konfigurasi admin: text, date, number
  - [ ] Label dari `template_data_tambahan_fields.label`
  - [ ] Helper text ditampilkan di bawah field jika ada
  - [ ] Field required divalidasi sebelum submit
  - [ ] Nilai disimpan ke `permohonan_data_tambahan_values` (EAV) saat submit

  **Lapisan 4 — File Persyaratan**
  - [ ] List persyaratan dari `syarat_surat` — tampilkan required/opsional
  - [ ] Jika persyaratan punya `template_file`: tombol "⬇ Download Template" di samping item
  - [ ] Per item: tombol "Upload File" DAN tombol "Pilih dari Dokumen Saya"
  - [ ] "Pilih dari Dokumen Saya": buka modal list `dokumen_mahasiswa`, bisa filter by tipe/nama
  - [ ] Upload baru → file otomatis tersimpan ke `dokumen_mahasiswa` sekaligus
  - [ ] Validasi: tipe file dan ukuran max sesuai konfigurasi persyaratan
  - [ ] Persyaratan required tidak bisa submit jika belum diupload

- [ ] Tombol "Simpan Draft" — simpan progress tanpa submit
- [ ] Tombol "Ajukan Permohonan" — submit, status → `pending`
- [ ] Submit → email konfirmasi otomatis ke mahasiswa

#### 5.2 Dokumen Saya (Media Library)

**Acceptance Criteria**:
- [ ] Tabel list semua file yang pernah diupload mahasiswa ini: nama, kategori syarat, tanggal upload, ukuran, dipakai di N permohonan
- [ ] Upload dokumen baru langsung dari halaman ini (tanpa harus ada permohonan)
- [ ] Hapus dokumen — tidak bisa hapus jika dokumen sedang dipakai di permohonan aktif (pending/diproses)
- [ ] Tidak ada expiry dokumen — mahasiswa manage sendiri

#### 5.3 Riwayat Permohonan

**Acceptance Criteria**:
- [ ] Flat list semua permohonan yang pernah diajukan, diurutkan terbaru
- [ ] Kolom: jenis surat, tanggal ajukan, status (dengan warna), aksi
- [ ] Status `pending` → tombol "Edit" dan "Batalkan" tampil
- [ ] Batalkan: konfirmasi dialog → status → `dibatalkan`; tidak bisa diurungkan
- [ ] Edit: buka ulang form dengan data pre-fill; submit → update record yang sama, status tetap `pending`
- [ ] Status `diverifikasi`, `disetujui`, `ditolak`, `selesai` → tombol Edit/Batalkan tidak tampil
- [ ] Status `ditolak` → tombol "Ajukan Ulang" tampil
- [ ] Status `selesai` + ada file → tombol "Download Surat"
- [ ] Halaman detail permohonan: tampilkan semua 4 lapisan data yang disubmit, status history, alasan tolak/setujui jika ada

#### 5.4 Resubmit (Ajukan Ulang)

**Acceptance Criteria**:
- [ ] Klik "Ajukan Ulang" dari permohonan yang ditolak → buka form baru dengan data pre-fill
- [ ] Pre-fill: isian surat (dari `isian_form` lama), data tambahan (dari `permohonan_data_tambahan_values` lama)
- [ ] File persyaratan pre-select dari `dokumen_mahasiswa` yang sama dengan permohonan sebelumnya
- [ ] Buat `permohonan_surat` baru dengan `parent_permohonan_id` = id permohonan yang ditolak
- [ ] Mahasiswa bisa ubah/tambah data sebelum submit ulang

---

### 6. Review & Approval Permohonan (Admin)

**Deskripsi**: Admin melihat daftar permohonan, review detail, approve atau reject.

**User Story**: Sebagai admin, saya bisa review semua permohonan dan filter berdasarkan status/jenis/tanggal. Saya bisa tolak dengan keterangan jelas.

**Acceptance Criteria**:
- [ ] Dashboard menampilkan: total pending, mendekati deadline (kuning), overdue (merah)
- [ ] Tabel permohonan: filter by status, jenis surat, tanggal, nama/NIM
- [ ] Membuka halaman detail permohonan berstatus `pending` → **otomatis trigger** update status ke `diverifikasi` (satu kali, idempotent — jika sudah `diverifikasi` tidak berubah lagi)
- [ ] Halaman detail: tampilkan semua 4 lapisan data — profil mahasiswa, isian surat, data tambahan (key-value), file persyaratan (dengan tombol download per file)
- [ ] Jika permohonan adalah resubmit: tampilkan label "Pengajuan ulang dari #[nomor permohonan lama]" + link ke permohonan sebelumnya
- [ ] Tombol Setujui: wajib isi keterangan persetujuan (mis. "Persyaratan lengkap, disetujui") + pilih pejabat dari dropdown — keterangan masuk log internal
- [ ] Tombol Tolak: wajib isi keterangan alasan penolakan — **keterangan ini yang ditampilkan ke mahasiswa** di riwayat permohonan
- [ ] Notifikasi email otomatis ke mahasiswa setelah approve/reject
- [ ] Permohonan yang sudah disetujui tidak bisa di-unApprove kecuali Super Admin dengan alasan tercatat
- [ ] Setelah approve → tombol "Generate Surat" muncul (masuk flow Fitur 7 sub-flow A)
- [ ] Admin bisa upload `file_surat_scan` (scan TTD basah) setelah surat dicetak fisik → mahasiswa bisa download dari riwayat

---

### 7. Generate & Cetak Surat

**Deskripsi**: Fitur ini adalah Kamar 2 dari arsitektur surat keluar — menghasilkan `surat_tercetak` dari template. Mendukung dua sub-flow: dari permohonan mahasiswa yang sudah disetujui, atau generate langsung oleh admin tanpa permohonan.

**User Story**: Sebagai admin, setelah approve saya generate surat langsung dari sistem, preview dulu, baru cetak resmi. Saya juga bisa generate surat internal (Nota Dinas, SK) langsung dari template tanpa harus ada permohonan.

**Acceptance Criteria**:

**Sub-flow A — Generate dari Permohonan (Kamar 1 → Kamar 2)**:
- [ ] Tombol "Generate Surat" hanya muncul di permohonan berstatus `disetujui`
- [ ] Form generate otomatis pre-fill dari data permohonan mahasiswa
- [ ] `surat_tercetak.permohonan_id` = id permohonan yang bersangkutan

**Sub-flow B — Generate Langsung oleh Admin (Kamar 2 tanpa Kamar 1)**:
- [ ] Menu "Generate Langsung" tersedia di sidebar — admin pilih template dari daftar aktif
- [ ] Form generate identik dengan sub-flow A — tidak perlu buat form baru
- [ ] Placeholder data mahasiswa (nama, NIM, prodi) tidak pre-fill dari profil — diisi manual oleh admin
- [ ] `surat_tercetak.permohonan_id = NULL`
- [ ] Contoh penggunaan: Nota Dinas, SK Rektor, Surat Edaran, Undangan Rapat internal

**Form Generate (berlaku untuk kedua sub-flow)**:
- [ ] Semua placeholder template tampil — Smart Default pre-fill otomatis, admin review dan override
- [ ] Field time-based (`{{tanggal_surat}}`, `{{bulan_surat}}`, `{{tahun_surat}}`): pre-fill hari ini, bisa di-override
- [ ] Field `{{nomor_surat}}`: pre-fill suggestion dari arsip terakhir template+tahun, bisa di-override; AJAX warning jika duplikat
- [ ] Field TTD slot: dropdown pejabat per slot, preview nama+jabatan, warning jika tidak punya file TTD
- [ ] Field bebas: render sesuai tipe infer/konfigurasi (date picker, file upload, textarea, dsb)
- [ ] Preview surat sebelum generate final
- [ ] Generate draft → PDF dengan watermark "DRAFT" diagonal
- [ ] Generate final → substitusi semua placeholder → simpan ke `data_placeholder` JSON → PDF bersih + DOCX
- [ ] QR code verifikasi digenerate dan dimasukkan ke surat (pojok bawah atau footer)

---

### 8. Arsip Surat Tercetak

**Deskripsi**: Log immutable setiap surat yang digenerate final. Cetak ulang buat entry baru.

**Acceptance Criteria**:
- [ ] Entry arsip: nomor surat, tanggal generate, user yang generate, nama pejabat approve, data placeholder (JSON snapshot), link file PDF & DOCX
- [ ] Data arsip tidak bisa di-UPDATE oleh siapapun
- [ ] Cetak ulang → entry baru, entry lama ditandai `status: digantikan`, ada `replaced_by_id`
- [ ] Pencarian arsip: by nomor surat, nama, NIM, tanggal, jenis surat
- [ ] Export arsip ke Excel untuk periode tertentu

---

### 9. Surat Masuk, Disposisi & Buku Agenda Masuk

**Deskripsi**: Admin mencatat surat fisik yang diterima (beserta scan digitalnya), kemudian memberi instruksi disposisi kepada pejabat/unit terkait. Buku agenda surat masuk adalah view tabel dari data yang sama.

**Alur utama**:
```
Surat fisik diterima bagian administrasi
  → Admin scan surat → upload ke sistem
  → Admin isi metadata surat masuk
  → [Opsional] Admin tambah satu atau lebih disposisi
     → Tulis tujuan (teks bebas: "WR I", "LPPM", "Dekan FT")
     → Tulis instruksi, sifat, batas waktu
     → [Opsional] Cetak lembar disposisi PDF → serahkan fisik ke pejabat
  → Pejabat tindaklanjuti secara offline
  → Admin kembali ke sistem → update status disposisi + catat tindak lanjut
```

#### 9.1 Form Surat Masuk

**Acceptance Criteria**:
- [ ] Nomor agenda: `SMALLINT UNSIGNED` auto-increment per tahun — **tidak diinput admin**, muncul sebagai label baca-saja setelah simpan (contoh: "012/2025")
- [ ] Field wajib: tanggal terima, nomor surat asli (dari kop surat pengirim), pengirim, perihal
- [ ] Field opsional: tanggal surat (di kop pengirim), kode klasifikasi (dari master klasifikasi), keterangan, lampiran (upload PDF/JPG max 10MB; bisa lebih dari satu file)
- [ ] File scan: upload wajib (PDF atau JPG/PNG dari scanner fisik)
- [ ] Soft delete — surat masuk yang dihapus masih ada di arsip, hanya tersembunyi dari tampilan aktif
- [ ] Pencarian: by pengirim, perihal, nomor surat, tanggal terima, status disposisi

#### 9.2 Disposisi Surat Masuk

**Keputusan Phase 1**: penerima disposisi adalah **teks bebas** — tidak perlu FK ke akun pejabat. Admin yang selalu mengelola dan update status. Prinsip sama dengan proxy approval.

**Acceptance Criteria**:
- [ ] Satu surat masuk bisa punya **lebih dari satu disposisi** (one-to-many)
- [ ] Form disposisi: tujuan (text, contoh: "Wakil Rektor I"), isi instruksi (textarea), sifat (`segera` / `biasa` / `rahasia`), batas waktu (date, opsional)
- [ ] Status disposisi: `belum_ditindaklanjuti` (default) → `sudah_ditindaklanjuti`
- [ ] Saat update status → admin isi: catatan tindak lanjut (textarea) + tanggal tindak lanjut (auto atau override)
- [ ] `ditindaklanjuti_oleh` dan `ditindaklanjuti_at` tercatat otomatis (siapa admin yang update + kapan)
- [ ] Tombol **Cetak Lembar Disposisi** → generate PDF lembar disposisi yang bisa dicetak dan diserahkan fisik ke pejabat
- [ ] Di dashboard admin: counter "X disposisi belum ditindaklanjuti" — bisa disertai highlight warna untuk yang melewati batas waktu
- [ ] Disposisi **tidak bisa diteruskan ke pejabat lain** (tidak ada chain) — fase 1 flat saja

**Phase 2 (catatan untuk upgrade nanti)**:
- Tambah kolom `pejabat_id FK pejabat NULL` di tabel disposisi
- Jika `pejabat_id` diisi dan pejabat punya kolom `email` di master → sistem kirim notifikasi email ke pejabat
- Pejabat bisa klik link di email untuk konfirmasi "sudah ditindaklanjuti" (magic link, sama seperti approval Phase 2)
- Tidak perlu pejabat login ke sistem

#### 9.3 Buku Agenda Surat Masuk

**Acceptance Criteria**:
- [ ] View tabel dengan kolom: No. Agenda, Tgl Terima, No. Surat (asli), Tgl Surat, Pengirim, Perihal, Disposisi ke (gabungan tujuan), Status Disposisi
- [ ] Filter: tahun, bulan, rentang tanggal, pengirim (search), status disposisi
- [ ] Export ke Excel dan PDF untuk periode tertentu
- [ ] Pagination + search bawaan (DataTables)
- [ ] **Buku agenda adalah view dari `surat_masuk` — bukan tabel terpisah**. Tidak ada proses double-entry.

---

### 10. Surat Keluar — Arsitektur 3 Kamar

**Keputusan arsitektur**: Mengikuti pola OpenSID — tiga kamar terpisah, tidak ada unified view, tidak ada FK antar kamar 2 dan 3.

```
KAMAR 1                  KAMAR 2                    KAMAR 3
Permohonan Surat    →    Arsip Surat Tercetak        Buku Agenda Surat Keluar
─────────────────        ─────────────────────       ─────────────────────────
permohonan_surat         surat_tercetak              surat_keluar
(Fitur 5 & 6)            (Fitur 7 & 8)               (manual entry, Fitur 10)

permohonan_id FK ──────> surat_tercetak              [tidak ada FK ke kamar 2]
                         (permohonan_id nullable)
                         ↑
                         juga dari generate
                         langsung admin
                         (permohonan_id = NULL)
```

**Prinsip**: Surat yang di-generate dari template (kamar 2) **tidak otomatis masuk** buku agenda keluar (kamar 3). Jika admin ingin surat yang digenerate dicatat di buku agenda korespondensi dinas, admin input manual di kamar 3. Double-entry ini disengaja — sama seperti OpenSID.

---

#### 10.1 Kamar 1 & 2 — Sudah Dirancang

- Kamar 1 (Permohonan): Fitur 5 & 6 — mahasiswa submit, admin approve
- Kamar 2 (Arsip Cetak): Fitur 7 & 8 — generate dari template (sub-flow A dari permohonan, sub-flow B generate langsung)

---

#### 10.2 Kamar 3 — Buku Agenda Surat Keluar (Manual)

**Deskripsi**: Pencatatan korespondensi dinas resmi yang keluar dari kampus ke pihak luar. 100% manual entry — tidak ada baris yang auto-masuk dari cetak template. Admin input metadata + upload scan surat.

**Kapan digunakan**: Surat ke Dikti, surat ke perusahaan mitra, SK yang dikirim ke instansi, nota dinas antar unit, surat jawaban ke orang tua mahasiswa, surat lama yang perlu didigitalisasi ke sistem.

**Alur**:
```
Admin cetak/terima surat keluar (dari manapun sumbernya)
  → Admin buka menu Buku Agenda → Surat Keluar
  → Isi form: nomor surat, tanggal, tujuan, perihal, upload scan
  → Simpan → nomor agenda tergenerate otomatis
  → Muncul di tabel buku agenda keluar
```

**Acceptance Criteria**:
- [ ] Nomor agenda: `SMALLINT UNSIGNED` auto-increment per tahun — tidak diinput admin (sama seperti surat masuk)
- [ ] Field wajib: nomor surat, tanggal surat, tujuan, perihal
- [ ] Field opsional: kode klasifikasi, keterangan, berkas scan (upload PDF/JPG)
- [ ] AJAX duplicate check pada `nomor_surat` sebelum simpan — warning jika nomor sudah pernah dipakai tahun ini
- [ ] Edit dan soft delete
- [ ] Tabel buku agenda: kolom No. Agenda, No. Surat, Tgl Surat, Tujuan, Perihal, Keterangan
- [ ] Filter: tahun, rentang tanggal, tujuan (search), perihal (search)
- [ ] Export Excel dan PDF untuk periode tertentu (buku agenda resmi)

**Yang tidak masuk Phase 1** (Phase 2):
- Tracking ekspedisi: `tanggal_pengiriman`, `tanda_terima`, flag `sudah_dikirim` — fitur dari OpenSID untuk tracking pengiriman fisik via kurir/pos

---

### 11. Notifikasi Email

**Acceptance Criteria**:
- [ ] Email terkirim: konfirmasi permohonan masuk, disetujui, ditolak (dengan alasan), surat siap
- [ ] Template email rapi (HTML, bukan plain text)
- [ ] SMTP via konfigurasi di sistem (bukan hardcode)
- [ ] Queue-based (retry otomatis jika SMTP down sementara)

---

**Persyaratan Teknis Keseluruhan**:
- PHP 8.1+, Laravel 10/11 (LTS)
- MySQL 8.0+
- PHPOffice/PHPWord untuk parsing & substitusi `.docx`
- MPDF untuk generate PDF
- Spatie Permission (RBAC), Spatie ActivityLog
- Laravel Storage (lokal, opsional S3/Minio)
- Laravel Queue (database driver untuk awal)
- SimpleSoftwareIO/simple-qrcode untuk QR code

---

## Fitur Tambahan — Sebaiknya Ada (Phase 2)

| Fitur | Deskripsi | Catatan |
|-------|-----------|---------|
| **Approval Berjenjang** | Pejabat punya akun, bisa approve dari portal, multi-level per template | Upgrade dari proxy approval Phase 1 |
| **Disposisi Berjenjang** | Disposisi bisa diteruskan dari satu pejabat ke pejabat lain | Butuh role Pejabat aktif |
| **Nomor Surat Semi-Otomatis** | Sistem suggest nomor urut berikutnya, admin konfirmasi | Upgrade dari manual penuh |
| **QR Code + Portal Verifikasi Publik** | Halaman web tanpa login untuk verifikasi keaslian surat | QR sudah ada di Phase 1 tapi portal verifikasi publik menyusul |
| **Notifikasi WhatsApp** | Via API (Fonnte/Wa.me) untuk notifikasi status permohonan | Tergantung budget provider |
| **Bulk Printing (Batch)** | Admin upload Excel daftar penerima, generate surat massal | Butuh Queue yang robust |
| **Media Library Mahasiswa** | Dokumen yang pernah diupload tersimpan — tidak perlu upload ulang | Tabel `media_files` polimorfik |
| **Watermark Nama Penerima** | PDF yang didownload mengandung watermark nama mahasiswa di setiap halaman | Layer keaslian tambahan |
| **Status Tracking Detail** | Timeline progress permohonan seperti tracking paket | UX tambahan untuk mahasiswa |
| **Export Laporan** | Statistik volume surat, permohonan per periode, per jenis, per status | Untuk kebutuhan pelaporan manajemen |

---

## Pertimbangan Masa Depan (Phase 3+)

- **Tanda Tangan Elektronik Tersertifikasi (TTE)** via BSrE BSSN — sah secara hukum UU ITE, butuh infrastruktur khusus
- **Integrasi SIAKAD realtime** — auto-fill data mahasiswa dari API SIAKAD saat request (eliminasi risiko data tidak up-to-date)
- **Multi-unit dengan admin terpisah** — setiap fakultas punya admin dan counter nomor surat sendiri
- **Generator Sertifikat** — template landscape (horizontal) untuk sertifikat seminar/KKN, batch untuk 1000+ peserta
- **e-Meterai** — untuk surat yang secara hukum memerlukan meterai (kontrak, perjanjian)
- **Mobile App / PWA** — notifikasi push dan tracking status dari smartphone
- **API Publik** — integrasi dengan portal mahasiswa, sistem kampus lain
- **OCR Surat Masuk** — ekstrak metadata otomatis dari scan surat fisik
- **Dashboard Analytics** — grafik tren, statistik volume, rata-rata processing time

---

## Pertimbangan Teknis Kritis

### Template & Placeholder — Kamus & Smart Default

- **Library**: PHPOffice/PHPWord — `TemplateProcessor` default pakai `${VAR}`, buat **wrapper class** yang normalize ke `{{VAR}}`
- **Deteksi**: Parse semua node XML dalam .docx — jangan hanya body paragraf; cek header, footer, tabel, textbox
- **Kamus Placeholder**: tabel `placeholder_definitions` di DB — bukan hardcode. Kolom kunci: `name`, `group` (profil/waktu/sistem/counter), `input_type`, `source`, `is_overridable`
- **Resolusi placeholder saat form generate dibuka**:
  1. Cocokkan tiap `{{var}}` dengan `placeholder_definitions`
  2. Jika cocok → ambil nilai default sesuai `source` (profil user, `Carbon::now()`, counter, config)
  3. Jika tidak cocok → cek pola regex TTD slot
  4. Jika tidak TTD → inferensikan tipe input dari nama: `tanggal_*` → date, `upload_*` / `*_file` → file, `*_keterangan` / `*_catatan` → textarea
  5. Semua hasil pre-fill tampil di form, semua (kecuali kelompok profil) bisa di-override
- **Loop/tabel**: `{{#items}}...{{/items}}` untuk baris berulang (daftar nilai, dsb)
- **Snapshot via JSON**: saat generate final, **seluruh nilai** — termasuk data pejabat TTD — disimpan ke `data_placeholder` JSON di `surat_tercetak`. Arsip bisa dibaca ulang tanpa JOIN ke tabel lain
- **Panduan Placeholder**: halaman admin yang render kamus secara dinamis dari tabel `placeholder_definitions` — admin pembuat template bisa copy-paste nama placeholder langsung ke Word

### Arsitektur Reusable — Template Substitution & Dynamic Form

Dua komponen dibuat generic dari awal agar tidak ada duplikasi logika:

**`TemplateSubstitutionService` (backend)**
Satu service class yang menerima `template_id` + `array [placeholder => value]` → return file stream .docx.
Dipakai di tiga konteks berbeda:

| Konteks | Simpan ke DB? | Tambahan |
|---|---|---|
| Preview admin (Fitur 3) | Tidak — ephemeral | Inject header "PREVIEW" ke dokumen |
| Generate surat dari permohonan (Fitur 7 sub-flow A) | Ya → `surat_tercetak` | Pre-fill dari `isian_form` JSON |
| Generate surat langsung admin (Fitur 7 sub-flow B) | Ya → `surat_tercetak` | `permohonan_id = NULL` |

**Blade partial `components.dynamic-placeholder-form`**
Menerima array field config → render input (text/date/number) dengan label, helper text, required marker.
Dipakai di tiga tempat:

| Tempat | Field source | Konteks |
|---|---|---|
| Modal preview (Fitur 3) | Semua placeholder template | Admin test, tidak ada validasi strict |
| Form permohonan mahasiswa — Lapisan 2 (Fitur 5) | `filled_by = 'mahasiswa'` | Mahasiswa isi, nilai ke `isian_form` |
| Form generate surat admin — Lapisan admin (Fitur 7) | `filled_by = 'admin'` | Admin isi sisa placeholder |

Perbedaan antar konteks dikontrol lewat **parameter partial** (mis. `$readOnly`, `$strict`, `$showHelperText`) — bukan partial berbeda.

### PDF Generation
- **MPDF** lebih cocok dari DomPDF untuk: tabel kompleks, karakter Indonesia (diakritik, tanda baca khusus), RTL jika dibutuhkan
- Untuk bulk (50+ surat): **Laravel Queue** — jangan generate synchronous; tampilkan notifikasi "sedang diproses" + notifikasi selesai
- Simpan file PDF hasil generate di storage — jangan generate ulang setiap kali diakses

### Database Design — Arsitektur Tabel Kritis

```sql
-- Jangan merge permohonan dan surat_tercetak
-- Hubungan: satu permohonan bisa hasilkan N surat (cetak ulang, batch)

permohonan_surat
  id
  parent_permohonan_id  BIGINT FK permohonan_surat NULL  -- resubmit: FK ke yang ditolak
  mahasiswa_id          BIGINT FK users
  template_id           BIGINT FK templates
  unit_id               BIGINT FK units
  status                ENUM('draft','pending','diverifikasi','disetujui','ditolak','selesai')
  isian_form            JSON NULL         -- Lapisan 2: nilai placeholder filled_by='mahasiswa'
  catatan_penolakan     TEXT NULL
  approved_by           BIGINT FK users NULL
  pejabat_id            BIGINT FK pejabat NULL
  catatan_approval      TEXT NULL
  approved_at           TIMESTAMP NULL
  file_surat_scan       VARCHAR(255) NULL -- admin upload scan TTD basah untuk download mahasiswa
  created_at, deleted_at

-- Lapisan 2 — konfigurasi placeholder per template (siapa yang isi, label, tipe)
template_placeholder_config
  id
  template_id       BIGINT FK templates
  placeholder_name  VARCHAR(100)      -- "nama_perusahaan"
  label_mahasiswa   VARCHAR(255)      -- "Nama Perusahaan Tempat Magang"
  tipe_input        ENUM('text','date','number','textarea')
  filled_by         ENUM('sistem','mahasiswa','admin')
  is_required       BOOLEAN DEFAULT true
  urutan            TINYINT

-- Lapisan 3 — schema Data Tambahan per template
template_data_tambahan_fields
  id
  template_id       BIGINT FK templates
  label             VARCHAR(255)      -- "Nomor Telepon Aktif"
  field_key         VARCHAR(100)      -- "no_telepon" (slug, tidak berubah meski label berubah)
  tipe_input        ENUM('text','date','number')
  is_required       BOOLEAN DEFAULT true
  helper_text       VARCHAR(255) NULL
  urutan            TINYINT DEFAULT 0
  deleted_at        TIMESTAMP NULL    -- soft delete; hard delete di-RESTRICT jika ada nilai

-- Lapisan 3 — nilai Data Tambahan per permohonan (EAV)
permohonan_data_tambahan_values
  id
  permohonan_id     BIGINT FK permohonan_surat  ON DELETE CASCADE
  field_id          BIGINT FK template_data_tambahan_fields  ON DELETE RESTRICT
  nilai             TEXT
  created_at

-- Lapisan 4 — master persyaratan (reusable lintas template)
ref_syarat_surat
  id
  nama              VARCHAR(255)      -- "Fotokopi KTP", "Surat Pernyataan"
  deskripsi         TEXT NULL
  template_file     VARCHAR(255) NULL -- path file Word/PDF untuk didownload mahasiswa
  accepted_types    VARCHAR(100)      -- "pdf,jpg,png,docx"
  max_size_mb       TINYINT DEFAULT 5

-- Lapisan 4 — pivot template ↔ persyaratan
syarat_surat
  id
  template_id       BIGINT FK templates
  syarat_id         BIGINT FK ref_syarat_surat
  is_required       BOOLEAN DEFAULT true
  urutan            TINYINT

-- Lapisan 4 — fulfilment persyaratan per permohonan
permohonan_syarat
  id
  permohonan_id     BIGINT FK permohonan_surat  ON DELETE CASCADE
  syarat_id         BIGINT FK ref_syarat_surat
  dokumen_id        BIGINT FK dokumen_mahasiswa NULL  -- link ke media library
  filename          VARCHAR(255)      -- snapshot nama file saat submit
  path              VARCHAR(255)      -- snapshot path storage
  uploaded_at       TIMESTAMP

-- Media library mahasiswa (reusable dokumen)
dokumen_mahasiswa
  id
  mahasiswa_id      BIGINT FK users
  nama              VARCHAR(255)      -- "KTP Saya", auto dari nama syarat
  syarat_id         BIGINT FK ref_syarat_surat NULL  -- kategorisasi opsional
  filename          VARCHAR(255)
  path              VARCHAR(255)
  file_size         INT
  created_at, deleted_at

surat_tercetak
  id, permohonan_id, nomor_surat, unit_id,
  digenerate_oleh, digenerate_at,
  data_placeholder (JSON), -- snapshot semua nilai saat generate
  file_pdf_path, file_docx_path,
  qr_hash, -- signed hash untuk URL verifikasi
  status (aktif|digantikan|dibatalkan),
  replaced_by_id, replaced_reason,
  created_at

units
  id, nama, kode, parent_id (untuk hierarki), is_active

pejabat
  id, unit_id, nama, nip_nidn, jabatan,
  file_ttd_path,   -- path ke storage private, BUKAN public
  is_active,
  user_id NULL     -- FK ke users (nullable); diisi di Phase 2
                   -- jika NULL → proxy approval; jika ada → bisa login & approve sendiri

-- Tabel pivot penandatangan per surat (support 1-N penandatangan)
-- Berbeda dengan OpenSID yang hanya punya satu id_pamong di log_surat
surat_penandatangan
  id, surat_tercetak_id FK,
  urutan,              -- 1, 2, 3 (urutan tampil di surat)
  label,               -- "Menyetujui", "Mengetahui" (opsional, untuk referensi)
  pejabat_id FK NULL,  -- null jika diisi manual
  nama_snapshot,       -- snapshot nama saat surat digenerate
  jabatan_snapshot,    -- snapshot jabatan saat digenerate
  nip_snapshot,
  file_ttd_path        -- snapshot path TTD yang dipakai saat itu
```

-- Surat masuk dari luar kampus (scan + metadata)
surat_masuk
  id
  nomor_agenda          SMALLINT UNSIGNED  -- auto per tahun (generate di PHP saat insert)
  tahun_agenda          SMALLINT           -- tahun nomor_agenda ini (untuk reset per tahun)
  nomor_surat           VARCHAR(50)        -- nomor dari kop surat pengirim
  tanggal_surat         DATE NULL          -- tanggal di kop pengirim (bisa beda dengan terima)
  tanggal_terima        DATE               -- tanggal surat diterima kampus
  pengirim              VARCHAR(150)
  perihal               VARCHAR(255)
  kode_klasifikasi      VARCHAR(20) NULL   -- dari master klasifikasi, opsional
  keterangan            TEXT NULL
  berkas_scan           VARCHAR(255)       -- path file scan di storage
  dicatat_oleh          BIGINT FK users
  unit_id               BIGINT FK units
  created_at, deleted_at

disposisi_surat_masuk
  id
  surat_masuk_id        BIGINT FK surat_masuk  -- cascade delete
  tujuan                VARCHAR(200)           -- teks bebas: "WR I", "LPPM", "Dekan FT"
  isi_instruksi         TEXT
  sifat                 ENUM('segera','biasa','rahasia') DEFAULT 'biasa'
  batas_waktu           DATE NULL
  status                ENUM('belum_ditindaklanjuti','sudah_ditindaklanjuti') DEFAULT 'belum_ditindaklanjuti'
  catatan_tindaklanjut  TEXT NULL
  dicatat_oleh          BIGINT FK users
  ditindaklanjuti_oleh  BIGINT FK users NULL
  ditindaklanjuti_at    TIMESTAMP NULL
  pejabat_id            BIGINT FK pejabat NULL  -- Phase 2: isi jika dipilih dari master pejabat
  created_at

-- Lampiran surat masuk (bisa lebih dari satu file per surat)
lampiran_surat_masuk
  id
  surat_masuk_id        BIGINT FK surat_masuk
  filename              VARCHAR(255)
  path                  VARCHAR(255)
  uploaded_by           BIGINT FK users
  created_at

-- Kamar 3: Buku agenda korespondensi dinas ke pihak luar (100% manual, tidak FK ke surat_tercetak)
surat_keluar
  id
  nomor_agenda          SMALLINT UNSIGNED  -- auto per tahun (generate di PHP saat insert)
  tahun_agenda          SMALLINT           -- untuk reset per tahun
  nomor_surat           VARCHAR(50)        -- nomor resmi surat yang dikirim
  kode_klasifikasi      VARCHAR(20) NULL   -- dari master klasifikasi
  tanggal_surat         DATE               -- tanggal di kop surat
  tanggal_catat         TIMESTAMP          -- waktu admin input (auto)
  tujuan                VARCHAR(150)
  perihal               VARCHAR(255)
  keterangan            VARCHAR(500) NULL
  berkas_scan           VARCHAR(255) NULL  -- upload scan PDF surat
  dicatat_oleh          BIGINT FK users
  unit_id               BIGINT FK units
  -- Phase 2: tracking ekspedisi/pengiriman fisik
  -- ekspedisi          TINYINT(1) DEFAULT 0
  -- tanggal_pengiriman DATE NULL
  -- tanda_terima       VARCHAR(200) NULL
  created_at, deleted_at
```

> **Kenapa snapshot?** Jabatan pejabat bisa berubah di masa depan. Arsip surat harus tetap menampilkan nama dan jabatan yang benar *saat surat dicetak*, bukan jabatan yang sekarang. Pola ini sama dengan sistem desa OpenSID yang menyimpan `id_pamong` — bedanya kita tambahkan snapshot field agar tidak perlu JOIN ke tabel `pejabat` untuk membaca data historis.

### Keamanan File Tanda Tangan
- Simpan di `storage/private/signatures/` — tidak boleh di folder `public/`
- Akses hanya via controller: `PejabatController@getTtd` dengan `Gate::authorize('view-ttd', $pejabat)`
- Setiap akses/generate yang menggunakan file TTD dicatat di ActivityLog dengan detail: user, pejabat, surat yang di-generate, timestamp

### QR Code & Verifikasi
- Generate saat `surat_tercetak` dibuat (bukan saat permohonan dibuat)
- Hash: `hash_hmac('sha256', $surat->id . $surat->nomor_surat, config('app.key'))`
- URL: `https://surat.kampus.ac.id/verify/{qr_hash}`
- Halaman verifikasi (publik, tanpa login): tampilkan jenis surat, status (valid/digantikan/dibatalkan), nama depan + inisial penerima, tanggal terbit
- Jika surat berstatus "digantikan": tampilkan keterangan + nomor surat penggantinya

### Nomor Surat — Suggestion + Anti Duplikat

**Storage**: `nomor_surat VARCHAR(100)` di `surat_tercetak` — plain string, sama seperti semua sistem referensi. Tidak ada tabel counter terpisah.

**Suggestion logic** (dijalankan saat form generate dibuka):
```php
$last = SuratTercetak::where('template_id', $template->id)
    ->whereYear('created_at', now()->year)
    ->whereIn('status', ['aktif', 'digantikan'])  // exclude dibatalkan
    ->latest('created_at')
    ->value('nomor_surat');  // "004/SK/UNsP/VII/2025"

if ($last && preg_match('/^(\d+)/', $last, $m)) {
    $next = str_pad((int)$m[1] + 1, strlen($m[1]), '0', STR_PAD_LEFT); // "005"
    $suggestion = preg_replace('/^\d+/', $next, $last); // "005/SK/UNsP/VII/2025"
} else {
    $suggestion = null; // input kosong, admin isi manual
}
```

**Anti duplikat — dua lapis**:
- AJAX check realtime saat admin ketik → warning friendly jika duplikat (bukan hard block)
- `UNIQUE KEY (nomor_surat, unit_id)` di DB → hard stop sebagai last resort; ditangkap di try/catch controller, ditampilkan sebagai pesan error bukan 500

**Cross-year**: `"001/SK/UNsP/I/2025"` ≠ `"001/SK/UNsP/I/2026"` → tidak duplikat secara natural, karena tahun ada di dalam string.

**Reset per tahun**: otomatis karena suggestion query filter `whereYear(now()->year)` — awal tahun baru tidak ada last nomor tahun ini → input kosong → admin mulai dari "001/...".

### UI Style (Konsisten dengan Ciengang)
- Template admin: **AdminLTE** (Bootstrap 3 base)
- Tombol: `btn btn-flat btn-sm` — jangan pakai button besar default Bootstrap
- Tabel: `table table-bordered table-striped table-hover` dengan `thead.bg-gray`
- Warna: `bg-olive` (positif/tambah), `btn-warning` (edit), `bg-maroon` (hapus/tolak), `bg-aqua` (info)
- Icon: **Font Awesome** — konsisten dengan ciengang
- DataTables untuk semua tabel dengan pagination + search + sort bawaan

---

## Pertanyaan yang Masih Belum Terjawab

Pertanyaan-pertanyaan di bawah ini belum ada jawabannya. Konfirmasi sebelum mulai implementasi.

### Prioritas Tinggi (Harus Terjawab Sebelum Mulai Coding)

1. **Format baku nomor surat**: Apakah kampus sudah punya format yang dipakai? Contoh: `001/BAA/UN-NP/VII/2025` — atau ada format berbeda? Ini menentukan helper text dan validasi format di sistem.

2. **Counter nomor surat**: Apakah per unit (BAA punya counter sendiri, Fakultas Teknik punya counter sendiri) atau satu counter global? Apakah reset per tahun kalender atau tahun akademik?

3. **Kapan pakai TTD basah vs TTD image**: Apakah ada aturan per jenis surat? Misal: semua surat ke instansi luar wajib TTD basah, surat internal bisa TTD image? Ini menentukan apakah perlu flag per template.

4. **Approval — apakah klien mau proxy approval (Phase 1) dulu atau langsung approval per pejabat?** Jelaskan ke klien dua opsi dan tradeoff-nya:
   - Option A: Admin Surat yang approve, wajib log nama pejabat → delivery cepat, tidak perlu onboarding pejabat
   - Option B: Pejabat punya akun dan login sendiri → lebih kredibel tapi butuh waktu ekstra untuk onboarding dan training

5. **Surat massal**: Apakah ada kebutuhan cetak surat untuk banyak mahasiswa sekaligus (batch)? Misal untuk undangan wisuda atau sertifikat seminar? Ini menentukan apakah perlu fitur bulk print atau cukup satu-satu.

### Prioritas Menengah (Terjawab Sebelum Phase 2)

6. **Disposisi**: Apakah pejabat yang menerima disposisi perlu akun di sistem, atau disposisi hanya dicatat dan ditandai "selesai" oleh admin? Ini menentukan apakah perlu onboarding pejabat di Phase 1 atau bisa ditunda ke Phase 2.

7. **Portal verifikasi publik**: Apakah perusahaan atau instansi luar perlu bisa verifikasi keaslian surat secara online (tanpa telepon ke kampus)? Jika ya, data apa yang boleh ditampilkan ke publik?

8. **Re-import SIAKAD**: Seberapa sering data mahasiswa perlu di-update? Ada prosedur SOP yang akan diberlakukan? Ini menentukan apakah perlu fitur diff/update atau selalu full-replace.

---

*Dokumen diperbarui: setiap jawaban konfirmasi dari klien harus mengubah label asumsi dari ⚠️ ke ✅. Pertanyaan yang sudah terjawab dipindahkan ke bagian "Ringkasan Jawaban" di atas.*
