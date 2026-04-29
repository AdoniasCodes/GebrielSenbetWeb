-- 004_academic_terms_is_current.sql
-- Add is_current flag so admin can designate one term as the active "current" term.
-- App layer enforces only one row with is_current=1 (per academic_year is allowed too if needed).

ALTER TABLE academic_terms
  ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 0 AFTER end_date;

CREATE INDEX idx_academic_terms_current ON academic_terms(is_current);
