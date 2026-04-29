<?php
// api/posts/index.php — public read-only blog feed (no auth required)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$sql = "SELECT p.id, p.title, p.content, p.created_at,
               u.email AS author_email
        FROM blog_posts p
        LEFT JOIN users u ON u.id=p.author_user_id
        WHERE p.is_archived=0
        ORDER BY p.created_at DESC
        LIMIT $limit";
$rows = $pdo->query($sql)->fetchAll();

// Strip author email — for privacy, only show first letter
foreach ($rows as &$r) {
    $r['author'] = $r['author_email'] ? strtok($r['author_email'], '@') : 'Staff';
    unset($r['author_email']);
}

Response::json(['data' => $rows]);
