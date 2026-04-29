<?php
// api/admin/student-assignments/index.php — manage student → class assignments

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

// GET: list all current assignments, optionally filtered by class_id or student_id
if ($method === 'GET') {
    $classId   = isset($_GET['class_id'])   ? (int)$_GET['class_id']   : 0;
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $sql = "SELECT sca.id, sca.student_id, sca.class_id, sca.assigned_at, sca.ended_at, sca.is_archived, sca.archived_at,
                   s.first_name, s.last_name,
                   c.name AS class_name, c.academic_year,
                   lvl.name AS level_name, t.name AS track_name
            FROM student_class_assignments sca
            JOIN students s ON s.id=sca.student_id
            JOIN classes c ON c.id=sca.class_id
            JOIN class_levels lvl ON lvl.id=c.level_id
            JOIN education_tracks t ON t.id=lvl.track_id
            WHERE 1=1";
    $params = [];
    if ($classId > 0)   { $sql .= ' AND sca.class_id=?';   $params[] = $classId; }
    if ($studentId > 0) { $sql .= ' AND sca.student_id=?'; $params[] = $studentId; }
    if (!$includeArchived) $sql .= ' AND sca.is_archived=0';
    $sql .= ' ORDER BY sca.assigned_at DESC, sca.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

// POST: assign a student to a class
//   body: { student_id, class_id, assigned_at? }
//   if student already has a non-archived assignment, that one is ended (transactional promotion)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $studentId = (int)($input['student_id'] ?? 0);
    $classId   = (int)($input['class_id'] ?? 0);
    $assignedAt = trim((string)($input['assigned_at'] ?? date('Y-m-d')));
    if ($studentId <= 0 || $classId <= 0) Response::error('student_id and class_id are required', 422);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $assignedAt)) Response::error('assigned_at must be YYYY-MM-DD', 422);

    try {
        $pdo->beginTransaction();
        // Validate student + class exist
        $sCheck = $pdo->prepare("SELECT id FROM students WHERE id=? AND is_archived=0");
        $sCheck->execute([$studentId]);
        if (!$sCheck->fetch()) { $pdo->rollBack(); Response::error('Student not found', 404); }

        $cCheck = $pdo->prepare("SELECT id FROM classes WHERE id=? AND is_archived=0");
        $cCheck->execute([$classId]);
        if (!$cCheck->fetch()) { $pdo->rollBack(); Response::error('Class not found', 404); }

        // End any current assignment(s) for this student
        $end = $pdo->prepare("UPDATE student_class_assignments SET ended_at=?, is_archived=1, archived_at=NOW() WHERE student_id=? AND is_archived=0");
        $end->execute([$assignedAt, $studentId]);

        // Create new assignment
        $ins = $pdo->prepare("INSERT INTO student_class_assignments (student_id, class_id, assigned_at) VALUES (?, ?, ?)");
        $ins->execute([$studentId, $classId, $assignedAt]);
        $newId = (int)$pdo->lastInsertId();

        $pdo->commit();
        Response::json(['ok' => true, 'id' => $newId]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to assign student', 500);
    }
}

// DELETE: archive an assignment (mark ended)
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $stmt = $pdo->prepare("UPDATE student_class_assignments SET ended_at=CURDATE(), is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0");
    $stmt->execute([$id]);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
