<?php
// api/teacher/departments.php — the teacher's department memberships, each with
// the department's student roster and the teacher's classes inside it.
//   GET -> { data: [ { id, slug, name, name_am, is_head, roster:[...], classes:[...] } ] }
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = tch_pdo();
$tid = teacher_id();
$deptIds = teacher_department_ids();

$data = [];
if ($deptIds) {
    $ph = implode(',', array_fill(0, count($deptIds), '?'));

    $dst = $pdo->prepare("SELECT id, slug, name, name_am FROM departments WHERE id IN ($ph) AND is_archived=0 ORDER BY sort_order, name");
    $dst->execute($deptIds);
    $depts = $dst->fetchAll();

    // is_head flag per department, keyed off this teacher's linked person.
    $headSet = [];
    $pidRow = $pdo->prepare('SELECT person_id FROM teachers WHERE id=?');
    $pidRow->execute([$tid]);
    $personId = (int)($pidRow->fetchColumn() ?: 0);
    if ($personId) {
        $hst = $pdo->prepare(
            'SELECT department_id FROM department_memberships
             WHERE person_id=? AND is_head=1 AND is_archived=0
               AND (ended_at IS NULL OR ended_at >= CURDATE())'
        );
        $hst->execute([$personId]);
        $headSet = array_flip(array_map('intval', $hst->fetchAll(\PDO::FETCH_COLUMN)));
    }

    foreach ($depts as $d) {
        $did = (int)$d['id'];

        $rst = $pdo->prepare(
            'SELECT s.id AS student_id, s.person_id, s.first_name, s.last_name
             FROM department_memberships dm
             JOIN students s ON s.person_id = dm.person_id AND s.is_archived = 0
             WHERE dm.department_id = ? AND dm.is_archived = 0
               AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())
             ORDER BY s.first_name, s.last_name'
        );
        $rst->execute([$did]);
        $roster = $rst->fetchAll();

        $cst = $pdo->prepare(
            "SELECT a.class_id, a.subject_id, c.name AS class_name, c.academic_year,
                    lvl.name AS level_name, lvl.name_am AS level_name_am,
                    sub.name AS subject_name, sub.name_am AS subject_name_am
             FROM teacher_subject_assignments a
             JOIN classes c ON c.id = a.class_id AND c.is_archived = 0 AND c.department_id = ?
             LEFT JOIN class_levels lvl ON lvl.id = c.level_id
             JOIN subjects sub ON sub.id = a.subject_id
             WHERE a.teacher_id = ? AND a.is_archived = 0
               AND (a.end_date IS NULL OR a.end_date >= CURDATE())
             GROUP BY a.class_id, a.subject_id
             ORDER BY c.name, sub.name"
        );
        $cst->execute([$did, $tid]);
        $classes = $cst->fetchAll();

        $data[] = [
            'id' => $did,
            'slug' => $d['slug'],
            'name' => $d['name'],
            'name_am' => $d['name_am'],
            'is_head' => isset($headSet[$did]) ? 1 : 0,
            'roster' => $roster,
            'classes' => $classes,
        ];
    }
}

Response::json(['data' => $data]);
