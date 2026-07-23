# PRD — Sistem Surat Kampus Universitas Nusa Putra

| | |
|---|---|
| **Produk** | Sistem Surat Kampus (Sistem Informasi Persuratan Terpusat) |
| **Organisasi** | Universitas Nusa Putra |
| **Versi Dokumen** | 1.0 (Draft) |
| **Tanggal** | 19 Juli 2026 |
| **Status** | Draft untuk review pemangku kepentingan |
| **Sumber Acuan** | `perancangan-murni.md` (utama), `perancangan-kasar.md` (pendukung) |

**Legenda Penilaian**:
`[DISIMPULKAN]` disintesis dari konteks sumber · `[PERLU VALIDASI]` butuh konfirmasi pemangku kepentingan · `[ASUMSI]` asumsi kerja yang perlu diuji · `[KESALAHAN KRITIS]` informasi penting yang hilang

---

## 1. Ringkasan Eksekutif

### Visi Produk

Sistem Surat Kampus adalah platform persuratan terpusat yang menggantikan pengelolaan template surat yang tersebar di komputer lokal staf dengan satu sumber kebenaran: template terstandarisasi, layanan permohonan mandiri untuk mahasiswa, arsip digital yang tidak dapat diubah, serta pencatatan surat masuk/keluar. Tujuannya menghilangkan ketergantungan pada individu tertentu, menstandarkan format dan penomoran surat, serta mempercepat layanan surat ke mahasiswa.

### Metrik Keberhasilan Utama

> Metrik berikut belum tercantum eksplisit di dokumen sumber. Angka target adalah usulan awal yang **wajib divalidasi** dengan pemangku kepentingan kampus. `[PERLU VALIDASI]`

| Metrik | Baseline Saat Ini | Target Usulan |
|---|---|---|
| Waktu rata-rata penerbitan surat layanan mahasiswa | Manual, tidak terukur | ≤ SLA per jenis surat (mis. 1-3 hari kerja) |
| Persentase surat terarsip digital | ~0% (file lokal terpencar) | 100% surat yang digenerate via sistem |
| Ketergantungan pada staf tunggal | Tinggi (proses berhenti bila staf absen) | Nol (semua template & arsip dapat diakses admin berwenang) |
| Insiden nomor surat duplikat/hilang | Tidak terlacak | 0 duplikat (dijamin constraint sistem) |
| Adopsi permohonan mandiri mahasiswa | 0% (semua tatap muka) | `[PERLU VALIDASI]` — mis. ≥60% permohonan via sistem dalam 6 bulan |

---

## 2. Pernyataan Masalah & Peluang

### 2.1 Masalah Utama

Sesuai `perancangan-murni.md` §1 dan `perancangan-kasar.md` (Konteks Utama):

1. **Template terdesentralisasi** — file Word tersebar di komputer masing-masing staf, tanpa standar format.
2. **Tidak ada arsip terpusat** — surat yang sudah dicetak tidak terlacak; sulit dicari atau dicetak ulang.
3. **Penomoran tidak terkontrol** — tidak ada cara mengetahui nomor surat terakhir; berisiko duplikat.
4. **Ketergantungan pada individu** — jika staf yang biasa mengelola surat absen, proses layanan berhenti.
5. **Antrian fisik** — mahasiswa harus datang langsung untuk setiap permohonan surat.

### 2.2 Kesenjangan Solusi Saat Ini

Proses manual berbasis Word + arsip fisik tidak menyediakan: standarisasi template, kontrol penomoran otomatis, arsip yang dapat dicari, jalur layanan mandiri, maupun jejak audit. Tidak ada sistem yang menghubungkan template → permohonan → penerbitan → arsip dalam satu alur.

### 2.3 Peluang

Membangun sistem informasi persuratan yang **mengutamakan manajemen data** dan **scalable** — dirancang unit-aware dan dapat di-clone untuk kampus lain sejak awal, sehingga investasi Phase 1 menjadi fondasi untuk perluasan multi-unit dan multi-kampus di masa depan.

---

## 3. Tujuan Produk & Metrik Keberhasilan

### 3.1 Tujuan Bisnis Utama

