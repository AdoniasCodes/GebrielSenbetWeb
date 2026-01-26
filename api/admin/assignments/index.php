<?php
// api/admin/assignments/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();

$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $params = [];
    $where = [];
    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
    $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === '1';
    $sql = 'SELECT a.id, a.teacher_id, a.class_id, a.subject_id, a.role, a.start_date, a.end_date, a.is_archived, a.archived_at,
                   t.first_name AS teacher_first_name, t.last_name AS teacher_last_name,
                   c.name AS class_name, s.name AS subject_name
            FROM teacher_subject_assignments a
            JOIN teachers t ON t.id = a.teacher_id
            JOIN classes c ON c.id = a.class_id
            JOIN subjects s ON s.id = a.subject_id';
    if ($classId > 0) { $where[] = 'a.class_id = ?'; $params[] = $classId; }
    if ($subjectId > 0) { $where[] = 'a.subject_id = ?'; $params[] = $subjectId; }
    if ($teacherId > 0) { $where[] = 'a.teacher_id = ?'; $params[] = $teacherId; }
    if ($activeOnly) { $where[] = 'a.is_archived = 0 AND (a.end_date IS NULL OR a.end_date >= CURDATE())'; }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY a.class_id ASC, a.subject_id ASC, a.role ASC, a.start_date DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $teacherId = (int)($input['teacher_id'] ?? 0);
    $classId = (int)($input['class_id'] ?? 0);
    $subjectId = (int)($input['subject_id'] ?? 0);
    $role = ($input['role'] ?? 'primary') === 'substitute' ? 'substitute' : 'primary';
    $start = trim($input['start_date'] ?? '');
    $end = isset($input['end_date']) && $input['end_date'] !== '' ? trim((string)$input['end_date']) : null;
    if ($teacherId <= 0 || $classId <= 0 || $subjectId <= 0 || $start === '') {
        Response::error('teacher_id, class_id, subject_id, and start_date are required', 422);
    }
    try {
        $pdo->beginTransaction();
        if ($role === 'primary') {
            $chk = $pdo->prepare("SELECT id FROM teacher_subject_assignments WHERE class_id=? AND subject_id=? AND role='primary' AND is_archived=0 AND (end_date IS NULL OR end_date >= ?) LIMIT 1");
            $chk->execute([$classId, $subjectId, $start]);
            if ($chk->fetch()) { $pdo->rollBack(); Response::error('Primary teacher already assigned for this class and subject', 409); }
        }
        $ins = $pdo->prepare('INSERT INTO teacher_subject_assignments (teacher_id, class_id, subject_id, role, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([$teacherId, $classId, $subjectId, $role, $start, $end]);
        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        Response::json(['message' => 'Assignment created', 'id' => $id], 201);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to create assignment', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $role = array_key_exists('role', $input) ? (($input['role'] === 'substitute') ? 'substitute' : 'primary') : null;
    $start = array_key_exists('start_date', $input) ? (trim((string)$input['start_date'])) : null;
    $end = array_key_exists('end_date', $input) ? (trim((string)$input['end_date'])) : null;
    if ($role === null && $start === null && $end === null) { Response::error('No changes provided', 422); }

    try {
        $pdo->beginTransaction();
        $row = $pdo->prepare('SELECT class_id, subject_id FROM teacher_subject_assignments WHERE id = ? FOR UPDATE');
        $row->execute([$id]);
        $r = $row->fetch();
        if (!$r) { $pdo->rollBack(); Response::error('Assignment not found', 404); }

        if ($role === 'primary') {
            // ensure single primary constraint for the (class, subject) tuple
            $chk = $pdo->prepare("SELECT id FROM teacher_subject_assignments WHERE id <> ? AND class_id=? AND subject_id=? AND role='primary' AND is_archived=0 AND (end_date IS NULL OR (CASE WHEN ? IS NOT NULL THEN end_date >= ? ELSE end_date IS NULL END)) LIMIT 1");
            $chk->execute([$id, (int)$r['class_id'], (int)$r['subject_id'], $start, $start]);
            if ($chk->fetch()) { $pdo->rollBack(); Response::error('Another primary teacher exists for this class and subject', 409); }
        }

        $fields = [];$params = [];
        if ($role !== null) { $fields[] = 'role = ?'; $params[] = $role; }
        if ($start !== null) { $fields[] = 'start_date = ?'; $params[] = $start; }
        if ($end !== null) { $fields[] = 'end_date = ?'; $params[] = ($end === '' ? null : $end); }
        if ($fields) {
            $params[] = $id;
            $up = $pdo->prepare('UPDATE teacher_subject_assignments SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $up->execute($params);
        }
        $pdo->commit();
        Response::json(['message' => 'Assignment updated']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to update assignment', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE teacher_subject_assignments SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Assignment not found or already archived', 404); }
    Response::json(['message' => 'Assignment archived']);
}

Response::error('Method Not Allowed', 405);
