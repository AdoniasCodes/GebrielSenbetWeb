# Tester Logins — Gebriel Senbet School Portal

> Internal testing only. All passwords will be rotated after the testing round.
> Live site: **https://mekaneselamss.com** · Log in at **https://mekaneselamss.com/login**
> **Every account below uses the same password: `demo1234`**

## Core test accounts (one per role, wired with sample data)

| Role | Email | Where to test |
|---|---|---|
| Teacher | `test-teacher@mekaneselamss.com` | `/teacher/` — class with Grades + Attendance |
| Student | `test-student@mekaneselamss.com` | `/student/` — grades, attendance, payment balance |
| Parent | `test-parent@mekaneselamss.com` | `/parent/` — one linked child |
| Dept-head (staff) | `test-staff@mekaneselamss.com` | `/staff/` — heads the Choir (መዝሙር) department |

## Department-head accounts (one per department, `/staff/` portal)

| Department | Email |
|---|---|
| Education & Curriculum (ትምህርት ክፍል) | `head-timhirt@mekaneselamss.com` |
| Choir & Hymns (መዝሙር) | `head-mezmur@mekaneselamss.com` |
| Charitable Outreach | `head-outreach@mekaneselamss.com` |
| — Development (ልማት) | `head-limat@mekaneselamss.com` |
| — Pilgrimage & Travel (ጉዞ) | `head-guzo@mekaneselamss.com` |
| — Charity (በጎ አድራጎት) | `head-bego-adragot@mekaneselamss.com` |
| Fine Arts (ኪነ ጥበብ) | `head-kinetbeb@mekaneselamss.com` |
| Audio & Visual | `head-av@mekaneselamss.com` |
| Board of Admins | `head-board@mekaneselamss.com` |
| Secretariat | `head-secretariat@mekaneselamss.com` |
| Construction Committee | `head-construction@mekaneselamss.com` |
| Parents' Committee | `head-parents@mekaneselamss.com` |

All passwords: `demo1234`. Each head sees ONLY their own department in the staff portal.

## Admin tools (for the team, not the tester)

| What | Credential |
|---|---|
| Reset / Data tool password (Admin → System → Reset / Data) | `Panda2022` |
| Local dev admin (local DB only, not the live site) | `admin@local.test` / `admin1234` |

## Notes for the tester
- If accounts stop working, an admin re-runs **Admin → System → Reset / Data →
  "Reset with test accounts"** — this recreates every account above (password `demo1234`)
  with fresh sample data.
- The **live production admin password is not listed here** — it is held by the site owner.
- After testing is complete we will rotate every password above.
