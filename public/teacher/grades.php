<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'teacher') {
  header('Location: /');
  exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Teacher - Grades</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:0}
    header{background:#2c3e50;color:#fff;padding:16px}
    main{padding:16px;max-width:1100px;margin:0 auto}
    .card{border:1px solid #e3e3e3;border-radius:8px;padding:16px;margin-bottom:16px}
    .row{display:flex;gap:8px;flex-wrap:wrap}
    input,select,button{padding:8px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    th{background:#f5f5f5}
    .muted{color:#666}
  </style>
</head>
<body>
<header><h1>Teacher - Grades</h1></header>
<main>
  <nav>
    <a href="/teacher/index.php">← Back to Teacher Dashboard</a>
  </nav>

  <div class="card">
    <h2>Select Class / Subject / Term</h2>
    <div class="row">
      <select id="assignmentSel"></select>
      <select id="termSel"></select>
      <button id="loadBtn">Load</button>
    </div>
    <div class="muted" id="msg"></div>
  </div>

  <div class="card">
    <h2>Students and Grades</h2>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Score</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="gradesBody"></tbody>
    </table>
  </div>
</main>
<script>
  async function ensureCsrf(){
    let t = sessionStorage.getItem('csrf_token');
    if(!t){ const r = await fetch('/api/auth/csrf.php'); const d = await r.json(); t = d.csrf_token; sessionStorage.setItem('csrf_token', t); }
    return t;
  }

  const assignmentSel = document.getElementById('assignmentSel');
  const termSel = document.getElementById('termSel');
  const gradesBody = document.getElementById('gradesBody');
  const msg = document.getElementById('msg');

  async function loadAssignments(){
    const r = await fetch('/api/teacher/assignments/list.php?active_only=1');
    const d = await r.json();
    assignmentSel.innerHTML='';
    d.data.forEach(a => {
      const o = document.createElement('option');
      o.value = JSON.stringify({ class_id: a.class_id, subject_id: a.subject_id });
      o.textContent = `${a.academic_year} / ${a.track_name} / ${a.level_name} / ${a.class_name} / ${a.subject_name} (${a.role})`;
      assignmentSel.appendChild(o);
    });
  }

  async function loadTerms(){
    const r = await fetch('/api/terms/index.php');
    const d = await r.json();
    termSel.innerHTML='';
    d.data.forEach(t => {
      const o = document.createElement('option'); o.value = t.id; o.textContent = `${t.academic_year} - ${t.name}`; termSel.appendChild(o);
    });
  }

  async function loadStudentsAndGrades(){
    gradesBody.innerHTML=''; msg.textContent='';
    if(!assignmentSel.value || !termSel.value){ msg.textContent='Select an assignment and term.'; return; }
    const sel = JSON.parse(assignmentSel.value);
    const class_id = sel.class_id; const subject_id = sel.subject_id; const term_id = parseInt(termSel.value,10);

    // Load students for class
    const rs = await fetch(`/api/teacher/classes/students.php?class_id=${class_id}`);
    const ds = await rs.json();
    const students = ds.data || [];

    // Load existing grades for this class/subject/term
    const rg = await fetch(`/api/teacher/grades/index.php?class_id=${class_id}&subject_id=${subject_id}&term_id=${term_id}`);
    const dg = await rg.json();
    const grades = {}; (dg.data||[]).forEach(g => { grades[g.student_id] = g; });

    students.forEach(st => {
      const g = grades[st.id];
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${st.first_name} ${st.last_name}</td>
        <td><input type="number" step="0.01" class="scoreInput" value="${g?g.score:''}"></td>
        <td><input type="text" class="remarksInput" value="${g? (g.remarks||'') : ''}"></td>
        <td>
          ${g ? `<button class="saveGrade" data-id="${g.id}" data-student-id="${st.id}">Update</button>`
               : `<button class="createGrade" data-student-id="${st.id}">Create</button>`}
        </td>`;
      tr.dataset.studentId = st.id;
      tr.dataset.classId = class_id;
      tr.dataset.subjectId = subject_id;
      tr.dataset.termId = term_id;
      gradesBody.appendChild(tr);
    });
  }

  document.getElementById('loadBtn').addEventListener('click', loadStudentsAndGrades);

  gradesBody.addEventListener('click', async (e)=>{
    const row = e.target.closest('tr'); if(!row) return;
    const student_id = parseInt(row.dataset.studentId,10);
    const class_id = parseInt(row.dataset.classId,10);
    const subject_id = parseInt(row.dataset.subjectId,10);
    const term_id = parseInt(row.dataset.termId,10);
    const score = parseFloat(row.querySelector('.scoreInput').value);
    const remarks = row.querySelector('.remarksInput').value.trim();
    const token = await ensureCsrf();

    if(e.target.classList.contains('createGrade')){
      const res = await fetch('/api/teacher/grades/index.php', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ student_id, class_id, subject_id, term_id, score, remarks })});
      const d = await res.json(); if(!res.ok){ alert(d.error||'Failed'); return; }
      await loadStudentsAndGrades();
    }
    if(e.target.classList.contains('saveGrade')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const res = await fetch('/api/teacher/grades/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, score, remarks })});
      const d = await res.json(); if(!res.ok){ alert(d.error||'Failed'); return; }
      await loadStudentsAndGrades();
    }
  });

  (async function init(){ await Promise.all([loadAssignments(), loadTerms()]); })();
</script>
</body>
</html>
