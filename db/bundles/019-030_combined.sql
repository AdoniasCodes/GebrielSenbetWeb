-- ---------------------------------------------------------------------------
-- Mekane Selam / GebrielSenbetWeb
-- Combined migration bundle: 019_registrations.sql .. 030_event_details.sql
-- Generated 2026-09-06
--
-- Use this ONLY if you are applying migrations by hand in phpMyAdmin.
-- The normal path is a single POST to /api/admin/deploy/migrate.php, which
-- already applies every pending migration in order by itself.
--
-- Every statement here is idempotent, so importing this against a database
-- that already has some of these migrations is safe.
--
-- The checksum recorded for each migration is the sha256 of its SOURCE file
-- under db/migrations, so the migrate endpoint afterwards reports them as
-- 'skipped'. Comment punctuation is normalised on the way in; executable SQL
-- is byte-identical to the source.
--
-- Import into phpMyAdmin against database mekanefh_RealDb.
-- Take a backup first: 025 rebuilds three join tables, 026 alters the
-- registration tables and 030 alters events.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL UNIQUE,
  checksum VARCHAR(64) NOT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================================
-- 019_registrations.sql
-- =========================================================================
-- 019_registrations.sql
-- Customizable public registration system. Public visitors submit registrations
-- for three activities (Sunday School, Begena classes, Gishen pilgrimage). Each
-- form belongs to a department; the main admin and that department's head can
-- see the submissions and customize the form's fields and open/closed status.

-- A registration form = one public activity people can sign up for.
CREATE TABLE IF NOT EXISTS registration_forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  title_en VARCHAR(200) NOT NULL,
  title_am VARCHAR(200) NULL,
  description_en TEXT NULL,
  description_am TEXT NULL,
  department_id INT NULL,
  status ENUM('open','limited','closed') NOT NULL DEFAULT 'open',
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_regform_slug (slug),
  CONSTRAINT fk_regform_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_regforms_dept ON registration_forms(department_id, is_archived);

-- A configurable question/field on a form. options_json holds a JSON array of
-- {value,label_en,label_am} for select/radio/checkbox types.
CREATE TABLE IF NOT EXISTS registration_form_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  label_en VARCHAR(200) NOT NULL,
  label_am VARCHAR(200) NULL,
  field_type ENUM('text','textarea','email','phone','number','date','select','radio','checkbox') NOT NULL DEFAULT 'text',
  options_json TEXT NULL,
  placeholder_en VARCHAR(200) NULL,
  placeholder_am VARCHAR(200) NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_regfield_form FOREIGN KEY (form_id) REFERENCES registration_forms(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_regfields_form ON registration_form_fields(form_id, is_archived, sort_order);

-- One public submission. answers_json maps field_id -> value (or array of values
-- for checkboxes). labels_snapshot_json freezes the field labels at submit time
-- so later edits to the form do not orphan old submissions.
CREATE TABLE IF NOT EXISTS registration_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  answers_json TEXT NOT NULL,
  labels_snapshot_json TEXT NULL,
  applicant_name VARCHAR(200) NULL,
  applicant_phone VARCHAR(60) NULL,
  status ENUM('new','seen','contacted') NOT NULL DEFAULT 'new',
  submitted_ip VARCHAR(45) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_regsub_form FOREIGN KEY (form_id) REFERENCES registration_forms(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_regsubs_form ON registration_submissions(form_id, is_archived, created_at);
CREATE INDEX idx_regsubs_ip ON registration_submissions(form_id, submitted_ip, created_at);

-- ---- Seed the three activity forms (idempotent on slug). ----
INSERT INTO registration_forms (slug, title_en, title_am, description_en, description_am, department_id, status, sort_order)
SELECT v.slug, v.title_en, v.title_am, v.description_en, v.description_am,
       (SELECT id FROM departments WHERE slug = v.dept_slug), v.status, v.sort_order
FROM (
  SELECT 'sunday-school' AS slug,
         'Sunday School Academic Registration' AS title_en,
         'የሰንበት ትምህርት ቤት ምዝገባ' AS title_am,
         'Register a child or youth for the Sunday School academic program.' AS description_en,
         'ልጅዎን ወይም ወጣቱን ለሰንበት ትምህርት ቤት የትምህርት መርሃ ግብር ያስመዝግቡ።' AS description_am,
         'timhirt' AS dept_slug, 'open' AS status, 10 AS sort_order
  UNION ALL
  SELECT 'begena',
         'Begena Classes',
         'የበገና ስልጠና ምዝገባ',
         'Sign up to learn the sacred Begena (harp) with our instructors.',
         'ከመምህራኖቻችን ጋር ቅዱሱን በገና ለመማር ይመዝገቡ።',
         'mezmur', 'open', 20
  UNION ALL
  SELECT 'gishen-pilgrimage',
         'Spiritual Pilgrimage to Gishen Mariam',
         'የግሸን ማርያም ጉዞ ምዝገባ',
         'Reserve your place on the spiritual pilgrimage to Gishen Mariam.',
         'ወደ ግሸን ማርያም በሚደረገው መንፈሳዊ ጉዞ ላይ ቦታዎን ያስይዙ።',
         'guzo', 'limited', 30
) v
WHERE NOT EXISTS (SELECT 1 FROM registration_forms f WHERE f.slug = v.slug);

-- ---- Seed default fields per form (only when the form has no fields yet). ----

-- Sunday School fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Baptismal name', 'የክርስትና ስም', 'text', NULL, NULL, NULL, 0, 30
  UNION ALL SELECT 'Date of birth', 'የልደት ቀን', 'date', NULL, NULL, NULL, 0, 40
  UNION ALL SELECT 'Sex', 'ጾታ', 'select', '[{"value":"male","label_en":"Male","label_am":"ወንድ"},{"value":"female","label_en":"Female","label_am":"ሴት"}]', NULL, NULL, 0, 50
  UNION ALL SELECT 'Guardian name', 'የአሳዳጊ ስም', 'text', NULL, NULL, NULL, 0, 60
  UNION ALL SELECT 'Guardian phone', 'የአሳዳጊ ስልክ', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 0, 70
  UNION ALL SELECT 'Address', 'አድራሻ', 'textarea', NULL, 'Sub-city, woreda, house no.', 'ክፍለ ከተማ፣ ወረዳ፣ የቤት ቁጥር', 0, 80
) v
JOIN registration_forms rf ON rf.slug = 'sunday-school'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'sunday-school'
);

