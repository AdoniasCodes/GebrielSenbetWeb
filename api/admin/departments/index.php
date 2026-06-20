<?php
// api/admin/departments/index.php — admin CRUD for departments & sub-departments.
// GET    /api/admin/departments?include_archived=   → flat list (parent_id gives the tree) + member_count
// POST   body: { name, name_am?, slug?, parent_id?, description?, sort_order? }
// PUT    body: { id, name?, name_am?, parent_id?, description?, sort_order? }
// DELETE body: { id }   (archives the department; refuses if it has active sub-departments)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

function _slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : ('dept-' . substr(md5($s . microtime()), 0, 6));
}

if ($method === 'GET') {
    $includeArc = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = "SELECT d.id, d.parent_id, d.slug, d.name, d.name_am, d.description, d.sort_order, d.is_archived,
                   p.name AS parent_name, p.name_am AS parent_name_am,
                   (SELECT COUNT(*) FROM department_memberships dm WHERE dm.department_id = d.id AND dm.is_archived = 0) AS member_count,
                   (SELECT COUNT(*) FROM department_levels dl WHERE dl.department_id = d.id AND dl.is_archived = 0) AS level_count
            FROM departments d
            LEFT JOIN departments p ON p.id = d.parent_id";
    if (!$includeArc) $sql .= ' WHERE d.is_archived = 0';
    $sql .= ' ORDER BY COALESCE(d.parent_id, d.id), d.parent_id IS NOT NULL, d.sort_order, d.id';
    Response::json(['data' => $pdo->query($sql)->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $name   = trim((string)($in['name'] ?? ''));
    $nameAm = isset($in['name_am']) && $in['name_am'] !== '' ? trim((string)$in['name_am']) : null;
    if ($name === '' && $nameAm === null) Response::error('name (or name_am) is required', 422);
    $slug = trim((string)($in['slug'] ?? '')) ?: _slugify($name !== '' ? $name : $nameAm);
    $parentId = (int)($in['parent_id'] ?? 0) ?: null;
    $desc = isset($in['description']) && $in['description'] !== '' ? trim((string)$in['description']) : null;
    $sort = (int)($in['sort_order'] ?? 0);

    if ($parentId) {
        $pc = $pdo->prepare('SELECT parent_id FROM departments WHERE id=? AND is_archived=0');
        $pc->execute([$parentId]);
        $prow = $pc->fetch();
        if (!$prow) Response::error('Parent department not found', 422);
        if ($prow['parent_id'] !== null) Response::error('Cannot nest more than one level deep', 422);
    }
    $dup = $pdo->prepare('SELECT id FROM departments WHERE slug=? LIMIT 1');
    $dup->execute([$slug]);
    if ($dup->fetch()) Response::error('A department with this slug already exists', 409);

    $ins = $pdo->prepare('INSERT INTO departments (parent_id, slug, name, name_am, description, sort_order) VALUES (?,?,?,?,?,?)');
    $ins->execute([$parentId, $slug, $name ?: ($nameAm ?? ''), $nameAm, $desc, $sort]);
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('department.create', 'department', $id, ['slug' => $slug]);
    Response::json(['ok' => true, 'id' => $id, 'slug' => $slug], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT id FROM departments WHERE id=? AND is_archived=0');
    $cur->execute([$id]);
    if (!$cur->fetch()) Response::error('Department not found', 404);

    $fields = []; $params = [];
    if (array_key_exists('name', $in))        { $fields[] = 'name=?';        $params[] = trim((string)$in['name']); }
    if (array_key_exists('name_am', $in))     { $fields[] = 'name_am=?';     $params[] = $in['name_am'] !== '' ? trim((string)$in['name_am']) : null; }
    if (array_key_exists('description', $in)) { $fields[] = 'description=?'; $params[] = $in['description'] !== '' ? trim((string)$in['description']) : null; }
    if (array_key_exists('sort_order', $in))  { $fields[] = 'sort_order=?';  $params[] = (int)$in['sort_order']; }
    if (array_key_exists('parent_id', $in)) {
        $parentId = (int)$in['parent_id'] ?: null;
        if ($parentId === $id) Response::error('A department cannot be its own parent', 422);
        if ($parentId) {
            $pc = $pdo->prepare('SELECT parent_id FROM departments WHERE id=? AND is_archived=0');
            $pc->execute([$parentId]);
            $prow = $pc->fetch();
            if (!$prow) Response::error('Parent department not found', 422);
            if ($prow['parent_id'] !== null) Response::error('Cannot nest more than one level deep', 422);
        }
        $fields[] = 'parent_id=?'; $params[] = $parentId;
    }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE departments SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('department.update', 'department', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $sub = $pdo->prepare('SELECT COUNT(*) AS c FROM departments WHERE parent_id=? AND is_archived=0');
    $sub->execute([$id]);
    if ((int)$sub->fetch()['c'] > 0) Response::error('Archive or move the sub-departments first', 409);
    $pdo->prepare('UPDATE departments SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE department_memberships SET is_archived=1, archived_at=NOW() WHERE department_id=? AND is_archived=0')->execute([$id]);
    \App\Audit::log('department.archive', 'department', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
