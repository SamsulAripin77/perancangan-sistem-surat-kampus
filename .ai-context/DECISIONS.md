# Technical Decisions

Record only accepted, durable decisions.

## 2026-07-21 — D-007 Library parser import Excel/CSV (M1-T8)

- Status: accepted
- Context: M1-T8 (Import Mahasiswa SIAKAD) butuh membaca file `.xlsx/.csv`
  (UX_SPEC 2.A.3). ARCHITECTURE §2 tidak mencantumkan library untuk ini; phpword
  yang sudah ada tidak membawa parser spreadsheet.
- Options considered:
  - `openspout/openspout` — reader ringan & hemat memori (streaming), dukung
    xlsx + csv, dependensi minimal.
  - `maatwebsite/excel` — fitur lengkap tapi berat (menarik phpspreadsheet + sub-paket).
  - CSV-only native (tanpa dependensi) — menyimpang dari spec (drop xlsx).
- Decision: pakai `openspout/openspout` (^4.28) sebagai parser import xlsx/csv.
- Consequences: menambah 1 dependensi backend di luar ARCHITECTURE §2 (perlu
  update §2 saat sinkronisasi dok); parsing dilakukan streaming agar aman untuk
  file besar; pratinjau + import memakai reader yang sama.
- Approved by: user (2026-07-21)