-- Begena fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Age', 'ዕድሜ', 'number', NULL, NULL, NULL, 0, 30
  UNION ALL SELECT 'Prior experience', 'ቀዳሚ ልምድ', 'select', '[{"value":"none","label_en":"None","label_am":"የለም"},{"value":"beginner","label_en":"Beginner","label_am":"ጀማሪ"},{"value":"intermediate","label_en":"Intermediate","label_am":"መካከለኛ"}]', NULL, NULL, 1, 40
  UNION ALL SELECT 'Preferred schedule', 'የሚመርጡት ጊዜ', 'select', '[{"value":"weekday_evening","label_en":"Weekday evening","label_am":"የስራ ቀን ማታ"},{"value":"weekend_morning","label_en":"Weekend morning","label_am":"ቅዳሜና እሁድ ጠዋት"},{"value":"flexible","label_en":"Flexible","label_am":"ተለዋዋጭ"}]', NULL, NULL, 0, 50
) v
JOIN registration_forms rf ON rf.slug = 'begena'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'begena'
);

-- Gishen pilgrimage fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Emergency contact name', 'የአደጋ ጊዜ ተጠሪ ስም', 'text', NULL, NULL, NULL, 1, 30
  UNION ALL SELECT 'Emergency contact phone', 'የአደጋ ጊዜ ተጠሪ ስልክ', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 40
  UNION ALL SELECT 'Number of seats', 'የመቀመጫ ብዛት', 'number', NULL, '1', '1', 1, 50
) v
JOIN registration_forms rf ON rf.slug = 'gishen-pilgrimage'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'gishen-pilgrimage'
);

INSERT INTO schema_migrations (filename, checksum) VALUES ('019_registrations.sql', 'f4f83b4526af8a5814256e7b0f1fdf9cecf8421f67855d6b7ebd4b6f6e03c499')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 020_academic_hierarchy_cleanup.sql
-- =========================================================================
-- 020_academic_hierarchy_cleanup.sql
-- Phase 1.2 (SYSTEM_AUDIT_AND_BLUEPRINT.md): commit to the single academic
-- hierarchy (Grades 1-11 under the Sunday School Curriculum track) and give
-- academic classes an owning department (timhirt), per MASTER_PLAN F2.
-- Idempotent: safe to re-run.

-- 1) Backfill: academic classes belong to the Education department (timhirt).
--    Only touches classes with no department; choir/arts courses created later
--    will set their own department explicitly.
UPDATE classes c
   SET c.department_id = (SELECT d.id FROM departments d WHERE d.slug = 'timhirt' LIMIT 1)
 WHERE c.department_id IS NULL
   AND EXISTS (SELECT 1 FROM departments d WHERE d.slug = 'timhirt');

