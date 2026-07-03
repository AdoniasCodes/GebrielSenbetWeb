<?php
// api/teacher/_guard.php
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? '') !== 'teacher') {
    Response::error('Forbidden', 403);
}

function require_csrf_for_write(): void {
    $cfg = app_config();
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH','DELETE'], true)) {
        return;
    }
    if (!\App\Utils\Csrf::validate($cfg['app']['csrf_header'])) {
        \App\Utils\Response::error('Invalid CSRF token', 403);
    }
}

function tch_pdo(): \PDO {
    static $pdo = null;
    if ($pdo === null) $pdo = (new \App\Database(app_config()['db']))->pdo();
    return $pdo;
}

function teacher_id(): int {
    static $id = -1;
    if ($id !== -1) return $id;
    $st = tch_pdo()->prepare('SELECT id FROM teachers WHERE user_id=? AND is_archived=0 LIMIT 1');
    $st->execute([(int)($_SESSION['user_id'] ?? 0)]);
    $id = (int)($st->fetchColumn() ?: 0);
    return $id;
}

// Class ids the teacher has an active subject assignment in.
function teacher_class_ids(): array {
    $tid = teacher_id();
    if (!$tid) return [];
    $st = tch_pdo()->prepare('SELECT DISTINCT class_id FROM teacher_subject_assignments
                              WHERE teacher_id=? AND is_archived=0 AND (end_date IS NULL OR end_date >= CURDATE())');
    $st->execute([$tid]);
    return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
}
function teacher_assert_class(int $classId): void {
    if (!in_array($classId, teacher_class_ids(), true)) {
        \App\Utils\Response::error('You do not teach this class', 403);
    }
}
function teacher_assert_class_subject(int $classId, int $subjectId): void {
    $st = tch_pdo()->prepare('SELECT 1 FROM teacher_subject_assignments
                              WHERE teacher_id=? AND class_id=? AND subject_id=? AND is_archived=0
                                AND (end_date IS NULL OR end_date >= CURDATE()) LIMIT 1');
    $st->execute([teacher_id(), $classId, $subjectId]);
    if (!$st->fetchColumn()) {
        \App\Utils\Response::error('You do not teach this subject in this class', 403);
    }
}
