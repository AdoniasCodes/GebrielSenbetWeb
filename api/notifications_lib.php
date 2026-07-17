<?php
// api/notifications_lib.php
// Phase 2.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md §9): the single definition of the
// notification target contract. Every PRODUCER writes through notify() (or a
// typed wrapper); every READER filters through notif_audience_clause(). Both
// derive from NOTIFY_TARGETS, so a target that can be written is by construction
// a target that can be read — the "composer and readers drifted into three
// independent lists" failure mode (see the pre-2.1 audit) is structurally gone.
//
// Target contract (4 types):
//   role       payload {role: admin|teacher|student|parent|staff}  -> everyone with that role
//   department payload {department_id: N}                          -> every member of the dept (any role)
//   class      payload {class_id: N}                               -> the class's students, their parents, its teachers
//   user       payload {user_id: N}                                -> one person
//
// is_public=1 is orthogonal (drives the public landing feed) and independent of target.
//
// JSON comparison convention: ATTR_EMULATE_PREPARES=false sends bound ints as
// typed params that a raw JSON_EXTRACT scalar will not match, so every
// bound-param JSON comparison unwraps both sides with JSON_UNQUOTE. A bare
// JSON_EXTRACT = 'literal' is only safe for an inline string literal (the role
// checks below). This is the same rule documented at api/teacher/notifications.php.

/**
 * The writable/readable target types and the payload key each one carries.
 * Adding a row here is the ONLY place a new target type is introduced; the
 * validator, the producers, and notif_audience_clause() all consult it.
 */
const NOTIFY_TARGETS = [
    'role'       => 'role',
    'department' => 'department_id',
    'class'      => 'class_id',
    'user'       => 'user_id',
];

const NOTIFY_ROLES = ['admin', 'teacher', 'student', 'parent', 'staff'];

class NotifyError extends \RuntimeException {}

/**
 * Write one notification row. The single choke point for all producers.
 *
 * @param \PDO        $pdo
 * @param string      $targetType  one of array_keys(NOTIFY_TARGETS)
 * @param array|null  $payload     must contain the key NOTIFY_TARGETS[$targetType]
 * @param string      $title       bilingual "EN / አማርኛ"
 * @param string      $message
 * @param array       $opts        senderUserId?, senderRoleId?, isPublic?(bool)
 * @return int notification id
 */
function notify(\PDO $pdo, string $targetType, ?array $payload, string $title, string $message, array $opts = []): int {
    if (!isset(NOTIFY_TARGETS[$targetType])) {
        throw new NotifyError("Unknown notification target_type: $targetType");
    }
    $key = NOTIFY_TARGETS[$targetType];
    if (!is_array($payload) || !array_key_exists($key, $payload)) {
        throw new NotifyError("target '$targetType' requires payload key '$key'");
    }
    if ($targetType === 'role' && !in_array($payload['role'], NOTIFY_ROLES, true)) {
        throw new NotifyError('Unknown role: ' . var_export($payload['role'], true));
    }

    $isPublic = !empty($opts['isPublic']) ? 1 : 0;
    $senderUserId = isset($opts['senderUserId']) ? (int)$opts['senderUserId'] : 0;
    $senderRoleId = isset($opts['senderRoleId']) ? (int)$opts['senderRoleId'] : 0;

    $ins = $pdo->prepare(
        'INSERT INTO notifications (sender_user_id, sender_role_id, target_type, target_payload, title, message, is_public)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $senderUserId ?: null,
        $senderRoleId ?: null,
        $targetType,
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        $title,
        $message,
        $isPublic,
    ]);
    return (int)$pdo->lastInsertId();
}

// --- Typed wrappers: intent-revealing call sites for the common producers. ---

function notify_user(\PDO $pdo, int $userId, string $title, string $message, array $opts = []): int {
    return notify($pdo, 'user', ['user_id' => $userId], $title, $message, $opts);
}

function notify_role(\PDO $pdo, string $role, string $title, string $message, array $opts = []): int {
    return notify($pdo, 'role', ['role' => $role], $title, $message, $opts);
}

function notify_department(\PDO $pdo, int $departmentId, string $title, string $message, array $opts = []): int {
    return notify($pdo, 'department', ['department_id' => $departmentId], $title, $message, $opts);
}

function notify_class(\PDO $pdo, int $classId, string $title, string $message, array $opts = []): int {
    return notify($pdo, 'class', ['class_id' => $classId], $title, $message, $opts);
}

/**
 * Users to notify for "the heads of department X". The single-axis target model
 * cannot express (role=staff AND department=X), so compound audiences fan out to
 * individual user rows at produce time (a handful of heads; leaves an auditable
 * record of exactly who was told). Reads department_memberships.is_head.
 *
 * @return int[] distinct users.id
 */
