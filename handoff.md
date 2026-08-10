# Handoff — GebrielSenbetWeb

## Deploy readiness (2026-08-10): dry-run against the real prod backup, 2 blockers fixed
Commit `7d42048`. Eyoel supplied a JetBackup dump of live `mekanefh_RealDb`; it was
restored into a scratch DB and the migrate endpoint was run against it for real.

**Prod baseline from the dump:** MariaDB 10.11.18, 35 tables, `schema_migrations`
holds 001-018, and the whole database is only ~134 rows (1 student, 1 teacher,
1 class, 1 enrolment; the rest is reference data). Events and blog_posts are empty.

**Two blockers found and fixed before any prod run:**
1. `CAST(x AS JSON)` is not valid MariaDB (its CAST has no JSON target type), and
   it sits in migration **022**'s backfill. The runner breaks on first hard
   failure, so 022 would have died and taken 023-028 with it. 022's own comment
   wrongly claimed the construct was MariaDB-safe. Both 022 and 028 now cast to
   CHAR, correct on both engines.
2. Migration **008**'s probe looked for a seeded row (`events.title='Sabbath
   Morning Service'`). Prod had been through the Reset tool so that row was gone,
   the self-heal read it as "008 never ran", pruned the tracker row and
   re-applied it: **5 fake events and 5 fake blog posts onto the live public
   site.** Proven against the backup. Seed migrations need a structural probe;
   008 now checks the events table exists.

**Result after the fixes, from a fresh restore:** 019-028 applied, `pruned: []`,
`failed: []`, events/blog_posts still 0, zero FK orphans, all operational data
preserved, and 12/12 endpoint checks pass (including the registrations endpoint
that 500s on prod today). A second run is a clean no-op, so the batch is idempotent.

Expected data changes on prod, both intended: `class_levels` 24 -> 11 (migration
020 purges the archived Era-1 levels) and `people` 15 -> 16 (021 links a missing
person). 019 seeds the 3 real registration forms; 027 seeds 1 eligibility rule.

**To deploy:** cPanel Update from Remote + Deploy HEAD Commit, then POST the
migrate endpoint with `X-DEPLOY-TOKEN`. Expect exactly 019-028 in `applied`.
The backup already taken (`download_mekanefh_1786391069_32910.tar.gz`) is the
rollback point.


## Last completed task (2026-08-09): Phase 2 and Phase 3 finished
Commit `58a3b4b`, pushed to main. **Migrations 019 through 028 are all still
unapplied on production** (see the deploy section below; this is now the single
biggest open item).

**Prod state as checked on 2026-08-09:** the *code* is deployed and current
(the follow band, JSON-LD and the YouTube feed endpoint all answer on
mekaneselamss.com, serving a real video from Aug 8). The *migrations* have never
run: `GET /api/registrations/index.php` still returns 500 because
`registration_forms` does not exist. Code deploy and migrate are two separate
steps and only the first has been done.

### Phase 2.4, dept-head announcements (approval-free, blueprint Q6)
`api/staff/announcements.php` is new: post to a headed department or to a class
inside it, list the whole department's traffic (teachers' posts included, each
tagged `is_mine`), retract your own. `is_public` is deliberately not exposed:
publishing to the public landing feed stays admin-only. The GET also returns the
department's classes so the class picker needs no second endpoint.
The listing reuses `notif_audience_clause()` instead of hand-rolling target
matching, so it cannot drift from how those rows are read elsewhere.
`api/teacher/announcements.php` was moved off its raw INSERT onto `notify()`,
which removes the last producer sitting outside the Phase 2.1 choke point.

### Phase 2.5, homework is no longer write-only
Teachers had been creating `tasks` that no student or parent could see.
`api/tasks_lib.php` is now the single definition of which tasks a student is
addressed by (class, grade and department scopes), and both the new student and
parent endpoints read through it, so the two views cannot disagree. Panels added
to both portals with overdue / due-today / due-soon chips, bilingual. The parent
view shows a child filter only when there is more than one child.
Read-only by design: there is no turn-in or submission flow.

### Phase 3.1, migration 025, join-table integrity
One active row per pair on `student_class_assignments`,
`teacher_subject_assignments` and `department_memberships`.
**The non-obvious part:** a plain `UNIQUE` would have permanently broken the
archive-then-re-add flow this schema depends on, because soft-deleted rows stay
in the table. Each table instead gets a generated `active_guard` column that is
1 while active and NULL once archived; MySQL treats NULLs as distinct inside a
unique index, so the constraint reads "at most one ACTIVE row, unlimited
archived history". Verified all three ways: duplicate active blocked, re-add
after archive allowed, re-add with two archived siblings allowed.
Duplicates are archived defensively first (local data was already clean).