| # | Tujuan | Indikator Keberhasilan |
|---|---|---|
| G1 | Sentralisasi template surat | Semua jenis surat aktif dikelola dari satu master, dapat dicetak admin berwenang manapun |
| G2 | Layanan mandiri mahasiswa | Mahasiswa dapat mengajukan, memantau status, menerima surat tanpa antrian fisik |
| G3 | Arsip immutable & tertelusur | Setiap surat tercetak terarsip permanen, dapat dicari & dicetak ulang |
| G4 | Kontrol penomoran surat | Tidak ada duplikat nomor; saran nomor otomatis dari arsip |
| G5 | Digitalisasi surat masuk/keluar | Surat fisik ter-scan & terarsip dengan buku agenda digital |
| G6 | Kesiapan skalabilitas | Arsitektur unit-aware & dapat di-clone tanpa refactor besar |

### 3.2 KPI & Target Adopsi

Lihat tabel Metrik di §1. Semua target kuantitatif berstatus `[PERLU VALIDASI]` karena dokumen sumber menetapkan arah fitur, bukan target numerik. **Rekomendasi**: tetapkan baseline pada bulan pertama go-live, lalu tinjau target per kuartal.

---

## 4. Pengguna Target & Persona

### 4.1 Segmen Pengguna (Phase 1)

Sesuai `perancangan-murni.md` §3, tiga role di Phase 1:

**Persona 1 — Super Admin (IT / Koordinator Sistem)**
- **Kebutuhan**: mengontrol konfigurasi sistem, mengelola user & role, mengelola kamus placeholder, membatalkan surat final, memulihkan data.
- **Perjalanan**: setup awal sistem → kelola user & master data → pengawasan & audit.
- **Frustrasi yang diatasi**: tidak ada kontrol terpusat atas hak akses.

**Persona 2 — Admin Surat (Staf BAA / Tata Usaha)**
- **Kebutuhan**: membuat & mengelola template, memproses permohonan, menyetujui/menolak, generate & cetak surat, mengelola surat masuk/keluar & disposisi.
- **Perjalanan**: buat template sekali → proses permohonan harian → generate surat → arsip otomatis.
- **Frustrasi yang diatasi**: edit manual Word berulang, kehilangan jejak nomor & arsip.

**Persona 3 — Mahasiswa**
- **Kebutuhan**: mengajukan permohonan online, mengunggah persyaratan, memantau status, mengunduh/mengambil surat jadi.
- **Perjalanan**: pilih jenis surat → isi form & unggah syarat → tunggu proses → unduh atau ambil di kampus.
- **Frustrasi yang diatasi**: antrian fisik, ketidakpastian status & estimasi waktu.

### 4.2 Persona Masa Depan (Phase 2)

- **Admin Unit** — Admin Surat yang di-scope ke unit tertentu (fakultas/prodi). `[DISIMPULKAN]`
- **Pejabat (Kaprodi/Dekan/WR)** — menyetujui permohonan lewat portal minimalis atau magic link, tanpa akun operasional penuh. `[DISIMPULKAN]`

---

## 5. Persyaratan Produk

### 5.1 Fitur Inti — Wajib Ada (MVP Phase 1)

Diturunkan dari `perancangan-murni.md` Fitur 1-13. Semua adalah *must-have* untuk go-live.

#### F1 — Autentikasi & Manajemen Role
- Login email + password (kredensial dari import SIAKAD).
- Tiga role dikelola via RBAC: Super Admin, Admin Surat, Mahasiswa.
- User nonaktif tidak dapat login; Super Admin dapat mengaktifkan/menonaktifkan & mengubah role.
- Setiap login/logout tercatat di jejak audit.
- **User Story**: *Sebagai Super Admin, saya menonaktifkan akun mahasiswa yang sudah lulus agar tidak lagi bisa mengajukan surat.*

#### F2 — Konfigurasi Sistem
- Profil kampus (nama, kode, logo, alamat, tahun akademik).
- Manajemen unit penerbit.
- Manajemen daftar pejabat (nama, jabatan, unit, NIP/NIDN, unggah gambar tanda tangan ke penyimpanan privat).
- Konfigurasi SMTP email; helper format nomor surat.
- **User Story**: *Sebagai Admin, saya menyiapkan daftar pejabat penandatangan sekali agar bisa dipilih saat generate surat.*

