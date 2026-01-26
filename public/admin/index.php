<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
use App\Utils\Response;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'admin') {
    header('Location: /');
    exit;
}
$csrf = Csrf::getToken();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:0;padding:0}
    header{background:#2c3e50;color:#fff;padding:16px}
    main{padding:16px;max-width:1100px;margin:0 auto}
    button{padding:10px}
    nav a{margin-right:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
    .card{border:1px solid #e3e3e3;border-radius:8px;padding:16px}
    input,select{padding:8px;margin:4px 0;width:100%;box-sizing:border-box}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    th{background:#f5f5f5}
    .row{display:flex;gap:8px;flex-wrap:wrap}
  </style>
</head>
<body>
<header>
  <h1>Admin Dashboard</h1>
</header>
<main>
  <nav>
    <a href="/">Home</a>
  </nav>
  <p>Welcome, Admin.</p>
  <button id="logoutBtn">Logout</button>

  <div class="grid">
    <div class="card">
      <h2>Education Tracks</h2>
      <div class="row">
        <input id="trackName" placeholder="New track name" />
        <button id="addTrackBtn">Add Track</button>
      </div>

  <div class="grid">
    <div class="card">
      <h2>Subjects</h2>
      <div class="row">
        <input id="subjectName" placeholder="Subject name" />
        <input id="subjectDesc" placeholder="Description (optional)" />
        <button id="addSubjectBtn">Add Subject</button>
      </div>
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
        <tbody id="subjectsBody"></tbody>
      </table>
    </div>

    <div class="card">
      <h2>Classes</h2>
      <div class="row">
        <select id="classLevel"></select>
        <input id="classYear" placeholder="Academic Year (e.g., 2025/2026)" />
        <input id="className" placeholder="Class Name (e.g., A)" />
        <button id="addClassBtn">Add Class</button>
      </div>
      <table>
        <thead><tr><th>ID</th><th>Track</th><th>Level</th><th>Year</th><th>Name</th><th>Actions</th></tr></thead>
        <tbody id="classesBody"></tbody>
      </table>
    </div>
  </div>
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
        <tbody id="tracksBody"></tbody>
      </table>
    </div>

    <div class="card">
      <h2>Class Levels</h2>
      <div class="row">
        <select id="levelTrack"></select>
        <input id="levelName" placeholder="Level name" />
        <input id="levelSort" placeholder="Sort order (number)" type="number" />
        <button id="addLevelBtn">Add Level</button>
      </div>
      <table>
        <thead><tr><th>ID</th><th>Track</th><th>Name</th><th>Sort</th><th>Actions</th></tr></thead>
        <tbody id="levelsBody"></tbody>
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
  document.getElementById('logoutBtn').addEventListener('click', async ()=>{
    const token = await ensureCsrf();
    const res = await fetch('/api/auth/logout.php', { method:'POST', headers: { 'X-CSRF-Token': token }});
    if(res.ok){ window.location.href = '/'; }
  });

  async function loadTracks(){
    const r = await fetch('/api/admin/tracks/index.php');
    const d = await r.json();
    const body = document.getElementById('tracksBody');
    const sel = document.getElementById('levelTrack');
    body.innerHTML = '';
    sel.innerHTML = '';
    d.data.forEach(tr => {
      const trEl = document.createElement('tr');
      trEl.innerHTML = `<td>${tr.id}</td><td><input value="${tr.name}" data-id="${tr.id}" class="trackNameInput"/></td>
                        <td><button data-id="${tr.id}" class="saveTrack">Save</button>
                            <button data-id="${tr.id}" class="archiveTrack">Archive</button></td>`;
      body.appendChild(trEl);
      const opt = document.createElement('option');
      opt.value = tr.id; opt.textContent = tr.name; sel.appendChild(opt);
    });
  }

  async function loadLevels(){
    const r = await fetch('/api/admin/levels/index.php');
    const d = await r.json();
    const body = document.getElementById('levelsBody');
    body.innerHTML = '';
    // also populate classLevel select
    const classLevelSel = document.getElementById('classLevel');
    classLevelSel.innerHTML = '';
    d.data.forEach(lv => {
      const trEl = document.createElement('tr');
      trEl.innerHTML = `<td>${lv.id}</td><td>${lv.track_name}</td>
                        <td><input value="${lv.name}" data-id="${lv.id}" class="levelNameInput"/></td>
                        <td><input type="number" value="${lv.sort_order}" data-id="${lv.id}" class="levelSortInput"/></td>
                        <td><button data-id="${lv.id}" class="saveLevel">Save</button>
                            <button data-id="${lv.id}" class="archiveLevel">Archive</button></td>`;
      body.appendChild(trEl);
      const opt = document.createElement('option');
      opt.value = lv.id; opt.textContent = `${lv.track_name} - ${lv.name}`; classLevelSel.appendChild(opt);
    });
  }

  document.getElementById('addTrackBtn').addEventListener('click', async ()=>{
    const name = document.getElementById('trackName').value.trim();
    if(!name) return;
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/tracks/index.php', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-Token': token }, body: JSON.stringify({ name })});
    if(res.ok){ document.getElementById('trackName').value=''; await loadTracks(); }
  });

  document.getElementById('addLevelBtn').addEventListener('click', async ()=>{
    const track_id = parseInt(document.getElementById('levelTrack').value, 10);
    const name = document.getElementById('levelName').value.trim();
    const sort_order = parseInt(document.getElementById('levelSort').value || '0', 10);
    if(!track_id || !name) return;
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/levels/index.php', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-Token': token }, body: JSON.stringify({ track_id, name, sort_order })});
    if(res.ok){ document.getElementById('levelName').value=''; document.getElementById('levelSort').value=''; await loadLevels(); }
  });

  document.addEventListener('click', async (e)=>{
    if(e.target.classList.contains('saveTrack')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const name = e.target.closest('tr').querySelector('.trackNameInput').value.trim();
      const token = await ensureCsrf();
      await fetch('/api/admin/tracks/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, name })});
      await loadTracks();
    }
    if(e.target.classList.contains('archiveTrack')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const token = await ensureCsrf();
      await fetch('/api/admin/tracks/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      await loadTracks(); await loadLevels();
    }
    if(e.target.classList.contains('saveLevel')){
      const row = e.target.closest('tr');
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const name = row.querySelector('.levelNameInput').value.trim();
      const sort_order = parseInt(row.querySelector('.levelSortInput').value||'0',10);
      const token = await ensureCsrf();
      await fetch('/api/admin/levels/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, name, sort_order })});
      await loadLevels();
    }
    if(e.target.classList.contains('archiveLevel')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const token = await ensureCsrf();
      await fetch('/api/admin/levels/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      await loadLevels();
    }
    if(e.target.classList.contains('saveSubject')){
      const row = e.target.closest('tr');
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const name = row.querySelector('.subjectNameInput').value.trim();
      const description = row.querySelector('.subjectDescInput').value.trim();
      const token = await ensureCsrf();
      await fetch('/api/admin/subjects/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, name, description })});
      await loadSubjects();
    }
    if(e.target.classList.contains('archiveSubject')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const token = await ensureCsrf();
      await fetch('/api/admin/subjects/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      await loadSubjects();
    }
    if(e.target.classList.contains('saveClass')){
      const row = e.target.closest('tr');
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const academic_year = row.querySelector('.classYearInput').value.trim();
      const name = row.querySelector('.classNameInput').value.trim();
      const token = await ensureCsrf();
      await fetch('/api/admin/classes/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id, academic_year, name })});
      await loadClasses();
    }
    if(e.target.classList.contains('archiveClass')){
      const id = parseInt(e.target.getAttribute('data-id'),10);
      const token = await ensureCsrf();
      await fetch('/api/admin/classes/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id })});
      await loadClasses();
    }
  });

  (async function init(){
    await loadTracks();
    await loadLevels();
    await loadSubjects();
    await loadClasses();
  })();

  async function loadSubjects(){
    const r = await fetch('/api/admin/subjects/index.php');
    const d = await r.json();
    const body = document.getElementById('subjectsBody');
    body.innerHTML = '';
    d.data.forEach(s => {
      const trEl = document.createElement('tr');
      trEl.innerHTML = `<td>${s.id}</td>
                        <td><input value="${s.name}" data-id="${s.id}" class="subjectNameInput"/></td>
                        <td><input value="${s.description ?? ''}" data-id="${s.id}" class="subjectDescInput"/></td>
                        <td><button data-id="${s.id}" class="saveSubject">Save</button>
                            <button data-id="${s.id}" class="archiveSubject">Archive</button></td>`;
      body.appendChild(trEl);
    });
  }

  document.getElementById('addSubjectBtn').addEventListener('click', async ()=>{
    const name = document.getElementById('subjectName').value.trim();
    const description = document.getElementById('subjectDesc').value.trim();
    if(!name) return;
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/subjects/index.php', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ name, description })});
    if(res.ok){ document.getElementById('subjectName').value=''; document.getElementById('subjectDesc').value=''; await loadSubjects(); }
  });

  async function loadClasses(){
    const r = await fetch('/api/admin/classes/index.php');
    const d = await r.json();
    const body = document.getElementById('classesBody');
    body.innerHTML = '';
    d.data.forEach(c => {
      const trEl = document.createElement('tr');
      trEl.innerHTML = `<td>${c.id}</td><td>${c.track_name}</td><td>${c.level_name}</td>
                        <td><input value="${c.academic_year}" data-id="${c.id}" class="classYearInput"/></td>
                        <td><input value="${c.name}" data-id="${c.id}" class="classNameInput"/></td>
                        <td><button data-id="${c.id}" class="saveClass">Save</button>
                            <button data-id="${c.id}" class="archiveClass">Archive</button></td>`;
      body.appendChild(trEl);
    });
  }

  document.getElementById('addClassBtn').addEventListener('click', async ()=>{
    const level_id = parseInt(document.getElementById('classLevel').value,10);
    const academic_year = document.getElementById('classYear').value.trim();
    const name = document.getElementById('className').value.trim();
    if(!level_id || !academic_year || !name) return;
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/classes/index.php', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ level_id, academic_year, name })});
    if(res.ok){ document.getElementById('classYear').value=''; document.getElementById('className').value=''; await loadClasses(); }
  });
</script>
</body>
</html>
