@AGENTS.md

## Claude Code notes

- This project uses the `acode` skill (`.claude/skills/acode/`) for cross-session
  task execution, state, and the verification gate. It should trigger automatically
  on phrases like "continue development" or "next task". If it doesn't trigger,
  invoke it explicitly with `/acode`.
- Everything else — working agreements, stack, commands, definition of done — lives
  in `AGENTS.md` above via import. Do not duplicate rules here; add only
  Claude Code–specific notes below this line.
