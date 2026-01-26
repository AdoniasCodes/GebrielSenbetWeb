<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'student') {
  header('Location: /');
  exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Grades</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:0}
    header{background:#2c3e50;color:#fff;padding:16px}
    main{padding:16px;max-width:900px;margin:0 auto}
    .card{border:1px solid #e3e3e3;border-radius:8px;padding:16px;margin-bottom:16px}
    .row{display:flex;gap:8px;flex-wrap:wrap}
    select{padding:8px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    th{background:#f5f5f5}
  </style>
</head>
<body>
<header><h1>My Grades</h1></header>
<main>
  <div class="card">
    <div class="row">
      <label for="termSel">Term:</label>
      <select id="termSel"></select>
      <button id="loadBtn">Load</button>
    </div>
  </div>
  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Subject</th>
          <th>Score</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody id="gradesBody"></tbody>
    </table>
  </div>
</main>
<script>
  async function loadTerms(){
    const r = await fetch('/api/terms/index.php');
    const d = await r.json();
    const sel = document.getElementById('termSel'); sel.innerHTML='';
    d.data.forEach(t=>{ const o=document.createElement('option'); o.value=t.id; o.textContent=`${t.academic_year} - ${t.name}`; sel.appendChild(o); });
  }
  async function loadGrades(){
    const term_id = parseInt(document.getElementById('termSel').value,10) || 0;
    const url = term_id? `/api/student/grades/index.php?term_id=${term_id}` : '/api/student/grades/index.php';
    const r = await fetch(url); const d = await r.json();
    const body = document.getElementById('gradesBody'); body.innerHTML='';
    (d.data||[]).forEach(g=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${g.subject_name}</td><td>${g.score}</td><td>${g.remarks??''}</td>`;
      body.appendChild(tr);
    });
  }
  document.getElementById('loadBtn').addEventListener('click', loadGrades);
  (async function init(){ await loadTerms(); })();
</script>
</body>
</html>