-- 2) Remove archived Era-1 tracks/levels (Children / Youth-Adult model retired
--    by migration 013) that nothing references anymore. Referenced rows are
--    kept so existing classes/curricula/resources/tasks stay intact.
DELETE cl FROM class_levels cl
 WHERE cl.is_archived = 1
   AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.level_id = cl.id)
   AND NOT EXISTS (SELECT 1 FROM grade_subjects gs WHERE gs.level_id = cl.id)
   AND NOT EXISTS (SELECT 1 FROM resources r WHERE r.scope_type = 'grade' AND r.scope_id = cl.id)
   AND NOT EXISTS (SELECT 1 FROM tasks t WHERE t.scope_type = 'grade' AND t.scope_id = cl.id);

DELETE et FROM education_tracks et
 WHERE et.is_archived = 1
   AND NOT EXISTS (SELECT 1 FROM class_levels cl WHERE cl.track_id = et.id);

-- 3) Stable marker so the migration runner's artifact probe can detect this
--    data-only migration (same pattern as future data migrations).
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_020_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('020_academic_hierarchy_cleanup.sql', 'a425d56dd504ec5cfa00aa2d34f3ca2d0244c83c3221303128d3a42eb933169e')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 021_identity_unification.sql
-- =========================================================================
-- 021_identity_unification.sql
-- Phase 1.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md §7): `people` becomes the single
-- source of personal identity. Every student/teacher row (archived included;
-- migration 018 only covered non-archived) gets a linked person, and parent/
-- staff logins get a canonical person row. Code side: person_accounts_lib now
-- creates people rows for all roles except admin, and the parents endpoint
-- writes through people. Idempotent: safe to re-run.

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

INSERT INTO schema_migrations (filename, checksum) VALUES ('021_identity_unification.sql', '00bf95afd89158f7eeb365d43e0fa1d12df969a8d9fbfe568f171d57aef795f2')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 022_notification_reads.sql
-- =========================================================================
-- 022_notification_reads.sql
-- Phase 2.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md §9): per-user read state moves out of
-- the notifications.read_by JSON array into a proper join table. Fixes the
-- lost-update race in the old read-modify-write (api/teacher/notifications.php)
-- and gives every portal, not just teachers, a queryable unread model.
-- read_by is left on notifications for rollback safety; Phase 3's column-debris
-- pass drops it. Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS notification_reads (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  notification_id BIGINT NOT NULL,
  user_id INT NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_notif_read (notification_id, user_id),
  CONSTRAINT fk_notifread_notif FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notifread_user  FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_notifread_user ON notification_reads(user_id);

-- Backfill from the legacy read_by array, testing membership without JSON_TABLE
-- (which MariaDB lacks before 10.6). The candidate is cast to CHAR, NOT to JSON:
-- production is MariaDB, whose CAST() has no JSON target type, so CAST(x AS JSON)
-- is a syntax error there. '257' is itself valid JSON for the number 257, so
-- CAST(u.id AS CHAR) is both correct and portable across MySQL and MariaDB.
-- The read_at we never stored, so it defaults to now(); INSERT IGNORE makes the
-- backfill re-runnable against the unique key.
INSERT IGNORE INTO notification_reads (notification_id, user_id)
SELECT n.id, u.id
  FROM notifications n
  JOIN users u
    ON n.read_by IS NOT NULL
   AND JSON_VALID(n.read_by)
   AND JSON_CONTAINS(n.read_by, CAST(u.id AS CHAR));

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_022_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('022_notification_reads.sql', '8d59b2639e13ef229dfc51ce23f8562a5ff7e15afea8cccfa03370b81a16f250')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 023_grade_finalization.sql
-- =========================================================================
-- 023_grade_finalization.sql
-- Phase 2.2 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 9): grade finalization.
--   1) grades.updated_by_user_id: accountability for who last wrote each grade.
--   2) grade_finalizations: a per (class, subject, term) soft lock. A row here
--      means the teacher marked that gradebook final; presence = locked. Reopen
--      deletes the row (the audit_log keeps the history).
--   3) academic_terms.closed_at / closed_by_user_id: the hard term lock. A
--      non-null closed_at blocks EVERY grade write for that term (teacher + admin).
-- Single-apply (like migrations 004/005/018): the migrate runner tracks applied
-- files and gates re-run via the migration_023_applied probe below, so the bare
-- ALTERs never execute twice.

-- 1) Who last touched each grade row.
ALTER TABLE grades
  ADD COLUMN updated_by_user_id INT NULL AFTER remarks;