Two paths needed hardening for this constraint, both fixed in the same commit:
- `api/setup/demo_logins.php` un-archived **every** matching membership row with
  no LIMIT, which would trip the constraint whenever archived siblings existed.
- Duplicate teacher assignments now return 409 with a real message instead of
  the generic 500 the duplicate-key throw would have produced.

**Delete policy: audited and deliberately left alone.** All 11 ON DELETE CASCADE
constraints are genuine owned-children and all 6 SET NULL are nullable actor
references; the other 44 are NO ACTION. That is already coherent, so churning it
on production would be risk without benefit. This closes the blueprint's
"inconsistent delete policy" item as "checked, nothing to do".

### Phase 3.2, migration 026, registration schema deltas
Columns only, per the blueprint: origin + event linkage, card presentation,
windowing, capacity (+ `capacity_counts` so the still-open counting policy is
data, not code), decision workflow kept as separate columns so the existing
triage `status` enum stays intact, person/student linkage, and explicit
`maps_to` applicant mapping replacing the English-label "name" heuristic.
`file` and `image` field types are **deliberately excluded** until the upload
pipeline exists. Everything stays inert because `REG_FIELD_TYPES` in
`api/registrations_lib.php` still gates what can be set.

### Phase 3.3, migration 027, eligibility tables
`eligibility_rules` + `eligibility_evaluations`. Rules carry their own
(context_type, context_id), so blueprint Q5 (pass-mark ownership: per
department, per grade-level, or global with overrides) is answerable as **data**
whichever way you decide, with no further migration. Seeded with the threshold
already sitting in `app_settings`, so the future engine starts life agreeing
with the current eligibility page rather than contradicting it. Nothing reads
these tables yet; `gs_compute_eligibility()` is untouched.

### Phase 3.4, migration 028 + label cleanup
Dropped `notifications.read_by` (migration 022 explicitly deferred this to
"Phase 3's column-debris pass") after re-running its backfill defensively, and
narrowed `target_type` to the four values `notify()` can actually produce.
Admin announcements now returns a real `read_count` from `notification_reads`
instead of the legacy JSON array no UI consumed.
Amharic label collision resolved: Departments and Classes both read **ክፍሎች**.
Now የአገልግሎት ክፍሎች vs የተማሪ ክፍሎች, and the nav says Grade levels / የክፍል ደረጃዎች,
matching the page it links to. Verified zero duplicate nav labels.

### Verification
Migrations 025-028 applied locally and inspected. 37 endpoint checks green
across admin, staff, teacher, student, parent and the public site. Headless
renders of every changed page in EN and አማርኛ, no console errors.
Test harnesses live in the session scratchpad (`api_test.py`, `portal.mjs`).

## Previous completed task (2026-08-07): YouTube + TikTok linked back onto the site
Commit `3620d40`, pushed to main. **Not deployed yet** (manual cPanel deploy still needed).

**Why they had disappeared:** nothing broke. `#tiktokSection` and `#youtubeSection` in
`public/index.php` ship with the `hidden` class and only unhide when `/api/videos/index.php`
returns a curated row from `video_embeds`. That table is operational data, so the admin Reset
tool wipes it, and after the last reset nobody re-pasted the links. The sections have been
silently empty ever since.

**What was built**
- `config/config.php` gained a `social` block: the single source of truth for both handles.
  YouTube `@MekaneSelam-m3j` (channel id `UC-Ybr6jVi_zCJ2wPdWv8H3A`), TikTok `@mekaneselamm`.
- New **Follow us** band directly above the footer on the landing page (`#follow`), using the
  existing `.scripture` dark treatment so it reads as native. Bilingual, two platform cards.
  Verified at 1440px and 390px, in EN and አማ.
- Icon links added to the landing footer's brand column and to the `blog.php` footer.
- `api/social/youtube.php` (+ `public/api/social/` delegate): reads the channel's **public Atom
  feed**, no API key and no OAuth. 6h disk cache in gitignored `tmp/social/`; if YouTube is
  unreachable it serves the stale cache rather than blanking the section.
