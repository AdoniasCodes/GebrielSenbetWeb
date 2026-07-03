<?php
// api/setup/demo_logins.php
// One-time helper: (re)create a demo login for every role with a known
// password, plus the linked records each portal needs. Guarded by the setup
// token. Idempotent — safe to run more than once. Remove/disable demo accounts
// before real use.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
$setupToken = $config['app']['setup_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Method Not Allowed', 405);
$provided = $_SERVER['HTTP_X_SETUP_TOKEN'] ?? '';
if ($setupToken === '' || $setupToken === 'CHANGE_ME_SETUP_TOKEN' || !hash_equals($setupToken, $provided)) {
    Response::error('Forbidden', 403);
}

$pdo = (new Database($config['db']))->pdo();
$PASS = 'demo1234';
$hash = password_hash($PASS, PASSWORD_DEFAULT);

$roles = [];
foreach ($pdo->query("SELECT id, name FROM roles")->fetchAll() as $r) $roles[$r['name']] = (int)$r['id'];

function demo_upsert_user(PDO $pdo, string $email, string $hash, int $roleId): int {
    $st = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    $row = $st->fetch();
    if ($row) {
        $pdo->prepare("UPDATE users SET password_hash=?, role_id=?, is_archived=0 WHERE id=?")
            ->execute([$hash, $roleId, (int)$row['id']]);
        return (int)$row['id'];
    }
    $pdo->prepare("INSERT INTO users (email, password_hash, role_id) VALUES (?,?,?)")
        ->execute([$email, $hash, $roleId]);
    return (int)$pdo->lastInsertId();
}

$accounts = [
    ['role' => 'admin',   'email' => 'demo@mekaneselamss.com'],
    ['role' => 'teacher', 'email' => 'teacher@demo.mekaneselamss.com'],
    ['role' => 'student', 'email' => 'student@demo.mekaneselamss.com'],
    ['role' => 'parent',  'email' => 'parent@demo.mekaneselamss.com'],
    ['role' => 'staff',   'email' => 'staff@demo.mekaneselamss.com'],
];

