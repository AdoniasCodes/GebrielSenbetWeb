<?php
// api/admin/levels/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();

$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    // Optional filter by track_id; exclude archived by default
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $trackId = isset($_GET['track_id']) ? (int)$_GET['track_id'] : 0;
    $params = [];
    $sql = 'SELECT l.id, l.track_id, l.name, l.name_am, l.alias, l.sort_order, l.is_archived, l.archived_at, l.created_at, l.updated_at, t.name AS track_name,
                   (SELECT COUNT(*) FROM grade_subjects gs WHERE gs.level_id = l.id AND gs.is_archived = 0) AS subject_count
            FROM class_levels l JOIN education_tracks t ON t.id = l.track_id';
    $where = [];
    if ($trackId > 0) { $where[] = 'l.track_id = ?'; $params[] = $trackId; }
    if (!$includeArchived) { $where[] = 'l.is_archived = 0'; }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY t.name ASC, l.sort_order ASC, l.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $trackId = (int)($input['track_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $nameAm = isset($input['name_am']) && $input['name_am'] !== '' ? trim((string)$input['name_am']) : null;
    $alias = isset($input['alias']) && $input['alias'] !== '' ? trim((string)$input['alias']) : null;
    $sort = (int)($input['sort_order'] ?? 0);
    if ($trackId <= 0 || $name === '') { Response::error('track_id and name are required', 422); }
    try {
        $stmt = $pdo->prepare('INSERT INTO class_levels (track_id, name, name_am, alias, sort_order) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$trackId, $name, $nameAm, $alias, $sort]);
        Response::json(['message' => 'Level created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Level already exists for this track', 409); }
        Response::error('Failed to create level', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $name = isset($input['name']) ? trim((string)$input['name']) : null;
    $sort = isset($input['sort_order']) ? (int)$input['sort_order'] : null;
    if ($id <= 0) { Response::error('id is required', 422); }
    $fields = [];
    $params = [];
    if ($name !== null && $name !== '') { $fields[] = 'name = ?'; $params[] = $name; }
    if (array_key_exists('name_am', $input)) { $fields[] = 'name_am = ?'; $params[] = $input['name_am'] !== '' ? trim((string)$input['name_am']) : null; }
    if (array_key_exists('alias', $input)) { $fields[] = 'alias = ?'; $params[] = $input['alias'] !== '' ? trim((string)$input['alias']) : null; }
    if ($sort !== null) { $fields[] = 'sort_order = ?'; $params[] = $sort; }
    if (!$fields) { Response::error('No changes provided', 422); }
    $params[] = $id;
    try {
        $stmt = $pdo->prepare('UPDATE class_levels SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) { Response::error('Level not found or no change', 404); }
        Response::json(['message' => 'Level updated']);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Level name conflict', 409); }
        Response::error('Failed to update level', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE class_levels SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Level not found or already archived', 404); }
    Response::json(['message' => 'Level archived']);
}

Response::error('Method Not Allowed', 405);
