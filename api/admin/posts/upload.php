<?php
// api/admin/posts/upload.php — upload an attachment to an existing post
// multipart/form-data: post_id, file
// Stores under public/uploads/posts/<random>.<ext> ; records mime, size, original name in blog_attachments

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Method not allowed', 405);

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) Response::error('post_id is required', 422);
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    Response::error('No file uploaded or upload error', 400);
}
$file = $_FILES['file'];
if ($file['size'] > 10 * 1024 * 1024) Response::error('File too large (max 10 MB)', 413);

$allowed = [
    'image/png'   => 'png',
    'image/jpeg'  => 'jpg',
    'image/webp'  => 'webp',
    'image/gif'   => 'gif',
    'application/pdf' => 'pdf',
    'text/plain'  => 'txt',
];

$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: ($file['type'] ?? '');
if (!isset($allowed[$mime])) Response::error('Unsupported file type: ' . htmlspecialchars($mime), 415);
$ext = $allowed[$mime];

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE id=? AND is_archived=0');
$stmt->execute([$postId]);
if (!$stmt->fetch()) Response::error('Post not found', 404);

$dir = __DIR__ . '/../../../public/uploads/posts';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) Response::error('Failed to prepare upload directory', 500);

$fname = bin2hex(random_bytes(16)) . '.' . $ext;
$dest = $dir . '/' . $fname;
if (!move_uploaded_file($file['tmp_name'], $dest)) Response::error('Failed to save file', 500);
@chmod($dest, 0644);

$rel = '/uploads/posts/' . $fname;
$ins = $pdo->prepare('INSERT INTO blog_attachments (post_id, file_path, original_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?)');
$ins->execute([$postId, $rel, $file['name'], $mime, (int)$file['size']]);

Response::json([
    'ok' => true,
    'attachment' => [
        'id'            => (int)$pdo->lastInsertId(),
        'post_id'       => $postId,
        'file_path'     => $rel,
        'original_name' => $file['name'],
        'mime_type'     => $mime,
        'file_size'     => (int)$file['size'],
    ],
]);
