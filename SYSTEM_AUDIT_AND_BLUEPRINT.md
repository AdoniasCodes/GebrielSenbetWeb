# System Audit & Architecture Blueprint

**Date:** 2026-07-12 · **Author:** Fable 5 (lead systems architect pass) · **Status:** PLANNING ONLY, no code changed.
**Method:** 7 parallel read-only audit agents (DB schema, Admin, Dept Head, Teacher, Student+Parent, Public/Registrations, Cross-module continuity) synthesized with MASTER_PLAN.md v2, FABLE_BUG_REPORT.md, and the current handoff. All file:line citations verified against the working tree at commit `5df998f`.

This document supersedes nothing; it feeds the next implementation cycle. MASTER_PLAN.md v2 remains the product vision (departments as self-running sub-orgs); this is the engineering audit and phased execution blueprint under it.

---

## 0. Executive summary

The system is functional on the surface (5 working portals, 35 tables, 19 migrations, decent per-endpoint security) but it is **an archipelago, not a platform**. The two biggest structural facts every other finding hangs off:

1. **Two schema eras were never reconciled.** Era 1 (migrations 001-010): generic school app with `users`/`students`/`teachers`, tracks/levels, classes, grades, payments. Era 2 (011-019): church domain model with `people`, `churches`, `departments`, attendance, resources, registrations. Era 2 was layered on top without retiring Era 1, so one human can exist as up to three unlinked-or-half-linked rows (`users`, `students`/`teachers`, `people`), and two academic hierarchies coexist behind an `is_archived` flag.
2. **Data flows in, almost nothing flows onward.** Grades feed nothing (no pass mark, no promotion, no lock). Department attendance is written and read by nobody. Eligibility is computed, displayed, and discarded. Registrations land in an inbox and never become people or students. The notification system has exactly one real producer. There is no promotion, no year rollover, no graduation concept anywhere in the code.

The registration system shipped this week is a solid v1 (secure submit path, bilingual field builder, dept scoping) but covers roughly a third of the target design: no event linkage, no capacity/deadline automation, no dynamic landing cards, no accept/reject, no person linkage, 9 of ~16 field types.

The roadmap in §9 sequences the fix: settle the architecture first (identity + academic model), then wire the broken workflows, then the database hardening, then build the registration system on the corrected foundation, then the Dept Head workspace UX, then automation.

---

## 1. System map (as built)

- **Stack:** vanilla PHP + vanilla JS + fetch, MySQL/InnoDB, soft deletes everywhere (`is_archived`). `public/api/**` are thin delegates into repo-root `api/**`.
- **Portals:** Admin (`public/admin/`, ~27 pages behind a shared page shell), Department Head (`public/staff/index.php`, one page), Teacher (`public/teacher/index.php`, SPA), Student (`public/student/index.php`), Parent (`public/parent/`), plus the public landing (`public/index.php`) and blog.
- **Auth:** session-based, per-tree `_guard.php` role gates, CSRF header on all writes, bcrypt, constant-time login. The 2026-07-03 security pass fixed 9 of 11 findings; **finding #1/#5 (demo admin `demo@mekaneselamss.com` / `demo1234`, a committed-to-repo admin backdoor once `api/setup/demo_logins.php` runs on prod) is still an open decision** (FABLE_BUG_REPORT.md).
- **DB:** 35 tables. Full inventory and ER map in the schema audit (§1 of the DB agent report, reproduced in condensed form in §7 below).

---

## 2. Per-role audit

Legend: ✅ complete · 🟡 partial · ⬜ not started.

### 2.1 Admin

**Complete:** dashboard stats; People CRUD (new model); Departments (tree, members, levels); Resources; Students/Teachers/Parents CRUD (old model); Tracks/Levels/Subjects/Classes/Terms; teacher-subject assignments; Payments (ledger, bulk generate from term tuition, CSV export); Grades entry; Attendance; Holidays + serving rota; Eligibility page (threshold + report); Announcements; Posts/Videos; Settings + password + current term; Reset tool (password-gated, load_demo / wipe_clean); Audit log; student CSV import/export; printable report card.

