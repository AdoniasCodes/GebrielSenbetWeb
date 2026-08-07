<?php
// config/config.php
// cPanel-friendly config without external dependencies

return [
    'db' => [
        'host' => getenv('APP_DB_HOST') ?: 'localhost',
        'name' => getenv('APP_DB_NAME') ?: 'mekanefh_RealDb',
        'user' => getenv('APP_DB_USER') ?: 'mekanefh_gebriel',
        'pass' => getenv('APP_DB_PASS') ?: 'Panda2022!!',
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
    // Official social accounts. Single source of truth for the landing page
    // links and for the YouTube channel feed reader (api/social/youtube.php).
    'social' => [
        'youtube_handle'     => '@MekaneSelam-m3j',
        'youtube_url'        => 'https://www.youtube.com/@MekaneSelam-m3j',
        // Channel UC id, needed for the public Atom feed. Find it in the channel
        // page source under "externalId" if the handle ever changes.
        'youtube_channel_id' => 'UC-Ybr6jVi_zCJ2wPdWv8H3A',
        'tiktok_handle'      => '@mekaneselamm',
        'tiktok_url'         => 'https://www.tiktok.com/@mekaneselamm',
    ],
];
