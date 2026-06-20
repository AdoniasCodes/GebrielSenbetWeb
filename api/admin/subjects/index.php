<?php
// api/admin/subjects/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();

$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = 'SELECT id, name, name_am, description, is_archived, archived_at, created_at, updated_at FROM subjects';
    if (!$includeArchived) { $sql .= ' WHERE is_archived = 0'; }
    $sql .= ' ORDER BY name ASC';
    $rows = $pdo->query($sql)->fetchAll();
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    $nameAm = isset($input['name_am']) && $input['name_am'] !== '' ? trim((string)$input['name_am']) : null;
    $desc = isset($input['description']) ? trim((string)$input['description']) : null;
    if ($name === '') { Response::error('Subject name is required', 422); }
    try {
        $stmt = $pdo->prepare('INSERT INTO subjects (name, name_am, description) VALUES (?, ?, ?)');
        $stmt->execute([$name, $nameAm, $desc]);
        Response::json(['message' => 'Subject created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Subject name already exists', 409); }
        Response::error('Failed to create subject', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $name = isset($input['name']) ? trim((string)$input['name']) : null;
    $desc = array_key_exists('description', $input) ? (is_null($input['description']) ? null : trim((string)$input['description'])) : null;
    if ($id <= 0) { Response::error('id is required', 422); }
    $fields = [];
    $params = [];
    if ($name !== null && $name !== '') { $fields[] = 'name = ?'; $params[] = $name; }
    if (array_key_exists('name_am', $input)) { $fields[] = 'name_am = ?'; $params[] = $input['name_am'] !== '' ? trim((string)$input['name_am']) : null; }
    if (array_key_exists('description', $input)) { $fields[] = 'description = ?'; $params[] = $desc; }
    if (!$fields) { Response::error('No changes provided', 422); }
    $params[] = $id;
    try {
        $stmt = $pdo->prepare('UPDATE subjects SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) { Response::error('Subject not found or no change', 404); }
        Response::json(['message' => 'Subject updated']);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { Response::error('Subject name already exists', 409); }
        Response::error('Failed to update subject', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE subjects SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Subject not found or already archived', 404); }
    Response::json(['message' => 'Subject archived']);
}

Response::error('Method Not Allowed', 405);
