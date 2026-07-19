#!/usr/bin/env python3
from __future__ import annotations

import argparse
import shutil
from pathlib import Path

from state_store import load_state, save_state


def main() -> int:
    p = argparse.ArgumentParser(description="Initialize acode portable project context")
    p.add_argument("project", nargs="?", default=".")
    p.add_argument("--force", action="store_true")
    p.add_argument("--with-agents", action="store_true")
    a = p.parse_args()
    project = Path(a.project).expanduser().resolve()
    if not project.is_dir():
        raise SystemExit(f"Project directory does not exist: {project}")
    templates = Path(__file__).resolve().parent.parent / "assets" / "templates"
    pairs = [
        ("MODULE-MAP.template.md", project / ".ai-context/MODULE-MAP.md"),
        ("DECISIONS.template.md", project / ".ai-context/DECISIONS.md"),
        ("MODULE.template.md", project / ".ai-context/modules/_TEMPLATE.md"),
        ("PROJECT-STATE.template.json", project / ".ai-context/PROJECT-STATE.json"),
        ("WORK-QUEUE.template.md", project / ".ai-context/WORK-QUEUE.md"),
        ("acode.config.template.json", project / ".ai-context/acode.config.json"),
    ]
    if a.with_agents:
        pairs.append(("AGENTS.template.md", project / "AGENTS.md"))
    for name, dest in pairs:
        if dest.exists() and not a.force:
            print(f"skip  {dest}")
            continue
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(templates / name, dest)
        print(f"write {dest}")
    (project / ".ai-context/tasks").mkdir(parents=True, exist_ok=True)
    (project / ".ai-context/archive").mkdir(parents=True, exist_ok=True)
    ignore = project / ".ai-context/.gitignore"
    if not ignore.exists() or a.force:
        ignore.parent.mkdir(parents=True, exist_ok=True)
        ignore.write_text("read-ledger.json\n*.read-ledger.json\n*.tmp\n", encoding="utf-8")
        print(f"write {ignore}")
    state = load_state(project, migrate=True)
    save_state(project, state)
    print("ready portable multi-task state")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
