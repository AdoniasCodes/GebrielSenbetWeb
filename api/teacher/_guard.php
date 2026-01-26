<?php
// api/teacher/_guard.php
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? '') !== 'teacher') {
    Response::error('Forbidden', 403);
}

function require_csrf_for_write(): void {
    $cfg = app_config();
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH','DELETE'], true)) {
        return;
    }
    if (!\App\Utils\Csrf::validate($cfg['app']['csrf_header'])) {
        \App\Utils\Response::error('Invalid CSRF token', 403);
    }
}
