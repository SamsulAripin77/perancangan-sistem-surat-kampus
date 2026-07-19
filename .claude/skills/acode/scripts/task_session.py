#!/usr/bin/env python3
from __future__ import annotations

import argparse
import re
import shutil
import subprocess
import sys
from datetime import datetime
from pathlib import Path

from state_store import (
    LANES,
    PRIORITIES,
    WORK_TYPES,
    current_path,
    derive_task_id,
    get_field,
    join_ids,
    load_state,
    now,
    render_preflight,
    safe_id,
    save_state,
    set_field,
    task_checkpoint_path,
    task_ledger_path,
    write_checkpoint_metadata,
)

TASK_TYPES = ("inspect", "debug", "implement", "refactor", "review")
MODES = ("low", "balanced", "deep")
RELATIONSHIPS = ("standalone", "sequential", "readiness-gap", "blocker", "follow-up", "replacement", "resume", "parallel")


def template_path() -> Path:
    return Path(__file__).resolve().parent.parent / "assets/templates/CURRENT-TASK.template.md"


def blank_checkpoint(task: dict) -> str:
    text = template_path().read_text(encoding="utf-8")
    return write_checkpoint_metadata(text, task)


def sync_current(root: Path, state: dict) -> None:
    focus_id = state.get("current_focus")
    path = current_path(root)
    if not focus_id or not path.is_file() or focus_id not in state.get("tasks", {}):
        return
    task = state["tasks"][focus_id]
    text = write_checkpoint_metadata(path.read_text(encoding="utf-8"), task)
    path.write_text(text, encoding="utf-8")
    checkpoint = task_checkpoint_path(root, focus_id)
    checkpoint.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(path, checkpoint)
    ledger = root / ".ai-context/read-ledger.json"
    if ledger.is_file():
        shutil.copyfile(ledger, task_ledger_path(root, focus_id))
    task["checkpoint_path"] = f".ai-context/tasks/{checkpoint.name}"
    task["updated_at"] = now()


def restore_task(root: Path, state: dict, task_id: str) -> int:
    task = state["tasks"][task_id]
    checkpoint = task_checkpoint_path(root, task_id)
    if checkpoint.is_file():
        text = checkpoint.read_text(encoding="utf-8")
    else:
        text = blank_checkpoint(task)
        checkpoint.parent.mkdir(parents=True, exist_ok=True)
        checkpoint.write_text(text, encoding="utf-8")
    text = write_checkpoint_metadata(text, task)
    current = current_path(root)
    current.parent.mkdir(parents=True, exist_ok=True)
    current.write_text(text, encoding="utf-8")
    source_ledger = task_ledger_path(root, task_id)
    active_ledger = root / ".ai-context/read-ledger.json"
    if source_ledger.is_file():
        shutil.copyfile(source_ledger, active_ledger)
        return revalidate(root)
    if active_ledger.exists():
        active_ledger.unlink()
    return 0


def archive_current(root: Path, state: dict, status: str) -> Path | None:
    focus_id = state.get("current_focus")
    current = current_path(root)
    if not focus_id or not current.is_file():
        return None
    task = state.get("tasks", {}).get(focus_id)
    if not task:
        return None
    task["status"] = status
    task["updated_at"] = now()
    text = write_checkpoint_metadata(current.read_text(encoding="utf-8"), task)
    archive = root / ".ai-context/archive"
    archive.mkdir(parents=True, exist_ok=True)
    goal_slug = re.sub(r"[^a-z0-9]+", "-", task.get("goal", focus_id).lower()).strip("-")[:60] or safe_id(focus_id).lower()
    base = f"{datetime.now():%Y%m%d-%H%M%S}-{goal_slug}"
    dest = archive / f"{base}.md"
    suffix = 2
    while dest.exists():
        dest = archive / f"{base}-{suffix}.md"
        suffix += 1
    dest.write_text(text, encoding="utf-8")
    active_ledger = root / ".ai-context/read-ledger.json"
    if active_ledger.is_file():
        shutil.copyfile(active_ledger, dest.with_name(dest.stem + ".read-ledger.json"))
    return dest


def revalidate(root: Path) -> int:
    script = Path(__file__).with_name("ledger_state.py")
    return subprocess.run([sys.executable, str(script), "revalidate", str(root)]).returncode


def read_verification(root: Path) -> dict | None:
    """Load .ai-context/verification-report.json produced by verify.py, if any."""
    import json
    path = root / ".ai-context/verification-report.json"
    if not path.is_file():
        return None
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return None