ALTER TABLE grades
  ADD CONSTRAINT fk_grades_updated_by FOREIGN KEY (updated_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

-- 2) Per-gradebook soft lock (one gradebook = one class + subject + term).
CREATE TABLE IF NOT EXISTS grade_finalizations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  subject_id INT NOT NULL,
  term_id INT NOT NULL,
  finalized_by_user_id INT NULL,
  finalized_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gradebook (class_id, subject_id, term_id),
  CONSTRAINT fk_gradefinal_class   FOREIGN KEY (class_id)   REFERENCES classes(id)         ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gradefinal_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)        ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gradefinal_term    FOREIGN KEY (term_id)    REFERENCES academic_terms(id)  ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gradefinal_by      FOREIGN KEY (finalized_by_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3) Hard term lock.
ALTER TABLE academic_terms
  ADD COLUMN closed_at DATETIME NULL AFTER is_current,
  ADD COLUMN closed_by_user_id INT NULL AFTER closed_at;
ALTER TABLE academic_terms
  ADD CONSTRAINT fk_terms_closed_by FOREIGN KEY (closed_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_023_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('023_grade_finalization.sql', '59c33096a5de808b17a45b460fef4cf916b99e0e115e57e78c5de22a45a21608')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 024_attendance_term.sql
-- =========================================================================
-- 024_attendance_term.sql
-- Phase 2.3 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 9): term-scoped attendance.
-- Adds attendance_sessions.term_id so a session belongs to an academic term, and
-- backfills existing sessions by the term whose date range contains session_date.
-- Terms cannot overlap (the terms endpoint validates it), so the map is normally
-- 1:1; the tie-break (is_current, id) keeps the backfill deterministic even if a
-- legacy overlap exists. Sessions dated in a gap between terms stay NULL.
-- Single-apply: the migrate runner gates re-run via the migration_024_applied probe.

ALTER TABLE attendance_sessions
  ADD COLUMN term_id INT NULL AFTER session_date;
ALTER TABLE attendance_sessions
  ADD CONSTRAINT fk_attsess_term FOREIGN KEY (term_id)
    REFERENCES academic_terms(id) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX idx_attsess_term ON attendance_sessions(term_id);

-- Backfill: correlated subquery (not a JOIN) so a stray overlap can never
-- multiply rows; deterministic tie-break prefers the current term, then lowest id.
UPDATE attendance_sessions s
   SET s.term_id = (
        SELECT t.id FROM academic_terms t
         WHERE t.is_archived = 0
           AND s.session_date BETWEEN t.start_date AND t.end_date
         ORDER BY t.is_current DESC, t.id
         LIMIT 1)
 WHERE s.term_id IS NULL;

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_024_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('024_attendance_term.sql', 'd0cad6cdcc4ca7282bbed993e9a3dbd948856cc608d3e88e66b4a90a073666b2')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 025_join_table_integrity.sql
-- =========================================================================
-- 025_join_table_integrity.sql
-- Phase 3.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 7): the three core join tables
-- had no unique constraint, so the same student could be enrolled in one class
-- twice, the same teacher assigned the same subject twice, and the same person
-- joined to a department twice. Only one write path (staff roster add) checked
-- for it in PHP; every other path could duplicate silently.
--
-- Why not a plain UNIQUE KEY: this schema soft-deletes (is_archived) rather than
-- deleting, so a plain UNIQUE(student_id, class_id) would reject the normal
-- "archive the old assignment, add a new one" flow forever after the first
-- archive. Instead each table gets a generated guard column that is 1 while the
-- row is active and NULL once archived. MySQL treats NULLs as distinct inside a
-- unique index, so the constraint reads as: AT MOST ONE ACTIVE ROW per pair,
-- unlimited archived history. STORED (not VIRTUAL) so the index is portable
-- across MySQL 5.7+/8.x and MariaDB 10.2+.
--
-- Polymorphic scopes (resources.scope_id, tasks.scope_id,
-- attendance_sessions.context_id): a real FK is impossible on a column whose
-- target table varies by scope_type, and a CHECK cannot reference another table.
-- Composite (scope_type, scope_id, is_archived) indexes already exist on all
-- three, so this migration adds nothing there; validation stays at the write
-- path, which every current producer already does.
--
-- Delete policy: audited during this phase and deliberately left alone. All 11
-- ON DELETE CASCADE constraints are genuine owned-children (attendance_records ->
-- attendance_sessions, registration_form_fields -> registration_forms, and so
-- on) and all 6 ON DELETE SET NULL are nullable actor references (audit_log ->
-- users, grades.updated_by). The remaining 44 are NO ACTION, which is what
-- protects master data. That is already coherent; churning it on production
-- would be risk without benefit.
--
-- Single-apply: the migrate runner gates re-run via the migration_025_applied probe.

