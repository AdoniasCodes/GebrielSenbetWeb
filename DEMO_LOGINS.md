# Demo / Test Logins

Demo accounts are created on demand by the admin **Reset / Data** tool — no passwords
are stored in this repo.

## How to get working test logins
1. Log in as an admin.
2. Go to **Admin → System → Reset / Data**.
3. Enter the reset password (`Panda2022`) and click **"Reset with test accounts"**.
4. The page shows one login per role with a freshly generated password — **copy them
   immediately** (they're shown once). Locally they're also written to the gitignored
   `DEMO_LOGINS.local.md`.

This wipes all operational data, keeps your admin + reference scaffolding (roles,
churches, departments, grades, subjects), and creates the accounts below wired with
sample data so every dashboard shows something.

## The test accounts (fixed emails, password `demo1234`)
| Role | Email | Password | Dashboard |
|---|---|---|---|
| Teacher | `test-teacher@mekaneselamss.com` | `demo1234` | `/teacher/` — a class with Grades + Attendance |
| Student | `test-student@mekaneselamss.com` | `demo1234` | `/student/` — grade, attendance, payment balance |
| Parent  | `test-parent@mekaneselamss.com`  | `demo1234` | `/parent/`  — one linked child |
| Dept-head (staff) | `test-staff@mekaneselamss.com` | `demo1234` | `/staff/` — heads the Choir (መዝሙር) dept only |

Additionally, the reset creates **one dept-head login per non-archived department**
(`head-<slug>@mekaneselamss.com`, password `demo1234`) so every department's head view
is testable. Full list in `TESTER_LOGINS.md`.

## Going live (clean slate)
Admin → Reset / Data → **"Wipe to clean slate"** (pw `Panda2022`) deletes all operational
data AND the test accounts, keeping only your admin + reference data.

## Local dev admin
`admin@local.test` / `admin1234` (local database only).
