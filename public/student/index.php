<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'student') {
    header('Location: /'); exit;
}
$email = $_SESSION['user_email'] ?? '';
?><!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Portal · Mekane Selam Senbet School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
  <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png">
  <link rel="apple-touch-icon" href="/images/logo-mekane-selam-192.png">
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: {
        surface:'#f4f7fc','surface-low':'#eef2fa','surface-mid':'#e5ecf7',
        ink:'#141824','ink-soft':'#3f4658',outline:'#6b7690','outline-soft':'#c4d0e4',
        primary:'#16357e','primary-soft':'#2f52a6',
        gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175',
        olive:'#384700','olive-soft':'#a2b665',error:'#9b1c1c',
      },
      fontFamily: { display:['Newsreader','serif'], body:['Plus Jakarta Sans','Noto Sans Ethiopic','sans-serif'] },
      letterSpacing: { widestest: '0.18em' }
    }}};
  </script>
  <style>
    body{font-family:'Plus Jakarta Sans','Noto Sans Ethiopic',sans-serif;background:#f4f7fc;color:#141824;}
    .panel{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;}
    .stat-card{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;padding:20px 22px;border-top:3px solid #c9a14a;}
    .seg-active{background:#fed175;color:#16357e;}
  </style>
</head>
<body>
  <header class="sticky top-0 z-40 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1100px] mx-auto px-6 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <img src="/images/logo-mekane-selam.webp" alt="Mekane Selam Sunday School" class="h-8 w-8 rounded-full object-cover">
        <span class="font-display text-lg font-semibold text-primary" data-en="Mekane Selam Senbet School · Student" data-am="መካነ ሰላም · ተማሪ">Mekane Selam Senbet School · Student</span>
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
      <h1 id="welcomeName" class="font-display text-3xl text-primary">…</h1>
      <p id="classLine" class="text-sm text-ink-soft mt-2"></p>
    </section>

    <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Attendance" data-am="የክትትል">Attendance</p>
        <p id="statAttendance" class="font-display text-2xl text-primary">—</p>
      </div>
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Subjects graded" data-am="የተመዘገቡ ትምህርቶች">Subjects graded</p>
        <p id="statSubjects" class="font-display text-2xl text-primary">—</p>
      </div>
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Average score" data-am="አማካይ ውጤት">Average score</p>
        <p id="statAvg" class="font-display text-2xl text-primary">—</p>
      </div>
      <div class="stat-card">
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Balance due" data-am="ቀሪ ክፍያ">Balance due</p>
        <p id="statBalance" class="font-display text-2xl text-primary">—</p>
      </div>
    </section>

    <section class="panel">
      <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
        <h2 class="font-display text-lg text-ink" data-en="My grades" data-am="የእኔ ውጤቶች">My grades</h2>
      </header>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-[11px] uppercase tracking-widestest text-outline border-b border-outline-soft/30">
              <th class="px-6 py-3 font-semibold" data-en="Subject" data-am="ትምህርት">Subject</th>
              <th class="px-6 py-3 font-semibold" data-en="Term" data-am="ወቅት">Term</th>
              <th class="px-6 py-3 font-semibold" data-en="Score" data-am="ውጤት">Score</th>
              <th class="px-6 py-3 font-semibold" data-en="Remarks" data-am="አስተያየት">Remarks</th>
            </tr>
          </thead>
          <tbody id="gradesBody" class="divide-y divide-outline-soft/20">
            <tr><td colspan="4" class="px-6 py-10 text-center text-ink-soft" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="grid lg:grid-cols-2 gap-8">
      <section class="panel">
        <header class="px-6 py-5 border-b border-outline-soft/40">
          <h2 class="font-display text-lg text-ink" data-en="Payments" data-am="ክፍያዎች">Payments</h2>
        </header>
        <ul id="paymentsWrap" class="divide-y divide-outline-soft/20">
          <li class="px-6 py-10 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
        </ul>
      </section>

      <section class="panel">
        <header class="px-6 py-5 border-b border-outline-soft/40">
          <h2 class="font-display text-lg text-ink" data-en="Announcements" data-am="ማስታወቂያዎች">Announcements</h2>
        </header>
        <ul id="annWrap" class="divide-y divide-outline-soft/30 max-h-[420px] overflow-y-auto">
          <li class="px-6 py-10 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
        </ul>
      </section>
    </div>

    <section class="panel">
      <header class="px-6 py-5 border-b border-outline-soft/40">
        <h2 class="font-display text-lg text-ink" data-en="Resources / ግብዓቶች" data-am="Resources / ግብዓቶች">Resources / ግብዓቶች</h2>
      </header>
      <ul id="resourcesWrap" class="divide-y divide-outline-soft/20">
        <li class="px-6 py-10 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
      </ul>
    </section>
  </main>

<script>
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function money(n){ return 'ETB ' + Number(n||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2}); }
  async function ensureCsrf(){
    var t = sessionStorage.getItem('csrf_token');
    if (!t){ var r = await fetch('/api/auth/csrf.php'); var d = await r.json(); t = d.csrf_token; sessionStorage.setItem('csrf_token', t); }
    return t;
  }
  async function api(url, opts){
    opts = opts || {}; opts.headers = opts.headers || {};
    if (['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase()) >= 0) opts.headers['X-CSRF-Token'] = await ensureCsrf();
    var res = await fetch(url, opts); var data; try { data = await res.json(); } catch(e){ data = {}; }
    if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
    return data;
  }
  function isAm(){ return document.documentElement.getAttribute('data-lang') === 'am'; }

  var STATE = null;

  function render(){
    if (!STATE) return;
    var am = isAm();
    var p = STATE.profile;
    var nameEl = document.getElementById('welcomeName');
    var clsEl = document.getElementById('classLine');
    if (!p){
      nameEl.textContent = am ? 'ተማሪ' : 'Student';
      clsEl.textContent = STATE.message || (am ? 'ምንም የተማሪ መዝገብ አልተገናኘም።' : 'No student record linked yet. Contact the school admin.');
    } else {
      nameEl.textContent = (p.first_name + ' ' + p.last_name).trim();
      if (p.class){
        var lvl = am && p.class.level_name_am ? p.class.level_name_am : p.class.level_name;
        if (p.class.level_alias) lvl = lvl + ' · ' + p.class.level_alias;
        clsEl.textContent = [p.class.track_name, lvl, p.class.class_name, p.class.academic_year].filter(Boolean).join(' · ');
      } else {
        clsEl.textContent = am ? 'ገና ክፍል አልተመደበም።' : 'Not assigned to a class yet.';
      }
    }
    var att = STATE.attendance;
    document.getElementById('statAttendance').textContent = (att && att.rate !== null) ? att.rate + '%' : '—';
    var grades = STATE.grades || [];
    document.getElementById('statSubjects').textContent = grades.length;
    if (grades.length){
      var avg = grades.reduce(function(a,g){ return a + Number(g.score||0); }, 0) / grades.length;
      document.getElementById('statAvg').textContent = avg.toFixed(1);
    } else { document.getElementById('statAvg').textContent = '—'; }
    document.getElementById('statBalance').textContent = money(STATE.payment_totals.outstanding);

    var gb = document.getElementById('gradesBody');
    if (!grades.length){
      gb.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-ink-soft">' + (am ? 'ገና ውጤት የለም።' : 'No grades recorded yet.') + '</td></tr>';
    } else {
      gb.innerHTML = grades.map(function(g){
        var subj = am && g.subject_name_am ? g.subject_name_am : g.subject_name;
        var term = escHtml(g.term_name || '') + (g.academic_year ? ' <span class="text-outline">' + escHtml(g.academic_year) + '</span>' : '');
        var cur = Number(g.is_current) === 1 ? ' <span class="text-[9px] uppercase tracking-widestest text-olive">' + (am?'አሁን':'current') + '</span>' : '';
        return '<tr>' +
          '<td class="px-6 py-3 font-medium">' + escHtml(subj) + '</td>' +
          '<td class="px-6 py-3 text-ink-soft">' + term + cur + '</td>' +
          '<td class="px-6 py-3"><span class="font-display text-base text-primary">' + escHtml(g.score) + '</span></td>' +
          '<td class="px-6 py-3 text-ink-soft">' + escHtml(g.remarks || '—') + '</td>' +
        '</tr>';
      }).join('');
    }

    var pw = document.getElementById('paymentsWrap');
    var pays = STATE.payments || [];
    if (!pays.length){
      pw.innerHTML = '<li class="px-6 py-10 text-center text-ink-soft text-sm">' + (am ? 'ምንም ክፍያ የለም።' : 'No payments on record.') + '</li>';
    } else {
      pw.innerHTML = pays.map(function(p){
        var bal = Number(p.amount||0) - Number(p.paid_amount||0);
        var paidCls = bal <= 0 ? 'text-olive' : 'text-error';
        var label = bal <= 0 ? (am?'የተከፈለ':'Paid') : money(bal) + (am?' ቀሪ':' due');
        return '<li class="px-6 py-4 flex items-center justify-between">' +
          '<div><p class="font-medium">' + escHtml(p.term_name||'') + ' <span class="text-outline text-xs">' + escHtml(p.academic_year||'') + '</span></p>' +
          '<p class="text-xs text-ink-soft">' + money(p.paid_amount) + ' / ' + money(p.amount) + '</p></div>' +
          '<span class="text-sm font-semibold ' + paidCls + '">' + label + '</span>' +
        '</li>';
      }).join('');
    }

    var aw = document.getElementById('annWrap');
    var anns = STATE.announcements || [];
    if (!anns.length){
      aw.innerHTML = '<li class="px-6 py-10 text-center text-ink-soft text-sm">' + (am ? 'ገና ማስታወቂያ የለም።' : 'No announcements yet.') + '</li>';
    } else {
      aw.innerHTML = anns.map(function(n){
        return '<li class="px-6 py-4">' +
          '<p class="font-medium leading-tight">' + escHtml(n.title) + '</p>' +
          '<p class="text-xs text-outline mb-1" data-iso="' + escHtml(n.created_at) + '" data-fmt-style="datetime">' + escHtml(n.created_at) + '</p>' +
          '<p class="text-sm text-ink-soft whitespace-pre-wrap">' + escHtml(n.message) + '</p>' +
        '</li>';
      }).join('');
    }

    var rw = document.getElementById('resourcesWrap');
    var res = STATE.resources || [];
    if (!res.length){
      rw.innerHTML = '<li class="px-6 py-10 text-center text-ink-soft text-sm">' + (am ? 'ገና ግብዓት የለም።' : 'No resources yet.') + '</li>';
    } else {
      rw.innerHTML = res.map(function(r){
        var hint = '';
        if (r.kind === 'file') {
          var sizeKb = (Number(r.size_bytes||0) / 1024).toFixed(1);
          hint = ' <span class="text-xs text-outline">' + escHtml(r.file_name) + ' · ' + sizeKb + ' KB</span>';
        }
        return '<li class="px-6 py-4">' +
          '<a href="' + escHtml(r.url) + '" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">' + escHtml(r.title) + '</a>' + hint +
        '</li>';
      }).join('');
    }

    if (window.EC) EC.rerenderIsoNodes();
  }

  async function load(){
    try {
      STATE = await api('/api/student/dashboard.php');
      try {
        var resData = await api('/api/student/resources.php');
        STATE.resources = resData.data || [];
      } catch(e) {
        STATE.resources = [];
      }
      render();
    } catch(e){
      document.getElementById('welcomeName').textContent = 'Error';
      document.getElementById('classLine').textContent = e.message;
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
      render();
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

  load();
</script>
</body>
</html>
