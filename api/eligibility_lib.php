<?php
// api/eligibility_lib.php — shared serving-eligibility computation.
// A department member's academic attendance % (from class roll-calls) decides
// whether they're eligible to serve. The threshold is an admin setting.

function gs_eligibility_threshold(\PDO $pdo): int {
    $v = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='serving_eligibility_min_attendance' LIMIT 1")->fetchColumn();
    return $v === false ? 75 : (int)$v;
}

// Returns ['threshold' => int, 'members' => [{person_id,name,level_name,level_name_am,
//   level_rank, attended, total, rate(null if no data), eligible(bool), has_data(bool)}]]
function gs_compute_eligibility(\PDO $pdo, int $departmentId): array {
    $threshold = gs_eligibility_threshold($pdo);
    $sql = "SELECT p.id AS person_id, CONCAT(p.first_name,' ',p.last_name) AS name,
                   dl.name AS level_name, dl.name_am AS level_name_am, dl.`rank` AS level_rank,
                   SUM(CASE WHEN s.id IS NOT NULL AND ar.status IN ('present','late') THEN 1 ELSE 0 END) AS attended,
                   SUM(CASE WHEN s.id IS NOT NULL AND ar.status IN ('present','late','absent') THEN 1 ELSE 0 END) AS total
            FROM department_memberships dm
            JOIN people p ON p.id = dm.person_id
            LEFT JOIN department_levels dl ON dl.id = dm.level_id
            LEFT JOIN attendance_records ar ON ar.person_id = p.id
            LEFT JOIN attendance_sessions s ON s.id = ar.session_id AND s.context_type='class' AND s.is_archived=0
            WHERE dm.department_id = ? AND dm.is_archived = 0
            GROUP BY p.id, name, dl.name, dl.name_am, dl.`rank`
            ORDER BY dl.`rank`, name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$departmentId]);
    $members = [];
    foreach ($stmt->fetchAll() as $r) {
        $attended = (int)$r['attended'];
        $total = (int)$r['total'];
        $rate = $total > 0 ? round($attended / $total * 100, 1) : null;
        $members[] = [
            'person_id'     => (int)$r['person_id'],
            'name'          => $r['name'],
            'level_name'    => $r['level_name'],
            'level_name_am' => $r['level_name_am'],
            'level_rank'    => $r['level_rank'] !== null ? (int)$r['level_rank'] : null,
            'attended'      => $attended,
            'total'         => $total,
            'rate'          => $rate,
            'eligible'      => ($rate !== null && $rate >= $threshold),
            'has_data'      => $total > 0,
        ];
    }
    return ['threshold' => $threshold, 'members' => $members];
}
