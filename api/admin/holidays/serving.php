<?php
// api/admin/holidays/serving.php — serving assignments for a holiday.
// Which department / advancement level serves at which church (and with seniors?).
// GET    ?holiday_id=  → assignments (with department / level / church names)
// POST   { holiday_id, department_id, level_id?, church_id?, with_seniors?, notes? }
// PUT    { id, ...any }
// DELETE { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($method === 'GET') {
    $hid = (int)($_GET['holiday_id'] ?? 0);
    if ($hid <= 0) Response::error('holiday_id is required', 422);
    $sql = "SELECT sa.id, sa.holiday_id, sa.department_id, sa.level_id, sa.church_id, sa.with_seniors, sa.notes,
                   COALESCE(d.name_am, d.name) AS department_name,
                   dl.name AS level_name, dl.name_am AS level_name_am,
                   ch.short_name AS church_name
            FROM serving_assignments sa
            JOIN departments d ON d.id = sa.department_id
            LEFT JOIN department_levels dl ON dl.id = sa.level_id
            LEFT JOIN churches ch ON ch.id = sa.church_id
            WHERE sa.holiday_id = ? AND sa.is_archived = 0
            ORDER BY d.sort_order, dl.`rank`";
    $stmt = $pdo->prepare($sql); $stmt->execute([$hid]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $hid = (int)($in['holiday_id'] ?? 0);
    $deptId = (int)($in['department_id'] ?? 0);
    if ($hid <= 0 || $deptId <= 0) Response::error('holiday_id and department_id are required', 422);
    $hc = $pdo->prepare('SELECT id FROM holidays WHERE id=? AND is_archived=0'); $hc->execute([$hid]);
    if (!$hc->fetch()) Response::error('Holiday not found', 404);
    $dc = $pdo->prepare('SELECT id FROM departments WHERE id=? AND is_archived=0'); $dc->execute([$deptId]);
    if (!$dc->fetch()) Response::error('Department not found', 404);
    $levelId = (int)($in['level_id'] ?? 0) ?: null;
    if ($levelId) {
        $lc = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND department_id=? AND is_archived=0');
        $lc->execute([$levelId, $deptId]);
        if (!$lc->fetch()) Response::error('Level does not belong to this department', 422);
    }
    $churchId = (int)($in['church_id'] ?? 0) ?: null;
    $withSeniors = !empty($in['with_seniors']) ? 1 : 0;
    $notes = isset($in['notes']) && $in['notes'] !== '' ? trim((string)$in['notes']) : null;
    $ins = $pdo->prepare('INSERT INTO serving_assignments (holiday_id, department_id, level_id, church_id, with_seniors, notes) VALUES (?,?,?,?,?,?)');
    $ins->execute([$hid, $deptId, $levelId, $churchId, $withSeniors, $notes]);
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('serving.create', 'serving_assignment', $id, ['holiday_id' => $hid, 'department_id' => $deptId]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT department_id FROM serving_assignments WHERE id=? AND is_archived=0'); $cur->execute([$id]);
    $row = $cur->fetch();
    if (!$row) Response::error('Assignment not found', 404);
    $deptId = (int)$row['department_id'];
    $fields = []; $params = [];
    if (array_key_exists('level_id', $in)) {
        $levelId = (int)$in['level_id'] ?: null;
        if ($levelId) {
            $lc = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND department_id=? AND is_archived=0');
            $lc->execute([$levelId, $deptId]);
            if (!$lc->fetch()) Response::error('Level does not belong to this department', 422);
        }
        $fields[]='level_id=?'; $params[]=$levelId;
    }
    if (array_key_exists('church_id', $in))    { $fields[]='church_id=?'; $params[]=(int)$in['church_id']?:null; }
    if (array_key_exists('with_seniors', $in)) { $fields[]='with_seniors=?'; $params[]=!empty($in['with_seniors'])?1:0; }
    if (array_key_exists('notes', $in))        { $fields[]='notes=?'; $params[]=$in['notes']!==''?trim((string)$in['notes']):null; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE serving_assignments SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('serving.update', 'serving_assignment', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE serving_assignments SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    \App\Audit::log('serving.remove', 'serving_assignment', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
