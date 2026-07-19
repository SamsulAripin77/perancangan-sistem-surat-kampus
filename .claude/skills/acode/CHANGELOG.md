# Changelog

## 0.5.0 — 2026-07-19

- Renamed the skill identity to **acode — ayaa code skill** with the `$acode` slug; project `.ai-context` compatibility is unchanged.
- Reframed `.ai-context` as portable multi-task project memory across chat sessions.
- Added `PROJECT-STATE.json`, generated `WORK-QUEUE.md`, and persistent per-task checkpoints/ledgers.
- Added mandatory read-only Project Preflight plus targeted task-candidate Preflight before unopened work can be activated.
- Added distinct work type and priority metadata.
- Added proposed findings outside the open queue and two explicit approval gates: queue approval and execution approval.
- Added `candidate`, `candidate-update`, `activate-candidate`, `clear-candidate`, `propose`, `approve`, `reject`, `start`, `pause`, `checkpoint`, `set-next`, `preflight`, and migration behavior.
- Ensured roadmap tasks that were never opened remain references rather than queue entries.
- Added full/partial blocker relations and automatic unblocking after completion.
- Added per-task checkpoint and ledger restoration when switching current focus.
- Clarified token estimates as planning indicators rather than API billing forecasts.

## 0.4.2 — 2026-07-18

- Added explicit task lifecycle metadata and structured handoff fields.
- Added readiness-gap, blocker, and resume transition guidance.
- Added `complete`, `resume`, and `status` actions to `task_session.py`.
- Prevented silent replacement of active or legacy-unknown current tasks.
- Added suspended-task ledger restoration and fingerprint revalidation.
- Added automatic task ID inference/generation and lifecycle-aware archival.
- Added `references/task-lifecycle.md` and updated templates, guardrails, README, and agent prompt.

## 0.4.1 — 2026-07-01

- Removed the `Confidence` field from estimated token reports.
- Kept work-mode reporting separate under `Mode Result`.
- Retained the `Estimated` labels and limitation note to communicate uncertainty.
- Updated skill instructions, token-reporting reference, README, and task-report template.

## 0.4.0 — 2026-07-01

- Added active read/write gates and approval-first mode escalation.
- Added low-mode checkpoints before implementation read 6 and write 6.
- Added balanced-to-deep escalation thresholds.
- Added module gate and separate classification for implementation, tests, context, and generated files.
- Added `mode_budget.py` with Git and read-ledger counters.
- Added mode-budget and expansion-log sections to `CURRENT-TASK.md`.
- Added final budget result to task reports.

## 0.3.0 — 2026-07-01

- Added cross-session ledger fingerprint validation and task archival.

## 0.2.0 — 2026-07-01

- Added estimated token reporting with actual telemetry precedence.
- Replaced `Changed since read?` with `Read state`.

## 0.1.0 — 2026-07-01

- Initial foundation.
