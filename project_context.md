# Project Context Log

Date: 2026-01-19T08:50:06+03:00
Task performed: Initialize project, create structure, add base config, DB layer, utilities, auth endpoints, public pages, and initial DB schema.
Files affected: 
- config/config.php
- src/Database.php
- src/Utils/Response.php
- src/Utils/Csrf.php
- api/auth/login.php
- api/auth/logout.php
- public/index.php
- public/login.html
- db/migrations/001_initial_schema.sql
- README.md
Database changes: Added initial schema migration file (not yet applied).
Reason for change: Establish production-ready foundation aligned with cPanel constraints, PHP 8+, MySQL (InnoDB), RESTful JSON APIs, and session-based auth.
Next steps: 
- Apply the migration to MySQL and verify constraints.
- Implement role-based redirects post-login and basic dashboards per role.
- Add CSRF token issuance endpoint and integrate on frontend.
- Add blog endpoints and file upload handling with server-side storage.
- Add admin CRUD endpoints for tracks, levels, classes, subjects, users, and assignments.
- Add grade and payment endpoints.
- Add events/notifications endpoints and recurrence logic.
- Configure .htaccess for security and routing as needed.

System status: Skeleton established; no data yet; endpoints: auth login/logout; schema file ready.
Architectural assumptions:
- Session-based authentication with PHP sessions stored server-side.
- CSRF token passed via `X-CSRF-Token` header for state-changing requests.
- No hard deletes; `is_archived` and `archived_at` used.
- JSON-only APIs in `api/` directory; public pages in `public/`.
- PDO with prepared statements only.
- cPanel deploy will point document root to `public/`.
Pending tasks:
- All CRUD/API beyond auth.
- Frontend dashboards and routing.
- Cron for event notifications (if needed) respecting shared hosting rules.
- Comprehensive documentation for deployment.
---

Date: 2026-01-19T08:50:06+03:00
Task performed: Added central bootstrap (autoload + config + session), exposed API under public via wrappers, updated entrypoints to use bootstrap, added public/.htaccess with security headers, and corrected redirects for public docroot.
Files affected:
- bootstrap.php (new)
- api/auth/login.php (updated to use bootsrap)
- api/auth/logout.php (updated to use bootstrap)
- public/api/auth/login.php (new wrapper)
- public/api/auth/logout.php (new wrapper)
- public/index.php (updated to use bootstrap and corrected redirects)
- public/.htaccess (new)
Database changes: None.
Reason for change: Ensure APIs are reachable with document root set to , centralize autoload/config to avoid Composer and duplicate includes, and harden the public directory for production on cPanel.
Next steps:
- Implement role dashboards: , ,  and redirect based on role after login.
- Add explicit CSRF token fetch endpoint if needed for preflight.
- Begin admin CRUD APIs (tracks, levels, classes, subjects, users, assignments) per schema with prepared statements and RBAC.
- Seed configurable tracks/levels via migration or admin UI.
- Add root-level  to deny access if root mistakenly set as docroot.
Current system status: Bootstrap in place; public API wrappers available; login/logout functional pending DB and user seeding.
---

Date: 2026-01-19T08:50:06+03:00
Task performed: Added central bootstrap (autoload + config + session), exposed API under public via wrappers, updated entrypoints to use bootstrap, added public/.htaccess with security headers, and corrected redirects for public docroot.
Files affected:
- bootstrap.php (new)
- api/auth/login.php (updated to use bootstrap)
- api/auth/logout.php (updated to use bootstrap)
- public/api/auth/login.php (new wrapper)
- public/api/auth/logout.php (new wrapper)
- public/index.php (updated to use bootstrap and corrected redirects)
- public/.htaccess (new)
Database changes: None.
Reason for change: Ensure APIs are reachable with document root set to public/, centralize autoload/config to avoid Composer and duplicate includes, and harden the public directory for production on cPanel.
Next steps:
- Implement role dashboards: public/admin/index.php, public/teacher/index.php, public/student/index.php and redirect based on role after login.
- Add explicit CSRF token fetch endpoint if needed for preflight.
- Begin admin CRUD APIs (tracks, levels, classes, subjects, users, assignments) per schema with prepared statements and RBAC.
- Seed configurable tracks/levels via migration or admin UI.
- Add root-level .htaccess to deny access if root mistakenly set as docroot.
Current system status: Bootstrap in place; public API wrappers available; login/logout functional pending DB and user seeding.
---

