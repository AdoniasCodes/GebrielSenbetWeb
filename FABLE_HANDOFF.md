# FABLE_HANDOFF.md — Fable 5 deep-work session log

Running log of what Fable 5 did, how it was verified, and what's next.
Written for a fresh model with zero context. Session date: 2026-07-03.

---

## TASK 1 — Teacher portal: reviewed, fixed, tested, COMMITTED (not pushed) ✅

**Commit:** `361bb2e` on `main` (local only — **awaiting user approval to push**).

### What existed (uncommitted from the 2026-06-22 session)
Teacher portal APIs (`api/teacher/{classes,roster,attendance}/index.php` + public delegates),
extended `api/teacher/_guard.php`, full bilingual portal UI in `public/teacher/index.php`.
Lint-clean but never executed.

### Bugs found & fixed during review + end-to-end testing
1. **roster/index.php — fatal 500 under ONLY_FULL_GROUP_BY** (MySQL error 1055, reproduced
   locally on MySQL 9.6): `GROUP BY s.id` while selecting LEFT-JOINed `g.id/g.score/g.remarks`.
   Fixed by deduping enrollments in a `SELECT DISTINCT` subquery and dropping GROUP BY entirely
   (the `uq_grade_unique` index guarantees ≤1 grade row per student). **This would have 500'd in
   production on every roster load.**
2. **roster/index.php — authz gap:** only asserted class ownership, not the (class, subject) pair,
   so a teacher could read grades for subjects they don't teach in their own class. Added
   `teacher_assert_class_subject()` to `_guard.php`; roster now requires+asserts subject_id/term_id.
3. **attendance/index.php POST — integrity hole:** accepted arbitrary `person_id`s into a class
   session (attendance feeds the serving-eligibility engine). Now validates person_ids against the
   class roster, and refuses to create a session if no valid records remain (no empty orphan
   sessions; all-invalid payload → 422).
4. **grades/index.php PUT — false 404 on unchanged values** (pre-existing bug): `rowCount()===0`
   was treated as "not found", but MySQL reports 0 affected rows for identical values. Every
   re-save of an unmodified row showed "N failed" in the portal. Removed the check (existence is
   already verified before the UPDATE).

### Demo wiring (production demo readiness)
Extended `api/setup/demo_logins.php`: after creating the 5 role logins it now wires
demo teacher ↔ class ↔ demo student — teacher_subject_assignment, student person profile
(`people` row + `students.person_id`), class enrollment, and one sample grade (never overwrites).
Only *creates* a term/class when none exist; never mutates real rows. Idempotent (tested 2×).
**After the next deploy, re-run:** `POST https://mekaneselamss.com/api/setup/demo_logins.php`
with header `X-Setup-Token: <setup_token from config.php>`.

### How it was verified (all local, PHP 8.5.7 + MySQL 9.6, ONLY_FULL_GROUP_BY on)
- Server: `APP_ENV=development APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel APP_DB_USER=eagleerq_gebriel APP_DB_PASS=gebrieldbpw php -S 127.0.0.1:8894 -t public`
- Test harness (Python urllib + cookie jar, scratchpad `test_teacher_portal.py`): **20/20 pass** —
  login → classes → roster → grade PUT → duplicate POST 409 → attendance GET/POST → persistence
  re-check → session reuse (no dupes) → 403 for un-owned class (GET+POST) → 403 without CSRF →
  422 for smuggled foreign person_id → teacher API 403 under a student session.
- DB rows verified directly via PDO (grade values, attendance record status, single session).
- Demo wiring verified: demo teacher login → sees class with 2 students → roster shows seeded
  grade → attendance saves for both; second run of demo_logins is a no-op.

### Next step for Task 1
User approves → `git push` → user deploys via cPanel (Update from Remote + Deploy HEAD Commit)
→ re-run demo_logins endpoint on prod.

---

## TASK 2 — Codebase bug hunt (fan-out + adversarial verification) — IN PROGRESS

Pre-checks already done inline (deterministic):
- **Delegate parity `api/**` vs `public/api/**`: CLEAN in both directions** as of `361bb2e`
  (checked with `comm` over sorted find lists, excluding `_guard.php`-style includes).
- **`api/admin/deploy/migrate.php` artifact probes: only 001–010 exist. Migrations 011–017 have
  NO probes** — confirmed lead handed to the workflow (impact depends on the runner's
  bootstrap/prune logic; verifier must read the code).

Findings report will land in `FABLE_BUG_REPORT.md`.
