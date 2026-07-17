# Project Instructions — GebrielSenbetWeb

## Seed demo users

`scripts/seed_demo_users.php` is a CLI-only, idempotent seeder that creates or
resets one demo login per role (admin, teacher, student, parent) plus one
department-head (staff) login per non-archived department
(`head-<slug>@mekaneselamss.com`), so per-department scoping is testable.
It re-hashes with `password_hash(..., PASSWORD_DEFAULT)` — exactly what
`api/auth/login.php` verifies — which is the fix for the recurring
"invalid credentials" breakage. It never wipes data and never stores a
password in the repo. Each run regenerates `DEMO_LOGINS.md` from the database.

### Seed (create or reset the accounts)

```sh
# Choose the shared demo password yourself:
DEMO_PASSWORD='pick-something' php scripts/seed_demo_users.php

# Or let it generate one (printed once at the end — save it):
php scripts/seed_demo_users.php

# Optionally write the actual password into DEMO_LOGINS.md (default is a placeholder):
DEMO_PASSWORD='pick-something' php scripts/seed_demo_users.php --write-password
```

DB credentials come from `config/config.php`, same as the app. Override with
env vars when needed: `APP_DB_HOST`, `APP_DB_NAME`, `APP_DB_USER`, `APP_DB_PASS`.

### Verify (smoke-test every login over HTTP)

Verify mode needs no DB access — it reads the account emails from
`DEMO_LOGINS.md` and POSTs each `{email, password}` to `/api/auth/login.php`,
printing PASS/FAIL per account (non-zero exit if any fail).

```sh
# Against local dev server (default base URL http://127.0.0.1:8080):
DEMO_PASSWORD='the-password' php scripts/seed_demo_users.php --verify

# Against production:
DEMO_PASSWORD='the-password' php scripts/seed_demo_users.php --verify --base-url=https://mekaneselamss.com
```

### Local vs production

- **Local:** start the dev server first, then seed + verify:
  ```sh
  APP_ENV=development APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel \
    APP_DB_USER=eagleerq_gebriel APP_DB_PASS=... php -S 127.0.0.1:8080 -t public
  APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel APP_DB_USER=eagleerq_gebriel \
    APP_DB_PASS=... DEMO_PASSWORD='...' php scripts/seed_demo_users.php
  DEMO_PASSWORD='...' php scripts/seed_demo_users.php --verify
  ```
  **GOTCHA — empty DB password won't work.** `config/config.php` uses
  `getenv('APP_DB_PASS') ?: 'Panda2022!!'`, so an *empty* `APP_DB_PASS=''` is
  falsy and silently falls back to the PROD password (login then fails). Local
  MySQL `root` has no password, so you can't use it via the app. Fix once:
  ```sh
  mysql -u root -e "CREATE USER IF NOT EXISTS 'gsb'@'localhost' IDENTIFIED BY 'gsblocal';
    GRANT ALL PRIVILEGES ON eagleerq_gebriel.* TO 'gsb'@'localhost'; FLUSH PRIVILEGES;"
  # then use APP_DB_USER=gsb APP_DB_PASS='gsblocal' everywhere above.
  ```
  Apply a single migration locally: `mysql -u gsb -pgsblocal eagleerq_gebriel < db/migrations/NNN.sql`.
  CSRF for curl tests: GET `/api/auth/csrf.php` (JSON key is `csrf_token`, not
  `token`), carry the cookie jar, send it back as the `X-CSRF-Token` header.
- **Production (cPanel):** after `git push` deploys, open the cPanel Terminal
  (or SSH) and run from the deployed repo root:
  ```sh
  # .cpanel.yml rsyncs the repo to /home/mekanefh/public_html — run from there
  cd /home/mekanefh/public_html
  DEMO_PASSWORD='...' php scripts/seed_demo_users.php
  ```
  (The script refuses to run under a web SAPI, so having it inside
  `public_html` does not expose a web-triggerable seeder.)
  Then from any machine:
  ```sh
  DEMO_PASSWORD='...' php scripts/seed_demo_users.php --verify --base-url=https://mekaneselamss.com
  ```
  On production the seeder uses the live `config/config.php` credentials
  automatically. Never commit the chosen `DEMO_PASSWORD` anywhere; if the
  seeder generated one, it is shown once in the terminal only.

### Notes

- Re-running is always safe: existing demo accounts are updated in place
  (password reset, role corrected, un-archived), never duplicated.
- The seeder creates only the minimal linked rows (teacher/student/people
  records, parent-student link, dept-head memberships). For fully wired sample
  data (classes, grades, attendance, payments) use the admin tool:
  Admin → System → Reset / Data → "Reset with test accounts".
- `test-admin@mekaneselamss.com` is a separate demo admin; the real production
  admin account is never touched.

## Public registration system (added 2026-07-12)

- Migration: `db/migrations/019_registrations.sql` (tables `registration_forms`, `registration_form_fields`, `registration_submissions` + seeded forms). Apply on prod via the migrate endpoint after deploy.
- Public API: `GET /api/registrations/index.php` (forms + fields, frozen contract used by landing JS), `POST /api/registrations/submit.php` (JSON `{form_id, answers:{fieldId:value}, website:""}`, header `X-CSRF-Token` from `/api/auth/csrf.php`; honeypot field `website`; 5/hour/IP/form flood guard).
- Admin: `/admin/registrations.php` (nav Community → Registrations). Actions API `api/admin/registrations/index.php` — POST `{action: 'form.create'|'form.update'|'form.archive'|'field.create'|'field.update'|'field.archive'|'field.reorder'|'submission.status'|'submission.archive', ...}`; GET `?resource=submissions&form_id=N`.
- Dept heads: same actions via `api/staff/registrations.php`, scoped to departments they head, EXCEPT `form.create` which is admin-only server-side since Phase 1.3 (heads customize existing forms; they cannot create forms or reassign a form's department). UI section "Public registrations" in `/staff/`.
- Form ownership defaults: sunday-school → timhirt, begena → mezmur, gishen-pilgrimage → guzo (Pilgrimage & Travel). Admin can reassign via form.update.