**Partial:**
- 🟡 **Events**: admin can CRUD events but has **no approval capability and no visibility into pending proposals**. Teacher proposals go `pending` and only dept heads can decide (`api/staff/events.php`). The admin events page shows a "Status" column header (`public/admin/events.php:76`) that actually renders archive state, never `pending/approved/rejected`. Admin POST always inserts `status='approved'` (`api/admin/events/index.php:75`); PUT never touches status.
- 🟡 **Registrations** (deep dive below, §4.6): wiring is actually **correct**; the "does not function" impression comes from capability gaps, chiefly that archiving a form is irreversible from the UI.
- 🟡 **Announcements**: admin can only target `role/class/subject/payment_defaulters/event` (`api/admin/announcements/index.php:20`), but readers only consume `public/role/class/department/user`. So admin cannot send the department- or user-targeted notices that portals actually display, and three of admin's target types are written but never read by anyone.
- 🟡 Bilingual gaps: `events.php` almost entirely English-only; English-only toasts on reset/eligibility/settings pages.

**Not started:** ⬜ Churches management page (API exists, no UI anywhere); ⬜ registration submissions export; ⬜ any oversight dashboards (pending events, new submissions, at-risk students).

**Legacy debris:** `public/admin/legacy.php` and `public/admin/users.php` are orphaned raw-HTML consoles, unlinked but URL-reachable; they bypass the page shell, bilingual layer, and modern JS helpers. Nav uses the same Amharic word ክፍሎች for both Departments and Classes (`page-shell.php:16` vs `:29`).

**Recommendations:** give admin the event-approval oversight view (or explicitly document that approval lives with heads and relabel the column); add archived-forms toggle + unarchive to registrations; add submissions CSV; retire the two legacy consoles; add Churches page; unify the dual create paths (People vs Students/Teachers) behind one flow; fix admin announcement targeting; close bilingual gaps.

### 2.2 Department Head (staff)

**Complete (within one long page):** dept picker (headed depts only); levels add/delete with member counts; read-only serving-eligibility table; resources upload/link/delete; event approve/reject queue + direct create (self-approved); members roster with add-existing (live search) / create-new (generated password) / level + title edit / remove; registration form customization (status, field builder, submissions triage) scoped to own departments (cross-dept 403 verified).

**Partial:**
- 🟡 Level rename/re-rank: API supports PUT (`api/staff/levels.php:51-71`), no UI control.
- 🟡 Submissions status filter exists server-side (`api/staff/registrations.php:30`), not exposed in UI.
- 🟡 Event flow: heads see a pending queue but get **no badge/notification** when proposals arrive; they must scroll to discover them.

**Not started:** ⬜ Overview/KPI dashboard; ⬜ announcements (no dept-head announcement feature exists at all, in UI or API); ⬜ attendance capture (heads can only view derived eligibility, never take attendance); ⬜ assignments; ⬜ reports/exports of any kind; ⬜ member communication; ⬜ class/section management inside the dept.

**Security finding (needs a decision):** the rule "dept heads cannot create registration forms" is **UI-only**. `api/staff/registrations.php:40-42` routes `form.create` into `reg_create_form()`, which explicitly allows scoped callers to create forms in departments they manage (`api/registrations_lib.php:191-196`). A head crafting a direct POST can create forms today. Either intended (then add the UI) or not (then remove the case server-side). The target design in §4 says heads should NOT create standalone forms, so the server-side removal is the recommended direction, with event-linked creation added later.

**UX:** the core complaint is confirmed structurally: one `#detail` container with **7 stacked panels, zero tabs, zero accordions**, nearly all forms inline and always-open (the members panel alone embeds two full forms; each registration form card expands into a field builder plus a paginated submissions table). Full redesign recommendation in §5.

### 2.3 Teacher

**Complete:** department-first navigation (chips, auto-select, "General (my classes)" fallback); grades entry per class+subject+term with server-side enrollment + assignment checks; class attendance and dept attendance (4 statuses, bulk mark, upsert); tasks CRUD; resources; announcements (post + list); events (propose, withdraw; head approves).

**Partial:**
- 🟡 Dashboard is just a picker: no ungraded counts, no attendance-due, no pending-proposal status.
- 🟡 Notifications: read UI exists but the **only producer in the entire system** is "you were assigned to a department" (`api/person_accounts_lib.php`, called from admin users + staff roster). Bell is empty in normal operation.
- 🟡 Announcements have no edit/delete (unlike tasks/events) and bypass any approval, while events require head approval: inconsistent moderation model.
- 🟡 Attendance: dept mode lists past sessions, class mode shows no history; neither shows percentages, though the server computes them for eligibility.
- 🟡 Grade saving is one HTTP request per student (no batch endpoint, partial-failure states).

