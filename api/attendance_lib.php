<?php
// api/attendance_lib.php
// Phase 2.3: term scoping for attendance. Two responsibilities:
//   1) attendance_term_for_date(): the single term-derivation rule, so every
//      session-creating endpoint stamps term_id the same way.
//   2) attendance_class_summary(): per-student attendance % for a class + term,
//      using the canonical formula shared with the student dashboard and the
//      eligibility engine — (present+late) / (present+late+absent), excused
//      excluded, class-context sessions only.

/**
 * The academic term whose date range contains $date, or null if the date falls
 * in a gap between terms. Deterministic (is_current, then lowest id) so a stray
 * overlap can never make this ambiguous. Mirrors migration 024's backfill.
 *
 * @param string $date 'YYYY-MM-DD'
 */
function attendance_term_for_date(\PDO $pdo, string $date): ?int {
    $st = $pdo->prepare(
        "SELECT id FROM academic_terms
          WHERE is_archived = 0 AND ? BETWEEN start_date AND end_date
          ORDER BY is_current DESC, id
          LIMIT 1"
    );
    $st->execute([$date]);
    $v = $st->fetchColumn();
    return $v === false ? null : (int)$v;
}

/**
 * Per-student attendance summary for one class in one term. Returns one row per
 * enrolled student (ungraded/unmarked students included, rate null) with
 * present/late/absent/excused counts and the canonical rate.
 *
 * @return array<int,array{student_id:int,person_id:int,first_name:string,last_name:string,present:int,late:int,absent:int,excused:int,rate:?float}>
 */
function attendance_class_summary(\PDO $pdo, int $classId, int $termId): array {
    $st = $pdo->prepare(
        "SELECT s.id AS student_id, s.person_id, s.first_name, s.last_name,
                SUM(CASE WHEN ar.status='present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN ar.status='late'    THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN ar.status='absent'  THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN ar.status='excused' THEN 1 ELSE 0 END) AS excused
           FROM (SELECT DISTINCT student_id FROM student_class_assignments
                  WHERE class_id = ? AND is_archived = 0) sca
           JOIN students s ON s.id = sca.student_id AND s.is_archived = 0
           LEFT JOIN attendance_sessions ses
                  ON ses.context_type = 'class' AND ses.context_id = ?
                 AND ses.term_id = ? AND ses.is_archived = 0
           LEFT JOIN attendance_records ar
                  ON ar.session_id = ses.id AND ar.person_id = s.person_id
          GROUP BY s.id, s.person_id, s.first_name, s.last_name
          ORDER BY s.first_name, s.last_name"
    );
    $st->execute([$classId, $classId, $termId]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $p = (int)$r['present']; $l = (int)$r['late']; $a = (int)$r['absent']; $e = (int)$r['excused'];
        $counted = $p + $l + $a; // excused excluded from the denominator
        $out[] = [
            'student_id' => (int)$r['student_id'],
            'person_id'  => (int)$r['person_id'],
            'first_name' => $r['first_name'],
            'last_name'  => $r['last_name'],
            'present'    => $p, 'late' => $l, 'absent' => $a, 'excused' => $e,
            'rate'       => $counted > 0 ? round(($p + $l) * 100.0 / $counted, 1) : null,
        ];
    }
    return $out;
}
