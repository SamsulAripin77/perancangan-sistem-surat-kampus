#!/usr/bin/env python3
from __future__ import annotations

import json
import re
import shutil
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

SCHEMA_VERSION = 2
SKILL_VERSION = "0.5.0"
OPEN_STATUSES = {"ready", "active", "paused", "blocked", "waiting-decision"}
TERMINAL_STATUSES = {"completed", "cancelled", "superseded", "rejected"}
PRIORITIES = ("critical", "high", "normal", "low")
WORK_TYPES = ("main-task", "gap", "task-requirement", "parallel-prep", "follow-up")
LANES = ("main", "backend", "frontend", "integration", "infrastructure", "documentation", "testing", "decision", "other")


def now() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")


def section_bounds(text: str, title: str) -> tuple[int, int] | None:
    match = re.search(rf"(?m)^## {re.escape(title)}\s*$", text)
    if not match:
        return None
    next_match = re.search(r"(?m)^##\s+", text[match.end():])
    end = match.end() + next_match.start() if next_match else len(text)
    return match.start(), end


def get_field(text: str, section: str, label: str) -> str:
    bounds = section_bounds(text, section)
    if not bounds:
        return ""
    chunk = text[bounds[0]:bounds[1]]
    match = re.search(rf"(?m)^- {re.escape(label)}:[ \t]*(.*)$", chunk)
    return match.group(1).strip() if match else ""


def set_field(text: str, section: str, label: str, value: str, *, before: str = "Scope") -> str:
    bounds = section_bounds(text, section)
    if not bounds:
        block = f"## {section}\n\n- {label}: {value}\n\n"
        before_match = re.search(rf"(?m)^## {re.escape(before)}\s*$", text)
        return text[:before_match.start()] + block + text[before_match.start():] if before_match else text.rstrip() + "\n\n" + block
    start, end = bounds
    chunk = text[start:end]
    pattern = rf"(?m)^- {re.escape(label)}:[ \t]*.*$"
    if re.search(pattern, chunk):
        chunk = re.sub(pattern, f"- {label}: {value}", chunk, count=1)
    else:
        chunk = chunk.rstrip() + f"\n- {label}: {value}\n\n"
    return text[:start] + chunk + text[end:]


def split_ids(raw: str) -> list[str]:
    if not raw or raw.lower() in {"none", "n/a", "not recorded"}:
        return []
    return [item.strip() for item in re.split(r"[,;]", raw) if item.strip()]


def join_ids(values: list[str]) -> str:
    return ", ".join(dict.fromkeys(v for v in values if v)) or "none"


def safe_id(raw: str) -> str:
    cleaned = re.sub(r"[^A-Za-z0-9._-]+", "-", raw.strip()).strip("-._")
    return cleaned or "TASK-UNASSIGNED"


def derive_task_id(goal: str, prefix: str = "TASK") -> str:
    match = re.search(r"\b((?:TASK|GATE|GAP|REQ|UI|API)[A-Z0-9_-]*-\d+)\b", goal, re.I)
    if match:
        return match.group(1).upper()
    stamp = datetime.now().strftime("%Y%m%d-%H%M%S-%f")[:-3]
    return f"{prefix}-{stamp}"


def lifecycle_status(text: str) -> str:
    status = get_field(text, "Task lifecycle", "Status").lower()
    if status:
        if status == "suspended":
            return "paused"
        return status
    result = get_field(text, "Completion", "Result").strip().lower()
    checks = get_field(text, "Completion", "Tests/checks").strip()
    budget = get_field(text, "Completion", "Final budget result").strip()
    if re.search(r"\b(completed|complete)\b", result):
        return "completed"
    if result and checks and budget and not re.search(r"\b(pending|incomplete|not completed|blocked)\b", result):
        return "completed"
    return "waiting-decision"


def task_id_from(text: str) -> str:
    task_id = get_field(text, "Task lifecycle", "Task ID") or get_field(text, "Session continuity", "Task ID")
    if task_id and task_id.lower() not in {"not assigned", "none", "n/a"}:
        return task_id
    goal = get_field(text, "Scope", "Goal")
    return derive_task_id(goal) if goal else "TASK-UNASSIGNED"


