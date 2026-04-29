<?php
// api/admin/teachers/detail.php — return one teacher with profile + assignments + recent grade entries

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
    SELECT t.id, t.user_id, t.first_name, t.last_name, t.phone, t.bio,
           t.is_archived, t.archived_at, t.created_at, t.updated_at,
           u.email
    FROM teachers t JOIN users u ON u.id=t.user_id
    WHERE t.id=?
    LIMIT 1
");
$stmt->execute([$id]);
$teacher = $stmt->fetch();
if (!$teacher) Response::error('Teacher not found', 404);

// Current assignments (class × subject)
$stmt = $pdo->prepare("
    SELECT tsa.id AS assignment_id, tsa.role, tsa.start_date, tsa.end_date,
           c.id AS class_id, c.name AS class_name, c.academic_year,
           lvl.name AS level_name, tr.name AS track_name,
           s.id AS subject_id, s.name AS subject_name
    FROM teacher_subject_assignments tsa
    JOIN classes c ON c.id=tsa.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN education_tracks tr ON tr.id=lvl.track_id
    JOIN subjects s ON s.id=tsa.subject_id
    WHERE tsa.teacher_id=? AND tsa.is_archived=0
    ORDER BY tr.name, lvl.sort_order, c.academic_year DESC, s.name
");
$stmt->execute([$id]);
$assignments = $stmt->fetchAll();

// Recent grade entries by this teacher (via primary assignments)
$stmt = $pdo->prepare("
    SELECT g.id, g.score, g.created_at,
           s.first_name AS student_first, s.last_name AS student_last,
           subj.name AS subject_name,
           c.name AS class_name, lvl.name AS level_name
    FROM grades g
    JOIN students s ON s.id=g.student_id
    JOIN subjects subj ON subj.id=g.subject_id
    JOIN classes c ON c.id=g.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN teacher_subject_assignments tsa
      ON tsa.class_id=g.class_id AND tsa.subject_id=g.subject_id AND tsa.is_archived=0
    WHERE tsa.teacher_id=? AND g.is_archived=0
    ORDER BY g.created_at DESC
    LIMIT 10
");
$stmt->execute([$id]);
$recentGrades = $stmt->fetchAll();

Response::json([
    'teacher'        => $teacher,
    'assignments'    => $assignments,
    'recent_grades'  => $recentGrades,
]);
