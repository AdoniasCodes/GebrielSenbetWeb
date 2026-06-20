<?php
// api/admin/people/login.php — grant / manage a login account for a person.
// A person (people row) optionally links to a users row (people.user_id). This
// lets department heads (role 'staff'), and others, actually sign in.
// GET    ?person_id=        → { has_login, email, role }
// POST   { person_id, email, role?, password? }   → creates the login (role default 'staff')
// PUT    { person_id, role?, password? }           → change role / reset password
// DELETE { person_id }                             → revoke login (archives user, unlinks)

use App\Database;
use App\Utils\Response;
use App\Utils\Password;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

const GRANTABLE_ROLES = ['staff','teacher','parent','admin','student'];

function _role_id(\PDO $pdo, string $name): int {
    $s = $pdo->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');
    $s->execute([$name]);
    $r = $s->fetch();
    if (!$r) Response::error("Role '$name' not provisioned", 500);
    return (int)$r['id'];
}
function _person(\PDO $pdo, int $id): ?array {
    $s = $pdo->prepare('SELECT id, first_name, last_name, user_id FROM people WHERE id=? AND is_archived=0');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

if ($method === 'GET') {
    $personId = (int)($_GET['person_id'] ?? 0);
    if ($personId <= 0) Response::error('person_id is required', 422);
    $p = _person($pdo, $personId);
    if (!$p) Response::error('Person not found', 404);
    if (!$p['user_id']) Response::json(['has_login' => false]);
    $u = $pdo->prepare('SELECT u.email, r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_archived=0');
    $u->execute([(int)$p['user_id']]);
    $row = $u->fetch();
    Response::json(['has_login' => (bool)$row, 'email' => $row['email'] ?? null, 'role' => $row['role'] ?? null]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $personId = (int)($in['person_id'] ?? 0);
    $email = trim((string)($in['email'] ?? ''));
    $role  = trim((string)($in['role'] ?? 'staff'));
    if ($personId <= 0 || $email === '') Response::error('person_id and email are required', 422);
    if (!in_array($role, GRANTABLE_ROLES, true)) Response::error('Invalid role', 422);
    $p = _person($pdo, $personId);
    if (!$p) Response::error('Person not found', 404);
    if ($p['user_id']) Response::error('This person already has a login', 409);
    $password = isset($in['password']) && $in['password'] !== '' ? (string)$in['password'] : Password::generate(12);

    try {
        $pdo->beginTransaction();
        $dup = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $dup->execute([$email]);
        if ($dup->fetch()) { $pdo->rollBack(); Response::error('Email already in use', 409); }
        $ins = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?,?,?)');
        $ins->execute([$email, password_hash($password, PASSWORD_DEFAULT), _role_id($pdo, $role)]);
        $uid = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE people SET user_id=? WHERE id=?')->execute([$uid, $personId]);
        $pdo->commit();
        \App\Audit::log('person.login.grant', 'person', $personId, ['role' => $role, 'email' => $email]);
        Response::json(['ok' => true, 'user_id' => $uid, 'generated_password' => $password], 201);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to grant login', 500);
    }
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $personId = (int)($in['person_id'] ?? 0);
    if ($personId <= 0) Response::error('person_id is required', 422);
    $p = _person($pdo, $personId);
    if (!$p || !$p['user_id']) Response::error('This person has no login', 404);
    $uid = (int)$p['user_id'];

    $fields = []; $params = [];
    if (isset($in['role'])) {
        $role = trim((string)$in['role']);
        if (!in_array($role, GRANTABLE_ROLES, true)) Response::error('Invalid role', 422);
        $fields[] = 'role_id=?'; $params[] = _role_id($pdo, $role);
    }
    $newPw = null;
    if (!empty($in['password'])) { $fields[] = 'password_hash=?'; $params[] = password_hash((string)$in['password'], PASSWORD_DEFAULT); }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $uid;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('person.login.update', 'person', $personId);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $personId = (int)($in['person_id'] ?? 0);
    if ($personId <= 0) Response::error('person_id is required', 422);
    $p = _person($pdo, $personId);
    if (!$p || !$p['user_id']) Response::error('This person has no login', 404);
    $uid = (int)$p['user_id'];
    $pdo->prepare('UPDATE users SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$uid]);
    $pdo->prepare('UPDATE people SET user_id=NULL WHERE id=?')->execute([$personId]);
    \App\Audit::log('person.login.revoke', 'person', $personId);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
