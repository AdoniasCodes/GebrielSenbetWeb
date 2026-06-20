# Gebriel Senbet — Local Demo Logins

**Site:** http://127.0.0.1:8080  (local only — runs on this Mac)

**Password for ALL demo accounts:** `demo1234`
**Admin password is different** (see below).

> If the site doesn't load, the local PHP server isn't running. Ask me to start it, or run:
> `cd /Users/eyoel/vibecoding/GebrielSenbetWeb && APP_ENV=development /opt/homebrew/bin/php -S 127.0.0.1:8080 -t public`
>
> To reset/refresh all demo data: `php db/seeds/demo_seed.php` (wipes demo rows, keeps the admin).

---

## Admin (full access)
| Email | Password |
|---|---|
| `admin@local.test` | `admin1234` |

Lands on the admin dashboard → People, Departments, Curriculum, Students, Grades, Payments, etc.

## Department heads — Staff portal (`/staff/`)
Each manages **only their own department's** roster + levels. Password `demo1234`.

| Email | Department |
|---|---|
| `head-timhirt@demo.gebriel` | ትምህርት ክፍል — Education & Curriculum |
| `head-mezmur@demo.gebriel` | መዝሙር ክፍል — Choir & Hymns |
| `head-outreach@demo.gebriel` | በጎ አድራጎት ክፍል — Charitable Outreach |
| `head-limat@demo.gebriel` | ልማት ክፍል — Development |
| `head-guzo@demo.gebriel` | ጉዞ ክፍል — Pilgrimage & Travel |
| `head-bego-adragot@demo.gebriel` | በጎ አድራጎት — Charity |
| `head-kinetbeb@demo.gebriel` | ኪነጥበብ ክፍል — Fine Arts |
| `head-av@demo.gebriel` | ኦዲዮ ቪዥዋል ክፍል — Audio & Visual |
| `head-board@demo.gebriel` | የቦርድ አስተዳደር — Board of Admins |
| `head-parents@demo.gebriel` | ወላጆች ኮሚቴ — Parents' Committee |

## Teachers (teacher portal — currently a stub)
`teacher1@demo.gebriel` … `teacher7@demo.gebriel`

## Students (student portal — currently a stub)
`student1@demo.gebriel` … `student16@demo.gebriel`

## Parents (parent portal)
`parent24@demo.gebriel`, `parent25@demo.gebriel` — each linked to two children.

---
*Teacher/Student portals are not built yet (blank pages). Admin, Staff portal, and Parent portal are functional.*
