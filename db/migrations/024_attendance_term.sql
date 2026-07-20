-- 024_attendance_term.sql
-- Phase 2.3 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 9): term-scoped attendance.
-- Adds attendance_sessions.term_id so a session belongs to an academic term, and
-- backfills existing sessions by the term whose date range contains session_date.
-- Terms cannot overlap (the terms endpoint validates it), so the map is normally
-- 1:1; the tie-break (is_current, id) keeps the backfill deterministic even if a
-- legacy overlap exists. Sessions dated in a gap between terms stay NULL.
-- Single-apply: the migrate runner gates re-run via the migration_024_applied probe.

SET NAMES utf8mb4;

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
