# Perancangan Sistem Surat Kampus — Universitas Nusa Putra

---

## 1. Gambaran Umum

**Masalah yang diselesaikan**: Template surat tersebar di komputer lokal masing-masing staf. Tidak ada arsip terpusat, tidak ada standarisasi, tidak ada cara tahu nomor surat yang sudah keluar. Jika staf yang biasa mengelola surat tidak ada, proses berhenti.

**Yang dibangun**:
1. Sistem terpusat untuk menyimpan dan mengelola template surat
2. Layanan permohonan surat mandiri untuk mahasiswa
3. Arsip immutable setiap surat yang pernah dicetak
4. Pencatatan surat fisik (scan) masuk dan keluar

**Prinsip**: Fitur approval berjenjang, tanda tangan digital, dan integrasi SIAKAD adalah pelengkap — bukan misi utama. Jangan sampai menghambat delivery inti.

---

## 2. Scope & Prioritas Phase

| Phase | Fokus |
|---|---|
| **Phase 1 (MVP)** | Template management + Permohonan surat mahasiswa + Arsip cetak + Surat masuk/keluar scan |
| **Phase 2** | Approval berjenjang, multi-unit, pejabat punya akun, disposisi ke email |
| **Phase 3+** | TTE BSrE, integrasi SIAKAD realtime, bulk print, mobile app |

**Tipologi surat per phase**:

| Kategori | Contoh | Phase |
|---|---|---|
| Layanan Mahasiswa | SKMA, Rekomendasi Magang, Pengantar KKN, Dispensasi | Phase 1 |
| Administrasi Internal | Nota Dinas, Surat Edaran, Undangan Rapat | Phase 1 (tanpa approval strict) |
| Surat Keputusan (SK) | SK Rektor, SK Dekan, SK Panitia | Phase 2 |
| Kerjasama & Eksternal | MOU, Surat ke Instansi | Phase 2 |
| Sertifikat & Penghargaan | Sertifikat Seminar, Piagam | Phase 2 |

---

## 3. Aktor & Role

### Phase 1 — 3 Role

| Role | Siapa | Akses |
|---|---|---|
| **Super Admin** | IT / Koordinator | Akses penuh + manajemen user + batalkan surat final + restore |
| **Admin Surat** | Staf TU / BAA | Kelola template, proses permohonan, approve, generate surat, input surat masuk/keluar |
| **Mahasiswa** | Mahasiswa aktif | Buat permohonan, lihat riwayat milik sendiri, download surat |

- Admin Surat tidak bisa kelola user
- Mahasiswa tidak bisa lihat permohonan orang lain (`WHERE mahasiswa_id = auth()->id()`)
- Super Admin dan Admin Surat di Phase 1 hampir sama tampilannya — beda di menu manajemen user dan aksi destruktif

### Phase 2 — Tambah 2 Role

| Role Baru | Siapa | Akses |
|---|---|---|
| **Admin Unit** | Admin Surat yang di-scope per unit | Sama dengan Admin Surat tapi hanya data `unit_id = unit mereka`; bisa di-assign ke lebih dari 1 unit |
| **Pejabat** | Kaprodi, Dekan, WR | Dashboard minimalis: daftar surat yang butuh approval; tidak lihat menu operasional |

Implementasi multi-unit Phase 2 via Spatie Permission `teams` — `team_id = unit_id`. Satu user bisa punya role berbeda di unit berbeda.

---

## 4. Keputusan Desain Global

### 4.1 Arsitektur Sistem

- **Single-tenant**, satu kampus — bisa di-clone dan dikonfigurasi untuk kampus lain
- **Unit-aware sejak Phase 1**: tabel `units` terisi data real (Prodi Informatika, BAA, dll), semua tabel utama punya `unit_id FK units`. Phase 1: semua operasional satu unit, tidak ada filter. Phase 2: aktifkan `UnitScope` middleware.
- **Audit trail wajib Phase 1**: setiap tabel utama punya kolom `created_by` / `approved_by` / `dicatat_oleh`. Spatie ActivityLog sebagai lapisan kedua untuk timeline lengkap per record.

### 4.2 Arsitektur 3 Kamar Surat Keluar

```
KAMAR 1                  KAMAR 2                    KAMAR 3
Permohonan Surat    →    Arsip Surat Tercetak        Buku Agenda Surat Keluar
─────────────────        ─────────────────────       ─────────────────────────
permohonan_surat         surat_tercetak              surat_keluar
(Fitur 5 & 6)            (Fitur 7 & 8)               (manual entry, Fitur 10)
```

- Kamar 2 dan Kamar 3 tidak punya FK satu sama lain — intentional, beda tujuan
- Kamar 2: generate dari template (sub-flow A: dari permohonan; sub-flow B: langsung admin, `permohonan_id = NULL`)
- Kamar 3: 100% manual entry, tidak ada baris yang auto-masuk dari cetak template

### 4.3 Sistem Approval

