<?php
// api/registrations/submit.php — public submission of a registration form.
// POST JSON: { form_id, answers: { <field_id>: value|array }, website: "" }
//   - CSRF required (X-CSRF-Token header vs session token, minted by /api/auth/csrf.php)
//   - "website" is a honeypot: if filled, we return success WITHOUT inserting.
//   - Server-side validates required/format/options and stores a labels snapshot.
// Success: { "data": { "ok": true } }

use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    Response::error('Method not allowed', 405);
}
if (!Csrf::validate($config['app']['csrf_header'])) {
    Response::error('Invalid or missing security token / የደህንነት ኮድ ትክክል አይደለም', 403);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) Response::error('Invalid request body / የተሳሳተ መረጃ', 422);

// Honeypot: bots fill hidden fields. Pretend success, insert nothing.
if (isset($in['website']) && trim((string)$in['website']) !== '') {
    Response::json(['data' => ['ok' => true]]);
}

$formId = (int)($in['form_id'] ?? 0);
$answers = $in['answers'] ?? [];
if ($formId <= 0) Response::error('form_id is required / ቅጽ ይምረጡ', 422);
if (!is_array($answers)) Response::error('answers must be an object / መልሶች ትክክል አይደሉም', 422);

$pdo = (new Database($config['db']))->pdo();

$fstmt = $pdo->prepare(
    'SELECT id, slug, title_en, status, is_archived FROM registration_forms WHERE id = ? LIMIT 1'
);
$fstmt->execute([$formId]);
$form = $fstmt->fetch();
if (!$form || (int)$form['is_archived'] === 1) {
    Response::error('This registration form is not available / ይህ የምዝገባ ቅጽ አይገኝም', 404);
}
if ($form['status'] === 'closed') {
    Response::error('Registration for this activity is closed / የዚህ ተግባር ምዝገባ ተዘግቷል', 409);
}

// Flood guard: max 5 submissions per IP per form per hour.
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
if ($ip !== null) {
    $flood = $pdo->prepare(
        'SELECT COUNT(*) FROM registration_submissions
         WHERE form_id = ? AND submitted_ip = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)'
    );
    $flood->execute([$formId, $ip]);
    if ((int)$flood->fetchColumn() >= 5) {
        Response::error('Too many submissions. Please try again later / በጣም ብዙ ምዝገባዎች። እባክዎ ቆይተው ይሞክሩ', 429);
    }
}

$fldStmt = $pdo->prepare(
    'SELECT id, label_en, label_am, field_type, options_json, is_required
     FROM registration_form_fields
     WHERE form_id = ? AND is_archived = 0
     ORDER BY sort_order ASC, id ASC'
);
$fldStmt->execute([$formId]);
$fields = $fldStmt->fetchAll();

$MAX_LONG = 2000;   // textarea
$MAX_SHORT = 300;   // everything else

$clean = [];        // field_id => stored value (string|array)
$snapshot = [];     // field_id => { label_en, label_am, type }
$applicantName = null;
$applicantPhone = null;

