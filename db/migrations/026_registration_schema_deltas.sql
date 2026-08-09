-- 026_registration_schema_deltas.sql
-- Phase 3.2 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 4.3): schema only. Every
-- column here is inert until Phase 4 builds the behaviour on top; nothing in the
-- current code reads or writes any of it. Adding the columns now means Phase 4 is
-- a code change rather than a code-plus-migration change on live data.
--
-- Field types: the new values are added to the enum but REG_FIELD_TYPES in
-- api/registrations_lib.php still gates what can actually be set, and the admin
-- builder renders its own type list, so the widened enum stays unreachable until
-- Phase 4 opts each type in. 'file' and 'image' are DELIBERATELY EXCLUDED: they
-- need an upload pipeline (size/type allow-list, non-executable storage path,
-- correct serving headers) which section 4.3 calls the single riskiest addition
-- and assigns its own hardened sub-phase. Adding the enum value early would
-- invite someone to switch it on without that work.
--
-- Submission decisions: kept as separate columns rather than widening the
-- existing status enum, per the section 4.3 recommendation. status stays the
-- internal triage state (new|seen|contacted) that admin already uses; decision
-- is the applicant-facing outcome. Widening status in place would have
-- conflated two different concepts on rows that already exist.
--
-- Single-apply: the migrate runner gates re-run via the migration_026_applied probe.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- registration_forms: origin, event linkage, card presentation, windowing,
-- capacity, authorship.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_forms
  ADD COLUMN origin ENUM('standalone','event') NOT NULL DEFAULT 'standalone' AFTER slug,
  ADD COLUMN event_id INT NULL AFTER origin,
  ADD COLUMN subtitle_en VARCHAR(255) NULL AFTER description_am,
  ADD COLUMN subtitle_am VARCHAR(255) NULL AFTER subtitle_en,
  ADD COLUMN button_text_en VARCHAR(80) NULL AFTER subtitle_am,
  ADD COLUMN button_text_am VARCHAR(80) NULL AFTER button_text_en,
  ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER button_text_am,
  ADD COLUMN card_image_path VARCHAR(255) NULL AFTER cover_image_path,
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER card_image_path,
  ADD COLUMN opens_at DATETIME NULL AFTER status,
  ADD COLUMN closes_at DATETIME NULL AFTER opens_at,
  ADD COLUMN deadline_at DATETIME NULL AFTER closes_at,
  ADD COLUMN capacity INT NULL AFTER deadline_at,
  ADD COLUMN capacity_counts ENUM('accepted','all_active') NOT NULL DEFAULT 'accepted' AFTER capacity,
  ADD COLUMN created_by_user_id INT NULL AFTER capacity_counts;

ALTER TABLE registration_forms
  ADD CONSTRAINT fk_regform_event FOREIGN KEY (event_id)
    REFERENCES events(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_forms
  ADD CONSTRAINT fk_regform_creator FOREIGN KEY (created_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX idx_regform_origin ON registration_forms(origin, event_id);
CREATE INDEX idx_regform_window ON registration_forms(opens_at, closes_at);

-- ---------------------------------------------------------------------------
-- registration_form_fields: explicit applicant mapping (replacing the English
-- label "name" heuristic), help text, defaults, preset validators, new types.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_form_fields
  MODIFY COLUMN field_type ENUM(
    'text','textarea','email','phone','number','date','select','radio','checkbox',
    'time','datetime','url','address','hidden','section','consent','multiselect'
  ) NOT NULL;

ALTER TABLE registration_form_fields
  ADD COLUMN maps_to ENUM('applicant_name','applicant_phone','applicant_email') NULL AFTER field_type,
  ADD COLUMN help_text_en VARCHAR(300) NULL AFTER placeholder_am,
  ADD COLUMN help_text_am VARCHAR(300) NULL AFTER help_text_en,
  ADD COLUMN default_value VARCHAR(255) NULL AFTER help_text_am,
  ADD COLUMN validation_json TEXT NULL AFTER default_value;

CREATE INDEX idx_regfield_maps ON registration_form_fields(form_id, maps_to);

-- ---------------------------------------------------------------------------
-- registration_submissions: decision workflow + person/student linkage, so a
-- submission can stop being a dead end and become a real enrolment in Phase 4.
-- ---------------------------------------------------------------------------

ALTER TABLE registration_submissions
  ADD COLUMN applicant_email VARCHAR(200) NULL AFTER applicant_phone,
  ADD COLUMN decision ENUM('pending','accepted','rejected','waitlisted') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN decided_by_user_id INT NULL AFTER decision,
  ADD COLUMN decided_at DATETIME NULL AFTER decided_by_user_id,
  ADD COLUMN person_id INT NULL AFTER decided_at,
  ADD COLUMN student_id INT NULL AFTER person_id;

ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_decider FOREIGN KEY (decided_by_user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_person FOREIGN KEY (person_id)
    REFERENCES people(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE registration_submissions
  ADD CONSTRAINT fk_regsub_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON UPDATE CASCADE ON DELETE SET NULL;
CREATE INDEX idx_regsub_decision ON registration_submissions(form_id, decision, is_archived);

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_026_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
