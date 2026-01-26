<?php
// api/auth/logout.php

use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

if (!Csrf::validate($config['app']['csrf_header'])) {
    Response::error('Invalid CSRF token', 403);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

Response::json(['message' => 'Logged out']);
