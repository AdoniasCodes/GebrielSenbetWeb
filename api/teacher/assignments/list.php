<?php
// api/teacher/assignments/list.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$teacherUserId = (int)($_SESSION['user_id'] ?? 0);
// map to teacher.id
$ts = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? AND is_archived = 0');
$ts->execute([$teacherUserId]);
$t = $ts->fetch();
if (!$t) { Response::json(['data' => []]); }
$teacherId = (int)$t['id'];

$params = [$teacherId];
$where = ['a.teacher_id = ?'];
$activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === '1';
if ($activeOnly) { $where[] = 'a.is_archived = 0 AND (a.end_date IS NULL OR a.end_date >= CURDATE())'; }
$sql = 'SELECT a.id, a.class_id, a.subject_id, a.role, a.start_date, a.end_date,
               c.name AS class_name, c.academic_year,
               l.name AS level_name, t.name AS track_name,
               s.name AS subject_name
        FROM teacher_subject_assignments a
        JOIN classes c ON c.id = a.class_id
        JOIN class_levels l ON l.id = c.level_id
        JOIN education_tracks t ON t.id = l.track_id
        JOIN subjects s ON s.id = a.subject_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY c.academic_year DESC, t.name, l.sort_order, c.name, s.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