**Phase 1 — Proxy Approval**:
- Pejabat tidak perlu akun. Admin Surat adalah satu-satunya approver di sistem.
- Saat approve: admin wajib pilih nama pejabat dari dropdown master + isi keterangan persetujuan → keterangan masuk log internal.
- Saat tolak: admin wajib isi keterangan alasan → ditampilkan ke mahasiswa.
- Data tersimpan di `permohonan_surat`: `approved_by` (admin yang klik), `pejabat_id` (pejabat yang memberikan persetujuan offline), `catatan_approval`, `approved_at`.

**Phase 2 — Approval per Pejabat**:
- Pejabat punya akun. Approval berjenjang, dikonfigurasi per template.
- Login via magic link (signed URL 48 jam) atau portal login sederhana.
- Tabel tambahan: `approval_workflow_templates` (konfigurasi step per template), `permohonan_approval_logs` (jejak per step).

### 4.4 Sistem Placeholder & Template

- Format: `{{nama_variabel}}` — double curly braces, snake_case
- Kamus Placeholder tersimpan di tabel `placeholder_definitions` (bukan hardcode) — Super Admin bisa tambah tanpa deploy ulang
- **Kelompok placeholder**:
  - Profil mahasiswa (`{{nama_mahasiswa}}`, `{{nim}}`, `{{prodi}}`, `{{fakultas}}`) → auto-fill, tidak bisa di-override
  - Konfigurasi sistem (`{{nama_universitas}}`, `{{logo_kampus}}`) → auto-fill, tidak bisa di-override
  - Waktu (`{{tanggal_surat}}`, `{{bulan_surat}}`, `{{tahun_surat}}`) → smart default hari ini, bisa di-override
  - Nomor surat (`{{nomor_surat}}`) → suggestion dari arsip terakhir, bisa di-override
  - Slot TTD (`{{ttd_1}}`, `{{nama_ttd_1}}`, `{{jabatan_ttd_1}}`, `{{nip_ttd_1}}`) → dropdown pejabat per slot; ganti angka untuk penandatangan berikutnya
  - Placeholder bebas → inferensi tipe dari nama, `filled_by` default `'mahasiswa'`
- **Dimensi `filled_by`** per placeholder: `sistem` / `mahasiswa` / `admin`
  - `mahasiswa` → tampil di form permohonan
  - `admin` → hanya tampil di form generate surat
  - `sistem` → auto-fill, tidak tampil di form mana pun
- **Inferensi tipe input** dari nama placeholder:
  - `tanggal_*` / `*_tanggal` → date picker
  - `upload_*` / `*_file` → file upload
  - `*_keterangan` / `*_catatan` / `*_isi` → textarea
  - `*_jumlah` / `*_nilai` / `*_angka` → number
  - Lainnya → text
- Admin bisa override tipe yang diinfer di pengaturan template
- Output surat: PDF dan DOCX
- Tipe konten: teks, gambar/image, tabel-loop (`{{#items}}...{{/items}}`)

### 4.5 Nomor Surat

- **Mode A**: satu placeholder `{{nomor_surat}}`, diisi admin sebagai string lengkap
- **Suggestion engine** (tanpa tabel counter terpisah): ambil `nomor_surat` terakhir template + tahun ini dari `surat_tercetak` → regex ekstrak angka di depan → increment → pre-fill
- AJAX duplicate check realtime saat admin ketik
- `UNIQUE KEY (nomor_surat, unit_id)` di DB sebagai hard guard
- Reset per tahun otomatis karena suggestion query filter `whereYear`

### 4.6 Tanda Tangan

- **Primer**: tanda tangan basah (cetak → TTD pena)
- **Sekunder**: image PNG di-embed ke dokumen, untuk surat semi-formal
- File TTD disimpan di `storage/private/signatures/` — tidak di folder public
- Akses file TTD hanya via controller dengan `Gate::authorize`
- Setiap penggunaan file TTD tercatat di ActivityLog

### 4.7 Data Mahasiswa

- Import snapshot dari SIAKAD: NIM + email + password
- Login dari nol, bukan SSO
- Akses dikontrol manual via `is_active` — tidak ada auto-suspend dari status akademik
- Re-import dilakukan per semester atau saat ada perubahan data massal
- Perubahan individual (DO, cuti, wisuda): admin update manual di profil user

### 4.8 Snapshot Historis

Saat surat di-generate final, seluruh nilai placeholder (termasuk data pejabat TTD) disimpan ke `data_placeholder` JSON di `surat_tercetak`. Arsip bisa dibaca ulang tanpa JOIN ke tabel lain, meski data pejabat/mahasiswa berubah di masa depan.

---

## 5. Fitur Phase 1 (MVP)

---

### Fitur 1 — Autentikasi & Manajemen Role