-- ---------------------------------------------------------------------------
-- 1. Defensive dedup. Local data is clean, but production has never had these
--    constraints, so archive every duplicate ACTIVE row and keep the newest.
--    A derived table (not a correlated subquery) so MySQL will materialise it
--    and allow updating the same table it reads from.
-- ---------------------------------------------------------------------------

UPDATE student_class_assignments a
  JOIN (SELECT student_id, class_id, MAX(id) AS keep_id
          FROM student_class_assignments
         WHERE is_archived = 0
         GROUP BY student_id, class_id
        HAVING COUNT(*) > 1) d
    ON d.student_id = a.student_id AND d.class_id = a.class_id
   SET a.is_archived = 1, a.archived_at = NOW()
 WHERE a.is_archived = 0 AND a.id <> d.keep_id;

UPDATE teacher_subject_assignments a
  JOIN (SELECT teacher_id, class_id, subject_id, MAX(id) AS keep_id
          FROM teacher_subject_assignments
         WHERE is_archived = 0
         GROUP BY teacher_id, class_id, subject_id
        HAVING COUNT(*) > 1) d
    ON d.teacher_id = a.teacher_id AND d.class_id = a.class_id AND d.subject_id = a.subject_id
   SET a.is_archived = 1, a.archived_at = NOW()
 WHERE a.is_archived = 0 AND a.id <> d.keep_id;

UPDATE department_memberships m
  JOIN (SELECT person_id, department_id, MAX(id) AS keep_id
          FROM department_memberships
         WHERE is_archived = 0
         GROUP BY person_id, department_id
        HAVING COUNT(*) > 1) d
    ON d.person_id = m.person_id AND d.department_id = m.department_id
   SET m.is_archived = 1, m.archived_at = NOW()
 WHERE m.is_archived = 0 AND m.id <> d.keep_id;

-- ---------------------------------------------------------------------------
-- 2. Active-row guards.
-- ---------------------------------------------------------------------------

ALTER TABLE student_class_assignments
  ADD COLUMN active_guard TINYINT GENERATED ALWAYS AS (IF(is_archived = 0, 1, NULL)) STORED;
ALTER TABLE student_class_assignments
  ADD UNIQUE KEY uq_sca_active (student_id, class_id, active_guard);

ALTER TABLE teacher_subject_assignments
  ADD COLUMN active_guard TINYINT GENERATED ALWAYS AS (IF(is_archived = 0, 1, NULL)) STORED;
ALTER TABLE teacher_subject_assignments
  ADD UNIQUE KEY uq_tsa_active (teacher_id, class_id, subject_id, active_guard);

ALTER TABLE department_memberships
  ADD COLUMN active_guard TINYINT GENERATED ALWAYS AS (IF(is_archived = 0, 1, NULL)) STORED;
ALTER TABLE department_memberships
  ADD UNIQUE KEY uq_depmem_active (person_id, department_id, active_guard);

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_025_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('025_join_table_integrity.sql', '5b8123c038f4bbf20560c999bf7641b95ddac7ad511c139285865d8fcb336384')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 026_registration_schema_deltas.sql
-- =========================================================================
-- 026_registration_schema_deltas.sql
-- Phase 3.2 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 4.3): schema only. Every
-- column here is inert until Phase 4 builds the behaviour on top; nothing in the
-- current code reads or writes any of it. Adding the columns now means Phase 4 is
-- a code change rather than a code-plus-migration change on live data.
--
-- Field types: the new values are added to the enum but REG_FIELD_TYPES in
-- api/registrations_lib.php still gates what can actually be set, and the admin
-- builder renders its own type list, so the widened enum stays unreachable until
-- Phase 4 opts each type in. 'file' and 'image' are DELIBERATELY EXCLUDED: they
-- need an upload pipeline (size/type allow-list, non-executable storage path,
-- correct serving headers) which section 4.3 calls the single riskiest addition
-- and assigns its own hardened sub-phase. Adding the enum value early would
-- invite someone to switch it on without that work.
--
-- Submission decisions: kept as separate columns rather than widening the
-- existing status enum, per the section 4.3 recommendation. status stays the
-- internal triage state (new|seen|contacted) that admin already uses; decision
-- is the applicant-facing outcome. Widening status in place would have
-- conflated two different concepts on rows that already exist.
--
-- Single-apply: the migrate runner gates re-run via the migration_026_applied probe.

