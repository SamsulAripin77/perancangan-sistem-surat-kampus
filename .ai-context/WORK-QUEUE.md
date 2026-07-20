# Work Queue

Generated from `PROJECT-STATE.json` at 2026-07-20T16:02:52Z.

## Current focus

- M0-T6 — Blade components inti (x-ui.*, x-form.*) + tema app.css + konvensi class app-*/js-*
- Status: active
- Type / priority / lane: main-task / normal / frontend
- Next exact action: Buat resources/views/components/ui/{button,card,datatable,badge-status}.blade.php + components/form/{input,select,file}.blade.php; tambah token tema (warna aksi) + kelas compact di resources/css/app.css; verifikasi render

## Candidate under Preflight

- none

## Open queue

1. **M0-T6** — Blade components inti (x-ui.*, x-form.*) + tema app.css + konvensi class app-*/js-*
   - Status: active
   - Type / priority / lane: main-task / normal / frontend
   - Relationship: no blocking relation
   - Next exact action: Buat resources/views/components/ui/{button,card,datatable,badge-status}.blade.php + components/form/{input,select,file}.blade.php; tambah token tema (warna aksi) + kelas compact di resources/css/app.css; verifikasi render
2. **QR-LIBRARY-CONFLICT-001** — Pilih pengganti simplesoftwareio/simple-qrcode (konflik bacon/bacon-qr-code ^2 vs Fortify ^3) sebelum M5-T2
   - Status: ready
   - Type / priority / lane: task-requirement / low / backend
   - Relationship: blocks M5-T2
   - Next exact action: not recorded

## Proposed findings

- none

## Next roadmap reference

- none

## Recently completed

- M0-T5 — Master layout AdminLTE (app/mahasiswa/auth/guest) + partial sidebar/topbar/breadcrumb/flash

## Recommendation

- Recommend M0-T6 (main-task, normal) because highest effective priority among open work.
- Present all runnable choices and trade-offs, then wait for the user's explicit selection before changing current focus or editing code.
