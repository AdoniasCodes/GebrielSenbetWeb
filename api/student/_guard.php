<?php
// api/student/_guard.php — gate student-portal endpoints + resolve the
// student record for the logged-in user.
use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? '') !== 'student') {
    Response::error('Forbidden', 403);
}

// The student row owned by the current session user (or null if none linked).
function student_record(): ?array {
    static $rec = false;
    if ($rec !== false) return $rec ?: null;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) { $rec = null; return null; }
    $pdo = (new Database(app_config()['db']))->pdo();
    $st = $pdo->prepare('SELECT id, person_id, first_name, last_name, date_of_birth, guardian_name, phone
                         FROM students WHERE user_id=? AND is_archived=0 LIMIT 1');
    $st->execute([$uid]);
    $rec = $st->fetch() ?: null;
    return $rec;
}
