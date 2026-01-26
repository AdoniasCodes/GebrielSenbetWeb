<?php
// bootstrap.php - central bootstrap for autoloading and config

// Load config
$APP_CONFIG = require __DIR__ . '/config/config.php';
$GLOBALS['APP_CONFIG'] = $APP_CONFIG;

// Simple PSR-4 autoloader for namespace App\
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

if (!function_exists('app_config')) {
    function app_config(): array {
        return $GLOBALS['APP_CONFIG'] ?? [];
    }
}

// Initialize secure session
if (session_status() !== PHP_SESSION_ACTIVE) {
    $cfg = app_config();
    $sessionName = $cfg['app']['session_name'] ?? 'CHURCH_EDU_SESSID';
    session_name($sessionName);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}
