#!/usr/bin/env python3
from __future__ import annotations
import argparse
import json
import os
from collections import Counter
from pathlib import Path

EXCLUDES={".git",".idea",".vscode","node_modules","vendor","dist","build","coverage",".cache",".next",".nuxt","storage","__pycache__"}
SIGNALS={"composer.json":"PHP/Composer","artisan":"Laravel","package.json":"Node.js","wp-config.php":"WordPress","theme.json":"WordPress theme","pyproject.toml":"Python","go.mod":"Go","Cargo.toml":"Rust","pom.xml":"Java/Maven"}


def main() -> int:
    p=argparse.ArgumentParser(description="Filename-only repository inventory")
    p.add_argument("project",nargs="?",default=".")
    p.add_argument("--max-files",type=int,default=250)
    p.add_argument("--max-depth",type=int,default=5)
    p.add_argument("--json",action="store_true")
    a=p.parse_args(); root=Path(a.project).expanduser().resolve()
    files=[]; ext=Counter(); signals=[]; truncated=False
    for current,dirs,names in os.walk(root):
        rel=Path(current).relative_to(root)
        dirs[:]=sorted(d for d in dirs if d not in EXCLUDES)
        if len(rel.parts)>=a.max_depth: dirs[:]=[]
        for name in sorted(names):
            path=rel/name
            ext[Path(name).suffix.lower() or "[no extension]"]+=1
            if name in SIGNALS: signals.append({"path":path.as_posix(),"signal":SIGNALS[name]})
            if len(files)<a.max_files: files.append(path.as_posix())
            else: truncated=True
    data={"root":str(root),"stack_signals":signals,"extension_counts":dict(ext.most_common(20)),"files":files,"truncated":truncated}
    if a.json: print(json.dumps(data,indent=2))
    else:
        print(f"Root: {root}")
        for item in signals: print(f"- {item['path']}: {item['signal']}")
        print(f"Retained paths: {len(files)}{' (truncated)' if truncated else ''}")
        print("\n".join(files))
    return 0


if __name__=="__main__": raise SystemExit(main())