**Acceptance Criteria**:
- [ ] Login email + password
- [ ] Role: Super Admin, Admin Surat, Mahasiswa
- [ ] User nonaktif (`is_active = false`) tidak bisa login — redirect dengan pesan jelas
- [ ] Properti user: `is_active`, `role`, `unit_id`
- [ ] Super Admin bisa activate/deactivate user dan ubah role kapan saja
- [ ] Setiap login/logout tercatat di ActivityLog

---

### Fitur 2 — Konfigurasi Sistem

**Acceptance Criteria**:
- [ ] Setup: nama universitas, kode universitas, logo, alamat
- [ ] Manajemen `units`: nama unit, kode unit — meski Phase 1 hanya satu aktif
- [ ] Manajemen daftar pejabat: nama, jabatan, unit, NIP/NIDN, foto tanda tangan (upload PNG, simpan di storage private)
- [ ] Tahun akademik aktif
- [ ] Konfigurasi SMTP email
- [ ] Format nomor surat (teks helper panduan admin): `{urut}/{unit}/{kode_univ}/{bulan_romawi}/{tahun}`

---

### Fitur 3 — Master Template Surat

**Upload & Deteksi Placeholder**:
- [ ] Upload .docx, validasi tipe & ukuran (max 10MB)
- [ ] Deteksi otomatis semua `{{variabel}}` dari seluruh konten file (paragraf, tabel, header, footer, textbox)
- [ ] Cocokkan dengan Kamus Placeholder → tentukan `filled_by` default:
  - Kelompok profil & sistem → `filled_by = 'sistem'`
  - Kelompok waktu, nomor surat, TTD slot → `filled_by = 'admin'`
  - Placeholder bebas → `filled_by = 'mahasiswa'`
- [ ] Admin review tabel hasil deteksi: bisa override `filled_by`, tipe input, dan set `label_mahasiswa`

**Informasi & Konfigurasi**:
- [ ] Admin set: nama, kategori, unit, deskripsi, SLA (hari kerja), status (draft/aktif)
- [ ] Flag `is_permohonan_mandiri`: jika `true` → template muncul di list permohonan mahasiswa
- [ ] Template aktif yang sudah punya permohonan tidak bisa dihapus — hanya bisa dinonaktifkan
- [ ] Halaman "Panduan Placeholder" — daftar kamus placeholder + konvensi penamaan, bisa di-copy admin

**Setup Persyaratan (FindOrCreate)**:
- [ ] Section "Persyaratan" dengan searchable dropdown `ref_syarat_surat`
- [ ] Ketik nama → autocomplete; tidak ketemu → tombol "+ Buat Persyaratan Baru" (inline modal, tidak keluar halaman)
- [ ] Inline modal: nama, deskripsi, `template_file` (nullable — untuk didownload mahasiswa), tipe file diterima, ukuran max
- [ ] Setelah simpan: masuk master `ref_syarat_surat` DAN langsung ter-link ke template
- [ ] Per persyaratan yang di-link: toggle `is_required` dan drag reorder urutan

**Setup Data Tambahan (FindOrCreate)**:
- [ ] Section "Data Tambahan" — field yang diisi mahasiswa tapi tidak masuk ke isi surat (mis. no. telepon, alamat)
- [ ] Admin tambah field: label, tipe (text/date/number), is_required, helper_text
- [ ] `field_key` auto-generate dari label (bisa di-edit manual)
- [ ] Drag reorder urutan field
- [ ] Soft delete field — hard delete di-RESTRICT oleh FK jika sudah ada nilai dari permohonan

**Coba Template (Preview)**:
- [ ] Tombol "Coba Template" di halaman detail template → buka modal
- [ ] Modal: flat form satu input per placeholder (semua placeholder template), label dari `label_mahasiswa` atau nama teknis
- [ ] Tidak ada validasi required — mode percobaan
- [ ] Tombol "Generate & Download" → substitusi nilai ke .docx → download langsung ke browser
- [ ] Tidak disimpan ke DB — ephemeral, tidak ada entry arsip
- [ ] File diberi keterangan di header: `PREVIEW — [nama template] — [tanggal]`
- [ ] Output format: .docx (Phase 1)

---

### Fitur 4 — Master Persyaratan Surat

**Acceptance Criteria**:
- [ ] CRUD `ref_syarat_surat`: nama, deskripsi, `template_file` (nullable), tipe file diterima, ukuran max
- [ ] Persyaratan yang punya `template_file` → tombol "⬇ Download Template" di form permohonan mahasiswa
- [ ] Satu persyaratan bisa dipakai di banyak template (many-to-many via `syarat_surat`)
- [ ] Dari halaman detail persyaratan: tampilkan daftar template yang menggunakannya
- [ ] Persyaratan tidak bisa dihapus jika masih dipakai oleh template aktif

---

### Fitur 5 — Permohonan Surat Mandiri (Mahasiswa)

#### 5.1 Buat Permohonan

