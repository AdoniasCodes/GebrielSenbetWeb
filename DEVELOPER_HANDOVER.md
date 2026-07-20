# Mekane Selam Senbet School - Developer Handover

Version 1.0 · Prepared 2026-07-20 · Repository `AdoniasCodes/GebrielSenbetWeb`

## How to read this document

This is the onboarding reference for a developer or technical project manager joining the project. It describes the system exactly as it exists in the codebase today, section by section: what the product is, how it is built, the data model, every feature grouped by who uses it, the main data flows, the current project status (what is built and verified versus what is planned), the known technical debt, and how to deploy and run it locally.

Everything here was checked against the actual source (all 24 database migrations, every API endpoint, and every portal page). Where something is planned but not yet built, it is labelled **PLANNED** so nothing is mistaken for existing behaviour. Where there is a caveat or a known weakness, it is called out plainly under "Known issues and technical debt."

A companion file, `SYSTEM_AUDIT_AND_BLUEPRINT.md`, holds the long-range architecture plan and the eight-phase roadmap. `PHASE2_PLAN.md` holds the detailed plan for the phase currently in progress. `handoff.md`, `instructions.md`, and `CLAUDE.md` are the living project files (state, repeatable workflows, and project rules respectively).

---

## 1. What the system is

Mekane Selam Senbet School is a management system for an Ethiopian Orthodox Tewahedo Sunday school. It is a single-church organisation (St. Gabriel / Gebriel is the church; St. Mary / Mariam is a serving location, not a second church). The school is organised into **departments** that run themselves as sub-organisations (education/timhirt, choir/mezmur, outreach and its sub-departments, arts/kinetbeb, audio-visual, board, secretariat, construction, and parents).

The application serves five kinds of user, each with their own portal:

- **Admin** - runs the whole school.
- **Teacher** - teaches classes and subjects, records grades and attendance.
- **Staff (department head)** - runs one or more departments they lead.
- **Student** - sees their own academic record.
- **Parent** - sees their linked children's records.

It also has a public marketing website (landing page and blog) and a public registration system (people can sign up for activities like Sunday school, begena lessons, or the Gishen pilgrimage without an account).

Every user-facing surface is **bilingual: English and Amharic (አማርኛ)**, switchable at any time, with an Ethiopian-calendar date display in Amharic mode.

---

## 2. Technology stack

- **Language:** Vanilla PHP 8+. No framework, no Composer, no `vendor/`. A small hand-written PSR-4 autoloader in `bootstrap.php` maps the `App\` namespace to `src/`.
- **Database:** MySQL / MariaDB (InnoDB, `utf8mb4`) accessed through PDO with real prepared statements (`ATTR_EMULATE_PREPARES = false`) and exceptions on error.
- **Frontend:** Vanilla JavaScript with `fetch`. No build step, no bundler. All page logic is inline `<script>` plus two static helper files. Styling is Tailwind CSS loaded from a CDN with an inline theme config. Fonts come from Google Fonts (including Noto Sans/Serif Ethiopic).
- **Hosting:** cPanel shared hosting. The web document root is the `public/` subfolder.

There is no automated test suite. Verification to date has been done by scripted end-to-end HTTP checks against a local server (see "Local development").

---

## 3. Architecture and repository layout

### 3.1 The two API trees (read this first)

There are two `api/` directories and the distinction is the single most important thing to understand before editing anything:

- **The root tree** is `api/` at the repository root, OUTSIDE the web root. It holds the real endpoint implementations, the per-role guards (`_guard.php`), and the shared libraries (`*_lib.php`).
- **The shim tree** is `public/api/` INSIDE the web root. Each file is a thin one-line wrapper that only does a `require_once` of the matching root file.

The web server can only reach `public/`, so a request to `/api/admin/classes/index.php` hits the shim in `public/api/...`, which pulls in the real logic that lives one level above the web root. This keeps `config/`, `src/`, `db/`, and the real `api/` code unreachable by URL.

**Rule: edit the root `/api/` files. Never put logic in `public/api/` shims.** When you add a new endpoint, you must also add its matching one-line shim under `public/api/` or it will not be reachable.

### 3.2 Directory map

```text
GebrielSenbetWeb/
  api/                  Real endpoint logic (guards + *_lib.php helpers live here too)
    admin/  teacher/  staff/  student/  parent/    Per-role endpoints + _guard.php
    auth/                                           login, logout, csrf
    setup/  admin/deploy/                           token-gated bootstrap + migration runner
    announcements/ events/ posts/ videos/ terms/ registrations/   Public read feeds
    *_lib.php                                        Shared engines (see section 9)
  public/               WEB DOCUMENT ROOT
    index.php           Marketing landing page
    login.html          Sign-in page (static)
    blog.php            Public blog
    admin/ teacher/ staff/ student/ parent/         Portal pages (each self-guards)
    admin/_partials/    page-shell.php + page-shell-end.php (shared admin chrome + gs.* JS)
    api/                One-line shims mirroring /api/
    assets/js/          ec-date.js (Ethiopian calendar), video-embed.js
    uploads/            User-uploaded files (blog attachments, resources)
    .htaccess           CSP + security headers
  src/                  App\ classes: Database, Utils\Response, Utils\Csrf, Utils\Password, Audit
  db/migrations/        001-024 SQL migrations (the schema history)
  config/config.php     DB credentials + app tokens (see security note)
  bootstrap.php         Config load, autoloader, hardened session start
  .htaccess             Rewrites all requests into public/
  .cpanel.yml           Deployment recipe
