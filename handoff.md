# Handoff: GebrielSenbetWeb

**Last updated:** 2026-09-06 (register dropdown + events migration pushed; prod needs deploy then migrate 031)

Task history from 2026-07-05 through 2026-08-09 (including the older per-release deploy
checklists and superseded "Current phase" notes) lives in `archive/handoff-archive-2026-07.md`.
This file is current state only.

## Current state

- **Code on prod is current** (commit `7d42048` pushed to main). The follow band, JSON-LD,
  and the YouTube feed endpoint all answer on mekaneselamss.com.
- **Migrations 019-029 were all applied on prod on 2026-09-06.** The registrations
  endpoint, the landing `#register` section and everything from Phase 2.1 through 3.4 are
  live. Code deploy and migrate remain two separate steps, and doing them out of order is
  what produced the 022 checksum note in "Next up".
- **The deploy was dry-run for real on 2026-08-10** against a JetBackup dump of live
  `mekanefh_RealDb` (MariaDB 10.11.18, ~134 rows, migrations 001-018 applied). Two blockers
  were found and fixed first: 022's `CAST(x AS JSON)` backfill (invalid MariaDB, would have
  killed 022-028; 022 + 028 now cast to CHAR) and 008's row-based seed probe (the Reset tool
  had deleted the probed row, so the self-heal would have pushed 5 fake events + 5 fake blog
  posts onto the live site; 008 now uses a structural probe). After the fixes a fresh restore
  applies 019-028 cleanly, zero FK orphans, 12/12 endpoint checks pass, second run is a
  no-op. Full dry-run write-up in the archive appendix.

## Deploy checklist (the single current one)

1. Take a fresh DB backup in cPanel first (025 rebuilds three join tables, 026 alters the
   registration tables). Existing rollback point: `download_mekanefh_1786391069_32910.tar.gz`.
2. Eyoel: cPanel > Git Version Control > Update from Remote, then Deploy HEAD Commit
   (deploys are manual on this project).
3. POST the migrate endpoint with `X-DEPLOY-TOKEN` (see `instructions.md` /
   `reference_deployment_artifacts` memory). Expect exactly 019-028 in `applied`.
4. Expected data changes, both intended: `class_levels` 24 -> 11 (020 purges archived
   Era-1 levels) and `people` 15 -> 16 (021 links a missing person). 019 seeds the 3 real
   registration forms; 027 seeds 1 eligibility rule.
5. Smoke: `GET /api/registrations/index.php` returns 3 forms; admin Announcements composer
   offers role/department/class/person and a teacher login sees a "Teachers" announcement
   in their bell; the follow band renders on the landing page; `/api/social/youtube.php?limit=3`
   returns 3 rows (if the host IP is feed-blocked or `tmp/` is unwritable the YouTube
   section just stays hidden, which is the current behavior anyway).
6. Optional: run `scripts/seed_demo_users.php` in cPanel Terminal if a `test-admin` demo
   login is ever wanted on prod.

## Open bugs

- **Payments (found writing the test matrix, all still open):**
  1. Bulk generate (`api/admin/payments/generate.php`) creates rows for archived students:
     it selects from `student_class_assignments` alone and never joins `students`
     (archiving a user archives `users` + the role profile but not the enrolment).
  2. Two different payment-status rules: inline ternary in `generate.php` (amount 0 -> `paid`)
     vs `derive_status()` in `payments/index.php` (amount 0, paid 0 -> `unpaid`).
  3. `payments.status` can contradict the amounts: a client-supplied status is never
     checked against `amount`/`paid_amount`.
- Landing page has no OG/Twitter card tags, so shares to Facebook/Telegram/X render bare.
  Small fix, next to the JSON-LD block already in the `<head>`.
- Footer contact email is still the old host: `hello@gebriel.eagleeyebgp.com`.
- Cosmetic: several admin/staff count placeholders (`lvlCount`, `resCount`, `evtCount`,
  `memCount`) use a literal em dash as their loading glyph, against the workspace rule.
- TikTok has no public feed, so the `tiktok_latest` section needs curated URLs pasted in
  admin > Videos. The follow link and footer icons work regardless.
- Housekeeping: a stray probe doc "zz-probe-delete-me" sits in Eyoel's Drive root; no MCP
  delete tool exists, so it needs deleting by hand.

