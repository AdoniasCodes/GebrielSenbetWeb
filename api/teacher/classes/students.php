<?php
// api/teacher/classes/students.php - list students in a class for an assigned teacher
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$teacherUserId = (int)($_SESSION['user_id'] ?? 0);
$ts = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? AND is_archived = 0');
$ts->execute([$teacherUserId]);
$t = $ts->fetch();
if (!$t) { Response::error('Teacher not found', 403); }
$teacherId = (int)$t['id'];

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($classId <= 0) { Response::error('class_id is required', 422); }

// Ensure teacher has assignment for class (any subject)
$chk = $pdo->prepare('SELECT 1 FROM teacher_subject_assignments WHERE teacher_id = ? AND class_id = ? AND is_archived=0 AND (end_date IS NULL OR end_date >= CURDATE()) LIMIT 1');
$chk->execute([$teacherId, $classId]);
if (!$chk->fetch()) { Response::error('Not assigned to this class', 403); }

$sql = 'SELECT s.id, s.first_name, s.last_name
        FROM student_class_assignments sca
        JOIN students s ON s.id = sca.student_id
        WHERE sca.class_id = ? AND sca.is_archived = 0 AND (sca.ended_at IS NULL OR sca.ended_at >= CURDATE())
        ORDER BY s.first_name, s.last_name';
$stmt = $pdo->prepare($sql);
$stmt->execute([$classId]);
Response::json(['data' => $stmt->fetchAll()]);
