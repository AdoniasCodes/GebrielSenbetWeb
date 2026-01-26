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
  <title>Admin - Users Management</title>
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
    .notice{color:#c0392b}
  </style>
</head>
<body>
<header><h1>Users Management</h1></header>
<main>
  <nav>
    <a href="/admin/index.php">← Back to Dashboard</a>
  </nav>
  <div class="grid">
    <div class="card">
      <h2>Create User</h2>
      <div class="row">
        <select id="role">
          <option value="admin">Admin</option>
          <option value="teacher">Teacher</option>
          <option value="student">Student</option>
        </select>
        <input id="email" type="email" placeholder="Email" />
        <input id="password" type="text" placeholder="Password (optional, auto-generated if blank)" />
      </div>
      <div id="teacherStudentFields" class="row">
        <input id="first_name" placeholder="First name" />
        <input id="last_name" placeholder="Last name" />
        <input id="phone" placeholder="Phone (teacher/student)" />
        <input id="guardian_name" placeholder="Guardian name (student)" />
        <input id="address" placeholder="Address (student)" />
        <input id="bio" placeholder="Bio (teacher)" />
      </div>
      <div class="row">
        <button id="createBtn">Create User</button>
        <span id="createMsg" class="muted"></span>
      </div>
    </div>

    <div class="card">
      <h2>Users List</h2>
      <div class="row">
        <select id="filterRole">
          <option value="">All roles</option>
          <option value="admin">Admin</option>
          <option value="teacher">Teacher</option>
          <option value="student">Student</option>
        </select>
        <input id="search" placeholder="Search email/name/phone" />
        <button id="refreshBtn">Refresh</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Email</th><th>Role</th><th>Profile</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="usersBody"></tbody>
      </table>
      <div class="row">
        <button id="prevPage">Prev</button>
        <span id="pageInfo" class="muted"></span>
        <button id="nextPage">Next</button>
      </div>
      <div id="listMsg" class="notice"></div>
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

  const roleSel = document.getElementById('role');
  const createMsg = document.getElementById('createMsg');
  const createBtn = document.getElementById('createBtn');

  roleSel.addEventListener('change', ()=>{
    // Show helper placeholders
    document.getElementById('guardian_name').style.display = roleSel.value === 'student' ? '' : 'none';
    document.getElementById('address').style.display = roleSel.value === 'student' ? '' : 'none';
    document.getElementById('bio').style.display = roleSel.value === 'teacher' ? '' : 'none';
  });
  roleSel.dispatchEvent(new Event('change'));

  createBtn.addEventListener('click', async ()=>{
    createMsg.textContent='';
    const body = {
      role: document.getElementById('role').value,
      email: document.getElementById('email').value.trim(),
      password: document.getElementById('password').value.trim(),
      first_name: document.getElementById('first_name').value.trim(),
      last_name: document.getElementById('last_name').value.trim(),
      phone: document.getElementById('phone').value.trim(),
      guardian_name: document.getElementById('guardian_name').value.trim(),
      address: document.getElementById('address').value.trim(),
      bio: document.getElementById('bio').value.trim(),
    };
    if(body.password==='') delete body.password;
    const token = await ensureCsrf();
    const res = await fetch('/api/admin/users/index.php', { method: 'POST', headers: { 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify(body)});
    const d = await res.json();
    if(!res.ok){ createMsg.textContent = d.error || 'Failed to create user'; return; }
    createMsg.textContent = d.generated_password ? `User created. Generated password: ${d.generated_password}` : 'User created';
    document.getElementById('email').value=''; document.getElementById('password').value='';
    document.getElementById('first_name').value=''; document.getElementById('last_name').value='';
    document.getElementById('phone').value=''; document.getElementById('guardian_name').value=''; document.getElementById('address').value=''; document.getElementById('bio').value='';
    await loadUsers();
  });

  // Users list with client-side pagination and search
  let users = [];
  let page = 1; const pageSize = 10;

  document.getElementById('refreshBtn').addEventListener('click', loadUsers);
  document.getElementById('prevPage').addEventListener('click', ()=>{ if(page>1){ page--; renderUsers(); } });
  document.getElementById('nextPage').addEventListener('click', ()=>{ if(page*pageSize < filteredUsers().length){ page++; renderUsers(); } });
  document.getElementById('search').addEventListener('input', ()=>{ page=1; renderUsers(); });
  document.getElementById('filterRole').addEventListener('change', ()=>{ page=1; loadUsers(); });

  async function loadUsers(){
    const role = document.getElementById('filterRole').value;
    const url = role ? `/api/admin/users/index.php?role=${encodeURIComponent(role)}` : '/api/admin/users/index.php';
    const r = await fetch(url);
    const d = await r.json();
    users = d.data || [];
    page = 1;
    renderUsers();
  }

  function filteredUsers(){
    const q = document.getElementById('search').value.trim().toLowerCase();
    if(!q) return users;
    return users.filter(u => (u.email||'').toLowerCase().includes(q)
      || ((u.profile?.first_name||'').toLowerCase().includes(q))
      || ((u.profile?.last_name||'').toLowerCase().includes(q))
      || ((u.profile?.phone||'').toLowerCase().includes(q))
    );
  }

  function renderUsers(){
    const listMsg = document.getElementById('listMsg'); listMsg.textContent='';
    const body = document.getElementById('usersBody'); body.innerHTML='';
    const data = filteredUsers();
    const start = (page-1)*pageSize; const end = Math.min(start+pageSize, data.length);
    for(let i=start;i<end;i++){
      const u = data[i];
      const tr = document.createElement('tr');
      const profileSummary = u.role==='teacher' ? `${u.profile?.first_name||''} ${u.profile?.last_name||''} ${u.profile?.phone||''}`
                           : u.role==='student' ? `${u.profile?.first_name||''} ${u.profile?.last_name||''} ${u.profile?.guardian_name||''}`
                           : '';
      tr.innerHTML = `
        <td>${u.id}</td>
        <td><input class="emailInput" value="${u.email}" /></td>
        <td>${u.role}</td>
        <td>${profileSummary}</td>
        <td>
          <button class="saveUser">Save</button>
          <button class="archiveUser">Archive</button>
        </td>
      `;
      tr.dataset.userId = u.id;
      tr.dataset.role = u.role;
      body.appendChild(tr);
    }
    document.getElementById('pageInfo').textContent = `${start+1}-${end} / ${data.length}`;
  }

  document.getElementById('usersBody').addEventListener('click', async (e)=>{
    const row = e.target.closest('tr'); if(!row) return;
    const userId = parseInt(row.dataset.userId,10);
    const role = row.dataset.role;
    if(e.target.classList.contains('saveUser')){
      const email = row.querySelector('.emailInput').value.trim();
      // For simplicity here we allow updating only email via UI; profile edits can be extended below.
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/users/index.php', { method:'PUT', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id: userId, email })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to save'); return; }
      await loadUsers();
    }
    if(e.target.classList.contains('archiveUser')){
      if(!confirm('Archive this user?')) return;
      const token = await ensureCsrf();
      const res = await fetch('/api/admin/users/index.php', { method:'DELETE', headers:{ 'Content-Type':'application/json','X-CSRF-Token': token }, body: JSON.stringify({ id: userId })});
      if(!res.ok){ const d = await res.json(); alert(d.error||'Failed to archive'); return; }
      await loadUsers();
    }
  });

  // Initial load
  (async function init(){ await loadUsers(); })();
</script>
</body>
</html>