#### F3 — Master Template Surat
- Unggah `.docx`; sistem mendeteksi otomatis semua placeholder `{{variabel}}` dari seluruh bagian dokumen.
- Sistem mengklasifikasikan tiap placeholder (siapa pengisi: sistem/admin/mahasiswa; tipe input) berdasarkan Kamus Placeholder; admin dapat mengoreksi hasil deteksi.
- Admin menetapkan metadata: nama, kategori (dari master), unit, deskripsi, SLA (hari kerja), tipe pemohon (Mahasiswa/Umum), status, dan flag ketersediaan permohonan mandiri.
- Setup persyaratan & data tambahan secara inline (FindOrCreate).
- Fitur "Coba Template" untuk uji coba tanpa menyimpan ke arsip; halaman Panduan Placeholder sebagai referensi.
- **User Story**: *Sebagai Admin, saya mengunggah file Word yang sudah saya buat dan sistem otomatis mengenali kolom yang perlu diisi — tidak perlu mendefinisikan field satu per satu.*

#### F4 — Master Persyaratan Surat
- CRUD daftar persyaratan reusable (mis. Fotokopi KHS), dengan file template opsional untuk diunduh mahasiswa.
- Satu persyaratan dapat dipakai lintas template; tidak dapat dihapus jika masih terpakai.

#### F5 — Permohonan Surat Mandiri (Mahasiswa)
- Daftar jenis surat yang tersedia untuk permohonan mandiri, dengan estimasi SLA.
- Form permohonan 4 lapisan: data profil (read-only), isian surat, data tambahan, unggah persyaratan.
- Media library "Dokumen Saya" agar file dapat dipakai ulang di permohonan berikutnya.
- Riwayat permohonan dengan status berwarna; aksi Edit/Batalkan (saat pending), Ajukan Ulang (saat ditolak), Unduh (saat selesai & diizinkan).
- **User Story**: *Sebagai Mahasiswa, saya mengajukan Surat Keterangan Aktif dari HP, mengunggah KTM, lalu memantau statusnya tanpa datang ke kampus.*

#### F6 — Review & Approval Permohonan (Admin)
- Dashboard: jumlah pending, mendekati deadline, dan overdue.
- Membuka detail permohonan pending otomatis mengubah status ke "diverifikasi".
- Setujui (wajib pilih pejabat + keterangan, model *proxy approval*) atau Tolak (wajib alasan, tampil ke mahasiswa).
- Perubahan status langsung tercermin di riwayat mahasiswa + notifikasi email.
- **User Story**: *Sebagai Admin, saya menolak permohonan dengan alasan "KHS buram" dan mahasiswa langsung melihat alasannya untuk mengajukan ulang.*

#### F7 — Generate & Cetak Surat
- Dua jalur: (A) dari permohonan mahasiswa yang disetujui, (B) generate langsung oleh admin tanpa permohonan.
- Untuk template bertipe "Mahasiswa" di jalur B, disediakan pencarian mahasiswa untuk auto-isi data profil.
- Form generate dengan smart default (tanggal, saran nomor surat, pemilihan pejabat penandatangan per slot).
- Pilihan **Metode Pengambilan** (Unduh / Ambil di Kampus) dengan saran otomatis berdasarkan kelengkapan tanda tangan.
- Output final: **DOCX wajib** (dijamin); **PDF opsional** — hanya dihasilkan bila LibreOffice tersedia di server (graceful, pola OpenSID — lihat ARCHITECTURE.md §2.1). Phase 1 dapat berjalan DOCX-only.
- QR code verifikasi disisipkan pada output PDF (jika PDF dibuat); bila DOCX-only, verifikasi tetap via nomor surat.
- **User Story**: *Sebagai Admin, saya generate Nota Dinas internal langsung dari template tanpa harus ada permohonan mahasiswa.*

