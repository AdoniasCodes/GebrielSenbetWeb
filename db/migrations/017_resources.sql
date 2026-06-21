-- 017_resources.sql
-- Shared resources (files + links) scoped to a grade (class_levels) or a department.
CREATE TABLE IF NOT EXISTS resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scope_type ENUM('grade','department') NOT NULL,
  scope_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  kind ENUM('file','link') NOT NULL DEFAULT 'file',
  url VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NULL,
  size_bytes INT NULL,
  uploaded_by_user_id INT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_res_scope (scope_type, scope_id, is_archived)
) ENGINE=InnoDB;