**Not started / dead:** ⬜ grade finalization/locking (none anywhere: grades editable forever, no `updated_by`); ⬜ assessment components (single 0-100 score per subject-term, no quiz/exam weights); ⬜ holiday awareness in attendance. Dead code: `api/teacher/assignments/list.php`, `api/teacher/classes/students.php`, the GET branch of `api/teacher/grades/index.php`, and the `public/teacher/grades.php` redirect stub.

### 2.4 Student

**Complete:** dashboard (welcome, class, grades table, payments view, announcements with public/role/dept/class targeting, grade-scoped resources, average/graded stats, bilingual + Ethiopian dates).

**Partial:** 🟡 attendance shown as one lifetime percentage; present/late/absent/excused breakdown is computed server-side and discarded by the UI; no session history. 🟡 profile: DOB/guardian/phone returned by the API, never rendered.

**Not started:** ⬜ assignments/homework/feedback view; ⬜ events + event registration; ⬜ eligibility/promotion status; ⬜ department membership display; ⬜ communion. Dead: `public/student/grades.php` (redirect stub), `api/student/grades/index.php` (orphaned; its comment promises parent access its role gate forbids).

### 2.5 Parent

**Complete:** children list, family announcements, per-child grades + payments with solid ownership checks (allow-list from `student_guardians`, 403 on out-of-scope ids, server-side re-check on the child page).

**Not started:** ⬜ per-child attendance (nothing at all, not even the percentage the student sees); ⬜ per-child resources; ⬜ assignments/feedback; ⬜ events; ⬜ profile detail beyond name+class. Hygiene: parent accounts are `users`-row only (no `people` row) and `api/admin/parents/index.php` bypasses the shared `create_person_account()` helper entirely.

### 2.6 Public visitor

**Complete:** landing with 17 sections (hero, about, building campaign + swipeable progress slider, galleries, programs, subjects, Abnet, calendar, announcements, posts, TikTok/YouTube, enroll CTA, dynamic registration form + result modal); blog; hardened submit path (CSRF + honeypot + per-IP flood guard + strict server validation).

**Partial / missing:**
- 🟡 The 3 registration announcement cards are **hardcoded HTML** only decorated with live status badges; a 4th form in the DB gets a form tab but **no card**, and a deleted form leaves an orphaned static card (`public/index.php:448-497` vs the fully dynamic `#register` section).
- ⬜ SEO (commercial-project default, currently failing): no Open Graph/Twitter tags, no sitemap.xml, no robots.txt, no JSON-LD, no hreflang for the EN/AM content. One H1 and a meta description exist.
- 🟡 Security: no rate limiting on unauthenticated read feeds; CSP allows `script-src 'unsafe-inline'` + CDN Tailwind; `api/terms/index.php` lacks the method guard its siblings have.

---

## 3. Workflow atlas

For each workflow: origin → actors → data → storage → consumers → what should be automatic → missing connections. (Condensed; the continuity table in §6 gives the link-by-link status.)

