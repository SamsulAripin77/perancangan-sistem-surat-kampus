---
name: acode
description: Portable, approval-first, verification-gated workflow for vibe coding across chat sessions. Maintains multi-task project state, Preflight queue and priority decisions, gap/requirement approval gates, scoped repository inspection, cross-session ledgers, real test/lint verification before a task is "done", and token-efficient mode budgets. Use this skill whenever continuing, implementing, or changing an existing codebase (especially Laravel) across sessions without losing pending work, rereading the full development plan, or expanding scope silently — including phrases like "continue development", "next task", "implement feature", "run the task", or "verify and complete".
---

# acode — ayaa code skill

Treat `.ai-context/` as portable project memory, not merely task history. A new chat session must be able to understand the active work, unfinished parallel work, approved gaps, blockers, last completed task, and next roadmap reference without reading the entire development plan or archive.

The order of priorities is:

1. cross-session continuity;
2. approval-first project control;
3. smallest sufficient context;
4. correctness and verification;
5. token efficiency.

## Mandatory session bootstrap

At the start of every new session or after uncertain context:

1. Read repository `AGENTS.md` instructions.
2. Detect `.ai-context/`.
3. Run:

   ```bash
   python <skill>/scripts/task_session.py <project> preflight
   ```

4. Initially read only:
   - `.ai-context/PROJECT-STATE.json`;
   - `.ai-context/WORK-QUEUE.md`;
   - `.ai-context/CURRENT-TASK.md` when a current focus exists;
   - directly referenced module or decision files.
5. Do not read the full development plan or archive by default.
6. Present the Preflight result before editing.
7. If several runnable tasks exist, provide a recommendation and trade-offs, then ask the user which task to select.
8. Do not change current focus or edit implementation files until the user has explicitly approved the selection.

If `.ai-context/PROJECT-STATE.json` is missing, migrate legacy v0.4.x context without deleting history:

```bash
python <skill>/scripts/task_session.py <project> migrate
```

## Solo simple mode (default for one-task-at-a-time work)

The full queue supports parallel lanes, candidates, and proposals. A solo developer
working one task at a time does not need all of it. Unless the user asks for parallel
work, use this reduced loop and keep the advanced machinery available but idle:

1. `preflight` — see current focus / next reference.
2. `candidate` → targeted readiness read → `candidate-update --status ready` → present Preflight → `activate-candidate --approved`.
3. Implement within mode budget.
4. Commit → `verify.py` (verification gate).
5. `complete` (blocked unless verification passed).

Only reach for `propose`/`approve`/two-gate lane switching when a genuine blocking gap
appears mid-task. Do not create parallel lanes for a solo linear roadmap.

## Portable project state

Use these roles:

- `PROJECT-STATE.json`: machine-readable source of truth for open work, task candidate, proposals, dependencies, and roadmap pointer.
- `WORK-QUEUE.md`: generated human/agent summary; never maintain it independently.
- `CURRENT-TASK.md`: working copy of the selected current focus.
- `tasks/*.md`: persistent checkpoints for queued, paused, or blocked work.
- `tasks/*.read-ledger.json`: per-task cross-session file fingerprints.
- `archive/`: completed, cancelled, or superseded history; not a default queue.
- `MODULE-MAP.md` and `modules/*.md`: durable repository navigation.
- `DECISIONS.md`: approved architecture and product decisions.

A task that has never been opened is not part of the queue. Record it only as `next_planned_reference`. Activating it requires explicit user approval.

## Work types and priority

Work type and priority are separate.

Work types:

- `main-task`: approved roadmap/product implementation currently opened.
- `gap`: discrepancy, defect, missing contract, or unexpected condition discovered during work.
- `task-requirement`: prerequisite required to make another task executable or verifiable.
- `parallel-prep`: intentionally early work such as UI foundations before integration is due.
- `follow-up`: approved non-blocking work discovered from another task.

Priorities:

- `critical`
- `high`
- `normal`
- `low`

A gap or task requirement does not automatically become high priority. Normally it inherits the priority of the task it blocks and must not exceed that priority without user approval. A low-priority frontend preparation gap remains low even when it is technically a blocker for that preparation lane.

Open statuses:

- `ready`
- `active`
- `paused`
- `blocked`
- `waiting-decision`

Terminal statuses:

- `completed`
- `cancelled`
- `superseded`
- `rejected`

Only one task may be `active` and selected as `current_focus`. Several other tasks may remain `ready`, `paused`, or `blocked` across sessions.

## Preflight control gate

Preflight is a read-only decision surface. It must show:

