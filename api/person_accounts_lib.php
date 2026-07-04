<?php
// api/person_accounts_lib.php
// Shared account-creation logic used by BOTH the admin users endpoint
// (api/admin/users/index.php) and the staff/dept-head roster endpoint
// (api/staff/roster.php). Creating a person here always makes the canonical
// `users` row and — for teacher/student roles — the linked `people` row plus
// the role profile row, in a single flow. The caller owns the transaction.
//
// Also provides the per-user department-assignment notification writer, so both
// the admin multi-dept flow and the dept-head flow emit an identical row shape
// (target_type='user', target_payload {"user_id": <teacher's users.id>}).

use App\Utils\Password;

// Validation error carrying an HTTP status; callers map it to Response::error.
class PersonAccountError extends \RuntimeException {
    public int $status;
    public function __construct(string $message, int $status = 422) {
        parent::__construct($message);
        $this->status = $status;
    }
}

/**
 * Create a user account and (for teacher/student) its canonical person + profile.
 * MUST be called inside an open transaction. Throws PersonAccountError on any
 * validation failure (duplicate email, missing name, invalid role, ...).
 *
 * @return array{user_id:int, person_id:?int, profile_id:?int, role:string, password:string, password_generated:bool}
 */
function create_person_account(\PDO $pdo, array $input): array {
    $roleName = trim((string)($input['role'] ?? ''));
    $email    = trim((string)($input['email'] ?? ''));
    if ($roleName === '' || $email === '') {
        throw new PersonAccountError('role and email are required', 422);
    }

    $supplied = isset($input['password']) && $input['password'] !== '' ? (string)$input['password'] : null;
    $password = $supplied ?? Password::generate(12);

    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
    $roleStmt->execute([$roleName]);
    $role = $roleStmt->fetch();
    if (!$role) { throw new PersonAccountError('Invalid role', 422); }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) { throw new PersonAccountError('Email already exists', 409); }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)');
    $ins->execute([$email, $hash, (int)$role['id']]);
    $userId = (int)$pdo->lastInsertId();

    $personId = null;
    $profileId = null;

    if ($roleName === 'student') {
        $first = trim((string)($input['first_name'] ?? ''));
        $last  = trim((string)($input['last_name'] ?? ''));
        if ($first === '' || $last === '') { throw new PersonAccountError('first_name and last_name required for student', 422); }
        $guardian = trim((string)($input['guardian_name'] ?? ''));
        $phone    = trim((string)($input['phone'] ?? ''));
        $address  = trim((string)($input['address'] ?? ''));
        $ip = $pdo->prepare('INSERT INTO people (user_id, first_name, last_name) VALUES (?, ?, ?)');
        $ip->execute([$userId, $first, $last]);
        $personId = (int)$pdo->lastInsertId();
        $is = $pdo->prepare('INSERT INTO students (user_id, person_id, first_name, last_name, guardian_name, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $is->execute([$userId, $personId, $first, $last, $guardian, $phone, $address]);
        $profileId = (int)$pdo->lastInsertId();
    } elseif ($roleName === 'teacher') {
        $first = trim((string)($input['first_name'] ?? ''));
        $last  = trim((string)($input['last_name'] ?? ''));
        if ($first === '' || $last === '') { throw new PersonAccountError('first_name and last_name required for teacher', 422); }
        $phone = trim((string)($input['phone'] ?? ''));
        $bio   = isset($input['bio']) ? (string)$input['bio'] : null;
        $ip = $pdo->prepare('INSERT INTO people (user_id, first_name, last_name) VALUES (?, ?, ?)');
        $ip->execute([$userId, $first, $last]);
        $personId = (int)$pdo->lastInsertId();
        $it = $pdo->prepare('INSERT INTO teachers (user_id, person_id, first_name, last_name, phone, bio) VALUES (?, ?, ?, ?, ?, ?)');
        $it->execute([$userId, $personId, $first, $last, $phone, $bio]);
        $profileId = (int)$pdo->lastInsertId();
    }
    // Any other role (staff/parent/admin): user row only, matching prior behaviour.

    return [
        'user_id'            => $userId,
        'person_id'          => $personId,
        'profile_id'         => $profileId,
        'role'               => $roleName,
        'password'           => $password,
        'password_generated' => $supplied === null,
    ];
}

/**
 * Human-friendly bilingual department label, e.g. "Choir & Hymns (መዝሙር ክፍል)".
 */
function department_display_name(array $deptRow): string {
    $name = trim((string)($deptRow['name'] ?? ''));
    $am   = trim((string)($deptRow['name_am'] ?? ''));
    if ($name !== '' && $am !== '') return $name . ' (' . $am . ')';
    return $name !== '' ? $name : $am;
}

/**
 * Write the per-user "you were assigned to department(s)" notification.
 * Row shape (consumed by the teacher portal — Phase C):
 *   target_type   = 'user'
 *   target_payload= {"user_id": <teacher's users.id>}
 *   is_public     = 0
 * One row per assignment operation; when several departments are assigned at
 * once the message lists all of them.
 *
 * @param string[] $deptDisplayNames one bilingual label per department
 * @return int notification id
 */
function notify_department_assignment(
    \PDO $pdo,
    int $targetUserId,
    array $deptDisplayNames,
    int $senderUserId,
    ?int $senderRoleId
): int {
    $labels = array_values(array_filter(array_map('strval', $deptDisplayNames), fn($s) => $s !== ''));
    if (!$labels) { $labels = ['a department']; }
    $joined = implode(', ', $labels);

    if (count($labels) > 1) {
        $title   = 'Department assignment / የክፍል ምደባ';
        $message = 'You were assigned to the following departments: ' . $joined . '.';
    } else {
        $title   = 'Department assignment / የክፍል ምደባ';
        $message = 'You were assigned to the ' . $joined . ' department.';
    }

    $payload = json_encode(['user_id' => $targetUserId], JSON_UNESCAPED_UNICODE);
    $ins = $pdo->prepare(
        'INSERT INTO notifications (sender_user_id, sender_role_id, target_type, target_payload, title, message, is_public)
         VALUES (?, ?, ?, ?, ?, ?, 0)'
    );
    $ins->execute([$senderUserId ?: null, $senderRoleId ?: null, 'user', $payload, $title, $message]);
    return (int)$pdo->lastInsertId();
}