| # | Workflow | Begins | Creates/edits | Approves | Views | Stored in | Consumed by | Missing connections |
|---|---|---|---|---|---|---|---|---|
| W1 | Person/account creation | Admin (People or Students/Teachers/Parents pages); Dept head (roster create-new) | Admin, dept head | nobody | admin, staff | `users` + `people` + `students`/`teachers` (parents: `users` only) | login, rosters | Registration submissions never enter this flow; parent creation bypasses shared lib; three identity tables drift |
| W2 | Academic year + terms | Admin types free-text year, creates terms | Admin | n/a | all portals (term pickers) | `academic_terms` | grades, payments | No rollover, no auto current-term switch, nothing happens at term end |
| W3 | Class enrollment | Admin assigns student→class | Admin | n/a | teacher rosters, student dashboard | `student_class_assignments` | grades auth check, attendance rosters | No unique constraint (duplicate enrollment possible); "promotion" is a manual per-student reassignment |
| W4 | Class attendance | Teacher (General mode) | Teacher | n/a | teacher (today only), student (lifetime % only) | `attendance_sessions(context=class)` + `attendance_records` | eligibility engine, student stat | Not term-scoped; no history UI for class mode; parents see nothing; no holiday awareness |
| W5 | Dept attendance | Teacher (dept mode) | Teacher | n/a | teacher (same screen only) | `attendance_sessions(context=department)` | **nothing** | Pure dead-end: eligibility reads class context only |
| W6 | Grading | Teacher picks class+subject+term | Teacher (also admin page) | nobody, ever | student, parent, admin, report card | `grades` (one score/subject/term) | display only | No lock/finalize, no pass mark, no promotion input, no curriculum validation against `grade_subjects` |
| W7 | Eligibility | Admin/staff opens page | computed on the fly | n/a | admin, staff | **nowhere** (never persisted) | **nothing** | Result never gates serving assignments, registration, or promotion; attendance-only; single global threshold |
| W8 | Events | Teacher proposes (`pending`) or head/admin creates (`approved`) | teacher, head, admin | **Dept head only**; admin blind | head queue, public feed (approved+future) | `events` (+ status, dept, approved_by) | landing calendar | No notifications either direction; admin has no oversight; no event↔registration link; students/parents have no event surface |
| W9 | Announcements | Admin (role/class/+3 dead targets) or teacher (dept/class, no approval) | admin, teacher | nobody | student, parent, teacher-own, public (is_public) | `notifications` | portals + landing | Admin can't emit `department`/`user` targets readers consume; 3 admin target types have zero readers; dept heads have no announcement feature; teachers can't edit/delete |
| W10 | Tasks (homework) | Teacher, dept mode | Teacher | n/a | teacher only | `tasks` (polymorphic scope, no FK) | **no student/parent surface** | Students never see the homework; no submissions/feedback loop; admin assignments page manages teacher-subject links, an unrelated concept sharing the name |
| W11 | Resources | Admin, staff, teacher upload | same | n/a | student (grade scope), staff, teacher | `resources` (polymorphic, no FK) | portals | Parents have no view; no class/student-level scope yet (planned T2) |
| W12 | Payments | Admin bulk-generates from term tuition | Admin | n/a | admin, student, parent (read-only) | `payments` | exports, dashboards | No auto-generation at term start, no reminders/dunning, `payment_defaulters` announcements unreadable, no online payment |
| W13 | Public registration | Visitor submits on landing | Admin/head customize forms | **nobody** (triage only: new/seen/contacted) | admin, owning dept head | `registration_forms/fields/submissions` | **nothing downstream** | No notify on submit, no accept/reject, no capacity/deadline, no person/student conversion, no event linkage, cards not data-driven |
| W14 | Holidays + serving | Admin defines holidays, schedules dept/level/church | Admin | n/a | admin | `holidays`, `serving_assignments` | **nothing** | Never joined to eligibility; teachers' attendance UI holiday-blind; no person-level serving roster |
| W15 | Blog/videos | Admin posts/embeds | Admin | n/a | public | `blog_posts`, `video_embeds` | landing, blog | YouTube RSS auto-fetch still unbuilt (long-standing) |
| W16 | Reset/demo | Admin, password-gated | Admin | explicit approval for prod | admin | wipes/seeds | n/a | Working as designed; prod use requires Eyoel's explicit approval (CLAUDE.md rule) |

---

## 4. Registration system redesign (architecture only)

### 4.1 What exists (verified)

Three tables from migration 019: `registration_forms` (slug, bilingual title/description, `department_id`, status `open|limited|closed`, sort, soft-archive), `registration_form_fields` (9 field types: text, textarea, email, phone, number, date, select, radio, checkbox; bilingual labels/placeholders; `options_json`; required flag), `registration_submissions` (`answers_json` keyed by field id, `labels_snapshot_json` frozen at submit, heuristic `applicant_name`/`applicant_phone`, status `new|seen|contacted`, IP). Public GET + hardened POST; admin full CRUD via dot-notation actions; dept-scoped staff endpoint. Landing renders the form area dynamically but the announcement cards are static HTML.

### 4.2 Target architecture: two registration sources

**A. Standalone registrations (Admin only).** School-year, Sunday school, class, volunteer, membership, general applications. Created and owned in the Admin Registrations module. `registration_forms.origin = 'standalone'`.

**B. Event-linked registrations (Admin + Dept Head, via events only).** On the event create/edit form, a toggle `requires_registration`. When enabled, the system provisions a linked `registration_forms` row (`origin = 'event'`, `event_id` FK) and opens the form builder for it. The form's lifecycle follows the event: event rejected/archived → form auto-closed/archived; event date passed → form auto-closed. Many events will not require registration; the toggle defaults off.

**Permissions matrix:**

| Capability | Admin | Dept Head |
|---|---|---|
| Create standalone form | ✅ | ❌ (enforce server-side; remove `form.create` from the staff endpoint or restrict it to `origin='event'`) |
| Create event-linked form | ✅ | ✅ but only through their own event, in their own dept |
| Edit fields/status | ✅ all | own-dept forms only (already enforced) |
| View/triage/decide submissions | ✅ all | own-dept forms only |
| Archive/unarchive | ✅ | own event-linked forms only |

