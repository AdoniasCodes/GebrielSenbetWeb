<?php
// api/admin/users/index.php
use App\Database;
use App\Utils\Response;
use App\Utils\Password;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();

$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $role = isset($_GET['role']) ? trim((string)$_GET['role']) : '';
    $params = [];
    $sql = 'SELECT u.id, u.email, r.name AS role, u.is_archived, u.archived_at, u.created_at, u.updated_at
            FROM users u JOIN roles r ON r.id = u.role_id';
    $where = [];
    if ($role !== '') { $where[] = 'r.name = ?'; $params[] = $role; }
    if (!$includeArchived) { $where[] = 'u.is_archived = 0'; }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY u.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    // fetch minimal profile data per role
    foreach ($users as &$u) {
        if ($u['role'] === 'student') {
            $ps = $pdo->prepare('SELECT first_name, last_name, guardian_name, phone FROM students WHERE user_id = ?');
            $ps->execute([$u['id']]);
            $u['profile'] = $ps->fetch() ?: null;
        } elseif ($u['role'] === 'teacher') {
            $pt = $pdo->prepare('SELECT first_name, last_name, phone, bio FROM teachers WHERE user_id = ?');
            $pt->execute([$u['id']]);
            $u['profile'] = $pt->fetch() ?: null;
        } else {
            $u['profile'] = null;
        }
    }
    Response::json(['data' => $users]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $roleName = trim($input['role'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = isset($input['password']) && $input['password'] !== '' ? (string)$input['password'] : Password::generate(12);
    if ($roleName === '' || $email === '') { Response::error('role and email are required', 422); }

    try {
        $pdo->beginTransaction();
        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $roleStmt->execute([$roleName]);
        $role = $roleStmt->fetch();
        if (!$role) { $pdo->rollBack(); Response::error('Invalid role', 422); }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) { $pdo->rollBack(); Response::error('Email already exists', 409); }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)');
        $ins->execute([$email, $hash, (int)$role['id']]);
        $userId = (int)$pdo->lastInsertId();

        if ($roleName === 'student') {
            $first = trim($input['first_name'] ?? '');
            $last = trim($input['last_name'] ?? '');
            $guardian = trim($input['guardian_name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $address = trim($input['address'] ?? '');
            if ($first === '' || $last === '') { $pdo->rollBack(); Response::error('first_name and last_name required for student', 422); }
            $is = $pdo->prepare('INSERT INTO students (user_id, first_name, last_name, guardian_name, phone, address) VALUES (?, ?, ?, ?, ?, ?)');
            $is->execute([$userId, $first, $last, $guardian, $phone, $address]);
        } elseif ($roleName === 'teacher') {
            $first = trim($input['first_name'] ?? '');
            $last = trim($input['last_name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $bio = isset($input['bio']) ? (string)$input['bio'] : null;
            if ($first === '' || $last === '') { $pdo->rollBack(); Response::error('first_name and last_name required for teacher', 422); }
            $it = $pdo->prepare('INSERT INTO teachers (user_id, first_name, last_name, phone, bio) VALUES (?, ?, ?, ?, ?)');
            $it->execute([$userId, $first, $last, $phone, $bio]);
        }

        $pdo->commit();
        Response::json(['message' => 'User created', 'user_id' => $userId, 'generated_password' => $password], 201);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to create user', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int)($input['id'] ?? 0);
    if ($userId <= 0) { Response::error('id is required', 422); }

    // Allow updating email and profile fields depending on role; not role changes
    try {
        $pdo->beginTransaction();
        $ur = $pdo->prepare('SELECT u.id, u.role_id, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
        $ur->execute([$userId]);
        $user = $ur->fetch();
        if (!$user) { $pdo->rollBack(); Response::error('User not found', 404); }

        if (isset($input['email'])) {
            $email = trim((string)$input['email']);
            if ($email === '') { $pdo->rollBack(); Response::error('email cannot be empty', 422); }
            $du = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $du->execute([$email, $userId]);
            if ($du->fetch()) { $pdo->rollBack(); Response::error('Email already in use', 409); }
            $ue = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
            $ue->execute([$email, $userId]);
        }

        if ($user['role_name'] === 'student') {
            $fields = [];$params=[];
            if (isset($input['first_name'])) { $fields[]='first_name = ?'; $params[] = trim((string)$input['first_name']); }
            if (isset($input['last_name'])) { $fields[]='last_name = ?'; $params[] = trim((string)$input['last_name']); }
            if (isset($input['guardian_name'])) { $fields[]='guardian_name = ?'; $params[] = trim((string)$input['guardian_name']); }
            if (isset($input['phone'])) { $fields[]='phone = ?'; $params[] = trim((string)$input['phone']); }
            if (isset($input['address'])) { $fields[]='address = ?'; $params[] = trim((string)$input['address']); }
            if ($fields) {
                $params[] = $userId;
                $us = $pdo->prepare('UPDATE students SET ' . implode(', ', $fields) . ' WHERE user_id = ?');
                $us->execute($params);
            }
        } elseif ($user['role_name'] === 'teacher') {
            $fields = [];$params=[];
            if (isset($input['first_name'])) { $fields[]='first_name = ?'; $params[] = trim((string)$input['first_name']); }
            if (isset($input['last_name'])) { $fields[]='last_name = ?'; $params[] = trim((string)$input['last_name']); }
            if (isset($input['phone'])) { $fields[]='phone = ?'; $params[] = trim((string)$input['phone']); }
            if (array_key_exists('bio', $input)) { $fields[]='bio = ?'; $params[] = is_null($input['bio']) ? null : (string)$input['bio']; }
            if ($fields) {
                $params[] = $userId;
                $ut = $pdo->prepare('UPDATE teachers SET ' . implode(', ', $fields) . ' WHERE user_id = ?');
                $ut->execute($params);
            }
        }

        $pdo->commit();
        Response::json(['message' => 'User updated']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to update user', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int)($input['id'] ?? 0);
    if ($userId <= 0) { Response::error('id is required', 422); }
    try {
        $pdo->beginTransaction();
        $ur = $pdo->prepare('SELECT u.id, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
        $ur->execute([$userId]);
        $u = $ur->fetch();
        if (!$u) { $pdo->rollBack(); Response::error('User not found', 404); }

        $uu = $pdo->prepare('UPDATE users SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
        $uu->execute([$userId]);
        if ($uu->rowCount() === 0) { $pdo->rollBack(); Response::error('User already archived', 409); }

        if ($u['role_name'] === 'student') {
            $ps = $pdo->prepare('UPDATE students SET is_archived = 1, archived_at = NOW() WHERE user_id = ? AND is_archived = 0');
            $ps->execute([$userId]);
        } elseif ($u['role_name'] === 'teacher') {
            $pt = $pdo->prepare('UPDATE teachers SET is_archived = 1, archived_at = NOW() WHERE user_id = ? AND is_archived = 0');
            $pt->execute([$userId]);
        }

        $pdo->commit();
        Response::json(['message' => 'User archived']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to archive user', 500);
    }
}

Response::error('Method Not Allowed', 405);