**Acceptance Criteria**:
- [ ] List template hanya menampilkan yang `is_permohonan_mandiri = true` dan `status = aktif`
- [ ] Setiap item: nama surat, deskripsi, estimasi SLA, jumlah persyaratan
- [ ] Form permohonan 4 lapisan:

  **Lapisan 1 — Data Otomatis (profil)**
  - [ ] Tampilkan read-only: nama, NIM, prodi, fakultas — tidak bisa diedit mahasiswa

  **Lapisan 2 — Isian Surat** (placeholder `filled_by = 'mahasiswa'`)
  - [ ] Render field sesuai tipe: text, date, number
  - [ ] Label dari `label_mahasiswa` di `template_placeholder_config`
  - [ ] Field required divalidasi sebelum submit
  - [ ] Nilai disimpan ke `permohonan_surat.isian_form` JSON

  **Lapisan 3 — Data Tambahan** (dari `template_data_tambahan_fields`)
  - [ ] Render field sesuai konfigurasi: text, date, number
  - [ ] Helper text ditampilkan di bawah field jika ada
  - [ ] Field required divalidasi sebelum submit
  - [ ] Nilai disimpan ke `permohonan_data_tambahan_values` (EAV)

  **Lapisan 4 — File Persyaratan**
  - [ ] List persyaratan dari `syarat_surat` — tampilkan required/opsional
  - [ ] Jika persyaratan punya `template_file`: tombol "⬇ Download Template"
  - [ ] Per item: tombol "Upload File" DAN "Pilih dari Dokumen Saya"
  - [ ] "Pilih dari Dokumen Saya": buka modal list `dokumen_mahasiswa`, bisa filter
  - [ ] Upload baru → otomatis tersimpan ke `dokumen_mahasiswa` sekaligus
  - [ ] Validasi tipe file dan ukuran max sesuai konfigurasi persyaratan
  - [ ] Persyaratan required tidak bisa submit jika belum diupload

- [ ] Tombol "Simpan Draft" — simpan tanpa submit
- [ ] Tombol "Ajukan Permohonan" — submit, status → `pending`
- [ ] Submit → email konfirmasi otomatis ke mahasiswa

#### 5.2 Dokumen Saya (Media Library)

**Acceptance Criteria**:
- [ ] List semua file yang pernah diupload: nama, kategori syarat, tanggal upload, ukuran, dipakai di N permohonan
- [ ] Upload dokumen baru langsung dari halaman ini (tanpa harus ada permohonan)
- [ ] Hapus dokumen — tidak bisa jika sedang dipakai di permohonan aktif (pending/diproses)
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
- [ ] Halaman detail: tampilkan semua 4 lapisan data, status history, alasan tolak/setujui

#### 5.4 Resubmit (Ajukan Ulang)

**Acceptance Criteria**:
- [ ] Klik "Ajukan Ulang" dari permohonan ditolak → buka form baru dengan data pre-fill
- [ ] Pre-fill: isian surat (dari `isian_form` lama), data tambahan (dari `permohonan_data_tambahan_values` lama)
- [ ] File persyaratan pre-select dari `dokumen_mahasiswa` yang sama
- [ ] Buat `permohonan_surat` baru dengan `parent_permohonan_id` = id permohonan yang ditolak
- [ ] Mahasiswa bisa ubah/tambah data sebelum submit ulang

---

### Fitur 6 — Review & Approval Permohonan (Admin)

**Acceptance Criteria**:
- [ ] Dashboard: total pending, mendekati deadline (kuning), overdue (merah)
- [ ] Tabel permohonan: filter by status, jenis surat, tanggal, nama/NIM
- [ ] Membuka halaman detail permohonan berstatus `pending` → **otomatis trigger** update status ke `diverifikasi` (idempotent)
- [ ] Halaman detail: tampilkan semua 4 lapisan data — profil, isian surat, data tambahan (key-value), file persyaratan (tombol download per file)
- [ ] Jika resubmit: tampilkan label "Pengajuan ulang dari #[id permohonan lama]" + link ke permohonan sebelumnya
- [ ] Tombol Setujui: wajib isi keterangan persetujuan + pilih pejabat dari dropdown — keterangan masuk log internal
- [ ] Tombol Tolak: wajib isi keterangan alasan penolakan — **keterangan ini ditampilkan ke mahasiswa**
- [ ] Notifikasi email otomatis ke mahasiswa setelah approve/reject
- [ ] Permohonan yang sudah disetujui tidak bisa di-unApprove kecuali Super Admin dengan alasan tercatat
- [ ] Setelah approve → tombol "Generate Surat" muncul (masuk Fitur 7 sub-flow A)
- [ ] Admin bisa upload `file_surat_scan` (scan TTD basah) → mahasiswa bisa download dari riwayat

---

### Fitur 7 — Generate & Cetak Surat

**Sub-flow A — Generate dari Permohonan (Kamar 1 → Kamar 2)**:
- [ ] Tombol "Generate Surat" hanya muncul di permohonan berstatus `disetujui`
- [ ] Form generate otomatis pre-fill dari data permohonan mahasiswa
- [ ] `surat_tercetak.permohonan_id` = id permohonan yang bersangkutan

