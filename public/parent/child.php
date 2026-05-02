<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Database;
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'parent') {
    header('Location: /'); exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$userId = (int)($_SESSION['user_id'] ?? 0);

$pdo = (new Database($config['db']))->pdo();
$ck = $pdo->prepare('SELECT s.first_name, s.last_name FROM students s
                     JOIN student_guardians sg ON sg.student_id=s.id AND sg.is_archived=0
                     WHERE sg.user_id=? AND s.id=? AND s.is_archived=0 LIMIT 1');
$ck->execute([$userId, $studentId]);
$child = $ck->fetch();
if (!$child) { http_response_code(403); echo 'Not your child.'; exit; }

$childName = trim($child['first_name'] . ' ' . $child['last_name']);
$email = $_SESSION['user_email'] ?? '';
?><!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($childName) ?> · Parent Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&display=swap" rel="stylesheet" />
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
    .data{width:100%;border-collapse:collapse;}
    .data th,.data td{padding:12px 16px;text-align:left;border-bottom:1px solid rgba(220,192,192,0.3);}
    .data th{font-size:11px;text-transform:uppercase;letter-spacing:.18em;color:#897172;background:#f6f3ec;font-weight:600;}
    .pill{display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;}
    .pill-paid{background:#e7f0d2;color:#384700;}
    .pill-partial{background:#fef0c7;color:#795901;}
    .pill-unpaid{background:#fde4e4;color:#ba1a1a;}
    .seg-active{background:#fed175;color:#5b0617;}
  </style>
</head>
<body>
  <header class="sticky top-0 z-40 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1100px] mx-auto px-6 h-16 flex items-center justify-between">
      <a href="/parent/index.php" class="flex items-center gap-2 text-sm text-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <span data-en="Back to family" data-am="ወደ ቤተሰብ ተመለስ">Back to family</span>
      </a>
      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft">አማ</button>
        </div>
        <button id="logoutBtn" class="text-xs font-semibold uppercase tracking-widestest text-primary border border-outline-soft px-3 py-2 rounded hover:bg-surface-mid">Logout</button>
      </div>
    </div>
  </header>

  <main class="max-w-[1100px] mx-auto px-6 py-10 space-y-8">
    <section>
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-2" data-en="Student" data-am="ተማሪ">Student</p>
      <h1 class="font-display text-3xl text-primary"><?= htmlspecialchars($childName) ?></h1>
    </section>

    <div class="grid sm:grid-cols-3 gap-5">
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3" data-en="Average score" data-am="አማካይ ውጤት">Average score</p>
        <p class="font-display text-3xl text-ink leading-none"><span id="avgScore">—</span></p>
      </div>
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3" data-en="Outstanding" data-am="ያልተከፈለ">Outstanding</p>
        <p class="font-display text-3xl text-ink leading-none">ETB <span id="outstanding">—</span></p>
      </div>
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3" data-en="Class" data-am="ክፍል">Class</p>
        <p class="font-display text-lg text-ink leading-tight" id="classLine">—</p>
      </div>
    </div>

    <section class="panel">
      <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
        <h2 class="font-display text-lg text-ink" data-en="Grades" data-am="ውጤቶች">Grades</h2>
      </header>
      <div class="overflow-x-auto"><table class="data">
        <thead><tr><th>Term</th><th>Class</th><th>Subject</th><th>Score</th><th>Remarks</th></tr></thead>
        <tbody id="gradesTbody"><tr><td colspan="5" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
      </table></div>
    </section>

    <section class="panel">
      <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
        <h2 class="font-display text-lg text-ink" data-en="Payments" data-am="ክፍያዎች">Payments</h2>
      </header>
      <div class="overflow-x-auto"><table class="data">
        <thead><tr><th>Term</th><th>Expected</th><th>Paid</th><th>Status</th><th>Notes</th></tr></thead>
        <tbody id="paymentsTbody"><tr><td colspan="5" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
      </table></div>
    </section>
  </main>

<script>
  var STUDENT_ID = <?= (int)$studentId ?>;
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function fmtMoney(n){ return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
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

  async function loadProfile(){
    var d = await api('/api/parent/students/index.php');
    var s = (d.data || []).find(function(x){ return x.id == STUDENT_ID; });
    if (s) {
      var line = s.class_name ? (escHtml(s.track_name||'') + ' · ' + escHtml(s.level_name||'') + ' · ' + escHtml(s.class_name)) : '<span class="text-outline">Unassigned</span>';
      document.getElementById('classLine').innerHTML = line;
    }
  }

  async function loadGrades(){
    try {
      var d = await api('/api/parent/grades/index.php?student_id=' + STUDENT_ID);
      var rows = d.data || [];
      var tbody = document.getElementById('gradesTbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-12">No grades yet.</td></tr>'; document.getElementById('avgScore').textContent = '—'; return; }
      var sum = 0;
      tbody.innerHTML = rows.map(function(g){
        sum += parseFloat(g.score || 0);
        return '<tr><td class="text-ink-soft">'+escHtml(g.academic_year||'')+' · '+escHtml(g.term_name||'')+'</td>' +
          '<td>'+escHtml(g.class_name||'')+'</td>' +
          '<td>'+escHtml(g.subject_name||'')+'</td>' +
          '<td class="font-medium">'+escHtml(g.score)+'</td>' +
          '<td class="text-ink-soft text-sm">'+escHtml(g.remarks||'')+'</td></tr>';
      }).join('');
      document.getElementById('avgScore').textContent = (sum / rows.length).toFixed(1);
    } catch(e){
      document.getElementById('gradesTbody').innerHTML = '<tr><td colspan="5" class="text-center text-error py-12">'+escHtml(e.message)+'</td></tr>';
    }
  }

  async function loadPayments(){
    try {
      var d = await api('/api/parent/payments/index.php?student_id=' + STUDENT_ID);
      var rows = d.data || [];
      var tbody = document.getElementById('paymentsTbody');
      document.getElementById('outstanding').textContent = fmtMoney((d.totals && d.totals.outstanding) || 0);
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-12">No payments recorded.</td></tr>'; return; }
      tbody.innerHTML = rows.map(function(p){
        var pill = '<span class="pill pill-'+escHtml(p.status)+'">'+escHtml(p.status)+'</span>';
        return '<tr><td class="text-ink-soft">'+escHtml(p.academic_year||'')+' · '+escHtml(p.term_name||'')+'</td>' +
          '<td>ETB '+fmtMoney(p.amount)+'</td>' +
          '<td>ETB '+fmtMoney(p.paid_amount)+'</td>' +
          '<td>'+pill+'</td>' +
          '<td class="text-ink-soft text-sm">'+escHtml(p.notes||'')+'</td></tr>';
      }).join('');
    } catch(e){
      document.getElementById('paymentsTbody').innerHTML = '<tr><td colspan="5" class="text-center text-error py-12">'+escHtml(e.message)+'</td></tr>';
    }
  }

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

  loadProfile();
  loadGrades();
  loadPayments();
</script>
</body>
</html>
