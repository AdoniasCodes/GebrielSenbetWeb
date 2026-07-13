<?php
// api/staff/registrations.php — department-head view of registration forms.
// Scoped to the departments the caller heads. Heads can customize fields and
// status of their forms; they cannot CREATE forms (admin-only until event-linked
// registrations exist) and cannot reassign a form's department_id (enforced in
// registrations_lib.php).
//
// GET  ?resource=forms                                          -> forms in headed depts
// GET  ?resource=submissions&form_id=&page=&status=            -> submissions (scope-checked)
// POST { action, ... }  (same actions as the admin endpoint)

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../registrations_lib.php';
require_csrf_for_write();

$pdo = $GLOBALS['__staff_pdo'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Scope = departments this user heads (admin => all dept ids). Never null here,
// so dept-less forms are invisible to staff and cross-dept access is denied.
$scope = staff_headed_department_ids();

if ($method === 'GET') {
    $resource = $_GET['resource'] ?? 'forms';
    if ($resource === 'submissions') {
        $formId = (int)($_GET['form_id'] ?? 0);
        reg_require_form($pdo, $formId, $scope);
        $page = (int)($_GET['page'] ?? 1);
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : null;
        Response::json(reg_list_submissions($pdo, $formId, $page, $status));
    }
    Response::json(['data' => reg_list_forms($pdo, $scope, false)]);
}

if ($method === 'POST') {
    $in = reg_body();
    $action = (string)($in['action'] ?? '');
    switch ($action) {
        case 'form.create':
            // Standalone form creation is admin-only (see SYSTEM_AUDIT_AND_BLUEPRINT.md §4.2).
            Response::error('Form creation is admin-only', 403);
        case 'form.update':
            reg_update_form($pdo, $in, $scope);
            Response::json(['data' => ['ok' => true]]);
        case 'form.archive':
            reg_set_form_archived($pdo, $in, $scope, true);
            Response::json(['data' => ['ok' => true]]);
        case 'form.unarchive':
            reg_set_form_archived($pdo, $in, $scope, false);
            Response::json(['data' => ['ok' => true]]);
        case 'field.create':
            $id = reg_create_field($pdo, $in, $scope);
            Response::json(['data' => ['id' => $id]], 201);
        case 'field.update':
            reg_update_field($pdo, $in, $scope);
            Response::json(['data' => ['ok' => true]]);
        case 'field.archive':
            reg_set_field_archived($pdo, $in, $scope, true);
            Response::json(['data' => ['ok' => true]]);
        case 'field.reorder':
            reg_reorder_fields($pdo, $in, $scope);
            Response::json(['data' => ['ok' => true]]);
        case 'submission.status':
            reg_set_submission_status($pdo, $in, $scope);
            Response::json(['data' => ['ok' => true]]);
        case 'submission.archive':
            reg_archive_submission($pdo, $in, $scope);
            Response::json(['data' => ['ok' => true]]);
        default:
            Response::error('Unknown action', 422);
    }
}

Response::error('Method not allowed', 405);
