-- 014_staff_role.sql
-- Phase A: a 'staff' login role for department heads / coordinators who manage
-- their own department(s) but are not full admins. Scope is derived from
-- department_memberships.is_head (a staff user manages depts where their person
-- is a head). Idempotent.

INSERT INTO roles (name)
SELECT 'staff' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name='staff');
