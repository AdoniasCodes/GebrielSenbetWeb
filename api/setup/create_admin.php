<?php
// api/setup/create_admin.php
// One-time endpoint to create initial admin. Guarded by setup token from config.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
$setupToken = $config['app']['setup_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

$provided = $_SERVER['HTTP_X_SETUP_TOKEN'] ?? '';
if ($setupToken === '' || $setupToken === 'CHANGE_ME_SETUP_TOKEN' || !hash_equals($setupToken, $provided)) {
    Response::error('Forbidden', 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');
$first = trim($input['first_name'] ?? '');
$last = trim($input['last_name'] ?? '');

if ($email === '' || $password === '' || $first === '' || $last === '') {
    Response::error('Missing required fields', 422);
}

$db = new Database($config['db']);
$pdo = $db->pdo();

try {
    $pdo->beginTransaction();

    // Ensure role id for admin
    $ridStmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
    $ridStmt->execute(['admin']);
    $role = $ridStmt->fetch();
    if (!$role) { Response::error('Roles not seeded', 500); }

    // Check if user exists
    $uStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $uStmt->execute([$email]);
    if ($uStmt->fetch()) {
        $pdo->rollBack();
        Response::error('User already exists', 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insU = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)');
    $insU->execute([$email, $hash, (int)$role['id']]);
    $userId = (int)$pdo->lastInsertId();

    // Admin is not teacher/student record by default; just a user

    $pdo->commit();
    Response::json(['message' => 'Admin user created', 'user_id' => $userId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Response::error('Setup failed', 500);
}
