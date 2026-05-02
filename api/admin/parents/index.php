<?php
// api/admin/parents/index.php — admin CRUD for parent users + their student links.
// GET    /api/admin/parents?include_archived=
// POST   body: { email, password?, full_name, phone?, student_ids?: int[] }
// PUT    body: { id, email?, full_name?, phone?, password?, student_ids?: int[] (replaces links) }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;
use App\Utils\Password;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

function _parent_role_id(\PDO $pdo): int {
    $s = $pdo->prepare("SELECT id FROM roles WHERE name='parent' LIMIT 1");
    $s->execute();
    $r = $s->fetch();
    if (!$r) Response::error('Parent role not provisioned. Run db migration 009.', 500);
    return (int)$r['id'];
}

function _replace_links(\PDO $pdo, int $userId, array $studentIds): void {
    $pdo->prepare('UPDATE student_guardians SET is_archived=1, archived_at=NOW() WHERE user_id=? AND is_archived=0')
        ->execute([$userId]);
    if (!$studentIds) return;
    $ins = $pdo->prepare('INSERT INTO student_guardians (user_id, student_id, is_primary)
                          VALUES (?, ?, 0)
                          ON DUPLICATE KEY UPDATE is_archived=0, archived_at=NULL');
    foreach (array_unique($studentIds) as $sid) {
        $sid = (int)$sid;
        if ($sid > 0) $ins->execute([$userId, $sid]);
    }
}

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = "SELECT u.id, u.email, u.is_archived, u.created_at,
                   COUNT(DISTINCT sg.student_id) AS linked_students,
                   GROUP_CONCAT(DISTINCT CONCAT(s.first_name,' ',s.last_name) ORDER BY s.first_name SEPARATOR ', ') AS children_names,
                   GROUP_CONCAT(DISTINCT s.id ORDER BY s.id) AS children_ids
            FROM users u
            JOIN roles r ON r.id=u.role_id AND r.name='parent'
            LEFT JOIN student_guardians sg ON sg.user_id=u.id AND sg.is_archived=0
            LEFT JOIN students s ON s.id=sg.student_id";
    if (!$includeArchived) $sql .= ' WHERE u.is_archived=0';
    $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';
    $rows = $pdo->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['linked_students'] = (int)$r['linked_students'];
        $r['children_ids'] = $r['children_ids'] ? array_map('intval', explode(',', $r['children_ids'])) : [];
    }
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim((string)($input['email'] ?? ''));
    $password = isset($input['password']) && $input['password'] !== '' ? (string)$input['password'] : Password::generate(12);
    $studentIds = is_array($input['student_ids'] ?? null) ? $input['student_ids'] : [];
    if ($email === '') Response::error('email is required', 422);

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) { $pdo->rollBack(); Response::error('Email already exists', 409); }
        $roleId = _parent_role_id($pdo);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)');
        $ins->execute([$email, $hash, $roleId]);
        $userId = (int)$pdo->lastInsertId();
        _replace_links($pdo, $userId, $studentIds);
        $pdo->commit();
        \App\Audit::log('parent.create', 'user', $userId, ['email' => $email, 'student_count' => count($studentIds)]);
        Response::json(['ok' => true, 'id' => $userId, 'generated_password' => $password], 201);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to create parent', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    try {
        $pdo->beginTransaction();
        $u = $pdo->prepare("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND r.name='parent'");
        $u->execute([$id]);
        if (!$u->fetch()) { $pdo->rollBack(); Response::error('Parent not found', 404); }

        if (isset($input['email'])) {
            $email = trim((string)$input['email']);
            if ($email === '') { $pdo->rollBack(); Response::error('email cannot be empty', 422); }
            $du = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
            $du->execute([$email, $id]);
            if ($du->fetch()) { $pdo->rollBack(); Response::error('Email already in use', 409); }
            $pdo->prepare('UPDATE users SET email=? WHERE id=?')->execute([$email, $id]);
        }
        if (!empty($input['password'])) {
            $hash = password_hash((string)$input['password'], PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
        }
        if (array_key_exists('student_ids', $input) && is_array($input['student_ids'])) {
            _replace_links($pdo, $id, $input['student_ids']);
        }
        $pdo->commit();
        \App\Audit::log('parent.update', 'user', $id);
        Response::json(['ok' => true]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to update parent', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE users SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE student_guardians SET is_archived=1, archived_at=NOW() WHERE user_id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('parent.archive', 'user', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
