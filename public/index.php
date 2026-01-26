<?php
// public/index.php

use App\Utils\Csrf;

require_once __DIR__ . '/../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
$role = $_SESSION['role_name'] ?? null;

// Basic redirect logic per role (to be implemented with real dashboards)
if ($role === 'admin') {
    header('Location: /admin/index.php');
    exit;
} elseif ($role === 'teacher') {
    header('Location: /teacher/index.php');
    exit;
} elseif ($role === 'student') {
    header('Location: /student/index.php');
    exit;
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Church Education Management</title>
  <style>
    body { font-family: system-ui, Arial, sans-serif; margin: 0; padding: 0; color: #222; }
    header { background: #2c3e50; color: #fff; padding: 16px; }
    main { padding: 16px; max-width: 720px; margin: 0 auto; }
    .card { border: 1px solid #e3e3e3; border-radius: 8px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    input, button { padding: 10px; font-size: 14px; }
    .row { display: flex; gap: 8px; flex-wrap: wrap; }
    .row > * { flex: 1 1 240px; }
    .notice { margin-top: 12px; color: #c0392b; }
  </style>
</head>
<body>
  <header>
    <h1>Church Education Management</h1>
  </header>
  <main>
    <div class="card">
      <h2>Login</h2>
      <div class="row">
        <input type="email" id="email" placeholder="Email" />
        <input type="password" id="password" placeholder="Password" />
      </div>
      <div class="row">
        <button id="loginBtn">Login</button>
      </div>
      <div id="msg" class="notice"></div>
    </div>
  </main>
  <script>
    const loginBtn = document.getElementById('loginBtn');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const msg = document.getElementById('msg');

    loginBtn.addEventListener('click', async () => {
      msg.textContent = '';
      try {
        const res = await fetch('/api/auth/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email.value, password: password.value })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Login failed');
        // Store CSRF token for subsequent requests
        sessionStorage.setItem('csrf_token', data.csrf_token);
        msg.style.color = '#27ae60';
        msg.textContent = `Welcome ${data.role}. Redirecting...`;
        // Redirect by role
        const dest = data.role === 'admin' ? '/admin/index.php'
                    : data.role === 'teacher' ? '/teacher/index.php'
                    : '/student/index.php';
        setTimeout(() => { window.location.href = dest; }, 600);
      } catch (e) {
        msg.style.color = '#c0392b';
        msg.textContent = e.message;
      }
    });
  </script>
</body>
</html>
