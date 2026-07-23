# PRD-INDEX — Peta Baca Sistem Surat Kampus

| | |
|---|---|
| **Fungsi** | Titik masuk WAJIB tiap sesi. Menentukan dokumen & section mana yang dibaca untuk sebuah task — agar tidak memuat seluruh PRD. |
| **Aturan** | Baca file ini dulu → buka HANYA dokumen yang ditunjuk untuk milestone/task aktif → baru kerjakan. Jangan membuka semua dokumen "untuk jaga-jaga". |
| **Path dasar** | Dokumen perancangan di `docs/` (lihat peta path §1). State eksekusi ada di `.ai-context/` (dikelola skill acode, bukan ditulis tangan). File di `_sources/` **tidak boleh** dibaca saat eksekusi task. |

---

## §1. Dokumen & perannya (satu sumber kebenaran per urusan)

| Dokumen | Path | Isi (SSOT untuk…) | Kapan dibaca |
|---|---|---|---|
| **PRD** | `docs/product/PRD.md` | Fitur F1–F13, persona, tujuan bisnis, metrik | Saat butuh "kenapa" sebuah fitur ada; jarang saat coding |
| **ERD** | `docs/design/ERD.md` | Skema tabel, kolom, relasi, constraint | Setiap task yang menyentuh DB / model |
| **ARCHITECTURE** | `docs/design/ARCHITECTURE.md` | Konvensi kode, stack, pola, otorisasi, testing | Setiap task (aturan main); baca section relevan saja |
| **FEATURE_MAP** | `docs/design/FEATURE_MAP.md` | Route × modul × tabel; **deteksi overlap (§4)** | Sebelum menambah route/model; cek overlap |
| **UX_SPEC** | `docs/design/UX_SPEC.md` | Wireframe, flow, UI-states per route | Task yang punya UI (frontend) |
| **BACKLOG** | `docs/delivery/BACKLOG.md` | Task (M-T) + AC + DoD | Sumber task yang dikerjakan |
| **DECISIONS** | `.ai-context/DECISIONS.md` | Resolusi item terbuka ⚠️ — SATU-SATUNYA log keputusan (dikelola acode) | Saat task bertanda ⚠️ / ada konflik |
| **PROJECT-STATE** | `.ai-context/PROJECT-STATE.json` | Task aktif, antrian, status, prioritas (mesin, jangan diedit tangan) | Awal tiap sesi (via `preflight`) |
| **WORK-QUEUE** | `.ai-context/WORK-QUEUE.md` | Ringkasan antrian yang di-generate dari PROJECT-STATE | Awal sesi — lihat sudah sampai mana |
| **CURRENT-TASK** | `.ai-context/CURRENT-TASK.md` | Checkpoint kerja task yang sedang aktif | Sepanjang task berjalan |

**Konvensi ID silang**: `F1–F13` (fitur PRD) · `M{n}-T{k}` (task) · modul `M-XXX` (FEATURE_MAP §1) · section `§n` per dokumen · `AC-<TASK>-<n>` (acceptance criterion, dipetakan ke test lewat `verify.py --ac`).

> **Catatan**: `docs/_state/` adalah legacy pointer saja — state aktif digantikan penuh oleh `.ai-context/` yang dikelola skill acode (fingerprint lintas sesi + gate verifikasi). Jangan buat ulang `current_task.md` atau `PROGRESS.md` manual.

---

## §2. Protokol tiap sesi (ringkas)

1. Skill acode jalankan `task_session.py preflight` → baca `.ai-context/PROJECT-STATE.json` + `WORK-QUEUE.md` + `CURRENT-TASK.md` (bukan ditulis/dibaca manual oleh Anda).
2. Jika task aktif belum ada laporan verifikasi yang lulus & segar → **verifikasi dulu** (`verify.py`), jangan mulai task baru.
3. Ambil task berikutnya dari `BACKLOG.md`. **Jika bertanda ⚠️** → cek `.ai-context/DECISIONS.md`; bila belum ada keputusan → STOP, minta keputusan (lalu dicatat lewat `task_session.py propose/approve`).
4. Buka HANYA dokumen yang ditunjuk §3 untuk milestone task itu.
5. Sebelum menambah route/model → cek `FEATURE_MAP §2` (route) & `§4` (overlap/owner).
6. Kerjakan → commit → `verify.py` (test benar-benar dijalankan) → `complete` (ditolak otomatis kalau verifikasi belum lulus).

---

## §3. Peta milestone → dokumen (context funnel)

> Untuk tiap milestone: dokumen inti yang perlu dibuka, dan section awal yang relevan. Section per-task yang lebih tepat ada di header tiap task di BACKLOG (kolom **Referensi**) — gunakan itu untuk mempersempit lebih jauh, dan isi `--source-reference` saat membuat candidate task di acode dengan section itu.

### Selalu berlaku (baca sekali, pegang terus)
- `ARCHITECTURE §0` (Bootstrap/gate), `§3` (folder & layering), `§5–§7` (controller tipis, Action/Service, Form Request), `§12` (penamaan), `§14` (testing), `§17` (standar list/index).
- `ERD §0` (konvensi umum — ENUM→string, softDeletes, aturan file K4).

### M0 — Project Bootstrap
Dokumen: `ARCHITECTURE §0, §1, §2, §2.1, §2.2, §10, §11, §13, §15`.
Catatan: **M0 belum detail** — perlu dipecah + diberi AC sebelum dieksekusi (gate: smoke test hijau sebelum fitur bisnis).

