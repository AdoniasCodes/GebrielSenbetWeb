<?php
// api/registrations_lib.php — shared logic for the admin + staff registration
// endpoints. Each function takes a PDO and an optional $deptScope:
//   - null            => full access (admin): all forms, including dept-less ones.
//   - array<int>      => scoped access (staff): only forms whose department_id is
//                        in the array. An empty array means "no access".
// Functions call App\Utils\Response::error() (which exits) on invalid input or
// access denial, mirroring the rest of the codebase.

use App\Utils\Response;

const REG_FIELD_TYPES = ['text','textarea','email','phone','number','date','select','radio','checkbox'];
const REG_FORM_STATUSES = ['open','limited','closed'];
const REG_SUB_STATUSES = ['new','seen','contacted'];
const REG_CHOICE_TYPES = ['select','radio','checkbox'];
const REG_PAGE_SIZE = 25;

function reg_body(): array {
    $in = json_decode(file_get_contents('php://input'), true);
    return is_array($in) ? $in : [];
}

// Build a WHERE fragment restricting forms to the scope. $alias is the forms table alias.
function reg_scope_clause(?array $deptScope, string $alias = 'rf'): array {
    if ($deptScope === null) return ['1=1', []];
    if (count($deptScope) === 0) return ['1=0', []];
    $ph = implode(',', array_fill(0, count($deptScope), '?'));
    return ["$alias.department_id IN ($ph)", array_values($deptScope)];
}

// Fetch a form and enforce scope. Returns the form row (assoc) or exits with 403/404.
function reg_require_form(\PDO $pdo, int $formId, ?array $deptScope): array {
    if ($formId <= 0) Response::error('form id is required', 422);
    $stmt = $pdo->prepare('SELECT * FROM registration_forms WHERE id = ? LIMIT 1');
    $stmt->execute([$formId]);
    $form = $stmt->fetch();
    if (!$form) Response::error('Form not found', 404);
    if ($deptScope !== null && !in_array((int)$form['department_id'], $deptScope, true)) {
        Response::error('You do not manage this form', 403);
    }
    return $form;
}

function reg_fields_for_form(\PDO $pdo, int $formId, bool $includeArchived = false): array {
    $sql = 'SELECT id, form_id, label_en, label_am, field_type, options_json,
                   placeholder_en, placeholder_am, is_required, sort_order, is_archived
            FROM registration_form_fields WHERE form_id = ?';
    if (!$includeArchived) $sql .= ' AND is_archived = 0';
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$formId]);
    $out = [];
    foreach ($stmt->fetchAll() as $f) {
        $opts = null;
        if ($f['options_json'] !== null && $f['options_json'] !== '') {
            $d = json_decode($f['options_json'], true);
            if (is_array($d)) $opts = $d;
        }
        $out[] = [
            'id'             => (int)$f['id'],
            'form_id'        => (int)$f['form_id'],
            'label_en'       => $f['label_en'],
            'label_am'       => $f['label_am'],
            'field_type'     => $f['field_type'],
            'options'        => $opts,
            'placeholder_en' => $f['placeholder_en'],
            'placeholder_am' => $f['placeholder_am'],
            'is_required'    => (int)$f['is_required'],
            'sort_order'     => (int)$f['sort_order'],
            'is_archived'    => (int)$f['is_archived'],
        ];
    }
    return $out;
}

// List forms (with department label, submission count, and fields) within scope.
function reg_list_forms(\PDO $pdo, ?array $deptScope, bool $includeArchived = false): array {
    [$scopeSql, $scopeParams] = reg_scope_clause($deptScope, 'rf');
    $sql = "SELECT rf.id, rf.slug, rf.title_en, rf.title_am, rf.description_en, rf.description_am,
                   rf.department_id, rf.status, rf.sort_order, rf.is_archived, rf.created_at, rf.updated_at,
                   d.name AS department_name, d.name_am AS department_name_am,
                   (SELECT COUNT(*) FROM registration_submissions s WHERE s.form_id = rf.id AND s.is_archived = 0) AS submission_count,
                   (SELECT COUNT(*) FROM registration_submissions s WHERE s.form_id = rf.id AND s.is_archived = 0 AND s.status = 'new') AS new_count
            FROM registration_forms rf
            LEFT JOIN departments d ON d.id = rf.department_id
            WHERE $scopeSql";
    if (!$includeArchived) $sql .= ' AND rf.is_archived = 0';
    $sql .= ' ORDER BY rf.sort_order ASC, rf.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($scopeParams);
    $forms = $stmt->fetchAll();
    foreach ($forms as &$f) {
        $f['id'] = (int)$f['id'];
        $f['department_id'] = $f['department_id'] !== null ? (int)$f['department_id'] : null;
        $f['sort_order'] = (int)$f['sort_order'];
        $f['is_archived'] = (int)$f['is_archived'];
        $f['submission_count'] = (int)$f['submission_count'];
        $f['new_count'] = (int)$f['new_count'];
        $f['fields'] = reg_fields_for_form($pdo, $f['id'], false);
    }
    return $forms;
}

