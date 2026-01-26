<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'teacher') {
    header('Location: /');
    exit;
}
$csrf = Csrf::getToken();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Teacher Dashboard</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:0;padding:0}
    header{background:#2c3e50;color:#fff;padding:16px}
    main{padding:16px;max-width:960px;margin:0 auto}
    button{padding:10px}
    nav a{margin-right:12px}
  </style>
</head>
<body>
<header>
  <h1>Teacher Dashboard</h1>
</header>
<main>
  <nav>
    <a href="/">Home</a>
  </nav>
  <p>Welcome, Teacher.</p>
  <button id="logoutBtn">Logout</button>
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
</script>
</body>
</html>
