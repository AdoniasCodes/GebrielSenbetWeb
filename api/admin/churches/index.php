<?php
// api/admin/churches/index.php — the churches/locations the school serves.
// GET    /api/admin/churches?include_archived=
// POST   body: { name, name_am?, short_name? }
// PUT    body: { id, name?, name_am?, short_name? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($method === 'GET') {
    $includeArc = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = 'SELECT id, name, name_am, short_name, is_archived FROM churches';
    if (!$includeArc) $sql .= ' WHERE is_archived=0';
    $sql .= ' ORDER BY id';
    Response::json(['data' => $pdo->query($sql)->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim((string)($in['name'] ?? ''));
    if ($name === '') Response::error('name is required', 422);
    $nameAm = isset($in['name_am']) && $in['name_am'] !== '' ? trim((string)$in['name_am']) : null;
    $short  = isset($in['short_name']) && $in['short_name'] !== '' ? trim((string)$in['short_name']) : null;
    try {
        $ins = $pdo->prepare('INSERT INTO churches (name, name_am, short_name) VALUES (?,?,?)');
        $ins->execute([$name, $nameAm, $short]);
    } catch (\PDOException $e) {
        Response::error('A church with this name already exists', 409);
    }
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('church.create', 'church', $id, ['name' => $name]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT id FROM churches WHERE id=? AND is_archived=0');
    $cur->execute([$id]);
    if (!$cur->fetch()) Response::error('Church not found', 404);
    $fields = []; $params = [];
    if (array_key_exists('name', $in))       { $n = trim((string)$in['name']); if ($n==='') Response::error('name cannot be empty', 422); $fields[] = 'name=?'; $params[] = $n; }
    if (array_key_exists('name_am', $in))    { $fields[] = 'name_am=?';    $params[] = $in['name_am'] !== '' ? trim((string)$in['name_am']) : null; }
    if (array_key_exists('short_name', $in)) { $fields[] = 'short_name=?'; $params[] = $in['short_name'] !== '' ? trim((string)$in['short_name']) : null; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE churches SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('church.update', 'church', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE churches SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('church.archive', 'church', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