// Paginated submissions for a form (newest first). Each row includes an ordered
// items[] array combining the labels snapshot with the submitted values.
function reg_list_submissions(\PDO $pdo, int $formId, int $page, ?string $statusFilter): array {
    $page = max(1, $page);
    $offset = ($page - 1) * REG_PAGE_SIZE;

    $where = 'form_id = ? AND is_archived = 0';
    $params = [$formId];
    if ($statusFilter !== null && in_array($statusFilter, REG_SUB_STATUSES, true)) {
        $where .= ' AND status = ?';
        $params[] = $statusFilter;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registration_submissions WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT id, form_id, answers_json, labels_snapshot_json, applicant_name, applicant_phone,
                   status, created_at
            FROM registration_submissions
            WHERE $where
            ORDER BY created_at DESC, id DESC
            LIMIT " . (int)REG_PAGE_SIZE . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $answers = json_decode($r['answers_json'] ?? '', true);
        if (!is_array($answers)) $answers = [];
        $labels = json_decode($r['labels_snapshot_json'] ?? '', true);
        if (!is_array($labels)) $labels = [];

        $items = [];
        foreach ($labels as $fid => $meta) {
            $val = $answers[$fid] ?? null;
            $items[] = [
                'field_id' => (int)$fid,
                'label_en' => $meta['label_en'] ?? ('#' . $fid),
                'label_am' => $meta['label_am'] ?? null,
                'type'     => $meta['type'] ?? 'text',
                'value'    => is_array($val) ? implode(', ', array_map('strval', $val)) : ($val ?? ''),
            ];
        }
        $rows[] = [
            'id'              => (int)$r['id'],
            'form_id'         => (int)$r['form_id'],
            'applicant_name'  => $r['applicant_name'],
            'applicant_phone' => $r['applicant_phone'],
            'status'          => $r['status'],
            'created_at'      => $r['created_at'],
            'items'           => $items,
        ];
    }

    return ['data' => $rows, 'page' => $page, 'per_page' => REG_PAGE_SIZE, 'total' => $total];
}

// ---- Field option normalization ----
function reg_normalize_options($options): ?string {
    if ($options === null || $options === '') return null;
    if (is_string($options)) {
        $d = json_decode($options, true);
        $options = is_array($d) ? $d : null;
    }
    if (!is_array($options) || count($options) === 0) return null;
    $clean = [];
    foreach ($options as $o) {
        if (!is_array($o)) continue;
        $value = trim((string)($o['value'] ?? ''));
        if ($value === '') continue;
        $clean[] = [
            'value'    => mb_substr($value, 0, 120),
            'label_en' => mb_substr(trim((string)($o['label_en'] ?? $value)), 0, 200),
            'label_am' => mb_substr(trim((string)($o['label_am'] ?? '')), 0, 200),
        ];
    }
    return count($clean) ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
}

// ---- Form writes ----
function reg_create_form(\PDO $pdo, array $in, ?array $deptScope): int {
    $titleEn = trim((string)($in['title_en'] ?? ''));
    if ($titleEn === '') Response::error('title_en is required', 422);
    $status = in_array(($in['status'] ?? 'open'), REG_FORM_STATUSES, true) ? $in['status'] : 'open';
    $deptId = isset($in['department_id']) && $in['department_id'] !== '' && $in['department_id'] !== null
        ? (int)$in['department_id'] : null;
    // Scoped callers may only create forms inside a department they manage.
    if ($deptScope !== null) {
        if ($deptId === null || !in_array($deptId, $deptScope, true)) {
            Response::error('You may only create forms for a department you manage', 403);
        }
    }
    $slug = reg_slugify($in['slug'] ?? $titleEn);
    $slug = reg_unique_slug($pdo, $slug);

    $stmt = $pdo->prepare(
        'INSERT INTO registration_forms (slug, title_en, title_am, description_en, description_am, department_id, status, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $slug,
        mb_substr($titleEn, 0, 200),
        reg_nullable($in['title_am'] ?? null, 200),
        reg_nullable($in['description_en'] ?? null, 5000),
        reg_nullable($in['description_am'] ?? null, 5000),
        $deptId,
        $status,
        (int)($in['sort_order'] ?? 0),
    ]);
    return (int)$pdo->lastInsertId();
}