**Sub-flow B — Generate Langsung Admin (Kamar 2 tanpa Kamar 1)**:
- [ ] Menu "Generate Langsung" di sidebar — admin pilih template dari daftar aktif
- [ ] Form generate identik dengan sub-flow A
- [ ] Placeholder data mahasiswa tidak pre-fill — diisi manual oleh admin
- [ ] `surat_tercetak.permohonan_id = NULL`

**Form Generate (berlaku kedua sub-flow)**:
- [ ] Semua placeholder template tampil — Smart Default pre-fill otomatis, admin bisa review dan override
- [ ] Field waktu: pre-fill hari ini, bisa di-override
- [ ] Field `{{nomor_surat}}`: pre-fill suggestion dari arsip terakhir template + tahun; AJAX warning jika duplikat
- [ ] Field TTD slot: dropdown pejabat per slot, preview nama + jabatan, warning jika tidak punya file TTD
- [ ] Field bebas: render sesuai tipe infer/konfigurasi
- [ ] Preview surat sebelum generate final
- [ ] Generate draft → .docx / PDF dengan header "DRAFT"
- [ ] Generate final → substitusi semua placeholder → simpan ke `data_placeholder` JSON → PDF bersih + DOCX
- [ ] QR code verifikasi digenerate dan dimasukkan ke surat (pojok bawah / footer)

---

### Fitur 8 — Arsip Surat Tercetak

**Acceptance Criteria**:
- [ ] Entry arsip: nomor surat, tanggal generate, user yang generate, nama pejabat approve, `data_placeholder` JSON snapshot, link file PDF & DOCX
- [ ] Data arsip tidak bisa di-UPDATE oleh siapapun
- [ ] Cetak ulang → entry baru, entry lama ditandai `status: digantikan`, ada `replaced_by_id`
- [ ] Pencarian: by nomor surat, nama, NIM, tanggal, jenis surat
- [ ] Export arsip ke Excel untuk periode tertentu
- [ ] Halaman verifikasi publik (tanpa login): jenis surat, status (valid/digantikan/dibatalkan), nama depan + inisial penerima, tanggal terbit

---

### Fitur 9 — Surat Masuk, Disposisi & Buku Agenda Masuk

**Alur utama**:
```
Surat fisik diterima → Admin scan → upload ke sistem → isi metadata
  → [Opsional] Tambah disposisi → tulis tujuan + instruksi → cetak lembar disposisi PDF
  → Pejabat tindaklanjuti offline
  → Admin update status disposisi + catat tindak lanjut di sistem
```

#### 9.1 Form Surat Masuk

**Acceptance Criteria**:
- [ ] Nomor agenda: auto-increment per tahun — tidak diinput admin, muncul sebagai label baca-saja setelah simpan
- [ ] Field wajib: tanggal terima, nomor surat asli, pengirim, perihal
- [ ] Field opsional: tanggal surat, kode klasifikasi, keterangan, lampiran (multi-file)
- [ ] File scan: upload wajib (PDF atau JPG/PNG)
- [ ] Soft delete
- [ ] Pencarian: by pengirim, perihal, nomor surat, tanggal terima, status disposisi

#### 9.2 Disposisi Surat Masuk

- Penerima disposisi: **teks bebas** (Phase 1) — tidak perlu FK ke akun pejabat
- Admin Surat yang mengelola dan update status

**Acceptance Criteria**:
- [ ] Satu surat masuk bisa punya lebih dari satu disposisi (one-to-many)
- [ ] Form disposisi: tujuan (text bebas), isi instruksi, sifat (`segera`/`biasa`/`rahasia`), batas waktu (opsional)
- [ ] Status: `belum_ditindaklanjuti` → `sudah_ditindaklanjuti`
- [ ] Saat update status: admin isi catatan tindak lanjut + `ditindaklanjuti_oleh` + `ditindaklanjuti_at` tercatat otomatis
- [ ] Tombol "Cetak Lembar Disposisi" → generate PDF untuk diserahkan fisik ke pejabat
- [ ] Counter di dashboard: "X disposisi belum ditindaklanjuti" + highlight yang melewati batas waktu
- [ ] Tidak ada chain disposisi — flat Phase 1

#### 9.3 Buku Agenda Surat Masuk

**Acceptance Criteria**:
- [ ] View tabel: No. Agenda, Tgl Terima, No. Surat, Tgl Surat, Pengirim, Perihal, Disposisi ke, Status Disposisi
- [ ] Filter: tahun, bulan, rentang tanggal, pengirim, status disposisi
- [ ] Export Excel dan PDF
- [ ] **Bukan tabel terpisah — view dari `surat_masuk`**

---

### Fitur 10 — Buku Agenda Surat Keluar (Kamar 3)

- 100% manual entry — tidak ada baris yang auto-masuk dari cetak template
- Tidak ada FK ke `surat_tercetak`

