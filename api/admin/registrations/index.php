<?php
// api/admin/registrations/index.php — admin CRUD for registration forms, fields
// and submissions (full scope). Delegates the real work to registrations_lib.php.
//
// GET  ?resource=forms[&include_archived=1]                      -> forms + fields + counts
// GET  ?resource=submissions&form_id=&page=&status=             -> paginated submissions
// POST { action, ... }  actions:
//   form.create | form.update | form.archive | form.unarchive
//   field.create | field.update | field.archive | field.reorder
//   submission.status | submission.archive

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../registrations_lib.php';
require_csrf_for_write();

$config = app_config();
$pdo = (new Database($config['db']))->pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Admin => full scope.
$scope = null;

if ($method === 'GET') {
    $resource = $_GET['resource'] ?? 'forms';
    if ($resource === 'submissions') {
        $formId = (int)($_GET['form_id'] ?? 0);
        reg_require_form($pdo, $formId, $scope);
        $page = (int)($_GET['page'] ?? 1);
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : null;
        Response::json(reg_list_submissions($pdo, $formId, $page, $status));
    }
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    Response::json(['data' => reg_list_forms($pdo, $scope, $includeArchived)]);
}

if ($method === 'POST') {
    $in = reg_body();
    $action = (string)($in['action'] ?? '');
    switch ($action) {
        case 'form.create':
            $id = reg_create_form($pdo, $in, $scope);
            \App\Audit::log('registration.form.create', 'registration_form', $id);
            Response::json(['data' => ['id' => $id]], 201);
        case 'form.update':
            reg_update_form($pdo, $in, $scope);
            \App\Audit::log('registration.form.update', 'registration_form', (int)($in['id'] ?? 0));
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
