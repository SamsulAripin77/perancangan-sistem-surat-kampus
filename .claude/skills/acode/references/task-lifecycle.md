# Multi-task Lifecycle

The lifecycle tracks several open tasks while allowing only one active current focus.

## Statuses

- `ready`: approved for queue and runnable, but not selected.
- `active`: selected current focus with execution approval.
- `paused`: incomplete and intentionally parked.
- `blocked`: incomplete and unable to proceed due to approved dependency.
- `waiting-decision`: open work whose next lifecycle decision is unresolved.
- `completed`: verified and archived.
- `cancelled`: intentionally stopped.
- `superseded`: replaced by another approved approach.
- `rejected`: proposed finding declined or deferred outside the queue.

## Relationships

- `standalone`
- `sequential`
- `readiness-gap`
- `blocker`
- `follow-up`
- `replacement`
- `resume`
- `parallel`

Relationships explain origin. They do not determine priority or authorize execution.

## Typical parallel flow

```text
TASK-008 completed
UI-COMPONENTS-001 paused, low priority
TASK-009 next roadmap reference, not queued
API-RESPONSE-GAP-001 proposed
```

After queue approval:

```text
UI-COMPONENTS-001 blocked, low priority
API-RESPONSE-GAP-001 ready, low priority
TASK-009 still not queued
```

Preflight then recommends and asks the user whether to:

- execute the low-priority gap;
- resume another open task;
- explicitly activate the next roadmap task.

## Completion

Completing current work must:

1. archive the final checkpoint and ledger;
2. remove it from open tasks;
3. set `last_completed`;
4. release dependencies it blocked;
5. preserve any next roadmap ID as a reference only;
6. leave no implied execution approval for another task.
