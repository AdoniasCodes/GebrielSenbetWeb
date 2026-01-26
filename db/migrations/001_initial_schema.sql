-- 001_initial_schema.sql
-- InnoDB only; no hard deletes; archival fields

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (name) VALUES ('admin'), ('teacher'), ('student');

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_users_role ON users(role_id);

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  date_of_birth DATE NULL,
  guardian_name VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(50) NULL,
  bio TEXT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE education_tracks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE class_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  track_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_levels_track FOREIGN KEY (track_id) REFERENCES education_tracks(id)
    ON UPDATE CASCADE,
  UNIQUE KEY uq_track_level (track_id, name)
) ENGINE=InnoDB;
CREATE INDEX idx_levels_track ON class_levels(track_id);

CREATE TABLE academic_terms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_term_name_year (name, academic_year)
) ENGINE=InnoDB;

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level_id INT NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_classes_level FOREIGN KEY (level_id) REFERENCES class_levels(id)
    ON UPDATE CASCADE,
  UNIQUE KEY uq_class (level_id, academic_year, name)
) ENGINE=InnoDB;
CREATE INDEX idx_classes_level ON classes(level_id);

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subject_name (name)
) ENGINE=InnoDB;

CREATE TABLE teacher_subject_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  class_id INT NOT NULL,
  subject_id INT NOT NULL,
  role ENUM('primary','substitute') NOT NULL DEFAULT 'primary',
  start_date DATE NOT NULL,
  end_date DATE NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tsa_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON UPDATE CASCADE,
  CONSTRAINT fk_tsa_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE,
  CONSTRAINT fk_tsa_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_tsa_composite ON teacher_subject_assignments(class_id, subject_id, role, start_date, end_date);
-- Enforce single primary per class+subject by trigger or application logic; here we add a partial uniqueness via role filter using generated column workaround if needed (kept to app level due to MySQL limitations without CHECK on condition).

CREATE TABLE student_class_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  assigned_at DATE NOT NULL,
  ended_at DATE NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sca_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE,
  CONSTRAINT fk_sca_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_sca_student ON student_class_assignments(student_id);
CREATE INDEX idx_sca_class ON student_class_assignments(class_id);

CREATE TABLE grades (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  class_id INT NOT NULL,
  term_id INT NOT NULL,
  score DECIMAL(5,2) NOT NULL,
  remarks VARCHAR(255) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE,
  CONSTRAINT fk_grades_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON UPDATE CASCADE,
  CONSTRAINT fk_grades_class FOREIGN KEY (class_id) REFERENCES classes(id) ON UPDATE CASCADE,
  CONSTRAINT fk_grades_term FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_grades_lookup ON grades(student_id, subject_id, term_id);

CREATE TABLE payments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  term_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('paid','unpaid','partial') NOT NULL DEFAULT 'unpaid',
  notes VARCHAR(255) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE,
  CONSTRAINT fk_pay_term FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON UPDATE CASCADE,
  UNIQUE KEY uq_payment_student_term (student_id, term_id)
) ENGINE=InnoDB;
CREATE INDEX idx_payments_status ON payments(status);

CREATE TABLE notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  sender_user_id INT NULL,
  sender_role_id INT NULL,
  target_type ENUM('role','class','subject','payment_defaulters','event') NOT NULL,
  target_payload JSON NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  read_by JSON NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_sender_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON UPDATE CASCADE,
  CONSTRAINT fk_notif_sender_role FOREIGN KEY (sender_role_id) REFERENCES roles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_notifications_target ON notifications(target_type);

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NULL,
  is_recurring TINYINT(1) NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE event_recurrence_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  freq ENUM('weekly','monthly','every_x_months') NOT NULL,
  interval_num INT NOT NULL DEFAULT 1,
  by_day VARCHAR(20) NULL,
  until_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rr_event FOREIGN KEY (event_id) REFERENCES events(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_rr_event ON event_recurrence_rules(event_id);

CREATE TABLE blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  author_user_id INT NOT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_blog_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE blog_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attach_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_attach_post ON blog_attachments(post_id);

SET FOREIGN_KEY_CHECKS = 1;
