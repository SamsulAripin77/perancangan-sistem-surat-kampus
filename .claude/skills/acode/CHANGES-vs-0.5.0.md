# acode 0.6.0 — changes vs 0.5.0 (your uploaded version)

Approach: **surgical edits + additions**, not a rewrite. The proven engine
(ledger fingerprints, state store, queue, git integration) is untouched. Only the
verification gap was closed and Laravel/Claude bindings added.

## Added (new files)
- `scripts/verify.py` — runs the project's REAL commands (test/lint/static/migrate),
  captures exit codes, writes `.ai-context/verification-report.json`. This is the
  mechanism acode lacked. Tested here with real pass/fail commands.
- `assets/templates/acode.config.template.json` — per-project verify commands
  (Laravel defaults: `php artisan test` required; Pint/PHPStan/migrate optional).
- `references/verification-and-ac.md` — explains the gate and the AC bridge.
- `CHANGES-vs-0.5.0.md` — this file.

## Edited (surgical)
- `scripts/task_session.py`
  - New `read_verification()` / `verification_ok()` helpers.
  - `complete` now REFUSES unless a fresh, passing `verification-report.json` exists for
    the task (fresh = report's git commit == HEAD). Bypass only via
    `--override-verification --approved`, recorded as remaining risk.
  - `complete` auto-fills "Tests/checks" from the real report instead of a typed string.
- `scripts/init_context.py` — also writes `.ai-context/acode.config.json`.
- `SKILL.md`
  - Description rebranded Codex → Claude; trigger phrases added (under-trigger fix).
  - New "Verification gate (mandatory before completion)" section.
  - New "Solo simple mode" section (reduced loop for one-task-at-a-time work).
  - Final-report step now points to verify.py, not a hand-typed test claim.
- `assets/templates/AGENTS.template.md` — verification + Laravel working agreements.
- `VERSION` → 0.6.0.
- `tests/test_task_session.py` — updated to satisfy the new gate (writes a passing
  report before `complete`, mirroring what verify.py does in real use).

## NOT changed (kept as-is)
- `scripts/ledger_state.py`, `scripts/state_store.py`, `scripts/mode_budget.py`,
  `scripts/context_summary.py`, `scripts/inspect_project.py`, `scripts/token_report.py`,
  `scripts/migrate_read_ledger.py` — untouched.
- Multi-lane queue / candidate / proposal / two-gate machinery — kept intact but made
  optional via "Solo simple mode". Not removed, so nothing breaks. If you want it fully
  stripped for a leaner solo skill, that is a follow-up.

## Verified in this environment
- All Python compiles; `verify.py --help` works.
- Full existing test suite passes (5/5) after edits.
- `verify.py` end-to-end: PASS (exit 0) on passing command, FAIL (exit 1) on failing one,
  report written with AC coverage + git commit.

## NOT verified here (you must, on your machine)
- Real `php artisan test` execution — no Laravel app in this environment. The mechanism
  is proven with stand-in commands; point `acode.config.json` at your real commands and
  run once to confirm.
