<?php
// api/admin/videos/index.php — admin CRUD for video_embeds.
// GET    /api/admin/videos?section=&include_archived=
// POST   body: { section, video_url, title?, caption?, sort_order?, is_active? }
// PUT    body: { id, video_url?, title?, caption?, section?, sort_order?, is_active? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

function _detect_platform(string $url): ?string {
    $u = strtolower($url);
    if (str_contains($u, 'tiktok.com'))   return 'tiktok';
    if (str_contains($u, 'facebook.com') || str_contains($u, 'fb.watch')) return 'facebook';
    if (str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be'))  return 'youtube';
    return null;
}

function _can_embed(string $platform, string $url): bool {
    if ($platform === 'tiktok')   return (bool)preg_match('#/video/(\d+)#', $url);
    if ($platform === 'youtube')  return str_contains($url, 'youtu.be/') || str_contains($url, 'watch?v=') || str_contains($url, '/shorts/') || str_contains($url, '/embed/');
    if ($platform === 'facebook') return true;
    return false;
}

if ($method === 'GET') {
    $section = isset($_GET['section']) ? trim((string)$_GET['section']) : '';
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = 'SELECT id, platform, section, video_url, title, caption, sort_order, is_active, is_archived, created_at, updated_at
            FROM video_embeds WHERE 1=1';
    $params = [];
    if ($section !== '') { $sql .= ' AND section=?'; $params[] = $section; }
    if (!$includeArchived) $sql .= ' AND is_archived=0';
    $sql .= ' ORDER BY section, sort_order ASC, created_at DESC LIMIT 500';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $section = trim((string)($input['section'] ?? ''));
    $url     = trim((string)($input['video_url'] ?? ''));
    $title   = isset($input['title']) ? trim((string)$input['title']) : null;
    $caption = isset($input['caption']) ? trim((string)$input['caption']) : null;
    $sort    = isset($input['sort_order']) ? (int)$input['sort_order'] : 0;
    $active  = array_key_exists('is_active', $input) ? (!empty($input['is_active']) ? 1 : 0) : 1;

    if ($section === '' || $url === '') Response::error('section and video_url are required', 422);
    $platform = _detect_platform($url);
    if (!$platform) Response::error('Must be a TikTok, YouTube, or Facebook URL', 422);
    if (!_can_embed($platform, $url)) Response::error('Could not parse this URL into an embed (TikTok needs /video/<id>; YouTube needs full link)', 422);

    $ins = $pdo->prepare('INSERT INTO video_embeds (platform, section, video_url, title, caption, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$platform, $section, $url, $title ?: null, $caption ?: null, $sort, $active]);
    $newId = (int)$pdo->lastInsertId();
    \App\Audit::log('video.create', 'video_embed', $newId, ['platform' => $platform, 'section' => $section]);
    Response::json(['ok' => true, 'id' => $newId, 'platform' => $platform], 201);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $row = $pdo->prepare('SELECT * FROM video_embeds WHERE id=? AND is_archived=0');
    $row->execute([$id]);
    $cur = $row->fetch();
    if (!$cur) Response::error('Video not found', 404);

    $fields = []; $params = [];
    if (isset($input['video_url'])) {
        $url = trim((string)$input['video_url']);
        if ($url === '') Response::error('video_url cannot be empty', 422);
        $platform = _detect_platform($url);
        if (!$platform) Response::error('Must be a TikTok, YouTube, or Facebook URL', 422);
        if (!_can_embed($platform, $url)) Response::error('Could not parse this URL into an embed', 422);
        $fields[] = 'video_url=?'; $params[] = $url;
        $fields[] = 'platform=?';  $params[] = $platform;
    }
    if (array_key_exists('title', $input))   { $fields[] = 'title=?';   $params[] = $input['title']   !== '' ? trim((string)$input['title']) : null; }
    if (array_key_exists('caption', $input)) { $fields[] = 'caption=?'; $params[] = $input['caption'] !== '' ? trim((string)$input['caption']) : null; }
    if (isset($input['section']))    { $fields[] = 'section=?';    $params[] = trim((string)$input['section']); }
    if (isset($input['sort_order'])) { $fields[] = 'sort_order=?'; $params[] = (int)$input['sort_order']; }
    if (array_key_exists('is_active', $input)) { $fields[] = 'is_active=?'; $params[] = !empty($input['is_active']) ? 1 : 0; }

    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE video_embeds SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('video.update', 'video_embed', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE video_embeds SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('video.archive', 'video_embed', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
