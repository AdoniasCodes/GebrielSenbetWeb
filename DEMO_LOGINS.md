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

## The test accounts (fixed emails, random password each reset)
| Role | Email | Dashboard |
|---|---|---|
| Teacher | `test-teacher@mekaneselamss.com` | `/teacher/` — a class with Grades + Attendance |
| Student | `test-student@mekaneselamss.com` | `/student/` — grade, attendance, payment balance |
| Parent  | `test-parent@mekaneselamss.com`  | `/parent/`  — one linked child |
| Dept-head (staff) | `test-staff@mekaneselamss.com` | `/staff/` — heads the Choir (መዝሙር) dept only |

## Going live (clean slate)
Admin → Reset / Data → **"Wipe to clean slate"** (pw `Panda2022`) deletes all operational
data AND the test accounts, keeping only your admin + reference data.

## Local dev admin
`admin@local.test` / `admin1234` (local database only).
