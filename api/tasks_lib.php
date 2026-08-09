<?php
// api/tasks_lib.php
// Phase 2.5 (SYSTEM_AUDIT_AND_BLUEPRINT.md §9): tasks/homework were write-only.
// Teachers created them and no reader existed. This is the single definition of
// "which tasks does a given student see", so the student portal and the parent
// portal cannot drift apart the way the notification producers/readers once did.
//
// A task is scoped one of three ways (tasks.scope_type):
//   class       -> the student's active class assignments
//   grade       -> the class_levels behind those classes
//   department  -> departments the student's person row belongs to
//
// Reads only. Turn-in / submission is deliberately out of scope.

/**
 * Resolve the scope ids a student is addressed by.
 *
 * @return array{class:int[],grade:int[],department:int[]}
 */
function tsk_scopes_for_students(\PDO $pdo, array $studentIds): array {
    $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
    $empty = ['class' => [], 'grade' => [], 'department' => []];
    if (!$studentIds) return $empty;

    $ph = implode(',', array_fill(0, count($studentIds), '?'));

    // Classes the student is actively assigned to, and the grade behind each.
    $st = $pdo->prepare(
        "SELECT DISTINCT sca.student_id, c.id AS class_id, c.level_id
           FROM student_class_assignments sca
           JOIN classes c ON c.id = sca.class_id AND c.is_archived = 0
          WHERE sca.student_id IN ($ph) AND sca.is_archived = 0"
    );
    $st->execute($studentIds);

    $byStudent = [];
    $classIds = $gradeIds = [];
    foreach ($st->fetchAll() as $r) {
        $sid = (int)$r['student_id'];
        $byStudent[$sid]['class'][]  = (int)$r['class_id'];
        $classIds[] = (int)$r['class_id'];
        if ($r['level_id'] !== null) {
            $byStudent[$sid]['grade'][] = (int)$r['level_id'];
            $gradeIds[] = (int)$r['level_id'];
        }
    }

    // Departments, reached through the student's canonical person row.
    $ds = $pdo->prepare(
        "SELECT DISTINCT s.id AS student_id, dm.department_id
           FROM students s
           JOIN department_memberships dm ON dm.person_id = s.person_id AND dm.is_archived = 0
          WHERE s.id IN ($ph) AND s.person_id IS NOT NULL
            AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())"
    );
    $ds->execute($studentIds);
    $deptIds = [];
    foreach ($ds->fetchAll() as $r) {
        $byStudent[(int)$r['student_id']]['department'][] = (int)$r['department_id'];
        $deptIds[] = (int)$r['department_id'];
    }

    return [
        'class'      => array_values(array_unique($classIds)),
        'grade'      => array_values(array_unique($gradeIds)),
        'department' => array_values(array_unique($deptIds)),
        'by_student' => $byStudent,
    ];
}

/**
 * Every non-archived task addressed to any of these students, newest-relevant
 * first (undated last, then by due date). Each row carries student_ids so a
 * parent viewing several children can tell whose task it is.
 */
function tsk_list_for_students(\PDO $pdo, array $studentIds, ?int $onlyStudentId = null): array {
    $scopes = tsk_scopes_for_students($pdo, $studentIds);
    $byStudent = $scopes['by_student'] ?? [];

    $ors = [];
    $params = [];
    foreach (['class', 'grade', 'department'] as $type) {
        if (!$scopes[$type]) continue;
        $ph = implode(',', array_fill(0, count($scopes[$type]), '?'));
        $ors[] = "(t.scope_type = ? AND t.scope_id IN ($ph))";
        $params[] = $type;
        $params = array_merge($params, $scopes[$type]);
    }
    if (!$ors) return [];

    $sql = "SELECT t.id, t.scope_type, t.scope_id, t.title, t.description, t.due_date, t.created_at,
                   COALESCE(NULLIF(TRIM(CONCAT(COALESCE(te.first_name,''),' ',COALESCE(te.last_name,''))), ''), u.email) AS posted_by
              FROM tasks t
              LEFT JOIN users u    ON u.id = t.created_by_user_id
              LEFT JOIN teachers te ON te.user_id = t.created_by_user_id AND te.is_archived = 0
             WHERE t.is_archived = 0 AND (" . implode(' OR ', $ors) . ")
             ORDER BY (t.due_date IS NULL), t.due_date ASC, t.created_at DESC
             LIMIT 200";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    if (!$rows) return [];

    // Human labels for each scope, resolved in three small batched lookups.
    $need = ['class' => [], 'grade' => [], 'department' => []];
    foreach ($rows as $r) $need[$r['scope_type']][] = (int)$r['scope_id'];

    $labels = ['class' => [], 'grade' => [], 'department' => []];
    $sources = [
        'class'      => ['classes', 'name', null],
        'grade'      => ['class_levels', 'name', 'name_am'],
        'department' => ['departments', 'name', 'name_am'],
    ];
    foreach ($sources as $type => [$table, $col, $colAm]) {
        $ids = array_values(array_unique($need[$type]));
        if (!$ids) continue;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $select = "id, $col AS label" . ($colAm ? ", $colAm AS label_am" : ", NULL AS label_am");
        $q = $pdo->prepare("SELECT $select FROM $table WHERE id IN ($ph)");
        $q->execute($ids);
        foreach ($q->fetchAll() as $row) $labels[$type][(int)$row['id']] = $row;
    }

    $out = [];
    foreach ($rows as $r) {
        $type = $r['scope_type'];
        $sid  = (int)$r['scope_id'];
        $lab  = $labels[$type][$sid] ?? null;

        // Which of these students this particular task actually addresses.
        $forStudents = [];
        foreach ($byStudent as $studentId => $mine) {
            if (in_array($sid, $mine[$type] ?? [], true)) $forStudents[] = (int)$studentId;
        }
        if ($onlyStudentId !== null && !in_array($onlyStudentId, $forStudents, true)) continue;

        $out[] = [
            'id'          => (int)$r['id'],
            'title'       => $r['title'],
            'description' => $r['description'],
            'due_date'    => $r['due_date'],
            'created_at'  => $r['created_at'],
            'posted_by'   => $r['posted_by'],
            'scope_type'  => $type,
            'scope_label'    => $lab['label']    ?? null,
            'scope_label_am' => $lab['label_am'] ?? null,
            'student_ids' => $forStudents,
        ];
    }
    return $out;
}
