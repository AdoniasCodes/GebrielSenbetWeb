# Master Plan v2 — Department-Run Sunday School (single church, Gebriel)

Status: PLANNING (2026-07-03, revised after church clarification). Author: Fable 5.
Supersedes v1 (which over-built multi-church tenancy — no longer needed).

---

## 0. The clarification that simplifies everything
**All grades, classes, and departments are at Gebriel.** Mariam is only a *serving/event
location* (choir performances + charity events there). So church is NOT a tenant boundary — it's a
light location tag on events/serving/attendance, and those are **already modeled** (`church_id` on
`attendance_sessions`, `serving_assignments`). The only likely add: nullable `church_id` on `events`.
→ **The heavy "church scoping" foundation phase is deleted.** The real work is *departments as
self-running sub-organizations*, a richer teacher role, and per-grade/department file areas.

---

## 1. What's already built (we're far from zero)
- **Departments**: all 12 incl. sub-depts (mezmur, timhirt, በጎአድራጎት + ልማት/ጉዞ/charity, kinetbeb, av,
  board, secretary, construction, parents) + **advancement levels** (choir ladder መደበኛ አገልጋይ→ተተኪ1→
  ተተኪ2→ቀዳማይ seeded) + **memberships** (person↔dept, is_head).
- **Attendance** (academic + department), **serving-eligibility engine** (academic attendance %
  gates serving), **holidays + serving assignments** (per level/church).
