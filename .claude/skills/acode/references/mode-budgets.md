# Mode Budgets and Escalation

Mode budgets constrain exploration and patch size. They protect scope and token usage, but they never justify skipping required verification.

## What is counted

### Implementation files

Count source and runtime configuration that can change application behavior, including:

- PHP, Java, C#, Python, Go, Rust, Ruby;
- JavaScript, TypeScript, Vue, React, Blade, Twig, templates;
- CSS and preprocessors;
- routes, migrations, runtime configuration, manifests when behavior is affected.

### Tests

Count separately:

- `tests/`, `test/`, `__tests__/`;
- `*.test.*`, `*.spec.*`;
- framework-specific test files.

### Context and documentation

Do not count as implementation files:

- `AGENTS.md`;
- `.ai-context/*`;
- normal documentation and task reports.

A documentation file that directly drives runtime generation may be classified as implementation when the project treats it as executable input.

### Generated and dependency files

Do not count generated outputs or dependencies as intentional implementation reads/writes. Avoid reading or editing them unless the task explicitly requires it.

## Low mode

Low mode is intended for one localized behavior and normally one primary module.

### Reads

- 1–5 implementation files: normal initial budget.
- File 6 requires a written expansion checkpoint before opening it.
- Files 6–8 may be inspected only when a concrete dependency proves they are necessary, the task remains in one primary module, and risk remains low.
- File 9 or a second primary module requires a pause and recommendation to use `balanced`.

### Writes

- 1–3 implementation files: normal.
- 4–5: soft range; explain why the files are inseparable and why a smaller patch is unsafe or incomplete.
- Before file 6 is modified: pause and request approval to use `balanced`.

Do not edit five files first and ask afterward. The gate applies before exceeding the limit.

## Balanced mode

Balanced mode is the normal default.

- Initial implementation reads: up to 12.
- Initial tests: up to 3.
- Normal implementation writes: up to 8.
- Reads 13–18 or writes 9–12 require a documented expansion checkpoint.
- Read 19, write 13, or a high-risk architecture/security boundary requires recommendation and approval for `deep`.

## Deep mode

Deep mode is dependency-led and plan-led rather than unrestricted.

Before broad work:

- define boundaries and invariants;
- identify phases;
- state expected modules and file classes;
- define verification and rollback strategy;
- checkpoint after each phase.

Deep does not mean “read everything.”

## Module gate

A primary module is a business or technical boundary such as authentication, tenant registration, payment, notification, or reporting.

These do not normally create another primary module count:

- a test for the active module;
- shared CSS/JS directly owned by the same UI component;
- framework plumbing required solely by the active behavior.

A second independent business responsibility triggers mode review even if the file count is low.

## Required expansion checkpoint

Use this format before crossing a gate:

```text
Mode checkpoint
- Current mode: low
- Implementation files read: 5
- Implementation files changed/planned: 3
- Primary modules: 1
- Additional context required: app/Services/ExampleService.php
- Reason: the controller delegates the failing state transition to this service
- Smallest alternative considered: patch controller only; rejected because it duplicates domain logic
- Recommendation: continue low within the documented read expansion
- User approval required: no
```

When escalation is required:

```text
Mode checkpoint
- Current mode: low
- Implementation files read: 8
- Implementation files changed/planned: 6
- Primary modules: 2
- Recommendation: switch to balanced
- Reason: the fix crosses registration and notification boundaries
- Implementation status: paused before exceeding the write gate
- User approval required: yes
```

## Approval behavior

- Do not silently change mode.
- Do not interpret general permission to edit as permission to cross a mode gate.
- Continue only after explicit approval when the checkpoint says approval is required.
- If the user declines escalation, propose a smaller scoped alternative or stop with the limitation clearly stated.
