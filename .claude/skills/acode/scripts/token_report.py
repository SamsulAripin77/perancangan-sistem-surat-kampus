#!/usr/bin/env python3
from __future__ import annotations
import argparse,json
from pathlib import Path
from typing import Any


def walk(v:Any):
    if isinstance(v,dict):
        yield v
        for x in v.values(): yield from walk(x)
    elif isinstance(v,list):
        for x in v: yield from walk(x)


def main()->int:
    p=argparse.ArgumentParser(description="Aggregate real token telemetry")
    p.add_argument("files",nargs="+"); p.add_argument("--pretty",action="store_true"); p.add_argument("--markdown",action="store_true")
    a=p.parse_args(); total={"input_tokens":0,"cached_input_tokens":0,"uncached_input_tokens":0,"output_tokens":0,"reasoning_tokens":0,"total_tokens":0,"usage_objects":0}
    seen=set()
    for name in a.files:
        text=Path(name).read_text(encoding="utf-8").strip()
        try: objects=json.loads(text); objects=objects if isinstance(objects,list) else [objects]
        except json.JSONDecodeError: objects=[json.loads(line) for line in text.splitlines() if line.strip()]
        for obj in objects:
            for node in walk(obj):
                usage=node.get("usage") if isinstance(node,dict) else None
                if not isinstance(usage,dict):
                    if isinstance(node,dict) and ("input_tokens" in node or "output_tokens" in node): usage=node
                    else: continue
                if id(usage) in seen: continue
                seen.add(id(usage)); inp=int(usage.get("input_tokens") or 0); out=int(usage.get("output_tokens") or 0)
                ind=usage.get("input_tokens_details") or {}; outd=usage.get("output_tokens_details") or {}
                cached=int(ind.get("cached_tokens") or 0) if isinstance(ind,dict) else 0
                reasoning=int(outd.get("reasoning_tokens") or 0) if isinstance(outd,dict) else 0
                total["input_tokens"]+=inp; total["cached_input_tokens"]+=cached; total["uncached_input_tokens"]+=max(0,inp-cached)
                total["output_tokens"]+=out; total["reasoning_tokens"]+=reasoning; total["total_tokens"]+=int(usage.get("total_tokens") or inp+out); total["usage_objects"]+=1
    if a.markdown:
        print("Token report")
        print(f"- Actual token: input {total['input_tokens']:,} / output {total['output_tokens']:,}")
        print(f"- Cached input: {total['cached_input_tokens']:,}")
        print(f"- Uncached input: {total['uncached_input_tokens']:,}")
        print(f"- Reasoning token: {total['reasoning_tokens']:,}")
        print(f"- Total: {total['total_tokens']:,}")
        print("- Source: runtime/API telemetry")
    else: print(json.dumps(total,indent=2 if a.pretty else None,sort_keys=True))
    return 0


if __name__=="__main__": raise SystemExit(main())
