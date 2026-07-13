-- 020_academic_hierarchy_cleanup.sql
-- Phase 1.2 (SYSTEM_AUDIT_AND_BLUEPRINT.md): commit to the single academic
-- hierarchy (Grades 1-11 under the Sunday School Curriculum track) and give
-- academic classes an owning department (timhirt), per MASTER_PLAN F2.
-- Idempotent: safe to re-run.

SET NAMES utf8mb4;

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