- The YouTube section now prefers a curated `video_embeds` row and otherwise renders the three
  newest uploads from the channel: lead video as an embed, next two as thumbnail cards.
  This closes the long-standing "YouTube channel RSS auto-fetch" open item.
- `EducationalOrganization` JSON-LD with `sameAs` in the landing `<head>`, so search and AI
  answer engines tie both accounts to the school.

**Gotchas found (also in instructions.md)**
- The Atom feed is gated on User-Agent: no UA returns **500**, a custom bot UA returns **404**.
  Only a normal browser UA gets 200. The endpoint sends one.
- YouTube soft-blocks an IP after ~10 feed requests in a few minutes; a known-good control
  channel 404s too while blocked. It clears on its own. The dev machine was blocked at the end
  of the session, so the *live* fetch is unverified from here; the parser was unit-tested against
  real captured feed XML (4 videos, Amharic titles intact) and the full page was rendered from a
  primed cache.
- **TikTok cannot be auto-fetched.** The profile page ships no video IDs (the list is lazy-loaded
  via an internal API), so that section still needs curated URLs pasted in admin → Videos with
  section `tiktok_latest`. The follow link and footer icons work regardless.

**After deploying:** confirm the follow band renders, then check whether the YouTube section
populates from the server (`/api/social/youtube.php?limit=3` should return 3 rows). If the host's
IP is blocked or `tmp/` is not writable, the section just stays hidden, exactly as it is today.

