from __future__ import annotations

import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SKILL = Path(__file__).resolve().parents[1]
INIT = SKILL / "scripts/init_context.py"
TASK = SKILL / "scripts/task_session.py"


class TaskSessionTests(unittest.TestCase):
    def run_cmd(self, *args: str, ok: bool = True) -> subprocess.CompletedProcess[str]:
        result = subprocess.run([sys.executable, *args], capture_output=True, text=True)
        if ok and result.returncode != 0:
            self.fail(f"command failed: {args}\nstdout:\n{result.stdout}\nstderr:\n{result.stderr}")
        return result

    def load_state(self, root: Path) -> dict:
        return json.loads((root / ".ai-context/PROJECT-STATE.json").read_text(encoding="utf-8"))

    def pass_verification(self, root: Path, task_id: str) -> None:
        """Simulate a passing verify.py run so the completion gate is satisfied."""
        try:
            head = subprocess.run(["git", "-C", str(root), "rev-parse", "HEAD"],
                                  capture_output=True, text=True).stdout.strip() or "unavailable"
        except Exception:
            head = "unavailable"
        report = {"schema_version": 1, "task_id": task_id, "git_commit": head,
                  "acceptance_criteria_covered": [], "overall_pass": True,
                  "steps": [{"step": "test", "passed": True, "required": True, "exit_code": 0}]}
        (root / ".ai-context/verification-report.json").write_text(json.dumps(report), encoding="utf-8")

    def test_parallel_queue_gap_gates_and_resume(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            self.run_cmd(str(INIT), str(root))
            self.assertFalse((root / ".ai-context/CURRENT-TASK.md").exists())

            self.run_cmd(
                str(TASK), str(root), "new",
                "--task-id", "TASK-008",
                "--goal", "TASK-008 timezone cutoff",
                "--work-type", "main-task",
                "--priority", "high",
                "--lane", "backend",
                "--next-action", "Implement cutoff service",
                "--readiness-confirmed",
                "--approved",
            )
            state = self.load_state(root)
            self.assertEqual(state["current_focus"], "TASK-008")
            self.assertEqual(state["tasks"]["TASK-008"]["status"], "active")

            self.pass_verification(root, "TASK-008")
            self.run_cmd(
                str(TASK), str(root), "complete",
                "--result", "TASK-008 completed",
                "--tests", "focused tests passed",
                "--next-task", "TASK-009",
                "--next-source", "docs/plan.md#task-009",
            )
            state = self.load_state(root)
            self.assertEqual(state["last_completed"]["id"], "TASK-008")
            self.assertNotIn("TASK-009", state["tasks"])
            self.assertEqual(state["next_planned_reference"]["id"], "TASK-009")

            self.run_cmd(
                str(TASK), str(root), "new",
                "--task-id", "UI-COMPONENTS-001",
                "--goal", "Implement Figma UI components in stages",
                "--work-type", "parallel-prep",
                "--priority", "low",
                "--lane", "frontend",
                "--next-action", "Implement form validation states",
                "--readiness-confirmed",
                "--approved",
            )
            self.run_cmd(
                str(TASK), str(root), "propose",
                "--task-id", "API-RESPONSE-GAP-001",
                "--goal", "Add response contract for UI validation",
                "--work-type", "gap",
                "--lane", "integration",
                "--impact", "UI validation cannot finish",
                "--blocking-effect", "full",
                "--blocks", "UI-COMPONENTS-001",
            )
            state = self.load_state(root)
            self.assertIn("API-RESPONSE-GAP-001", state["proposals"])
            self.assertNotIn("API-RESPONSE-GAP-001", state["tasks"])
            self.assertEqual(state["proposals"]["API-RESPONSE-GAP-001"]["priority"], "low")

            denied = self.run_cmd(
                str(TASK), str(root), "approve", "--task-id", "API-RESPONSE-GAP-001", ok=False
            )
            self.assertNotEqual(denied.returncode, 0)

            self.run_cmd(
                str(TASK), str(root), "approve",
                "--task-id", "API-RESPONSE-GAP-001",
                "--approved",
            )
            state = self.load_state(root)
            self.assertEqual(state["tasks"]["UI-COMPONENTS-001"]["status"], "blocked")
            self.assertEqual(state["tasks"]["API-RESPONSE-GAP-001"]["status"], "ready")
            self.assertFalse(state["tasks"]["API-RESPONSE-GAP-001"]["approved_for_execution"])
            self.assertNotIn("TASK-009", state["tasks"])

            self.run_cmd(
                str(TASK), str(root), "start",
                "--task-id", "API-RESPONSE-GAP-001",
                "--approved",
            )
            state = self.load_state(root)
            self.assertEqual(state["current_focus"], "API-RESPONSE-GAP-001")
            self.assertEqual(state["tasks"]["API-RESPONSE-GAP-001"]["status"], "active")

            self.pass_verification(root, "API-RESPONSE-GAP-001")
            self.run_cmd(
                str(TASK), str(root), "complete",
                "--result", "API contract gap resolved",
                "--tests", "contract tests passed",
            )
            state = self.load_state(root)
            self.assertEqual(state["tasks"]["UI-COMPONENTS-001"]["status"], "ready")
            self.assertIsNone(state["current_focus"])

            self.run_cmd(
                str(TASK), str(root), "start",
                "--task-id", "UI-COMPONENTS-001",
                "--approved",
            )
            state = self.load_state(root)
            self.assertEqual(state["current_focus"], "UI-COMPONENTS-001")
            self.assertEqual(state["next_planned_reference"]["id"], "TASK-009")

            output = self.run_cmd(str(TASK), str(root), "preflight").stdout
            self.assertIn("UI-COMPONENTS-001", output)
            self.assertIn("TASK-009", output)
            self.assertIn("Queue membership: none until explicitly activated", output)

    def test_priority_override_requires_explicit_approval(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            self.run_cmd(str(INIT), str(root))
            self.run_cmd(
                str(TASK), str(root), "new",
                "--task-id", "UI-LOW-001",
                "--goal", "Low priority UI prep",
                "--work-type", "parallel-prep",
                "--priority", "low",
                "--lane", "frontend",
                "--readiness-confirmed",
                "--approved",
            )
            self.run_cmd(
                str(TASK), str(root), "propose",
                "--task-id", "GAP-HIGH-001",
                "--goal", "Attempt high priority gap",
                "--work-type", "gap",
                "--priority", "high",
                "--impact", "Only blocks low priority UI prep",
                "--blocking-effect", "full",
                "--blocks", "UI-LOW-001",
            )
            denied = self.run_cmd(
                str(TASK), str(root), "approve",
                "--task-id", "GAP-HIGH-001",
                "--approved",
                ok=False,
            )
            self.assertIn("priority", denied.stderr.lower())
            self.run_cmd(
                str(TASK), str(root), "approve",
                "--task-id", "GAP-HIGH-001",
                "--approved",
                "--priority-override-approved",
            )

    def test_candidate_preflight_gap_then_activation(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            self.run_cmd(str(INIT), str(root))
            self.run_cmd(str(TASK), str(root), "set-next", "--task-id", "TASK-009", "--source", "docs/plan.md#task-009")
            self.run_cmd(
                str(TASK), str(root), "candidate",
                "--task-id", "TASK-009",
                "--goal", "Implement TASK-009 catalog domain",
                "--work-type", "main-task",
                "--priority", "high",
                "--lane", "backend",
                "--source-reference", "docs/plan.md#task-009",
            )
            state = self.load_state(root)
            self.assertEqual(state["preflight_candidate"]["id"], "TASK-009")
            self.assertNotIn("TASK-009", state["tasks"])

            self.run_cmd(
                str(TASK), str(root), "propose",
                "--task-id", "REQ-CATALOG-001",
                "--goal", "Resolve catalog DTO contract mismatch",
                "--work-type", "task-requirement",
                "--lane", "integration",
                "--impact", "TASK-009 cannot start safely",
                "--blocking-effect", "full",
                "--blocks", "TASK-009",
            )
            state = self.load_state(root)
            self.assertEqual(state["proposals"]["REQ-CATALOG-001"]["priority"], "high")
            self.run_cmd(str(TASK), str(root), "approve", "--task-id", "REQ-CATALOG-001", "--approved")
            state = self.load_state(root)
            self.assertEqual(state["preflight_candidate"]["readiness_status"], "blocked")
            self.assertEqual(state["tasks"]["REQ-CATALOG-001"]["status"], "ready")

            denied = self.run_cmd(str(TASK), str(root), "activate-candidate", "--approved", ok=False)
            self.assertIn("readiness", denied.stderr.lower())
            self.run_cmd(str(TASK), str(root), "start", "--task-id", "REQ-CATALOG-001", "--approved")
            self.pass_verification(root, self.load_state(root).get("current_focus", "unspecified"))
            self.run_cmd(str(TASK), str(root), "complete", "--result", "Catalog contract resolved", "--tests", "contract checks passed")
            state = self.load_state(root)
            self.assertEqual(state["preflight_candidate"]["readiness_status"], "pending-revalidation")

            self.run_cmd(
                str(TASK), str(root), "candidate-update",
                "--status", "ready",
                "--summary", "Targeted readiness checks passed after contract resolution",
                "--next-action", "Implement catalog schema and service",
            )
            self.run_cmd(str(TASK), str(root), "activate-candidate", "--approved")
            state = self.load_state(root)
            self.assertEqual(state["current_focus"], "TASK-009")
            self.assertIsNone(state["preflight_candidate"])
            self.assertIsNone(state["next_planned_reference"])


    def test_task_switch_restores_ledger_and_marks_changed_file_stale(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            source = root / "sample.txt"
            source.write_text("v1\n", encoding="utf-8")
            self.run_cmd(str(INIT), str(root))
            self.run_cmd(
                str(TASK), str(root), "new",
                "--task-id", "TASK-A",
                "--goal", "Read sample file",
                "--work-type", "main-task",
                "--readiness-confirmed",
                "--approved",
            )
            current = root / ".ai-context/CURRENT-TASK.md"
            text = current.read_text(encoding="utf-8")
            text = text.replace(
                "|---|---|---|\n",
                "|---|---|---|\n| sample.txt | sample contract | re-read |\n",
                1,
            )
            current.write_text(text, encoding="utf-8")
            ledger = SKILL / "scripts/ledger_state.py"
            self.run_cmd(str(ledger), "snapshot", str(root))
            self.run_cmd(
                str(TASK), str(root), "new",
                "--task-id", "TASK-B",
                "--goal", "Other task",
                "--work-type", "main-task",
                "--park-current", "paused",
                "--readiness-confirmed",
                "--approved",
            )
            source.write_text("v2\n", encoding="utf-8")
            self.run_cmd(
                str(TASK), str(root), "start",
                "--task-id", "TASK-A",
                "--park-current", "paused",
                "--approved",
            )
            restored = current.read_text(encoding="utf-8")
            self.assertIn("| sample.txt | sample contract | stale |", restored)


    def test_legacy_completed_current_becomes_last_completed_and_next_reference(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            context = root / ".ai-context"
            context.mkdir(parents=True)
            legacy = """# Current Task

## Session continuity

- Ledger schema: 1
- Task ID: TASK-017

## Scope

- Goal: Implement TASK-017 checkout
- Type: implement
- Mode: deep

## Evidence and checkpoint

- Next verification: TASK-018 must enforce cut-off.

## Read ledger

| Path | Purpose / symbols | Read state |
|---|---|---|

## Completion

- Result: TASK-017 completed
- Tests/checks: passed
- Final budget result: within deep mode
- Remaining risk: TASK-018 pending
"""
            (context / "CURRENT-TASK.md").write_text(legacy, encoding="utf-8")
            self.run_cmd(str(TASK), str(root), "migrate")
            state = self.load_state(root)
            self.assertEqual(state["last_completed"]["id"], "TASK-017")
            self.assertEqual(state["next_planned_reference"]["id"], "TASK-018")
            self.assertEqual(state["tasks"], {})
            self.assertIsNone(state["current_focus"])



if __name__ == "__main__":
    unittest.main()
