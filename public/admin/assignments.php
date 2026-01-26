<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') {
  header('Location: /');
  exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Teacher Subject Assignments</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:0}
    header{background:#2c3e50;color:#fff;padding:16px}
    main{padding:16px;max-width:1200px;margin:0 auto}
    .grid{display:grid;grid-template-columns:1fr;gap:16px}
    .card{border:1px solid #e3e3e3;border-radius:8px;padding:16px}
    input,select,button{padding:8px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    th{background:#f5f5f5}
    .row{display:flex;gap:8px;flex-wrap:wrap}
    .muted{color:#666}
  </style>
</head>
<body>
<header><h1>Teacher Subject Assignments</h1></header>
<main>
  <nav>
    <a href="/admin/index.php">← Back to Dashboard</a>
  </nav>

  <div class="grid">
    <div class="card">
      <h2>Create Assignment</h2>
      <div class="row">
        <select id="teacherSel"></select>
        <select id="classSel"></select>
        <select id="subjectSel"></select>
        <select id="roleSel">
          <option value="primary">Primary</option>
          <option value="substitute">Substitute</option>
        </select>
        <input id="startDate" type="date" />
        <input id="endDate" type="date" placeholder="End date (optional)" />
        <button id="createBtn">Create</button>
      </div>
      <div id="createMsg" class="muted"></div>
    </div>

    <div class="card">
      <h2>Assignments</h2>
      <div class="row">
        <select id="filterTeacher"><option value="">All Teachers</option></select>
        <select id="filterClass"><option value="">All Classes</option></select>
        <select id="filterSubject"><option value="">All Subjects</option></select>
        <label><input type="checkbox" id="activeOnly" checked /> Active only</label>
        <button id="refreshBtn">Refresh</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Teacher</th><th>Class</th><th>Subject</th><th>Role</th><th>Start</th><th>End</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="assignBody"></tbody>
      </table>
    </div>
  </div>
</main>
<script>
  async function ensureCsrf(){
    let t = sessionStorage.getItem('csrf_token');
    if(!t){
      const r = await fetch('/api/auth/csrf.php');
      const d = await r.json();
      t = d.csrf_token; sessionStorage.setItem('csrf_token', t);
    }
    return t;
  }

  async function loadTeachers(q=''){
    const r = await fetch('/api/admin/teachers/list.php' + (q?`?q=${encodeURIComponent(q)}`:''));
    const d = await r.json();
    const tSel = document.getElementById('teacherSel');
    const ftSel = document.getElementById('filterTeacher');
    [tSel, ftSel].forEach(sel => sel.innerHTML='');
    d.data.forEach(t=>{
      const opt1 = document.createElement('option'); opt1.value = t.id; opt1.textContent = `${t.first_name} ${t.last_name} (${t.phone||''})`; tSel.appendChild(opt1);
      const opt2 = document.createElement('option'); opt2.value = t.id; opt2.textContent = `${t.first_name} ${t.last_name}`; ftSel.appendChild(opt2);
    });
  }

  async function loadSubjects(){
    const r = await fetch('/api/admin/subjects/index.php');
    const d = await r.json();
    const sSel = document.getElementById('subjectSel');
    const fsSel = document.getElementById('filterSubject');
    [sSel, fsSel].forEach(sel => sel.innerHTML='');
    d.data.forEach(s=>{
      const o1 = document.createElement('option'); o1.value = s.id; o1.textContent = s.name; sSel.appendChild(o1);
      const o2 = document.createElement('option'); o2.value = s.id; o2.textContent = s.name; fsSel.appendChild(o2);
    });
  }

  async function loadClasses(){
    const r = await fetch('/api/admin/classes/index.php');
    const d = await r.json();
    const cSel = document.getElementById('classSel');
    const fcSel = document.getElementById('filterClass');
    [cSel, fcSel].forEach(sel => sel.innerHTML='');
    d.data.forEach(c=>{
      const label = `${c.track_name} / ${c.level_name} / ${c.academic_year} / ${c.name}`;
      const o1 = document.createElement('option'); o1.value = c.id; o1.textContent = label; cSel.appendChild(o1);
      const o2 = document.createElement('option'); o2.value = c.id; o2.textContent = label; fcSel.appendChild(o2);
    });
  }

  async function loadAssignments(){
    const qs = [];
    const ft = document.getElementById('filterTeacher').value; if(ft) qs.push('teacher_id='+encodeURIComponent(ft));
    const fc = document.getElementById('filterClass').value; if(fc) qs.push('class_id='+encodeURIComponent(fc));
    const fs = document.getElementById('filterSubject').value; if(fs) qs.push('subject_id='+encodeURIComponent(fs));
    const ao = document.getElementById('activeOnly').checked; if(ao) qs.push('active_only=1');
    const url = '/api/admin/assignments/index.php' + (qs.length?('?'+qs.join('&')):'');
    const r = await fetch(url);
    const d = await r.json();
    const body = document.getElementById('assignBody'); body.innerHTML='';
    d.data.forEach(a=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${a.id}</td>
        <td>${a.teacher_first_name} ${a.teacher_last_name}</td>
        <td>${a.class_name}</td>
        <td>${a.subject_name}</td>
        <td>
          <select class="roleInp">
            <option value="primary" ${a.role==='primary'?'selected':''}>Primary</option>
            <option value="substitute" ${a.role==='substitute'?'selected':''}>Substitute</option>
          </select>
        </td>
        <td><input class="startInp" type="date" value="${a.start_date||''}"></td>
        <td><input class="endInp" type="date" value="${a.end_date||''}"></td>
        <td>
          <button class="saveAssign">Save</button>
          <button class="archiveAssign">Archive</button>
        </td>
      `;
      tr.dataset.id = a.id;
      body.appendChild(tr);
    });
  }

  document.getElementById('createBtn').addEventListener('click', async ()=>{
    const teacher_id = parseInt(document.getElementById('teacherSel').value,10);
    const class_id = parseInt(document.getElementById('classSel').value,10);
    const subject_id = parseInt(document.getElementById('subjectSel').value,10);
    const role = document.getElementById('roleSel').value;
    const start_date = document.getElementById('startDate').value;
    const end_date = document.getElementById('endDate').value;
    const msg = document.getElementById('createMsg'); msg.textContent='';
    if(!teacher_id || !class_id || !subject_id || !start_date){ msg.textContent='All required fields must be provided'; return; }
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/assignments/index.php', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ teacher_id, class_id, subject_id, role, start_date, end_date })});
    const d = await res.json();
    if(!res.ok){ msg.textContent = d.error || 'Failed to create assignment'; return; }
    msg.textContent = 'Assignment created';
    document.getElementById('startDate').value=''; document.getElementById('endDate').value='';
    await loadAssignments();
  });

  document.getElementById('refreshBtn').addEventListener('click', loadAssignments);
  document.getElementById('assignBody').addEventListener('click', async (e)=>{
    const row = e.target.closest('tr'); if(!row) return;
    const id = parseInt(row.dataset.id,10);
    if(e.target.classList.contains('saveAssign')){
      const role = row.querySelector('.roleInp').value;
      const start_date = row.querySelector('.startInp').value;
      const end_date = row.querySelector('.endInp').value;
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/assignments/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, role, start_date, end_date })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to save'); return; }
      await loadAssignments();
    }
    if(e.target.classList.contains('archiveAssign')){
      if(!confirm('Archive this assignment?')) return;
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/assignments/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to archive'); return; }
      await loadAssignments();
    }
  });

  (async function init(){
    await Promise.all([loadTeachers(), loadClasses(), loadSubjects()]);
    await loadAssignments();
  })();
</script>
</body>
</html>
