# Task 3 — Remaining-work audit & phased plan (for approval, nothing built yet)

## What exists today (audited 2026-07-03)
- **Resources** (mig 017): table scoped `grade` (class_levels) OR `department`, kind file|link.
  Admin CRUD at `api/admin/resources/`. **Students/staff portals do NOT surface them** (admin-only).
- **Departments** (mig 012): all depts + sub-depts seeded incl. `mezmur`; choir **advancement ladder**
  seeded in `department_levels` (መደበኛ አገልጋይ → ተተኪ1 → ተተኪ2 → ቀዳማይ); `department_memberships` with
  is_head/level. Staff portal shows a dept's roster/levels/eligibility. **No choir-specific modules.**
- **Attendance/eligibility** (mig 015/016): academic + department attendance, holiday/serving calendar,
  configurable serving-eligibility engine. Solid base for choir advancement.
- **Video** (mig 010): manual URL paste only. No channel/RSS auto-fetch.
- **Finance:** only tuition `payments`. No fund/course-fee/donation concept.
- **Communion:** `people.last_communion_date` column exists; no tracking UI/workflow.

## Proposed phases (in order; each ends with report + your OK before the next)

### Phase 3.0 — Resources in student & staff portals  ·  SMALL (~½ day)
The smallest real win; unblocks a feature already built for admins.
- Read-only scoped endpoints: `api/student/resources` (grade of the student's class) +
  `api/staff/resources` (departments the staff head). Reuse existing scoping guards.
- Add a "Resources / ግብዓቶች" panel to `public/student/index.php` and `public/staff/index.php`.
- Delegates + local test. Low risk, no schema change.

### Phase 3.1 — YouTube channel RSS auto-fetch  ·  SMALL (~½ day, independent)
Can slot anytime; needs **one input from you: the channel URL/ID**.
- Extend `video_embeds.video_url` to accept a channel URL; `/api/videos` resolves the latest video
  server-side from `youtube.com/feeds/videos.xml?channel_id=…`, cached ~30 min, falls back to the last
  manual video if the fetch fails. No API key. (TikTok stays manual — the three walls still hold.)

### Phase C — Choir (መዝሙር) module  ·  LARGE — the distinctive feature. Decomposed:
- **C1 — Song/Hymn catalog** (new mig): songs (title_am, category holiday|celebration|course, grade
  link optional) + per-member memorization/qualification records. Admin CRUD + choir-head view.
- **C2 — Advancement workflow:** tie level promotion to memorization + performance + academic grades +
  attendance (the eligibility engine already computes attendance %). Promote/deny UI for the choir head.
- **C3 — Celebration services:** the 3rd attendance context (weddings/graduations), gated to the top
  two levels + a *separate* celebration-song repertoire qualification (per domain model, added 2026-06-21).
- **C4 — Extracurricular courses** (በገና/ከበሮ): course catalog, enrollment, fees, alumni instructors,
  fund tracking → this is where **finance** starts.
- **C5 — Choir dept dashboard variant** (per-department dashboards, per domain model).
> Recommendation: build C1→C2 first (highest value, reuses attendance), pause for review, then C3/C4/C5.

### Phase D — Finance → Communion → other dept tools → reporting  ·  LATER
- Generalize funds (course fees, shop, trips, charity, fine-arts events) beyond tuition.
- Communion tracking UI on `people.last_communion_date` (mission-critical metric).
- Per-department tools + easy report generation; remaining dept dashboards.

## Decisions I need from you to proceed
1. Confirm the **order** above (3.0 → 3.1 → C1/C2 → review → …), or reprioritize.
2. For 3.1: the **YouTube channel URL/ID**.
3. For Phase C: confirm **C1→C2 as the first buildable slice**, or you want the full C-scope specced first.
