-- 009_parents.sql
-- Add parent role + a many-to-many link from parent users to their student(s).
-- Existing students.guardian_name (text) is kept untouched: still useful as a
-- display fallback when no parent account is linked.

INSERT INTO roles (name)
SELECT 'parent' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'parent');

CREATE TABLE IF NOT EXISTS student_guardians (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  student_id INT NOT NULL,
  relationship VARCHAR(50) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_guardian_link (user_id, student_id),
  KEY idx_guardian_user (user_id),
  KEY idx_guardian_student (student_id),
  CONSTRAINT fk_guardian_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE,
  CONSTRAINT fk_guardian_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
