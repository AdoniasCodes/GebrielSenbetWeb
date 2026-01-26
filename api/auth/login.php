<?php
// api/auth/login.php

use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();

Csrf::ensureSession($config['app']['session_name']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

// For login, allow without CSRF to establish session, but require CSRF afterwards
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    Response::error('Email and password are required', 422);
}

$db = new Database($config['db']);
$pdo = $db->pdo();

$sql = 'SELECT u.id, u.email, u.password_hash, u.role_id, u.is_archived, r.name AS role_name
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.email = ? LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash']) || (int)$user['is_archived'] === 1) {
    Response::error('Invalid credentials', 401);
}

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role_id'] = (int)$user['role_id'];
$_SESSION['role_name'] = $user['role_name'];

$csrf = Csrf::getToken();

Response::json([
    'message' => 'Logged in',
    'role' => $user['role_name'],
    'csrf_token' => $csrf,
]);
