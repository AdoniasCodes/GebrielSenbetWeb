<?php
// api/announcements/index.php — public read-only feed of announcements
// flagged as is_public by an admin. No auth required.

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$sql = "SELECT id, title, message, created_at
        FROM notifications
        WHERE is_archived = 0 AND is_public = 1
        ORDER BY created_at DESC
        LIMIT $limit";
$rows = $pdo->query($sql)->fetchAll();

Response::json(['data' => $rows]);
