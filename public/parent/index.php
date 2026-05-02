<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'parent') {
    header('Location: /'); exit;
}
$year = date('Y');
$email = $_SESSION['user_email'] ?? '';
$initials = strtoupper(substr($email, 0, 2));
?><!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parent Portal · Gebriel Senbet</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: {
        surface:'#fcf9f2','surface-low':'#f6f3ec','surface-mid':'#f0eee7',
        ink:'#1c1c18','ink-soft':'#564242',outline:'#897172','outline-soft':'#dcc0c0',
        primary:'#5b0617','primary-soft':'#7a1f2b',
        gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175',
        olive:'#384700','olive-soft':'#a2b665',
      },
      fontFamily: { display:['Newsreader','serif'], body:['Plus Jakarta Sans','Noto Sans Ethiopic','sans-serif'] },
      letterSpacing: { widestest: '0.18em' }
    }}};
  </script>
  <style>
    body{font-family:'Plus Jakarta Sans','Noto Sans Ethiopic',sans-serif;background:#fcf9f2;color:#1c1c18;}
    .panel{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;}
    .stat-card{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;padding:24px;border-top:3px solid #c9a14a;}
    .seg-active{background:#fed175;color:#5b0617;}
  </style>
</head>
<body>
  <header class="sticky top-0 z-40 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1100px] mx-auto px-6 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/><circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/></svg>
        </span>
        <span class="font-display text-lg font-semibold text-primary" data-en="Gebriel Senbet · Parent" data-am="ገብርኤል ሰንበት · ወላጅ">Gebriel Senbet · Parent</span>
      </a>
      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft">አማ</button>
        </div>
        <span class="text-xs text-ink-soft hidden sm:inline"><?= htmlspecialchars($email) ?></span>
        <button id="logoutBtn" class="text-xs font-semibold uppercase tracking-widestest text-primary border border-outline-soft px-3 py-2 rounded hover:bg-surface-mid">Logout</button>
      </div>
    </div>
  </header>

  <main class="max-w-[1100px] mx-auto px-6 py-10 space-y-8">
    <section>
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-2" data-en="Welcome" data-am="እንኳን ደህና መጡ">Welcome</p>
      <h1 class="font-display text-3xl text-primary" data-en="Your family" data-am="ቤተሰብዎ">Your family</h1>
      <p class="text-sm text-ink-soft mt-2" data-en="Track grades, payments, and announcements for each child below." data-am="የእያንዳንዱ ልጅዎን ውጤት፣ ክፍያና ማስታወቂያ ከታች ይመልከቱ።">Track grades, payments, and announcements for each child below.</p>
    </section>

    <section id="childrenWrap" class="grid sm:grid-cols-2 gap-5">
      <p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
    </section>

    <section class="panel">
      <header class="px-6 py-5 border-b border-outline-soft/40">
        <h2 class="font-display text-lg text-ink" data-en="Announcements" data-am="ማስታወቂያዎች">Announcements</h2>
      </header>
      <ul id="annWrap" class="divide-y divide-outline-soft/30 max-h-[480px] overflow-y-auto">
        <li class="px-6 py-12 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
      </ul>
    </section>
  </main>

<script>
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  async function ensureCsrf(){
    var t = sessionStorage.getItem('csrf_token');
    if (!t){ var r = await fetch('/api/auth/csrf.php'); var d = await r.json(); t = d.csrf_token; sessionStorage.setItem('csrf_token', t); }
    return t;
  }
  async function api(url, opts){
    opts = opts || {}; opts.headers = opts.headers || {};
    if (opts.body && !opts.headers['Content-Type']) opts.headers['Content-Type'] = 'application/json';
    if (['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase()) >= 0) opts.headers['X-CSRF-Token'] = await ensureCsrf();
    var res = await fetch(url, opts); var data; try { data = await res.json(); } catch(e){ data = {}; }
    if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
    return data;
  }

  function fmtDate(s){ return s && window.EC ? EC.fmtDate(s, 'datetime') : (s || ''); }

  async function loadChildren(){
    var wrap = document.getElementById('childrenWrap');
    try {
      var d = await api('/api/parent/students/index.php');
      var rows = d.data || [];
      if (!rows.length){
        wrap.innerHTML = '<p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="No children linked to this account yet. Contact the school admin." data-am="ገና ምንም ልጅ ከዚህ መለያ ጋር አልተገናኘም። እባክዎ ት/ቤቱን ያነጋግሩ።">No children linked to this account yet. Contact the school admin.</p>';
        return;
      }
      wrap.innerHTML = rows.map(function(s){
        var name = (s.first_name + ' ' + s.last_name).trim();
        var classLine = s.class_name ? (escHtml(s.track_name||'') + ' · ' + escHtml(s.level_name||'') + ' · ' + escHtml(s.class_name)) : '<span class="text-outline">Unassigned</span>';
        return '<a href="/parent/child.php?student_id='+s.id+'" class="block panel p-6 hover:shadow-md transition-shadow">' +
          '<p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-3">Student</p>' +
          '<h3 class="font-display text-2xl text-primary mb-2">'+escHtml(name)+'</h3>' +
          '<p class="text-sm text-ink-soft mb-4">'+classLine+'</p>' +
          '<span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary">View details <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>' +
        '</a>';
      }).join('');
    } catch(e){
      wrap.innerHTML = '<p class="text-sm text-error col-span-full text-center py-8">'+escHtml(e.message)+'</p>';
    }
  }

  async function loadAnnouncements(){
    var ul = document.getElementById('annWrap');
    try {
      var d = await api('/api/parent/announcements/index.php');
      var rows = d.data || [];
      if (!rows.length){ ul.innerHTML = '<li class="px-6 py-12 text-center text-ink-soft text-sm" data-en="No announcements yet." data-am="ገና ማስታወቂያ የለም።">No announcements yet.</li>'; return; }
      ul.innerHTML = rows.map(function(n){
        return '<li class="px-6 py-4">' +
          '<p class="font-medium leading-tight">'+escHtml(n.title)+'</p>' +
          '<p class="text-xs text-outline mb-2" data-iso="'+escHtml(n.created_at)+'" data-fmt-style="datetime">'+escHtml(n.created_at)+'</p>' +
          '<p class="text-sm text-ink-soft whitespace-pre-wrap">'+escHtml(n.message)+'</p>' +
        '</li>';
      }).join('');
      if (window.EC) EC.rerenderIsoNodes();
    } catch(e){
      ul.innerHTML = '<li class="px-6 py-12 text-center text-error text-sm">'+escHtml(e.message)+'</li>';
    }
  }

  // Lang toggle
  (function(){
    function applyLang(lang){
      if (lang!=='en' && lang!=='am') lang='en';
      document.documentElement.setAttribute('data-lang', lang);
      document.querySelectorAll('[data-en], [data-am]').forEach(function(el){
        var v = el.getAttribute('data-' + lang); if (v !== null) el.innerHTML = v;
      });
      document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){
        btn.classList.toggle('seg-active', btn.dataset.lang === lang);
        btn.classList.toggle('text-ink-soft', btn.dataset.lang !== lang);
      });
      try { localStorage.setItem('gs_lang', lang); } catch(e){}
      if (window.EC) EC.rerenderIsoNodes();
    }
    document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){
      btn.addEventListener('click', function(){ applyLang(btn.dataset.lang); });
    });
    var saved = 'en'; try { saved = localStorage.getItem('gs_lang') || 'en'; } catch(e){}
    applyLang(saved);
  })();

  document.getElementById('logoutBtn').addEventListener('click', async function(){
    try { await api('/api/auth/logout.php', { method:'POST' }); window.location.href = '/'; }
    catch(e) { alert(e.message); }
  });

  loadChildren();
  loadAnnouncements();
</script>
</body>
</html>
