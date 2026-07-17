# Phase 2 — Workflow consistency

Source: `SYSTEM_AUDIT_AND_BLUEPRINT.md` §9 Phase 2. Phase 1 completed 2026-07-13.

## Decisions locked (2026-07-15)
- **Announcements stay approval-free** (blueprint open question #6 — RESOLVED). Only events require approval.
- **Dead composer targets are removed**, not implemented: `subject`, `payment_defaulters`, `event`. Nothing is lost — no reader has ever existed for them, so no user has ever received one. `payment_defaulters` returns in Phase 6 with the real payments flow.
- **Unread moves to a `notification_reads` table** (migration 022), replacing the `read_by` JSON array.

---

## 2.1 Notification engine v1 (in progress)

### The problem, precisely
`$validTargets` (`api/admin/announcements/index.php:20`), the composer dropdown
(`public/admin/announcements.php:37-41,82`), and each portal's reader query are three
independently maintained lists that have drifted. Result: 5 of the composer's 8 effective
target choices write rows **no query can ever match**, and the 2 targets readers *do*
support (`department`, `user`, added by migration 018) **cannot be written by an admin**.

| Composer writes | Reader exists | Status |
|---|---|---|
| `role:student` | student dashboard | works |
| `role:parent` | parent announcements | works |
| `role:teacher` | — | **silently discarded** |
| `role:admin` | — | **silently discarded** |
| `class` | student + parent | works (teachers can't see it) |
| `subject` | — | **silently discarded** |
| `payment_defaulters` | — | **silently discarded** |
| `event` | — | **silently discarded** |
| `department` | student + parent | **admin cannot write it** |
| `user` | teacher | **admin cannot write it** |

### The fix: one choke point
`api/notifications_lib.php` becomes the single definition of the target contract. Producers
go through `notify()`; readers go through `notif_audience_clause()`. Both derive from the
same `NOTIFY_TARGETS` map, so a target that can be written is by construction a target that
can be read. The three-lists-drift failure mode becomes structurally impossible.

**Target contract after 2.1** (4 types, down from 7):

| target_type | payload | audience |
|---|---|---|
| `role` | `{role}` — admin/teacher/student/parent/staff | every user with that role |
| `department` | `{department_id}` | every member of the department (any role) |
| `class` | `{class_id}` | students in the class, their parents, and the class's teachers |
| `user` | `{user_id}` | one person |

`is_public=1` is orthogonal and stays as-is (drives the public landing feed).

### Compound audiences: fan-out, not a new target type
"Dept heads of department X" is `role=staff AND department=X` — an AND across two axes the
single-axis target model can't express. Rather than add a compound `department_role` type,
producers **resolve to individual `user` rows at produce time**. At this scale (a handful of
heads) the row count is trivial, `user` targeting already works, and it leaves an auditable
record of exactly who was notified. Helper: `notif_department_head_user_ids($pdo, $deptId)`
(reads `department_memberships.is_head=1`).

### Work items
1. **Migration `022_notification_reads.sql`**
   - `notification_reads(id, notification_id, user_id, read_at)`, unique on
     `(notification_id, user_id)`, FKs cascade on delete.
   - Backfill from `read_by` via `JSON_CONTAINS` (portable across MySQL 5.7+ / MariaDB 10.2+;
     avoids `JSON_TABLE`, which MariaDB lacks before 10.6).
   - `migration_022_applied` marker in `app_settings` + probe in `api/admin/deploy/migrate.php`
     (follows the 020/021 pattern).
   - `notifications.read_by` is left in place but no longer written — removed in Phase 3's
     enum/column debris pass, so this release stays rollback-safe.
2. **`api/notifications_lib.php`** — `notify()` + validated `NOTIFY_TARGETS`, typed wrappers
   (`notify_user`/`notify_role`/`notify_department`/`notify_class`),
   `notif_audience_clause()` reader builder, `notif_mark_read()` (INSERT IGNORE — fixes the
   lost-update race at `api/teacher/notifications.php:57`), `notif_department_head_user_ids()`.
   Seeded from the row-shape contract already documented in
   `notify_department_assignment()` (`api/person_accounts_lib.php:134`).
3. **Composer** — `$validTargets` → `role|department|class|user`; dropdown loses subject /
   payment_defaulters / event, gains department + user (with pickers); role list gains `staff`.
4. **Readers** — all five portals through the shared clause, all with unread:
   - teacher: `user`-only → `user|role:teacher|department|class` (closes the biggest gap:
     a dept head's department post currently reaches students and parents but not teachers)
   - student, parent: move onto the lib, gain `user` target + unread
   - **staff: new inbox** (`role:staff|department|user`) — required, or "event proposed"
     has nowhere to land
   - **admin: new inbox** (`role:admin|user`) — required, or "registration submitted"
     has nowhere to land
5. **Producers** (all via `notify()`):
   - event proposed → dept heads of the event's dept (`api/teacher/events.php:57`)
   - event approved/rejected → proposer (`api/staff/events.php:59`, `api/admin/events/index.php:72`)
   - registration submitted → admins + dept heads (`api/registrations/submit.php`)
   - ~~registration decided → applicant~~ **DEFERRED to Phase 4.** Submissions have no
     user/person link and the status enum is internal triage (`new|seen|contacted`), not an
     applicant-facing accept/reject. There is no account to notify until the Phase 4 redesign
     adds accept/reject/waitlist + convert-to-person.
   - payment generated → student (batched after commit, `api/admin/payments/generate.php:62`)
   - dept assignment → migrate the existing helper onto `notify()` (behavior unchanged)
   - demo reset → fix the null-payload row (`api/admin/reset-data/index.php:188`)

### Conventions to preserve
- **`JSON_UNQUOTE` on every bound-param JSON comparison.** `ATTR_EMULATE_PREPARES=false`
  sends typed params that a raw `JSON_EXTRACT` scalar won't match. Bare comparisons only work
  for string literals. Documented at `api/teacher/notifications.php:15-18`.
- Bilingual EN/አማርኛ titles on anything user-facing.
- Public delegates in `public/api/` are one-line `require_once` into repo-root `api/`.

### Verification
- Unit-ish: every `NOTIFY_TARGETS` entry has a matching audience clause branch (assert both
  maps agree — this is the regression guard against future drift).
- E2E per producer: trigger → row written → appears in the right inbox → absent from every
  other inbox → mark-read is idempotent under repeat calls.
- Regression: admin → Teachers announcement now actually arrives (the headline bug).

---

## 2.2–2.5 (not started)
2. Grade finalization: per (class, subject, term) lock + `updated_by`, term-close action.
3. Term-scoped attendance: `term_id` on sessions, per-student percentages to teachers + dept heads.
4. Dept-head announcements: mirror the events pattern, approval-free per the locked decision.
5. Tasks/homework: expose to students + parents, or explicitly park.