## Previous completed task (2026-08-02): feature & data-flow test matrix
QA companion to `DEVELOPER_HANDOVER.md`, for walking every user flow and checking data
consistency end to end. Built by reading the actual endpoints (every `INSERT` target mapped, plus
the core libs and each portal's write paths) — not from the handover doc.
- **Repo:** `FEATURE_TEST_MATRIX.md`. **Google Doc:** "Mekane Selam Senbet School - Feature & Data
  Flow Test Matrix" — https://docs.google.com/document/d/12GPCphROe-BfVgJVEFLAOBkcUe9C5VVSt9FOluQYkOo/edit
- **Shape:** 16 sections, 19 tables, ~165 rows. Four columns: Feature / Data in / Data out /
  Test it + edge cases. Every value carries an origin tag — **[U]** user input, **[P]** from a
  previous process, **[A]** auto-generated, **[S]** settings or seed. The `[P]` values are the
  consistency-test targets.
- Sections 14 (7 cross-module chains), 15 (19 known inconsistencies), 16 (permission matrix).
- **Regenerating the Doc:** scratchpad `md2html.py` converts the .md (headings, tables, inline
  code, blockquotes, lists) → HTML, then Drive `create_file` with `contentMimeType: text/html`.
  Strip the repeated per-cell inline styles first (the file drops 78 KB → 59 KB) since the whole
  HTML has to be inlined in the tool call. Note: `<strong>` inside a table cell DOES import as real
  bold — the Drive read-back tool escapes it as `\*\*`, which is an export artifact, verified with
  a probe doc. **A stray probe doc "zz-probe-delete-me" is in Eyoel's Drive root; no MCP delete
  tool exists, so it needs deleting by hand.**

### Findings worth acting on (from writing the matrix)
1. **Archived students still get bulk-generated payment rows.** `api/admin/payments/generate.php`
   selects from `student_class_assignments` alone and never joins `students`; archiving a user
   archives `users` + the role profile but NOT the enrolment, `people` row, or dept memberships.
2. **Two different payment-status rules.** Inline ternary in `generate.php` (amount 0 → `paid`) vs
   `derive_status()` in `payments/index.php` (amount 0, paid 0 → `unpaid`). Same inputs, different
   status.
3. **`payments.status` can contradict the amounts** — a client-supplied status is never checked
   against `amount`/`paid_amount`.
4. **`api/teacher/announcements.php` writes to `notifications` with a raw INSERT** instead of
   going through `notify()`. The row shape is currently correct, but it sits outside the 2.1
   choke point, so it is exactly the drift 2.1 was built to prevent.

## Previous completed task (2026-08-02) — feature film on the landing gallery
`IMG_1671.MOV` (110 MB, 1080p30, 74s) optimized and placed in the **"Our family, in worship." /
ቤተሰባችን ፣ በአምልኮ።** section (`#life`), directly **above** the photo mosaic.
- **Assets (new dir `public/media/`)**: `life-together-720.mp4` — 720p H.264 high, CRF 28, preset
  slow, AAC 96k, `+faststart` (moov at byte 36) → **12 MB, 89% smaller** than source.
  `life-together-poster.webp` (103 KB, frame @54.5s, the blessing shot).
  VP9/WebM was encoded and **discarded** — libvpx came out at 18 MB, larger than the H.264, so
  shipping it would have been pure weight. Do not re-add it without re-measuring.
- **Markup** (`public/index.php`): `<figure>` with `preload="none"` + poster, so the section costs
  ~103 KB until someone presses play. Custom overlay play button (`#lifeFilmPlay`), bilingual
  label + figcaption.
- **`controls` is NOT in the HTML** — JS sets `film.controls = true` on play. First attempt left it
  on and the native control bar painted on top of the poster.
- **`.film-scrim` / `.film-label`** added to the `<style>` block. First attempt used `bg-ink/40`,
  then a weak linear gradient; both were far too light against a bright frame and the white label
  was illegible. Final scrim is a radial vignette over a linear gradient. Verified by screenshot,
  not by assumption.
- **Verified** with puppeteer-core against the local server: video sits before the mosaic in
  document order; pre-play `controls=false` + overlay visible; click → `paused=false`,
  1280x720 decoded, duration 74.35s, controls on, overlay hidden; `ended` → overlay returns,
  controls off, `currentTime` reset to 0; **zero console/page errors**. Desktop + 390px mobile
  screenshots both good.
- CSP needed no change (`default-src 'self'` covers self-hosted media; there is no `media-src`).

## PROD IS STALE — migrations 019–024 never applied (verified 2026-08-02)
`GET https://mekaneselamss.com/api/registrations/index.php` → **HTTP 500** (site root is 200).
That endpoint reads the `registration_forms` table from migration 019, so **019 through 024 are all
still unapplied on prod**. Live consequence: the landing page's `#register` section and the whole
public registration flow are broken in production, and everything Phase 2.1-2.3 added (notification
reads table, grade finalization, term-scoped attendance) is not live either.
**Unblock:** cPanel → Git Version Control → Update from Remote + Deploy HEAD Commit, then hit the
migrate endpoint with `X-DEPLOY-TOKEN` (see `instructions.md` / `reference_deployment_artifacts`
memory) → expect 019, 020, 021, 022, 023, 024 applied. Then re-check the URL above returns 3 forms.

## Current phase
PHASE 2.3 COMPLETE (2026-07-20): term-scoped attendance built + verified locally (5/5 HTTP E2E). 2.1 (96783b9) + 2.2 (e6a1edb) pushed; 2.3 committing now. Prod deploy still pending: migrations 019-024 all unapplied on prod. Next in Phase 2: 2.4 dept-head announcements (approval-free), 2.5 tasks/homework exposure. Full plan in `PHASE2_PLAN.md`.

## Phase 2.3 summary (2026-07-20) — term-scoped attendance
- **Migration 024**: `attendance_sessions.term_id` (FK academic_terms, ON DELETE SET NULL) + index;
  backfill by correlated date-range subquery (tie-break is_current, id); marker + probe.
- **`api/attendance_lib.php`**: `attendance_term_for_date()` (the single term-derivation rule, term
  whose [start,end] contains the date; gap → NULL) + `attendance_class_summary()` (per-student
  counts + canonical rate `(present+late)/(present+late+absent)`, excused excluded, class context).
- **Producers stamp term_id** on session insert: teacher class, teacher dept, admin attendance.
- **Teacher view**: `api/teacher/attendance/summary.php?class_id=&term_id=`; teacher Attendance tab
  gained an "Attendance this term" per-student table (class context only, current term).
- **Dept-head/admin view**: `gs_compute_eligibility($pdo,$dept,$termId)` gained an ADDITIVE
  `term_attended/term_total/term_rate` per member; `eligible` flag STILL uses all-time rate
  (no change to who can serve — that's Phase 6). Staff + admin eligibility tables show a
  "This term" column. New helper `gs_current_term_id()`.
- Student dashboard left as-is (all-time) by design — 2.3 targets teachers + dept heads only.
- Note: local dev DB class-84 attendance rows were cleared by the E2E (test data only; prod
  unaffected). Dept roll-call attendance (context_type='department') remains uncounted by
  eligibility, same as before — a known dead-end for a later phase.

## Phase 2.2 summary (2026-07-17) — grade finalization
Two locks with clear precedence (both consult `api/grades_lib.php`):
- **Soft lock (per gradebook = class+subject+term):** teacher self-service. Teacher marks their
  gradebook final (`grade_finalizations` row) → blocks the teacher's writes; ADMIN BYPASSES it.
  Teacher can reopen their own (while term open). Endpoint `api/teacher/grades/finalize.php`.
- **Hard lock (per term):** admin closes a term (`academic_terms.closed_at`) → blocks EVERY grade
  write, teacher AND admin, until admin reopens. Endpoint `api/admin/terms/close.php`; notifies
  role:teacher on close + reopen via the 2.1 engine.
- **updated_by:** `grades.updated_by_user_id` stamped on every teacher + admin write; admin grades
  GET returns updated_by_email + gradebook_finalized + term closed_at.
- **Migration 023**: grades.updated_by_user_id, grade_finalizations table, academic_terms.closed_at
  + closed_by_user_id, marker + probe.
- **Lock check order** (`grade_lock_reason`): term_closed (hard, everyone) beats finalized (soft,
  teacher only; admin passes isAdmin=true). Teacher writes return **423 Locked** when blocked.
- **UI**: teacher gradebook shows a lock banner + Finalize/Reopen button, disables inputs when
  locked; admin Terms page gains a Closed pill + Close/Reopen action.
- NOTE: gradebook-reopen notification dropped by design — only the finalizing teacher reopens (admin
  bypasses), so it would be a self-notification. Term close/reopen notifications are the real ones.

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
1. **PROD IS 10 MIGRATIONS BEHIND (019-028). This is the top priority.** Code is
   deployed and current; only the migrate step is outstanding. Until it runs,
   registrations 500 on prod and none of Phase 2.1-3.4 exists there. Deploy, then
   POST the migrate endpoint with `X-DEPLOY-TOKEN` and expect 019 through 028 in
   `applied`. 025 rebuilds three join tables and 026 alters the registration
   tables, so take a DB backup from cPanel first.
2. Blueprint decisions still open and now *cheap* to answer, because 027 made them
   data rather than schema: Q4 capacity counting (026 defaults to `accepted`) and
   Q5 pass-mark ownership.
3. Next blueprint phase is **Phase 4, the registration system redesign**, which is
   what 026's columns were laid down for.
4. Optional: run `scripts/seed_demo_users.php` in cPanel Terminal after the next
   prod deploy if a `test-admin` demo login is ever wanted on prod.
5. ~~YouTube channel RSS auto-fetch~~ DONE 2026-08-07 (`api/social/youtube.php`).
6. TikTok has no public feed, so `tiktok_latest` still needs URLs pasted in admin → Videos.
7. Landing page has no OG/Twitter card tags at all, so shares to Facebook/Telegram/X render bare.
   Small fix, worth doing next to the JSON-LD block already in the `<head>`.
8. Footer contact email is still the old host: `hello@gebriel.eagleeyebgp.com`.
9. Cosmetic: several admin/staff count placeholders still use a literal em dash as
   their loading glyph (`lvlCount`, `resCount`, `evtCount`, `memCount`). Pre-existing,
   against the workspace no-em-dash rule, worth a sweep when someone is next in there.

## Developer handover doc (2026-07-20)
Full onboarding doc for the new developer, grounded in the actual code (3 parallel audits: data model / API / frontend). Two forms:
- Repo: `DEVELOPER_HANDOVER.md` (canonical, renders on GitHub) + `DEVELOPER_HANDOVER.html` (import fallback). Committed f8b0ee0.
- Google Doc: "Mekane Selam Senbet School - Developer Handover" — https://docs.google.com/document/d/1FxoLoL1PC_rdmXT3IH9ZahxRIeO1BxgBOuXw12AxLBE/edit
Covers architecture (root api/ vs public/api shim split), auth/CSRF, identity + academic data model, feature inventory by role, ASCII data-flow diagrams, phase status (1 + 2.1-2.3 done vs planned), tech debt, deploy, local dev. Regenerate the Doc from the .md via scratchpad `md2html.php` then Drive create_file (text/html).

## Key locations
- Tester credentials list: `TESTER_LOGINS.md` (shared password documented there)
- Demo account list: `DEMO_LOGINS.md` (regenerated by the seeder)
- Local-only credentials: `DEMO_LOGINS.local.md` (gitignored)
- Deploy/host facts: memory `reference_new_host_mekaneselamss` + `project_deployment`
- Master plan: `MASTER_PLAN.md`; prior handoff notes: `FABLE_HANDOFF.md`

## How to run locally
See `instructions.md` (server start + seed + verify one-liners).
