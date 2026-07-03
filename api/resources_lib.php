<?php
// api/resources_lib.php — shared list / upload / link / archive for the `resources`
// table, reused by admin, staff (department scope) and teacher (grade scope) endpoints.
// SECURITY CONTRACT: the CALLER must compute and pass the exact scope ids the current
// user is allowed to touch. These helpers never widen scope on their own.
use App\Utils\Response;

function res_valid_scope($t): bool { return in_array($t, ['grade', 'department'], true); }

const RES_ALLOWED_EXT = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','rtf','csv','jpg','jpeg','png','gif','webp','mp3','m4a','zip'];
const RES_MAX_BYTES = 26214400; // 25 MB

// List non-archived resources for a scope type across one or more allowed scope ids.
function res_list(\PDO $pdo, string $scopeType, array $scopeIds): array {
    if (!res_valid_scope($scopeType)) return [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $scopeIds), fn($i) => $i > 0)));
    if (!$ids) return [];
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, scope_type, scope_id, title, kind, url, file_name, size_bytes, created_at
                           FROM resources
                           WHERE scope_type=? AND scope_id IN ($ph) AND is_archived=0
                           ORDER BY created_at DESC");
    $stmt->execute(array_merge([$scopeType], $ids));
    return $stmt->fetchAll();
}

// Assert a scope_id is inside the caller's allowed set, else 403. Call before any write.
function res_assert_scope(int $scopeId, array $allowedIds): void {
    $allowed = array_map('intval', $allowedIds);
    if ($scopeId <= 0 || !in_array($scopeId, $allowed, true)) {
        Response::error('You cannot add resources to that scope', 403);
    }
}

// Store an uploaded file ($_FILES['file']) into a scope. Caller already scope-asserted.
function res_store_file(\PDO $pdo, string $scopeType, int $scopeId, int $userId): array {
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) Response::error('File upload failed', 422);
    $f = $_FILES['file'];
    if ((int)$f['size'] > RES_MAX_BYTES) Response::error('File too large (max 25MB)', 422);
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, RES_ALLOWED_EXT, true)) Response::error('That file type is not allowed', 422);
    $title = trim($_POST['title'] ?? '');
    if ($title === '') $title = pathinfo($f['name'], PATHINFO_FILENAME);
    $dir = __DIR__ . '/../public/uploads/resources';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $safe = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $safe)) Response::error('Could not store the file', 500);
    $ins = $pdo->prepare("INSERT INTO resources (scope_type,scope_id,title,kind,url,file_name,size_bytes,uploaded_by_user_id)
                          VALUES (?,?,?,'file',?,?,?,?)");
    $ins->execute([$scopeType, $scopeId, $title, '/uploads/resources/' . $safe, $f['name'], (int)$f['size'], $userId]);
    return ['message' => 'Uploaded', 'id' => (int)$pdo->lastInsertId()];
}

// Store a link into a scope. Caller already scope-asserted.
function res_store_link(\PDO $pdo, string $scopeType, int $scopeId, string $title, string $url, int $userId): array {
    $title = trim($title); $url = trim($url);
    if ($title === '' || $url === '') Response::error('Title and link are required', 422);
    if (!preg_match('#^https?://#i', $url)) Response::error('Link must start with http:// or https://', 422);
    $ins = $pdo->prepare("INSERT INTO resources (scope_type,scope_id,title,kind,url,uploaded_by_user_id)
                          VALUES (?,?,?,'link',?,?)");
    $ins->execute([$scopeType, $scopeId, $title, $url, $userId]);
    return ['message' => 'Added', 'id' => (int)$pdo->lastInsertId()];
}

// Archive a resource ONLY if it lives inside the caller's allowed scope ids.
function res_archive_scoped(\PDO $pdo, int $id, string $scopeType, array $allowedIds): array {
    if ($id <= 0) Response::error('id is required', 422);
    $ids = array_values(array_unique(array_map('intval', $allowedIds)));
    if (!$ids) Response::error('Forbidden', 403);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $chk = $pdo->prepare("SELECT 1 FROM resources WHERE id=? AND scope_type=? AND scope_id IN ($ph) AND is_archived=0 LIMIT 1");
    $chk->execute(array_merge([$id, $scopeType], $ids));
    if (!$chk->fetchColumn()) Response::error('Not found in your scope', 404);
    $pdo->prepare("UPDATE resources SET is_archived=1 WHERE id=?")->execute([$id]);
    return ['message' => 'Removed'];
}
