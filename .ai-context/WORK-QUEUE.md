# Work Queue

Generated from `PROJECT-STATE.json` at 2026-07-20T15:29:13Z.

## Current focus

- M0-T4 — Install & konfigurasi frontend npm+Vite (AdminLTE, Bootstrap, jQuery, DataTables, Select2, FilePond, SweetAlert2, FontAwesome)
- Status: active
- Type / priority / lane: main-task / normal / frontend
- Next exact action: npm uninstall tailwindcss @tailwindcss/vite; npm install paket2 di atas; update vite.config.js utk import scss/js semua lib; update resources/css/app.css & resources/js/app.js utk inisialisasi (bukan CDN)

## Candidate under Preflight

- none

## Open queue

1. **M0-T4** — Install & konfigurasi frontend npm+Vite (AdminLTE, Bootstrap, jQuery, DataTables, Select2, FilePond, SweetAlert2, FontAwesome)
   - Status: active
   - Type / priority / lane: main-task / normal / frontend
   - Relationship: no blocking relation
   - Next exact action: npm uninstall tailwindcss @tailwindcss/vite; npm install paket2 di atas; update vite.config.js utk import scss/js semua lib; update resources/css/app.css & resources/js/app.js utk inisialisasi (bukan CDN)
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

- M0-T3 — Setup tooling kualitas (Pint, Larastan, Pest, Laravel Boost MCP)

## Recommendation

- Recommend M0-T4 (main-task, normal) because highest effective priority among open work.
- Present all runnable choices and trade-offs, then wait for the user's explicit selection before changing current focus or editing code.
