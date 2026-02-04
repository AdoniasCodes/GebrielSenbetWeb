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
  <title>Admin - Academic Terms</title>
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
<header><h1>Academic Terms</h1></header>
<main>
  <nav>
    <a href="/admin/index.php">← Back to Dashboard</a>
  </nav>

  <div class="card">
    <h2>Create Term</h2>
    <div class="row">
      <input id="name" placeholder="Term name (e.g., Term 1)" />
      <input id="ay" placeholder="Academic Year (e.g., 2025/2026)" />
      <input id="start" type="date" />
      <input id="end" type="date" />
      <button id="createBtn">Create</button>
    </div>
    <div id="createMsg" class="muted"></div>
  </div>

  <div class="card">
    <h2>Terms</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Academic Year</th><th>Start</th><th>End</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="termsBody"></tbody>
    </table>
  </div>
</main>
<script>
  async function ensureCsrf(){
    let t = sessionStorage.getItem('csrf_token');
    if(!t){ const r = await fetch('/api/auth/csrf.php'); const d = await r.json(); t = d.csrf_token; sessionStorage.setItem('csrf_token', t); }
    return t;
  }

  async function loadTerms(){
    const r = await fetch('/api/admin/terms/index.php');
    const d = await r.json();
    const body = document.getElementById('termsBody'); body.innerHTML='';
    (d.data||[]).forEach(term => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${term.id}</td>
        <td><input class="nameInp" value="${term.name}"/></td>
        <td><input class="ayInp" value="${term.academic_year}"/></td>
        <td><input class="startInp" type="date" value="${term.start_date}"/></td>
        <td><input class="endInp" type="date" value="${term.end_date}"/></td>
        <td>
          <button class="saveBtn" data-id="${term.id}">Save</button>
          <button class="archiveBtn" data-id="${term.id}">Archive</button>
        </td>`;
      body.appendChild(tr);
    });
  }

  document.getElementById('createBtn').addEventListener('click', async ()=>{
    const name = document.getElementById('name').value.trim();
    const academic_year = document.getElementById('ay').value.trim();
    const start_date = document.getElementById('start').value;
    const end_date = document.getElementById('end').value;
    const msg = document.getElementById('createMsg'); msg.textContent='';
    if(!name || !academic_year || !start_date || !end_date){ msg.textContent='All fields required'; return; }
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/terms/index.php', { method: 'POST', headers: { 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ name, academic_year, start_date, end_date })});
    const d = await res.json(); if(!res.ok){ msg.textContent = d.error || 'Failed to create'; return; }
    document.getElementById('name').value=''; document.getElementById('ay').value=''; document.getElementById('start').value=''; document.getElementById('end').value='';
    await loadTerms();
  });

  document.getElementById('termsBody').addEventListener('click', async (e)=>{
    const row = e.target.closest('tr'); if(!row) return;
    const id = parseInt(e.target.getAttribute('data-id'),10);
    if(e.target.classList.contains('saveBtn')){
      const name = row.querySelector('.nameInp').value.trim();
      const academic_year = row.querySelector('.ayInp').value.trim();
      const start_date = row.querySelector('.startInp').value;
      const end_date = row.querySelector('.endInp').value;
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/terms/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, name, academic_year, start_date, end_date })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to save'); return; }
      await loadTerms();
    }
    if(e.target.classList.contains('archiveBtn')){
      if(!confirm('Archive this term?')) return;
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/terms/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to archive'); return; }
      await loadTerms();
    }
  });

  (async function init(){ await loadTerms(); })();
</script>
</body>
</html>
