<?php
// api/admin/classes/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();

$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    // Optional filters: level_id, academic_year; exclude archived by default
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $levelId = isset($_GET['level_id']) ? (int)$_GET['level_id'] : 0;
    $year = isset($_GET['academic_year']) ? trim((string)$_GET['academic_year']) : '';
    $params = [];
    $where = [];
    $sql = 'SELECT c.id, c.level_id, c.academic_year, c.name, c.is_archived, c.archived_at, c.created_at, c.updated_at,
                   l.name AS level_name, t.name AS track_name
            FROM classes c
            JOIN class_levels l ON l.id = c.level_id
            JOIN education_tracks t ON t.id = l.track_id';
    if ($levelId > 0) { $where[] = 'c.level_id = ?'; $params[] = $levelId; }
    if ($year !== '') { $where[] = 'c.academic_year = ?'; $params[] = $year; }
    if (!$includeArchived) { $where[] = 'c.is_archived = 0'; }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY c.academic_year DESC, t.name ASC, l.sort_order ASC, c.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $levelId = (int)($input['level_id'] ?? 0);
    $year = trim($input['academic_year'] ?? '');
    $name = trim($input['name'] ?? '');
    if ($levelId <= 0 || $year === '' || $name === '') {
        Response::error('level_id, academic_year, and name are required', 422);
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO classes (level_id, academic_year, name) VALUES (?, ?, ?)');
        $stmt->execute([$levelId, $year, $name]);
        Response::json(['message' => 'Class created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Class already exists for level and year', 409); }
        Response::error('Failed to create class', 500);
    }
}

if ($method === 'PUT') {
    // Allow updating name and academic_year. Keep level_id immutable to preserve integrity.
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $year = isset($input['academic_year']) ? trim((string)$input['academic_year']) : null;
    $name = isset($input['name']) ? trim((string)$input['name']) : null;
    if ($id <= 0 || ($year === null && $name === null)) {
        Response::error('id and at least one of academic_year/name required', 422);
    }
    $fields = [];
    $params = [];
    if ($year !== null && $year !== '') { $fields[] = 'academic_year = ?'; $params[] = $year; }
    if ($name !== null && $name !== '') { $fields[] = 'name = ?'; $params[] = $name; }
    if (!$fields) { Response::error('No changes provided', 422); }
    $params[] = $id;
    try {
        $stmt = $pdo->prepare('UPDATE classes SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) { Response::error('Class not found or no change', 404); }
        Response::json(['message' => 'Class updated']);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Class name/year conflict', 409); }
        Response::error('Failed to update class', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE classes SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Class not found or already archived', 404); }
    Response::json(['message' => 'Class archived']);
}

Response::error('Method Not Allowed', 405);
