<?php
// api/staff/levels.php — advancement-level management scoped to departments the user heads.
// GET ?department_id= | POST {department_id, name, name_am?, rank?} | PUT {id, ...} | DELETE {id}

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = $GLOBALS['__staff_pdo'];

function _level_dept(\PDO $pdo, int $id): ?int {
    $s = $pdo->prepare('SELECT department_id FROM department_levels WHERE id=? AND is_archived=0');
    $s->execute([$id]);
    $r = $s->fetch();
    return $r ? (int)$r['department_id'] : null;
}

if ($method === 'GET') {
    $deptId = (int)($_GET['department_id'] ?? 0);
    if ($deptId <= 0) Response::error('department_id is required', 422);
    staff_assert_dept($deptId);
    $sql = "SELECT id, department_id, name, name_am, `rank`, description,
                   (SELECT COUNT(*) FROM department_memberships dm WHERE dm.level_id=department_levels.id AND dm.is_archived=0) AS member_count
            FROM department_levels WHERE department_id=? AND is_archived=0 ORDER BY `rank`, id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deptId]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId = (int)($in['department_id'] ?? 0);
    $name   = trim((string)($in['name'] ?? ''));
    if ($deptId <= 0 || $name === '') Response::error('department_id and name are required', 422);
    staff_assert_dept($deptId);
    $nameAm = isset($in['name_am']) && $in['name_am'] !== '' ? trim((string)$in['name_am']) : null;
    $rank   = (int)($in['rank'] ?? 0);
    try {
        $pdo->prepare('INSERT INTO department_levels (department_id, name, name_am, `rank`) VALUES (?,?,?,?)')
            ->execute([$deptId, $name, $nameAm, $rank]);
    } catch (\PDOException $e) {
        Response::error('A level with this name already exists in this department', 409);
    }
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('staff.level.create', 'department_level', $id, ['department_id' => $deptId]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $deptId = _level_dept($pdo, $id);
    if ($deptId === null) Response::error('Level not found', 404);
    staff_assert_dept($deptId);
    $fields = []; $params = [];
    if (array_key_exists('name', $in))    { $n = trim((string)$in['name']); if ($n==='') Response::error('name cannot be empty', 422); $fields[]='name=?'; $params[]=$n; }
    if (array_key_exists('name_am', $in)) { $fields[]='name_am=?'; $params[]=$in['name_am']!==''?trim((string)$in['name_am']):null; }
    if (array_key_exists('rank', $in))    { $fields[]='`rank`=?'; $params[]=(int)$in['rank']; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    try {
        $pdo->prepare('UPDATE department_levels SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    } catch (\PDOException $e) {
        Response::error('A level with this name already exists in this department', 409);
    }
    \App\Audit::log('staff.level.update', 'department_level', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $deptId = _level_dept($pdo, $id);
    if ($deptId === null) Response::error('Level not found', 404);
    staff_assert_dept($deptId);
    $pdo->prepare('UPDATE department_memberships SET level_id=NULL WHERE level_id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE department_levels SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    \App\Audit::log('staff.level.archive', 'department_level', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