foreach ($fields as $f) {
    $fid = (int)$f['id'];
    $type = $f['field_type'];
    $required = ((int)$f['is_required']) === 1;
    $raw = $answers[$fid] ?? ($answers[(string)$fid] ?? null);

    // Allowed option values for choice fields.
    $optValues = [];
    if (in_array($type, ['select','radio','checkbox'], true) && $f['options_json']) {
        $decoded = json_decode($f['options_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $o) {
                if (isset($o['value'])) $optValues[] = (string)$o['value'];
            }
        }
    }

    $isEmpty = $raw === null || (is_string($raw) && trim($raw) === '') || (is_array($raw) && count($raw) === 0);

    if ($isEmpty) {
        if ($required) {
            Response::error('Please fill in: ' . $f['label_en'] . ' / እባክዎ ይሙሉ፡ ' . ($f['label_am'] ?: $f['label_en']), 422);
        }
        $snapshot[$fid] = ['label_en' => $f['label_en'], 'label_am' => $f['label_am'], 'type' => $type];
        continue;
    }

    $value = null;
    switch ($type) {
        case 'checkbox':
            $arr = is_array($raw) ? $raw : [$raw];
            $vals = [];
            foreach ($arr as $item) {
                $s = trim((string)$item);
                if ($s === '') continue;
                if ($optValues && !in_array($s, $optValues, true)) {
                    Response::error('Invalid selection for: ' . $f['label_en'] . ' / የተሳሳተ ምርጫ', 422);
                }
                if (mb_strlen($s) > $MAX_SHORT) Response::error('Answer too long for: ' . $f['label_en'], 422);
                $vals[] = $s;
            }
            $value = $vals;
            break;

        case 'select':
        case 'radio':
            $s = trim((string)(is_array($raw) ? '' : $raw));
            if ($optValues && !in_array($s, $optValues, true)) {
                Response::error('Invalid selection for: ' . $f['label_en'] . ' / የተሳሳተ ምርጫ', 422);
            }
            $value = $s;
            break;

        case 'email':
            $s = trim((string)$raw);
            if (mb_strlen($s) > $MAX_SHORT || !filter_var($s, FILTER_VALIDATE_EMAIL)) {
                Response::error('Please enter a valid email for: ' . $f['label_en'] . ' / ትክክለኛ ኢሜይል ያስገቡ', 422);
            }
            $value = $s;
            break;

        case 'number':
            $s = trim((string)$raw);
            if (!is_numeric($s)) {
                Response::error('Please enter a number for: ' . $f['label_en'] . ' / ቁጥር ያስገቡ', 422);
            }
            $value = $s;
            break;

        case 'date':
            $s = trim((string)$raw);
            $d = \DateTime::createFromFormat('Y-m-d', $s);
            if (!$d || $d->format('Y-m-d') !== $s) {
                Response::error('Please enter a valid date for: ' . $f['label_en'] . ' / ትክክለኛ ቀን ያስገቡ', 422);
            }
            $value = $s;
            break;

        case 'phone':
            $s = trim((string)$raw);
            if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $s)) {
                Response::error('Please enter a valid phone number for: ' . $f['label_en'] . ' / ትክክለኛ ስልክ ቁጥር ያስገቡ', 422);
            }
            $value = $s;
            if ($applicantPhone === null) $applicantPhone = $s;
            break;

        case 'textarea':
            $s = trim((string)$raw);
            if (mb_strlen($s) > $MAX_LONG) Response::error('Answer too long for: ' . $f['label_en'], 422);
            $value = $s;
            break;

        case 'text':
        default:
            $s = trim((string)$raw);
            if (mb_strlen($s) > $MAX_SHORT) Response::error('Answer too long for: ' . $f['label_en'], 422);
            $value = $s;
            if ($applicantName === null && stripos($f['label_en'], 'name') !== false) {
                $applicantName = $s;
            }
            break;
    }

    $clean[$fid] = $value;
    $snapshot[$fid] = ['label_en' => $f['label_en'], 'label_am' => $f['label_am'], 'type' => $type];
}

$answersJson = json_encode($clean, JSON_UNESCAPED_UNICODE);
$labelsJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

$ins = $pdo->prepare(
    'INSERT INTO registration_submissions
        (form_id, answers_json, labels_snapshot_json, applicant_name, applicant_phone, submitted_ip)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$ins->execute([
    $formId,
    $answersJson,
    $labelsJson,
    $applicantName !== null ? mb_substr($applicantName, 0, 200) : null,
    $applicantPhone !== null ? mb_substr($applicantPhone, 0, 60) : null,
    $ip,
]);

\App\Audit::log('registration.submit', 'registration_submission', (int)$pdo->lastInsertId(), ['form_slug' => $form['slug']]);

Response::json(['data' => ['ok' => true]]);
