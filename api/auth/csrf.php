<?php
// api/auth/csrf.php - returns current CSRF token and auth status
use App\Utils\Csrf;
use App\Utils\Response;
use App\Database;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed', 405);
}

$authed = isset($_SESSION['user_id']);
$role = $_SESSION['role_name'] ?? null;
$csrf = Csrf::getToken();

Response::json([
    'csrf_token' => $csrf,
    'authenticated' => $authed,
    'role' => $role,
]);