function reg_update_form(\PDO $pdo, array $in, ?array $deptScope): void {
    $id = (int)($in['id'] ?? 0);
    $form = reg_require_form($pdo, $id, $deptScope);

    $titleEn = trim((string)($in['title_en'] ?? $form['title_en']));
    if ($titleEn === '') Response::error('title_en is required', 422);
    $status = in_array(($in['status'] ?? $form['status']), REG_FORM_STATUSES, true) ? ($in['status'] ?? $form['status']) : $form['status'];

    // department_id: scoped callers can NEVER reassign it. Admins may.
    $deptId = $form['department_id'] !== null ? (int)$form['department_id'] : null;
    if ($deptScope === null && array_key_exists('department_id', $in)) {
        $deptId = ($in['department_id'] === '' || $in['department_id'] === null) ? null : (int)$in['department_id'];
    }

    $stmt = $pdo->prepare(
        'UPDATE registration_forms
         SET title_en = ?, title_am = ?, description_en = ?, description_am = ?, department_id = ?, status = ?, sort_order = ?
         WHERE id = ?'
    );
    $stmt->execute([
        mb_substr($titleEn, 0, 200),
        reg_nullable($in['title_am'] ?? $form['title_am'], 200),
        reg_nullable($in['description_en'] ?? $form['description_en'], 5000),
        reg_nullable($in['description_am'] ?? $form['description_am'], 5000),
        $deptId,
        $status,
        (int)($in['sort_order'] ?? $form['sort_order']),
        $id,
    ]);
}

function reg_set_form_archived(\PDO $pdo, array $in, ?array $deptScope, bool $archived): void {
    $id = (int)($in['id'] ?? 0);
    reg_require_form($pdo, $id, $deptScope);
    $stmt = $pdo->prepare('UPDATE registration_forms SET is_archived = ?, archived_at = ? WHERE id = ?');
    $stmt->execute([$archived ? 1 : 0, $archived ? date('Y-m-d H:i:s') : null, $id]);
}

// ---- Field writes ----
function reg_create_field(\PDO $pdo, array $in, ?array $deptScope): int {
    $formId = (int)($in['form_id'] ?? 0);
    reg_require_form($pdo, $formId, $deptScope);

    $labelEn = trim((string)($in['label_en'] ?? ''));
    if ($labelEn === '') Response::error('label_en is required', 422);
    $type = in_array(($in['field_type'] ?? 'text'), REG_FIELD_TYPES, true) ? $in['field_type'] : 'text';
    $optionsJson = in_array($type, REG_CHOICE_TYPES, true) ? reg_normalize_options($in['options'] ?? null) : null;
    if (in_array($type, REG_CHOICE_TYPES, true) && $optionsJson === null) {
        Response::error('This field type needs at least one option', 422);
    }

    // Default sort_order to end of list when not provided.
    $sortOrder = isset($in['sort_order']) ? (int)$in['sort_order'] : null;
    if ($sortOrder === null) {
        $m = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM registration_form_fields WHERE form_id = ?');
        $m->execute([$formId]);
        $sortOrder = (int)$m->fetchColumn();
    }

    $stmt = $pdo->prepare(
        'INSERT INTO registration_form_fields
            (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $formId,
        mb_substr($labelEn, 0, 200),
        reg_nullable($in['label_am'] ?? null, 200),
        $type,
        $optionsJson,
        reg_nullable($in['placeholder_en'] ?? null, 200),
        reg_nullable($in['placeholder_am'] ?? null, 200),
        !empty($in['is_required']) ? 1 : 0,
        $sortOrder,
    ]);
    return (int)$pdo->lastInsertId();
}

function reg_require_field(\PDO $pdo, int $fieldId, ?array $deptScope): array {
    if ($fieldId <= 0) Response::error('field id is required', 422);
    $stmt = $pdo->prepare('SELECT * FROM registration_form_fields WHERE id = ? LIMIT 1');
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();
    if (!$field) Response::error('Field not found', 404);
    reg_require_form($pdo, (int)$field['form_id'], $deptScope);
    return $field;
}

