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

## Optimizing a video for the site (added 2026-08-02)

Source clips come off a phone at ~1.5 MB/s (a 74s 1080p `.MOV` was 110 MB). Never commit the raw
file. Produce one 720p MP4 plus a WebP poster, both under `public/media/`.

```sh
SRC=/path/to/IMG_XXXX.MOV
NAME=life-together          # asset basename

# 1. Inspect the source (duration, resolution, whether it carries rotation metadata)
ffprobe -v error -show_entries stream=codec_name,width,height,r_frame_rate \
        -show_entries format=duration,size,bit_rate -of default=noprint_wrappers=1 "$SRC"

# 2. Encode: 720p H.264, CRF 28, faststart so it streams before the file finishes downloading
ffmpeg -y -i "$SRC" -vf "scale=-2:720" \
  -c:v libx264 -profile:v high -level 4.0 -crf 28 -preset slow -pix_fmt yuv420p -g 60 \
  -movflags +faststart -c:a aac -b:a 96k -ac 2 -ar 44100 \
  public/media/$NAME-720.mp4

# 3. Poster from a representative frame (this ffmpeg has no libwebp; go via JPEG + cwebp)
ffmpeg -y -v error -ss 54.5 -i "$SRC" -frames:v 1 -vf "scale=1280:-2" -q:v 4 /tmp/poster.jpg
cwebp -q 80 /tmp/poster.jpg -o public/media/$NAME-poster.webp

# 4. Verify faststart — moov must appear BEFORE mdat
python3 -c "d=open('public/media/$NAME-720.mp4','rb').read(4000000); print('moov',d.find(b'moov'),'mdat',d.find(b'mdat'))"
```

**CRF choice:** 25 → 16 MB, 28 → 12 MB on the same 74s clip, and the two were indistinguishable at
a 1:1 crop of the *hardest* shot (night-time, high motion, noisy). Compare crops before spending
bitrate:
`ffmpeg -ss 22 -i out.mp4 -frames:v 1 -vf "crop=640:360:200:120" crop.png`

**Do not bother with VP9/WebM.** Tried at CRF 34: 18 MB versus 12 MB for the H.264. libvpx loses on
this kind of handheld crowd footage, and every browser we support plays H.264.

**Markup rules** (see the `#life` section of `public/index.php` for the reference implementation):
- `preload="none"` + `poster=` so the page pays only the poster's weight until someone presses play.
- Do NOT put `controls` in the HTML — the native control bar paints on top of the poster. Set
  `video.controls = true` in the play handler, and back to `false` on `ended`.
- An overlay scrim over a bright frame needs a radial vignette, not a flat tint; `bg-ink/40` and a
  weak linear gradient both left white label text illegible. Screenshot it, never assume.
- Bilingual `data-en`/`data-am` on the button label and figcaption. `applyLang()` assigns
  `el.innerHTML`, so only ever put those attributes on leaf elements.

**Verifying playback locally** — headless Chrome alone cannot click. Install `puppeteer-core` in the
scratchpad (system Chrome is the executable) and assert the real states: pre-play `controls=false`,
post-click `paused=false` + `videoWidth` non-zero, and `ended` → overlay restored.

## Social accounts: changing a handle, or debugging the YouTube feed

Both handles live in **one place**: the `social` block in `config/config.php`. Change them there
and the landing band, both footers, and the JSON-LD `sameAs` all follow. Nothing is in the DB.

### Getting a YouTube channel id from a handle
The Atom feed needs the `UC…` id, not the `@handle`:
```bash
UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
curl -sL -A "$UA" "https://www.youtube.com/@THEHANDLE" | grep -oE '"externalId":"UC[A-Za-z0-9_-]{22}"' | head -1
```

### The two traps in the YouTube Atom feed
1. **It is gated on User-Agent.** No UA gets you HTTP 500, a custom bot UA (`MySite/1.0`) gets you
   404, and only a normal browser UA gets 200. `api/social/youtube.php` sends a browser UA for
   this reason; do not "clean it up" into a polite bot string.
2. **YouTube soft-blocks your IP after roughly ten feed requests in a few minutes**, and the block
   returns 404, which reads exactly like a wrong channel id. Before you go hunting for a bad id,
   test a control channel:
   ```bash
   curl -sL -A "$UA" "https://www.youtube.com/feeds/videos.xml?channel_id=UC_x5XG1OV2P6uZZ5FSM9Ttw" -o /dev/null -w "%{http_code}\n"
   ```
   If Google's own developer channel also 404s, you are rate limited, not misconfigured. Wait it
   out. The endpoint caches for 6h in `tmp/social/` (gitignored) and serves the stale cache when a
   fetch fails, so production absorbs this without the section going blank.

### Testing the feed without the network
The parser can be exercised on a saved feed without touching the endpoint's execution path:
```bash
php -r '
$src = file_get_contents("api/social/youtube.php");
preg_match("/function yt_parse_feed.*?\n}/s", $src, $m); eval($m[0]);
var_dump(yt_parse_feed(file_get_contents("/path/to/saved-feed.xml")));'
```
To see the landing page render with real videos while blocked, write
`tmp/social/youtube-<CHANNELID>.json` as `{"fetched_at": <now>, "videos": [...]}` and the endpoint
serves it straight from cache.