def checkpoint_metadata(text: str, checkpoint_path: str) -> dict[str, Any]:
    task_id = task_id_from(text)
    status = lifecycle_status(text)
    work_type = get_field(text, "Work state", "Work type") or "main-task"
    priority = get_field(text, "Work state", "Priority") or "normal"
    lane = get_field(text, "Work state", "Lane") or "main"
    return {
        "id": task_id,
        "goal": get_field(text, "Scope", "Goal") or task_id,
        "work_type": work_type if work_type in WORK_TYPES else "main-task",
        "priority": priority if priority in PRIORITIES else "normal",
        "status": status if status in OPEN_STATUSES | TERMINAL_STATUSES else "waiting-decision",
        "mode": get_field(text, "Scope", "Mode") or "balanced",
        "task_type": get_field(text, "Scope", "Type") or "implement",
        "lane": lane if lane in LANES else "other",
        "previous_task": get_field(text, "Task lifecycle", "Previous task") or "none",
        "relationship": get_field(text, "Task lifecycle", "Relationship") or "standalone",
        "triggered_by": get_field(text, "Task lifecycle", "Triggered by") or "legacy migration",
        "resume_target": get_field(text, "Task lifecycle", "Resume target") or "none",
        "discovered_during": get_field(text, "Work state", "Discovered during") or "none",
        "blocks": split_ids(get_field(text, "Work state", "Blocks")),
        "blocked_by": split_ids(get_field(text, "Work state", "Blocked by")),
        "partially_blocked_by": split_ids(get_field(text, "Work state", "Partially blocked by")),
        "next_action": get_field(text, "Work state", "Next exact action") or get_field(text, "Evidence and checkpoint", "Next verification") or "not recorded",
        "source_reference": get_field(text, "Work state", "Source reference") or "not recorded",
        "checkpoint_path": checkpoint_path,
        "approved_for_queue": True,
        "approved_for_execution": status == "active",
        "created_at": now(),
        "updated_at": now(),
    }


def default_state() -> dict[str, Any]:
    return {
        "schema_version": SCHEMA_VERSION,
        "skill_version": SKILL_VERSION,
        "updated_at": now(),
        "current_focus": None,
        "preflight_candidate": None,
        "last_completed": None,
        "next_planned_reference": None,
        "tasks": {},
        "proposals": {},
        "rejected_findings": [],
    }


def state_path(root: Path) -> Path:
    return root / ".ai-context/PROJECT-STATE.json"


def queue_path(root: Path) -> Path:
    return root / ".ai-context/WORK-QUEUE.md"


def tasks_dir(root: Path) -> Path:
    return root / ".ai-context/tasks"


def current_path(root: Path) -> Path:
    return root / ".ai-context/CURRENT-TASK.md"


def task_checkpoint_path(root: Path, task_id: str) -> Path:
    return tasks_dir(root) / f"{safe_id(task_id)}.md"


def task_ledger_path(root: Path, task_id: str) -> Path:
    return tasks_dir(root) / f"{safe_id(task_id)}.read-ledger.json"


def load_state(root: Path, *, migrate: bool = True) -> dict[str, Any]:
    path = state_path(root)
    if path.is_file():
        state = json.loads(path.read_text(encoding="utf-8"))
        state.setdefault("schema_version", SCHEMA_VERSION)
        state.setdefault("skill_version", SKILL_VERSION)
        state.setdefault("tasks", {})
        state.setdefault("proposals", {})
        state.setdefault("rejected_findings", [])
        state.setdefault("current_focus", None)
        state.setdefault("preflight_candidate", None)
        state.setdefault("last_completed", None)
        state.setdefault("next_planned_reference", None)
        return state
    state = migrate_legacy(root) if migrate else default_state()
    save_state(root, state)
    return state


