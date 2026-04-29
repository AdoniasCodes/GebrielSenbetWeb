<?php
// api/admin/posts/index.php — blog post CRUD (without file upload — see upload.php)
// GET    /api/admin/posts?include_archived=&q=
// POST   body: { title, content }
// PUT    body: { id, title?, content? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $q = trim((string)($_GET['q'] ?? ''));

    $sql = "SELECT p.id, p.title, p.content, p.author_user_id, p.is_archived, p.created_at, p.updated_at,
                   u.email AS author_email
            FROM blog_posts p
            LEFT JOIN users u ON u.id=p.author_user_id
            WHERE 1=1";
    $params = [];
    if (!$includeArchived) $sql .= ' AND p.is_archived=0';
    if ($q !== '') { $sql .= ' AND (p.title LIKE ? OR p.content LIKE ?)'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; }
    $sql .= ' ORDER BY p.created_at DESC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    if ($rows) {
        $ids = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $aq = $pdo->prepare("SELECT post_id, id, file_path, original_name, mime_type, file_size FROM blog_attachments WHERE post_id IN ($placeholders) ORDER BY id ASC");
        $aq->execute($ids);
        $byPost = [];
        foreach ($aq->fetchAll() as $a) { $byPost[$a['post_id']][] = $a; }
        foreach ($rows as &$r) { $r['attachments'] = $byPost[$r['id']] ?? []; }
    }
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $title = trim($input['title'] ?? '');
    $content = (string)($input['content'] ?? '');
    if ($title === '' || $content === '') Response::error('title and content are required', 422);
    $authorId = (int)($_SESSION['user_id'] ?? 0);
    if ($authorId <= 0) Response::error('Not authenticated', 401);

    $stmt = $pdo->prepare('INSERT INTO blog_posts (title, content, author_user_id) VALUES (?, ?, ?)');
    $stmt->execute([$title, $content, $authorId]);
    Response::json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $fields = []; $params = [];
    if (isset($input['title']))   { $fields[]='title=?';   $params[] = trim((string)$input['title']); }
    if (isset($input['content'])) { $fields[]='content=?'; $params[] = (string)$input['content']; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $stmt = $pdo->prepare('UPDATE blog_posts SET '.implode(',', $fields).' WHERE id=?');
    $stmt->execute($params);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare('UPDATE blog_posts SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
