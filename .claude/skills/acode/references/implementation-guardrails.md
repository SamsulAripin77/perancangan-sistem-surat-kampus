# Implementation Guardrails

## Scope

- User request, accepted PRD, project state, and repository instructions define scope.
- Out-of-scope improvements belong in a proposed finding, not the patch.
- Do not reorganize unrelated code while fixing a localized issue.

## Approval-first decisions

Pause and present options, trade-offs, and a recommendation before:

- changing current focus;
- adding a discovered gap or requirement to the queue;
- starting an approved queued task;
- crossing a mode escalation gate;
- raising priority above the affected parent task;
- adding dependencies;
- changing architecture or service boundaries;
- adding migrations or storage;
- changing public contracts;
- expanding product scope;
- making an ambiguous requirement concrete.

Queue approval is not execution approval. Approval to continue one task is not blanket approval for architecture, scope, dependency, or priority decisions.

## Bug fixing

1. Establish evidence.
2. Identify the causal path.
3. Patch the smallest responsible layer.
4. Add regression coverage when practical.
5. Verify the affected boundary.
6. Confirm final file counts against the selected mode.

## Gap and interruption triage

Before changing project state because a gap was discovered:

1. Stop only the affected portion.
2. Explain evidence, impact, affected task, proposed type, priority, and blocking effect in Preflight Amendment.
3. Record the finding as `proposed`; it is not yet open queued work.
4. Ask for queue approval.
5. After approval, persist it as `ready` and update blocking relations.
6. Regenerate Preflight and ask which task should become current focus.
7. Park the existing active task as `paused` or `blocked` only after the user approves the switch.
8. Restore and revalidate the selected task ledger before editing.

Do not mark incomplete work as completed. Do not rely on archive order to remember pending work.
