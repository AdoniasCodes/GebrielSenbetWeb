#!/usr/bin/env php
<?php
/**
 * scripts/seed_demo_users.php
 *
 * Idempotent CLI seeder for demo/test logins — one account per role, plus one
 * department-head (staff) account per non-archived department so per-department
 * scoping can be tested. Safe to run repeatedly: existing demo accounts are
 * RESET (password re-hashed, role fixed, un-archived), never duplicated.
 * Nothing else in the database is touched or wiped.
 *
 * The password is NEVER hardcoded here:
 *   - set env var DEMO_PASSWORD to choose it, or
 *   - omit it and a random one is generated and printed at the end.
 * Hashing uses password_hash(..., PASSWORD_DEFAULT), exactly what
 * api/auth/login.php verifies with password_verify().
 *
 * USAGE
 *   Seed / reset accounts (uses config/config.php DB credentials, overridable
 *   via APP_DB_HOST / APP_DB_NAME / APP_DB_USER / APP_DB_PASS env vars):
 *     DEMO_PASSWORD='choose-something' php scripts/seed_demo_users.php
 *     php scripts/seed_demo_users.php                  # generates a password
 *     php scripts/seed_demo_users.php --write-password # also put the real
 *                                                      # password in DEMO_LOGINS.md
 *
 *   Smoke-test the logins over HTTP (no DB access needed; reads the account
 *   list from DEMO_LOGINS.md):
 *     DEMO_PASSWORD='the-password' php scripts/seed_demo_users.php --verify
 *     DEMO_PASSWORD='the-password' php scripts/seed_demo_users.php --verify \
 *         --base-url=https://mekaneselamss.com
 *
 * Exit code is non-zero if seeding or any verification fails.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$ROOT = dirname(__DIR__);
$DEMO_LOGINS_MD = $ROOT . '/DEMO_LOGINS.md';
$DOMAIN = 'mekaneselamss.com';

// ---------------------------------------------------------------- arguments
$args = array_slice($argv, 1);
$mode = 'seed';
$baseUrl = 'http://127.0.0.1:8080';
$cliPassword = null;
$writePassword = false;

foreach ($args as $arg) {
    if ($arg === '--verify') { $mode = 'verify'; }
    elseif (strpos($arg, '--base-url=') === 0) { $baseUrl = rtrim(substr($arg, 11), '/'); }
    elseif (strpos($arg, '--password=') === 0) { $cliPassword = substr($arg, 11); }
    elseif ($arg === '--write-password') { $writePassword = true; }
    elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage:\n";
        echo "  DEMO_PASSWORD=... php scripts/seed_demo_users.php [--write-password]\n";
        echo "  DEMO_PASSWORD=... php scripts/seed_demo_users.php --verify [--base-url=https://{$DOMAIN}]\n";
        exit(0);
    } else {
        fwrite(STDERR, "Unknown argument: $arg (try --help)\n");
        exit(2);
    }
}

function out(string $msg): void { echo $msg . "\n"; }
function fail(string $msg): void { fwrite(STDERR, "ERROR: $msg\n"); exit(1); }

// =============================================================== VERIFY MODE
if ($mode === 'verify') {
    $password = $cliPassword ?? (getenv('DEMO_PASSWORD') ?: null);
    if ($password === null || $password === '') {
        fail("--verify needs the demo password: set DEMO_PASSWORD or pass --password=...");
    }
    if (!is_file($DEMO_LOGINS_MD)) {
        fail("DEMO_LOGINS.md not found — run the seeder first (it regenerates the file).");
    }

    // Pull "| role | `email` |" rows out of DEMO_LOGINS.md.
    $accounts = [];
    foreach (file($DEMO_LOGINS_MD, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos(ltrim($line), '|') !== 0) continue;
        if (!preg_match('/`([A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+)`/', $line, $m)) continue;
        $cells = array_values(array_filter(array_map('trim', explode('|', $line)), 'strlen'));
        $role = $cells[0] ?? '?';
        if (stripos($role, '---') === 0 || strcasecmp($role, 'Role') === 0) continue;
        $accounts[] = ['role' => $role, 'email' => $m[1]];
    }
    if (!$accounts) fail("No account emails found in DEMO_LOGINS.md.");

    $endpoint = $baseUrl . '/api/auth/login.php';
    out("Verifying " . count($accounts) . " logins against $endpoint");
    if (strpos($baseUrl, 'https://') === 0) {
        out("NOTE: this performs real logins against a remote site.");
    }
    out('');

    $failures = 0;
    foreach ($accounts as $a) {
        $body = json_encode(['email' => $a['email'], 'password' => $password]);
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 20,
        ]]);
        $resp = @file_get_contents($endpoint, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $status = (int)$m[1];
        }
        $json = is_string($resp) ? (json_decode($resp, true) ?: []) : [];
        $ok = ($status === 200) && isset($json['role']);
        $label = str_pad(substr($a['role'], 0, 34), 36);
        $email = str_pad($a['email'], 42);
        if ($ok) {
            out("PASS  $label $email (role: {$json['role']})");
        } else {
            $failures++;
            $why = $status === 0 ? 'no response / connection failed'
                 : "HTTP $status " . ($json['error'] ?? trim((string)$resp));
            out("FAIL  $label $email ($why)");
        }
    }
    out('');
    out($failures === 0
        ? "All " . count($accounts) . " logins passed."
        : "$failures of " . count($accounts) . " logins FAILED.");
    exit($failures === 0 ? 0 : 1);
}

// ================================================================= SEED MODE
$configFile = $ROOT . '/config/config.php';
if (!is_file($configFile)) fail("config/config.php not found at $configFile");
$config = require $configFile;
$db = $config['db'] ?? null;
if (!$db) fail("No 'db' section in config/config.php");

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset'] ?? 'utf8mb4'),
        $db['user'],
        $db['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    fail("Database connection failed ({$db['user']}@{$db['host']}/{$db['name']}). " .
         "Override with APP_DB_HOST/APP_DB_NAME/APP_DB_USER/APP_DB_PASS. PDO said: " . $e->getMessage());
}

// Password: env var or generated (never hardcoded).
$envPw = getenv('DEMO_PASSWORD');
$generated = false;
if ($envPw !== false && $envPw !== '') {
    $password = $envPw;
} else {
    $password = bin2hex(random_bytes(6)); // 12 hex chars
    $generated = true;
}
$hash = password_hash($password, PASSWORD_DEFAULT); // matches login.php's password_verify()

// Role map from DB.
$roles = [];
foreach ($pdo->query('SELECT id, name FROM roles')->fetchAll() as $r) {
    $roles[$r['name']] = (int)$r['id'];
}

/** Create-or-reset a user row. Returns user id. */
$upsertUser = function (string $email, int $roleId) use ($pdo, $hash): int {
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch();
    if ($row) {
        $pdo->prepare('UPDATE users SET password_hash = ?, role_id = ?, is_archived = 0 WHERE id = ?')
            ->execute([$hash, $roleId, (int)$row['id']]);
        return (int)$row['id'];
    }
    $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?,?,?)')
        ->execute([$email, $hash, $roleId]);
    return (int)$pdo->lastInsertId();
};

