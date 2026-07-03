<?php
// api/admin/reset-data/index.php
// Password-gated "production-ready reset". Admin-guarded + CSRF.
//   GET                                  -> current row counts (for the UI)
//   POST {action, password}              -> wipe_clean | load_demo
// Keeps the CURRENTLY LOGGED-IN admin + all reference/scaffolding tables.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

const RESET_PASSWORD = 'Panda2022';
// Fixed password for the generated test accounts (convenience — these are wiped
// before real launch via "Wipe to clean slate").
const DEMO_ACCOUNT_PASSWORD = 'demo1234';

$config = app_config();
$pdo = (new Database($config['db']))->pdo();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Operational tables wiped by both actions. Reference tables (roles, churches,
// departments, department_levels, education_tracks, class_levels, subjects,
// grade_subjects, app_settings, holidays, schema_migrations) are left intact.
$WIPE_TABLES = [
    'attendance_records', 'attendance_sessions', 'serving_assignments',
    'grades', 'payments', 'teacher_subject_assignments', 'student_class_assignments',
    'student_guardians', 'department_memberships', 'notifications',
    'events', 'event_recurrence_rules', 'blog_posts', 'blog_attachments',
    'video_embeds', 'resources', 'audit_log',
    'students', 'teachers', 'people', 'classes', 'academic_terms',
];

function rd_count(\PDO $pdo, string $t): int {
    try { return (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
    catch (\Throwable $e) { return -1; }
}

if ($method === 'GET') {
    $counts = [];
    foreach (['users','people','students','teachers','classes','grades','payments',
              'attendance_sessions','notifications','departments'] as $t) {
        $counts[$t] = rd_count($pdo, $t);
    }
    Response::json(['counts' => $counts]);
}

if ($method !== 'POST') {
    Response::error('Method not allowed', 405);
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? '';
if (!hash_equals(RESET_PASSWORD, (string)($in['password'] ?? ''))) {
    Response::error('Invalid reset password', 403);
}
if (!in_array($action, ['wipe_clean', 'load_demo'], true)) {
    Response::error('Unknown action', 422);
}

$keepAdminId = (int)($_SESSION['user_id'] ?? 0);
if ($keepAdminId <= 0) Response::error('No admin session', 400);

// ---- The wipe (both actions) ----
function rd_wipe(\PDO $pdo, array $tables, int $keepAdminId): array {
    $deleted = [];
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($tables as $t) {
            $st = $pdo->prepare("DELETE FROM `$t`");
            $st->execute();
            $deleted[$t] = $st->rowCount();
        }
        // Keep ONLY the operator's own admin row.
        $du = $pdo->prepare('DELETE FROM users WHERE id <> ?');
        $du->execute([$keepAdminId]);
        $deleted['users'] = $du->rowCount();
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    return $deleted;
}

try {
    $deleted = rd_wipe($pdo, $WIPE_TABLES, $keepAdminId);

    if ($action === 'wipe_clean') {
        Response::json(['ok' => true, 'action' => 'wipe_clean',
                        'deleted' => $deleted, 'admin_kept_id' => $keepAdminId]);
    }

    // ---- load_demo: one wired dummy per non-admin role ----
    $roles = [];
    foreach ($pdo->query('SELECT id, name FROM roles')->fetchAll() as $r) $roles[$r['name']] = (int)$r['id'];
    foreach (['teacher','student','parent','staff'] as $need) {
        if (!isset($roles[$need])) Response::error("Role '$need' not seeded — cannot build demo", 500);
    }

    $year = date('Y');
    // 1) term
    $pdo->prepare("INSERT INTO academic_terms (name, academic_year, start_date, end_date, default_tuition, is_current)
                   VALUES ('Term I', ?, ?, ?, 500.00, 1)")
        ->execute([$year, "$year-01-01", "$year-12-31"]);
    $termId = (int)$pdo->lastInsertId();
    // 2) class on the first grade level
    $levelId = (int)($pdo->query("SELECT id FROM class_levels WHERE is_archived=0 ORDER BY sort_order, id LIMIT 1")->fetchColumn() ?: 0);
    if (!$levelId) Response::error('No class_levels (grades) seeded — cannot build demo', 500);
    $pdo->prepare("INSERT INTO classes (level_id, academic_year, name) VALUES (?, ?, 'Section A')")
        ->execute([$levelId, $year]);
    $classId = (int)$pdo->lastInsertId();
    // 3) first subject
    $subjectId = (int)($pdo->query("SELECT id FROM subjects WHERE is_archived=0 ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
    if (!$subjectId) Response::error('No subjects seeded — cannot build demo', 500);

    $accounts = [];
    $mkUser = function (string $email, int $roleId) use ($pdo): array {
        $pw = DEMO_ACCOUNT_PASSWORD;
        $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?,?,?)')
            ->execute([$email, password_hash($pw, PASSWORD_DEFAULT), $roleId]);
        return ['id' => (int)$pdo->lastInsertId(), 'pw' => $pw];
    };
    $mkPerson = function (int $userId, string $fn, string $ln) use ($pdo): int {
        $pdo->prepare('INSERT INTO people (user_id, first_name, last_name) VALUES (?,?,?)')
            ->execute([$userId, $fn, $ln]);
        return (int)$pdo->lastInsertId();
    };

    // TEACHER
    $tu = $mkUser('test-teacher@mekaneselamss.com', $roles['teacher']);
    $tPid = $mkPerson($tu['id'], 'Test', 'Teacher');
    $pdo->prepare('INSERT INTO teachers (user_id, person_id, first_name, last_name) VALUES (?,?,?,?)')
        ->execute([$tu['id'], $tPid, 'Test', 'Teacher']);
    $teacherId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO teacher_subject_assignments (teacher_id, class_id, subject_id, start_date) VALUES (?,?,?,CURDATE())')
        ->execute([$teacherId, $classId, $subjectId]);
    $accounts[] = ['role' => 'teacher', 'email' => 'test-teacher@mekaneselamss.com', 'password' => $tu['pw']];

    // STUDENT (+ enrollment, grade, attendance, payment)
    $su = $mkUser('test-student@mekaneselamss.com', $roles['student']);
    $sPid = $mkPerson($su['id'], 'Test', 'Student');
    $pdo->prepare('INSERT INTO students (user_id, person_id, first_name, last_name) VALUES (?,?,?,?)')
        ->execute([$su['id'], $sPid, 'Test', 'Student']);
    $studentId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO student_class_assignments (student_id, class_id, assigned_at) VALUES (?,?,CURDATE())')
        ->execute([$studentId, $classId]);
    $pdo->prepare("INSERT INTO grades (student_id, subject_id, class_id, term_id, score, remarks) VALUES (?,?,?,?,85.00,'Demo')")
        ->execute([$studentId, $subjectId, $classId, $termId]);
    $pdo->prepare("INSERT INTO attendance_sessions (context_type, context_id, session_date, created_by_user_id) VALUES ('class', ?, CURDATE(), ?)")
        ->execute([$classId, $keepAdminId]);
    $sessId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO attendance_records (session_id, person_id, status) VALUES (?,?, 'present')")
        ->execute([$sessId, $sPid]);
    $pdo->prepare("INSERT INTO payments (student_id, term_id, amount, paid_amount, status, notes) VALUES (?,?,500.00,200.00,'partial','Demo balance')")
        ->execute([$studentId, $termId]);
    $accounts[] = ['role' => 'student', 'email' => 'test-student@mekaneselamss.com', 'password' => $su['pw']];

    // PARENT (linked to the student)
    $pu = $mkUser('test-parent@mekaneselamss.com', $roles['parent']);
    $pdo->prepare("INSERT INTO student_guardians (user_id, student_id, relationship, is_primary) VALUES (?,?, 'Parent', 1)")
        ->execute([$pu['id'], $studentId]);
    $accounts[] = ['role' => 'parent', 'email' => 'test-parent@mekaneselamss.com', 'password' => $pu['pw']];

    // STAFF (department head of the choir, or first dept)
    $stu = $mkUser('test-staff@mekaneselamss.com', $roles['staff']);
    $stPid = $mkPerson($stu['id'], 'Test', 'Staff');
    $deptId = (int)($pdo->query("SELECT id FROM departments WHERE slug='mezmur' AND is_archived=0 LIMIT 1")->fetchColumn() ?: 0);
    if (!$deptId) $deptId = (int)($pdo->query("SELECT id FROM departments WHERE is_archived=0 ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
    if ($deptId) {
        $pdo->prepare("INSERT INTO department_memberships (person_id, department_id, is_head, title, joined_at) VALUES (?,?,1,'Head',CURDATE())")
            ->execute([$stPid, $deptId]);
    }
    $accounts[] = ['role' => 'staff', 'email' => 'test-staff@mekaneselamss.com', 'password' => $stu['pw']];

    // A public announcement so dashboards show one.
    $pdo->prepare("INSERT INTO notifications (sender_user_id, target_type, title, message, is_public)
                   VALUES (?, 'role', 'Welcome', 'This is a demo announcement created by the reset tool.', 1)")
        ->execute([$keepAdminId]);

    Response::json(['ok' => true, 'action' => 'load_demo', 'deleted' => $deleted,
                    'accounts' => $accounts, 'demo_password_note' => 'Shown once — save now.']);
} catch (\Throwable $e) {
    Response::error('Reset failed: ' . $e->getMessage(), 500);
}