Date: 2026-01-19T09:01:13+03:00
Task performed: Added CSRF token endpoint, created role dashboards and updated role-based redirects, added seed migration for tracks/levels, and created one-time admin setup endpoint with config setup token.
Files affected:
- api/auth/csrf.php (new)
- public/api/auth/csrf.php (new wrapper)
- public/admin/index.php (new)
- public/teacher/index.php (new)
- public/student/index.php (new)
- public/index.php (updated redirects)
- db/migrations/002_seed_tracks_levels.sql (new)
- api/setup/create_admin.php (new)
- public/api/setup/create_admin.php (new wrapper)
- config/config.php (updated with setup_token)
Database changes: New seed migration 002_seed_tracks_levels.sql (not yet applied).
Reason for change: Enable CSRF prefetch, role isolation with dashboards, initial track/level seeding, and secure bootstrap of first admin on fresh deployments.
Next steps:
- Configure DB credentials in config/config.php or via cPanel environment variables.
- Apply migrations 001 and 002 to MySQL (via phpMyAdmin or mysql CLI).
- Set APP_SETUP_TOKEN securely in cPanel; call /public/api/setup/create_admin.php to create the first admin; then clear or rotate the token.
- Begin implementing Admin CRUD REST endpoints (tracks, levels, classes, subjects, users, assignments), then Teacher grade endpoints, Payments APIs, Blog and file uploads, Events & Notifications.
Current system status: Login/logout and role dashboards wired; CSRF endpoint available; schema and seeding scripts present; pending DB apply and admin creation.
---

Date: 2026-01-19T09:24:42+03:00
Task performed: Implemented Admin CRUD endpoints for education tracks and class levels with RBAC and CSRF; added public wrappers.
Files affected:
- api/admin/_guard.php (new)
- api/admin/tracks/index.php (new with GET/POST/PUT/DELETE)
- api/admin/levels/index.php (new with GET/POST/PUT/DELETE)
- public/api/admin/tracks/index.php (new wrapper)
- public/api/admin/levels/index.php (new wrapper)
Database changes: None (relies on existing schema).
Reason for change: Provide secure admin management for configurable education tracks and levels, enforcing no hard deletes (archive only) and role isolation.
Next steps:
- Implement Admin CRUD for subjects, classes, teacher assignments, users/students/teachers (with transactions for promotions).
- Add validation utilities and centralized error handling conventions.
- Build minimal admin pages to manage tracks and levels via fetch calls to these endpoints.
Current system status: Admin endpoints for tracks and levels are available; public wrappers expose them under public/; ready for UI integration.
---

Date: 2026-01-19T09:24:42+03:00
Task performed: Added Admin CRUD endpoints for subjects and classes with RBAC and CSRF; added public wrappers; enhanced Admin dashboard UI to manage tracks and levels.
Files affected:
- api/admin/subjects/index.php (new with GET/POST/PUT/DELETE)
- public/api/admin/subjects/index.php (new wrapper)
- api/admin/classes/index.php (new with GET/POST/PUT/DELETE)
- public/api/admin/classes/index.php (new wrapper)
- public/admin/index.php (enhanced UI to manage tracks and levels)
- src/Utils/Validation.php (new)
Database changes: None (uses existing schema).
Reason for change: Progress feature set for admin to manage core education structure components through secure REST APIs and simple UI.
Next steps:
- Build Admin UI for subjects and classes to use the new endpoints.
- Implement Users (admin/teacher/student) management endpoints and pages; student auto-credential generation; teacher profiles.
- Implement Teacher Subject Assignments with single primary enforcement (transactional check).
- Implement Grades endpoints (teacher-only permissions) and Student Class Promotions (archive old, create new, transactional).
- Implement Payments endpoints and UI (admin-only) including bulk unpaid queries.
- Implement Blog endpoints and file uploads with secure storage and metadata.
- Implement Events & Recurrence logic and Notifications targeting (roles/classes/subjects/payment defaulters/events).
Current system status: Admin can manage tracks and levels via UI; subjects/classes endpoints available for upcoming UI wiring.
---

