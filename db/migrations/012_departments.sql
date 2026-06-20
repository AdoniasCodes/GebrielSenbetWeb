-- 012_departments.sql
-- Phase A foundation: the department system. The school is a federation of
-- departments (ክፍል), some with sub-departments. Each department can have its
-- own advancement levels (e.g. the choir ladder) and a roster of members.

SET NAMES utf8mb4;

-- Departments and sub-departments. parent_id => sub-department (e.g. ልማት under
-- the በጎ አድራጎት umbrella). slug is the stable code used in URLs/permissions.
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  slug VARCHAR(80) NOT NULL,
  name VARCHAR(150) NOT NULL,
  name_am VARCHAR(150) NULL,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_department_slug (slug),
  CONSTRAINT fk_department_parent FOREIGN KEY (parent_id) REFERENCES departments(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_departments_parent ON departments(parent_id);

-- Top-level departments (idempotent on slug).
INSERT INTO departments (slug, name, name_am, sort_order)
SELECT * FROM (
  SELECT 'timhirt'      AS slug, 'Education & Curriculum'  AS name, 'ትምህርት ክፍል'        AS name_am, 10 AS sort_order UNION ALL
  SELECT 'mezmur',      'Choir & Hymns',                   'መዝሙር ክፍል',                 20 UNION ALL
  SELECT 'outreach',    'Charitable Outreach',             'በጎ አድራጎት ክፍል',             30 UNION ALL
  SELECT 'kinetbeb',    'Fine Arts',                       'ኪነጥበብ ክፍል',                40 UNION ALL
  SELECT 'av',          'Audio & Visual',                  'ኦዲዮ ቪዥዋል ክፍል',            50 UNION ALL
  SELECT 'board',       'Board of Admins',                 'የቦርድ አስተዳደር',              60 UNION ALL
  SELECT 'secretariat', 'Secretariat',                     'ጽሕፈት ቤት',                  70 UNION ALL
  SELECT 'construction','Construction Committee',          'የግንባታ ኮሚቴ',                80 UNION ALL
  SELECT 'parents',     'Parents'' Committee',             'ወላጆች ኮሚቴ',                 90
) v
WHERE NOT EXISTS (SELECT 1 FROM departments d WHERE d.slug = v.slug);

-- Sub-departments under the በጎ አድራጎት umbrella.
INSERT INTO departments (parent_id, slug, name, name_am, sort_order)
SELECT (SELECT id FROM departments WHERE slug='outreach'), v.slug, v.name, v.name_am, v.sort_order
FROM (
  SELECT 'limat'        AS slug, 'Development'           AS name, 'ልማት ክፍል'        AS name_am, 1 AS sort_order UNION ALL
  SELECT 'guzo',        'Pilgrimage & Travel',           'ጉዞ ክፍል',                  2 UNION ALL
  SELECT 'bego-adragot','Charity',                       'በጎ አድራጎት ክፍል',           3
) v
WHERE EXISTS (SELECT 1 FROM departments WHERE slug='outreach')
  AND NOT EXISTS (SELECT 1 FROM departments d WHERE d.slug = v.slug);

-- Per-department advancement ladders. rank 1 = most senior.
CREATE TABLE IF NOT EXISTS department_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  name_am VARCHAR(120) NULL,
  `rank` INT NOT NULL DEFAULT 0,
  description TEXT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_deplevel_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE,
  UNIQUE KEY uq_deplevel (department_id, name)
) ENGINE=InnoDB;
CREATE INDEX idx_deplevels_dept ON department_levels(department_id, `rank`);

-- Seed the choir (መዝሙር) ladder: most senior -> newest.
INSERT INTO department_levels (department_id, name, name_am, `rank`)
SELECT (SELECT id FROM departments WHERE slug='mezmur'), v.name, v.name_am, v.`rank`
FROM (
  SELECT 'Regular Servant'   AS name, 'መደበኛ አገልጋይ' AS name_am, 1 AS `rank` UNION ALL
  SELECT 'Successor 1',        'ተተኪ 1',            2 UNION ALL
  SELECT 'Successor 2',        'ተተኪ 2',            3 UNION ALL
  SELECT 'Newcomer',           'ቀዳማይ',             4
) v
WHERE EXISTS (SELECT 1 FROM departments WHERE slug='mezmur')
  AND NOT EXISTS (
    SELECT 1 FROM department_levels dl
    JOIN departments d ON d.id = dl.department_id
    WHERE d.slug='mezmur' AND dl.name = v.name
  );

-- Membership of a person in a department, with optional advancement level and a
-- head flag (department heads get scoped management rights, wired up later).
CREATE TABLE IF NOT EXISTS department_memberships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  person_id INT NOT NULL,
  department_id INT NOT NULL,
  level_id INT NULL,
  title VARCHAR(120) NULL,                 -- e.g. head, secretary, instructor, member
  is_head TINYINT(1) NOT NULL DEFAULT 0,
  joined_at DATE NULL,
  ended_at DATE NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_depmem_person FOREIGN KEY (person_id) REFERENCES people(id) ON UPDATE CASCADE,
  CONSTRAINT fk_depmem_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE,
  CONSTRAINT fk_depmem_level FOREIGN KEY (level_id) REFERENCES department_levels(id) ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_depmem_person ON department_memberships(person_id);
CREATE INDEX idx_depmem_dept ON department_memberships(department_id, is_archived);
