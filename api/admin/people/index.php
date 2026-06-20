<?php
// api/admin/people/index.php — admin CRUD for canonical person records.
// A person is the unified human; department membership is managed separately
// (see api/admin/departments/members.php). Login linkage (user_id) is optional.
// GET    /api/admin/people?q=&church_id=&status=&department_id=&include_archived=
// POST   body: { first_name, last_name, baptismal_name?, date_of_birth?, gender?, phone?, address?, primary_church_id?, member_status?, joined_at?, last_communion_date?, notes? }
// PUT    body: { id, ...any of the above }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

const PERSON_STATUSES = ['active','inactive','alumni','prospective'];
const PERSON_GENDERS  = ['male','female'];

// Whitelisted writable columns and how to sanitize each value.
function _person_fields(array $in, bool $isCreate): array {
    $out = [];
    $str = fn($v) => ($v === '' || $v === null) ? null : trim((string)$v);
    if (array_key_exists('first_name', $in))         $out['first_name'] = $str($in['first_name']);
    if (array_key_exists('last_name', $in))          $out['last_name']  = $str($in['last_name']);
    if (array_key_exists('baptismal_name', $in))     $out['baptismal_name'] = $str($in['baptismal_name']);
    if (array_key_exists('date_of_birth', $in))      $out['date_of_birth'] = $str($in['date_of_birth']);
    if (array_key_exists('phone', $in))              $out['phone'] = $str($in['phone']);
    if (array_key_exists('address', $in))            $out['address'] = $str($in['address']);
    if (array_key_exists('joined_at', $in))          $out['joined_at'] = $str($in['joined_at']);
    if (array_key_exists('last_communion_date', $in))$out['last_communion_date'] = $str($in['last_communion_date']);
    if (array_key_exists('notes', $in))              $out['notes'] = $str($in['notes']);
    if (array_key_exists('gender', $in)) {
        $g = $str($in['gender']);
        $out['gender'] = in_array($g, PERSON_GENDERS, true) ? $g : null;
    }
    if (array_key_exists('member_status', $in)) {
        $s = $str($in['member_status']);
        $out['member_status'] = in_array($s, PERSON_STATUSES, true) ? $s : 'active';
    }
    if (array_key_exists('primary_church_id', $in)) {
        $c = (int)$in['primary_church_id'];
        $out['primary_church_id'] = $c > 0 ? $c : null;
    }
    return $out;
}

if ($method === 'GET') {
    $q          = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $churchId   = (int)($_GET['church_id'] ?? 0);
    $status     = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $deptId     = (int)($_GET['department_id'] ?? 0);
    $includeArc = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $sql = "SELECT p.id, p.first_name, p.last_name, p.baptismal_name, p.date_of_birth, p.gender,
                   p.phone, p.address, p.primary_church_id, ch.short_name AS church_name,
                   p.member_status, p.joined_at, p.last_communion_date, p.user_id, p.is_archived, p.created_at,
                   GROUP_CONCAT(DISTINCT COALESCE(d.name_am, d.name) ORDER BY d.sort_order SEPARATOR ', ') AS departments
            FROM people p
            LEFT JOIN churches ch ON ch.id = p.primary_church_id
            LEFT JOIN department_memberships dm ON dm.person_id = p.id AND dm.is_archived = 0
            LEFT JOIN departments d ON d.id = dm.department_id";
    $where = [];
    $params = [];
    if (!$includeArc) $where[] = 'p.is_archived = 0';
    if ($q !== '') {
        $where[] = "(CONCAT(p.first_name,' ',p.last_name) LIKE ? OR p.baptismal_name LIKE ? OR p.phone LIKE ?)";
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    if ($churchId > 0) { $where[] = 'p.primary_church_id = ?'; $params[] = $churchId; }
    if (in_array($status, PERSON_STATUSES, true)) { $where[] = 'p.member_status = ?'; $params[] = $status; }
    if ($deptId > 0) {
        $where[] = 'p.id IN (SELECT person_id FROM department_memberships WHERE department_id = ? AND is_archived = 0)';
        $params[] = $deptId;
    }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' GROUP BY p.id ORDER BY p.first_name, p.last_name LIMIT 1000';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $fields = _person_fields($in, true);
    if (empty($fields['first_name']) || empty($fields['last_name'])) {
        Response::error('first_name and last_name are required', 422);
    }
    $cols = array_keys($fields);
    $place = implode(', ', array_fill(0, count($cols), '?'));
    $sql = 'INSERT INTO people (' . implode(', ', $cols) . ') VALUES (' . $place . ')';
    $pdo->prepare($sql)->execute(array_values($fields));
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('person.create', 'person', $id, ['name' => $fields['first_name'] . ' ' . $fields['last_name']]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT id FROM people WHERE id=? AND is_archived=0');
    $cur->execute([$id]);
    if (!$cur->fetch()) Response::error('Person not found', 404);

    $fields = _person_fields($in, false);
    if (array_key_exists('first_name', $fields) && empty($fields['first_name'])) Response::error('first_name cannot be empty', 422);
    if (array_key_exists('last_name', $fields)  && empty($fields['last_name']))  Response::error('last_name cannot be empty', 422);
    if (!$fields) Response::error('Nothing to update', 422);

    $set = implode(', ', array_map(fn($c) => "$c=?", array_keys($fields)));
    $params = array_values($fields);
    $params[] = $id;
    $pdo->prepare("UPDATE people SET $set WHERE id=?")->execute($params);
    \App\Audit::log('person.update', 'person', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE people SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE department_memberships SET is_archived=1, archived_at=NOW() WHERE person_id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('person.archive', 'person', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
