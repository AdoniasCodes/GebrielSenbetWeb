# Handoff — GebrielSenbetWeb

## Current phase
PHASE 2.1 COMPLETE (2026-07-17): notification engine v1 built + verified locally (21/21 lib E2E, HTTP producer/reader chains green). NOT yet committed or deployed. Migrations 019+020+021 STILL pending on prod, and now 022 too. Next in Phase 2: 2.2 grade finalization, 2.3 term-scoped attendance, 2.4 dept-head announcements (approval-free — decided), 2.5 tasks/homework exposure. Full plan in `PHASE2_PLAN.md`.

## Phase 2.1 summary (2026-07-17) — notification engine v1
The pre-2.1 bug: composer target list, UI dropdown, and each portal's reader query were three drifted lists. 5 of 8 composer targets wrote rows no reader could match (admin→Teachers announcements were silently discarded forever); the 2 targets readers supported (department, user) couldn't be written by admin.
- **`api/notifications_lib.php` (new)**: single target contract. `NOTIFY_TARGETS` (role|department|class|user), `notify()` choke point + typed wrappers, `notif_audience_clause()`/`notif_inbox_query()` reader builders, `notif_mark_read()` (INSERT IGNORE), `notif_department_head_user_ids()` (compound "dept heads" audience fans out to user rows). A writable target is by construction a readable one — drift is now structurally impossible.
- **Migration `022_notification_reads.sql`**: per-user read state moves from `notifications.read_by` JSON (had a lost-update race) into a join table; JSON_CONTAINS backfill (MariaDB-safe); `migration_022_applied` marker + migrate.php probe. `read_by` left in place (Phase 3 drops it) so release is rollback-safe.
- **Composer** (`api/admin/announcements/index.php` + `public/admin/announcements.php`): targets → role|department|class|user; dropped subject/payment_defaulters/event (dead), added department + user pickers, role list gained staff. Routes through notify().
- **Readers**: teacher (was user-only → now user+role:teacher+department+class, the biggest gap), student, parent moved onto shared clause + unread. NEW inboxes: staff (`api/staff/notifications.php`) and admin (`api/admin/inbox/`) — required so new producers land somewhere. UI: bell+panel in staff portal header and admin shell (`page-shell.php`/`page-shell-end.php`, works on every admin page).
- **Producers via notify()**: event proposed→dept heads; event approved/rejected→proposer (staff + admin); registration submitted→admins+dept heads; payment generated→students (batched after commit); dept assignment migrated onto notify(); demo-reset null-payload row fixed. NOTE: "registration decided→applicant" DEFERRED to Phase 4 (submissions have no user link; status is internal triage new|seen|contacted, not applicant-facing).
- Decision locked: announcements stay approval-free (blueprint open Q#6 resolved).

## Deploy checklist for Phase 2.1 (after commit)
1. Eyoel: cPanel → Git Version Control → Update from Remote + Deploy HEAD Commit.
2. Hit migrate endpoint → expect 019, 020, 021, 022 applied.
3. Smoke: admin Announcements composer now offers role/department/class/person; send to Teachers → a teacher login sees it in their bell (the headline fix). Staff + admin bells populate.

## Phase 1 summary (2026-07-13)
1. **1.3 security**: `form.create` removed from `api/staff/registrations.php` (admin-only, verified 403 + no row). `api/setup/demo_logins.php` hardened: never seeds admin, random per-run password, auto-archives + rotates the legacy `demo@` admin if present (verified). Prod checked: backdoor login already rejected (401). Bonus fix: `reg_create_form` fataled when `status` omitted (bad ternary) — fixed.
2. **1.4 admin event oversight**: `api/admin/events` gained `?status=` filter, creator email, and POST `{action:'approve'|'reject', id}`; admin events page now shows true approval status (Pending/Approved/Rejected pills), pending count + filter, approve/reject buttons, dept + proposer line. Verified: pending hidden from public feed until approved.
3. **1.2 single academic hierarchy**: migration `020_academic_hierarchy_cleanup.sql` (backfills `classes.department_id` → timhirt, purges unreferenced archived Era-1 tracks/levels, `migration_020_applied` marker probe). Deleted: `admin/legacy.php`, `admin/users.php`, dead teacher/student endpoints + redirect stubs (10 files, zero references confirmed).
4. **1.1 identity unification**: migration `021_identity_unification.sql` (every student/teacher incl. archived linked to a `people` row; parent + staff logins get people rows; marker probe). `create_person_account()` now creates people rows for parent/staff (admin excluded). Parents endpoint rewired through the shared lib: full_name/phone now stored on people (they were silently dropped before), GET returns them, PUT edits them, DELETE archives the person. Parents admin page gained Name/Phone fields + column. Verified end-to-end: 0 unlinked rows, no duplicate people, CRUD green, portals green.

## Deploy checklist for this release
1. Eyoel: cPanel → Git Version Control → Update from Remote + Deploy HEAD Commit.
2. Hit the migrate endpoint (X-DEPLOY-TOKEN) → expect 019, 020, 021 applied.
3. Smoke: GET /api/registrations/index.php (3 forms); admin → Events shows status pills; admin → Parents shows Name column.

## Last completed task (2026-07-12, late)
System audit & blueprint (7 parallel read-only audit agents + synthesis). Headline findings: two unreconciled schema eras (triple identity: users+students/teachers+people; dual academic hierarchies); data dead-ends everywhere (dept attendance unread, eligibility computed then discarded, grades feed nothing, submissions never become students); no promotion/rollover/graduation anywhere; admin registrations page wiring is CORRECT, the "broken" feel is the irreversible archive (no unarchive UI) + applicant-name heuristic; staff `form.create` restriction is UI-only (API allows it, security-relevant); notification system has one producer. Demo-admin backdoor decision (FABLE_BUG_REPORT #1/#5) still open.

## Previous completed task (2026-07-12)
Landing content overhaul + customizable public registrations + new logo (multi-agent build, QA'd end-to-end locally):
- **Landing (`public/index.php`)**: new hero H1/subtext (paschal greeting untouched), Mission → "Core Mission & End Goal", gallery text tweak, Three Pillars → 3 registration announcement cards (live status badges), Features → 7 Core Academic Subjects, Roles → Abnet Traditional Education, building-campaign section gained a touch-swipeable progress slider (2 real progress photos + 4 renders, scroll-snap), new `#register` section near footer with dynamic form renderer.
- **Registration system**: migration `019_registrations.sql` (forms/fields/submissions + seeds: sunday-school→timhirt, begena→mezmur, gishen-pilgrimage→guzo; probe entry added in `migrate.php`). Public API `api/registrations/` (GET forms, POST submit w/ CSRF+honeypot+validation+flood guard). Admin CRUD `api/admin/registrations/` + page `public/admin/registrations.php` (nav: Community → Registrations). Dept-scoped `api/staff/registrations.php` + section in staff portal — dept heads customize fields/status of their own forms only (verified: cross-dept 403). Shared logic in `api/registrations_lib.php`.
- **Logo/favicon**: new circular seal (`public/images/logo-mekane-selam.*`) replaced the placeholder star SVG on landing, login, blog, all portals, admin shell; favicons added site-wide (site previously had none).
- QA: full curl round trips (submit → DB → admin + dept dashboards), headless-Chrome visual pass, em-dash audit.

## Deploy checklist for this release
1. Push is done; Eyoel: cPanel → Git Version Control → Update from Remote + Deploy HEAD Commit.
2. Hit the migrate endpoint (X-DEPLOY-TOKEN, see `reference_deployment_artifacts` memory / instructions.md) to apply migration 019.
3. Smoke: GET /api/registrations/index.php returns 3 forms; submit one test registration; check /admin/registrations.php.

## Prod status (2026-07-05, RESOLVED)
- Migration `018_departments_teacher_workflows.sql` applied on prod via the migrate endpoint (017 was already applied). All 18 migrations now live.
- Root cause of broken demo logins: prod DB was never reseeded after the Phase A–D deploy. Fixed by running the admin Reset tool (`load_demo`) via API with Eyoel's approval — prod had almost no data (0 classes/grades/payments), so the wipe was harmless.
- Prod verify: 15/16 demo logins PASS with the TESTER_LOGINS.md password. `test-admin@` fails BY DESIGN — the Reset tool never creates a demo admin (only the seeder does, and it hasn't been run on prod). Testers don't need it; TESTER_LOGINS.md lists no admin account.

## Open items
1. Optional: run `scripts/seed_demo_users.php` in cPanel Terminal after next prod deploy if a `test-admin` demo login is ever wanted on prod.
2. YouTube channel RSS auto-fetch — still to build (long-standing).

## Key locations
- Tester credentials list: `TESTER_LOGINS.md` (shared password documented there)
- Demo account list: `DEMO_LOGINS.md` (regenerated by the seeder)
- Local-only credentials: `DEMO_LOGINS.local.md` (gitignored)
- Deploy/host facts: memory `reference_new_host_mekaneselamss` + `project_deployment`
- Master plan: `MASTER_PLAN.md`; prior handoff notes: `FABLE_HANDOFF.md`

## How to run locally
See `instructions.md` (server start + seed + verify one-liners).