- current focus and exact next action;
- unopened task currently under targeted Preflight, when present;
- all open work already started or explicitly approved for the queue;
- work type, priority, lane, status, blockers, and blocked targets;
- proposed findings not yet approved for the queue;
- last completed task;
- next roadmap reference, clearly marked as not queued;
- recommendation and trade-offs;
- decisions required before execution.

Preflight never authorizes implementation. User approval is still required.

When the user already explicitly names the task to continue in the same message, still provide a compact Preflight confirmation before editing. Do not ask them to choose again unless the recorded state conflicts with their instruction.


## Targeted task Preflight before activation

Project Preflight restores the queue. A second, targeted task Preflight validates an unopened task before it becomes queued or active. This preserves the existing behavior where field mismatches, missing requirements, schema gaps, or readiness blockers are reported before implementation starts.

When the user tentatively selects a roadmap task that has not started, prepare it as a candidate rather than adding it to the queue:

```bash
python <skill>/scripts/task_session.py <project> candidate \
  --task-id TASK-009 \
  --goal "Implement TASK-009 catalog domain" \
  --work-type main-task \
  --priority high \
  --lane backend \
  --source-reference "docs/development-plan.md#task-009"
```

The candidate is outside the queue. Read only its referenced development-plan section, relevant decisions/module context, minimum implementation symbols, and nearest tests/config needed to validate readiness.

If readiness is clear:

```bash
python <skill>/scripts/task_session.py <project> candidate-update \
  --status ready \
  --summary "Targeted readiness checks passed" \
  --next-action "Implement the validated catalog schema and service"
```

Then present Preflight and request execution approval. Only after approval:

```bash
python <skill>/scripts/task_session.py <project> activate-candidate --approved
```

If the targeted audit finds a gap or requirement, use the proposal and two-gate flow below. A full blocker keeps the candidate outside the queue and marks it blocked. After the blocker is completed, the candidate becomes `pending-revalidation`; rerun the targeted audit before marking it ready.

## Two approval gates for gaps and requirements

When a gap, requirement, contract mismatch, missing field, schema discrepancy, or new blocker is found before or during implementation:

1. Stop only the affected portion.
2. Present a Preflight or Preflight Amendment with evidence, impact, affected task, proposed type, proposed priority, blocking effect, and recommendation.
3. Record it as a proposal only:

   ```bash
   python <skill>/scripts/task_session.py <project> propose \
     --task-id API-RESPONSE-GAP-001 \
     --goal "Add the response contract required by UI validation" \
     --work-type gap \
     --priority low \
     --lane integration \
     --impact "UI validation integration cannot finish" \
     --blocking-effect full \
     --blocks UI-COMPONENTS-001
   ```

4. Gate 1: ask whether the finding should enter the open queue.
5. Only after explicit approval, run:

   ```bash
   python <skill>/scripts/task_session.py <project> approve \
     --task-id API-RESPONSE-GAP-001 \
     --approved
   ```

6. Regenerate and present Preflight. Approval to queue is not approval to execute.
7. Gate 2: ask which queued task should become current focus.
8. Only after explicit selection, run:

   ```bash
   python <skill>/scripts/task_session.py <project> start \
     --task-id API-RESPONSE-GAP-001 \
     --park-current blocked \
     --approved
   ```

If the finding is rejected or deferred:

```bash
python <skill>/scripts/task_session.py <project> reject \
  --task-id API-RESPONSE-GAP-001 \
  --reason "UI integration is not yet a roadmap priority" \
  --approved
```

Do not interpret one user confirmation as both queue approval and execution approval unless the user explicitly states both decisions.

## Opening, switching, pausing, and completing work

For unopened roadmap work, use `candidate` → targeted readiness audit → `candidate-update --status ready` → `activate-candidate --approved`.

The direct `new --readiness-confirmed --approved` command remains a compatibility path for work whose targeted Preflight has already been completed and documented. Do not use it to skip readiness validation.

If another task is active, explicitly park it as `paused` or `blocked` when activating the candidate. Never replace it silently.

Switch to an existing queued task:

```bash
python <skill>/scripts/task_session.py <project> start \
  --task-id UI-COMPONENTS-001 \
  --park-current paused \
  --approved
```

Pause without selecting another task:

```bash
python <skill>/scripts/task_session.py <project> pause \
  --status paused \
  --reason "Returning to the main roadmap lane" \
  --approved
```

Update the portable checkpoint during gradual implementation:

```bash
python <skill>/scripts/task_session.py <project> checkpoint \
  --next-action "Implement validation error states using the approved API contract"
```

Complete the current task and leave the next roadmap task as a reference only:

