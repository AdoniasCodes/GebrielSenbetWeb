<?php
// api/parent/grades/index.php — grades for the parent's children, optionally scoped by student_id or term_id.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$allowed = parent_student_ids();
if (!$allowed) { Response::json(['data' => []]); }

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$termId    = isset($_GET['term_id'])    ? (int)$_GET['term_id']    : 0;

if ($studentId > 0 && !in_array($studentId, $allowed, true)) Response::error('Forbidden', 403);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($studentId > 0) {
    $ids = [$studentId];
    $placeholders = '(?)';
} else {
    $ids = $allowed;
    $placeholders = parent_student_id_placeholders();
}

$sql = "SELECT g.id, g.student_id, g.score, g.remarks, g.created_at,
               s.first_name, s.last_name,
               sub.name AS subject_name,
               c.name   AS class_name, lvl.name AS level_name,
               t.name   AS term_name, t.academic_year
        FROM grades g
        JOIN students s        ON s.id=g.student_id
        JOIN subjects sub      ON sub.id=g.subject_id
        JOIN classes c         ON c.id=g.class_id
        LEFT JOIN class_levels lvl ON lvl.id=c.level_id
        JOIN academic_terms t  ON t.id=g.term_id
        WHERE g.is_archived=0 AND g.student_id IN $placeholders";
$params = $ids;
if ($termId > 0) { $sql .= ' AND g.term_id=?'; $params[] = $termId; }
$sql .= ' ORDER BY t.academic_year DESC, t.start_date DESC, sub.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
