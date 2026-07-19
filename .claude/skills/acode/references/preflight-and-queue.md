# Preflight and Work Queue

Preflight is the control gate generated from portable project state before implementation begins or resumes.

## Preflight contents

Always show:

1. current focus and status;
2. open queue sorted by effective recommendation order;
3. proposed findings awaiting queue approval;
4. blocked and partially blocked relations;
5. next roadmap reference outside the queue;
6. last completed task;
7. recommendation and trade-offs;
8. explicit decisions required.


## Project Preflight and task-candidate Preflight

Project Preflight restores the portable queue at session startup. It does not prove that an unopened roadmap task is technically ready.

An unopened task first becomes `preflight_candidate`, which is outside the queue. The agent then performs a targeted readiness audit using only the referenced plan section, decisions, module context, relevant symbols, tests, and configuration.

Candidate states:

- `pending`: targeted readiness inspection has not finished.
- `blocked`: an approved full blocker exists.
- `waiting-decision`: a partial blocker or ambiguous requirement needs a decision.
- `pending-revalidation`: a blocker was completed and readiness must be checked again.
- `ready`: targeted evidence is sufficient and activation may be offered.

`ready` is still not execution approval. `activate-candidate --approved` is required.

## Two gates

### Gate 1 — queue approval

A discovered gap or requirement begins as `proposed`. The user decides whether it should enter the queue.

Queue approval means:

- the finding is accepted as tracked work;
- its priority and dependencies become persistent;
- affected tasks may become blocked or partially blocked.

It does not mean implementation may start.

### Gate 2 — execution approval

After the queue is updated, rerun Preflight. The user chooses which queued task becomes current focus.

Execution approval means:

- one task becomes `active`;
- the previous current task is explicitly paused or blocked when switching;
- the selected checkpoint and ledger are restored;
- implementation may begin within the approved scope.

Do not collapse both gates into one vague confirmation.

## Full versus partial blocker

- `full`: the affected task cannot continue and becomes `blocked`.
- `partial`: only a portion is blocked; unaffected work may continue.
- `none`: the finding is a follow-up and does not change the parent task status.

## Recommendation versus decision

The agent may rank choices using priority, blocker relationships, roadmap alignment, switching cost, and risk. The user remains the final decision maker.

A gap serving low-priority parallel UI preparation normally stays low. It should not jump above a high-priority main task solely because it was found more recently.
