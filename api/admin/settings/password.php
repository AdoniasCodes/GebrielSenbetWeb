<?php
// api/admin/settings/password.php — change own password

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') Response::error('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$current = (string)($input['current_password'] ?? '');
$next    = (string)($input['new_password'] ?? '');

if ($current === '' || $next === '') Response::error('current_password and new_password are required', 422);
if (strlen($next) < 8) Response::error('New password must be at least 8 characters', 422);

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) Response::error('Not authenticated', 401);

$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE id=? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) Response::error('User not found', 404);

if (!password_verify($current, $user['password_hash'])) {
    Response::error('Current password is incorrect', 403);
}

$newHash = password_hash($next, PASSWORD_DEFAULT);
$upd = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
$upd->execute([$newHash, $userId]);

\App\Audit::log('settings.password_change', 'user', $userId);
Response::json(['ok' => true]);