function notif_department_head_user_ids(\PDO $pdo, int $departmentId): array {
    $st = $pdo->prepare(
        "SELECT DISTINCT p.user_id
           FROM department_memberships dm
           JOIN people p ON p.id = dm.person_id
          WHERE dm.department_id = ?
            AND dm.is_head = 1
            AND dm.is_archived = 0
            AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())
            AND p.user_id IS NOT NULL"
    );
    $st->execute([$departmentId]);
    return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
}

/**
 * Build the WHERE fragment matching every notification a given reader should
 * see, plus its bound params. Membership facts are supplied by the caller
 * (each portal computes "my departments / my classes / my role" its own way);
 * this function is the single authority on how those facts map to target rows.
 * Columns are prefixed with the `n` alias — callers must alias notifications AS n
 * (notif_inbox_query() does this for you).
 *
 * @param array $ctx  user_id:int, role:string, dept_ids?:int[], class_ids?:int[],
 *                    include_public?:bool (default true)
 * @return array ['sql' => '(...)', 'params' => [...]]  — sql is a parenthesised
 *               boolean expression over the `n`-aliased notifications columns.
 */
function notif_audience_clause(array $ctx): array {
    $userId   = (int)($ctx['user_id'] ?? 0);
    $role     = (string)($ctx['role'] ?? '');
    $deptIds  = array_values(array_unique(array_map('intval', $ctx['dept_ids'] ?? [])));
    $classIds = array_values(array_unique(array_map('intval', $ctx['class_ids'] ?? [])));
    $includePublic = $ctx['include_public'] ?? true;

    $ors = [];
    $params = [];

    if ($includePublic) {
        $ors[] = 'n.is_public = 1';
    }
    if ($role !== '') {
        // Inline string literal — safe as a bare JSON_EXTRACT comparison.
        $ors[] = "(n.target_type = 'role' AND JSON_EXTRACT(n.target_payload, '$.role') = " . _notif_quote_role($role) . ")";
    }
    if ($userId > 0) {
        $ors[] = "(n.target_type = 'user' AND JSON_UNQUOTE(JSON_EXTRACT(n.target_payload, '$.user_id')) = ?)";
        $params[] = $userId;
    }
    if ($deptIds) {
        $ph = implode(',', array_fill(0, count($deptIds), '?'));
        $ors[] = "(n.target_type = 'department' AND JSON_UNQUOTE(JSON_EXTRACT(n.target_payload, '$.department_id')) IN ($ph))";
        $params = array_merge($params, $deptIds);
    }
    if ($classIds) {
        $ph = implode(',', array_fill(0, count($classIds), '?'));
        $ors[] = "(n.target_type = 'class' AND JSON_UNQUOTE(JSON_EXTRACT(n.target_payload, '$.class_id')) IN ($ph))";
        $params = array_merge($params, $classIds);
    }

    if (!$ors) {
        // A reader with no identity at all matches nothing (never all rows).
        return ['sql' => '(1 = 0)', 'params' => []];
    }
    return ['sql' => '(' . implode(' OR ', $ors) . ')', 'params' => $params];
}

/**
 * Full inbox SELECT for a reader: the audience clause + a LEFT JOIN onto
 * notification_reads so each row carries is_read for this user. The single
 * query shape every portal inbox uses.
 *
 * @return array ['sql' => '...', 'params' => [...]]  rows: id,title,message,created_at,is_read
 */
function notif_inbox_query(array $ctx, int $limit = 100): array {
    $clause = notif_audience_clause($ctx);
    $uid = (int)($ctx['user_id'] ?? 0);
    $limit = max(1, min(500, $limit));
    // The join's ? (nr.user_id) precedes the WHERE clause params in text order.
    $sql = "SELECT n.id, n.title, n.message, n.created_at,
                   (nr.notification_id IS NOT NULL) AS is_read
              FROM notifications n
              LEFT JOIN notification_reads nr
                     ON nr.notification_id = n.id AND nr.user_id = ?
             WHERE n.is_archived = 0 AND {$clause['sql']}
             ORDER BY n.created_at DESC
             LIMIT $limit";
    return ['sql' => $sql, 'params' => array_merge([$uid], $clause['params'])];
}

// role is validated against NOTIFY_ROLES, so quoting here can only ever emit a
// known-safe literal — but validate defensively rather than interpolate blind.
function _notif_quote_role(string $role): string {
    if (!in_array($role, NOTIFY_ROLES, true)) {
        throw new NotifyError("Unknown role in audience clause: $role");
    }
    return "'" . $role . "'";
}

/**
 * Mark a notification read for a user, idempotently. INSERT IGNORE against the
 * (notification_id, user_id) unique key — no read-modify-write, so it is safe
 * under concurrency (fixes the pre-2.1 lost-update race on read_by).
 */
function notif_mark_read(\PDO $pdo, int $notificationId, int $userId): void {
    $st = $pdo->prepare(
        'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)'
    );
    $st->execute([$notificationId, $userId]);
}
