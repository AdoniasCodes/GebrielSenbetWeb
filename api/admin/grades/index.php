<?php
// api/admin/grades/index.php — admin CRUD for grades across the whole school.
// GET    /api/admin/grades?term_id=&class_id=&subject_id=&student_id=&include_archived=
// POST   body: { student_id, subject_id, class_id, term_id, score, remarks? }
// PUT    body: { id, score?, remarks? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $termId    = isset($_GET['term_id'])    ? (int)$_GET['term_id']    : 0;
    $classId   = isset($_GET['class_id'])   ? (int)$_GET['class_id']   : 0;
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $sql = "SELECT g.id, g.student_id, g.subject_id, g.class_id, g.term_id,
                   g.score, g.remarks, g.is_archived, g.created_at, g.updated_at,
                   s.first_name, s.last_name,
                   sub.name AS subject_name,
                   c.name   AS class_name,
                   lvl.name AS level_name,
                   t.name   AS term_name, t.academic_year
            FROM grades g
            JOIN students s        ON s.id = g.student_id
            JOIN subjects sub      ON sub.id = g.subject_id
            JOIN classes c         ON c.id = g.class_id
            LEFT JOIN class_levels lvl ON lvl.id = c.level_id
            JOIN academic_terms t  ON t.id = g.term_id
            WHERE 1=1";
    $params = [];
    if ($termId    > 0) { $sql .= ' AND g.term_id=?';    $params[] = $termId; }
    if ($classId   > 0) { $sql .= ' AND g.class_id=?';   $params[] = $classId; }
    if ($subjectId > 0) { $sql .= ' AND g.subject_id=?'; $params[] = $subjectId; }
    if ($studentId > 0) { $sql .= ' AND g.student_id=?'; $params[] = $studentId; }
    if (!$includeArchived) { $sql .= ' AND g.is_archived=0'; }
    $sql .= ' ORDER BY t.academic_year DESC, t.start_date DESC, s.last_name, s.first_name, sub.name LIMIT 1000';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $sum = 0.0; $n = 0;
    foreach ($rows as $r) { $sum += (float)$r['score']; $n++; }
    Response::json([
        'data' => $rows,
        'totals' => [
            'count' => $n,
            'avg'   => $n > 0 ? round($sum / $n, 2) : 0,
        ],
    ]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $studentId = (int)($input['student_id'] ?? 0);
    $subjectId = (int)($input['subject_id'] ?? 0);
    $classId   = (int)($input['class_id']   ?? 0);
    $termId    = (int)($input['term_id']    ?? 0);
    $score     = isset($input['score']) ? (float)$input['score'] : null;
    $remarks   = isset($input['remarks']) && $input['remarks'] !== '' ? trim((string)$input['remarks']) : null;

    if ($studentId <= 0 || $subjectId <= 0 || $classId <= 0 || $termId <= 0 || $score === null) {
        Response::error('student_id, subject_id, class_id, term_id, score are required', 422);
    }
    if ($score < 0 || $score > 100) Response::error('score must be between 0 and 100', 422);

    try {
        $stmt = $pdo->prepare('INSERT INTO grades (student_id, subject_id, class_id, term_id, score, remarks) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$studentId, $subjectId, $classId, $termId, $score, $remarks]);
        $newId = (int)$pdo->lastInsertId();
        \App\Audit::log('grade.create', 'grade', $newId, ['student_id' => $studentId, 'subject_id' => $subjectId, 'class_id' => $classId, 'term_id' => $termId, 'score' => $score]);
        Response::json(['ok' => true, 'id' => $newId], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) Response::error('Grade already exists for this student/subject/class/term', 409);
        Response::error('Failed to create grade', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $g = $pdo->prepare('SELECT id, score, remarks FROM grades WHERE id=? AND is_archived=0');
    $g->execute([$id]);
    $row = $g->fetch();
    if (!$row) Response::error('Grade not found', 404);

    $score = array_key_exists('score', $input)   ? (float)$input['score']   : (float)$row['score'];
    $remarks = array_key_exists('remarks', $input)
        ? (is_null($input['remarks']) || $input['remarks'] === '' ? null : trim((string)$input['remarks']))
        : $row['remarks'];

    if ($score < 0 || $score > 100) Response::error('score must be between 0 and 100', 422);

    $upd = $pdo->prepare('UPDATE grades SET score=?, remarks=? WHERE id=?');
    $upd->execute([$score, $remarks, $id]);
    \App\Audit::log('grade.update', 'grade', $id, ['score' => $score]);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare('UPDATE grades SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    \App\Audit::log('grade.archive', 'grade', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
