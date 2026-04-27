<?php
$deployedAt = date('c');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Deploy test</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 600px; margin: 40px auto; padding: 0 16px; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
    .ok { color: #27ae60; }
  </style>
</head>
<body>
  <h1 class="ok">Deploy works</h1>
  <p>If you can see this, the cPanel Git deploy synced files correctly.</p>
  <p>Server time when this page rendered: <code><?= htmlspecialchars($deployedAt) ?></code></p>
  <p>Commit marker: <code>v1 - initial test page</code></p>
</body>
</html>
