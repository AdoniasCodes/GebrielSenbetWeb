<?php
// api/admin/tracks/index.php
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
    $sql = 'SELECT id, name, is_archived, archived_at, created_at, updated_at FROM education_tracks';
    if (!$includeArchived) { $sql .= ' WHERE is_archived = 0'; }
    $sql .= ' ORDER BY name ASC';
    $rows = $pdo->query($sql)->fetchAll();
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    if ($name === '') { Response::error('Track name is required', 422); }

    try {
        $stmt = $pdo->prepare('INSERT INTO education_tracks (name) VALUES (?)');
        $stmt->execute([$name]);
        Response::json(['message' => 'Track created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) { // duplicate
            Response::error('Track name already exists', 409);
        }
        Response::error('Failed to create track', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    if ($id <= 0 || $name === '') { Response::error('id and name are required', 422); }
    try {
        $stmt = $pdo->prepare('UPDATE education_tracks SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
        if ($stmt->rowCount() === 0) { Response::error('Track not found or no change', 404); }
        Response::json(['message' => 'Track updated']);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            Response::error('Track name already exists', 409);
        }
        Response::error('Failed to update track', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE education_tracks SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Track not found or already archived', 404); }
    Response::json(['message' => 'Track archived']);
}

Response::error('Method Not Allowed', 405);
