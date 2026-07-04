<?php
// api/admin/users/index.php
use App\Database;
use App\Utils\Response;
use App\Utils\Password;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../person_accounts_lib.php';
require_csrf_for_write();

// Keep people.first_name/last_name in sync with a teacher/student rename, but
// only for the fields actually provided and only if a linked person exists.
function sync_person_name(\PDO $pdo, int $userId, array $input): void {
    $fields = []; $params = [];
    if (isset($input['first_name'])) { $fields[] = 'first_name = ?'; $params[] = trim((string)$input['first_name']); }
    if (isset($input['last_name']))  { $fields[] = 'last_name = ?';  $params[] = trim((string)$input['last_name']); }
    if (!$fields) { return; }
    $params[] = $userId;
    $up = $pdo->prepare('UPDATE people SET ' . implode(', ', $fields) . ' WHERE user_id = ?');
    $up->execute($params);
}

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
            $ps = $pdo->prepare('SELECT id AS student_id, first_name, last_name, guardian_name, phone, address, date_of_birth FROM students WHERE user_id = ?');
            $ps->execute([$u['id']]);
            $u['profile'] = $ps->fetch() ?: null;
        } elseif ($u['role'] === 'teacher') {
            $pt = $pdo->prepare('SELECT id AS teacher_id, first_name, last_name, phone, bio FROM teachers WHERE user_id = ?');
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
    if ($roleName === '' || $email === '') { Response::error('role and email are required', 422); }

    // Optional: assign a teacher/student to one or more departments at creation.
    $deptIds = [];
    if (isset($input['department_ids']) && is_array($input['department_ids'])) {
        foreach ($input['department_ids'] as $d) { $d = (int)$d; if ($d > 0) $deptIds[$d] = $d; }
        $deptIds = array_values($deptIds);
    }
    $adminUserId = (int)($_SESSION['user_id'] ?? 0);
    $adminRoleId = (int)($_SESSION['role_id'] ?? 0);

    try {
        $pdo->beginTransaction();
        // create_person_account() creates users (+ people + role profile for
        // teacher/student). Shared with the dept-head flow via person_accounts_lib.
        $acct = create_person_account($pdo, $input); // throws PersonAccountError
        $userId   = (int)$acct['user_id'];
        $personId = $acct['person_id'] !== null ? (int)$acct['person_id'] : null;
        $password = $acct['password'];

        // Attach departments (memberships) + notify the person if a teacher.
        $assignedLabels = [];
        if ($deptIds && $personId !== null) {
            $dsel = $pdo->prepare('SELECT id, name, name_am FROM departments WHERE id = ? AND is_archived = 0');
            $mins = $pdo->prepare('INSERT INTO department_memberships (person_id, department_id, assigned_by_user_id, is_head, joined_at) VALUES (?,?,?,0,NULL)');
            foreach ($deptIds as $did) {
                $dsel->execute([$did]);
                $drow = $dsel->fetch();
                if (!$drow) { throw new PersonAccountError('Department not found: ' . $did, 422); }
                $mins->execute([$personId, $did, $adminUserId ?: null]);
                $assignedLabels[] = department_display_name($drow);
            }
            // Notification is teacher-only (per product rule).
            if ($roleName === 'teacher' && $assignedLabels) {
                notify_department_assignment($pdo, $userId, $assignedLabels, $adminUserId, $adminRoleId);
            }
        }

        $pdo->commit();
        \App\Audit::log('user.create', 'user', $userId, ['role' => $roleName, 'email' => $email, 'department_ids' => $deptIds]);
        Response::json(['message' => 'User created', 'user_id' => $userId, 'generated_password' => $password, 'department_ids' => $deptIds], 201);
    } catch (PersonAccountError $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error($e->getMessage(), $e->status);
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

        // Allow unarchive via { is_archived: 0 }
        if (array_key_exists('is_archived', $input) && (int)$input['is_archived'] === 0) {
            $uu = $pdo->prepare('UPDATE users SET is_archived = 0, archived_at = NULL WHERE id = ?');
            $uu->execute([$userId]);
            if ($user['role_name'] === 'student') {
                $ps = $pdo->prepare('UPDATE students SET is_archived = 0, archived_at = NULL WHERE user_id = ?');
                $ps->execute([$userId]);
            } elseif ($user['role_name'] === 'teacher') {
                $pt = $pdo->prepare('UPDATE teachers SET is_archived = 0, archived_at = NULL WHERE user_id = ?');
                $pt->execute([$userId]);
            }
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
            // Keep the canonical person's name in sync with the student row.
            sync_person_name($pdo, $userId, $input);
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
            // Keep the canonical person's name in sync with the teacher row.
            sync_person_name($pdo, $userId, $input);
        }

        $pdo->commit();
        \App\Audit::log('user.update', 'user', $userId, ['role' => $user['role_name']]);
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
        \App\Audit::log('user.archive', 'user', $userId, ['role' => $u['role_name']]);
        Response::json(['message' => 'User archived']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to archive user', 500);
    }
}

Response::error('Method Not Allowed', 405);
