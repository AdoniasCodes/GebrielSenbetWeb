<?php
// api/registrations/index.php — public read-only feed of registration forms.
// GET only. No auth. FROZEN CONTRACT consumed by the landing page:
// { "data": [ { id, slug, title_en, title_am, description_en, description_am,
//              status, fields: [ { id, label_en, label_am, type, required(bool),
//              options(array|null), placeholder_en, placeholder_am, sort_order } ] } ] }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Method not allowed', 405);
}

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

$forms = $pdo->query(
    "SELECT id, slug, title_en, title_am, description_en, description_am, status
     FROM registration_forms
     WHERE is_archived = 0
     ORDER BY sort_order ASC, id ASC"
)->fetchAll();

$out = [];
if ($forms) {
    $ids = array_map(static fn($f) => (int)$f['id'], $forms);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $fstmt = $pdo->prepare(
        "SELECT id, form_id, label_en, label_am, field_type, options_json,
                placeholder_en, placeholder_am, is_required, sort_order
         FROM registration_form_fields
         WHERE form_id IN ($ph) AND is_archived = 0
         ORDER BY sort_order ASC, id ASC"
    );
    $fstmt->execute($ids);

    $byForm = [];
    foreach ($fstmt->fetchAll() as $f) {
        $opts = null;
        if ($f['options_json'] !== null && $f['options_json'] !== '') {
            $decoded = json_decode($f['options_json'], true);
            if (is_array($decoded)) $opts = $decoded;
        }
        $byForm[(int)$f['form_id']][] = [
            'id'             => (int)$f['id'],
            'label_en'       => $f['label_en'],
            'label_am'       => $f['label_am'],
            'type'           => $f['field_type'],
            'required'       => ((int)$f['is_required']) === 1,
            'options'        => $opts,
            'placeholder_en' => $f['placeholder_en'],
            'placeholder_am' => $f['placeholder_am'],
            'sort_order'     => (int)$f['sort_order'],
        ];
    }

    foreach ($forms as $form) {
        $fid = (int)$form['id'];
        $out[] = [
            'id'             => $fid,
            'slug'           => $form['slug'],
            'title_en'       => $form['title_en'],
            'title_am'       => $form['title_am'],
            'description_en' => $form['description_en'],
            'description_am' => $form['description_am'],
            'status'         => $form['status'],
            'fields'         => $byForm[$fid] ?? [],
        ];
    }
}

Response::json(['data' => $out]);