**Acceptance Criteria**:
- [ ] Nomor agenda: auto-increment per tahun — tidak diinput admin
- [ ] Field wajib: nomor surat, tanggal surat, tujuan, perihal
- [ ] Field opsional: kode klasifikasi, keterangan, berkas scan
- [ ] AJAX duplicate check pada `nomor_surat` sebelum simpan
- [ ] Edit dan soft delete
- [ ] Tabel: No. Agenda, No. Surat, Tgl Surat, Tujuan, Perihal, Keterangan
- [ ] Filter: tahun, rentang tanggal, tujuan, perihal
- [ ] Export Excel dan PDF

**Phase 2**: tracking ekspedisi — `tanggal_pengiriman`, `tanda_terima`, flag `sudah_dikirim`

---

### Fitur 11 — Notifikasi Email

**Acceptance Criteria**:
- [ ] Email terkirim pada: permohonan masuk, disetujui, ditolak (dengan alasan), surat siap
- [ ] Template email HTML (bukan plain text)
- [ ] SMTP via konfigurasi sistem
- [ ] Queue-based (retry otomatis jika SMTP down)

---

## 6. Fitur Phase 2

| Fitur | Deskripsi |
|---|---|
| **Approval Berjenjang** | Pejabat punya akun, approve dari portal (login atau magic link), multi-step per template |
| **Multi-Unit Admin** | Admin Unit di-scope ke unit tertentu; aktifkan `UnitScope` middleware |
| **Role Pejabat** | Dashboard minimalis untuk approve/tolak surat yang relevan per unit |
| **Disposisi ke Email Pejabat** | `pejabat_id FK` di disposisi + notifikasi email; konfirmasi via magic link |
| **Disposisi Berjenjang** | Disposisi bisa diteruskan dari satu pejabat ke pejabat lain |
| **QR + Portal Verifikasi Publik** | Halaman web tanpa login untuk verifikasi keaslian surat (QR sudah di-generate Phase 1) |
| **Notifikasi WhatsApp** | Via API (Fonnte / Wa.me) |
| **Bulk Printing** | Admin upload Excel daftar penerima, generate surat massal via Queue |
| **Watermark Nama Penerima** | PDF download mengandung watermark nama + NIM mahasiswa per halaman |
| **Export Laporan** | Statistik volume surat, permohonan per periode, per jenis, per status |
| **Cetak Bukti Permohonan** | Export PDF riwayat satu permohonan untuk mahasiswa |

---

## 7. Masa Depan (Phase 3+)

- **TTE Tersertifikasi** via BSrE BSSN — sah secara hukum UU ITE
- **Integrasi SIAKAD realtime** — auto-fill data mahasiswa dari API (eliminasi risiko snapshot stale)
- **Generator Sertifikat** — template landscape, batch untuk 1000+ peserta
- **e-Meterai** — untuk surat yang memerlukan meterai secara hukum
- **Mobile App / PWA** — notifikasi push, tracking status
- **API Publik** — integrasi portal mahasiswa dan sistem kampus lain
- **OCR Surat Masuk** — ekstrak metadata otomatis dari scan surat fisik
- **Dashboard Analytics** — grafik tren, statistik volume, rata-rata processing time

---

## 8. Arsitektur Teknis

### Tech Stack

| Komponen | Pilihan |
|---|---|
| Backend | PHP 8.1+, Laravel 10/11 (LTS) |
| Database | MySQL 8.0+ |
| Template Engine | PHPOffice/PHPWord (buat wrapper untuk normalize ke `{{VAR}}`) |
| PDF Generator | MPDF (lebih baik dari DomPDF untuk tabel kompleks + karakter Indonesia) |
| RBAC | Spatie Permission |
| Audit Trail | Spatie ActivityLog |
| Storage | Laravel Storage — lokal, opsional S3/Minio |
| Queue | Laravel Queue (database driver untuk awal) |
| QR Code | SimpleSoftwareIO/simple-qrcode |
| UI | AdminLTE (Bootstrap 3) |

### Komponen Reusable

**`TemplateSubstitutionService` (backend)**

Satu service yang menerima `template_id` + `array [placeholder => value]` → return file stream .docx. Dipakai di tiga konteks:

| Konteks | Simpan ke DB? | Tambahan |
|---|---|---|
| Preview admin (Fitur 3) | Tidak — ephemeral | Inject header "PREVIEW" ke dokumen |
| Generate dari permohonan (Fitur 7 sub-flow A) | Ya → `surat_tercetak` | Pre-fill dari `isian_form` JSON |
| Generate langsung admin (Fitur 7 sub-flow B) | Ya → `surat_tercetak` | `permohonan_id = NULL` |

**Blade partial `components.dynamic-placeholder-form`**

Menerima array field config → render input dengan label, helper text, required marker. Dipakai di tiga tempat:

| Tempat | Field source | Konteks |
|---|---|---|
| Modal preview (Fitur 3) | Semua placeholder template | Admin test, tidak ada validasi strict |
| Form permohonan mahasiswa — Lapisan 2 (Fitur 5) | `filled_by = 'mahasiswa'` | Nilai ke `isian_form` |
| Form generate surat admin (Fitur 7) | `filled_by = 'admin'` | Admin isi sisa placeholder |