-- ---------------------------------------------------------------------------
-- registration_forms: origin, event linkage, card presentation, windowing,
-- capacity, authorship.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_forms
  ADD COLUMN origin ENUM('standalone','event') NOT NULL DEFAULT 'standalone' AFTER slug,
  ADD COLUMN event_id INT NULL AFTER origin,
  ADD COLUMN subtitle_en VARCHAR(255) NULL AFTER description_am,
  ADD COLUMN subtitle_am VARCHAR(255) NULL AFTER subtitle_en,
  ADD COLUMN button_text_en VARCHAR(80) NULL AFTER subtitle_am,
  ADD COLUMN button_text_am VARCHAR(80) NULL AFTER button_text_en,
  ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER button_text_am,
  ADD COLUMN card_image_path VARCHAR(255) NULL AFTER cover_image_path,
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER card_image_path,
  ADD COLUMN opens_at DATETIME NULL AFTER status,
  ADD COLUMN closes_at DATETIME NULL AFTER opens_at,
  ADD COLUMN deadline_at DATETIME NULL AFTER closes_at,
  ADD COLUMN capacity INT NULL AFTER deadline_at,
  ADD COLUMN capacity_counts ENUM('accepted','all_active') NOT NULL DEFAULT 'accepted' AFTER capacity,
  ADD COLUMN created_by_user_id INT NULL AFTER capacity_counts;

