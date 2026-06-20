<?php
// api/admin/settings/options.php — generic key/value app settings.
// GET → { settings: { key: value, ... } }
// PUT body: { key, value }   → upsert a single setting

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

// Allowed keys + a validator for each (keeps arbitrary writes out).
$ALLOWED = [
    'serving_eligibility_min_attendance' => function ($v) {
        $n = (int)$v;
        return ($n >= 0 && $n <= 100) ? (string)$n : null;
    },
];

if ($method === 'GET') {
    $out = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM app_settings') as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    Response::json(['settings' => $out]);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $key = trim((string)($in['key'] ?? ''));
    if (!isset($ALLOWED[$key])) Response::error('Unknown setting', 422);
    $value = $ALLOWED[$key]((string)($in['value'] ?? ''));
    if ($value === null) Response::error('Invalid value for this setting', 422);
    $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
        ->execute([$key, $value]);
    \App\Audit::log('settings.update', 'app_setting', null, ['key' => $key, 'value' => $value]);
    Response::json(['ok' => true, 'key' => $key, 'value' => $value]);
}

Response::error('Method not allowed', 405);
