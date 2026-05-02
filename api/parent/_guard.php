<?php
// api/parent/_guard.php
use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? '') !== 'parent') {
    Response::error('Forbidden', 403);
}

function require_csrf_for_write(): void {
    $cfg = app_config();
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH','DELETE'], true)) return;
    if (!\App\Utils\Csrf::validate($cfg['app']['csrf_header'])) {
        \App\Utils\Response::error('Invalid CSRF token', 403);
    }
}

// Returns the list of student IDs the current parent can access. Cached per request.
function parent_student_ids(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) { $cache = []; return $cache; }
    $cfg = app_config();
    $pdo = (new Database($cfg['db']))->pdo();
    $stmt = $pdo->prepare('SELECT student_id FROM student_guardians WHERE user_id=? AND is_archived=0');
    $stmt->execute([$userId]);
    $cache = array_map('intval', array_column($stmt->fetchAll(), 'student_id'));
    return $cache;
}

// Helper: SQL placeholder list "(?, ?, ?)" matching parent_student_ids count.
function parent_student_id_placeholders(): string {
    $n = count(parent_student_ids());
    if ($n === 0) return '(NULL)';
    return '(' . implode(',', array_fill(0, $n, '?')) . ')';
}