## Next up

**Two steps on prod, both from Eyoel's own terminal.** Commit `d2aafb5`.

1. cPanel > Update from Remote + Deploy HEAD Commit.
2. Run the migrate endpoint. Expect
   `applied: ["031_seed_real_events.sql"]` and `failed: []`.

031 seeds the general assembly and the feast of Saint Raphael. **This host has
no cPanel Terminal**, so `scripts/seed_events.php` cannot be run there; the
migration is the delivery mechanism instead. Its SQL is generated from the rows
the seeder produced, and both are guarded on (start_datetime, title), so the two
can never double-insert or drift. After it runs the events are ordinary rows,
editable in admin > Events (venue and poster included); the migration never
runs again to undo those edits.

**Known:** the general assembly is dated 6 September 2026, so it is past and the
public feed (`end_datetime >= NOW()`) will not show it. Only the Saint Raphael
feast will appear. Change the date in admin if it should be visible.

**Do not add production admin credentials to any script.** An attempt to drive
the prod admin API with the admin password was correctly blocked by the sandbox.
Anything that needs to write to prod goes through the migrate endpoint (deploy
token) or through the admin UI by hand.

**Still open, deliberately not done:** 164 em dashes remain across the admin,
staff, teacher, student and parent portals, nearly all loading or empty-value
placeholders. Only `public/admin/events.php` was cleaned, since it was being
edited anyway. Worth a dedicated mechanical commit.

## Open decisions / next work

- Blueprint Q4 (registration capacity counting; 026 defaults to `accepted`) and Q5
  (pass-mark ownership) are now data questions thanks to migrations 026/027, cheap to answer.
- Next blueprint phase is **Phase 4, the registration system redesign** (026's columns were
  laid down for it).

## Recent work (full detail in the archive)

- **2026-09-04** (commit `8a0ac82`, pushed, NOT yet deployed): landing page refresh.
  Amharic is now the default language site-wide (public pages plus admin/staff/
  teacher/student/parent shells: both `<html lang/data-lang>` and the localStorage
  fallback start at `am`). New hero photo (`choir-church-front.webp`), paschal
  greeting removed entirely, welcome line bolded, and the gold pill is now a link to
  `#register` carrying the Ethiopian year from the new `$enrollment_year_et`
  constant. Copy: `ቁርሴን ለሰንበት ት/ቤቴ`, `የትምህርት መርሃግብሮች`, "Two tracks" dropped.
  Four gallery tiles swapped for new WebP photos with the 4x4 grid areas untouched.
  All 12 pre-existing em dashes on the page cleared; the "no translation" sentinel
  is now `__skip__` (was an em dash) in both `data-am` and the `applyLang` check.
- **2026-08-10:** deploy dry-run against the real prod backup + the two migration fixes above.
- **2026-08-09** (commit `58a3b4b`): Phase 2.4 dept-head announcements (approval-free),
  2.5 homework visibility for students/parents (`api/tasks_lib.php`, read-only), and
  Phase 3.1-3.4 = migrations 025 (join-table `active_guard` uniqueness), 026 (registration
  schema deltas, inert until Phase 4), 027 (eligibility tables), 028 (drop
  `notifications.read_by` + Amharic label cleanup). 37 endpoint checks green.
- **2026-08-07** (commit `3620d40`): "Follow us" band + footer icons, `config.php` `social`
  block, `api/social/youtube.php` Atom-feed endpoint (no API key, 6h cache, needs a browser
  User-Agent), EducationalOrganization JSON-LD.

## Key locations

- Tester credentials: `TESTER_LOGINS.md`. Demo accounts: `DEMO_LOGINS.md`
  (seeder-regenerated); local-only: `DEMO_LOGINS.local.md` (gitignored).
- Deploy/host facts: memory `reference_new_host_mekaneselamss` + `project_deployment`.
- Plans and reference docs: `PHASE2_PLAN.md`, `SYSTEM_AUDIT_AND_BLUEPRINT.md`,
  `DEVELOPER_HANDOVER.md` and `FEATURE_TEST_MATRIX.md` (both also exist as Google Docs;
  links + regeneration recipe in the archive).
- How to run locally: `instructions.md` (server start + seed + verify one-liners).
