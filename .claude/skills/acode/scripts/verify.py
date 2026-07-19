#!/usr/bin/env python3
"""acode verify — run REAL verification (tests/lint/static/migrate) and record
an auditable result. This is the mechanical gate acode <=0.5.0 lacked: instead of
recording a free-text "tests passed" claim, it executes the project's configured
commands, captures real exit codes, and writes .ai-context/verification-report.json.

`task_session.py complete` refuses to finish a task unless a FRESH, PASSING report
exists (see complete gate). That turns "done" from a claim into evidence.

Config lives at .ai-context/acode.config.json (created by init_context.py). Example
for a Laravel project:

{
  "verify": {
    "test":    "php artisan test",
    "lint":    "./vendor/bin/pint --test",
    "static":  "./vendor/bin/phpstan analyse --no-progress",
    "migrate": "php artisan migrate:fresh --seed --env=testing"
  },
  "required": ["test"]
}

Usage:
  verify.py <project> --task-id M1-T6 \
     --test-filter PejabatTest \
     --ac AC-M1-T6-1 --ac AC-M1-T6-2 --ac AC-M1-T6-3
  verify.py <project> --only lint
  verify.py <project> --list
"""
from __future__ import annotations

import argparse
import json
import shlex
import subprocess
from datetime import datetime, timezone
from pathlib import Path

CONTEXT = ".ai-context"
CONFIG = "acode.config.json"
REPORT = "verification-report.json"
KNOWN = ("test", "lint", "static", "migrate")


def now() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def git(root: Path, *args: str) -> str:
    try:
        r = subprocess.run(["git", "-C", str(root), *args], capture_output=True, text=True, timeout=10)
        return r.stdout.strip() if r.returncode == 0 else "unavailable"
    except Exception:
        return "unavailable"


def load_config(root: Path) -> dict:
    path = root / CONTEXT / CONFIG
    if not path.is_file():
        raise SystemExit(
            f"Missing {path}. Run init_context.py first, then fill in the 'verify' commands "
            "for this project (e.g. \"test\": \"php artisan test\")."
        )
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        raise SystemExit(f"{path} is not valid JSON: {exc}")


def run_command(command: str, cwd: Path, tail: int = 40) -> dict:
    """Run one shell command, capture exit code + tail of combined output."""
    started = now()
    try:
        proc = subprocess.run(
            command, cwd=str(cwd), shell=True,
            capture_output=True, text=True, timeout=1800,
        )
        out = (proc.stdout or "") + (proc.stderr or "")
        lines = out.strip().splitlines()
        return {
            "command": command,
            "exit_code": proc.returncode,
            "passed": proc.returncode == 0,
            "output_tail": "\n".join(lines[-tail:]),
            "started_at": started,
            "finished_at": now(),
        }
    except subprocess.TimeoutExpired:
        return {"command": command, "exit_code": 124, "passed": False,
                "output_tail": "TIMEOUT after 1800s", "started_at": started, "finished_at": now()}
    except Exception as exc:  # noqa: BLE001
        return {"command": command, "exit_code": 1, "passed": False,
                "output_tail": f"failed to launch: {exc}", "started_at": started, "finished_at": now()}


def main() -> int:
    p = argparse.ArgumentParser(description="Run real verification and record an auditable report")
    p.add_argument("project", nargs="?", default=".")
    p.add_argument("--task-id", help="Task being verified (recorded in the report)")
    p.add_argument("--only", choices=KNOWN, action="append",
                   help="Run only these steps (repeatable). Default: every configured step.")
    p.add_argument("--test-filter", help="Appended to the test command (e.g. a --filter value)")
    p.add_argument("--ac", action="append", default=[],
                   help="Acceptance-criterion ID this run covers (repeatable). "
                        "Recorded so the human can audit AC->test coverage.")
    p.add_argument("--list", action="store_true", help="Print configured commands and exit")
    args = p.parse_args()

    root = Path(args.project).expanduser().resolve()
    if not root.is_dir():
        raise SystemExit(f"Project directory does not exist: {root}")

    config = load_config(root)
    verify_cfg = config.get("verify", {}) or {}
    required = config.get("required", ["test"]) or []

    if args.list:
        print(json.dumps({"verify": verify_cfg, "required": required}, indent=2))
        return 0

    steps = args.only or [k for k in KNOWN if verify_cfg.get(k)]
    if not steps:
        raise SystemExit("No verify commands configured. Edit .ai-context/acode.config.json.")

    results = []
    for step in steps:
        command = verify_cfg.get(step)
        if not command:
            results.append({"step": step, "skipped": True, "reason": "not configured", "passed": True})
            continue
        if step == "test" and args.test_filter:
            command = f"{command} {shlex.quote('--filter')} {shlex.quote(args.test_filter)}" \
                if "--filter" not in command else f"{command} {shlex.quote(args.test_filter)}"
        result = run_command(command, root)
        result["step"] = step
        result["required"] = step in required
        results.append(result)

    # Overall pass = every REQUIRED, non-skipped step passed.
    required_results = [r for r in results if r.get("required") and not r.get("skipped")]
    missing_required = [s for s in required if s not in [r["step"] for r in results if not r.get("skipped")]]
    overall = bool(required_results) and all(r["passed"] for r in required_results) and not missing_required

    report = {
        "schema_version": 1,
        "task_id": args.task_id or "unspecified",
        "generated_at": now(),
        "git_commit": git(root, "rev-parse", "HEAD"),
        "git_dirty": bool(git(root, "status", "--porcelain")) and git(root, "status", "--porcelain") != "unavailable",
        "acceptance_criteria_covered": args.ac,
        "steps": results,
        "overall_pass": overall,
    }
    out_path = root / CONTEXT / REPORT
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")

    print(f"acode verify -> {out_path}")
    for r in results:
        if r.get("skipped"):
            print(f"  - {r['step']:<8} skipped ({r.get('reason')})")
        else:
            flag = "PASS" if r["passed"] else "FAIL"
            req = "required" if r.get("required") else "optional"
            print(f"  - {r['step']:<8} {flag}  (exit {r['exit_code']}, {req})")
    if args.ac:
        print(f"  AC covered: {', '.join(args.ac)}")
    print(f"OVERALL: {'PASS' if overall else 'FAIL'}")
    if not overall:
        print("  Task cannot be completed until required steps pass. Fix and re-run verify.")
    return 0 if overall else 1


if __name__ == "__main__":
    raise SystemExit(main())