This also resolves today's UI-only enforcement hole (§2.2).

### 4.3 Schema deltas (design, not migration)

On `registration_forms`:
- `origin ENUM('standalone','event')`, `event_id` nullable FK → events.
- Card presentation: `subtitle_en/am`, `button_text_en/am`, `cover_image_path`, `card_image_path`, `is_featured`.
- Windowing: `opens_at`, `closes_at`, `deadline_at` (deadline may differ from close for display).
- Capacity: `capacity` nullable int (null = unlimited), `count_statuses` policy decision (recommended: count `accepted` only; see 4.6).
- `created_by_user_id`.

On `registration_submissions`:
- Decision workflow: extend status to `new|reviewing|accepted|rejected|waitlisted` (or a separate `decision` + `decided_by_user_id` + `decided_at`; recommended: the separate columns, keeping the triage enum intact for backward compatibility).
- Linkage: nullable `person_id`, `student_id` FKs + an explicit "convert to person/student" admin action reusing `create_person_account()`.
- Explicit applicant mapping: replace the English-label "name" heuristic with per-form flags on fields (`maps_to ENUM('applicant_name','applicant_phone','applicant_email') NULL`).

On `registration_form_fields`:
- New types toward the 16-type target: `time`, `datetime`, `url`, `file`, `image`, `address`, `hidden`, `section` (visual heading), `consent` (must-check), `multiselect`. File/image require an upload pipeline (size/type allow-list, storage under a non-executable path, served with correct headers); this is the single riskiest addition and should be its own sub-phase.
- `help_text_en/am`, `default_value`, `validation_json` (min/max, length, regex) for the preset-validator layer. Custom validators later plug into `validation_json.validator = 'national_id'` etc.

### 4.4 Dynamic landing cards + automatic visibility

Replace the hardcoded `#join` cards with a renderer over `GET /api/registrations` (same endpoint, enriched payload). Each card: cover image, title, subtitle, description, status badge, deadline line, featured badge, CTA with custom button text linking to the form tab or standalone page.

**Effective status is computed server-side, one function, used by both the list and submit endpoints:**

```
effective_status(form) =
  archived                      → hidden
  now < opens_at                → upcoming (card optional, "opens on X")
  now > closes_at               → closed → hidden after grace period
  capacity reached (accepted)   → closed ("full") 
  manual status                 → as set (open | limited | closed)
```

Cards appear/disappear with zero manual homepage editing. Submit re-checks the same function (409 on closed/full) so the rule cannot be bypassed by a stale page.

### 4.5 Submission lifecycle (target)

Visitor submits → dept head/admin notified (in-app notification; email later) → reviewer triages → **decision: accept / reject / waitlist** → on accept: optional one-click **convert** (create person + student via the shared lib, link `person_id`/`student_id` back to the submission, prefill from mapped answers) → conversion feeds W1 (accounts) and, for school-year forms, W3 (class enrollment). Acceptance counts against capacity; reaching capacity flips effective status to full.

### 4.6 Why the Admin Registrations page "does not function correctly" (investigation result)

The wiring is **not** broken: the deep-dive verified every JS call matches the backend contract (dot-notation actions, `?resource=submissions`, answers in `items`, field names all match; the submit → answers_json → admin rendering pipeline is intact end-to-end). The dysfunction is capability gaps that read as breakage:

1. **Archive is a one-way door.** Backend supports `form.unarchive` and `include_archived=1` (`api/admin/registrations/index.php:35,54`) but the UI never passes the flag and has no unarchive button (`public/admin/registrations.php:304,339`). Archived forms silently vanish forever. Most likely the observed "broken" behavior.
2. **Name/Phone columns show a blank placeholder while the data exists**, because applicant extraction is an English-label heuristic (`api/registrations/submit.php:180`); Amharic-labeled name fields defeat it.
3. **"Limited" status is cosmetic**: no capacity field or enforcement anywhere.
4. Every mutation refetches and re-renders the whole form list (state resets mid-edit).
5. No submissions export.

None of these require re-architecting; all are absorbed into the Phase 4 build (§9).

---

## 5. Department Head workspace redesign (recommendation only)

Current structure confirmed: one page, left dept rail, right column of 7 always-expanded panels; only one modal exists (an error dialog); the members panel embeds two full inline forms; each registration card expands a field builder + submissions table inline. Six parallel fetches on dept select.