#### F8 — Arsip Surat Tercetak
- Setiap surat final tersimpan sebagai arsip yang **tidak dapat diubah** (immutable), lengkap dengan snapshot data.
- Cetak ulang membuat entri baru; entri lama ditandai "digantikan".
- Pencarian & export. QR code verifikasi disisipkan di setiap surat (dibuat Phase 1); **halaman publik yang membacanya ditunda ke Phase 2** (lihat §5.2, keputusan D-002 di `.ai-context/DECISIONS.md`).

#### F9 — Surat Masuk, Disposisi & Buku Agenda Masuk
- Pencatatan surat fisik masuk (unggah scan + metadata), nomor agenda otomatis per tahun.
- Disposisi (satu surat bisa banyak disposisi) dengan tujuan teks bebas & status tindak lanjut; cetak lembar disposisi.
- Buku agenda sebagai tampilan tabel dengan filter & export.

#### F10 — Buku Agenda Surat Keluar
- Pencatatan manual korespondensi keluar (nomor agenda otomatis, unggah scan opsional), dengan filter & export.

#### F11 — Notifikasi Email
- Email otomatis pada peristiwa kunci (permohonan masuk, disetujui, ditolak, surat siap), dibedakan sesuai metode pengambilan; berbasis antrian dengan retry.

#### F12 — Master Kategori Surat
- CRUD kategori template, dipakai sebagai dropdown & filter; tidak dapat dihapus jika terpakai.

#### F13 — Master Kamus Placeholder (Super Admin)
- CRUD kamus placeholder yang menentukan perilaku deteksi template; dapat diperluas tanpa deploy ulang kode.

### 5.2 Fitur Tambahan — Sebaiknya Ada (Phase 2)

Sesuai `perancangan-murni.md` §6:

- Approval berjenjang (pejabat punya akun / magic link, multi-step per template).
- Multi-Unit Admin dengan pembatasan data per unit.
- Role Pejabat dengan dashboard minimalis.
- Disposisi ke email pejabat & disposisi berjenjang.
- Portal verifikasi publik via QR (QR sudah digenerate sejak Phase 1).
- Notifikasi WhatsApp; bulk printing; watermark nama penerima; export laporan statistik; cetak bukti permohonan.

### 5.3 Pertimbangan Masa Depan — Bisa Ada (Phase 3+)

Sesuai `perancangan-murni.md` §7:

- Tanda Tangan Elektronik Tersertifikasi (TTE BSrE) yang sah secara hukum.
- Integrasi SIAKAD realtime (mengeliminasi risiko data snapshot usang).
- Generator sertifikat massal, e-Meterai, Mobile App/PWA, API publik, OCR surat masuk, dashboard analitik.

---

## 6. User Story & Kasus Penggunaan Utama

### 6.1 Alur Utama — Permohonan sampai Terbit (Jalur Mahasiswa)

```
Mahasiswa pilih jenis surat → isi form 4 lapisan + unggah syarat → Ajukan
   → Admin buka detail (status: diverifikasi otomatis) → verifikasi kelengkapan
   → Setujui (pilih pejabat + keterangan) → Generate Surat
   → pilih penandatangan + metode pengambilan → Generate Final
   → Arsip tersimpan + email ke mahasiswa
   → Mahasiswa: Unduh (jika diizinkan) ATAU Ambil di Kampus
```

### 6.2 Alur Alternatif — Generate Langsung Admin (Tanpa Permohonan)

Untuk surat internal (Nota Dinas, SK, Undangan) yang tidak berasal dari permohonan mahasiswa: admin memilih template → (jika bertipe Mahasiswa, cari mahasiswa dulu) → isi placeholder manual → generate final → arsip. `[DISIMPULKAN dari F7 sub-flow B]`

### 6.3 Skenario Kesalahan & Kasus Khusus

| Skenario | Perilaku Sistem |
|---|---|
| Mahasiswa unggah syarat wajib belum lengkap | Submit diblokir hingga lengkap |
| Nomor surat yang diketik admin sudah dipakai | Peringatan realtime sebelum simpan + jaminan keunikan sistem |
| Pejabat penandatangan tidak punya gambar TTD | Peringatan; surat digenerate untuk tanda tangan basah, disarankan "Ambil di Kampus" |
| Permohonan ditolak | Mahasiswa dapat "Ajukan Ulang" dengan data ter-prefill |
| Ada kesalahan pada surat yang sudah tercetak | Cetak ulang membuat arsip baru; arsip lama ditandai "digantikan" — **catatan**: mekanisme identifikasi surat terdampak akibat template salah masih perlu dirancang (lihat §12) |

