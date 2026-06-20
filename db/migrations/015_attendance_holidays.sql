-- 015_attendance_holidays.sql
-- Phase B: cross-cutting engines.
--  1) Attendance — roll-call for a class meeting OR a department gathering/duty.
--     Records reference the canonical person, so the same human's academic
--     attendance and service attendance live together (used for eligibility).
--  2) EOTC holiday / celebration calendar.
--  3) Serving assignments — which department/level serves a holiday at which church.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS attendance_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  context_type ENUM('class','department') NOT NULL,
  context_id INT NOT NULL,                 -- class_id or department_id
  title VARCHAR(200) NULL,
  session_date DATE NOT NULL,
  church_id INT NULL,
  notes TEXT NULL,
  created_by_user_id INT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_attsess_church FOREIGN KEY (church_id) REFERENCES churches(id) ON UPDATE CASCADE,
  CONSTRAINT fk_attsess_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_attsess_ctx ON attendance_sessions(context_type, context_id, session_date);

CREATE TABLE IF NOT EXISTS attendance_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  person_id INT NOT NULL,
  status ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_attrec_session FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_attrec_person FOREIGN KEY (person_id) REFERENCES people(id) ON UPDATE CASCADE,
  UNIQUE KEY uq_attrec (session_id, person_id)
) ENGINE=InnoDB;
CREATE INDEX idx_attrec_person ON attendance_records(person_id, status);

CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  name_am VARCHAR(200) NULL,
  holiday_date DATE NULL,
  scale ENUM('major','minor') NOT NULL DEFAULT 'minor',
  is_recurring_annually TINYINT(1) NOT NULL DEFAULT 1,
  description TEXT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE INDEX idx_holidays_date ON holidays(holiday_date);

CREATE TABLE IF NOT EXISTS serving_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_id INT NOT NULL,
  department_id INT NOT NULL,              -- usually the choir (mezmur)
  level_id INT NULL,                       -- which advancement level serves (NULL = whole dept)
  church_id INT NULL,                      -- which church (NULL = both / unspecified)
  with_seniors TINYINT(1) NOT NULL DEFAULT 0,
  notes VARCHAR(255) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_serv_holiday FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
  CONSTRAINT fk_serv_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE,
  CONSTRAINT fk_serv_level FOREIGN KEY (level_id) REFERENCES department_levels(id) ON UPDATE CASCADE,
  CONSTRAINT fk_serv_church FOREIGN KEY (church_id) REFERENCES churches(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_serv_holiday ON serving_assignments(holiday_id);
CREATE INDEX idx_serv_dept ON serving_assignments(department_id);