**Proposed workspace:**

```
[Dept switcher: persistent left rail or top dropdown]
Tabs: Overview | Students | Teachers | Resources | Attendance | Assignments | Events | Registrations | Reports | Settings
```

- **Overview (new):** member/level counts, pending-event badge, new-submission badge, eligibility summary, recent activity. Mostly computable from data the page already loads; no new API needed for v1.
- **Students / Teachers:** split today's merged Members panel by `people.type`; add-existing/create-new move into modals; roster gets search/filter.
- **Resources:** current panel as-is, upload in a modal.
- **Attendance (new capability):** capture UI for dept sessions (reuse the teacher dept-attendance endpoint pattern) + summary view reusing the eligibility computation.
- **Assignments (new):** dept-scoped view over `tasks` once students can see them (W10 fix).
- **Events:** pending queue with badge, decided list, create in modal; show who proposed and when; notification hooks per §6.
- **Registrations:** form cards collapsed by default; field builder and submissions each open in a focused drawer/modal; expose the existing status filter; add export when Phase 4 lands.
- **Reports (new):** roster CSV, attendance summary, submissions export, eligibility report.
- **Settings:** levels management (add the missing rename/re-rank UI; API already supports it).

Implementation notes: lazy-load per tab (replace the 6-parallel-fetch burst), keep URL hash per tab for deep-linking, move all creates to modals per the existing `#regErrModal` pattern, keep bilingual coverage. Mobile: tabs become a horizontally swipeable scroll-snap strip (workspace rule: touch-swipeable).

---

## 6. Continuity audit (the chain)

Target: Departments → Classes → Students → Attendance → Assessments → Eligibility → Registration → Promotion → Reports → Graduation.

| Link | Status | Reality |
|---|---|---|
| Departments → Classes | 🟡 | `classes.department_id` exists (018) but nullable, no backfill; classes still hang off tracks/levels. Two parallel hierarchies. |
| Classes → Students | ✅ | `student_class_assignments` (but no unique constraint). |
| Students → Attendance | 🟡 | Class attendance works; dept attendance is an orphan write; not term-scoped. |
| Attendance → Assessments | ⬜ | Never meet. |
| Assessments → Eligibility | ⬜ | Eligibility ignores grades entirely; no pass mark exists anywhere in the codebase. |
| Eligibility → Registration | ⬜ | Submit path performs no eligibility check. |
| Eligibility → Serving | ⬜ | Serving rota never reads the eligibility result it exists to gate. |
| Registration → Promotion/Enrollment | ⬜ | Submissions never become people/students. |
| Promotion (N → N+1) | ⬜ | Only manual per-student class reassignment. No cohort promote, no grade increment. |
| Year rollover | ⬜ | `academic_year` is free text retyped by hand; nothing rolls forward. |
| Reports | 🟡 | One printable per-student per-term card; no pass/fail, no cumulative record, no bulk issue. |
| Graduation | ⬜ | Zero mentions in schema, API, or UI. |

**The user's example scenario** (Grade 6 finishes → grades finalized → eligibility computed → pass → auto-eligible for Grade 7 registration; fail → blocked + admin notified + student informed) currently has **zero of its six steps** implemented: no finalization, no grade-based eligibility, no registration gating, no notifications on any of it.

### Data dead-ends (write-only / read-only inventory)

**Written, never consumed:** dept-context attendance; eligibility results (computed per request, discarded); `serving_assignments` (never joined to eligibility); event `approved_by/approved_at` (never notifies or gates); registration submissions (inbox only); notification target types `subject`/`payment_defaulters`/`event` (admin can write, no reader filters on them); `people.last_communion_date` (admin-visible only, no log, promised "later phase" in migration 011); teacher tasks (students never see them); student profile fields in API payloads (UI never renders); attendance status breakdown for students (computed, discarded).

**Readable, no producer/UI:** `department`/`user` announcement targets (portals consume them; admin UI cannot emit them); `form.unarchive` (API yes, UI no); level rename (API yes, UI no); dead teacher endpoints (assignments/list, classes/students, grades GET).

**Duplicate/legacy surfaces:** `users.php` + `legacy.php` admin consoles; `public/student/grades.php` + `public/teacher/grades.php` redirect stubs; `api/student/grades/index.php` orphan; archived Era-1 tracks/levels rows coexisting with Grades 1-11; `students`/`teachers` name/phone/DOB columns duplicating `people` with no sync.

---

