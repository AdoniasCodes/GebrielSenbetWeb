<?php
// api/parent/announcements/index.php — announcements relevant to the parent.
// Includes: any announcement targeted at role=parent, and any is_public announcement.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

$sql = "SELECT id, title, message, target_type, target_payload, is_public, created_at
        FROM notifications
        WHERE is_archived=0
          AND (
            is_public=1
            OR (target_type='role' AND JSON_EXTRACT(target_payload, '$.role') = 'parent')
          )
        ORDER BY created_at DESC
        LIMIT 100";
$rows = $pdo->query($sql)->fetchAll();
foreach ($rows as &$r) {
    $r['target_payload'] = $r['target_payload'] ? json_decode($r['target_payload'], true) : null;
}
Response::json(['data' => $rows]);
