# Cross-Session Ledger

Human-readable read state lives in the selected task checkpoint. SHA-256 fingerprints are stored per task.

Active task:

```text
.ai-context/CURRENT-TASK.md
.ai-context/read-ledger.json
```

Queued or paused task:

```text
.ai-context/tasks/<TASK-ID>.md
.ai-context/tasks/<TASK-ID>.read-ledger.json
```

When current focus changes, the workflow snapshots the old checkpoint and ledger, restores the selected task's checkpoint and ledger, then revalidates fingerprints.

For continuation:

```bash
python <skill>/scripts/task_session.py <project> continue
```

For an approved focus switch:

```bash
python <skill>/scripts/task_session.py <project> start --task-id <TASK-ID> --park-current paused --approved
```

Before cross-session handoff:

```bash
python <skill>/scripts/ledger_state.py snapshot <project>
python <skill>/scripts/task_session.py <project> checkpoint --next-action "..."
```

States:

- `current`: fingerprint matches.
- `stale`: content changed.
- `re-read`: refreshed in active session and ready for snapshot.
- `session-unverified`: inherited without usable fingerprint.
- `missing`: path no longer exists.
- `unknown`: cannot be validated safely.
