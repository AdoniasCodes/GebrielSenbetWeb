<?php
// api/admin/resources/index.php — files & links shared per grade or department.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$pdo = (new Database(app_config()['db']))->pdo();
$method = $_SERVER['REQUEST_METHOD'];

function res_valid_scope($t): bool { return in_array($t, ['grade', 'department'], true); }

if ($method === 'GET') {
    $st = $_GET['scope_type'] ?? '';
    $sid = (int)($_GET['scope_id'] ?? 0);
    if (!res_valid_scope($st) || $sid <= 0) Response::error('scope_type and scope_id are required', 422);
    $stmt = $pdo->prepare("SELECT id, title, kind, url, file_name, size_bytes, created_at
                           FROM resources
                           WHERE scope_type=? AND scope_id=? AND is_archived=0
                           ORDER BY created_at DESC");
    $stmt->execute([$st, $sid]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $isMultipart = !empty($_FILES) ||
        (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);

    if ($isMultipart) {
        $st = $_POST['scope_type'] ?? '';
        $sid = (int)($_POST['scope_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if (!res_valid_scope($st) || $sid <= 0) Response::error('Pick a grade or department first', 422);
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) Response::error('File upload failed', 422);
        $f = $_FILES['file'];
        if ($f['size'] > 25 * 1024 * 1024) Response::error('File too large (max 25MB)', 422);
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','rtf','csv','jpg','jpeg','png','gif','webp','mp3','m4a','zip'];
        if (!in_array($ext, $allowed, true)) Response::error('That file type is not allowed', 422);
        if ($title === '') $title = pathinfo($f['name'], PATHINFO_FILENAME);
        $dir = __DIR__ . '/../../../public/uploads/resources';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $safe = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $safe)) Response::error('Could not store the file', 500);
        $ins = $pdo->prepare("INSERT INTO resources (scope_type,scope_id,title,kind,url,file_name,size_bytes,uploaded_by_user_id)
                              VALUES (?,?,?,'file',?,?,?,?)");
        $ins->execute([$st, $sid, $title, '/uploads/resources/' . $safe, $f['name'], (int)$f['size'], (int)($_SESSION['user_id'] ?? 0)]);
        Response::json(['message' => 'Uploaded', 'id' => (int)$pdo->lastInsertId()]);
    }

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $st = $in['scope_type'] ?? '';
    $sid = (int)($in['scope_id'] ?? 0);
    $title = trim($in['title'] ?? '');
    $url = trim($in['url'] ?? '');
    if (!res_valid_scope($st) || $sid <= 0) Response::error('Pick a grade or department first', 422);
    if ($title === '' || $url === '') Response::error('Title and link are required', 422);
    if (!preg_match('#^https?://#i', $url)) Response::error('Link must start with http:// or https://', 422);
    $ins = $pdo->prepare("INSERT INTO resources (scope_type,scope_id,title,kind,url,uploaded_by_user_id)
                          VALUES (?,?,?,'link',?,?)");
    $ins->execute([$st, $sid, $title, $url, (int)($_SESSION['user_id'] ?? 0)]);
    Response::json(['message' => 'Added', 'id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare("UPDATE resources SET is_archived=1 WHERE id=?")->execute([$id]);
    Response::json(['message' => 'Removed']);
}

Response::error('Method not allowed', 405);
