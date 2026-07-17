<?php
// api/grades_lib.php
// Phase 2.2: the single authority on whether a (class, subject, term) gradebook
// may be written. Two independent locks, in precedence order:
//   1) term_closed  (hard) — academic_terms.closed_at set; blocks teacher AND admin.
//   2) finalized    (soft) — a grade_finalizations row for the gradebook; blocks
//                            the teacher only (admin writes bypass it).
// Both teacher and admin grade endpoints consult this so the rule lives in one place.

/** True if the term has been closed by an admin (hard lock, blocks everyone). */
function grade_is_term_closed(\PDO $pdo, int $termId): bool {
    $st = $pdo->prepare('SELECT closed_at FROM academic_terms WHERE id = ? LIMIT 1');
    $st->execute([$termId]);
    $v = $st->fetchColumn();
    return $v !== false && $v !== null;
}

/** True if this exact (class, subject, term) gradebook is finalized. */
function grade_is_finalized(\PDO $pdo, int $classId, int $subjectId, int $termId): bool {
    $st = $pdo->prepare(
        'SELECT 1 FROM grade_finalizations WHERE class_id = ? AND subject_id = ? AND term_id = ? LIMIT 1'
    );
    $st->execute([$classId, $subjectId, $termId]);
    return (bool)$st->fetchColumn();
}

/**
 * Why a grade write to this gradebook is blocked, or null if it is writable.
 * @param bool $isAdmin admin writes ignore the soft (finalized) lock.
 * @return string|null  'term_closed' | 'finalized' | null
 */
function grade_lock_reason(\PDO $pdo, int $classId, int $subjectId, int $termId, bool $isAdmin = false): ?string {
    if (grade_is_term_closed($pdo, $termId)) return 'term_closed';
    if (!$isAdmin && grade_is_finalized($pdo, $classId, $subjectId, $termId)) return 'finalized';
    return null;
}

/** Human message (bilingual) for a lock reason, for API error bodies. */
function grade_lock_message(string $reason): string {
    if ($reason === 'term_closed') {
        return 'This term is closed; grades are locked. / ይህ ኮርስ ተዘግቷል፤ ውጤቶች ተቆልፈዋል።';
    }
    return 'This gradebook is finalized; reopen it to make changes. / ይህ የውጤት መዝገብ ተጠናቋል፤ ለማስተካከል እንደገና ይክፈቱት።';
}