### TikTok
There is no public feed. The profile HTML carries the account stats but **no video ids** (the list
is lazy-loaded from an internal API), so the "Latest on TikTok" section can only be filled by
pasting video URLs in admin → Videos with section `tiktok_latest`. The follow link and the footer
icons are static and need nothing.

### Screenshotting a section during UI work
Zero-dependency CDP driver (system Chrome, no puppeteer install) lives in the session scratchpad as
`shot.mjs`; node 22+ has a global `WebSocket`, so it needs no packages:
```bash
php -S 127.0.0.1:8899 -t public &
W=390 CLICK='[data-lang-toggle] button[data-lang="am"]' \
  node shot.mjs http://127.0.0.1:8899/ ./out "#follow" "body > footer"
```
`W` sets the emulated viewport width, `CLICK` clicks a selector before capture (used for the
Amharic pass). It clips to each element's bounding box, so a `hidden` section reports height 0
instead of producing a blank PNG.

## Adding a migration (the pattern this repo actually uses)

1. Write `db/migrations/0NN_name.sql`. Start with `SET NAMES utf8mb4`, end with a
   marker row so the runner can prove it landed:
   ```sql
   INSERT INTO app_settings (setting_key, setting_value)
   VALUES ('migration_0NN_applied', '1')
   ON DUPLICATE KEY UPDATE setting_value = '1';
   ```
2. Register the probe in `_migration_artifact_present()` in
   `api/admin/deploy/migrate.php`. **Skipping this is not cosmetic:** the runner
   uses probes to self-heal a tracker row whose schema artifact is missing, and an
   unregistered migration returns null and loses that protection.
3. Apply locally: `curl -s -X POST -H "X-DEPLOY-TOKEN: <token>" http://127.0.0.1:8899/api/admin/deploy/migrate.php`
   The token is `app.deploy_token` in `config/config.php`.
4. The runner splits statements on `;` followed by a newline, so never put a
   semicolon mid-statement before a line break. It also skips the transaction
   wrapper whenever the file contains DDL, because MySQL implicitly commits DDL.

### Unique constraints on soft-deleted tables
Everything here soft-deletes with `is_archived`, so a plain `UNIQUE(a, b)` is
almost always wrong: it permanently blocks the normal "archive the old row, add a
new one" flow. Use the guard-column pattern from `025_join_table_integrity.sql`:
```sql
ALTER TABLE t ADD COLUMN active_guard TINYINT
  GENERATED ALWAYS AS (IF(is_archived = 0, 1, NULL)) STORED;
ALTER TABLE t ADD UNIQUE KEY uq_t_active (a, b, active_guard);
```
NULLs are distinct inside a MySQL unique index, so archived rows never collide
while at most one active row survives. STORED (not VIRTUAL) keeps it indexable on
both MySQL and MariaDB. Dedup the existing actives in the same migration first.

**After adding any constraint, sweep the write paths.** Adding 025 turned two
latent bugs into real ones: an un-archive `UPDATE ... WHERE person_id=? AND
department_id=?` with no LIMIT, and an insert whose only duplicate check covered
one enum value. Grep every `INSERT INTO <table>` and every `SET is_archived=0`
before shipping, and make duplicates return 409 rather than a generic 500.

## Testing endpoints and portals locally

Chained curl subshells flake on this machine, so HTTP tests go through Python
with a cookie jar. Both harnesses live in the session scratchpad and are worth
recreating if a session starts without them:

- `api_test.py`: a `Client` with login + CSRF handling. Note that the session id
  rotates on login, so the CSRF token must be re-read *after* logging in.
- `portal.mjs`: zero-dependency CDP driver over system Chrome (node 22+ has a
  global `WebSocket`, so no puppeteer install). Logs in inside the page, opens a
  portal, optionally runs an in-page script, clips a screenshot to one selector,
  and reports console errors. `W=390` emulates mobile.

```sh
APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel APP_DB_USER=eagleerq_gebriel \
  APP_DB_PASS=gebrieldbpw php -S 127.0.0.1:8899 -t public &
DEMO_PASSWORD='demo1234' php scripts/seed_demo_users.php   # fixes local logins
node portal.mjs test-admin@mekaneselamss.com demo1234 /admin/index.php nav out.png
```

Demo accounts are `test-<role>@mekaneselamss.com` and `head-<slug>@mekaneselamss.com`.
`teacher@demo.mekaneselamss.com` style addresses come from the *setup endpoint*,
not the seeder, and will 401 if only the seeder has been run.

### The public registrations feed has a frozen contract
`GET /api/registrations/index.php` returns fields as `type` and `required`, NOT
`field_type` and `is_required` like the lib does. It is documented as a frozen
contract at the top of the file because the landing JS consumes it. A test that
reads the lib's key names off the public feed will silently see nulls.
