# acode — ayaa code skill

Portable, approval-first Codex skill untuk vibe coding lintas chat/session dengan multi-task queue, Preflight control gate, scoped repository inspection, per-task read ledger, dan mode-budget enforcement.

**Version: 0.5.0**

**Skill slug:** `acode`  
**Full name:** **acode — ayaa code skill**

## Tujuan utama

Saat membuka session baru pada project yang sama, agent dapat mengetahui tanpa membaca ulang seluruh development plan:

- task terakhir yang selesai;
- current focus dan next exact action;
- task lain yang paused, ready, atau blocked;
- gap/requirement yang masih proposed atau sudah masuk queue;
- priority, lane, dependency, dan rekomendasi urutan;
- next roadmap task yang belum pernah dimulai dan karena itu belum masuk queue.

## Perubahan v0.5.0

- Menambahkan `PROJECT-STATE.json` sebagai source of truth multi-task.
- Menambahkan `WORK-QUEUE.md` yang dihasilkan otomatis.
- Menambahkan persistent checkpoint `tasks/<TASK-ID>.md` dan ledger per task.
- Preflight sekarang wajib pada bootstrap session dan sebelum focus switch.
- Queue hanya memuat pekerjaan yang sudah dibuka atau disetujui; next roadmap task tetap reference.
- Menambahkan work type: `main-task`, `gap`, `task-requirement`, `parallel-prep`, `follow-up`.
- Menambahkan priority: `critical`, `high`, `normal`, `low`.
- Menambahkan dua approval gate untuk gap/requirement: approve queue, lalu approve execution.
- Menambahkan proposal, approve, reject, start, pause, checkpoint, dan set-next commands.
- Menambahkan full/partial blocker dan automatic dependency release setelah blocker selesai.
- Menambahkan migration dari v0.4.x tanpa menghapus archive.
- Mengubah token estimate menjadi planning-only indicator, bukan billing forecast.

## Instalasi

Salin folder `acode` ke:

```text
$HOME/.agents/skills/acode/
```

Restart Codex Desktop setelah instalasi atau upgrade.

### Migrasi dari nama skill sebelumnya

Bila sebelumnya terpasang sebagai `ayaa-dev-workflow`, hapus atau nonaktifkan folder skill lama setelah memasang `acode` agar tidak muncul sebagai dua skill berbeda. Tidak diperlukan migrasi `.ai-context`; format project memory v0.5.0 tetap sama dan langsung kompatibel. Pemanggilan skill berubah dari `$ayaa-dev-workflow` menjadi `$acode`.

## Inisialisasi project baru

```bash
python scripts/init_context.py /path/to/project --with-agents
```

## Upgrade project v0.4.x

```bash
python scripts/task_session.py /path/to/project migrate
```

Migration mempertahankan archive lama dan mengimpor current/suspended work yang masih terbuka jika dapat diidentifikasi dengan aman.

## Session bootstrap

```bash
python scripts/task_session.py /path/to/project preflight
```

Preflight bersifat read-only. Agent harus menampilkan queue, priority, blocker, proposed findings, rekomendasi, dan keputusan yang diperlukan sebelum editing.

## Contoh flow parallel development

### Task utama selesai, UI preparation masih terbuka

```text
TASK-008                  completed
UI-COMPONENTS-001         paused / low / parallel-prep
TASK-009                  next roadmap reference, not queued
```

### Menemukan API gap saat UI work

```bash
python scripts/task_session.py . propose \
  --task-id API-RESPONSE-GAP-001 \
  --goal "Add API response contract required by UI validation" \
  --work-type gap \
  --priority low \
  --lane integration \
  --impact "UI validation cannot finish" \
  --blocking-effect full \
  --blocks UI-COMPONENTS-001
```

Proposal tampil di Preflight tetapi belum berada dalam open queue.

Setelah user menyetujui pencatatan:

```bash
python scripts/task_session.py . approve \
  --task-id API-RESPONSE-GAP-001 \
  --approved
```

Setelah Preflight diperbarui dan user memilih gap tersebut untuk dikerjakan:

```bash
python scripts/task_session.py . start \
  --task-id API-RESPONSE-GAP-001 \
  --park-current blocked \
  --approved
```

Queue approval dan execution approval tidak boleh dianggap sebagai satu persetujuan yang sama.

## Membuka next roadmap task

Task yang belum pernah dimulai tidak ada di queue. Setelah user memilihnya secara tentatif, jadikan **Preflight candidate**, bukan langsung active task:

```bash
python scripts/task_session.py . candidate \
  --task-id TASK-009 \
  --goal "Implement TASK-009" \
  --work-type main-task \
  --priority high \
  --lane backend \
  --source-reference "docs/development-plan.md#task-009"
```

Agent lalu membaca hanya section plan dan context teknis minimum untuk readiness audit. Jika tidak ada gap:

```bash
python scripts/task_session.py . candidate-update \
  --status ready \
  --summary "Targeted readiness checks passed" \
  --next-action "Implement the validated TASK-009 scope"
```

Setelah Preflight ditampilkan dan user memberi execution approval:

```bash
python scripts/task_session.py . activate-candidate --approved
```

Jika ditemukan gap atau task requirement, catat sebagai proposal, jalankan queue approval, selesaikan blocker, lalu revalidasi candidate sebelum activation.

## Gradual checkpoint

```bash
python scripts/task_session.py . checkpoint \
  --next-action "Continue form validation states using the approved contract"
```

## Complete task

```bash
python scripts/task_session.py . complete \
  --result "TASK-008 completed" \
  --tests "Focused tests and build passed" \
  --next-task TASK-009 \
  --next-source "docs/development-plan.md#task-009"
```

`TASK-009` hanya menjadi next roadmap reference dan tidak otomatis masuk queue.

## Token report

Estimasi adalah planning-only indicator:

```text
Token report
- Planning estimate input: ~7k–12k visible tokens
- Planning estimate output: ~800–1.5k visible tokens
- Actual token: unavailable
- Note: not an API billing forecast; internal calls, repeated context, tools, cache, and reasoning may not be represented.
```