```bash
python <skill>/scripts/task_session.py <project> complete \
  --result "TASK-008 completed" \
  --tests "Focused tests and build passed" \
  --next-task TASK-009 \
  --next-source "docs/development-plan.md#task-009"
```

Completion removes the task from the open queue, archives it, unblocks approved dependent tasks when appropriate, and does not automatically activate the next roadmap reference.

## Recommendation rules

The agent recommends but never decides.

Consider:

1. approved requirements or gaps that directly block an open main task;
2. priority level;
3. current active work and switching cost;
4. main roadmap alignment;
5. whether work is only low-priority parallel preparation;
6. remaining verification and risk.

Do not promote a low-priority parallel-preparation gap above a main roadmap task merely because it was discovered most recently.

When several tasks are runnable, show all relevant choices, recommend one, explain the trade-off, and wait for user selection.

## Context funnel and token control

Use the smallest sufficient context:

1. project instructions;
2. project state, queue, and current checkpoint;
3. directly referenced decision/module context;
4. targeted symbol, route, component, table, or error search;
5. relevant line ranges in the smallest implementation set;
6. nearest tests and configuration;
7. adjacent modules only after a concrete dependency is identified.

Do not reread the full development plan, archive, repository tree, generated files, dependencies, or verbose logs by default.

The main token-saving mechanism is portable state and avoided rereads. Token estimates are planning indicators, not API billing forecasts.

## Mode budgets

| Mode | Initial implementation reads | Initial tests | Normal implementation writes | Soft write ceiling | Intended behavior |
|---|---:|---:|---:|---:|---|
| low | up to 5 | 1 | 1–3 | 5 | one localized module, minimal exploration |
| balanced | up to 12 | up to 3 | 1–8 | 12 | normal multi-file task with close dependencies |
| deep | dependency-led | risk-led | plan-led | approval-led | cross-module, migration, security, architecture |

Budgets are behavioral gates, not promised token totals. Never escalate mode, scope, architecture, dependency, or write count silently.

Run at meaningful checkpoints:

```bash
python <skill>/scripts/mode_budget.py <project> --update
```

## Cross-session ledger

Before relying on a continued task:

```bash
python <skill>/scripts/task_session.py <project> continue
```

Before handoff, snapshot verified fingerprints:

```bash
python <skill>/scripts/ledger_state.py snapshot <project>
```

When switching tasks, the workflow stores and restores a per-task ledger. Any changed file must become `stale` and be reread selectively.

## Verification gate (mandatory before completion)

A task is "done" only with **evidence**, never a self-reported claim. Verification is
executed by a script and recorded, not typed as prose.

1. Ensure `.ai-context/acode.config.json` has real commands under `verify` (test/lint/static/migrate).
2. After implementing, commit the change, then run:

   ```bash
   python <skill>/scripts/verify.py <project> --task-id <TASK-ID> \
     --test-filter <TestFilter> --ac <AC-ID> [--ac <AC-ID> ...]
   ```

   This runs the configured commands, captures real exit codes, and writes
   `.ai-context/verification-report.json`.
3. `complete` REFUSES to finish the task unless a fresh, passing report exists for it
   (fresh = produced against the current `git HEAD`). The only bypass is
   `complete --override-verification --approved`, which records the skip as remaining risk.

Map each acceptance criterion of the task to a test and pass its ID via `--ac`. The
script cannot judge whether an AC is semantically right — that is the human's review —
but it guarantees the tests the agent claims to have run actually ran and passed against
the current code. Acceptance criteria come from the project's plan (e.g.
`docs/delivery/BACKLOG.md`); read only the AC block for the active task, not the whole plan.

For Laravel projects the default config runs `php artisan test` (required), plus optional
`pint --test`, `phpstan`, and a `migrate:fresh --seed --env=testing`. Adjust to the repo.

## Verification and final report

Before editing, record evidence, exact next action, allowed files, preserved contracts, exclusions, verification plan, and budget counters.

After editing:

1. review focused `git diff`;
2. run `verify.py` (the verification gate above) — not a hand-typed test claim;
3. update mode budget;
4. checkpoint unfinished work or complete verified work;
5. update durable module/decision context only when stable knowledge changed;
6. provide a concise result, verification, remaining risk, queue/handoff state, and token report.

When runtime/API telemetry exists, report actual usage. Otherwise provide a clearly labeled planning-only range and state that internal calls, tool definitions, cached context, reasoning, and repeated agent turns may not be represented.

Read these references when relevant:

- `references/portable-project-memory.md`
- `references/preflight-and-queue.md`
- `references/task-lifecycle.md`
- `references/mode-budgets.md`
- `references/cross-session-ledger.md`
- `references/token-governance.md`
