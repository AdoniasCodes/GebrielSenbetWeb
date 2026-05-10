<?php
// api/videos/index.php — public read-only feed of curated video embeds.
// Filter by section to scope (e.g. 'tiktok_latest', 'youtube_latest').

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$section = isset($_GET['section']) ? trim((string)$_GET['section']) : '';
$limit   = max(1, min(20, (int)($_GET['limit'] ?? 6)));

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

$sql = "SELECT id, platform, section, video_url, title, caption, sort_order, created_at
        FROM video_embeds
        WHERE is_archived=0 AND is_active=1";
$params = [];
if ($section !== '') { $sql .= ' AND section=?'; $params[] = $section; }
$sql .= ' ORDER BY sort_order ASC, created_at DESC LIMIT ' . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
