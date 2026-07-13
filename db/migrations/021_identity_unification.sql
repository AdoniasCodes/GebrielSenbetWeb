-- 021_identity_unification.sql
-- Phase 1.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md §7): `people` becomes the single
-- source of personal identity. Every student/teacher row (archived included;
-- migration 018 only covered non-archived) gets a linked person, and parent/
-- staff logins get a canonical person row. Code side: person_accounts_lib now
-- creates people rows for all roles except admin, and the parents endpoint
-- writes through people. Idempotent: safe to re-run.

SET NAMES utf8mb4;

-- 1) Link role rows to an existing person via shared user_id.
UPDATE students s JOIN people p ON p.user_id = s.user_id
   SET s.person_id = p.id
 WHERE s.person_id IS NULL;

UPDATE teachers t JOIN people p ON p.user_id = t.user_id
   SET t.person_id = p.id
 WHERE t.person_id IS NULL;

-- 2) Create people for students/teachers that still lack one, then link.
INSERT INTO people (user_id, first_name, last_name, phone, date_of_birth, address, is_archived, archived_at)
SELECT s.user_id, s.first_name, s.last_name, s.phone, s.date_of_birth, s.address, s.is_archived, s.archived_at
  FROM students s
 WHERE s.person_id IS NULL
   AND s.user_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM people p WHERE p.user_id = s.user_id);

UPDATE students s JOIN people p ON p.user_id = s.user_id
   SET s.person_id = p.id
 WHERE s.person_id IS NULL;

INSERT INTO people (user_id, first_name, last_name, phone, is_archived, archived_at)
SELECT t.user_id, t.first_name, t.last_name, t.phone, t.is_archived, t.archived_at
  FROM teachers t
 WHERE t.person_id IS NULL
   AND t.user_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM people p WHERE p.user_id = t.user_id);

UPDATE teachers t JOIN people p ON p.user_id = t.user_id
   SET t.person_id = p.id
 WHERE t.person_id IS NULL;

-- 3) Parent and staff logins get a canonical person. No name was ever captured
--    for legacy parents, so the email local part is a visible placeholder the
--    admin can correct from the Parents page (which now edits names).
INSERT INTO people (user_id, first_name, last_name, is_archived, archived_at)
SELECT u.id, SUBSTRING_INDEX(u.email, '@', 1), '', u.is_archived, u.archived_at
  FROM users u
  JOIN roles r ON r.id = u.role_id AND r.name IN ('parent','staff')
 WHERE NOT EXISTS (SELECT 1 FROM people p WHERE p.user_id = u.id);

-- 4) Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_021_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
