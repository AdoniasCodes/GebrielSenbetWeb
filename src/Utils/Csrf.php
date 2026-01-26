<?php
// src/Utils/Csrf.php

namespace App\Utils;

class Csrf
{
    public static function ensureSession(string $sessionName = 'CHURCH_EDU_SESSID'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($sessionName);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function getToken(): string
    {
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate(string $headerName = 'HTTP_X_CSRF_TOKEN'): bool
    {
        self::ensureSession();
        $token = $_SERVER[$headerName] ?? '';
        return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