```

### 3.3 Request lifecycle

```text
Browser
  |  GET/POST /api/<area>/<endpoint>
  v
public/.htaccess ---- rewrites into public/ (docroot) --------------+
  |                                                                 |
  v                                                                 |
public/api/<area>/<endpoint>.php   (one-line shim)                  |
  |  require_once                                                   |
  v                                                                 |
api/<area>/<endpoint>.php   (real logic)                           |
  |  require_once _guard.php  --> bootstrap.php                     |
  |      - starts hardened session (App\ autoloader, app_config)    |
  |      - enforces $_SESSION['role_name'] == this area's role      |
  |      - require_csrf_for_write() on POST/PUT/PATCH/DELETE        |
  v                                                                 |
  Business logic (PDO via App\Database) + App\Audit::log(...)       |
  |                                                                 |
  v                                                                 |
  App\Utils\Response::json(...)  --> JSON back to the browser <-----+
```

Public feeds (`/api/announcements`, `/api/events`, `/api/posts`, `/api/videos`, `/api/terms`, `/api/registrations`) skip the role guard; they are read-only.

---

## 4. Roles and access

| Role | Added in | Portal | What they can do |
|---|---|---|---|
| admin | migration 001 | `/admin/` | Everything: people, users, academics, grades, attendance, payments, content, settings, data reset. |
| teacher | migration 001 | `/teacher/` | Enter grades and attendance for assigned classes/subjects; manage tasks, files, announcements, and propose events for their departments. |
| student | migration 001 | `/student/` | Read-only: own grades, attendance %, payments/balance, announcements, resources. |
| parent | migration 009 | `/parent/` | Read-only view of their linked children's grades, payments, and announcements. |
| staff (department head) | migration 014 | `/staff/` | Manage the department(s) they head: members, levels, resources, events, serving eligibility, registrations. Scope comes from `department_memberships.is_head`. |

The staff portal also admits admins (an admin has full scope there). Every other portal is single-role.

---

## 5. Authentication, sessions, and CSRF

- **Login** (`POST /api/auth/login`): verifies email + bcrypt password (`password_verify`), rejects archived accounts, and (to prevent email enumeration) verifies a dummy hash even when the email is unknown so timing is constant. On success it regenerates the session id and sets `$_SESSION['user_id']`, `user_email`, `role_id`, `role_name`, and returns `{ role, csrf_token }`. Login is deliberately CSRF-exempt because it establishes the session.
- **The login page** is the static `public/login.html`. Its script stores the CSRF token in `sessionStorage` and redirects by role: admin→`/admin/`, staff→`/staff/`, teacher→`/teacher/`, parent→`/parent/`, everything else→`/student/`.
- **CSRF** (`src/Utils/Csrf.php`): a per-session 64-hex token, issued by `GET /api/auth/csrf`. Every state-changing request must send it in the **`X-CSRF-Token`** header. Guards enforce this on POST/PUT/PATCH/DELETE via `require_csrf_for_write()`; comparison is constant-time. Logout requires CSRF; the public registration submit requires CSRF too.
- **Sessions** are started in `bootstrap.php` with `HttpOnly`, `SameSite=Lax`, and `Secure` on HTTPS. Session cookie name is `CHURCH_EDU_SESSID`.
- **Guards:** each area has `api/<area>/_guard.php` that boots the session, checks the role, and exposes `require_csrf_for_write()` plus role-scoped helper functions. Portal pages also self-guard at the top (`if role_name !== '<role>' redirect to /`).

```text
Login and portal routing
------------------------------------------------------------
login.html  --POST /api/auth/login-->  api/auth/login.php
   |                                       | verify bcrypt, set session,
   |  {role, csrf_token}  <----------------+ return csrf_token
   v
