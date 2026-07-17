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

SET NAMES utf8mb4;

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
