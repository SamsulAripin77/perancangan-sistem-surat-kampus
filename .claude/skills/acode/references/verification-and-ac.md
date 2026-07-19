# Verification gate and acceptance-criteria bridge

## Why this exists

acode <=0.5.0 recorded verification as free text: `complete --tests "tests passed"`
wrote whatever string it was given. Nothing ran the tests. A task could be "completed"
with no real evidence. This is the single most important correctness gap for a project
where a broken flow (timer, auto-submit, numbering, permissions) has real consequences.

acode >=0.6.0 replaces the claim with executed evidence.

## How the gate works

1. `.ai-context/acode.config.json` declares the project's real commands:

   ```json
   { "verify": { "test": "php artisan test", "lint": "./vendor/bin/pint --test" },
     "required": ["test"] }
   ```

2. `scripts/verify.py` runs them, captures real exit codes and an output tail, and
   writes `.ai-context/verification-report.json` including the current `git_commit`.

3. `task_session.py complete` calls `verification_ok()`. It refuses unless:
   - a report exists, and
   - `overall_pass` is true (every REQUIRED step passed), and
   - the report's `git_commit` matches HEAD (evidence matches the code — not stale).

4. Bypass is possible only with `complete --override-verification --approved`, and it is
   written into the archived task as "VERIFICATION OVERRIDDEN" remaining risk.

## The AC bridge

Acceptance criteria live in the project plan (e.g. `docs/delivery/BACKLOG.md`), not in the
skill. verify.py accepts `--ac <AC-ID>` (repeatable). Those IDs are recorded in the report
so a human can audit which criteria each verified run claims to cover.

What is mechanical vs human here:
- Mechanical (enforced): the tests actually ran and passed against the current commit.
- Human (not automatable): whether the test truly proves the AC, and whether the AC set
  is complete. The gate raises the floor; it does not replace review.

Recommended discipline: one test per AC where feasible, named so the mapping is obvious
(e.g. AC-M1-T6-2 -> PejabatTest::test_multi_unit_attaches_two_rows). Pass every AC ID for
the task to verify.py. If an AC has no test, it is not done.

## Reading only what you need

Read the AC block for the ACTIVE task only. Do not load the whole BACKLOG or PRD to verify
one task — that defeats the token funnel. The task's checkpoint records its source
reference (e.g. `docs/delivery/BACKLOG.md#m1-t6`); open that section, not the file.