def verification_ok(root: Path, focus_id: str) -> tuple[bool, str]:
    """A task may complete only with a fresh, passing verification report for it.
    Fresh = report's git_commit matches HEAD (i.e. produced against the current code)."""
    report = read_verification(root)
    if not report:
        return False, "No verification-report.json. Run verify.py before completing."
    if not report.get("overall_pass"):
        return False, "Latest verification did not pass. Fix the failing steps and re-run verify.py."
    reported_task = report.get("task_id")
    if reported_task not in (focus_id, "unspecified", None):
        return False, f"Verification report is for {reported_task}, not the current task {focus_id}."
    head = subprocess.run(["git", "-C", str(root), "rev-parse", "HEAD"],
                          capture_output=True, text=True).stdout.strip()
    if head and report.get("git_commit") not in (head, "unavailable"):
        return False, ("Verification is stale: it was run against a different commit than HEAD. "
                       "Commit your changes and re-run verify.py so the evidence matches the code.")
    return True, "verified"


def require_approval(args: argparse.Namespace, action: str) -> None:
    if not getattr(args, "approved", False):
        raise SystemExit(f"{action} requires explicit user approval. Re-run with --approved only after the user confirms the Preflight choice.")


def parse_ids(raw: str | None) -> list[str]:
    if not raw:
        return []
    return [item.strip() for item in re.split(r"[,;]", raw) if item.strip()]


def task_record(args: argparse.Namespace, task_id: str, *, status: str = "active") -> dict:
    return {
        "id": task_id,
        "goal": args.goal,
        "work_type": args.work_type,
        "priority": args.priority,
        "status": status,
        "mode": args.mode,
        "task_type": args.type,
        "lane": args.lane,
        "previous_task": args.previous or "none",
        "relationship": args.relationship or "standalone",
        "triggered_by": args.triggered_by or "user-approved Preflight selection",
        "resume_target": args.resume or "none",
        "discovered_during": getattr(args, "discovered_during", None) or "none",
        "blocks": parse_ids(getattr(args, "blocks", None)),
        "blocked_by": [],
        "partially_blocked_by": [],
        "next_action": args.next_action or "not recorded",
        "source_reference": args.source_reference or "not recorded",
        "checkpoint_path": f".ai-context/tasks/{safe_id(task_id)}.md",
        "approved_for_queue": True,
        "approved_for_execution": status == "active",
        "created_at": now(),
        "updated_at": now(),
    }


def create_checkpoint(root: Path, task: dict) -> None:
    checkpoint = task_checkpoint_path(root, task["id"])
    checkpoint.parent.mkdir(parents=True, exist_ok=True)
    checkpoint.write_text(blank_checkpoint(task), encoding="utf-8")


def park_current(root: Path, state: dict, status: str | None) -> None:
    focus_id = state.get("current_focus")
    if not focus_id:
        return
    current = state["tasks"].get(focus_id)
    if not current:
        state["current_focus"] = None
        return
    if current.get("status") == "active":
        if status not in {"paused", "blocked"}:
            raise SystemExit(f"Current task {focus_id} is active. Pass --park-current paused|blocked after the user approves the focus switch.")
        current["status"] = status
        current["approved_for_execution"] = False
    sync_current(root, state)
    state["current_focus"] = None
    path = current_path(root)
    if path.exists():
        path.unlink()
    ledger = root / ".ai-context/read-ledger.json"
    if ledger.exists():
        ledger.unlink()


def unblock_dependents(state: dict, completed_id: str) -> None:
    for task in state.get("tasks", {}).values():
        task["blocked_by"] = [item for item in task.get("blocked_by", []) if item != completed_id]
        task["partially_blocked_by"] = [item for item in task.get("partially_blocked_by", []) if item != completed_id]
        if task.get("status") == "blocked" and not task.get("blocked_by"):
            task["status"] = "ready"
            task["approved_for_execution"] = False
        task["updated_at"] = now()


def print_preflight(root: Path, state: dict) -> None:
    save_state(root, state)
    print(render_preflight(state))


