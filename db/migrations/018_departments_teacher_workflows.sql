-- 018_departments_teacher_workflows.sql
-- Phase A schema foundation for the department-centric build-out:
--  a) classes belong to a department (nullable until admin assigns).
--  b) events gain a dept-head approval workflow (default 'approved' so every
--     existing admin-created event stays visible).
--  c) notifications can target a whole department or a single user.
--  d) a new `tasks` (homework/assignment) concept, scoped like resources (017).
--  e) department_memberships record who assigned the membership.
--  f) backfill: every non-archived teacher/student without a person_id gets a
--     canonical `people` row and is linked to it.
-- Additive + idempotency via the migrate.php artifact check (matches 011/015:
-- plain ALTERs, no ADD COLUMN IF NOT EXISTS on MySQL). The data backfill in (f)
-- is SQL-level idempotent via WHERE person_id IS NULL / NOT EXISTS guards.

SET NAMES utf8mb4;

-- (a) Classes belong to a department. Nullable: existing classes stay
-- unassigned until an admin sets them.
ALTER TABLE classes ADD COLUMN department_id INT NULL AFTER level_id;
ALTER TABLE classes ADD CONSTRAINT fk_classes_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE;
CREATE INDEX idx_classes_department ON classes(department_id);

-- (b) Event dept-head approval workflow. Default 'approved' keeps all existing
-- (admin-created) events visible; only dept-proposed events start 'pending'.
ALTER TABLE events ADD COLUMN status ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved' AFTER is_recurring;
ALTER TABLE events ADD COLUMN created_by_user_id INT NULL AFTER status;
ALTER TABLE events ADD COLUMN department_id INT NULL AFTER created_by_user_id;
ALTER TABLE events ADD COLUMN approved_by_user_id INT NULL AFTER department_id;
ALTER TABLE events ADD COLUMN approved_at DATETIME NULL AFTER approved_by_user_id;
ALTER TABLE events ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON UPDATE CASCADE;
ALTER TABLE events ADD CONSTRAINT fk_events_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE;
ALTER TABLE events ADD CONSTRAINT fk_events_approved_by FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON UPDATE CASCADE;
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_department ON events(department_id);

-- (c) Notifications can now target a whole department or a single user. Append
-- new ENUM values (order-preserving); every existing row keeps its string value.
ALTER TABLE notifications MODIFY COLUMN target_type ENUM('role','class','subject','payment_defaulters','event','department','user') NOT NULL;

-- (e) Record who assigned a department membership (admin or a dept head).
ALTER TABLE department_memberships ADD COLUMN assigned_by_user_id INT NULL AFTER title;
ALTER TABLE department_memberships ADD CONSTRAINT fk_depmem_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON UPDATE CASCADE;

-- (f) Backfill canonical people for existing teachers/students. Idempotent:
-- WHERE person_id IS NULL guards re-runs; NOT EXISTS on people.user_id avoids
-- violating the people.user_id UNIQUE constraint when a row already exists.
INSERT INTO people (user_id, first_name, last_name) SELECT t.user_id, t.first_name, t.last_name FROM teachers t WHERE t.person_id IS NULL AND t.is_archived = 0 AND NOT EXISTS (SELECT 1 FROM people p WHERE p.user_id = t.user_id);
UPDATE teachers t JOIN people p ON p.user_id = t.user_id SET t.person_id = p.id WHERE t.person_id IS NULL AND t.is_archived = 0;
INSERT INTO people (user_id, first_name, last_name) SELECT s.user_id, s.first_name, s.last_name FROM students s WHERE s.person_id IS NULL AND s.is_archived = 0 AND NOT EXISTS (SELECT 1 FROM people p WHERE p.user_id = s.user_id);
UPDATE students s JOIN people p ON p.user_id = s.user_id SET s.person_id = p.id WHERE s.person_id IS NULL AND s.is_archived = 0;

-- (d) Tasks (homework/assignments), scoped exactly like resources (017): a
-- department, a class, or a grade (class_levels). Soft-delete via is_archived.
-- Created last so its presence is a decisive "migration complete" artifact.
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scope_type ENUM('department','class','grade') NOT NULL,
  scope_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  due_date DATE NULL,
  created_by_user_id INT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON UPDATE CASCADE,
  INDEX idx_tasks_scope (scope_type, scope_id, is_archived)
) ENGINE=InnoDB;