Perbedaan konteks dikontrol lewat parameter partial (`$readOnly`, `$strict`, `$showHelperText`) — bukan partial berbeda.

### Keamanan File Tanda Tangan

- Simpan di `storage/private/signatures/` — tidak di folder `public/`
- Akses hanya via controller: `Gate::authorize('view-ttd', $pejabat)`
- Setiap generate yang menggunakan file TTD dicatat di ActivityLog: user, pejabat, surat, timestamp

### QR Code & Verifikasi

- Generate saat `surat_tercetak` dibuat
- Hash: `hash_hmac('sha256', $surat->id . $surat->nomor_surat, config('app.key'))`
- URL: `https://surat.kampus.ac.id/verify/{qr_hash}`
- Halaman publik (tanpa login): jenis surat, status (valid/digantikan/dibatalkan), nama depan + inisial, tanggal terbit
- Status "digantikan": tampilkan keterangan + nomor surat penggantinya

### UI Style (Konsisten dengan Ciengang)

- Template: **AdminLTE** (Bootstrap 3)
- Tombol: `btn btn-flat btn-sm`
- Tabel: `table table-bordered table-striped table-hover` dengan `thead.bg-gray`
- Warna: `bg-olive` (positif/tambah), `btn-warning` (edit), `bg-maroon` (hapus/tolak), `bg-aqua` (info)
- Icon: **Font Awesome**
- DataTables untuk semua tabel: pagination + search + sort

---

## 9. Database Schema