def add_common_task_args(parser: argparse.ArgumentParser) -> None:
    parser.add_argument("--goal", required=True)
    parser.add_argument("--type", choices=TASK_TYPES, default="implement")
    parser.add_argument("--mode", choices=MODES, default="balanced")
    parser.add_argument("--task-id")
    parser.add_argument("--work-type", choices=WORK_TYPES, default="main-task")
    parser.add_argument("--priority", choices=PRIORITIES, default="normal")
    parser.add_argument("--lane", choices=LANES, default="main")
    parser.add_argument("--relationship", choices=RELATIONSHIPS, default="standalone")
    parser.add_argument("--previous")
    parser.add_argument("--resume")
    parser.add_argument("--triggered-by")
    parser.add_argument("--next-action")
    parser.add_argument("--source-reference")


def main() -> int:
    parser = argparse.ArgumentParser(description="Manage acode portable multi-task project state")
    parser.add_argument("project", nargs="?", default=".")
    sub = parser.add_subparsers(dest="action", required=True)

    sub.add_parser("migrate", help="Create PROJECT-STATE.json from existing v0.4.x context without deleting history")
    sub.add_parser("preflight", help="Render the read-only project state, queue, priority recommendation, and approval gates")
    sub.add_parser("status", help="Alias of preflight")
    sub.add_parser("queue", help="Regenerate WORK-QUEUE.md and print it")
    sub.add_parser("continue", help="Revalidate the active task ledger and refresh its checkpoint")

    candidate = sub.add_parser("candidate", help="Prepare a task for targeted readiness Preflight without adding it to the queue")
    add_common_task_args(candidate)
    candidate.set_defaults(relationship="sequential")

    candidate_update = sub.add_parser("candidate-update", help="Record targeted readiness evidence for the current Preflight candidate")
    candidate_update.add_argument("--status", choices=("pending", "ready", "blocked", "waiting-decision", "pending-revalidation"), required=True)
    candidate_update.add_argument("--summary", required=True)
    candidate_update.add_argument("--next-action")
    candidate_update.add_argument("--source-reference")

    clear_candidate = sub.add_parser("clear-candidate", help="Remove the current Preflight candidate without affecting the queue")
    clear_candidate.add_argument("--reason", required=True)
    clear_candidate.add_argument("--approved", action="store_true")

    activate_candidate = sub.add_parser("activate-candidate", help="Activate a ready Preflight candidate after explicit execution approval")
    activate_candidate.add_argument("--park-current", choices=("paused", "blocked"))
    activate_candidate.add_argument("--approved", action="store_true")

    new = sub.add_parser("new", help="Open and immediately select a new user-approved task (compatibility path; candidate flow is preferred)")
    add_common_task_args(new)
    new.add_argument("--park-current", choices=("paused", "blocked"))
    new.add_argument("--readiness-confirmed", action="store_true", help="Confirms targeted readiness Preflight was already completed")
    new.add_argument("--approved", action="store_true")

    propose = sub.add_parser("propose", help="Record a discovered finding for Preflight; this does not add it to the open queue")
    propose.add_argument("--goal", required=True)
    propose.add_argument("--task-id")
    propose.add_argument("--work-type", choices=("gap", "task-requirement", "follow-up"), required=True)
    propose.add_argument("--priority", choices=PRIORITIES, help="Defaults to the affected task priority when available")
    propose.add_argument("--lane", choices=LANES, default="other")
    propose.add_argument("--discovered-during")
    propose.add_argument("--impact", required=True)
    propose.add_argument("--blocking-effect", choices=("none", "partial", "full"), default="none")
    propose.add_argument("--blocks", help="Comma-separated task IDs affected by this finding")
    propose.add_argument("--recommendation")
    propose.add_argument("--next-action")
    propose.add_argument("--source-reference")
    propose.add_argument("--mode", choices=MODES, default="balanced")
    propose.add_argument("--type", choices=TASK_TYPES, default="review")

    approve = sub.add_parser("approve", help="Move an approved proposal into the open queue without starting it")
    approve.add_argument("--task-id", required=True)
    approve.add_argument("--approved", action="store_true")
    approve.add_argument("--priority-override-approved", action="store_true", help="Required when proposal priority exceeds the task it affects")

    reject = sub.add_parser("reject", help="Reject/defer a proposed finding")
    reject.add_argument("--task-id", required=True)
    reject.add_argument("--reason", required=True)
    reject.add_argument("--approved", action="store_true")

    start = sub.add_parser("start", help="Select an existing queued task as current focus after Preflight approval")
    start.add_argument("--task-id", required=True)
    start.add_argument("--park-current", choices=("paused", "blocked"))
    start.add_argument("--approved", action="store_true")

    pause = sub.add_parser("pause", help="Checkpoint and remove the current focus without completing it")
    pause.add_argument("--status", choices=("paused", "blocked"), default="paused")
    pause.add_argument("--reason")
    pause.add_argument("--approved", action="store_true")

    checkpoint = sub.add_parser("checkpoint", help="Refresh current task metadata and persistent checkpoint")
    checkpoint.add_argument("--next-action")
    checkpoint.add_argument("--source-reference")
    checkpoint.add_argument("--priority", choices=PRIORITIES)
    checkpoint.add_argument("--status", choices=("active", "paused", "blocked", "waiting-decision"))
    checkpoint.add_argument("--blocked-by")
    checkpoint.add_argument("--partially-blocked-by")
    checkpoint.add_argument("--blocks")
    checkpoint.add_argument("--approved", action="store_true")

    complete = sub.add_parser("complete", help="Complete and archive the current task, preserving next roadmap only as a reference")
    complete.add_argument("--result")
    complete.add_argument("--tests")
    complete.add_argument("--remaining-risk")
    complete.add_argument("--next-task")
    complete.add_argument("--next-source")
    complete.add_argument("--next-note")
    complete.add_argument("--override-verification", action="store_true",
                          help="Bypass the passing-verification gate. Requires --approved and is recorded as remaining risk.")
    complete.add_argument("--approved", action="store_true",
                          help="Explicit user approval; required only when --override-verification is used.")

    set_next = sub.add_parser("set-next", help="Set a roadmap reference without adding it to the queue")
    set_next.add_argument("--task-id", required=True)
    set_next.add_argument("--source")
    set_next.add_argument("--note")

    resume = sub.add_parser("resume", help="Compatibility alias for starting a paused queued task")
    resume.add_argument("--task-id", required=True)
    resume.add_argument("--park-current", choices=("paused", "blocked"))
    resume.add_argument("--approved", action="store_true")

    args = parser.parse_args()
    root = Path(args.project).expanduser().resolve()
    if not root.is_dir():
        raise SystemExit(f"Project directory does not exist: {root}")
    state = load_state(root, migrate=True)

    if args.action == "migrate":
        save_state(root, state)
        print(f"Migrated project state to schema {state['schema_version']} at {root / '.ai-context/PROJECT-STATE.json'}")
        return 0

    if args.action in {"preflight", "status"}:
        sync_current(root, state)
        print_preflight(root, state)
        return 0

    if args.action == "queue":
        sync_current(root, state)
        save_state(root, state)
        print((root / ".ai-context/WORK-QUEUE.md").read_text(encoding="utf-8"))
        return 0

    if args.action == "continue":
        focus_id = state.get("current_focus")
        if not focus_id:
            raise SystemExit("No current focus. Run preflight and ask the user which queued task to start.")
        task = state["tasks"].get(focus_id)
        if not task or task.get("status") != "active":
            raise SystemExit(f"Current focus {focus_id} is not active. Run preflight before continuing.")
        code = revalidate(root)
        sync_current(root, state)
        save_state(root, state)
        return code

    if args.action == "candidate":
        existing = state.get("preflight_candidate")
        task_id = args.task_id or derive_task_id(args.goal, "TASK")
        if existing and existing.get("id") != task_id:
            raise SystemExit(
                f"Preflight candidate {existing.get('id')} already exists. Clear it with explicit approval before preparing another candidate."
            )
        state["preflight_candidate"] = {
            "id": task_id,
            "goal": args.goal,
            "work_type": args.work_type,
            "priority": args.priority,
            "lane": args.lane,
            "mode": args.mode,
            "task_type": args.type,
            "previous_task": args.previous or (state.get("last_completed") or {}).get("id", "none"),
            "relationship": args.relationship or "sequential",
            "triggered_by": args.triggered_by or "user request for targeted Preflight",
            "resume_target": args.resume or "none",
            "next_action": args.next_action or "perform targeted readiness inspection",
            "source_reference": args.source_reference or "not recorded",
            "readiness_status": "pending",
            "readiness_summary": "targeted readiness inspection not completed",
            "blocked_by": [],
            "partially_blocked_by": [],
            "created_at": existing.get("created_at") if existing else now(),
            "updated_at": now(),
        }
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "candidate-update":
        candidate = state.get("preflight_candidate")
        if not candidate:
            raise SystemExit("No Preflight candidate is recorded.")
        candidate_id = candidate.get("id")
        unresolved_proposals = [
            item.get("id") for item in state.get("proposals", {}).values()
            if item.get("discovered_during") == candidate_id or candidate_id in item.get("blocks", [])
        ]
        open_blockers = [
            task.get("id") for task in state.get("tasks", {}).values()
            if candidate_id in task.get("blocks", []) and task.get("status") in {"ready", "active", "paused", "blocked", "waiting-decision"}
        ]
        if args.status == "ready" and (unresolved_proposals or open_blockers or candidate.get("blocked_by") or candidate.get("partially_blocked_by")):
            raise SystemExit(
                "Candidate cannot be marked ready while findings/blockers remain: "
                + join_ids(unresolved_proposals + open_blockers + candidate.get("blocked_by", []) + candidate.get("partially_blocked_by", []))
            )
        candidate["readiness_status"] = args.status
        candidate["readiness_summary"] = args.summary
        if args.next_action:
            candidate["next_action"] = args.next_action
        if args.source_reference:
            candidate["source_reference"] = args.source_reference
        candidate["updated_at"] = now()
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "clear-candidate":
        require_approval(args, "Clearing the Preflight candidate")
        candidate = state.get("preflight_candidate")
        if not candidate:
            raise SystemExit("No Preflight candidate is recorded.")
        state.setdefault("rejected_findings", []).append({
            "id": candidate.get("id"),
            "goal": candidate.get("goal"),
            "status": "candidate-cleared",
            "reason": args.reason,
            "cleared_at": now(),
        })
        state["rejected_findings"] = state["rejected_findings"][-20:]
        state["preflight_candidate"] = None
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "activate-candidate":
        require_approval(args, "Activating the Preflight candidate")
        candidate = state.get("preflight_candidate")
        if not candidate:
            raise SystemExit("No Preflight candidate is recorded.")
        if candidate.get("readiness_status") != "ready":
            raise SystemExit(
                f"Candidate {candidate.get('id')} readiness is {candidate.get('readiness_status')}; complete targeted Preflight first."
            )
        candidate_id = candidate.get("id")
        unresolved = [
            item.get("id") for item in state.get("proposals", {}).values()
            if item.get("discovered_during") == candidate_id or candidate_id in item.get("blocks", [])
        ]
        blockers = [
            task.get("id") for task in state.get("tasks", {}).values()
            if candidate_id in task.get("blocks", []) and task.get("status") in {"ready", "active", "paused", "blocked", "waiting-decision"}
        ]
        if unresolved or blockers or candidate.get("blocked_by") or candidate.get("partially_blocked_by"):
            raise SystemExit("Candidate still has unresolved findings/blockers: " + join_ids(unresolved + blockers + candidate.get("blocked_by", []) + candidate.get("partially_blocked_by", [])))
        if candidate_id in state.get("tasks", {}):
            raise SystemExit(f"Task {candidate_id} already exists in the queue.")
        park_current(root, state, args.park_current)
        task = {
            "id": candidate_id,
            "goal": candidate.get("goal"),
            "work_type": candidate.get("work_type", "main-task"),
            "priority": candidate.get("priority", "normal"),
            "status": "active",
            "mode": candidate.get("mode", "balanced"),
            "task_type": candidate.get("task_type", "implement"),
            "lane": candidate.get("lane", "main"),
            "previous_task": candidate.get("previous_task", "none"),
            "relationship": candidate.get("relationship", "sequential"),
            "triggered_by": "user-approved targeted Preflight",
            "resume_target": candidate.get("resume_target", "none"),
            "discovered_during": "none",
            "blocks": [],
            "blocked_by": [],
            "partially_blocked_by": [],
            "next_action": candidate.get("next_action", "not recorded"),
            "source_reference": candidate.get("source_reference", "not recorded"),
            "checkpoint_path": f".ai-context/tasks/{safe_id(candidate_id)}.md",
            "approved_for_queue": True,
            "approved_for_execution": True,
            "created_at": candidate.get("created_at", now()),
            "started_at": now(),
            "updated_at": now(),
        }
        state["tasks"][candidate_id] = task
        state["current_focus"] = candidate_id
        state["preflight_candidate"] = None
        next_ref = state.get("next_planned_reference")
        if next_ref and next_ref.get("id", "").lower() == candidate_id.lower():
            state["next_planned_reference"] = None
        create_checkpoint(root, task)
        restore_task(root, state, candidate_id)
        save_state(root, state)
        print(f"Activated Preflight candidate {candidate_id}. Execution approval recorded.")
        return 0

    if args.action == "new":
        require_approval(args, "Starting a new task")
        if not args.readiness_confirmed:
            raise SystemExit(
                "Direct new-task activation requires --readiness-confirmed. Prefer candidate -> candidate-update ready -> activate-candidate."
            )
        task_id = args.task_id or derive_task_id(args.goal, "TASK")
        if task_id in state["tasks"]:
            raise SystemExit(f"Task {task_id} already exists in the open queue. Use start --task-id {task_id} --approved.")
        park_current(root, state, args.park_current)
        task = task_record(args, task_id, status="active")
        state["tasks"][task_id] = task
        state["current_focus"] = task_id
        next_ref = state.get("next_planned_reference")
        if next_ref and next_ref.get("id", "").lower() == task_id.lower():
            state["next_planned_reference"] = None
        create_checkpoint(root, task)
        restore_task(root, state, task_id)
        save_state(root, state)
        print(f"Started new task {task_id}. Run preflight again if project priorities changed.")
        return 0

    if args.action == "propose":
        task_id = args.task_id or derive_task_id(args.goal, "GAP" if args.work_type == "gap" else "REQ")
        if task_id in state["tasks"] or task_id in state["proposals"]:
            raise SystemExit(f"Task/finding {task_id} already exists.")
        affected_ids = parse_ids(args.blocks)
        if not affected_ids and args.blocking_effect != "none" and state.get("current_focus"):
            affected_ids = [state["current_focus"]]
        parent_id = affected_ids[0] if affected_ids else (args.discovered_during or state.get("current_focus"))
        parent = state.get("tasks", {}).get(parent_id) if parent_id else None
        candidate = state.get("preflight_candidate")
        if not parent and candidate and candidate.get("id") == parent_id:
            parent = candidate
        inherited_priority = parent.get("priority", "normal") if parent else "normal"
        proposed_priority = args.priority or inherited_priority
        rank = {name: index for index, name in enumerate(PRIORITIES)}
        priority_override_required = bool(parent and rank.get(proposed_priority, 2) < rank.get(inherited_priority, 2))
        proposal = {
            "id": task_id,
            "goal": args.goal,
            "work_type": args.work_type,
            "priority": proposed_priority,
            "inherited_priority": inherited_priority,
            "priority_override_required": priority_override_required,
            "lane": args.lane,
            "status": "proposed",
            "discovered_during": args.discovered_during or state.get("current_focus") or "none",
            "impact": args.impact,
            "blocking_effect": args.blocking_effect,
            "blocks": affected_ids,
            "recommendation": args.recommendation or "record for user decision; do not execute automatically",
            "next_action": args.next_action or "not recorded",
            "source_reference": args.source_reference or "not recorded",
            "mode": args.mode,
            "task_type": args.type,
            "approved_for_queue": False,
            "approved_for_execution": False,
            "created_at": now(),
            "updated_at": now(),
        }
        state["proposals"][task_id] = proposal
        sync_current(root, state)
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "approve":
        require_approval(args, "Approving a finding for the queue")
        proposal = state["proposals"].get(args.task_id)
        if not proposal:
            raise SystemExit(f"Proposed finding not found: {args.task_id}")
        if proposal.get("priority_override_required") and not args.priority_override_approved:
            raise SystemExit(
                f"Proposal {args.task_id} raises priority above inherited {proposal.get('inherited_priority')}. "
                "Obtain explicit approval and re-run with --priority-override-approved."
            )
        proposal = state["proposals"].pop(args.task_id)
        task = {
            **proposal,
            "status": "ready",
            "previous_task": proposal.get("discovered_during", "none"),
            "relationship": "blocker" if proposal.get("blocking_effect") in {"full", "partial"} else "follow-up",
            "triggered_by": f"approved finding from {proposal.get('discovered_during', 'unknown task')}",
            "resume_target": join_ids(proposal.get("blocks", [])),
            "blocked_by": [],
            "partially_blocked_by": [],
            "checkpoint_path": f".ai-context/tasks/{safe_id(args.task_id)}.md",
            "approved_for_queue": True,
            "approved_for_execution": False,
            "updated_at": now(),
        }
        state["tasks"][args.task_id] = task
        for target_id in task.get("blocks", []):
            target = state["tasks"].get(target_id)
            candidate = state.get("preflight_candidate")
            if not target and candidate and candidate.get("id") == target_id:
                if proposal.get("blocking_effect") == "full":
                    if args.task_id not in candidate.setdefault("blocked_by", []):
                        candidate["blocked_by"].append(args.task_id)
                    candidate["readiness_status"] = "blocked"
                elif proposal.get("blocking_effect") == "partial":
                    if args.task_id not in candidate.setdefault("partially_blocked_by", []):
                        candidate["partially_blocked_by"].append(args.task_id)
                    candidate["readiness_status"] = "waiting-decision"
                candidate["updated_at"] = now()
                continue
            if not target:
                continue
            if proposal.get("blocking_effect") == "full":
                if args.task_id not in target.setdefault("blocked_by", []):
                    target["blocked_by"].append(args.task_id)
                target["status"] = "blocked"
                target["approved_for_execution"] = False
            elif proposal.get("blocking_effect") == "partial":
                if args.task_id not in target.setdefault("partially_blocked_by", []):
                    target["partially_blocked_by"].append(args.task_id)
            target["updated_at"] = now()
        create_checkpoint(root, task)
        sync_current(root, state)
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "reject":
        require_approval(args, "Rejecting a proposed finding")
        proposal = state["proposals"].pop(args.task_id, None)
        if not proposal:
            raise SystemExit(f"Proposed finding not found: {args.task_id}")
        proposal.update({"status": "rejected", "reason": args.reason, "rejected_at": now()})
        state["rejected_findings"].append(proposal)
        state["rejected_findings"] = state["rejected_findings"][-20:]
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action in {"start", "resume"}:
        require_approval(args, "Changing current focus")
        task = state["tasks"].get(args.task_id)
        if not task:
            raise SystemExit(f"Open queued task not found: {args.task_id}")
        if task.get("status") == "blocked" or task.get("blocked_by"):
            raise SystemExit(f"Task {args.task_id} is blocked by {join_ids(task.get('blocked_by', []))}; it cannot start yet.")
        if task.get("status") not in {"ready", "paused", "waiting-decision", "active"}:
            raise SystemExit(f"Task {args.task_id} cannot start from status {task.get('status')}.")
        if state.get("current_focus") == args.task_id and task.get("status") == "active":
            task["approved_for_execution"] = True
            task["updated_at"] = now()
            sync_current(root, state)
            save_state(root, state)
            print(f"Current focus remains {args.task_id}. Execution approval confirmed.")
            return 0
        if state.get("current_focus") != args.task_id:
            park_current(root, state, args.park_current)
        task["status"] = "active"
        task["approved_for_execution"] = True
        task["started_at"] = task.get("started_at") or now()
        task["updated_at"] = now()
        state["current_focus"] = args.task_id
        next_ref = state.get("next_planned_reference")
        if next_ref and next_ref.get("id", "").lower() == args.task_id.lower():
            state["next_planned_reference"] = None
        code = restore_task(root, state, args.task_id)
        save_state(root, state)
        print(f"Current focus: {args.task_id}. Execution approval recorded.")
        return code

    if args.action == "pause":
        require_approval(args, "Pausing current work")
        focus_id = state.get("current_focus")
        if not focus_id or focus_id not in state["tasks"]:
            raise SystemExit("No current focus to pause.")
        task = state["tasks"][focus_id]
        task["status"] = args.status
        task["approved_for_execution"] = False
        if args.reason:
            task["pause_reason"] = args.reason
        sync_current(root, state)
        state["current_focus"] = None
        if current_path(root).exists():
            current_path(root).unlink()
        ledger = root / ".ai-context/read-ledger.json"
        if ledger.exists():
            ledger.unlink()
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "checkpoint":
        focus_id = state.get("current_focus")
        if not focus_id or focus_id not in state["tasks"]:
            raise SystemExit("No current focus to checkpoint.")
        task = state["tasks"][focus_id]
        relationship_change = (
            (args.status and args.status != task.get("status"))
            or (args.priority and args.priority != task.get("priority"))
            or args.blocked_by is not None
            or args.partially_blocked_by is not None
            or args.blocks is not None
        )
        if relationship_change:
            require_approval(args, "Changing task lifecycle, priority, or dependency state")
        if args.status and args.status != task.get("status"):
            task["status"] = args.status
            if args.status != "active":
                task["approved_for_execution"] = False
        if args.priority:
            task["priority"] = args.priority
        if args.next_action:
            task["next_action"] = args.next_action
        if args.source_reference:
            task["source_reference"] = args.source_reference
        if args.blocked_by is not None:
            task["blocked_by"] = parse_ids(args.blocked_by)
        if args.partially_blocked_by is not None:
            task["partially_blocked_by"] = parse_ids(args.partially_blocked_by)
        if args.blocks is not None:
            task["blocks"] = parse_ids(args.blocks)
        task["updated_at"] = now()
        sync_current(root, state)
        save_state(root, state)
        print(f"Checkpoint updated for {focus_id}.")
        return 0

    if args.action == "complete":
        focus_id = state.get("current_focus")
        if not focus_id or focus_id not in state["tasks"]:
            raise SystemExit("No current focus to complete.")
        task = state["tasks"][focus_id]
        current = current_path(root)
        if not current.is_file():
            raise SystemExit(f"Missing current checkpoint: {current}")

        # Verification gate (acode >=0.6.0): "done" must be evidence, not a claim.
        ok, reason = verification_ok(root, focus_id)
        if not ok:
            if not args.override_verification:
                raise SystemExit(
                    f"Cannot complete {focus_id}: {reason}\n"
                    f"Run: python <skill>/scripts/verify.py {root} --task-id {focus_id} --test-filter <Filter> --ac <AC-IDs>\n"
                    "Only bypass with explicit user approval: complete --override-verification --approved"
                )
            require_approval(args, "Overriding the verification gate")

        report = read_verification(root) or {}
        auto_tests = None
        if report.get("overall_pass"):
            steps = ", ".join(
                f"{s['step']}=PASS" for s in report.get("steps", []) if not s.get("skipped")
            )
            ac = ", ".join(report.get("acceptance_criteria_covered", []) or [])
            auto_tests = f"verify.py: {steps}" + (f" | AC: {ac}" if ac else "")

        text = current.read_text(encoding="utf-8")
        task["status"] = "completed"
        task["approved_for_execution"] = False
        task["updated_at"] = now()
        text = write_checkpoint_metadata(text, task)
        text = set_field(text, "Completion", "Result", args.result or f"{focus_id} completed")
        tests_value = args.tests or auto_tests
        if tests_value:
            text = set_field(text, "Completion", "Tests/checks", tests_value)
        remaining = args.remaining_risk
        if args.override_verification:
            note = "VERIFICATION OVERRIDDEN by explicit approval (no passing report)."
            remaining = f"{remaining}; {note}" if remaining else note
        if remaining:
            text = set_field(text, "Completion", "Remaining risk", remaining)
        if args.next_task:
            text = set_field(text, "Handoff", "Next planned task", args.next_task, before="Completion")
        current.write_text(text, encoding="utf-8")
        archive_current(root, state, "completed")
        state["last_completed"] = {"id": focus_id, "goal": task.get("goal", focus_id), "completed_at": now()}
        if args.next_task:
            state["next_planned_reference"] = {
                "id": args.next_task,
                "source": args.next_source or f"handoff from {focus_id}",
                "note": args.next_note or "not activated; not part of the open queue",
                "activated": False,
            }
        candidate = state.get("preflight_candidate")
        if candidate and candidate.get("id") in task.get("blocks", []):
            candidate["blocked_by"] = [item for item in candidate.get("blocked_by", []) if item != focus_id]
            candidate["partially_blocked_by"] = [item for item in candidate.get("partially_blocked_by", []) if item != focus_id]
            if not candidate.get("blocked_by") and not candidate.get("partially_blocked_by"):
                candidate["readiness_status"] = "pending-revalidation"
                candidate["readiness_summary"] = f"Blocker {focus_id} completed; targeted readiness must be revalidated."
            candidate["updated_at"] = now()
        unblock_dependents(state, focus_id)
        state["tasks"].pop(focus_id, None)
        state["current_focus"] = None
        task_checkpoint_path(root, focus_id).unlink(missing_ok=True)
        task_ledger_path(root, focus_id).unlink(missing_ok=True)
        current.unlink(missing_ok=True)
        (root / ".ai-context/read-ledger.json").unlink(missing_ok=True)
        save_state(root, state)
        print(render_preflight(state))
        return 0

    if args.action == "set-next":
        state["next_planned_reference"] = {
            "id": args.task_id,
            "source": args.source or "user-approved roadmap reference",
            "note": args.note or "not activated; not part of the open queue",
            "activated": False,
        }
        save_state(root, state)
        print(render_preflight(state))
        return 0

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