store csrf_token in sessionStorage
   |
   +-- admin   --> /admin/index.php
   +-- staff   --> /staff/index.php
   +-- teacher --> /teacher/index.php
   +-- parent  --> /parent/index.php
   +-- student --> /student/index.php   (default)

Every later write:  gs.api() attaches X-CSRF-Token header
                    --> area _guard.php validates role + CSRF
```

---

## 6. Data model

The schema is defined by 24 migrations in `db/migrations/`. Two conventions run through it:

1. **Soft deletes.** Almost every table has `is_archived` + `archived_at`. Rows are archived, not deleted. Most foreign keys therefore use `ON UPDATE CASCADE` with the default `ON DELETE RESTRICT`.
2. **A canonical person identity.** `people` is the single record of a human being; logins, student profiles, teacher profiles, department memberships, and attendance all resolve back to it.

### 6.1 The identity model (important)

```text
                          people  (canonical human identity)
                            ^  ^  ^
             user_id (opt)  |  |  |  person_id
        +-------------------+  |  +-------------------+
        |                      | person_id            |
     users  (login: email,     |                      |
      password, role)     students / teachers   department_memberships
        |                  (role profiles)      attendance_records
        | role_id                                (reference people, not users)
        v
      roles  (admin/teacher/student/parent/staff)
```

- `people` holds name, baptismal name, DOB, gender, contact, primary church, member status, last communion date. A person may exist **without** a login (`people.user_id` is nullable and unique).
- `users` is authentication only (email, password hash, one role).
- `students` and `teachers` are role profiles; each links to one `users` row and one `people` row.
- Parents link to students through the `student_guardians` join table (many-to-many).
- Migrations 011, 018, and 021 progressively unified this: today every non-admin login and every student/teacher resolves to a `people` row. This was Phase 1 work and is complete.

### 6.2 The academic hierarchy

```text
education_tracks ("Sunday School Curriculum")
      |
      v
