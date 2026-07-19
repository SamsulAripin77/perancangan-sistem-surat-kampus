# Project Instructions

## Product and scope
- Product: Sistem Surat Kampus (Universitas Nusa Putra)
- Primary PRD/source of truth: docs/product/PRD.md
- Read docs/ai-context/PRD-INDEX.md FIRST for any task — it maps which
  design doc/section to open per milestone. Do not open the full PRD,
  ERD, or UX_SPEC without checking PRD-INDEX first.
- Explicit out-of-scope: Phase 2 personas (Admin Unit, Pejabat approval portal) —
  see PRD §4.2. Do not implement unless explicitly moved into scope via a decision.

## Stack

- Backend: Laravel (see docs/design/ARCHITECTURE.md §1 for version)
- Frontend: AdminLTE + Blade + jQuery/DataTables/Select2/FilePond (ARCHITECTURE §10)
- Database: MySQL
- Integrations: SIAKAD (credential import), SMTP (ARCHITECTURE §2)
- Package managers: composer, npm

## Working agreements

- Treat `.ai-context/` as the single source of truth for portable multi-task project
  state across chat sessions. Do not maintain a separate state file elsewhere.
- Run `task_session.py <project> preflight` before implementation or focus switching.
- Read `.ai-context/PROJECT-STATE.json` and `.ai-context/WORK-QUEUE.md` before the
  full development plan or archive.
- Before opening any design document, consult `docs/ai-context/PRD-INDEX.md` to find
  the exact section — never load ERD.md, ARCHITECTURE.md, or UX_SPEC.md in full.
- Before adding a route or model, check `docs/design/FEATURE_MAP.md §4` (ownership /
  overlap detection) to avoid two modules writing the same table unsafely.
- Decisions (including resolutions of ⚠️ open items from BACKLOG/FEATURE_MAP/UX_SPEC)
  are recorded in `.ai-context/DECISIONS.md` — this is the single canonical decision
  log. `docs/decisions/` no longer holds a separate copy.
- Queue only work that was started or explicitly approved; keep unopened roadmap work
  as next reference only.
- Distinguish work type from priority.
- Do not add a discovered gap/requirement to the queue without explicit approval.
- Queue approval is not execution approval; request a separate task selection.
- Do not replace an active task; explicitly pause or block it and preserve its checkpoint.
- Revalidate a continued/restored task ledger before reusing source findings.
- Follow existing architecture and naming (docs/design/ARCHITECTURE.md).
- Make the smallest coherent change.
- Do not add features or dependencies outside scope.
- Enforce the selected mode's read/write gates.
- Never escalate mode, architecture, scope, priority, or dependencies silently.
- If work would deviate from ERD/ARCHITECTURE/UX_SPEC, STOP and ask for a decision —
  do not decide unilaterally.
- Agent recommends; user makes final decisions.

## Commands

- Install: composer install && npm install
- Development: php artisan serve / npm run dev
- Focused tests: php artisan test --filter=<TestName>
- Full tests: php artisan test
- Lint/format: ./vendor/bin/pint
- Static analysis: ./vendor/bin/phpstan analyse --no-progress
- Build: npm run build

## Definition of done

- Acceptance criteria (from the task's BACKLOG.md entry) satisfied and each mapped
  to a test ID passed to verify.py via `--ac`.
- Relevant tests/checks pass — verified by `scripts/verify.py`, never by a self-typed
  claim. `task_session.py complete` refuses without a fresh, passing
  `.ai-context/verification-report.json`.
- `git diff` reviewed.
- No unrelated behavior changed.
- Current task checkpoint (`.ai-context/`) and project queue updated.
- Final mode-budget result recorded.
- Controllers stay thin; business logic lives in Actions/Services
  (ARCHITECTURE §5–§6); follow ERD naming/conventions.