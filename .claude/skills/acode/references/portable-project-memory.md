# Portable Project Memory

`.ai-context` must let a new chat session recover project work state without replaying chat history or rereading the complete development plan.

## Source-of-truth roles

- `PROJECT-STATE.json`: all open tasks, Preflight candidate, proposals, priority, dependencies, current focus, last completed task, and next roadmap reference.
- `WORK-QUEUE.md`: generated readable projection of project state.
- `CURRENT-TASK.md`: active working checkpoint only.
- `tasks/<TASK-ID>.md`: persistent checkpoint for each open task.
- `tasks/<TASK-ID>.read-ledger.json`: file fingerprints belonging to that task.
- `archive/`: terminal history and forensic evidence.

## Queue membership

A task enters the open queue only when one of these is true:

1. it has already started;
2. the user explicitly opened it;
3. a discovered gap/requirement was approved for the queue;
4. an active task was paused or blocked before completion.

A roadmap task that has never been started is not queued. Keep only one `next_planned_reference` pointer so the agent can recommend it without pretending it is already active work.

## Session bootstrap invariant

A new session should be able to answer from bounded context:

- What was last completed?
- Is an unopened task currently under targeted Preflight?
- What is current focus?
- Which tasks are paused, ready, or blocked?
- Which findings still await queue approval?
- Which open task blocks another?
- What is the next exact action for each open task?
- What roadmap task is next but not yet opened?
- Which decision must the user make before execution?

If the answer requires reading every archive file or the entire development plan, the portable memory is incomplete.

## Checkpoint discipline

Update `Next exact action` whenever work stops at a meaningful boundary. It should be executable and technical, not merely “continue task”.

Good:

```text
Implement validation error states using the approved API error contract.
```

Weak:

```text
Continue UI work.
```

Each open task checkpoint should preserve completed work, remaining work, relevant files, verification state, blockers, and source references.