function reg_update_field(\PDO $pdo, array $in, ?array $deptScope): void {
    $id = (int)($in['id'] ?? 0);
    $field = reg_require_field($pdo, $id, $deptScope);

    $labelEn = trim((string)($in['label_en'] ?? $field['label_en']));
    if ($labelEn === '') Response::error('label_en is required', 422);
    $type = in_array(($in['field_type'] ?? $field['field_type']), REG_FIELD_TYPES, true) ? ($in['field_type'] ?? $field['field_type']) : $field['field_type'];
    if (array_key_exists('options', $in) || in_array($type, REG_CHOICE_TYPES, true)) {
        $optionsJson = in_array($type, REG_CHOICE_TYPES, true)
            ? reg_normalize_options($in['options'] ?? $field['options_json'])
            : null;
    } else {
        $optionsJson = $field['options_json'];
    }
    if (in_array($type, REG_CHOICE_TYPES, true) && $optionsJson === null) {
        Response::error('This field type needs at least one option', 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE registration_form_fields
         SET label_en = ?, label_am = ?, field_type = ?, options_json = ?, placeholder_en = ?, placeholder_am = ?, is_required = ?, sort_order = ?
         WHERE id = ?'
    );
    $stmt->execute([
        mb_substr($labelEn, 0, 200),
        reg_nullable($in['label_am'] ?? $field['label_am'], 200),
        $type,
        $optionsJson,
        reg_nullable($in['placeholder_en'] ?? $field['placeholder_en'], 200),
        reg_nullable($in['placeholder_am'] ?? $field['placeholder_am'], 200),
        array_key_exists('is_required', $in) ? (!empty($in['is_required']) ? 1 : 0) : (int)$field['is_required'],
        array_key_exists('sort_order', $in) ? (int)$in['sort_order'] : (int)$field['sort_order'],
        $id,
    ]);
}

function reg_set_field_archived(\PDO $pdo, array $in, ?array $deptScope, bool $archived): void {
    $id = (int)($in['id'] ?? 0);
    reg_require_field($pdo, $id, $deptScope);
    $stmt = $pdo->prepare('UPDATE registration_form_fields SET is_archived = ?, archived_at = ? WHERE id = ?');
    $stmt->execute([$archived ? 1 : 0, $archived ? date('Y-m-d H:i:s') : null, $id]);
}

function reg_reorder_fields(\PDO $pdo, array $in, ?array $deptScope): void {
    $formId = (int)($in['form_id'] ?? 0);
    reg_require_form($pdo, $formId, $deptScope);
    $order = $in['order'] ?? [];
    if (!is_array($order) || count($order) === 0) Response::error('order must be a non-empty array of field ids', 422);
    $upd = $pdo->prepare('UPDATE registration_form_fields SET sort_order = ? WHERE id = ? AND form_id = ?');
    $i = 10;
    foreach ($order as $fid) {
        $upd->execute([$i, (int)$fid, $formId]);
        $i += 10;
    }
}

// ---- Submission writes ----
function reg_set_submission_status(\PDO $pdo, array $in, ?array $deptScope): void {
    $id = (int)($in['id'] ?? 0);
    $status = (string)($in['status'] ?? '');
    if (!in_array($status, REG_SUB_STATUSES, true)) Response::error('Invalid status', 422);
    $sub = reg_require_submission($pdo, $id, $deptScope);
    $stmt = $pdo->prepare('UPDATE registration_submissions SET status = ? WHERE id = ?');
    $stmt->execute([$status, (int)$sub['id']]);
}

function reg_archive_submission(\PDO $pdo, array $in, ?array $deptScope): void {
    $id = (int)($in['id'] ?? 0);
    $sub = reg_require_submission($pdo, $id, $deptScope);
    $stmt = $pdo->prepare('UPDATE registration_submissions SET is_archived = 1, archived_at = ? WHERE id = ?');
    $stmt->execute([date('Y-m-d H:i:s'), (int)$sub['id']]);
}

function reg_require_submission(\PDO $pdo, int $id, ?array $deptScope): array {
    if ($id <= 0) Response::error('submission id is required', 422);
    $stmt = $pdo->prepare('SELECT * FROM registration_submissions WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $sub = $stmt->fetch();
    if (!$sub) Response::error('Submission not found', 404);
    reg_require_form($pdo, (int)$sub['form_id'], $deptScope);
    return $sub;
}

// ---- helpers ----
function reg_nullable($v, int $max): ?string {
    if ($v === null) return null;
    $s = trim((string)$v);
    return $s === '' ? null : mb_substr($s, 0, $max);
}

function reg_slugify($s): string {
    $s = strtolower(trim((string)$s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    if ($s === '') $s = 'form';
    return mb_substr($s, 0, 70);
}

function reg_unique_slug(\PDO $pdo, string $base): string {
    $slug = $base;
    $n = 1;
    $chk = $pdo->prepare('SELECT 1 FROM registration_forms WHERE slug = ? LIMIT 1');
    while (true) {
        $chk->execute([$slug]);
        if (!$chk->fetch()) return $slug;
        $n++;
        $slug = mb_substr($base, 0, 66) . '-' . $n;
    }
}