---

## 7. Pertimbangan Teknis

> Sesuai batasan penulisan PRD, bagian ini menyatakan **persyaratan** arsitektur, bukan skema/implementasi. Detail skema ada di `perancangan-murni.md` §9.

### 7.1 Persyaratan Arsitektur
- **Single-tenant, unit-aware sejak awal** — satu instalasi per kampus, tapi berkonsep unit agar dapat diperluas ke multi-unit (Phase 2) tanpa refactor besar.
- **Arsitektur data "3 Kamar"** untuk surat keluar: Permohonan → Arsip Surat Tercetak → Buku Agenda Keluar, sengaja tidak saling terkait FK karena beda tujuan.
- **Jejak audit wajib** di semua tabel utama (siapa membuat/menyetujui/mencatat) + lapisan log aktivitas.
- **Manajemen role** menggunakan library RBAC standar (Spatie Laravel Permission), bukan kolom role manual — agar siap untuk model "teams" per unit di Phase 2.
- **Standar halaman list/index (lintas-fitur)** — semua daftar data (template, permohonan, arsip, buku agenda, user, master, riwayat) **wajib** mendukung *advanced filter* (kategori, status, unit, rentang tanggal, dll) + pencarian + sort + pagination, lewat pola reusable yang seragam. Detail teknis: ARCHITECTURE §17.
- **Pengembangan per fitur (vertical slice)** — tiap fitur/sub-fitur dibangun tuntas end-to-end (migration→model→route→controller→service→view→test) sebagai satu unit fokus, mengikuti urutan dependensi antar-modul. Detail: ARCHITECTURE §18.

### 7.2 Integrasi Pihak Ketiga
- **SIAKAD** — import snapshot data mahasiswa (NIM, email, password) secara berkala; **bukan** realtime di Phase 1. `[DISIMPULKAN §4.7]`
- **SMTP** — pengiriman email notifikasi.
- **Mesin dokumen** — engine substitusi `.docx` (PHPWord); konversi PDF **opsional** via LibreOffice (jika tersedia); QR code generator.

### 7.3 Persyaratan Kinerja
> Tidak ada angka kinerja eksplisit di sumber. `[KESALAHAN KRITIS — perlu ditetapkan]`
- Target waktu generate surat, ukuran maks file unggah (template disebut 10MB), dan volume permohonan bersamaan **perlu ditetapkan** bersama pemangku kepentingan.

### 7.4 Keamanan & Kepatuhan
- File tanda tangan disimpan di penyimpanan privat, akses hanya via kontrol otorisasi, setiap penggunaan tercatat.
- Nama file unggahan diacak untuk mencegah tebakan URL. `[DISIMPULKAN dari pola OpenSID]`
- Verifikasi keaslian surat via nomor unik + QR (portal publik Phase 2).
- **Kepatuhan hukum**: TTE tersertifikasi (UU ITE) baru di Phase 3 — sampai saat itu, keabsahan bertumpu pada tanda tangan basah/semi-formal. `[PERLU VALIDASI dari sisi hukum kampus]`

---

## 8. Model Bisnis & Monetisasi

Sistem ini adalah **perangkat internal (internal tooling)** untuk operasional kampus, **bukan produk komersial berpendapatan**. Tidak ada model monetisasi langsung.

- **Nilai bisnis** berasal dari efisiensi operasional (pengurangan waktu layanan, eliminasi ketergantungan staf, standarisasi).
- **Implikasi biaya**: hosting, pemeliharaan, pengembangan Phase 2/3. `[DISIMPULKAN]`
- **Potensi masa depan**: karena dirancang dapat di-clone untuk kampus lain, ada peluang menjadi produk yang dapat direplikasi/dilisensikan ke institusi lain. `[ASUMSI — belum dinyatakan sebagai tujuan bisnis eksplisit]`