ALTER TABLE registration_forms
  ADD CONSTRAINT fk_regform_event FOREIGN KEY (event_id)
    REFERENCES events(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_forms
  ADD CONSTRAINT fk_regform_creator FOREIGN KEY (created_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX idx_regform_origin ON registration_forms(origin, event_id);
CREATE INDEX idx_regform_window ON registration_forms(opens_at, closes_at);

-- ---------------------------------------------------------------------------
-- registration_form_fields: explicit applicant mapping (replacing the English
-- label "name" heuristic), help text, defaults, preset validators, new types.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_form_fields
  MODIFY COLUMN field_type ENUM(
    'text','textarea','email','phone','number','date','select','radio','checkbox',
    'time','datetime','url','address','hidden','section','consent','multiselect'
  ) NOT NULL;

ALTER TABLE registration_form_fields
  ADD COLUMN maps_to ENUM('applicant_name','applicant_phone','applicant_email') NULL AFTER field_type,
  ADD COLUMN help_text_en VARCHAR(300) NULL AFTER placeholder_am,
  ADD COLUMN help_text_am VARCHAR(300) NULL AFTER help_text_en,
  ADD COLUMN default_value VARCHAR(255) NULL AFTER help_text_am,
  ADD COLUMN validation_json TEXT NULL AFTER default_value;

CREATE INDEX idx_regfield_maps ON registration_form_fields(form_id, maps_to);

-- ---------------------------------------------------------------------------
-- registration_submissions: decision workflow + person/student linkage, so a
-- submission can stop being a dead end and become a real enrolment in Phase 4.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_submissions
  ADD COLUMN applicant_email VARCHAR(200) NULL AFTER applicant_phone,
  ADD COLUMN decision ENUM('pending','accepted','rejected','waitlisted') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN decided_by_user_id INT NULL AFTER decision,
  ADD COLUMN decided_at DATETIME NULL AFTER decided_by_user_id,
  ADD COLUMN person_id INT NULL AFTER decided_at,
  ADD COLUMN student_id INT NULL AFTER person_id;

ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_decider FOREIGN KEY (decided_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_person FOREIGN KEY (person_id)
    REFERENCES people(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX idx_regsub_decision ON registration_submissions(form_id, decision, is_archived);

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_026_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('026_registration_schema_deltas.sql', '5a890a1e09c7614b6a1de8d6426510d2e91b239c9167a770e5b4c28ceb0f34ab')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 027_eligibility_tables.sql
-- =========================================================================
-- 027_eligibility_tables.sql
-- Phase 3.3 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 8): today eligibility is one
-- function with one input (lifetime class-attendance % against one global
-- threshold in app_settings), two display-only callers, and a result that is
-- never stored. Section 8 replaces that with a rule-based engine whose verdicts
-- are persisted at decision moments so downstream modules consume a stored fact
-- rather than recomputing.
--
-- Schema only. The engine, its callers, and the admin UI come later; nothing
-- reads these tables yet. The existing gs_compute_eligibility() path in
-- api/eligibility_lib.php is untouched and keeps working, so this migration
-- cannot regress the staff/admin eligibility pages.
--
-- Why rules are rows, not columns: section 10 Q5 (pass-mark ownership: per
-- department, per grade-level, or global with overrides) is still open. Because
-- a rule carries its own (context_type, context_id), all three answers are
-- expressible as DATA. Whichever way that question is settled, it will not need
-- another migration.
--
-- context_type/context_id is polymorphic for the same reason resources and tasks
-- are: the target table varies by context. No FK is possible, so the engine
-- validates the pair at write time (same convention documented in migration 025).
--
-- Single-apply: the migrate runner gates re-run via the migration_027_applied probe.

CREATE TABLE IF NOT EXISTS eligibility_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  -- What the rule applies to. 'system' is the global fallback and ignores context_id.
  context_type ENUM('system','department','grade_level','form','event') NOT NULL DEFAULT 'system',
  context_id INT NULL,
  -- What the rule is for. A rule only participates in evaluations of its purpose.
  purpose ENUM('serving','promotion','registration','event') NOT NULL,
  -- The check itself. params_json carries the thresholds, e.g. {"min_pct":75}.
  rule_type ENUM(
    'min_attendance_pct',
    'min_average_score',
    'passed_subjects',
    'prerequisite_grade_completed',
    'age_between',
    'registration_window',
    'requires_approval',
    'payment_clear'
  ) NOT NULL,
  params_json TEXT NULL,
  -- Ordering matters only for how reasons are reported, not for the verdict.
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by_user_id INT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_eligrule_user FOREIGN KEY (created_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_eligrule_lookup (purpose, context_type, context_id, is_active, is_archived)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eligibility_evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  purpose ENUM('serving','promotion','registration','event') NOT NULL,
  context_type ENUM('system','department','grade_level','form','event') NOT NULL DEFAULT 'system',
  context_id INT NULL,
  -- Term the verdict was computed against. NULL for purposes that are not
  -- term-scoped. Attendance rules became term-scoped in migration 024, so a
  -- verdict is only meaningful alongside the term it was measured in.
  term_id INT NULL,
  result ENUM('eligible','not_eligible','no_data') NOT NULL,
  -- Per-rule outcomes, so the student and parent portals can show WHY, not just
  -- a yes/no: [{"rule_type":"min_attendance_pct","ok":false,"actual":62,"required":75}]
  reasons_json TEXT NULL,
  computed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  computed_by_user_id INT NULL,
  CONSTRAINT fk_eligeval_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_eligeval_term FOREIGN KEY (term_id)
    REFERENCES academic_terms(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_eligeval_user FOREIGN KEY (computed_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_eligeval_student (student_id, purpose, term_id),
  INDEX idx_eligeval_context (purpose, context_type, context_id, computed_at)
) ENGINE=InnoDB;

-- Seed the one rule that already exists in behaviour, so the engine starts life
-- agreeing with the current page rather than contradicting it. The threshold is
-- read from app_settings (seeded 75 by migration 016) instead of being
-- hardcoded, so an admin who already changed it keeps their value.
INSERT INTO eligibility_rules (context_type, context_id, purpose, rule_type, params_json, sort_order, is_active)
SELECT 'system', NULL, 'serving', 'min_attendance_pct',
       CONCAT('{"min_pct":', COALESCE((SELECT setting_value FROM app_settings
                                        WHERE setting_key = 'serving_eligibility_min_attendance'), '75'), '}'),
       0, 1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM eligibility_rules
                    WHERE purpose = 'serving' AND rule_type = 'min_attendance_pct' AND context_type = 'system');

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_027_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('027_eligibility_tables.sql', '1ee7f9cb1ba422939c62540a94cfb10c58cbee7931a318c6dc9d8beaa0c80e0d')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 028_notification_column_debris.sql
-- =========================================================================
-- 028_notification_column_debris.sql
-- Phase 3.4 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 9): enum and column debris.
-- Migration 022 said it plainly: "read_by is left on notifications for rollback
-- safety; Phase 3's column-debris pass drops it." This is that pass.
--
-- Two pieces of dead schema go:
--
-- 1. notifications.read_by (legacy JSON array of user ids). Superseded by the
--    notification_reads join table in migration 022, which fixed the lost-update
--    race. Nothing has written read_by since; the only remaining reader was
--    api/admin/announcements/index.php, which returned it to a UI that never
--    displayed it, and now returns a real read_count from notification_reads.
--
-- 2. Three unreachable notifications.target_type values: 'subject',
--    'payment_defaulters' and 'event'. The Phase 2.1 contract (NOTIFY_TARGETS in
--    api/notifications_lib.php) defines exactly four targets, and notify() is the
--    only writer, so these three cannot be produced and no reader matches them.
--    Verified zero rows before narrowing.
--
-- The candidate below is cast to CHAR, not JSON: production is MariaDB, whose
-- CAST() has no JSON target type. '257' is valid JSON for the number 257, so this
-- is correct on both engines.
--
-- The backfill below re-runs 022's logic defensively. It is a no-op on any
-- database where 022 already ran, but it means the drop can never lose read
-- state even if the two migrations are applied in the same batch on a database
-- that had rows in flight.
--
-- Single-apply: the migrate runner gates re-run via the migration_028_applied probe.

-- Defensive re-backfill before the column disappears.
INSERT IGNORE INTO notification_reads (notification_id, user_id, read_at)
SELECT n.id, u.id, COALESCE(n.updated_at, n.created_at, NOW())
  FROM notifications n
  JOIN users u
    ON n.read_by IS NOT NULL
   AND JSON_VALID(n.read_by)
   AND JSON_CONTAINS(n.read_by, CAST(u.id AS CHAR));

ALTER TABLE notifications DROP COLUMN read_by;

-- Narrow the target contract to what notify() can actually produce.
ALTER TABLE notifications
  MODIFY COLUMN target_type ENUM('role','department','class','user') NOT NULL;

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_028_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('028_notification_column_debris.sql', 'c6cdc77fd2b71eaff79392f46d4f4a9863c9ef55855a076d32a8adbcea3945a3')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 029_registration_form_renames.sql
-- =========================================================================
-- 029_registration_form_renames.sql
-- Broadens two registration forms beyond the single activity they were named for:
-- the Begena class is now instrument training generally, and the Gishen Mariam
-- trip is now a spiritual pilgrimage generally. Titles and descriptions move in
-- both languages. Slugs are deliberately left alone: 'begena' and
-- 'gishen-pilgrimage' are the stable keys that 019 seeds fields against and that
-- the department-ownership defaults key off, so renaming them would break both.
-- Idempotent: the UPDATEs are matched on slug and simply rewrite to the target
-- values, so a second run is a no-op.

UPDATE registration_forms
   SET title_en       = 'Sacred Instrument Training',
       title_am       = 'የዜማ መሳሪያ ስልጠና ምዝገባ',
       description_en = 'Sign up to learn our sacred liturgical instruments with our instructors.',
       description_am = 'ከመምህራኖቻችን ጋር ቅዱሳን የዜማ መሳሪያዎችን ለመማር ይመዝገቡ።'
 WHERE slug = 'begena';

UPDATE registration_forms
   SET title_en       = 'Spiritual Pilgrimage',
       title_am       = 'የመንፈሳዊ ጉዞ ምዝገባ',
       description_en = 'Reserve your place on our spiritual pilgrimage.',
       description_am = 'በሚደረገው መንፈሳዊ ጉዞ ላይ ቦታዎን ያስይዙ።'
 WHERE slug = 'gishen-pilgrimage';

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_029_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('029_registration_form_renames.sql', 'bc78de159bc0f064178ef94c85b0b5200d7744519522154ba6c1f7a7b6684400')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);

-- =========================================================================
-- 030_event_details.sql
-- =========================================================================
-- 030_event_details.sql
-- The landing page shows events as cards: venue and time at a glance, then a
-- detail view with an optional poster. The events table carried none of that,
-- and no Amharic columns either, so every event was English-only and had
-- nowhere to record where it happens.
--
-- Adds, all nullable so existing rows stay valid:
--   title_am / description_am  bilingual pair for the existing columns
--   location_en / location_am  the venue, shown on the card face
--   image_url                  poster or photo, shown in the detail view
--
-- Idempotent: each ALTER is guarded by an information_schema check, because
-- MariaDB has no ADD COLUMN IF NOT EXISTS that is safe across versions here.

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'title_am') = 0,
              'ALTER TABLE events ADD COLUMN title_am VARCHAR(200) NULL AFTER title',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'description_am') = 0,
              'ALTER TABLE events ADD COLUMN description_am TEXT NULL AFTER description',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'location_en') = 0,
              'ALTER TABLE events ADD COLUMN location_en VARCHAR(200) NULL AFTER end_datetime',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'location_am') = 0,
              'ALTER TABLE events ADD COLUMN location_am VARCHAR(200) NULL AFTER location_en',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'image_url') = 0,
              'ALTER TABLE events ADD COLUMN image_url VARCHAR(500) NULL AFTER location_am',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_030_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';

INSERT INTO schema_migrations (filename, checksum) VALUES ('030_event_details.sql', 'db9b6eb6748124c8abb84b02af1cf94061bfd5d4c488bf05611c2e99a5c91324')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);