## 7. Database findings (summary; full inventory in the DB audit)

**Integrity gaps to close:**
- No unique constraints on `student_class_assignments`, `teacher_subject_assignments`, `department_memberships` (duplicates possible on all three core join tables).
- Polymorphic `scope_id`/`context_id` with no FK on `resources`, `tasks`, `attendance_sessions`.
- `attendance_sessions` has no `term_id`: attendance percentages are lifetime figures, which silently corrupts eligibility semantics across years.
- `grades` rows are not validated against curriculum (`grade_subjects`) and, until the July fix, not against enrollment.
- Inconsistent delete policy (soft-delete everywhere but mixed ON DELETE rules); app-level-only invariants (`is_current` term, primary teacher role).
- `registration_submissions.applicant_*` nullable free text, no person linkage (§4).

**Identity model (the big one):** one human = `users` (login) + `people` (canonical) + `students`/`teachers` (role record), with names/phone/DOB duplicated between `people` and `students`/`teachers` and no sync mechanism; parents skip `people` entirely. Direction (decision for Eyoel, recommendation below): make `people` the single source of personal data; `students`/`teachers` shrink to role-specific attributes keyed by `person_id`; parent accounts get `people` rows; admin "create" paths converge on `create_person_account()`. This is a data migration + query sweep, not a rewrite, and unblocks everything else (registration conversion, dept scoping, communion, messaging).

**Academic model:** commit to Grades 1-11 as the only hierarchy; either drop the archived Era-1 tracks/levels rows after verifying no FK references, or fence them behind an explicit legacy flag; rename UI language from "levels" to "grades" where it means grades; backfill `classes.department_id` for academic classes (ትምህርት ክፍል owns them) per MASTER_PLAN F2.

---

## 8. Eligibility engine design (system-wide concept)

**Today:** one function, one input (class-attendance % vs one global threshold), two display-only callers, result never stored. The premise "departments already contain minimum passing percentages" is **false**: no passing-percentage concept exists anywhere in the codebase. It must be designed, not merely surfaced.

**Design: a rule-based check engine with persisted evaluations.**

- **Rule:** `eligibility_rules(id, context_type, context_id, rule_type, params_json, is_active)` where context is department / grade-level / form / event / system, and `rule_type` ∈ `min_attendance_pct` (term-scoped), `min_average_score`, `passed_subjects` (per pass mark), `prerequisite_grade_completed`, `age_between`, `registration_window`, `requires_approval(role)`, `payment_clear`. Params carry thresholds.
- **Evaluation:** `evaluate(person_or_student, purpose, context) → {eligible, reasons[]}` composing all active rules for that purpose. Purposes: `serving`, `promotion`, `registration(form)`, `event(event)`.
- **Persistence:** `eligibility_evaluations(student_id, purpose, context, term_id, result, reasons_json, computed_at)` written at decision moments (term close, registration submit, serving assignment) so downstream modules consume a stored fact, not a recomputation, and the student/parent portals can display standing with reasons.
- **Consumers to wire:** serving assignment builder (filter ineligible), registration submit (gate + explain), promotion run (pass/fail per the department/grade pass mark, which becomes a real column on departments or grade-levels), notifications (at-risk alerts when a student drops below threshold mid-term).
- **Prereqs:** attendance must become term-scoped and grades must gain finalization before grade-based rules are meaningful; hence the phase ordering below.

---

## 9. Master roadmap

Dependencies flow downward; each phase is shippable alone. Per MASTER_PLAN §4, execution uses orchestrated agents with model tiers (strong model for schema/security phases, cheaper models for CRUD/UI phases).