---

## 9. Asumsi & Hipotesis

Diambil dari bagian "Asumsi Sementara" `perancangan-kasar.md` dan keputusan `perancangan-murni.md`:

| # | Asumsi | Status | Cara Validasi |
|---|---|---|---|
| A1 | Template dibuat dalam `.docx` dengan placeholder `{{snake_case}}` | ✅ Terkonfirmasi di sumber | — |
| A2 | Data mahasiswa cukup dari snapshot berkala SIAKAD (bukan realtime) | `[ASUMSI]` | Uji kasus DO/cuti/wisuda; ukur frekuensi data usang |
| A3 | Tanda tangan basah/PNG memadai secara administratif Phase 1 | `[PERLU VALIDASI]` | Konfirmasi ke bagian hukum/akademik kampus |
| A4 | Model *proxy approval* (admin mewakili pejabat) dapat diterima | `[PERLU VALIDASI]` | Konfirmasi ke pimpinan; siapkan SOP bukti persetujuan |
| A5 | Nomor surat diisi manual admin dengan bantuan saran | ✅ Terkonfirmasi (Mode A) | — |
| A6 | Mahasiswa memiliki email valid dari import untuk login & notifikasi | `[ASUMSI]` | Verifikasi kualitas data email SIAKAD |
| A7 | Adopsi mahasiswa terhadap layanan mandiri akan signifikan | `[HIPOTESIS]` | Pantau rasio permohonan online vs tatap muka pasca go-live |

**Hipotesis Utama**: *Jika mahasiswa diberi jalur permohonan mandiri dengan estimasi SLA yang jelas, maka antrian fisik dan beban admin turun signifikan tanpa menurunkan kualitas verifikasi.*

---

## 10. Ketergantungan & Batasan

### 10.1 Ketergantungan
- **Data SIAKAD** — kualitas & ketersediaan import menentukan akurasi data mahasiswa.
- **Infrastruktur email (SMTP)** — notifikasi bergantung pada konfigurasi yang benar.
- **Master data awal** — kategori, kamus placeholder, unit, dan daftar pejabat harus disiapkan sebelum operasional.
- **Engine konversi dokumen** — DOCX selalu tersedia (PHPWord, PHP murni); PDF **opsional**, bergantung ketersediaan LibreOffice di server (graceful fallback ke DOCX bila absen).

### 10.2 Batasan
- **Satu kampus per instalasi** (single-tenant) di Phase 1.
- **Tanpa TTE tersertifikasi** hingga Phase 3 — keabsahan hukum digital terbatas.
- **Snapshot data** — bukan integrasi realtime; ada risiko data usang yang dimitigasi manual.
- **Batasan sumber daya & waktu** — `[KESALAHAN KRITIS]` timeline, anggaran, dan ukuran tim tidak tercantum di sumber; perlu ditetapkan.

---

## 11. Di Luar Cakupan (Phase 1)

Secara eksplisit **tidak** termasuk MVP:

- Approval berjenjang & akun pejabat (Phase 2).
- Pembatasan data multi-unit aktif (struktur ada, penegakan Phase 2).
- Notifikasi WhatsApp, bulk printing, watermark, export laporan statistik (Phase 2).
- TTE tersertifikasi, integrasi SIAKAD realtime, generator sertifikat, e-Meterai, mobile app, API publik, OCR (Phase 3+).
- **Penggantian file surat hasil generate dengan hasil scan** — sengaja ditunda per keputusan terakhir; Phase 1 hanya menyediakan opsi metode pengambilan (Unduh/Ambil di Kampus).
- **Mekanisme versioning template** untuk perbaikan template yang sudah terpakai — belum dirancang (lihat §12).

---

## 12. Pertanyaan Terbuka & Langkah Selanjutnya

### 12.1 Keputusan yang Dibutuhkan dari Pemangku Kepentingan