Date: 2026-01-19T09:43:24+03:00
Task performed: Wired Admin UI for Subjects and Classes (list/add/edit/archive). Implemented Admin Users management endpoints with student auto-credential generation. Implemented Teacher Subject Assignments endpoints with single-primary rule.
Files affected:
- public/admin/index.php (wired Subjects and Classes UI)
- api/admin/users/index.php (new)
- public/api/admin/users/index.php (new wrapper)
- src/Utils/Password.php (new)
- api/admin/assignments/index.php (new)
- public/api/admin/assignments/index.php (new wrapper)
Database changes: None (uses existing schema).
Reason for change: Deliver core admin capabilities for managing curriculum structure, users, and teacher assignments with historical tracking and role isolation.
Next steps:
- Add Admin UI pages/forms for Users (admin/teacher/student) and Assignments (primary/substitute with date ranges).
- Add validation utilities expansion and unified error messages for all endpoints.
- Implement grades endpoints (teacher-only), promotions (transactional), payments (admin-only), blog with uploads, events & notifications.
Current system status: Admin UI supports tracks/levels, subjects and classes wired; Users and Assignments APIs ready for UI integration.
---

Date: 2026-01-20T12:11:37+03:00
Task performed: Built Admin UI pages for Users (create/list/edit/archive with pagination/search) and Teacher Subject Assignments (filter/list/create/edit/archive). Added teachers list endpoint. Wired client-side CSRF handling.
Files affected:
- public/admin/users.php (new)
- public/admin/assignments.php (new)
- api/admin/teachers/list.php (new)
- public/api/admin/teachers/list.php (new wrapper)
Database changes: None (uses existing schema).
Reason for change: Enable admin to manage users with auto-credentials and teacher subject assignments from the web UI; support filters and search for productivity.
Next steps:
- Add Admin UI navigation links across admin pages.
- Implement Grades endpoints and teacher UI; Promotions (transactional), Payments (admin) with bulk queries; Blog with uploads; Events & Notifications targets.
Current system status: Admin dashboard manages tracks/levels; Subjects/Classes wired; Users and Assignments UI pages available; endpoints secured with RBAC+CSRF.
---

Date: 2026-01-25T15:45:20+03:00
Task performed: Added CI/CD scaffolding: deploy_token config, secure migration endpoint (X-DEPLOY-TOKEN), public wrapper, GitHub Actions FTP deploy workflow, and .gitignore. Switched migration auth to deploy_token.
Files affected:
- config/config.php (added deploy_token)
- api/admin/deploy/migrate.php (uses deploy_token)
- public/api/admin/deploy/migrate.php (wrapper)
- .github/workflows/deploy.yml (new)
- .gitignore (new)
Database changes: None (migration runner executes existing SQL migrations).
Reason for change: Enable seamless version control and CI/CD from GitHub to cPanel with automatic DB migrations, avoiding manual copy-paste/SQL changes.
Next steps:
- Create a private GitHub repo and push this codebase.
- Configure GitHub secrets: FTP_SERVER, FTP_USERNAME, FTP_PASSWORD, FTP_PORT (21 or 990), FTP_ROOT_DIR, APP_BASE_URL (https://gebriel.eagleeyebgp.com), APP_DEPLOY_TOKEN (same as config).
- Configure cPanel environment variable APP_DEPLOY_TOKEN to match GitHub secret; ensure document root points to public/.
- Commit to main to trigger workflow; verify migration endpoint returns applied/skipped and no failures.
Current system status: CI/CD scaffolding ready; deploy token auth in place; production deployment path standardized.
---

Date: 2026-01-25T15:45:20+03:00
Task performed: Planned and documented CI/CD and remote DB connectivity steps; no code changes in this step.
Files affected: None
Database changes: None
Reason for change: Provide a production-grade, automated deployment path from GitHub to cPanel with automatic DB migrations, and instructions to connect local app to cPanel MySQL for seamless development.
Next steps:
- Create GitHub repo and push code; configure repo secrets (FTP_SERVER, FTP_USERNAME, FTP_PASSWORD, FTP_PORT, FTP_ROOT_DIR, APP_BASE_URL, APP_DEPLOY_TOKEN).
- Configure cPanel environment variable APP_DEPLOY_TOKEN to match the GitHub secret and ensure document root is public/.
- (Optional) Configure Remote MySQL in cPanel to allow local IP; update local APP_DB_HOST to cPanel MySQL hostname and use cPanel DB user/pass for direct dev against prod DB (or create a staging DB).
- Trigger GitHub Actions by pushing to main; verify FTP upload and migration run succeed; monitor response from /api/admin/deploy/migrate.php.
