#!/usr/bin/env python3
from __future__ import annotations
import argparse,re,shutil
from pathlib import Path


def main()->int:
    p=argparse.ArgumentParser(description="Migrate old read-ledger header")
    p.add_argument("project",nargs="?",default="."); p.add_argument("--dry-run",action="store_true")
    a=p.parse_args(); path=Path(a.project).expanduser().resolve()/".ai-context/CURRENT-TASK.md"
    if not path.is_file(): raise SystemExit(f"Not found: {path}")
    text=path.read_text(encoding="utf-8")
    text=text.replace("| Path | Purpose / symbols | Changed since read? |","| Path | Purpose / symbols | Read state |")
    lines=[]
    for line in text.splitlines():
        if line.lstrip().startswith("|"):
            cells=[c.strip() for c in line.strip().strip("|").split("|")]
            if len(cells)==3 and cells[2].lower() in {"yes","no"}:
                cells[2]="stale" if cells[2].lower()=="yes" else "session-unverified"
                line="| "+" | ".join(cells)+" |"
        lines.append(line)
    result="\n".join(lines).rstrip()+"\n"
    if a.dry_run: print(result); return 0
    shutil.copyfile(path,path.with_suffix(path.suffix+".bak")); path.write_text(result,encoding="utf-8")
    print(f"Migrated: {path}")
    return 0


if __name__=="__main__": raise SystemExit(main())
