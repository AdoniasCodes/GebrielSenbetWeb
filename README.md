# Church Education Management System

Production-ready PHP (8+) + MySQL app designed for shared hosting (cPanel).

## Stack
- PHP 8+, Vanilla JS, HTML/CSS
- MySQL (InnoDB), PDO prepared statements
- Session-based auth, RESTful JSON APIs

## Structure
- `public/` — public pages (document root)
- `api/` — JSON endpoints
- `src/` — PHP libraries (Database, Utils)
- `config/` — environment configuration
- `db/migrations/` — SQL migrations

## Local Setup (cPanel-compatible)
1. Create a MySQL database and user; grant privileges.
2. Copy repo to hosting.
3. Set document root to `public/`.
4. Update `config/config.php` with DB credentials and app settings.
5. Import `db/migrations/001_initial_schema.sql` into the database.

## Security
- CSRF token required for state-changing requests (send `X-CSRF-Token`).
- Sessions are used for authentication; session cookie is `HttpOnly`.
- No hard deletes; archival implemented via `is_archived` and `archived_at`.

## First Endpoints
- `POST /api/auth/login.php`
- `POST /api/auth/logout.php`

## Next
See `project_context.md` for ongoing status and plans.
# GebrielSenbetWeb