- **Curriculum**: grades 1–11 (Ge'ez aliases ቀዳማይ…ሃምሳይ), per-grade subjects, terms, classes, grades.
- **Resources table + working file upload** (scope grade|department, file|link) — but admin-only.
- **Staff/dept-head portal** — scoped, but read-mostly (members/levels/eligibility).
- Public landing already has the **building campaign** (CBE 1000469573382) + the **Paschal
  greeting hero animation** (ክርስቶስ ተንሥአ እሙታን …). Those are done.

## 2. What's missing (the actual build)
1. **Departments aren't linked to classes or teachers** → "the choir's classes/students" can't exist.
2. **Dept heads can't run their department** (create teachers, events, announcements, dept tools).
3. **No per-department dashboard variants** (choir needs different data than outreach).
4. **Resources are admin-only** and not surfaced per-grade/per-department in dashboards.
5. **Choir module** (song/hymn catalog, advancement workflow, extracurricular paid courses+funds).
6. **No communion tracking, no finance/funds, no per-department reporting, no messaging.**

---

## 3. Phased plan (reprioritized; cheap wins first)

### ⭐ R1 — Per-grade & per-department Resources area  ·  SMALL / CHEAP (do first)
Reuse the EXISTING `resources` table + upload endpoint. Minimal-token approach:
- Add read-only scoped views: student sees their **grade's** files; dept member/head sees their
  **department's** files; teacher sees both (their grade classes + their departments).
- Let **dept-heads & teachers upload** (currently admin-only) into their own scope.
- Later (T2) extend scope to `class` and `specific student`. R1 ships grade+department first.
No schema change needed for R1. This directly answers "every grade and every department needs their
own resources page." Smallest, highest-value first step — I can build it immediately.

### F2 — Join departments ↔ classes ↔ teachers  ·  FOUNDATION
- `classes.department_id` (nullable): academic classes → ትምህርት ክፍል; choir/arts courses → their dept.
- Teacher ↔ department link (teachers become dept members with an `instructor` role).
- Defines "a department's students" = enrolled in its classes. Unlocks D1–D3, T1.

### DEPARTMENT CONSOLES — each dept becomes a scoped sub-org admin
- **D1 · Console core**: dept head manages members, **assigns/creates teachers**, all scoped to
  their department. When a new dept is created it gets a head who runs it.
- **D2 · Dept events + announcements** (+ student/parent inbox to receive them). Events can be
  tagged to a church (Gebriel/Mariam) for choir/charity serving.
- **D3 · Per-department dashboard variants + department tools** (the heart of your vision):
  - **ትምህርት ክፍል**: curriculum management, teacher recruiting/roster, **successor-teacher training
    (የተተኪ መምህራን ስልጠና, yearly cohort)**, top-student→assistant-teacher promotion.
  - **መዝሙር**: advancement board (levels × memorization/performance/grades/attendance), serving-
    assignment scheduler per holiday/church, extracurricular course manager (see C).
  - **በጎ አድራጎት (+ ልማት/ጉዞ/charity)**: assignment show-up tracking, shop/tent activations, trip
    planner, charity/feeding/hospital drives, all with **fund tracking**.
  - **ኪነጥበብ**: poems/plays/programs planner + event/fundraising.
  - **A/V**: asset production tracker + social-media account management.
  - **Board / Secretary / Construction / Parents**: lighter tool sets.
  Each = a dashboard variant selected by department slug. **Spec-driven — refine with tomorrow's
  per-department specs.**

### CHOIR MODULE (Phase C) — the distinctive flagship
- Song/hymn **catalog per grade** + celebration repertoire; per-member **memorization/qualification**.
- **Advancement workflow**: promote/deny by memorization + performance + academic grades +
  attendance (eligibility engine already computes the attendance %).
- **Extracurricular paid courses** (በገና, ከበሮ …): enrollment + fees + **alumni instructors** + fund
  tracking (this seeds the finance concept).
- Serving: reuse holidays/serving; add celebration-service attendance context if specs require.

### TEACHER ENRICHMENT
- **T1 · Multi-context portal**: a teacher sees every context (each dept's classes) and grades/takes
  attendance per context (choir class vs Ge'ez class), with per-department grading where needed.
- **T2 · File sharing**: extend R1 scope to class + specific students; student/parent "Files" view.
- **T3 · Communication**: targeted announcements + polled inbox first; threaded messaging only if wanted.
- **T4 · Custom grading schemes** (choir memorization/performance vs academic numeric). Spec-driven.

### CROSS-CUTTING (later)
- **Holy Communion tracking** (`people.last_communion_date` exists — needs UI + per-person workflow).
- **Finance/funds** generalized (course fees, shop, trips, charity, arts programs, building campaign).
- **Per-department reporting** (every dept wants easy report generation).

### Suggested order
`R1` (now, cheap) → `F2` → then parallel `D1`, `T1`, `T2` → `D2` → `Phase C` → `D3`+`T4` (with specs)
→ communion → finance → reporting.

---

## 4. Execution (orchestrated, token-aware)
Per phase: cheap-model audit agents pin current-state + call sites → I design schema/specs →
build agents (strong model for schema/security, cheap for CRUD/UI) own non-overlapping files →
I integrate, test on the disposable local DB, gate deploy on you. Token-lean by default (the user
explicitly wants minimal token spend — small phases, reuse existing tables like `resources`).

## 4b. Captured 2026-07-03 eve — fold into the plan tomorrow (NOT yet designed)
Raw requirements from the user, to be turned into real phases once the per-department specs land.

### Data migration — Excel import with column mapping (NEW capability)
- Admin (and maybe dept heads) upload an **Excel file** from their existing system; we present a
  **column-mapping UI** (their columns → our fields) and import (students, members, teachers, etc.).
- Eases the transition off their current Excel workflow. Goes beyond the existing simple CSV student
  import. New phase (call it **M1**). Needs a light spreadsheet parser (xlsx) — feasible in PHP.

### Teacher sharing model — files, announcements, events (extends R1 + T1/T3)
Precondition: **a teacher is assigned to department(s)** (F2/D1). Then, when sharing anything, the
teacher **selects the target scope**: department(s) / class(es) / grade(s) (incl. multiple grades) /
groups / specific students.
- **File sharing**: R1 exists (grade + dept). Extend the scope PICKER so a teacher chooses among
  their departments/classes/grades (multi-select) when sharing. → extends resources scope to class +
  multi-grade (T2).
- **Announcements**: teacher can post announcements scoped the same way (grade(s)/class(es)/topics).
- **Events with dept-head APPROVAL**: teachers can *propose* events; the **department head approves**
  (or the head creates events directly). Teachers can't unilaterally publish events. → events need a
  `status` (proposed/approved) + `created_by` + `department_id` + approval action. (User: "put the
  feature in; if it's not worth it we remove it later.")
- Open Q for tomorrow: do **announcements** also need dept-head approval, or only events?

### Teacher creation & multi-department assignment (the permissions model — extends F2/D1)
- **teacher ↔ department is many-to-many** (a teacher can serve several departments).
- **Admin = full CRUD** on everything (classes, courses, departments, …) AND can create a teacher and
  **assign them to multiple departments at once**. Every assigned department sees that person on its
  dashboard.
- **Dept head = scoped**: can **create a teacher (auto-assigned to their own department)** and
  **assign existing teachers to their own department ONLY** — never to other departments.
- A dept-head-created teacher can *also* be assigned to other departments (by admin / other heads
  pulling them in), but the creating head only controls their own dept membership.
- **Teacher gets a dashboard notification** telling them which department(s) they've been assigned to.
- → this refines F2 (`teacher↔department` link = many-to-many with created_by/assigned_by) and D1
  (scoped teacher creation + assignment), plus a teacher-facing notification.

### Where these slot (provisional)
`M1` Excel import → standalone, can come early. Teacher sharing/announcements/events + the
permissions model all depend on **F2** (dept↔teacher link) and **D1** (dept console), so they get
designed together once specs arrive.

## 5. The only decisions still open (rest is settled by the specs)
1. **Start R1 (resources per grade/dept) now?** It's small, reuses existing code, high value. — I recommend yes.
2. **F2 linchpin:** confirm `classes.department_id` (academic classes → ትምህርት ክፍል) so departments
   own their classes. Choir/arts *courses* modeled separately in Phase C (they have fees/instructors).
3. **Dept-head powers:** can a dept head create brand-new teacher logins (scoped), or assign
   existing teachers only (admin mints accounts)?
