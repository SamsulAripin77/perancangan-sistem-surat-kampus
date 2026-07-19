#!/usr/bin/env python3
from __future__ import annotations
import argparse,hashlib,json,os,re,subprocess,uuid
from datetime import datetime,timezone
from pathlib import Path

STATE=Path(".ai-context/read-ledger.json"); TASK=Path(".ai-context/CURRENT-TASK.md")
VALID={"current","stale","re-read","session-unverified","missing","unknown"}


def now(): return datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00","Z")
def digest(path:Path):
    h=hashlib.sha256(); size=0
    with path.open("rb") as f:
        for chunk in iter(lambda:f.read(1024*1024),b""): h.update(chunk); size+=len(chunk)
    return h.hexdigest(),size

def parse(text):
    lines=text.splitlines(); start=next((i for i,l in enumerate(lines) if l.strip()=="| Path | Purpose / symbols | Read state |"),None)
    if start is None: raise SystemExit("Read ledger table not found")
    rows=[]; i=start+2
    while i<len(lines) and lines[i].lstrip().startswith("|"):
        c=[x.strip() for x in lines[i].strip().strip("|").split("|")]
        if len(c)==3: rows.append([i,c[0],c[1],c[2] if c[2] in VALID else "unknown"])
        i+=1
    return lines,rows

def git(root,args):
    try:
        r=subprocess.run(["git","-C",str(root),*args],capture_output=True,text=True,timeout=10)
        return r.stdout.strip() if r.returncode==0 else "unavailable"
    except Exception: return "unavailable"

def main()->int:
    p=argparse.ArgumentParser(description="Cross-session read-ledger state")
    p.add_argument("command",choices=["snapshot","revalidate","status"]); p.add_argument("project",nargs="?",default="."); p.add_argument("--dry-run",action="store_true")
    a=p.parse_args(); root=Path(a.project).expanduser().resolve(); task=root/TASK; statep=root/STATE
    if not task.is_file(): raise SystemExit(f"Not found: {task}")
    text=task.read_text(encoding="utf-8"); lines,rows=parse(text)
    try: state=json.loads(statep.read_text(encoding="utf-8")) if statep.is_file() else {"schema_version":1,"task_id":str(uuid.uuid4()),"entries":{}}
    except Exception: state={"schema_version":1,"task_id":str(uuid.uuid4()),"entries":{}}
    entries=state.setdefault("entries",{}); counts={k:0 for k in VALID}
    for row in rows:
        idx,raw,purpose,status=row; cleaned=raw.strip().strip("`").replace("\\","/"); candidate=(root/cleaned).resolve()
        try: candidate.relative_to(root)
        except ValueError: status="unknown"
        else:
            if a.command=="snapshot":
                if status not in {"current","re-read"}: counts[status]+=1; continue
                if not candidate.is_file(): status="missing"; entries.pop(cleaned,None)
                else:
                    try:
                        sha,size=digest(candidate); entries[cleaned]={"sha256":sha,"size_bytes":size,"purpose":purpose,"captured_at":now()}; status="current"
                    except OSError: status="unknown"
            else:
                saved=entries.get(cleaned)
                if not isinstance(saved,dict) or not saved.get("sha256"): status="session-unverified"
                elif not candidate.is_file(): status="missing"
                else:
                    try: status="current" if digest(candidate)[0]==saved["sha256"] else "stale"
                    except OSError: status="unknown"
        counts[status]+=1; row[3]=status; lines[idx]=f"| {cleaned} | {purpose} | {status} |"
    stamp=now(); commit=git(root,["rev-parse","HEAD"]); working="clean" if git(root,["status","--porcelain"])=="" else "dirty"
    if a.command=="snapshot": state.update({"last_snapshot_at":stamp,"baseline_commit":commit,"working_tree_at_snapshot":working})
    else: state.update({"last_revalidated_at":stamp,"last_revalidated_commit":commit,"working_tree_at_revalidation":working})
    result=", ".join(f"{k}={v}" for k,v in counts.items() if v) or "ledger empty"
    if a.command=="status": print(result); return 0
    rendered="\n".join(lines).rstrip()+"\n"
    if a.dry_run: print(result); print(rendered); return 0
    task.write_text(rendered,encoding="utf-8"); statep.parent.mkdir(parents=True,exist_ok=True); statep.write_text(json.dumps(state,indent=2,sort_keys=True)+"\n",encoding="utf-8")
    print(f"Ledger {a.command}: {result}")
    return 0


if __name__=="__main__": raise SystemExit(main())
