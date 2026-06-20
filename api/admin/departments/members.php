<?php
// api/admin/departments/members.php — manage a department's roster (memberships).
// GET    /api/admin/departments/members.php?department_id=   → roster (person + level + title + head)
// POST   body: { person_id, department_id, level_id?, title?, is_head?, joined_at? }
// PUT    body: { id, level_id?, title?, is_head?, ended_at? }
// DELETE body: { id }   (archives the membership)

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
    $sql = "SELECT dm.id, dm.person_id, dm.department_id, dm.level_id, dm.title, dm.is_head,
                   dm.joined_at, dm.ended_at,
                   CONCAT(p.first_name, ' ', p.last_name) AS person_name,
                   p.phone, p.member_status,
                   dl.name AS level_name, dl.name_am AS level_name_am, dl.`rank` AS level_rank
            FROM department_memberships dm
            JOIN people p ON p.id = dm.person_id
            LEFT JOIN department_levels dl ON dl.id = dm.level_id
            WHERE dm.department_id = ? AND dm.is_archived = 0
            ORDER BY dm.is_head DESC, dl.`rank` ASC, p.first_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deptId]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $personId = (int)($in['person_id'] ?? 0);
    $deptId   = (int)($in['department_id'] ?? 0);
    if ($personId <= 0 || $deptId <= 0) Response::error('person_id and department_id are required', 422);

    $pc = $pdo->prepare('SELECT id FROM people WHERE id=? AND is_archived=0'); $pc->execute([$personId]);
    if (!$pc->fetch()) Response::error('Person not found', 404);
    $dc = $pdo->prepare('SELECT id FROM departments WHERE id=? AND is_archived=0'); $dc->execute([$deptId]);
    if (!$dc->fetch()) Response::error('Department not found', 404);

    // Already an active member? Reactivate / update instead of duplicating.
    $ex = $pdo->prepare('SELECT id FROM department_memberships WHERE person_id=? AND department_id=? AND is_archived=0 LIMIT 1');
    $ex->execute([$personId, $deptId]);
    if ($ex->fetch()) Response::error('This person is already a member of this department', 409);

    $levelId = (int)($in['level_id'] ?? 0) ?: null;
    if ($levelId) {
        $lc = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND department_id=? AND is_archived=0');
        $lc->execute([$levelId, $deptId]);
        if (!$lc->fetch()) Response::error('Level does not belong to this department', 422);
    }
    $title  = isset($in['title']) && $in['title'] !== '' ? trim((string)$in['title']) : null;
    $isHead = !empty($in['is_head']) ? 1 : 0;
    $joined = isset($in['joined_at']) && $in['joined_at'] !== '' ? trim((string)$in['joined_at']) : null;

    $ins = $pdo->prepare('INSERT INTO department_memberships (person_id, department_id, level_id, title, is_head, joined_at) VALUES (?,?,?,?,?,?)');
    $ins->execute([$personId, $deptId, $levelId, $title, $isHead, $joined]);
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('department.member.add', 'department_membership', $id, ['department_id' => $deptId, 'person_id' => $personId]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT department_id FROM department_memberships WHERE id=? AND is_archived=0');
    $cur->execute([$id]);
    $row = $cur->fetch();
    if (!$row) Response::error('Membership not found', 404);

    $fields = []; $params = [];
    if (array_key_exists('level_id', $in)) {
        $levelId = (int)$in['level_id'] ?: null;
        if ($levelId) {
            $lc = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND department_id=? AND is_archived=0');
            $lc->execute([$levelId, (int)$row['department_id']]);
            if (!$lc->fetch()) Response::error('Level does not belong to this department', 422);
        }
        $fields[] = 'level_id=?'; $params[] = $levelId;
    }
    if (array_key_exists('title', $in))   { $fields[] = 'title=?';   $params[] = $in['title'] !== '' ? trim((string)$in['title']) : null; }
    if (array_key_exists('is_head', $in)) { $fields[] = 'is_head=?'; $params[] = !empty($in['is_head']) ? 1 : 0; }
    if (array_key_exists('ended_at', $in)){ $fields[] = 'ended_at=?';$params[] = $in['ended_at'] !== '' ? trim((string)$in['ended_at']) : null; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE department_memberships SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('department.member.update', 'department_membership', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE department_memberships SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('department.member.remove', 'department_membership', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
