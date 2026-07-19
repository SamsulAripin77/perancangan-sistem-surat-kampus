#!/usr/bin/env python3
from __future__ import annotations

import argparse
from pathlib import Path


def main() -> int:
    p = argparse.ArgumentParser(description="Print bounded acode portable project context")
    p.add_argument("project", nargs="?", default=".")
    p.add_argument("--max-chars", type=int, default=14000)
    a = p.parse_args()
    root = Path(a.project).expanduser().resolve()
    remaining = max(1000, a.max_chars)
    parts = []
    # Project state and queue must be first so a new session sees all open work
    # without reading the development plan or archive.
    for rel in [
        ".ai-context/PROJECT-STATE.json",
        ".ai-context/WORK-QUEUE.md",
        ".ai-context/CURRENT-TASK.md",
        ".ai-context/MODULE-MAP.md",
        ".ai-context/DECISIONS.md",
    ]:
        path = root / rel
        if not path.is_file() or remaining <= 0:
            continue
        text = path.read_text(encoding="utf-8", errors="replace").strip()
        if len(text) > remaining:
            text = text[:remaining].rstrip() + "\n[truncated]"
        parts.append(f"<!-- {rel} -->\n{text}")
        remaining -= len(text)
    if not parts:
        raise SystemExit("No .ai-context files found")
    print("\n\n".join(parts))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
