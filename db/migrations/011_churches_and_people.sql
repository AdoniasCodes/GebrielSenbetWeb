-- 011_churches_and_people.sql
-- Phase A foundation: the two churches the school operates in, and a canonical
-- `people` table (unified person model). One person can later hold many roles
-- (student, teacher, department member, parent) over time. Existing students/
-- teachers tables gain a nullable person_id link (additive, non-breaking).

SET NAMES utf8mb4;

-- The churches/locations the school serves. A real dimension for serving
-- assignments and events (esp. the መዝሙር department performs at both).
CREATE TABLE IF NOT EXISTS churches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  name_am VARCHAR(200) NULL,
  short_name VARCHAR(100) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_church_name (name)
) ENGINE=InnoDB;

INSERT INTO churches (name, name_am, short_name)
SELECT 'Kotebe Dagmawi Lul Kulbi St. Gabriel Cathedral', 'ኮተቤ ዳግማዊ ልዑል ቁልቢ ቅዱስ ገብርኤል ካቴደራል', 'St. Gabriel Cathedral'
WHERE NOT EXISTS (SELECT 1 FROM churches WHERE name='Kotebe Dagmawi Lul Kulbi St. Gabriel Cathedral');

INSERT INTO churches (name, name_am, short_name)
SELECT 'Kotebe Mesalemiya St. Mary', 'ኮተቤ መሳለምያ ቅድስት ድንግል ማርያም', 'St. Mary'
WHERE NOT EXISTS (SELECT 1 FROM churches WHERE name='Kotebe Mesalemiya St. Mary');

-- Canonical person. user_id links to a login if the person has one (many
-- members will not). Cross-cutting attributes (church, communion) live here so
-- the same human is tracked across every department.
CREATE TABLE IF NOT EXISTS people (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  baptismal_name VARCHAR(100) NULL,          -- የክርስትና ስም
  date_of_birth DATE NULL,
  gender ENUM('male','female') NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  primary_church_id INT NULL,
  member_status ENUM('active','inactive','alumni','prospective') NOT NULL DEFAULT 'active',
  joined_at DATE NULL,
  last_communion_date DATE NULL,             -- quick lookup; full log added in a later phase
  notes TEXT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_people_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE,
  CONSTRAINT fk_people_church FOREIGN KEY (primary_church_id) REFERENCES churches(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_people_church ON people(primary_church_id);
CREATE INDEX idx_people_status ON people(member_status);

-- Additive links: an existing student/teacher row points at its canonical person.
-- Nullable + no backfill needed (local DB has no student/teacher data yet).
ALTER TABLE students ADD COLUMN person_id INT NULL AFTER user_id;
ALTER TABLE students ADD CONSTRAINT fk_students_person FOREIGN KEY (person_id) REFERENCES people(id) ON UPDATE CASCADE;

ALTER TABLE teachers ADD COLUMN person_id INT NULL AFTER user_id;
ALTER TABLE teachers ADD CONSTRAINT fk_teachers_person FOREIGN KEY (person_id) REFERENCES people(id) ON UPDATE CASCADE;