| # | Pertanyaan Terbuka | Prioritas |
|---|---|---|
| Q1 | Target KPI kuantitatif (waktu layanan, adopsi) — berapa angkanya? | Tinggi |
| Q2 | Timeline, anggaran, dan komposisi tim pengembangan | Tinggi |
| Q3 | Apakah tanda tangan basah/PNG diterima secara sah oleh kampus & penerima eksternal? | Tinggi |
| Q4 | Apakah *proxy approval* disetujui pimpinan, dengan SOP bukti persetujuan? | Tinggi |
| Q5 | Perlukah **Master Klasifikasi Surat** tersendiri (kode klasifikasi surat masuk/keluar)? | Sedang |
| Q6 | Format baku nomor surat resmi UNsP | Sedang |
| Q7 | Sumber data **kalender hari libur nasional** untuk perhitungan SLA (skip weekend + libur) | Sedang |
| Q8 | Definisi **"tanggal submit"** untuk SLA — perlu timestamp submit terpisah dari draft | Sedang |
| Q9 | Mekanisme perbaikan/**versioning template** yang sudah terpakai & identifikasi surat terdampak | Sedang |
| Q10 | Kebijakan retensi & performa (volume, ukuran file, konkurensi) | Sedang |

### 12.2 Riset & Validasi Tambahan
- Audit kualitas data SIAKAD (email, kelengkapan) sebelum import pertama.
- Pemetaan jenis surat riil beserta SLA dan pejabat penandatangan per jenis.
- Uji penerimaan pengguna (UAT) dengan sampel admin & mahasiswa nyata.

### 12.3 Langkah Selanjutnya
1. Review PRD ini oleh pemangku kepentingan; jawab Q1-Q4 (prioritas tinggi).
2. Finalisasi metrik keberhasilan & timeline.
3. Kunci daftar jenis surat Phase 1 beserta SLA.
4. Lanjut ke perancangan detail teknis (skema tersedia di `perancangan-murni.md`).

---

## 13. Lampiran

### 13.1 Glosarium

| Istilah | Definisi |
|---|---|
| **Placeholder** | Penanda `{{variabel}}` di template Word yang disubstitusi nilai saat generate |
| **Kamus Placeholder** | Master data yang mendefinisikan perilaku tiap placeholder yang dikenal sistem |
| **filled_by** | Penanda siapa yang mengisi nilai placeholder: sistem / admin / mahasiswa |
| **Slot TTD** | Kelompok placeholder tanda tangan (nama, jabatan, gambar, NIP) untuk satu penandatangan |
| **Proxy Approval** | Model persetujuan di mana admin bertindak mewakili pejabat yang menyetujui secara offline |
| **Arsitektur 3 Kamar** | Pemisahan Permohonan, Arsip Surat Tercetak, dan Buku Agenda Keluar sebagai tiga entitas berbeda |
| **SLA** | Estimasi hari kerja penyelesaian surat, sebagai panduan & alert (bukan kontrak hukum) |
| **Arsip Immutable** | Arsip surat tercetak yang tidak dapat diubah; koreksi via cetak ulang |
| **Snapshot Historis** | Penyimpanan seluruh nilai data saat surat digenerate, agar arsip tetap valid meski data sumber berubah |
| **Metode Pengambilan** | Pilihan cara mahasiswa memperoleh surat: Unduh online atau Ambil di Kampus |
| **Disposisi** | Instruksi tindak lanjut dari pimpinan atas surat masuk |

### 13.2 Ringkasan Materi Referensi

| Dokumen | Peran |
|---|---|
| `perancangan-murni.md` | Sumber utama — keputusan desain final, daftar fitur Phase 1-3, skema database, struktur menu |
| `perancangan-kasar.md` | Sumber pendukung — konteks masalah, riset, rekomendasi, asumsi (khususnya §579+ tentang sistem templating) |
| `brd-alur-template-surat.md` | Referensi alur bisnis end-to-end template → permohonan → cetak (aturan bisnis BR-01 s/d BR-12) |
| `dokumentasi.md` | Referensi pembanding — arsitektur sistem surat OpenSID |

---

*PRD ini adalah dokumen hidup — diperbarui seiring jawaban atas pertanyaan terbuka dan keputusan pemangku kepentingan. Semua penanda `[PERLU VALIDASI]`, `[ASUMSI]`, dan `[KESALAHAN KRITIS]` menandai area yang belum final.*
