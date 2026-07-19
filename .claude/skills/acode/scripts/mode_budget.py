#!/usr/bin/env python3
"""Check read/write counts against acode mode gates without scanning source contents."""
from __future__ import annotations
import argparse,json,re,subprocess
from pathlib import Path

TEST_MARKERS=("/tests/","/test/","/__tests__/")
CONTEXT_PREFIXES=(".ai-context/","docs/","documentation/")
DEPENDENCY_PARTS={"node_modules","vendor","dist","build","coverage","public/build","storage/framework"}


def category(raw:str)->str:
    p=raw.strip().strip("`").replace("\\","/"); low="/"+p.lower().strip("/")+"/"; name=Path(p).name.lower()
    if p.startswith(".ai-context/") or p=="AGENTS.md" or p.lower().startswith(("docs/","documentation/")): return "context"
    if any(m in low for m in TEST_MARKERS) or ".test." in name or ".spec." in name or name.startswith("test_"): return "test"
    if any(part in p.split("/") for part in DEPENDENCY_PARTS): return "generated"
    return "implementation"


def git_names(root:Path)->set[str]:
    names=set()
    commands=(["diff","--name-only"],["diff","--cached","--name-only"],["ls-files","--others","--exclude-standard"])
    for args in commands:
        try:
            r=subprocess.run(["git","-C",str(root),*args],capture_output=True,text=True,timeout=15)
            if r.returncode==0: names.update(line.strip() for line in r.stdout.splitlines() if line.strip())
        except Exception: pass
    return names


def parse_task(path:Path):
    text=path.read_text(encoding="utf-8"); m=re.search(r"^- Mode:\s*(low|balanced|deep)\s*$",text,re.M); mode=m.group(1) if m else "balanced"
    lines=text.splitlines(); start=next((i for i,l in enumerate(lines) if l.strip()=="| Path | Purpose / symbols | Read state |"),None); paths=[]
    if start is not None:
        i=start+2
        while i<len(lines) and lines[i].lstrip().startswith("|"):
            c=[x.strip() for x in lines[i].strip().strip("|").split("|")]
            if len(c)==3 and c[0] and c[0] != "Path": paths.append(c[0].strip("`"))
            i+=1
    return text,mode,paths


def decide(mode,reads,writes,tests_read,tests_write):
    if mode=="deep": return "dependency-led",False,"Continue with phase checkpoints; deep mode is not unlimited."
    if mode=="low":
        if reads>8 or writes>5: return "escalation-required",True,"Pause and request approval to switch to balanced before further read/write expansion."
        if reads>5 or writes>3 or tests_read>1: return "documented-expansion",False,"Record the concrete dependency and why the task remains localized to one primary module."
        return "within-initial-budget",False,"Continue in low mode."
    if reads>18 or writes>12: return "escalation-required",True,"Pause and request approval to switch to deep."
    if reads>12 or writes>8 or tests_read>3: return "documented-expansion",False,"Record the dependency/risk reason before continuing."
    return "within-initial-budget",False,"Continue in balanced mode."


def update_section(text:str,values:dict)->str:
    section=("## Mode budget\n\n"
        f"- Primary modules: {values['modules']} (manual confirmation required)\n"
        f"- Implementation files read: {values['reads']}\n"
        f"- Tests read: {values['tests_read']}\n"
        f"- Planned implementation writes: {values['planned']} (manual)\n"
        f"- Actual implementation writes: {values['writes']}\n"
        f"- Actual test writes: {values['tests_write']}\n"
        f"- Budget state: {values['state']}\n"
        f"- Expansion approval: {'required' if values['approval'] else 'not required by numeric gate'}\n"
        f"- Recommendation: {values['recommendation']}\n")
    start=text.find("## Mode budget")
    if start>=0:
        end=text.find("\n## ",start+3)
        return text[:start]+section+(text[end+1:] if end>=0 else "")
    scope=text.find("\n## Evidence and checkpoint")
    return text[:scope].rstrip()+"\n\n"+section+"\n"+text[scope+1:] if scope>=0 else text.rstrip()+"\n\n"+section


def main()->int:
    p=argparse.ArgumentParser(description="Check acode mode read/write budget")
    p.add_argument("project",nargs="?",default="."); p.add_argument("--update",action="store_true"); p.add_argument("--json",action="store_true"); p.add_argument("--modules",type=int,default=1); p.add_argument("--planned-writes",type=int,default=0)
    a=p.parse_args(); root=Path(a.project).expanduser().resolve(); task=root/".ai-context/CURRENT-TASK.md"
    if not task.is_file(): raise SystemExit(f"Not found: {task}")
    text,mode,ledger=parse_task(task); readcats={"implementation":0,"test":0,"context":0,"generated":0}
    for x in ledger: readcats[category(x)]+=1
    changes=git_names(root); writecats={"implementation":0,"test":0,"context":0,"generated":0}
    for x in changes: writecats[category(x)]+=1
    state,approval,recommendation=decide(mode,readcats["implementation"],writecats["implementation"],readcats["test"],writecats["test"])
    if mode=="low" and a.modules>1: state="escalation-required"; approval=True; recommendation="Pause and request approval to switch to balanced because more than one primary module is involved."
    data={"mode":mode,"primary_modules":a.modules,"implementation_reads":readcats["implementation"],"test_reads":readcats["test"],"implementation_writes":writecats["implementation"],"test_writes":writecats["test"],"context_changes":writecats["context"],"budget_state":state,"approval_required":approval,"recommendation":recommendation}
    if a.update:
        values={"modules":a.modules,"reads":readcats["implementation"],"tests_read":readcats["test"],"planned":a.planned_writes,"writes":writecats["implementation"],"tests_write":writecats["test"],"state":state,"approval":approval,"recommendation":recommendation}
        task.write_text(update_section(text,values),encoding="utf-8")
    if a.json: print(json.dumps(data,indent=2))
    else:
        print("Mode budget check")
        print(f"- Mode: {mode}")
        print(f"- Implementation files read: {readcats['implementation']}")
        print(f"- Tests read: {readcats['test']}")
        print(f"- Implementation files changed: {writecats['implementation']}")
        print(f"- Tests changed: {writecats['test']}")
        print(f"- Primary modules: {a.modules}")
        print(f"- Budget state: {state}")
        print(f"- Approval required: {'yes' if approval else 'no'}")
        print(f"- Recommendation: {recommendation}")
    return 0


if __name__=="__main__": raise SystemExit(main())