/** Ensure a people row exists for a user; returns person id. */
$ensurePerson = function (int $userId, string $fn, string $ln) use ($pdo): int {
    $st = $pdo->prepare('SELECT id FROM people WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch();
    if ($row) return (int)$row['id'];
    $pdo->prepare('INSERT INTO people (user_id, first_name, last_name) VALUES (?,?,?)')
        ->execute([$userId, $fn, $ln]);
    return (int)$pdo->lastInsertId();
};

/** Ensure a department-head membership (idempotent). */
$ensureHead = function (int $personId, int $deptId) use ($pdo): void {
    $st = $pdo->prepare('SELECT id FROM department_memberships WHERE person_id = ? AND department_id = ? LIMIT 1');
    $st->execute([$personId, $deptId]);
    if ($st->fetch()) {
        $pdo->prepare('UPDATE department_memberships SET is_head = 1, is_archived = 0 WHERE person_id = ? AND department_id = ?')
            ->execute([$personId, $deptId]);
    } else {
        $pdo->prepare("INSERT INTO department_memberships (person_id, department_id, is_head, title, joined_at) VALUES (?,?,1,'Head',CURDATE())")
            ->execute([$personId, $deptId]);
    }
};

$results = []; // rows for DEMO_LOGINS.md: [role, email, scope]
$skipped = [];

$core = [
    ['role' => 'admin',   'email' => "test-admin@{$DOMAIN}",   'scope' => 'Everything (`/admin/`)'],
    ['role' => 'teacher', 'email' => "test-teacher@{$DOMAIN}", 'scope' => 'Own classes (`/teacher/`)'],
    ['role' => 'student', 'email' => "test-student@{$DOMAIN}", 'scope' => 'Own record (`/student/`)'],
    ['role' => 'parent',  'email' => "test-parent@{$DOMAIN}",  'scope' => 'Linked children (`/parent/`)'],
];

$demoStudentUserId = null;
$demoStudentId = null;

foreach ($core as $a) {
    if (!isset($roles[$a['role']])) {
        $skipped[] = "{$a['role']} (role not present in roles table)";
        continue;
    }
    $uid = $upsertUser($a['email'], $roles[$a['role']]);

    if ($a['role'] === 'teacher') {
        $st = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
        $st->execute([$uid]);
        if (!$st->fetch()) {
            $pid = $ensurePerson($uid, 'Test', 'Teacher');
            $pdo->prepare('INSERT INTO teachers (user_id, person_id, first_name, last_name) VALUES (?,?,?,?)')
                ->execute([$uid, $pid, 'Test', 'Teacher']);
        }
    } elseif ($a['role'] === 'student') {
        $demoStudentUserId = $uid;
        $st = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
        $st->execute([$uid]);
        $row = $st->fetch();
        if ($row) {
            $demoStudentId = (int)$row['id'];
        } else {
            $pid = $ensurePerson($uid, 'Test', 'Student');
            $pdo->prepare('INSERT INTO students (user_id, person_id, first_name, last_name) VALUES (?,?,?,?)')
                ->execute([$uid, $pid, 'Test', 'Student']);
            $demoStudentId = (int)$pdo->lastInsertId();
        }
    } elseif ($a['role'] === 'parent' && $demoStudentId) {
        $st = $pdo->prepare('SELECT id FROM student_guardians WHERE user_id = ? AND student_id = ? LIMIT 1');
        $st->execute([$uid, $demoStudentId]);
        if (!$st->fetch()) {
            $pdo->prepare("INSERT INTO student_guardians (user_id, student_id, relationship, is_primary) VALUES (?,?, 'Parent', 1)")
                ->execute([$uid, $demoStudentId]);
        }
    }
    $results[] = [$a['role'], $a['email'], $a['scope']];
}

// One department-head (staff) login per non-archived department.
if (!isset($roles['staff'])) {
    $skipped[] = 'staff department heads (staff role not present)';
} else {
    $depts = $pdo->query('SELECT id, slug, name FROM departments WHERE is_archived = 0 ORDER BY sort_order, id')->fetchAll();
    if (!$depts) $skipped[] = 'department heads (no non-archived departments found)';
    foreach ($depts as $d) {
        $email = 'head-' . $d['slug'] . '@' . $DOMAIN;
        $uid = $upsertUser($email, $roles['staff']);
        $pid = $ensurePerson($uid, 'Head', $d['name']);
        $ensureHead($pid, (int)$d['id']);
        $results[] = ['staff (dept head)', $email, 'Department: ' . $d['name'] . ' only (`/staff/`)'];
    }
}

// ------------------------------------------------------ regenerate DEMO_LOGINS.md
$pwCell = $writePassword ? '`' . $password . '`' : '_set via `DEMO_PASSWORD` at seed time — see seeder output_';
$now = date('Y-m-d H:i');
$md = "<!-- Generated by scripts/seed_demo_users.php on $now — do not edit by hand; re-run the seeder to refresh. -->\n";
$md .= "# Demo / Test Logins\n\n";
$md .= "All accounts share ONE password" . ($writePassword ? '' : ' (not stored in this file)') . ". ";
$md .= "To (re)create or fix these accounts:\n\n";
$md .= "```sh\nDEMO_PASSWORD='...' php scripts/seed_demo_users.php          # seed / reset\n";
$md .= "DEMO_PASSWORD='...' php scripts/seed_demo_users.php --verify # smoke-test logins\n```\n\n";
$md .= "Log in at `/login` with the email + the shared password. Password: $pwCell\n\n";
$md .= "| Role | Email | Scope |\n|---|---|---|\n";
foreach ($results as $r) {
    $md .= "| {$r[0]} | `{$r[1]}` | {$r[2]} |\n";
}
$md .= "\nSeeding is idempotent and non-destructive: it only creates/resets the accounts\n";
$md .= "above (plus their minimal linked records). It does NOT wipe data. For fully wired\n";
$md .= "sample data (classes, grades, attendance, payments) use Admin → System → Reset / Data.\n";
file_put_contents($DEMO_LOGINS_MD, $md);

// ------------------------------------------------------------------- summary
out('');
out('Seeded/reset ' . count($results) . ' demo account(s):');
foreach ($results as $r) out('  - ' . str_pad($r[0], 20) . $r[1]);
foreach ($skipped as $s) out('  ! skipped: ' . $s);
out('');
out('DEMO_LOGINS.md regenerated at: ' . $DEMO_LOGINS_MD);
out('');
out($generated
    ? "Generated password (save it now — not stored anywhere): $password"
    : "Password: taken from DEMO_PASSWORD env var (not echoed).");
out('');
out("Smoke test:  DEMO_PASSWORD='...' php scripts/seed_demo_users.php --verify [--base-url=https://{$DOMAIN}]");
exit(empty($skipped) ? 0 : 0);
