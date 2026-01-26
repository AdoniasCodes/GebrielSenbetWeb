<?php
// config/config.php
// cPanel-friendly config without external dependencies

return [
    'db' => [
        'host' => getenv('APP_DB_HOST') ?: 'localhost',
        'name' => getenv('APP_DB_NAME') ?: 'eagleerq_gebriel',
        'user' => getenv('APP_DB_USER') ?: 'eagleerq_gebriel',
        'pass' => getenv('APP_DB_PASS') ?: 'gebrieldbpw',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'env' => getenv('APP_ENV') ?: 'production',
        'csrf_header' => 'HTTP_X_CSRF_TOKEN',
        'session_name' => 'CHURCH_EDU_SESSID',
        // One-time setup token to initialize first admin user; set via env or update below
        'setup_token' => getenv('APP_SETUP_TOKEN') ?: '2wDRdMDeEv14D47u0UI8RLZ037CGkPR5',
        // Deployment token used by CI/CD (GitHub Actions) to trigger safe DB migrations
        'deploy_token' => getenv('APP_DEPLOY_TOKEN') ?: '2wDRdMDeEv14D47u0UI8RLZ037CGkPR5',
    ],
];
