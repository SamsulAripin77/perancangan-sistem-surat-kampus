# Project Instructions

## Product and scope

- Product:
- Current release:
- Primary PRD/source of truth:
- Development plan:
- Explicit out-of-scope areas:

## Stack

- Backend:
- Frontend:
- Database:
- Integrations:
- Package managers:

## Working agreements

- Treat `.ai-context` as portable multi-task project memory across chat sessions.
- Run `task_session.py <project> preflight` before implementation or focus switching.
- Read `PROJECT-STATE.json` and `WORK-QUEUE.md` before the full development plan or archive.
- Queue only work that was started or explicitly approved; keep unopened roadmap work as next reference only.
- Distinguish work type from priority.
- Do not add a discovered gap/requirement to the queue without explicit approval.
- Queue approval is not execution approval; request a separate task selection.
- Do not replace an active task; explicitly pause or block it and preserve its checkpoint.
- Revalidate a continued/restored task ledger before reusing source findings.
- Follow existing architecture and naming.
- Make the smallest coherent change.
- Do not add features or dependencies outside scope.
- Enforce the selected mode's read/write gates.
- Never escalate mode, architecture, scope, priority, or dependencies silently.
- Agent recommends; user makes final decisions.

## Commands

- Install:
- Development:
- Focused tests:
- Full tests:
- Lint/format:
- Build:

## Definition of done

- Acceptance criteria satisfied.
- Relevant tests/checks pass.
- `git diff` reviewed.
- No unrelated behavior changed.
- Current task checkpoint and project queue updated.
- Final mode-budget result recorded.
- Do not report a task complete on a self-typed "tests passed" claim. Run `scripts/verify.py`
  and let the recorded, passing `verification-report.json` gate completion.
- For Laravel: verification runs `php artisan test` (required) plus optional Pint/PHPStan/migrate.
  Keep controllers thin; put logic in Actions/Services; follow the ERD and naming conventions.
- Map each acceptance criterion of a task to a test; pass its ID to verify.py via `--ac`.
