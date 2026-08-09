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

SET NAMES utf8mb4;

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
