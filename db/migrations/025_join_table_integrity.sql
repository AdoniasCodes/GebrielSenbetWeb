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

SET NAMES utf8mb4;

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
