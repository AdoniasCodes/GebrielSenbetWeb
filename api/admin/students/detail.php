<?php
// api/admin/students/detail.php — return one student with profile, current class, grade history, payment history

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) Response::error('id is required', 422);

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$stmt = $pdo->prepare("
    SELECT s.id, s.user_id, s.first_name, s.last_name, s.date_of_birth, s.guardian_name, s.phone, s.address,
           s.is_archived, s.archived_at, s.created_at, s.updated_at,
           u.email
    FROM students s JOIN users u ON u.id=s.user_id
    WHERE s.id=?
    LIMIT 1
");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) Response::error('Student not found', 404);

// Current assignment
$stmt = $pdo->prepare("
    SELECT sca.id AS assignment_id, sca.assigned_at, sca.ended_at,
           c.id AS class_id, c.name AS class_name, c.academic_year,
           lvl.id AS level_id, lvl.name AS level_name,
           t.id AS track_id, t.name AS track_name
    FROM student_class_assignments sca
    JOIN classes c ON c.id=sca.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN education_tracks t ON t.id=lvl.track_id
    WHERE sca.student_id=? AND sca.is_archived=0
    ORDER BY sca.id DESC
    LIMIT 1
");
$stmt->execute([$id]);
$currentAssignment = $stmt->fetch() ?: null;

// All assignment history
$stmt = $pdo->prepare("
    SELECT sca.id, sca.assigned_at, sca.ended_at, sca.is_archived,
           c.name AS class_name, c.academic_year, lvl.name AS level_name, t.name AS track_name
    FROM student_class_assignments sca
    JOIN classes c ON c.id=sca.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN education_tracks t ON t.id=lvl.track_id
    WHERE sca.student_id=?
    ORDER BY sca.id DESC
");
$stmt->execute([$id]);
$assignmentHistory = $stmt->fetchAll();

// Grades
$stmt = $pdo->prepare("
    SELECT g.id, g.score, g.remarks, g.created_at,
           subj.name AS subject_name,
           term.name AS term_name, term.academic_year,
           c.name AS class_name, lvl.name AS level_name
    FROM grades g
    JOIN subjects subj ON subj.id=g.subject_id
    JOIN academic_terms term ON term.id=g.term_id
    JOIN classes c ON c.id=g.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    WHERE g.student_id=? AND g.is_archived=0
    ORDER BY term.start_date DESC, subj.name ASC
");
$stmt->execute([$id]);
$grades = $stmt->fetchAll();

// Payments
$stmt = $pdo->prepare("
    SELECT p.id, p.amount, p.status, p.notes, p.created_at,
           term.name AS term_name, term.academic_year
    FROM payments p
    JOIN academic_terms term ON term.id=p.term_id
    WHERE p.student_id=? AND p.is_archived=0
    ORDER BY term.start_date DESC
");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

Response::json([
    'student'             => $student,
    'current_assignment'  => $currentAssignment,
    'assignment_history'  => $assignmentHistory,
    'grades'              => $grades,
    'payments'            => $payments,
]);
