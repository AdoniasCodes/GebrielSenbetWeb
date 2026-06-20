<?php
// api/admin/grade-subjects/index.php — which curriculum subjects belong to a grade.
// GET /api/admin/grade-subjects?level_id=   → subjects assigned to that grade (ordered)
// PUT body: { level_id, subject_ids: int[] }  → replaces the full set for that grade

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($method === 'GET') {
    $levelId = (int)($_GET['level_id'] ?? 0);
    if ($levelId <= 0) Response::error('level_id is required', 422);
    $sql = "SELECT gs.id, gs.subject_id, gs.sort_order, s.name, s.name_am
            FROM grade_subjects gs
            JOIN subjects s ON s.id = gs.subject_id
            WHERE gs.level_id = ? AND gs.is_archived = 0
            ORDER BY gs.sort_order, s.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$levelId]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $levelId = (int)($in['level_id'] ?? 0);
    if ($levelId <= 0) Response::error('level_id is required', 422);
    $subjectIds = is_array($in['subject_ids'] ?? null) ? array_values(array_unique(array_map('intval', $in['subject_ids']))) : [];

    $lc = $pdo->prepare('SELECT id FROM class_levels WHERE id=? AND is_archived=0');
    $lc->execute([$levelId]);
    if (!$lc->fetch()) Response::error('Grade level not found', 404);

    try {
        $pdo->beginTransaction();
        // Archive the current set, then (re)activate the chosen ones with order.
        $pdo->prepare('UPDATE grade_subjects SET is_archived=1, archived_at=NOW() WHERE level_id=? AND is_archived=0')->execute([$levelId]);
        $up = $pdo->prepare('INSERT INTO grade_subjects (level_id, subject_id, sort_order, is_archived, archived_at)
                             VALUES (?, ?, ?, 0, NULL)
                             ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order), is_archived=0, archived_at=NULL');
        $o = 0;
        foreach ($subjectIds as $sid) {
            if ($sid > 0) $up->execute([$levelId, $sid, $o++]);
        }
        $pdo->commit();
        \App\Audit::log('curriculum.grade_subjects.set', 'class_level', $levelId, ['count' => count($subjectIds)]);
        Response::json(['ok' => true, 'count' => count($subjectIds)]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to update curriculum mapping', 500);
    }
}

Response::error('Method not allowed', 405);
