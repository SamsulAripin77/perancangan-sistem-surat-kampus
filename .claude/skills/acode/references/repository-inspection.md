# Repository Inspection

## Initial pass

1. Identify repository root.
2. Read active `AGENTS.md` guidance.
3. Inspect top-level names only.
4. Detect stack from manifests without opening dependency trees.
5. Read `.ai-context/MODULE-MAP.md` when present.
6. Search the task's strongest identifiers.
7. Update the mode-budget counter before expanding beyond the initial allowance.

Useful identifiers include exact errors, routes, classes, functions, components, database tables, hooks, queues, jobs, webhooks, tests, and visible UI labels.

## Default exclusions

Ignore unless directly relevant:

```text
.git/ .idea/ .vscode/ node_modules/ vendor/ dist/ build/
coverage/ .cache/ .next/ .nuxt/ storage/logs/ public/build/
*.min.js *.min.css *.map
```

## Expansion gate

Expand only when an import, call, event, route, hook, schema relation, focused-test failure, explicit behavior, security need, or data-integrity boundary proves the dependency.

A proven dependency permits a documented expansion; it does not automatically permit a mode escalation.
