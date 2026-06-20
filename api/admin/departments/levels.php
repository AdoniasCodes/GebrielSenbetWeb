<?php
// api/admin/departments/levels.php — manage a department's advancement ladder.
// GET    /api/admin/departments/levels.php?department_id=   → levels ordered by rank
// POST   body: { department_id, name, name_am?, rank?, description? }
// PUT    body: { id, name?, name_am?, rank?, description? }
// DELETE body: { id }   (archives the level; clears it from any memberships using it)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($method === 'GET') {
    $deptId = (int)($_GET['department_id'] ?? 0);
    if ($deptId <= 0) Response::error('department_id is required', 422);
    $sql = "SELECT id, department_id, name, name_am, `rank`, description,
                   (SELECT COUNT(*) FROM department_memberships dm WHERE dm.level_id = department_levels.id AND dm.is_archived=0) AS member_count
            FROM department_levels
            WHERE department_id = ? AND is_archived = 0
            ORDER BY `rank` ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deptId]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId = (int)($in['department_id'] ?? 0);
    $name   = trim((string)($in['name'] ?? ''));
    if ($deptId <= 0 || $name === '') Response::error('department_id and name are required', 422);
    $dc = $pdo->prepare('SELECT id FROM departments WHERE id=? AND is_archived=0'); $dc->execute([$deptId]);
    if (!$dc->fetch()) Response::error('Department not found', 404);

    $nameAm = isset($in['name_am']) && $in['name_am'] !== '' ? trim((string)$in['name_am']) : null;
    $rank   = (int)($in['rank'] ?? 0);
    $desc   = isset($in['description']) && $in['description'] !== '' ? trim((string)$in['description']) : null;
    try {
        $ins = $pdo->prepare('INSERT INTO department_levels (department_id, name, name_am, `rank`, description) VALUES (?,?,?,?,?)');
        $ins->execute([$deptId, $name, $nameAm, $rank, $desc]);
    } catch (\PDOException $e) {
        Response::error('A level with this name already exists in this department', 409);
    }
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('department.level.create', 'department_level', $id, ['department_id' => $deptId]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND is_archived=0');
    $cur->execute([$id]);
    if (!$cur->fetch()) Response::error('Level not found', 404);

    $fields = []; $params = [];
    if (array_key_exists('name', $in))        { $n = trim((string)$in['name']); if ($n==='') Response::error('name cannot be empty', 422); $fields[] = 'name=?'; $params[] = $n; }
    if (array_key_exists('name_am', $in))     { $fields[] = 'name_am=?';     $params[] = $in['name_am'] !== '' ? trim((string)$in['name_am']) : null; }
    if (array_key_exists('rank', $in))        { $fields[] = '`rank`=?';      $params[] = (int)$in['rank']; }
    if (array_key_exists('description', $in)) { $fields[] = 'description=?'; $params[] = $in['description'] !== '' ? trim((string)$in['description']) : null; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    try {
        $pdo->prepare('UPDATE department_levels SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    } catch (\PDOException $e) {
        Response::error('A level with this name already exists in this department', 409);
    }
    \App\Audit::log('department.level.update', 'department_level', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE department_memberships SET level_id=NULL WHERE level_id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE department_levels SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('department.level.archive', 'department_level', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
