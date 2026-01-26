<?php
// api/student/grades/index.php - student/parent read-only grades view
use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? '') !== 'student') {
    Response::error('Forbidden', 403);
}

$db = new Database($config['db']);
$pdo = $db->pdo();

$studentUserId = (int)($_SESSION['user_id'] ?? 0);
$ss = $pdo->prepare('SELECT id FROM students WHERE user_id = ? AND is_archived = 0');
$ss->execute([$studentUserId]);
$s = $ss->fetch();
if (!$s) { Response::json(['data' => []]); }
$studentId = (int)$s['id'];

$termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;
$params = [$studentId];
$where = ['g.student_id = ?','g.is_archived = 0'];
if ($termId > 0) { $where[] = 'g.term_id = ?'; $params[] = $termId; }

$sql = 'SELECT g.id, g.subject_id, g.class_id, g.term_id, g.score, g.remarks,
               sub.name AS subject_name
        FROM grades g
        JOIN subjects sub ON sub.id = g.subject_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY sub.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