$out = [];
$uids = [];
foreach ($accounts as $a) {
    $role = $a['role'];
    if (!isset($roles[$role])) { $out[] = ['role' => $role, 'error' => 'role not seeded']; continue; }
    $uid = demo_upsert_user($pdo, $a['email'], $hash, $roles[$role]);

    if ($role === 'teacher') {
        $c = $pdo->prepare("SELECT id FROM teachers WHERE user_id=?"); $c->execute([$uid]);
        if (!$c->fetch()) $pdo->prepare("INSERT INTO teachers (user_id, first_name, last_name) VALUES (?,?,?)")->execute([$uid, 'Demo', 'Teacher']);
    } elseif ($role === 'student') {
        $c = $pdo->prepare("SELECT id FROM students WHERE user_id=?"); $c->execute([$uid]);
        if (!$c->fetch()) $pdo->prepare("INSERT INTO students (user_id, first_name, last_name) VALUES (?,?,?)")->execute([$uid, 'Demo', 'Student']);
    } elseif ($role === 'staff') {
        $pc = $pdo->prepare("SELECT id FROM people WHERE user_id=?"); $pc->execute([$uid]);
        $prow = $pc->fetch();
        $pid = $prow ? (int)$prow['id'] : null;
        if (!$pid) { $pdo->prepare("INSERT INTO people (user_id, first_name, last_name) VALUES (?,?,?)")->execute([$uid, 'Demo', 'Staff']); $pid = (int)$pdo->lastInsertId(); }
        $deptId = (int)($pdo->query("SELECT id FROM departments WHERE is_archived=0 ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
        if ($deptId) {
            $mc = $pdo->prepare("SELECT id FROM department_memberships WHERE person_id=? AND department_id=?");
            $mc->execute([$pid, $deptId]);
            if ($mc->fetch()) $pdo->prepare("UPDATE department_memberships SET is_head=1, is_archived=0 WHERE person_id=? AND department_id=?")->execute([$pid, $deptId]);
            else $pdo->prepare("INSERT INTO department_memberships (person_id, department_id, is_head, title) VALUES (?,?,1,'Head')")->execute([$pid, $deptId]);
        }
    }
    $uids[$role] = $uid;
    $out[] = ['role' => $role, 'email' => $a['email'], 'password' => $PASS];
}

// ---- Wire demo teacher <-> class <-> demo student so both portals show data ----
// Idempotent; only CREATES a term/class when none exist (never mutates real rows).
$wiring = [];
if (isset($uids['teacher'], $uids['student'])) {
    $tRow = $pdo->prepare("SELECT id FROM teachers WHERE user_id=? LIMIT 1");
    $tRow->execute([$uids['teacher']]);
    $teacherId = (int)$tRow->fetchColumn();
    $sRow = $pdo->prepare("SELECT id, person_id FROM students WHERE user_id=? LIMIT 1");
    $sRow->execute([$uids['student']]);
    $stu = $sRow->fetch();
    $studentId = $stu ? (int)$stu['id'] : 0;

    if ($teacherId && $studentId) {
        // 1) A term (teacher portal pickers need one).
        $termId = (int)($pdo->query("SELECT id FROM academic_terms WHERE is_archived=0 ORDER BY is_current DESC, start_date DESC LIMIT 1")->fetchColumn() ?: 0);
        if (!$termId) {
            $yr = date('Y');
            $pdo->prepare("INSERT INTO academic_terms (name, academic_year, start_date, end_date, is_current) VALUES ('Demo Term', ?, ?, ?, 1)")
                ->execute([$yr, "$yr-01-01", "$yr-12-31"]);
            $termId = (int)$pdo->lastInsertId();
            $wiring[] = 'created demo term';
        }
        // 2) A class + subject.
        $classId = (int)($pdo->query("SELECT id FROM classes WHERE is_archived=0 ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
        if (!$classId) {
            $levelId = (int)($pdo->query("SELECT id FROM class_levels WHERE is_archived=0 ORDER BY sort_order, id LIMIT 1")->fetchColumn() ?: 0);
            if ($levelId) {
                $pdo->prepare("INSERT INTO classes (level_id, academic_year, name) VALUES (?, ?, 'Demo Class')")
                    ->execute([$levelId, date('Y')]);
                $classId = (int)$pdo->lastInsertId();
                $wiring[] = 'created demo class';
            }
        }
        $subjectId = (int)($pdo->query("SELECT id FROM subjects WHERE is_archived=0 ORDER BY id LIMIT 1")->fetchColumn() ?: 0);

        if ($classId && $subjectId) {
            // 3) Teacher assignment (unarchive/refresh if it already exists).
            $ac = $pdo->prepare("SELECT id FROM teacher_subject_assignments WHERE teacher_id=? AND class_id=? AND subject_id=? LIMIT 1");
            $ac->execute([$teacherId, $classId, $subjectId]);
            if ($aid = $ac->fetchColumn()) {
                $pdo->prepare("UPDATE teacher_subject_assignments SET is_archived=0, end_date=NULL WHERE id=?")->execute([(int)$aid]);
            } else {
                $pdo->prepare("INSERT INTO teacher_subject_assignments (teacher_id, class_id, subject_id, start_date) VALUES (?,?,?,CURDATE())")
                    ->execute([$teacherId, $classId, $subjectId]);
            }
            // 4) Student person profile (attendance is person-based).
            if (empty($stu['person_id'])) {
                $pc = $pdo->prepare("SELECT id FROM people WHERE user_id=? LIMIT 1");
                $pc->execute([$uids['student']]);
                $personId = (int)($pc->fetchColumn() ?: 0);
                if (!$personId) {
                    $pdo->prepare("INSERT INTO people (user_id, first_name, last_name) VALUES (?,?,?)")
                        ->execute([$uids['student'], 'Demo', 'Student']);
                    $personId = (int)$pdo->lastInsertId();
                }
                $pdo->prepare("UPDATE students SET person_id=? WHERE id=?")->execute([$personId, $studentId]);
                $wiring[] = 'linked student person profile';
            }
            // 5) Enroll the demo student in the class.
            $ec = $pdo->prepare("SELECT id FROM student_class_assignments WHERE student_id=? AND class_id=? LIMIT 1");
            $ec->execute([$studentId, $classId]);
            if ($eid = $ec->fetchColumn()) {
                $pdo->prepare("UPDATE student_class_assignments SET is_archived=0, ended_at=NULL WHERE id=?")->execute([(int)$eid]);
            } else {
                $pdo->prepare("INSERT INTO student_class_assignments (student_id, class_id, assigned_at) VALUES (?,?,CURDATE())")
                    ->execute([$studentId, $classId]);
            }
            // 6) One sample grade so the grades tab isn't empty (never overwrites).
            $pdo->prepare("INSERT INTO grades (student_id, subject_id, class_id, term_id, score, remarks)
                           VALUES (?,?,?,?,85.00,'Demo grade')
                           ON DUPLICATE KEY UPDATE score=score")
                ->execute([$studentId, $subjectId, $classId, $termId]);
            $wiring[] = "teacher assigned to class $classId / subject $subjectId; student enrolled";
        } else {
            $wiring[] = 'skipped: no class/subject available (run migrations 013 first)';
        }
    }
}

Response::json(['message' => 'Demo logins ready', 'accounts' => $out, 'wiring' => $wiring]);