### M1 — Fondasi Data & Master (F1, F2, F4, F12, F13)
- ERD: `§1 units, §2 users, §3 mahasiswa, §4 pejabat, §5 roles/permission, §5.1 settings, §6 kategori_surat, §8 placeholder_definitions, §10 ref_syarat_surat`.
- ARCHITECTURE: `§8 auth/otorisasi, §9 upload, §13 seeders`.
- UX_SPEC: `1.A Auth, 1.C Konfigurasi, 2.A User, 2.B Kamus, 2.C Kategori, 2.D Syarat`.
- FEATURE_MAP: modul `M-AUTH, M-CONFIG, M-USER, M-KAMUS, M-KATEGORI, M-SYARAT`.

### M2 — Template Surat (F3)
- ERD: `§7 templates, §7.1 template_unit, §9 template_placeholder_config, §13 template_data_tambahan_fields`; kait `§8, §10`.
- UX_SPEC: `3.A–3.G` (index, create, edit-hub, syarat, data-tambahan, coba, panduan).
- FEATURE_MAP: `M-TEMPLATE`. Cek overlap **O4** (`ref_syarat_surat` FindOrCreate).

### M3 — Permohonan Mahasiswa (F5)
- ERD: `§11 dokumen_mahasiswa, §12 permohonan_surat, §14 permohonan_data_tambahan_values, §15 permohonan_syarat`.
- UX_SPEC: `4.A Ajukan, 4.B Dokumen Saya, 4.C Riwayat, 4.D Edit/Batal, 4.E Resubmit, 4.F Download, 4.G Profil`.
- FEATURE_MAP: `M-MHS-AJUKAN, M-MHS-DOKUMEN, M-MHS-RIWAYAT, M-MHS-PROFIL`. Overlap **O1** (permohonan_surat state machine), **O3** (dokumen_mahasiswa).

### M4 — Review & Approval Permohonan (F6)
- ERD: `§12 permohonan_surat` (transisi status); kait `§14, §15`.
- UX_SPEC: `5.A Review & Approval`.
- FEATURE_MAP: `M-PERM-ADM` (owner skema `permohonan_surat`). Overlap **O1** — transisi via Action khusus.

### M5 — Generate & Cetak Surat (F7)
- ERD: `§16 surat_tercetak, §17 surat_penandatangan`; sumber `§7, §9, §12`.
- ARCHITECTURE: `§2.1 generator PDF, §9 file`.
- UX_SPEC: `5.B Generate & Cetak`.
- FEATURE_MAP: `M-GENERATE` (owner INSERT `surat_tercetak`). Overlap **O2, O7** (immutability arsip).

### M6 — Arsip Surat & Verifikasi Publik (F8)
- ERD: `§16 surat_tercetak` (status, replaced_by), `§17`.
- UX_SPEC: `6.A Arsip, 6.B Verifikasi Publik (tanpa login)`.
- FEATURE_MAP: `M-ARSIP, M-VERIFIKASI`. Overlap **O2** — M-ARSIP hanya `SupersedeSuratAction`.

### M7 — Surat Masuk, Disposisi & Agenda Masuk (F9)
- ERD: `§18 surat_masuk` (+ lampiran/disposisi terkait).
- UX_SPEC: `7.A Surat Masuk`.
- FEATURE_MAP: `M-MASUK`.

### M8 — Surat Keluar / Buku Agenda Keluar (F10)
- ERD: `§21 surat_keluar`.
- UX_SPEC: `7.B Surat Keluar`.
- FEATURE_MAP: `M-KELUAR`.

### M9 — Notifikasi Email (F11)
- ARCHITECTURE: `§2` (mail dari settings).
- UX_SPEC: `B. Notifikasi Email`.
- FEATURE_MAP: `M-NOTIF` (cross-cutting).

### M10 — Finalisasi & Hardening
- ARCHITECTURE: `§14 testing, §15 tooling, §16 konflik dokumen`.
- Cross-cutting: `M-MEDIA` (`A. Upload File`), audit menyeluruh.

---

## §4. Item terbuka (baca sebelum menyentuh task ⚠️)

Task/route bertanda ⚠️ bergantung keputusan yang **belum final**. Sumbernya:
- `ERD §24` (Item Terbuka), `FEATURE_MAP` (⚠️ per baris), `UX_SPEC` (⚠️ + Rekap), `BACKLOG` (task ⚠️).
- Resolusi dicatat ke `.ai-context/DECISIONS.md` (satu-satunya log keputusan; lihat §1) lewat alur `task_session.py propose` → approve.

**Status saat ini**: keputusan D-001 s/d D-008 sudah dicatat di `.ai-context/DECISIONS.md`. Jika task baru menemukan item ⚠️ yang belum punya keputusan, triase & putuskan sebelum milestone terkait dieksekusi — kalau tidak, task ⚠️ akan memblokir loop (`candidate-update --status ready` akan menolak selama masih ada blocker).

---

## §5. Yang TIDAK boleh dibaca saat eksekusi task
- `docs/_sources/perancangan-kasar.md` dan `perancangan-murni.md` — arsip sumber (besar). Hanya untuk telusur asal-usul keputusan saat audit, bukan saat coding.
- `.ai-context/archive/` — task yang sudah selesai. Hanya untuk audit historis, bukan konteks task aktif.