```sql
-- ─────────────────────────────────────────────
-- CORE: Units & Users
-- ─────────────────────────────────────────────

units
  id, nama, kode, parent_id NULL, is_active

users
  id, nama, email, password,
  role ENUM('super_admin','admin_surat','mahasiswa'),
  unit_id FK units,
  nim VARCHAR(20) NULL,
  prodi VARCHAR(100) NULL,
  fakultas VARCHAR(100) NULL,
  is_active BOOLEAN DEFAULT true,
  created_at, deleted_at

pejabat
  id, unit_id FK units, nama, nip_nidn, jabatan,
  file_ttd_path VARCHAR(255) NULL,  -- storage private
  is_active BOOLEAN DEFAULT true,
  user_id BIGINT FK users NULL,     -- Phase 2: diisi jika punya akun
  created_at

-- ─────────────────────────────────────────────
-- TEMPLATE & PLACEHOLDER
-- ─────────────────────────────────────────────

templates
  id, unit_id FK units, nama, kategori, deskripsi,
  file_path VARCHAR(255),
  sla_hari_kerja TINYINT NULL,
  is_permohonan_mandiri BOOLEAN DEFAULT false,
  status ENUM('draft','aktif','nonaktif'),
  created_by FK users,
  created_at, deleted_at

placeholder_definitions
  id, name VARCHAR(100),
  kelompok ENUM('profil','waktu','sistem','counter','ttd'),
  input_type ENUM('text','date','number','textarea','file','image'),
  source VARCHAR(100) NULL,
  is_overridable BOOLEAN DEFAULT true

template_placeholder_config
  id, template_id FK templates,
  placeholder_name VARCHAR(100),
  label_mahasiswa VARCHAR(255),
  tipe_input ENUM('text','date','number','textarea'),
  filled_by ENUM('sistem','mahasiswa','admin'),
  is_required BOOLEAN DEFAULT true,
  urutan TINYINT

-- ─────────────────────────────────────────────
-- PERSYARATAN
-- ─────────────────────────────────────────────

ref_syarat_surat
  id, nama VARCHAR(255), deskripsi TEXT NULL,
  template_file VARCHAR(255) NULL,
  accepted_types VARCHAR(100),
  max_size_mb TINYINT DEFAULT 5

syarat_surat
  id, template_id FK templates,
  syarat_id FK ref_syarat_surat,
  is_required BOOLEAN DEFAULT true,
  urutan TINYINT

dokumen_mahasiswa
  id, mahasiswa_id FK users,
  nama VARCHAR(255),
  syarat_id FK ref_syarat_surat NULL,
  filename VARCHAR(255), path VARCHAR(255), file_size INT,
  created_at, deleted_at

-- ─────────────────────────────────────────────
-- PERMOHONAN (Kamar 1)
-- ─────────────────────────────────────────────

permohonan_surat
  id,
  parent_permohonan_id BIGINT FK permohonan_surat NULL,
  mahasiswa_id FK users,
  template_id FK templates,
  unit_id FK units,
  status ENUM('draft','pending','diverifikasi','disetujui','ditolak','dibatalkan','selesai'),
  isian_form JSON NULL,
  catatan_penolakan TEXT NULL,
  approved_by FK users NULL,
  pejabat_id FK pejabat NULL,
  catatan_approval TEXT NULL,
  approved_at TIMESTAMP NULL,
  file_surat_scan VARCHAR(255) NULL,
  created_at, deleted_at

template_data_tambahan_fields
  id, template_id FK templates,
  label VARCHAR(255), field_key VARCHAR(100),
  tipe_input ENUM('text','date','number'),
  is_required BOOLEAN DEFAULT true,
  helper_text VARCHAR(255) NULL,
  urutan TINYINT DEFAULT 0,
  deleted_at TIMESTAMP NULL   -- soft delete; hard delete di-RESTRICT jika ada nilai

permohonan_data_tambahan_values
  id,
  permohonan_id FK permohonan_surat ON DELETE CASCADE,
  field_id FK template_data_tambahan_fields ON DELETE RESTRICT,
  nilai TEXT,
  created_at

permohonan_syarat
  id,
  permohonan_id FK permohonan_surat ON DELETE CASCADE,
  syarat_id FK ref_syarat_surat,
  dokumen_id FK dokumen_mahasiswa NULL,
  filename VARCHAR(255),
  path VARCHAR(255),
  uploaded_at TIMESTAMP

-- ─────────────────────────────────────────────
-- ARSIP SURAT TERCETAK (Kamar 2)
-- ─────────────────────────────────────────────

surat_tercetak
  id,
  permohonan_id FK permohonan_surat NULL,
  template_id FK templates,
  unit_id FK units,
  nomor_surat VARCHAR(100),
  digenerate_oleh FK users,
  digenerate_at TIMESTAMP,
  data_placeholder JSON,
  file_pdf_path VARCHAR(255),
  file_docx_path VARCHAR(255),
  qr_hash VARCHAR(64),
  status ENUM('aktif','digantikan','dibatalkan'),
  replaced_by_id FK surat_tercetak NULL,
  replaced_reason TEXT NULL,
  created_at

  UNIQUE KEY (nomor_surat, unit_id)

surat_penandatangan
  id, surat_tercetak_id FK,
  urutan TINYINT,
  label VARCHAR(100) NULL,
  pejabat_id FK pejabat NULL,
  nama_snapshot VARCHAR(255),
  jabatan_snapshot VARCHAR(255),
  nip_snapshot VARCHAR(50) NULL,
  file_ttd_path VARCHAR(255) NULL

-- ─────────────────────────────────────────────
-- SURAT MASUK & DISPOSISI
-- ─────────────────────────────────────────────

surat_masuk
  id,
  nomor_agenda SMALLINT UNSIGNED,
  tahun_agenda SMALLINT,
  nomor_surat VARCHAR(50),
  tanggal_surat DATE NULL,
  tanggal_terima DATE,
  pengirim VARCHAR(150),
  perihal VARCHAR(255),
  kode_klasifikasi VARCHAR(20) NULL,
  keterangan TEXT NULL,
  berkas_scan VARCHAR(255),
  dicatat_oleh FK users,
  unit_id FK units,
  created_at, deleted_at

disposisi_surat_masuk
  id,
  surat_masuk_id FK surat_masuk ON DELETE CASCADE,
  tujuan VARCHAR(200),
  isi_instruksi TEXT,
  sifat ENUM('segera','biasa','rahasia') DEFAULT 'biasa',
  batas_waktu DATE NULL,
  status ENUM('belum_ditindaklanjuti','sudah_ditindaklanjuti') DEFAULT 'belum_ditindaklanjuti',
  catatan_tindaklanjut TEXT NULL,
  dicatat_oleh FK users,
  ditindaklanjuti_oleh FK users NULL,
  ditindaklanjuti_at TIMESTAMP NULL,
  pejabat_id FK pejabat NULL,  -- Phase 2: isi jika dipilih dari master
  created_at

lampiran_surat_masuk
  id, surat_masuk_id FK surat_masuk,
  filename VARCHAR(255), path VARCHAR(255),
  uploaded_by FK users, created_at

-- ─────────────────────────────────────────────
-- BUKU AGENDA SURAT KELUAR (Kamar 3)
-- ─────────────────────────────────────────────

surat_keluar
  id,
  nomor_agenda SMALLINT UNSIGNED,
  tahun_agenda SMALLINT,
  nomor_surat VARCHAR(50),
  kode_klasifikasi VARCHAR(20) NULL,
  tanggal_surat DATE,
  tanggal_catat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  tujuan VARCHAR(150),
  perihal VARCHAR(255),
  keterangan VARCHAR(500) NULL,
  berkas_scan VARCHAR(255) NULL,
  dicatat_oleh FK users,
  unit_id FK units,
  -- Phase 2: tracking ekspedisi
  -- ekspedisi        TINYINT(1) DEFAULT 0
  -- tanggal_pengiriman DATE NULL
  -- tanda_terima     VARCHAR(200) NULL
  created_at, deleted_at
```

---

*Dokumen ini adalah perancangan aktif — diperbarui seiring konfirmasi dari klien dan keputusan implementasi.*