class_levels  (Grades 1-11; grades 7-11 carry a Ge'ez alias)
      |  \
      |   \--< grade_subjects >-- subjects   (which subjects belong to a grade)
      v
classes  (a section for a level + academic_year, owned by a department)
      |  \
      |   \--< teacher_subject_assignments >-- teachers + subjects
      v
   < student_class_assignments >-- students   (enrollment)
```

Migrations 013 and 020 retired an earlier two-track model ("Children" / "Youth-Adult") and committed to a single "Sunday School Curriculum" track with Grades 1-11. Classes were given an owning `department_id` (defaulting to the education/timhirt department).

Departments are a parallel structure: `departments` (self-referential for sub-departments) → `department_levels` (per-department advancement ranks, e.g. the choir ladder) → `department_memberships` (people rostered into a department, with an optional level and an `is_head` flag). The two hierarchies meet at `classes.department_id` and at the polymorphic scope of `resources` and `tasks`.

### 6.3 Table catalogue (grouped)

**Identity and access:** `roles`, `users`, `people`, `students`, `teachers`, `student_guardians` (parent↔student), `churches`.

**Academics:** `education_tracks`, `class_levels`, `subjects`, `grade_subjects` (level↔subject), `classes`, `academic_terms`, `teacher_subject_assignments` (teacher×class×subject), `student_class_assignments` (student×class), `grades`, `grade_finalizations` (per-gradebook lock).

**Departments and serving:** `departments`, `department_levels`, `department_memberships` (person×department), `holidays`, `serving_assignments` (holiday×department).

**Attendance:** `attendance_sessions` (a roll-call for a class or a department, now term-scoped), `attendance_records` (person×session).

**Money:** `payments` (student×term).

**Communications and content:** `notifications`, `notification_reads` (notification×user read state), `events`, `event_recurrence_rules`, `blog_posts`, `blog_attachments`, `video_embeds`.

**Registration (public sign-up):** `registration_forms`, `registration_form_fields`, `registration_submissions`.

**Cross-cutting:** `resources` (files/links scoped to a grade or department), `tasks` (homework scoped to a department/class/grade), `audit_log`, `app_settings` (key/value).

### 6.4 Things to know about the schema

- **Unique keys that define identity:** a grade is unique per `(student, subject, class, term)`; a payment per `(student, term)`; a gradebook lock per `(class, subject, term)`; an attendance mark per `(session, person)`; a guardian link per `(user, student)`.
- **Polymorphic columns with no foreign key:** `attendance_sessions.context_id` (class or department), `resources.scope_id`, and `tasks.scope_id`. Their target table depends on a sibling `*_type` enum. Referential integrity for these is enforced in application code only.
- **Legacy debris:** `notifications.read_by` (a JSON array) was replaced by the `notification_reads` table in migration 022 but is retained for rollback safety; it is scheduled to be dropped in a later phase.
- **Key enums:** roles (admin/teacher/student/parent/staff); `payments.status` (paid/unpaid/partial); `attendance_records.status` (present/absent/late/excused); `events.status` (approved/pending/rejected); `notifications.target_type` (role/class/subject/payment_defaulters/event/department/user); `registration_forms.status` (open/limited/closed); `registration_submissions.status` (new/seen/contacted).

---

## 7. Feature inventory

This is what exists and works today, grouped by who uses it. For each area it notes what the feature does and what it touches. The authoritative endpoint list is section 8.

### 7.1 Public website (no login)

- **Landing page** (`public/index.php`): hero, mission, formation stages, live events / announcements / blog / video strips (pulled from the public feeds), and a **public registration widget**. Also shows a link back to your dashboard if you are logged in.
- **Blog** (`public/blog.php`): public post listing.
- **Public registration**: visitors pick an open form (e.g. Sunday school, begena, Gishen pilgrimage), fill in configurable fields, and submit. Submitting is protected by CSRF, a honeypot field, per-IP flood limiting (5 per form per hour), and server-side validation. Each submission notifies the admins and the owning department's head(s). Affects: `registration_submissions`, and produces notifications.

### 7.2 Admin portal (`/admin/`)

The admin has a page for essentially every entity. Grouped:

- **People and accounts:** people registry (`people.php`), user accounts (`users.php`, creates teacher/student profiles and department memberships and keeps the `people` name in sync), parents and their child links (`parents.php`), grant/revoke a login for a person (`people/login.php`). Teacher and student detail pages.
- **Academic structure:** tracks, grade levels, subjects, grade↔subject curriculum map, classes, academic terms, teacher↔class↔subject assignments, student↔class enrollment.
- **Records:** grades (school-wide, with lock awareness), attendance (sessions + marks), payments (per-term records plus bulk-generate from a term's default tuition), serving eligibility.
- **Calendar and serving:** events (with recurrence and approval oversight), holidays, serving assignments, churches.
- **Content:** announcements composer, blog posts (with attachment upload), video embeds, resources.
- **Registrations:** review and manage forms, fields, and submissions.
- **System:** settings (current term, options, password), audit log viewer, CSV export (students, payments), CSV import (students), and a password-gated data reset tool.
- **Inbox:** the admin also receives targeted notifications (distinct from the composer).

Affects: nearly every table. Admin writes bypass the per-gradebook finalization lock but still respect a closed term (see 7.6).

### 7.3 Teacher portal (`/teacher/`)

A single-page portal. The teacher first picks a department (or works with their general classes), then uses tabs:

- **Grades:** enter/edit per-student scores and remarks for a class+subject+term; finalize the gradebook when done (and reopen it). Writes go to `grades` (and `grade_finalizations`), gated by the teacher's assignment and by lock state.
- **Attendance:** take roll-call for a class or a department by date, and see a per-student "attendance this term" summary. Writes `attendance_sessions` + `attendance_records`; sessions are stamped with the term derived from their date.
- **Tasks:** create/manage homework tasks for a department. Writes `tasks`.
- **Files:** upload files or add links for their grades/departments. Writes `resources`.
- **Announcements:** post announcements to their department or class - no approval needed. Produces `notifications`.
- **Events:** propose an event for a department; it starts `pending` and must be approved by a department head. Writes `events`; produces a notification to the department head(s).

### 7.4 Staff / department-head portal (`/staff/`)

The head selects a department they lead and manages:

- **Members** - add existing people or create new accounts, set level and title, mark heads.
- **Levels** - the department's advancement ladder.
- **Serving eligibility** - each member's academic attendance rate (all-time and this term) and whether they meet the serving threshold.
- **Resources** - files and links for the department.
- **Events** - approve or reject teacher proposals, or create an already-approved event. Approving/rejecting notifies the proposer.
- **Registrations** - customise the fields and status of the department's own forms (cannot create standalone forms or reassign a form to another department).
- **Inbox** - receives event proposals, new registration notifications, and broadcasts.

### 7.5 Student and parent portals

- **Student** (`/student/`): read-only dashboard - attendance %, subjects graded, average score, balance due, the grades table, payments, announcements, and resources for their grade.
- **Parent** (`/parent/`): a card per linked child, plus announcements; a per-child page shows average score, outstanding balance, class, grades, and payments. All child access is scope-checked against the parent's `student_guardians` links.

### 7.6 Grade finalization and term close (built in phase 2.2)

Two independent locks protect grades, resolved by `api/grades_lib.php`:

- **Soft lock (per gradebook = class+subject+term):** a teacher finalizes their own gradebook when done. This blocks that teacher's further edits, but an admin can still edit through it. The teacher can reopen it while the term is open. Backed by the `grade_finalizations` table.
- **Hard lock (per term):** an admin closes a term (`academic_terms.closed_at`), which blocks every grade write - teacher and admin alike - until the term is reopened. Closing or reopening notifies all teachers.

Every grade write also stamps `grades.updated_by_user_id` for accountability. Blocked writes return HTTP 423 (Locked).

### 7.7 Attendance percentages and serving eligibility (built in phase 2.3)

Attendance sessions carry a `term_id` (auto-derived from the session date). The **attendance rate** uses one canonical formula everywhere: `(present + late) / (present + late + absent)`, with excused absences excluded from the denominator, counting class sessions only.

Serving eligibility (`api/eligibility_lib.php`) computes each department member's academic attendance rate and compares it to an admin-set threshold (default 75%, stored in `app_settings`). As of phase 2.3 the views also show a term-scoped rate, but the eligible/not-eligible decision still uses the all-time rate (a deliberate choice - changing what makes someone eligible is deferred to a later phase).

### 7.8 Notifications (rebuilt in phase 2.1)

There is one notification engine (`api/notifications_lib.php`). A notification targets one of: a role, a department, a class, or a specific user (and can optionally be marked public for the landing page). Producers call one `notify()` function; every portal inbox reads through one shared query builder, so what can be sent is always something a reader can receive. Read state lives in `notification_reads` (per user, idempotent). Producers wired in: event proposed/approved/rejected, registration submitted, payment generated, and department assignment.

---

## 8. API reference (by area)

All endpoints are session- and role-guarded unless marked public; all writes require the `X-CSRF-Token` header. URL = `/api/<path>`. Full logic is in the root `api/` tree; each has a matching shim in `public/api/`.

**Auth (public):** `auth/login` (POST), `auth/logout` (POST, CSRF), `auth/csrf` (GET).

**Public read feeds:** `announcements`, `events`, `posts`, `videos`, `terms`, `registrations` (GET); `registrations/submit` (POST, CSRF).

**Token-gated (not role-guarded):** `setup/create_admin`, `setup/demo_logins` (X-Setup-Token), `admin/deploy/migrate` (X-Deploy-Token - note: this lives under the admin path but is protected by a deploy token, not an admin login).

**Student:** `student/dashboard`, `student/resources` (GET).

**Parent:** `parent/students`, `parent/grades`, `parent/payments`, `parent/announcements` (GET; all scope-checked to the parent's children).

**Teacher:** `teacher/classes`, `teacher/departments`, `teacher/roster` (GET); `teacher/grades` (GET/POST/PUT), `teacher/grades/finalize` (POST); `teacher/attendance` (GET/POST), `teacher/attendance/summary` (GET); `teacher/dept-attendance` (GET/POST); `teacher/tasks` (GET/POST/PUT/DELETE); `teacher/resources` (GET/POST/DELETE); `teacher/announcements` (GET/POST); `teacher/events` (GET/POST/DELETE); `teacher/notifications` (GET inbox / POST mark-read).

**Staff:** `staff/departments`, `staff/people`, `staff/eligibility` (GET); `staff/members`, `staff/levels`, `staff/resources` (GET/POST/PUT/DELETE); `staff/events`, `staff/registrations` (GET/POST); `staff/roster` (POST); `staff/notifications` (GET/POST).

**Admin:** the standard pattern is GET list / POST create / PUT update / DELETE archive. Areas: `admin/people` (+ `people/login`), `admin/users`, `admin/parents`, `admin/teachers/{list,detail}`, `admin/students/detail`, `admin/tracks`, `admin/levels`, `admin/subjects`, `admin/grade-subjects`, `admin/classes`, `admin/terms` (+ `terms/close`), `admin/settings/{current-term,options,password}`, `admin/assignments`, `admin/student-assignments`, `admin/departments` (+ `departments/levels`, `departments/members`), `admin/eligibility`, `admin/churches`, `admin/holidays` (+ `holidays/serving`), `admin/attendance` (+ `attendance/records`), `admin/grades`, `admin/payments` (+ `payments/generate`), `admin/announcements`, `admin/inbox`, `admin/posts` (+ `posts/upload`), `admin/videos`, `admin/resources`, `admin/registrations`, `admin/events`, `admin/stats`, `admin/audit-log`, `admin/export/{students,payments}`, `admin/import/students`, `admin/reset-data`.

---

## 9. Shared engines (`api/*_lib.php`)

These are the reusable cores. When changing behaviour, change it here, not in individual endpoints.

- **`notifications_lib.php`** - the notification target contract. `notify()` plus typed wrappers (`notify_user/role/department/class`), `notif_audience_clause()` and `notif_inbox_query()` (readers), `notif_mark_read()`, `notif_department_head_user_ids()`.
- **`grades_lib.php`** - the grade write-lock authority: `grade_is_term_closed()`, `grade_is_finalized()`, `grade_lock_reason()`, `grade_lock_message()`.
- **`attendance_lib.php`** - `attendance_term_for_date()` (term derivation) and `attendance_class_summary()` (the canonical per-student rate).
- **`eligibility_lib.php`** - `gs_compute_eligibility()`, `gs_eligibility_threshold()`, `gs_current_term_id()`.
- **`person_accounts_lib.php`** - `create_person_account()` (creates the user + people + role profile in one flow), and `notify_department_assignment()`. Shared by the admin user endpoint and the staff roster endpoint.
- **`registrations_lib.php`** - all registration form/field/submission logic, with a department-scope parameter (null = admin full access, an array = staff scoped, empty = no access).
- **`resources_lib.php`** - file/link storage and listing with strict scope checks (25 MB cap, extension allow-list).

---

## 10. Key data flows

### 10.1 Grade entry to term close

```text
Teacher opens gradebook (class + subject + term)
   |  GET /api/teacher/roster  --> students + existing grades + lock state
   v
Enter scores --> POST/PUT /api/teacher/grades
   |  grades_lib: is term closed? is gradebook finalized?
   |     - closed term  -> 423 (blocks everyone)
   |     - finalized    -> 423 for teacher (admin bypasses)
   v
Teacher clicks Finalize --> POST /api/teacher/grades/finalize
   |  inserts grade_finalizations row (soft lock)
   v
Admin closes the term --> POST /api/admin/terms/close
   |  sets academic_terms.closed_at (hard lock)
   |  notify_role('teacher', "Term closed")  --> teacher inboxes
   v
No further grade writes for that term until an admin reopens it.
```

### 10.2 Attendance to serving eligibility

```text
Teacher takes roll-call (class, date) --> POST /api/teacher/attendance
   |  attendance_lib.attendance_term_for_date(date) stamps session.term_id
   v
attendance_sessions (+ term_id) + attendance_records (person, status)
   |
   +--> Teacher summary: GET /api/teacher/attendance/summary  (per-student % this term)
   |
   +--> Eligibility: gs_compute_eligibility(department, term)
           rate = (present+late)/(present+late+absent), excused excluded, class sessions
           eligible = all-time rate >= threshold (default 75%)
           (term rate is shown too, but does not change the eligible flag)
   v
Staff + admin eligibility pages show all-time rate, this-term rate, and eligible/not.
```

### 10.3 Notification: produce to read

```text
Producer (e.g. registration submitted, event approved, payment generated)
   |  notify_role / notify_user / notify_department / notify_class
   v
notifications row (target_type + target_payload JSON)
   |
   v
Reader portal inbox: notif_inbox_query(context)
   |  matches role / department / class / user + is_public, joins notification_reads
   v
Inbox list with unread flags  --> POST mark-read --> notification_reads row (idempotent)
```

### 10.4 Public registration to follow-up

```text
Visitor --> GET /api/registrations (open forms + fields)
   |  fill + submit --> POST /api/registrations/submit (CSRF, honeypot, flood guard, validate)
   v
registration_submissions row  +  notify admins and the form's department head(s)
   |
   v
Admin/staff review --> /admin/registrations or /staff/registrations
   (Converting a submission into a real student account is PLANNED, not built - see 11.)
```

---

## 11. Project status: what is built vs planned

The long-range plan is an eight-phase roadmap in `SYSTEM_AUDIT_AND_BLUEPRINT.md`. Status as of 2026-07-20:

**Built and verified locally:**

- **Phase 1 - foundations (complete).** Identity unification (`people` is canonical; migrations 020, 021), single academic hierarchy, admin oversight of event approvals, and a security fix making standalone form creation admin-only.
- **Phase 2.1 - notification engine (complete).** The single `notify()` contract, the `notification_reads` table (migration 022), staff and admin inboxes, and producers across events/registrations/payments/assignments. Fixed a real bug where admin-to-teacher announcements had never been delivered.
- **Phase 2.2 - grade finalization (complete).** Soft per-gradebook lock, hard term close, `updated_by` accountability (migration 023).
- **Phase 2.3 - term-scoped attendance (complete).** `term_id` on sessions (migration 024), the canonical attendance summary, and additive term rates in eligibility.

**Planned, not yet built (in roughly the blueprint order):**

- **Phase 2.4** - department-head announcements surfaced consistently (approval-free, already decided).
- **Phase 2.5** - expose tasks/homework to students and parents (currently teacher/staff only), or formally park it.
- **Phase 3** - database hardening: unique constraints on the join tables, a strategy for the polymorphic scopes, registration schema additions, an eligibility rules/evaluations model, and enum/label cleanup (including dropping the legacy `notifications.read_by`).
- **Phase 4** - the registration redesign: standalone vs event-linked forms, capacity/waitlist, an accept/reject/waitlist decision workflow, and converting a submission into a person/student account. (Today submissions are captured and triaged as new/seen/contacted but never become accounts.)
- **Phase 5** - a redesigned department-head workspace (tabbed, KPIs, mobile).
- **Phase 6** - automation and continuity: student promotion between grades, academic-year rollover, automatic payment generation and reminders, and eligibility feeding serving assignments. This is where the eligibility window may switch from all-time to per-term.
- **Phase 7** - a regression/permission test suite, seed refresh, a post-auth-change security review, rate limiting on public feeds, and an SEO pass.
- **Phase 8** - future modules: a full choir module, per-department dashboards, finance generalisation, communion-tracking UI, messaging, Excel import with column mapping, YouTube RSS auto-fetch, and a report centre.

---

## 12. Known issues and technical debt

Honest list for the incoming developer:

- **No automated tests.** Verification is by ad-hoc end-to-end scripts. A real regression suite is Phase 7 and is a genuine risk until then.
- **Hardcoded secrets in `config/config.php`.** Real-looking DB credentials and the setup/deploy tokens are committed as fallbacks. These should be moved to environment variables and rotated. This is the highest-priority security item.
- **CSP allows `unsafe-inline` scripts** (`public/.htaccess`), which is necessary because all page logic is inline `<script>`. Tightening this (a self-hosted Tailwind build and externalised JS) is planned for Phase 7.
- **Polymorphic columns have no foreign keys** (`attendance_sessions.context_id`, `resources.scope_id`, `tasks.scope_id`); integrity is app-enforced only.
- **`notifications.read_by` is dead but not dropped** (kept for rollback after migration 022).
- **Department roll-call attendance is recorded but not counted** anywhere - eligibility only uses class attendance. This is a known dead-end to resolve later.
- **Registration submissions never become accounts** yet (Phase 4).
- **A few endpoints have no HTTP-method check** and respond to any verb, but all are read-only SELECTs (`api/terms`, `api/admin/stats`, `api/admin/teachers/list`).
- **The migrate endpoint sits under `admin/` but is token-gated, not admin-session-gated** - intentional for CLI/deploy use; keep the deploy token secret.

None of these block day-to-day use; they are the map of where to be careful and what to harden.

---

## 13. Deployment

Production is `mekaneselamss.com` on cPanel (account `mekanefh`). Deploys are **manual and two-step**:

1. Push to GitHub `main` (the developer does this).
2. In cPanel: Git Version Control → Update from Remote, then Deploy HEAD Commit. This rsyncs the repo into `public_html`. The document root must point at `public_html/public`.
3. After any deploy that adds migrations, call the migration runner once with the deploy token:
   `POST /api/admin/deploy/migrate` with header `X-Deploy-Token: <token from config>`. It applies pending migrations idempotently and self-bootstraps against existing schema.

**Current deploy debt:** migrations **019 through 024 are not yet applied on production.** They are all on `main`. On the next cPanel deploy, run the migrate endpoint and all six apply in order (registrations, hierarchy cleanup, identity unification, notification_reads, grade finalization, attendance term). The local development database already has all 24.

Commits must carry the normal git identity with **no AI attribution / no Co-Authored-By line** (a hard project rule; the free Vercel-style tooling and cPanel flow expect clean authorship). Pushing directly to `main` is the normal flow for this solo project.

---

## 14. Local development

Requires PHP 8+ and MySQL locally (Homebrew works).

**Step 1. Create a local database** named `eagleerq_gebriel`.

**Step 2. Create a dedicated DB user (credentials gotcha).** `config/config.php` uses `getenv('APP_DB_PASS') ?: '<prod fallback>'`, so an empty password silently falls back to the production password and fails. Local MySQL `root` usually has no password, so create a user once:

```sh
mysql -u root -e "CREATE USER IF NOT EXISTS 'gsb'@'localhost' IDENTIFIED BY 'gsblocal';
  GRANT ALL PRIVILEGES ON eagleerq_gebriel.* TO 'gsb'@'localhost'; FLUSH PRIVILEGES;"
```

**Step 3. Apply migrations** (run each file, or hit the migrate endpoint locally):

```sh
for f in db/migrations/*.sql; do mysql -u gsb -pgsblocal eagleerq_gebriel < "$f"; done
```

**Step 4. Start the server** against the local DB:

```sh
APP_ENV=development APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel \
  APP_DB_USER=gsb APP_DB_PASS='gsblocal' php -S 127.0.0.1:8099 -t public
```

**Step 5. Seed demo logins** (one per role, plus a head per department; non-destructive):

```sh
APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel APP_DB_USER=gsb APP_DB_PASS='gsblocal' \
  DEMO_PASSWORD='pick-one' php scripts/seed_demo_users.php
```

The generated account list is written to `DEMO_LOGINS.md` (the password itself is not stored there).

For scripted API checks: `GET /api/auth/csrf` (the JSON key is `csrf_token`), keep the cookie jar, and send the token back as the `X-CSRF-Token` header on writes.

`instructions.md` holds the fuller, canonical version of these steps.

---

## 15. Working conventions on this project

- **Three living files at the repo root**, kept current: `CLAUDE.md` (project rules), `instructions.md` (repeatable workflows), `handoff.md` (current state - read this first each session).
- **Bilingual everywhere:** every user-facing string carries `data-en` and `data-am`; new UI must too.
- **No em dashes in user-facing copy** (a content rule for this project).
- **Public endpoints are thin delegates** into the repo-root `api/`; keep that split.
- **Design tokens:** primary colour `#16357e`; the visual system is documented in the design references.

---

## 16. Glossary

- **EOTC** - Ethiopian Orthodox Tewahedo Church.
- **timhirt (ትምህርት)** - education; the education department that owns academic classes.
- **mezmur (መዝሙር)** - hymns; the choir department (has an advancement ladder).
- **begena (በገና)** - a traditional stringed instrument; one of the registration activities.
- **Gishen** - Gishen Mariam, a pilgrimage destination; another registration activity.
- **Serving / serving eligibility** - whether a member (typically choir) has enough attendance to be assigned to serve at a church on a holiday.
- **Gradebook** - the set of grades for one class + subject + term; the unit that gets finalized.
- **Term close** - an admin action that hard-locks all grades for a term.

---

End of handover. For the phase-by-phase plan and rationale, see `SYSTEM_AUDIT_AND_BLUEPRINT.md`; for current work, see `PHASE2_PLAN.md` and `handoff.md`.
