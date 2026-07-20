# Current Task

## Session continuity

- Ledger schema: 1
- Last snapshot: not captured
- Last revalidated: not validated
- Baseline commit: unavailable
- Working tree at validation: unavailable
- Validation result: required before reusing a previous-session ledger

## Task lifecycle

- Task ID: M1-T1
- Status: completed
- Previous task: M0-T9
- Relationship: sequential
- Resume target: none
- Triggered by: user-approved targeted Preflight

## Work state

- Work type: main-task
- Priority: normal
- Lane: backend
- Queue approval: approved
- Execution approval: required
- Discovered during: none
- Blocks: none
- Blocked by: none
- Partially blocked by: none
- Next exact action: Tulis 5 migration (units, alter users, mahasiswa, pejabat, pejabat_unit) + model Unit/Mahasiswa/Pejabat + update User; factories; CoreSchemaTest (migrate ok, 1-1 user-mahasiswa, pejabat 2 unit, unique kode/nim/email)
- Source reference: docs/delivery/BACKLOG.md#m1-t1

## Scope

- Goal: Migration & Model Core (units, users cols, mahasiswa, pejabat, pejabat_unit) + factories
- Type: implement
- Mode: balanced
- Allowed modules:
- Explicit exclusions:

## Mode budget

- Primary modules: 0
- Implementation files read: 0
- Tests read: 0
- Planned implementation writes: 0
- Actual implementation writes: 0
- Actual test writes: 0
- Budget state: within initial budget
- Expansion approval: not required

## Evidence and checkpoint

- Reproduction/evidence:
- Confirmed facts:
- Root cause or hypothesis:
- Next verification:

## Read ledger

| Path | Purpose / symbols | Read state |
|---|---|---|

Read-state meaning:

- `current`: saved fingerprint matches the current file.
- `stale`: file differs from the last verified fingerprint.
- `re-read`: relevant content was refreshed in this session; snapshot before handoff.
- `session-unverified`: inherited without a usable fingerprint.
- `missing`: path no longer exists.
- `unknown`: freshness cannot be established safely.

## Budget expansion log

| Trigger | Additional context/change | Reason | Recommendation | User decision |
|---|---|---|---|---|

## Change plan

- Files allowed to change:
- Contracts to preserve:
- Verification plan:

## Handoff

- Remaining verification: none recorded
- Next planned task: M1-T2
- Resume target: none
- Blocking decisions: none
- Preconditions for next task: none recorded

## Completion

- Result: M1-T1 selesai. 5 migration: create units (nama/kode uniq/parent_id self-FK/is_active/timestamps), alter users (+unit_id FK/must_change_password default false/softDeletes; is_active sudah M0-T8), create mahasiswa (user_id uniq 1-1/nim uniq/nama/prodi/is_active; tanpa fakultas D-001), create pejabat (nama/email null/nip_nidn null/jabatan/is_active/user_id null; TTD via Media Library coll ttd disk private), create pejabat_unit pivot (unique pejabat_id+unit_id). Model: Unit (parent/children/users), Mahasiswa (user), Pejabat (HasMedia ttd, belongsToMany units), User (+SoftDeletes, +unit/mahasiswa relasi, +unit_id/must_change_password fillable+cast). Factory: Unit/Mahasiswa/Pejabat/Setting + UserFactory state mahasiswa(). KEPUTUSAN USER: users.name DIPERTAHANKAN (bukan 'nama' ERD §2) -> deviasi ERD disengaja utk minim friction Fortify; users satu-satunya tabel core English column. timestamps di units/mahasiswa/pejabat per ERD §0 baris31; softDeletes hanya users; TANPA kolom role (Spatie).
- Tests/checks: php artisan test 25 passed (CoreSchemaTest 8: semua tabel core + kolom users benar + no role column, 1-1 user-mahasiswa dua arah, pejabat 2 unit n-n, hierarki unit self-ref, unique kode/nim/email, unique user_id mahasiswa, soft delete user, semua factory valid); pint passed; phpstan level 5 (0 errors); verify migrate:fresh --seed PASS.
- Final budget result:
- Remaining risk:
