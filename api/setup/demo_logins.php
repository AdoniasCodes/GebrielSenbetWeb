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
    $out[] = ['role' => $role, 'email' => $a['email'], 'password' => $PASS];
}

Response::json(['message' => 'Demo logins ready', 'accounts' => $out]);
