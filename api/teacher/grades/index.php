<?php
// api/teacher/grades/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$teacherUserId = (int)($_SESSION['user_id'] ?? 0);
$ts = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? AND is_archived = 0');
$ts->execute([$teacherUserId]);
$t = $ts->fetch();
if (!$t) { Response::error('Teacher not found', 403); }
$teacherId = (int)$t['id'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // List grades for classes/subjects assigned to this teacher
    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    $termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;

    // Restrict to teacher assignments
    $where = ['g.is_archived = 0'];
    $params = [];
    if ($classId > 0) { $where[] = 'g.class_id = ?'; $params[] = $classId; }
    if ($subjectId > 0) { $where[] = 'g.subject_id = ?'; $params[] = $subjectId; }
    if ($termId > 0) { $where[] = 'g.term_id = ?'; $params[] = $termId; }

    // Ensure teacher has assignment for any class/subject requested
    $assignFilter = '';
    if ($classId > 0 && $subjectId > 0) {
        $assignFilter = 'AND EXISTS (SELECT 1 FROM teacher_subject_assignments a WHERE a.teacher_id = ? AND a.class_id = g.class_id AND a.subject_id = g.subject_id AND a.is_archived=0 AND (a.end_date IS NULL OR a.end_date >= CURDATE()))';
        array_unshift($params, $teacherId); // teacherId used first in query
    } else {
        // broader: restrict any returned rows to those the teacher has assignments for
        $assignFilter = 'AND EXISTS (SELECT 1 FROM teacher_subject_assignments a WHERE a.teacher_id = ? AND a.class_id = g.class_id AND a.subject_id = g.subject_id AND a.is_archived=0 AND (a.end_date IS NULL OR a.end_date >= CURDATE()))';
        array_unshift($params, $teacherId);
    }

    $sql = 'SELECT g.id, g.student_id, g.subject_id, g.class_id, g.term_id, g.score, g.remarks,
                   s.first_name, s.last_name
            FROM grades g
            JOIN students s ON s.id = g.student_id
            WHERE ' . implode(' AND ', $where) . ' ' . $assignFilter . '
            ORDER BY s.first_name, s.last_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $studentId = (int)($input['student_id'] ?? 0);
    $subjectId = (int)($input['subject_id'] ?? 0);
    $classId = (int)($input['class_id'] ?? 0);
    $termId = (int)($input['term_id'] ?? 0);
    $score = isset($input['score']) ? (float)$input['score'] : null;
    $remarks = isset($input['remarks']) ? trim((string)$input['remarks']) : null;
    if ($studentId <= 0 || $subjectId <= 0 || $classId <= 0 || $termId <= 0 || $score === null) {
        Response::error('student_id, subject_id, class_id, term_id, score are required', 422);
    }
    // Ensure teacher is assigned to class+subject
    $chk = $pdo->prepare("SELECT 1 FROM teacher_subject_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND is_archived=0 AND (end_date IS NULL OR end_date >= CURDATE()) LIMIT 1");
    $chk->execute([$teacherId, $classId, $subjectId]);
    if (!$chk->fetch()) { Response::error('Not assigned to this class/subject', 403); }

    try {
        $stmt = $pdo->prepare('INSERT INTO grades (student_id, subject_id, class_id, term_id, score, remarks) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$studentId, $subjectId, $classId, $termId, $score, $remarks]);
        Response::json(['message' => 'Grade created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Grade already exists for this student/subject/class/term', 409); }
        Response::error('Failed to create grade', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $score = array_key_exists('score', $input) ? (float)$input['score'] : null;
    $remarks = array_key_exists('remarks', $input) ? (is_null($input['remarks']) ? null : trim((string)$input['remarks'])) : null;
    if ($id <= 0 || ($score === null && !array_key_exists('remarks', $input))) {
        Response::error('id and at least one of score/remarks required', 422);
    }
    // Ensure the grade belongs to a class/subject that this teacher is assigned to
    $g = $pdo->prepare('SELECT class_id, subject_id FROM grades WHERE id = ?');
    $g->execute([$id]);
    $row = $g->fetch();
    if (!$row) { Response::error('Grade not found', 404); }
    $chk = $pdo->prepare("SELECT 1 FROM teacher_subject_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? AND is_archived=0 AND (end_date IS NULL OR end_date >= CURDATE()) LIMIT 1");
    $chk->execute([$teacherId, (int)$row['class_id'], (int)$row['subject_id']]);
    if (!$chk->fetch()) { Response::error('Not assigned to this class/subject', 403); }

    $fields = [];$params = [];
    if ($score !== null) { $fields[] = 'score = ?'; $params[] = $score; }
    if (array_key_exists('remarks', $input)) { $fields[] = 'remarks = ?'; $params[] = $remarks; }
    $params[] = $id;
    $stmt = $pdo->prepare('UPDATE grades SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);
    if ($stmt->rowCount() === 0) { Response::error('No change or grade not found', 404); }
    Response::json(['message' => 'Grade updated']);
}

Response::error('Method Not Allowed', 405);
