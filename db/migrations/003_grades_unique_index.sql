-- 003_grades_unique_index.sql
-- Ensure one grade per (student, subject, class, term) and add lookup indexes

ALTER TABLE grades
  ADD UNIQUE KEY uq_grade_unique (student_id, subject_id, class_id, term_id);

CREATE INDEX idx_grades_class_term ON grades(class_id, term_id);