def save_state(root: Path, state: dict[str, Any]) -> None:
    path = state_path(root)
    path.parent.mkdir(parents=True, exist_ok=True)
    tasks_dir(root).mkdir(parents=True, exist_ok=True)
    state["schema_version"] = SCHEMA_VERSION
    state["skill_version"] = SKILL_VERSION
    state["updated_at"] = now()
    path.write_text(json.dumps(state, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    queue_path(root).write_text(render_work_queue(state), encoding="utf-8")


def meaningful_current(text: str) -> bool:
    return bool(get_field(text, "Scope", "Goal") or get_field(text, "Completion", "Result"))


def migrate_legacy(root: Path) -> dict[str, Any]:
    state = default_state()
    current = current_path(root)
    if current.is_file():
        text = current.read_text(encoding="utf-8")
        if meaningful_current(text):
            meta = checkpoint_metadata(text, f".ai-context/tasks/{safe_id(task_id_from(text))}.md")
            next_task = get_field(text, "Handoff", "Next planned task")
            next_source = "legacy CURRENT-TASK handoff"
            if not next_task or next_task.lower() in {"none", "n/a"}:
                next_verification = get_field(text, "Evidence and checkpoint", "Next verification")
                match = re.search(r"\b(TASK-\d+)\b", next_verification, re.I)
                if match:
                    next_task = match.group(1).upper()
                    next_source = "legacy CURRENT-TASK Next verification"
            if next_task and next_task.lower() not in {"none", "n/a"}:
                state["next_planned_reference"] = {"id": next_task, "source": next_source, "note": "not activated", "activated": False}
            if meta["status"] == "completed":
                state["last_completed"] = {"id": meta["id"], "goal": meta["goal"], "completed_at": now()}
            else:
                checkpoint = task_checkpoint_path(root, meta["id"])
                checkpoint.parent.mkdir(parents=True, exist_ok=True)
                shutil.copyfile(current, checkpoint)
                state["tasks"][meta["id"]] = meta
                if meta["status"] == "active":
                    state["current_focus"] = meta["id"]
    current_completion_is_authoritative = state["last_completed"] is not None
    archive = root / ".ai-context/archive"
    if archive.is_dir():
        latest: dict[str, tuple[Path, str]] = {}
        for path in sorted(archive.glob("*.md")):
            text = path.read_text(encoding="utf-8", errors="replace")
            task_id = task_id_from(text)
            latest[task_id] = (path, text)
        for task_id, (path, text) in latest.items():
            status = lifecycle_status(text)
            if status in {"paused", "blocked", "waiting-decision"} and task_id not in state["tasks"]:
                dest = task_checkpoint_path(root, task_id)
                dest.parent.mkdir(parents=True, exist_ok=True)
                shutil.copyfile(path, dest)
                meta = checkpoint_metadata(text, f".ai-context/tasks/{dest.name}")
                state["tasks"][task_id] = meta
                ledger = path.with_name(path.stem + ".read-ledger.json")
                if ledger.is_file():
                    shutil.copyfile(ledger, task_ledger_path(root, task_id))
            elif status == "completed" and not current_completion_is_authoritative:
                state["last_completed"] = {"id": task_id, "goal": get_field(text, "Scope", "Goal") or task_id, "completed_at": now()}
    return state


def task_sort_key(task: dict[str, Any], state: dict[str, Any]) -> tuple[int, int, int, str]:
    priority_rank = {name: index for index, name in enumerate(PRIORITIES)}
    type_rank = {"main-task": 0, "gap": 1, "task-requirement": 2, "parallel-prep": 3, "follow-up": 4}
    blocks_open_main = any(
        target in state.get("tasks", {}) and state["tasks"][target].get("work_type") == "main-task" and state["tasks"][target].get("status") in OPEN_STATUSES
        for target in task.get("blocks", [])
    )
    blocking_rank = 0 if blocks_open_main and task.get("status") == "ready" else 1
    runnable_rank = 0 if task.get("status") in {"active", "ready", "paused"} else 1
    return (
        blocking_rank,
        priority_rank.get(task.get("priority", "normal"), 2),
        runnable_rank * 10 + type_rank.get(task.get("work_type", "follow-up"), 9),
        task.get("id", ""),
    )


def recommendation(state: dict[str, Any]) -> tuple[str, str]:
    tasks = list(state.get("tasks", {}).values())
    candidates = [t for t in tasks if t.get("status") in {"active", "ready", "paused"} and not t.get("blocked_by")]
    candidates.sort(key=lambda t: task_sort_key(t, state))
    next_ref = state.get("next_planned_reference")
    candidate = state.get("preflight_candidate")
    if candidate:
        candidate_blockers = [
            task for task in tasks
            if candidate.get("id") in task.get("blocks", []) and task.get("status") in OPEN_STATUSES
        ]
        runnable_candidate_blockers = [task for task in candidate_blockers if task.get("status") in {"active", "ready", "paused"} and not task.get("blocked_by")]
        runnable_candidate_blockers.sort(key=lambda task: task_sort_key(task, state))
        if runnable_candidate_blockers:
            first_blocker = runnable_candidate_blockers[0]
            return (
                f"Recommend {first_blocker.get('id')} first because it blocks Preflight candidate {candidate.get('id')}.",
                "The candidate remains outside the queue and must be revalidated before activation after the blocker is completed.",
            )
        if candidate.get("readiness_status") == "ready" and not candidate.get("blocked_by"):
            return (
                f"Recommend activating Preflight candidate {candidate.get('id')} if the user wants to proceed with the main selected work.",
                "Queued alternatives remain available; wait for explicit execution approval before activation.",
            )
        if candidate.get("readiness_status") in {"pending", "pending-revalidation", "waiting-decision"}:
            return (
                f"Complete the targeted readiness Preflight for candidate {candidate.get('id')} before execution.",
                "Record discovered gaps as proposals and mark the candidate ready only when required evidence is complete.",
            )
    if not candidates:
        if next_ref:
            return (
                f"No open runnable task. The next roadmap reference is {next_ref.get('id')}, but it is not in the queue and requires user approval before activation.",
                "Approve a proposed finding, resolve a blocker, or explicitly activate the next roadmap task.",
            )
        return ("No runnable task is recorded.", "Ask the user which task should be opened; do not infer one from the full development plan.")

    def is_low_parallel_support(task: dict[str, Any]) -> bool:
        if task.get("priority") != "low":
            return False
        if task.get("work_type") == "parallel-prep":
            return True
        if task.get("work_type") not in {"gap", "task-requirement", "follow-up"}:
            return False
        targets = [state.get("tasks", {}).get(task_id) for task_id in task.get("blocks", [])]
        targets = [target for target in targets if target]
        return bool(targets) and all(target.get("work_type") == "parallel-prep" and target.get("priority") == "low" for target in targets)

    if next_ref and all(is_low_parallel_support(task) for task in candidates):
        return (
            f"Recommend returning to the main roadmap by explicitly activating {next_ref.get('id')} rather than executing only low-priority parallel preparation work.",
            "The low-priority queued work remains preserved; show it as an alternative and wait for the user's choice.",
        )

    first = candidates[0]
    reason = "highest effective priority among open work"
    if any(target in state.get("tasks", {}) and state["tasks"][target].get("work_type") == "main-task" for target in first.get("blocks", [])):
        reason = "it unblocks an open main task"
    elif first.get("work_type") == "parallel-prep" and first.get("priority") == "low":
        reason = "it is the only runnable open item, but it remains low-priority parallel preparation"
    return (
        f"Recommend {first['id']} ({first.get('work_type')}, {first.get('priority')}) because {reason}.",
        "Present all runnable choices and trade-offs, then wait for the user's explicit selection before changing current focus or editing code.",
    )


def render_work_queue(state: dict[str, Any]) -> str:
    lines = ["# Work Queue", "", f"Generated from `PROJECT-STATE.json` at {state.get('updated_at', now())}.", ""]
    focus_id = state.get("current_focus")
    focus = state.get("tasks", {}).get(focus_id) if focus_id else None
    lines += ["## Current focus", ""]
    if focus:
        lines += [
            f"- {focus['id']} — {focus.get('goal', '')}",
            f"- Status: {focus.get('status')}",
            f"- Type / priority / lane: {focus.get('work_type')} / {focus.get('priority')} / {focus.get('lane')}",
            f"- Next exact action: {focus.get('next_action', 'not recorded')}",
            "",
        ]
    else:
        lines += ["- none", ""]

    lines += ["## Candidate under Preflight", ""]
    candidate = state.get("preflight_candidate")
    if candidate:
        blocked_by = join_ids(candidate.get("blocked_by", []))
        lines += [
            f"- {candidate.get('id')} — {candidate.get('goal', '')}",
            f"- Readiness: {candidate.get('readiness_status', 'pending')}",
            f"- Type / priority / lane: {candidate.get('work_type')} / {candidate.get('priority')} / {candidate.get('lane')}",
            f"- Blocked by: {blocked_by}",
            f"- Readiness summary: {candidate.get('readiness_summary', 'not audited')}",
            f"- Source reference: {candidate.get('source_reference', 'not recorded')}",
            "- Queue membership: none until execution is explicitly approved",
            "",
        ]
    else:
        lines += ["- none", ""]

    lines += ["## Open queue", ""]
    open_tasks = [t for t in state.get("tasks", {}).values() if t.get("status") in OPEN_STATUSES]
    open_tasks.sort(key=lambda t: task_sort_key(t, state))
    if not open_tasks:
        lines += ["- none", ""]
    else:
        for idx, task in enumerate(open_tasks, 1):
            relations = []
            if task.get("blocked_by"):
                relations.append(f"blocked by {join_ids(task['blocked_by'])}")
            if task.get("partially_blocked_by"):
                relations.append(f"partially blocked by {join_ids(task['partially_blocked_by'])}")
            if task.get("blocks"):
                relations.append(f"blocks {join_ids(task['blocks'])}")
            relation_text = "; ".join(relations) or "no blocking relation"
            lines += [
                f"{idx}. **{task['id']}** — {task.get('goal', '')}",
                f"   - Status: {task.get('status')}",
                f"   - Type / priority / lane: {task.get('work_type')} / {task.get('priority')} / {task.get('lane')}",
                f"   - Relationship: {relation_text}",
                f"   - Next exact action: {task.get('next_action', 'not recorded')}",
            ]
        lines.append("")

    lines += ["## Proposed findings", ""]
    proposals = list(state.get("proposals", {}).values())
    if not proposals:
        lines += ["- none", ""]
    else:
        priority_rank = {name: index for index, name in enumerate(PRIORITIES)}
        for idx, item in enumerate(sorted(proposals, key=lambda p: (priority_rank.get(p.get("priority", "normal"), 2), p.get("id", ""))), 1):
            lines += [
                f"{idx}. **{item['id']}** — {item.get('goal', '')}",
                f"   - Proposed type / priority: {item.get('work_type')} / {item.get('priority')}",
                f"   - Found during: {item.get('discovered_during', 'none')}",
                f"   - Impact: {item.get('impact', 'not recorded')}",
                f"   - Priority source: inherited {item.get('inherited_priority', item.get('priority'))}" + ("; override approval required" if item.get("priority_override_required") else ""),
                f"   - Queue approval: required",
            ]
        lines.append("")

    lines += ["## Next roadmap reference", ""]
    next_ref = state.get("next_planned_reference")
    if next_ref:
        lines += [
            f"- {next_ref.get('id')}",
            f"- Source: {next_ref.get('source', 'not recorded')}",
            f"- Note: {next_ref.get('note', 'not activated')}",
            "- Queue membership: none until explicitly activated",
            "",
        ]
    else:
        lines += ["- none", ""]

    lines += ["## Recently completed", ""]
    last = state.get("last_completed")
    lines += [f"- {last.get('id')} — {last.get('goal')}" if last else "- none", ""]
    rec, gate = recommendation(state)
    lines += ["## Recommendation", "", f"- {rec}", f"- {gate}", ""]
    return "\n".join(lines).rstrip() + "\n"


def render_preflight(state: dict[str, Any]) -> str:
    queue = render_work_queue(state)
    proposals = len(state.get("proposals", {}))
    runnable = [t for t in state.get("tasks", {}).values() if t.get("status") in {"active", "ready", "paused"} and not t.get("blocked_by")]
    decision_lines: list[str] = []
    if proposals:
        decision_lines.append("Approve or reject each proposed finding before it can enter the open queue.")
    next_ref = state.get("next_planned_reference")
    candidate = state.get("preflight_candidate")
    runnable_ids = ", ".join(task["id"] for task in runnable)
    if candidate and candidate.get("readiness_status") == "ready" and not candidate.get("blocked_by"):
        alternatives = f" or an open queued task ({runnable_ids})" if runnable_ids else ""
        decision_lines.append(
            f"Confirm whether to activate Preflight candidate {candidate.get('id')}{alternatives}; do not start it automatically."
        )
    elif candidate and candidate.get("readiness_status") != "ready":
        alternative = f" You may instead select an open queued task ({runnable_ids})." if runnable_ids else ""
        decision_lines.append(
            f"Candidate {candidate.get('id')} is not ready for execution; complete/revalidate its targeted Preflight and resolve recorded blockers first.{alternative}"
        )
    elif runnable and next_ref:
        decision_lines.append(
            f"Choose between an open queued task ({runnable_ids}) and explicitly preparing/activating the next roadmap reference {next_ref.get('id')}; do not choose automatically."
        )
    elif len(runnable) > 1:
        decision_lines.append("Select which open task becomes current focus; do not choose automatically.")
    elif len(runnable) == 1:
        decision_lines.append(f"Confirm whether to continue/start {runnable[0]['id']} before implementation.")
    elif next_ref:
        decision_lines.append(f"No runnable open task exists; explicitly choose whether to activate {next_ref.get('id')} or open another task.")
    else:
        decision_lines.append("No runnable open task exists; explicitly choose which task should be opened.")
    return (
        "# Preflight\n\n"
        "This is a read-only control gate. No implementation, scope expansion, or current-focus switch is authorized by this report.\n\n"
        + queue.replace("# Work Queue\n\n", "")
        + "\n## Approval required\n\n"
        + "\n".join(f"- {line}" for line in decision_lines)
        + "\n"
    )


def write_checkpoint_metadata(text: str, task: dict[str, Any]) -> str:
    fields = [
        ("Task lifecycle", "Task ID", task["id"]),
        ("Task lifecycle", "Status", task.get("status", "ready")),
        ("Task lifecycle", "Previous task", task.get("previous_task", "none")),
        ("Task lifecycle", "Relationship", task.get("relationship", "standalone")),
        ("Task lifecycle", "Resume target", task.get("resume_target", "none")),
        ("Task lifecycle", "Triggered by", task.get("triggered_by", "user request")),
        ("Work state", "Work type", task.get("work_type", "main-task")),
        ("Work state", "Priority", task.get("priority", "normal")),
        ("Work state", "Lane", task.get("lane", "main")),
        ("Work state", "Queue approval", "approved" if task.get("approved_for_queue", True) else "not approved"),
        ("Work state", "Execution approval", "approved" if task.get("approved_for_execution", False) else "required"),
        ("Work state", "Discovered during", task.get("discovered_during", "none")),
        ("Work state", "Blocks", join_ids(task.get("blocks", []))),
        ("Work state", "Blocked by", join_ids(task.get("blocked_by", []))),
        ("Work state", "Partially blocked by", join_ids(task.get("partially_blocked_by", []))),
        ("Work state", "Next exact action", task.get("next_action", "not recorded")),
        ("Work state", "Source reference", task.get("source_reference", "not recorded")),
    ]
    for section, label, value in fields:
        text = set_field(text, section, label, str(value), before="Scope")
    text = set_field(text, "Scope", "Goal", task.get("goal", task["id"]))
    text = set_field(text, "Scope", "Type", task.get("task_type", "implement"))
    text = set_field(text, "Scope", "Mode", task.get("mode", "balanced"))
    return text