### Phase 1: Critical architectural issues (foundation)
1. **Decision + migration: unify identity on `people`** (§7). Includes parent `people` rows and converging creation paths on `create_person_account()`.
2. **Commit to the single academic hierarchy** (Grades 1-11), backfill `classes.department_id`, retire tracks-era rows and the `legacy.php`/`users.php` consoles and dead endpoints/stubs.
3. **Server-side permission fixes:** remove/restrict staff `form.create`; resolve the demo-admin backdoor decision (FABLE_BUG_REPORT #1/#5) before any further prod exposure.
4. **Event approval visibility for admin** (oversight list + truthful status column), keeping head-level approval authority.

### Phase 2: Workflow consistency
1. **Notification engine v1:** align admin composer targets with reader targets; add producers for: event proposed/approved/rejected, registration submitted, dept assignment (exists), payment generated. One shared `notify()` helper.
2. **Grade finalization:** per (class, subject, term) lock + `updated_by`; term-close action.
3. **Term-scope attendance** (`term_id` on sessions) and surface per-student percentages to teachers and dept heads.
4. **Dept-head announcements** (mirror the events pattern; decide approval policy consistently with teacher announcements).
5. **Expose tasks/homework to students and parents** or explicitly park the feature.

### Phase 3: Database improvements
1. Unique constraints on the three join tables; FK or CHECK strategy for polymorphic scopes; delete-policy pass.
2. Registration schema deltas from §4.3 (columns only; behavior comes in Phase 4).
3. Eligibility tables from §8 (rules + evaluations).
4. Naming/label cleanup (grades vs levels, ክፍሎች collision), enum debris removal.

### Phase 4: Registration system (the redesign, §4)
1. Standalone vs event-origin forms + permissions matrix; event "requires registration" toggle.
2. Form builder expansion (new field types staged: non-upload types first, file/image upload as its own hardened sub-step; help text, defaults, validation_json, applicant field mapping).
3. Effective-status engine (dates, capacity, archive) enforced in list + submit.
4. Dynamic landing cards replacing the hardcoded `#join` section.
5. Decision workflow (accept/reject/waitlist) + convert-to-person/student + capacity counting.
6. Admin page fixes folded in: unarchive UI, archived toggle, submissions CSV, targeted re-render instead of full refetch.
7. Registration ↔ eligibility gating hook (consumes Phase 3.3).

### Phase 5: Department Head workspace UX (§5)
Tabbed workspace, Overview KPIs, modal forms, split Students/Teachers, attendance capture, Reports tab, level-editing UI, swipeable mobile tabs. (Sequenced after Phase 4 so the Registrations tab is built once, against the final system.)

### Phase 6: Automation & continuity
1. **Promotion engine:** term/year close → finalized grades + eligibility rules → cohort promotion run (pass → next grade enrollment; fail → block + notify admin + inform student/parent), with per-student overrides.
2. **Year rollover:** structured academic-year entity, class cloning, auto current-term switching by date.
3. Payments: auto-generate at term start, due-date reminders, a working defaulters flow.
4. Serving assignments consume stored eligibility; at-risk attendance alerts.
5. Registration auto-close jobs already covered by effective-status; add scheduled digest notifications.

### Phase 7: Quality assurance
1. Regression suite covering the five portals' happy paths + the permission matrix (cross-dept 403s, parent allow-lists, teacher enrollment checks).
2. Seed/demo refresh so every new feature has demo data; prod smoke checklist per release.
3. Security re-review after Phases 1 and 4 (auth-touching changes trigger review per workspace rules); rate limiting on public feeds; CSP tightening plan (self-hosted Tailwind build to drop `unsafe-inline` + CDN).
4. SEO pass (commercial default): OG/Twitter tags, sitemap.xml, robots.txt, JSON-LD (EducationalOrganization + Event), hreflang.

### Phase 8: Future enhancements (from MASTER_PLAN v2 + backlog)
Choir module (song catalog, advancement workflow, paid courses + funds) · per-department dashboard variants (D3) · finance/funds generalization · communion tracking UI + log table · messaging/threaded communication · M1 Excel import with column mapping (currently ON HOLD) · YouTube RSS auto-fetch · online payments · student/parent event registration surfaces · report-center (bulk report cards, cumulative records, graduation certificates).

---

## 10. Decisions needed from Eyoel before implementation starts

1. **Identity unification direction** (Phase 1.1): green-light `people` as the single source of truth? (Recommended: yes; everything downstream assumes it.)
2. **Demo admin backdoor** (Phase 1.3): archive/rotate on prod + gate or randomize the endpoint? (Recommended: randomize password per run + never seed admin on prod.)
3. **Staff `form.create`**: confirm dept heads must NOT create standalone forms (recommended per your Step 3 spec) so the server-side removal is correct.
4. **Capacity counting policy** (§4.3): count accepted submissions only (recommended), or all non-archived?
5. **Pass-mark ownership** (§8): does the minimum passing percentage live per department, per grade-level, or globally with overrides? (Recommended: per grade-level with department override.)
6. **Announcement moderation policy** (Phase 2.4): teacher and dept-head announcements stay approval-free (current behavior for teachers), or gain head/admin approval to match events?
7. **Phase 5 design step**: the Dept Head workspace gets a design pass (ui-ux-pro-max, one page at a time per your standing preference) before build. Confirm tab list in §5 or amend.
