<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
$role = $_SESSION['role_name'] ?? null;
if ($role !== 'staff' && $role !== 'admin') { header('Location: /'); exit; }
$initials = strtoupper(substr($_SESSION['user_email'] ?? 'GS', 0, 2));
?>
<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Department · Mekane Selam Senbet School</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
  <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png">
  <link rel="apple-touch-icon" href="/images/logo-mekane-selam-192.png">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: { surface:'#f4f7fc','surface-low':'#eef2fa','surface-mid':'#e5ecf7','surface-high':'#ebe8e1', ink:'#141824','ink-soft':'#3f4658', outline:'#6b7690','outline-soft':'#c4d0e4', primary:'#16357e','primary-soft':'#2f52a6', gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175', olive:'#384700','olive-soft':'#a2b665', error:'#ba1a1a' },
      fontFamily: { display:['Newsreader','"Noto Serif Ethiopic"','serif'], body:['"Plus Jakarta Sans"','"Noto Sans Ethiopic"','system-ui','sans-serif'], ethiopic:['"Noto Sans Ethiopic"','serif'] },
      letterSpacing:{ widestest:'0.18em' } } } };
  </script>
  <script>
    // shared gs.* helpers (defined early so page scripts can use them)
    window.gs = {};
    gs.ensureCsrf = async function(){ var t=sessionStorage.getItem('csrf_token'); if(!t){ var r=await fetch('/api/auth/csrf.php'); t=(await r.json()).csrf_token; sessionStorage.setItem('csrf_token',t);} return t; };
    gs.api = async function(url,opts){ opts=opts||{}; opts.headers=opts.headers||{}; if(opts.body&&!opts.headers['Content-Type'])opts.headers['Content-Type']='application/json'; if(['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase())>=0)opts.headers['X-CSRF-Token']=await gs.ensureCsrf(); var res=await fetch(url,opts); var d; try{d=await res.json();}catch(e){d={};} if(!res.ok)throw new Error(d.error||('HTTP '+res.status)); return d; };
    gs.toast = function(msg,type){ var bg={info:'#384700',error:'#ba1a1a',success:'#384700'}[type]||'#384700'; var t=document.createElement('div'); t.style.cssText='position:fixed;bottom:24px;right:24px;background:'+bg+';color:#fff;padding:14px 20px;border-radius:6px;z-index:9999;font-size:14px;'; t.textContent=msg; document.body.appendChild(t); setTimeout(function(){t.remove();},3000); };
    gs.confirm = function(m){ return Promise.resolve(window.confirm(m)); };
  </script>
  <style>
    html,body{background:#f4f7fc;} .font-display{font-family:'Newsreader','Noto Serif Ethiopic',serif;} .ethiopic{font-family:'Noto Sans Ethiopic',serif;}
    .seg-active{background:#fed175;color:#16357e;}
    .panel{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:600;}
    .pill-active{background:rgba(56,71,0,0.10);color:#384700;} .pill-archived{background:rgba(137,113,114,0.15);color:#3f4658;}
    .input-field{width:100%;padding:9px 12px;background:#fff;border:1px solid rgba(137,113,114,0.25);border-radius:4px;font-size:14px;color:#141824;}
    .input-field:focus{outline:none;border-color:#c9a14a;box-shadow:0 0 0 3px rgba(201,161,74,0.12);}
    .btn-primary{display:inline-flex;align-items:center;gap:8px;background:#16357e;color:#f4f7fc;padding:9px 16px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;cursor:pointer;}
    .btn-primary:hover{background:#2f52a6;}
    .btn-ghost{display:inline-flex;align-items:center;gap:8px;background:#e5ecf7;color:#16357e;padding:9px 16px;border:1px solid rgba(220,192,192,0.5);border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;cursor:pointer;}
    .btn-icon{width:30px;height:30px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;color:#3f4658;}
    .btn-icon:hover{background:#e5ecf7;color:#16357e;} .btn-icon.danger:hover{background:rgba(186,26,26,0.1);color:#ba1a1a;}
    .nav-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-radius:4px;color:#3f4658;font-size:14px;cursor:pointer;width:100%;text-align:left;}
    .nav-item:hover{background:rgba(91,6,23,0.04);color:#16357e;} .nav-item.active{color:#16357e;font-weight:600;background:rgba(91,6,23,0.06);}
    table.data{width:100%;border-collapse:collapse;font-size:14px;} table.data thead th{padding:10px 16px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;border-bottom:1px solid rgba(220,192,192,0.4);background:#eef2fa;}
    table.data tbody td{padding:12px 16px;border-bottom:1px solid rgba(220,192,192,0.3);}
  </style>
</head>
<body class="font-body text-ink">
<div class="max-w-6xl mx-auto px-6 py-8">
  <header class="flex items-center justify-between mb-8">
    <div>
      <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Department Head" data-am="የክፍል ሃላፊ">Department Head</p>
      <h1 class="font-display text-2xl text-primary" data-en="My Departments" data-am="የእኔ ክፍሎች">My Departments</h1>
    </div>
    <div class="flex items-center gap-3">
      <button id="notifBell" class="relative p-2 text-ink-soft hover:text-primary" aria-label="Notifications">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
        <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center">0</span>
      </button>
      <div class="flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
        <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
        <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft ethiopic">አማ</button>
      </div>
      <?php if ($role === 'admin'): ?><a href="/admin/index.php" class="btn-ghost" data-en="Admin" data-am="አስተዳዳሪ">Admin</a><?php endif; ?>
      <button id="logoutBtn" class="btn-ghost" data-en="Sign out" data-am="ውጣ">Sign out</button>
    </div>
  </header>

  <!-- Notifications panel -->
  <section id="notifPanel" class="panel hidden mb-6">
    <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-lg text-ink" data-en="Notifications" data-am="ማሳወቂያዎች">Notifications</h2>
      <button id="notifClose" class="text-ink-soft hover:text-primary p-1" aria-label="Close"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </header>
    <ul id="notifList" class="divide-y divide-outline-soft/20 px-6">
      <li class="py-6 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
    </ul>
  </section>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="panel lg:col-span-1 self-start">
      <header class="px-5 py-4 border-b border-outline-soft/40"><h2 class="font-display text-base" data-en="Departments I lead" data-am="የምመራቸው ክፍሎች">Departments I lead</h2></header>
      <div id="deptList" class="p-3 space-y-1"><p class="text-center text-ink-soft py-8 text-sm">Loading…</p></div>
    </section>

    <section class="lg:col-span-2 space-y-6">
      <div id="empty" class="panel p-12 text-center text-ink-soft" data-en="Select a department to manage its members and levels." data-am="አባላትና ደረጃዎችን ለማስተዳደር ክፍል ይምረጡ።">Select a department to manage its members and levels.</div>
      <div id="detail" class="hidden space-y-6">
        <div class="panel px-6 py-5"><h2 id="detName" class="font-display text-xl text-ink">—</h2><p id="detSub" class="text-sm text-ink-soft mt-1"></p></div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Levels" data-am="ደረጃዎች">Levels</span> · <span id="lvlCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4">
            <div id="levelList" class="space-y-2 mb-3"></div>
            <form id="levelForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30">
              <div class="flex-1 min-w-[110px]"><label class="lbl" data-en="Name" data-am="ስም">Name</label><input id="lvl_name" class="input-field" /></div>
              <div class="flex-1 min-w-[110px]"><label class="lbl" data-en="Amharic" data-am="አማርኛ">Amharic</label><input id="lvl_name_am" class="input-field ethiopic" /></div>
              <div class="w-20"><label class="lbl" data-en="Rank" data-am="ደረጃ">Rank</label><input id="lvl_rank" type="number" class="input-field" value="0" /></div>
              <button type="submit" class="btn-ghost" data-en="Add" data-am="ጨምር">Add</button>
            </form>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Serving eligibility" data-am="የአገልግሎት ብቁነት">Serving eligibility</span> · <span id="eligNote" class="text-ink-soft text-sm"></span></h3></header>
          <div class="p-4">
            <div style="overflow-x:auto"><table class="data">
              <thead><tr><th data-en="Name" data-am="ስም">Name</th><th data-en="Level" data-am="ደረጃ">Level</th><th data-en="Attendance" data-am="መገኘት">Attendance</th><th data-en="Serving" data-am="አገልግሎት">Serving</th></tr></thead>
              <tbody id="eligBody"><tr><td colspan="4" class="text-center text-ink-soft py-6">—</td></tr></tbody>
            </table></div>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Resources" data-am="ግብዓቶች">Resources</span> · <span id="resCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4">
            <div id="resList" class="space-y-2 mb-3"></div>
            <div class="flex flex-wrap gap-3">
              <form id="resUploadForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 flex-1 min-w-[260px]">
                <div class="flex-1 min-w-[120px]"><label class="lbl" data-en="Title (optional)" data-am="ርዕስ (አማራጭ)">Title (optional)</label><input id="res_title" class="input-field" /></div>
                <div class="flex-1 min-w-[160px]"><label class="lbl" data-en="File" data-am="ፋይል">File</label><input id="res_file" type="file" class="input-field" /></div>
                <button type="submit" class="btn-primary" data-en="Upload" data-am="ስቀል">Upload</button>
              </form>
              <form id="resLinkForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 flex-1 min-w-[260px]">
                <div class="flex-1 min-w-[120px]"><label class="lbl" data-en="Title" data-am="ርዕስ">Title</label><input id="reslink_title" class="input-field" /></div>
                <div class="flex-1 min-w-[160px]"><label class="lbl" data-en="Link (https://…)" data-am="አገናኝ (https://…)">Link (https://…)</label><input id="reslink_url" class="input-field" placeholder="https://" /></div>
                <button type="submit" class="btn-ghost" data-en="Add link" data-am="አገናኝ ጨምር">Add link</button>
              </form>
            </div>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Events" data-am="ዝግጅቶች">Events</span> · <span id="evtCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4 space-y-4">
            <div>
              <p class="lbl" data-en="Pending proposals" data-am="በመጠባበቅ ላይ ያሉ ሐሳቦች">Pending proposals</p>
              <div id="evtPending" class="space-y-2"></div>
            </div>
            <div>
              <p class="lbl" data-en="Decided" data-am="ውሳኔ የተሰጣቸው">Decided</p>
              <div id="evtDecided" class="space-y-2"></div>
            </div>
            <form id="evtCreateForm" class="grid sm:grid-cols-2 gap-2 bg-surface-low rounded p-3 border border-outline-soft/30">
              <p class="sm:col-span-2 lbl" data-en="Create event (immediately approved)" data-am="ዝግጅት ፍጠር (ወዲያውኑ ጸድቋል)">Create event (immediately approved)</p>
              <input id="evt_title" class="input-field sm:col-span-2" placeholder="Title" />
              <textarea id="evt_desc" class="input-field sm:col-span-2" rows="2" placeholder="Description (optional)"></textarea>
              <div><label class="lbl" data-en="Start" data-am="ጀምር">Start</label><input id="evt_start" type="datetime-local" class="input-field" /></div>
              <div><label class="lbl" data-en="End (optional)" data-am="ጨርስ (አማራጭ)">End (optional)</label><input id="evt_end" type="datetime-local" class="input-field" /></div>
              <button type="submit" class="btn-primary sm:col-span-2" data-en="Create" data-am="ፍጠር">Create</button>
            </form>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Members" data-am="አባላት">Members</span> · <span id="memCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4">
            <div class="mb-4 space-y-3">
              <div class="flex items-center gap-1 bg-surface-mid rounded-full p-0.5 w-max border border-outline-soft/50">
                <button type="button" id="modeExisting" class="seg-active px-3 py-1 text-xs font-semibold rounded-full" data-en="Add existing" data-am="ነባር ጨምር">Add existing</button>
                <button type="button" id="modeNew" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft" data-en="Create new" data-am="አዲስ ፍጠር">Create new</button>
              </div>

              <form id="addExistingForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30">
                <div class="min-w-[110px]"><label class="lbl" data-en="Type" data-am="ዓይነት">Type</label>
                  <select id="ax_type" class="input-field"><option value="teacher" data-en="Teacher" data-am="መምህር">Teacher</option><option value="student" data-en="Student" data-am="ተማሪ">Student</option><option value="" data-en="Anyone" data-am="ማንኛውም">Anyone</option></select></div>
                <div class="flex-1 min-w-[200px]"><label class="lbl" data-en="Search person" data-am="ሰው ፈልግ">Search person</label>
                  <input id="ax_search" class="input-field" placeholder="Name or phone…" autocomplete="off" />
                  <select id="ax_person" class="input-field mt-2"><option value="">—</option></select></div>
                <div class="min-w-[120px]"><label class="lbl" data-en="Level" data-am="ደረጃ">Level</label><select id="ax_level" class="input-field"><option value="">—</option></select></div>
                <div class="min-w-[100px]"><label class="lbl" data-en="Title" data-am="ሚና">Title</label><input id="ax_title" class="input-field" /></div>
                <button type="submit" class="btn-primary" data-en="Add" data-am="ጨምር">Add</button>
              </form>

              <form id="createNewForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30" style="display:none">
                <div class="min-w-[110px]"><label class="lbl" data-en="Role" data-am="ሚና">Role</label><select id="cn_role" class="input-field"><option value="teacher" data-en="Teacher" data-am="መምህር">Teacher</option><option value="student" data-en="Student" data-am="ተማሪ">Student</option></select></div>
                <div class="min-w-[110px]"><label class="lbl" data-en="First name" data-am="ስም">First name</label><input id="cn_first" class="input-field" /></div>
                <div class="min-w-[110px]"><label class="lbl" data-en="Last name" data-am="የአባት ስም">Last name</label><input id="cn_last" class="input-field" /></div>
                <div class="flex-1 min-w-[160px]"><label class="lbl" data-en="Email" data-am="ኢሜይል">Email</label><input id="cn_email" type="email" class="input-field" /></div>
                <div class="min-w-[110px]"><label class="lbl" data-en="Phone" data-am="ስልክ">Phone</label><input id="cn_phone" class="input-field" /></div>
                <div class="min-w-[130px]"><label class="lbl" data-en="Password (optional)" data-am="የይለፍ ቃል (አማራጭ)">Password (optional)</label><input id="cn_password" class="input-field" /></div>
                <div class="min-w-[110px]"><label class="lbl" data-en="Level" data-am="ደረጃ">Level</label><select id="cn_level" class="input-field"><option value="">—</option></select></div>
                <button type="submit" class="btn-primary" data-en="Create &amp; add" data-am="ፍጠርና ጨምር">Create &amp; add</button>
              </form>

              <div id="credBox" class="hidden text-sm bg-olive/10 text-olive rounded p-3 border border-olive/30"></div>
            </div>
            <div style="overflow-x:auto"><table class="data">
              <thead><tr><th data-en="Name" data-am="ስም">Name</th><th data-en="Level" data-am="ደረጃ">Level</th><th data-en="Title" data-am="ሚና">Title</th><th class="text-right">&nbsp;</th></tr></thead>
              <tbody id="memBody"><tr><td colspan="4" class="text-center text-ink-soft py-8" data-en="No members yet." data-am="አባል የለም።">No members yet.</td></tr></tbody>
            </table></div>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Public registrations" data-am="የይፋ ምዝገባዎች">Public registrations</span> · <span id="regCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4"><div id="regForms" class="space-y-4"><p class="text-sm text-ink-soft" data-en="No registration form for this department." data-am="ለዚህ ክፍል የምዝገባ ቅጽ የለም።">No registration form for this department.</p></div></div>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Blocking error modal (project rule: errors are modals, not vanishing toasts) -->
<div id="regErrModal" style="position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;background:rgba(20,24,36,0.4);padding:16px;">
  <div class="panel" style="max-width:420px;width:100%;padding:22px;">
    <h3 class="font-display text-lg" style="color:#ba1a1a;margin-bottom:8px;" data-en="Something went wrong" data-am="ችግር ተፈጥሯል">Something went wrong</h3>
    <p id="regErrMsg" class="text-sm text-ink-soft" style="white-space:pre-wrap;margin-bottom:18px;"></p>
    <div style="text-align:right;"><button id="regErrOk" class="btn-primary" data-en="OK" data-am="እሺ">OK</button></div>
  </div>
</div>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}</style>

<script>
  var depts=[], people=[], current=null, levels=[], roster=[], resources=[];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function dlabel(d){ return curLang()==='am'?(d.name_am||d.name):(d.name||d.name_am); }
  function v(id){return document.getElementById(id);}

  function applyLang(lang){ if(lang!=='en'&&lang!=='am')lang='en'; document.documentElement.setAttribute('data-lang',lang);
    document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var t=el.getAttribute('data-'+lang); if(t!==null)el.innerHTML=t; });
    document.querySelectorAll('[data-lang]').forEach(function(b){ if(b.tagName==='BUTTON'){ b.classList.toggle('seg-active',b.dataset.lang===lang); } });
    renderDeptList(); if(current){ renderLevels(); fillLevelSelect(); renderRoster(); renderEligibility(); renderResources(); renderEvents(); renderRegForms(); }
  }
  document.querySelectorAll('header [data-lang]').forEach(function(b){ b.addEventListener('click',function(){applyLang(b.dataset.lang);}); });

  function renderDeptList(){
    var w=v('deptList');
    if(!depts.length){ w.innerHTML='<p class="text-center text-ink-soft py-8 text-sm" data-en="You don\'t lead any department yet." data-am="እስካሁን የሚመሩት ክፍል የለም።">You don\'t lead any department yet.</p>'; return; }
    w.innerHTML=depts.map(function(d){ return '<button class="nav-item '+(current&&current.id===d.id?'active':'')+'" data-dept="'+d.id+'"><span class="'+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(dlabel(d))+'</span><span class="text-[11px] text-ink-soft">'+d.member_count+'</span></button>'; }).join('');
  }

  async function selectDept(id){
    current=depts.find(function(d){return d.id===id;}); if(!current)return;
    v('empty').classList.add('hidden'); v('detail').classList.remove('hidden');
    v('detName').textContent=dlabel(current); v('detSub').textContent=current.description||'';
    renderDeptList();
    await Promise.all([loadLevels(id), loadRoster(id), loadEligibility(id), loadResources(id), loadEvents(id), loadRegForms(id)]);
    setMode('existing');
  }

  async function loadResources(id){ try{ var r=await gs.api('/api/staff/resources.php?department_id='+id); resources=r.data||[]; renderResources(); }catch(e){ resources=[]; renderResources(); } }
  function renderResources(){
    var w=v('resList'); if(!w) return;
    v('resCount').textContent=resources.length;
    if(!resources.length){ w.innerHTML='<p class="text-sm text-ink-soft" data-en="No resources yet." data-am="እስካሁን ግብዓት የለም።">No resources yet.</p>'; return; }
    w.innerHTML=resources.map(function(r){
      return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30">'+
        '<a href="'+escHtml(r.url)+'" target="_blank" rel="noopener" class="text-sm text-primary hover:underline">'+escHtml(r.title)+'</a>'+
        '<button class="btn-icon danger" data-delres="'+r.id+'" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>';
    }).join('');
  }

  var events=[];
  var EVT_STATUS_CHIP={
    pending:{bg:'#fed175',fg:'#795901',en:'Pending',am:'በመጠባበቅ ላይ'},
    approved:{bg:'#a2b665',fg:'#384700',en:'Approved',am:'ጸድቋል'},
    rejected:{bg:'#f3caca',fg:'#9b1c1c',en:'Rejected',am:'ውድቅ ተደርጓል'}
  };
  function evtChip(status){
    var c=EVT_STATUS_CHIP[status]||EVT_STATUS_CHIP.pending;
    return '<span class="text-[11px] font-semibold uppercase tracking-widestest px-2 py-0.5 rounded-full" style="background:'+c.bg+';color:'+c.fg+'">'+(curLang()==='am'?c.am:c.en)+'</span>';
  }
  async function loadEvents(id){ try{ var r=await gs.api('/api/staff/events.php?department_id='+id); events=r.data||[]; renderEvents(); }catch(e){ events=[]; renderEvents(); } }
  function renderEvents(){
    var pw=v('evtPending'), dw=v('evtDecided'); if(!pw||!dw) return;
    v('evtCount').textContent=events.length;
    var pending=events.filter(function(e){return e.status==='pending';});
    var decided=events.filter(function(e){return e.status!=='pending';});
    pw.innerHTML=pending.length?pending.map(function(ev){
      return '<div class="bg-surface-low rounded px-3 py-2 border border-outline-soft/30" data-evt="'+ev.id+'">'+
        '<div class="flex items-center justify-between gap-2 flex-wrap"><span class="font-medium text-sm">'+escHtml(ev.title)+'</span>'+evtChip(ev.status)+'</div>'+
        (ev.description?'<p class="text-xs text-ink-soft mt-1">'+escHtml(ev.description)+'</p>':'')+
        '<p class="text-[11px] text-ink-soft mt-1">'+escHtml(ev.start_datetime||'')+(ev.end_datetime?(' – '+escHtml(ev.end_datetime)):'')+
        (ev.created_by_email?(' · '+escHtml(ev.created_by_email)):'')+'</p>'+
        '<div class="flex gap-3 mt-2"><button class="text-xs font-semibold text-olive hover:underline" data-evt-approve="'+ev.id+'" data-en="Approve" data-am="ፍቀድ">'+(curLang()==='am'?'ፍቀድ':'Approve')+'</button>'+
        '<button class="text-xs font-semibold text-error hover:underline" data-evt-reject="'+ev.id+'">'+(curLang()==='am'?'ውድቅ አድርግ':'Reject')+'</button></div></div>';
    }).join('') : '<p class="text-sm text-ink-soft" data-en="No pending proposals." data-am="ምንም ያልተወሰነ ሐሳብ የለም።">No pending proposals.</p>';
    dw.innerHTML=decided.length?decided.map(function(ev){
      return '<div class="bg-surface-low rounded px-3 py-2 border border-outline-soft/30">'+
        '<div class="flex items-center justify-between gap-2 flex-wrap"><span class="font-medium text-sm">'+escHtml(ev.title)+'</span>'+evtChip(ev.status)+'</div>'+
        '<p class="text-[11px] text-ink-soft mt-1">'+escHtml(ev.start_datetime||'')+(ev.end_datetime?(' – '+escHtml(ev.end_datetime)):'')+'</p></div>';
    }).join('') : '<p class="text-sm text-ink-soft" data-en="No decided events yet." data-am="ገና ውሳኔ የተሰጠው ዝግጅት የለም።">No decided events yet.</p>';
  }
  v('evtCreateForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var body={action:'create',department_id:current.id,title:v('evt_title').value.trim(),start_datetime:v('evt_start').value};
    var desc=v('evt_desc').value.trim(); if(desc) body.description=desc;
    var end=v('evt_end').value; if(end) body.end_datetime=end;
    if(!body.title||!body.start_datetime){gs.toast(curLang()==='am'?'ርዕስ እና መጀመሪያ ጊዜ ያስፈልጋሉ':'Title and start time are required','error');return;}
    try{
      await gs.api('/api/staff/events.php',{method:'POST',body:JSON.stringify(body)});
      v('evt_title').value='';v('evt_desc').value='';v('evt_start').value='';v('evt_end').value='';
      await loadEvents(current.id); gs.toast(curLang()==='am'?'ተፈጠረ':'Created','success');
    }catch(err){gs.toast(err.message,'error');}
  });

  var eligibility=null;
  async function loadEligibility(id){ try{ var r=await gs.api('/api/staff/eligibility.php?department_id='+id); eligibility=r; renderEligibility(); }catch(e){ eligibility=null; renderEligibility(); } }
  function renderEligibility(){
    var b=v('eligBody'); if(!b) return;
    if(!eligibility){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-6">—</td></tr>'; v('eligNote').textContent=''; return; }
    var m=eligibility.members||[];
    v('eligNote').textContent=(curLang()==='am'?'ዝቅተኛ መገኘት ':'min ')+eligibility.threshold+'%';
    if(!m.length){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-6" data-en="No members." data-am="አባል የለም።">No members.</td></tr>'; return; }
    b.innerHTML=m.map(function(x){
      var lvl=curLang()==='am'?(x.level_name_am||x.level_name):x.level_name;
      var serving;
      if(!x.has_data){ serving='<span class="pill pill-archived">'+(curLang()==='am'?'መረጃ የለም':'no data')+'</span>'; }
      else if(x.eligible){ serving='<span class="pill pill-active">'+(curLang()==='am'?'ብቁ':'eligible')+'</span>'; }
      else { serving='<span class="pill" style="background:rgba(186,26,26,0.1);color:#ba1a1a">'+(curLang()==='am'?'ብቁ አይደለም':'not eligible')+'</span>'; }
      var rate=x.has_data?(x.rate+'% ('+x.attended+'/'+x.total+')'):'—';
      return '<tr><td class="font-medium">'+escHtml(x.name)+'</td><td class="ethiopic text-ink-soft">'+escHtml(lvl||'—')+'</td><td class="text-ink-soft text-sm">'+rate+'</td><td>'+serving+'</td></tr>';
    }).join('');
  }

  async function loadLevels(id){ try{ var r=await gs.api('/api/staff/levels.php?department_id='+id); levels=r.data||[]; renderLevels(); fillLevelSelect(); }catch(e){gs.toast(e.message,'error');} }
  function renderLevels(){ v('lvlCount').textContent=levels.length; var w=v('levelList');
    if(!levels.length){ w.innerHTML='<p class="text-sm text-ink-soft" data-en="No levels." data-am="ደረጃ የለም።">No levels.</p>'; return; }
    w.innerHTML=levels.map(function(l){ return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30"><span class="text-sm '+(curLang()==='am'?'ethiopic':'')+'">#'+l.rank+' '+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+' <span class="text-xs text-ink-soft">('+l.member_count+')</span></span><button class="btn-icon danger" data-dellevel="'+l.id+'" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>'; }).join('');
  }
  function fillLevelSelect(){
    var opts='<option value="">'+(curLang()==='am'?'— ደረጃ የለም':'— No level')+'</option>'+levels.map(function(l){return '<option value="'+l.id+'">'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>';}).join('');
    ['ax_level','cn_level'].forEach(function(id){ var el=v(id); if(el){ var cur=el.value; el.innerHTML=opts; el.value=cur; } });
  }

  async function loadRoster(id){ try{ var r=await gs.api('/api/staff/members.php?department_id='+id); roster=r.data||[]; renderRoster(); }catch(e){gs.toast(e.message,'error');} }
  function renderRoster(){ v('memCount').textContent=roster.length; var b=v('memBody');
    if(!roster.length){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-8" data-en="No members yet." data-am="አባል የለም።">No members yet.</td></tr>'; return; }
    b.innerHTML=roster.map(function(m){ var lo='<option value="">—</option>'+levels.map(function(l){return '<option value="'+l.id+'" '+(m.level_id===l.id?'selected':'')+'>'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>';}).join('');
      return '<tr><td><span class="font-medium">'+escHtml(m.person_name)+'</span>'+(m.is_head?' <span class="pill pill-active">'+(curLang()==='am'?'ሃላፊ':'Head')+'</span>':'')+'</td>'+
        '<td><select class="input-field" style="padding:5px 8px" data-setlevel="'+m.id+'" '+(levels.length?'':'disabled')+'>'+lo+'</select></td>'+
        '<td><input class="input-field" style="padding:5px 8px;max-width:130px" value="'+escHtml(m.title||'')+'" data-settitle="'+m.id+'" /></td>'+
        '<td class="text-right"><button class="btn-icon danger" data-delmem="'+m.id+'" title="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td></tr>'; }).join('');
  }
  // Live people search for the "Add existing" picker.
  var axSearchTimer=null;
  async function axSearch(){
    if(!current) return;
    var q=v('ax_search').value.trim(), type=v('ax_type').value;
    var url='/api/staff/people.php?exclude_dept_id='+current.id+(type?('&type='+type):'')+(q?('&q='+encodeURIComponent(q)):'');
    try{
      var r=await gs.api(url); var list=r.data||[];
      var sel=v('ax_person');
      sel.innerHTML='<option value="">'+(curLang()==='am'?'— ምረጥ':'— Select')+'</option>'+list.map(function(p){
        var tag=p.type==='teacher'?(curLang()==='am'?' · መምህር':' · teacher'):(p.type==='student'?(curLang()==='am'?' · ተማሪ':' · student'):'');
        return '<option value="'+p.id+'">'+escHtml(p.first_name+' '+p.last_name)+(p.phone?(' · '+escHtml(p.phone)):'')+tag+'</option>';
      }).join('');
    }catch(e){ /* silent */ }
  }
  function setMode(mode){
    var ex=mode!=='new';
    v('addExistingForm').style.display=ex?'flex':'none';
    v('createNewForm').style.display=ex?'none':'flex';
    v('modeExisting').classList.toggle('seg-active',ex); v('modeExisting').classList.toggle('text-ink-soft',!ex);
    v('modeNew').classList.toggle('seg-active',!ex); v('modeNew').classList.toggle('text-ink-soft',ex);
    v('credBox').classList.add('hidden');
    if(ex) axSearch();
  }

  v('levelForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var body={department_id:current.id,name:v('lvl_name').value.trim(),name_am:v('lvl_name_am').value.trim(),rank:parseInt(v('lvl_rank').value||'0',10)};
    if(!body.name){gs.toast('Name required','error');return;}
    try{ await gs.api('/api/staff/levels.php',{method:'POST',body:JSON.stringify(body)}); v('lvl_name').value='';v('lvl_name_am').value='';v('lvl_rank').value='0'; await loadLevels(current.id); reloadDepts(current.id); }catch(err){gs.toast(err.message,'error');}
  });
  v('modeExisting').addEventListener('click',function(){setMode('existing');});
  v('modeNew').addEventListener('click',function(){setMode('new');});
  v('ax_type').addEventListener('change',axSearch);
  v('ax_search').addEventListener('input',function(){ clearTimeout(axSearchTimer); axSearchTimer=setTimeout(axSearch,250); });

  v('addExistingForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var pid=v('ax_person').value; if(!pid){gs.toast(curLang()==='am'?'ሰው ይምረጡ':'Pick a person','error');return;}
    var body={action:'add_existing',department_id:current.id,person_id:parseInt(pid,10),level_id:v('ax_level').value?parseInt(v('ax_level').value,10):null,title:v('ax_title').value.trim()};
    try{ await gs.api('/api/staff/roster.php',{method:'POST',body:JSON.stringify(body)}); v('ax_search').value='';v('ax_person').innerHTML='<option value="">—</option>';v('ax_level').value='';v('ax_title').value=''; await loadRoster(current.id); reloadDepts(current.id); gs.toast(curLang()==='am'?'ተጨመረ':'Added','success'); }catch(err){gs.toast(err.message,'error');}
  });

  v('createNewForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var body={action:'create_new',department_id:current.id,role:v('cn_role').value,
      first_name:v('cn_first').value.trim(),last_name:v('cn_last').value.trim(),email:v('cn_email').value.trim(),
      phone:v('cn_phone').value.trim(),password:v('cn_password').value,
      level_id:v('cn_level').value?parseInt(v('cn_level').value,10):null};
    if(!body.first_name||!body.last_name||!body.email){gs.toast(curLang()==='am'?'ስም እና ኢሜይል ያስፈልጋሉ':'Name and email are required','error');return;}
    try{
      var r=await gs.api('/api/staff/roster.php',{method:'POST',body:JSON.stringify(body)});
      v('cn_first').value='';v('cn_last').value='';v('cn_email').value='';v('cn_phone').value='';v('cn_password').value='';v('cn_level').value='';
      if(r.generated_password){
        var cb=v('credBox'); cb.classList.remove('hidden');
        cb.textContent=(curLang()==='am'?'ተፈጠረ። የይለፍ ቃል፡ ':'Created. Password: ')+r.generated_password+(curLang()==='am'?' — አሁን ያጋሩት (እንደገና አይታይም)።':' — share it now (it will not be shown again).');
      } else { gs.toast(curLang()==='am'?'ተፈጠረ':'Created','success'); }
      await loadRoster(current.id); reloadDepts(current.id);
    }catch(err){gs.toast(err.message,'error');}
  });

  v('resUploadForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var fileInput=v('res_file'); if(!fileInput.files||!fileInput.files.length){gs.toast('Choose a file','error');return;}
    var fd=new FormData(); fd.append('scope_id',current.id); fd.append('title',v('res_title').value.trim()); fd.append('file',fileInput.files[0]);
    try{
      var csrf=await gs.ensureCsrf();
      var res=await fetch('/api/staff/resources.php',{method:'POST',headers:{'X-CSRF-Token':csrf},body:fd});
      var d; try{d=await res.json();}catch(err){d={};}
      if(!res.ok) throw new Error(d.error||('HTTP '+res.status));
      v('res_title').value=''; fileInput.value='';
      await loadResources(current.id); gs.toast('Uploaded','success');
    }catch(err){gs.toast(err.message,'error');}
  });

  v('resLinkForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var title=v('reslink_title').value.trim(), url=v('reslink_url').value.trim();
    if(!title||!url){gs.toast('Title and link are required','error');return;}
    try{
      await gs.api('/api/staff/resources.php',{method:'POST',body:JSON.stringify({scope_id:current.id,title:title,url:url})});
      v('reslink_title').value=''; v('reslink_url').value='';
      await loadResources(current.id); gs.toast('Added','success');
    }catch(err){gs.toast(err.message,'error');}
  });

  document.addEventListener('click', async function(e){
    var d=e.target.closest('[data-dept]'); if(d){ selectDept(parseInt(d.dataset.dept,10)); return; }
    var dr=e.target.closest('[data-delres]'); if(dr){ if(!await gs.confirm(curLang()==='am'?'ይህን ግብዓት ያስወግዱ?':'Remove this resource?'))return; try{await gs.api('/api/staff/resources.php',{method:'DELETE',body:JSON.stringify({id:parseInt(dr.dataset.delres,10)})});await loadResources(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var rm=e.target.closest('[data-delmem]'); if(rm){ if(!await gs.confirm(curLang()==='am'?'ከክፍሉ ያስወግዱ?':'Remove from department?'))return; try{await gs.api('/api/staff/members.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rm.dataset.delmem,10)})});await loadRoster(current.id);reloadDepts(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var rl=e.target.closest('[data-dellevel]'); if(rl){ if(!await gs.confirm(curLang()==='am'?'ይህን ደረጃ ያስወግዱ?':'Remove this level?'))return; try{await gs.api('/api/staff/levels.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rl.dataset.dellevel,10)})});await loadLevels(current.id);await loadRoster(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var ea=e.target.closest('[data-evt-approve]'); if(ea){ try{await gs.api('/api/staff/events.php',{method:'POST',body:JSON.stringify({action:'approve',id:parseInt(ea.dataset.evtApprove,10)})});await loadEvents(current.id);gs.toast(curLang()==='am'?'ጸድቋል':'Approved','success');}catch(err){gs.toast(err.message,'error');} return; }
    var er=e.target.closest('[data-evt-reject]'); if(er){ if(!await gs.confirm(curLang()==='am'?'ይህን ሐሳብ ውድቅ ማድረግ ይፈልጋሉ?':'Reject this proposal?'))return; try{await gs.api('/api/staff/events.php',{method:'POST',body:JSON.stringify({action:'reject',id:parseInt(er.dataset.evtReject,10)})});await loadEvents(current.id);gs.toast(curLang()==='am'?'ውድቅ ተደርጓል':'Rejected','success');}catch(err){gs.toast(err.message,'error');} return; }
  });
  document.addEventListener('change', async function(e){
    var sl=e.target.closest('[data-setlevel]'); if(sl){ try{await gs.api('/api/staff/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(sl.dataset.setlevel,10),level_id:sl.value?parseInt(sl.value,10):null})});await loadRoster(current.id);await loadLevels(current.id);}catch(err){gs.toast(err.message,'error');} }
  });
  document.addEventListener('blur', async function(e){
    var ti=e.target.closest('[data-settitle]'); if(ti){ try{await gs.api('/api/staff/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(ti.dataset.settitle,10),title:ti.value.trim()})});}catch(err){gs.toast(err.message,'error');} }
  }, true);

  async function reloadDepts(keepId){ var r=await gs.api('/api/staff/departments.php'); depts=r.data||[]; renderDeptList(); if(keepId){ var s=depts.find(function(d){return d.id===keepId;}); if(s)selectDept(keepId); } }

  // ===== Public registrations (department-scoped) =====
  var REG_API='/api/staff/registrations.php';
  var regForms=[], regSubs={}, regSubPage={};
  var REG_TYPES=[['text','Short text','አጭር ጽሑፍ'],['textarea','Long text','ረጅም ጽሑፍ'],['email','Email','ኢሜይል'],['phone','Phone','ስልክ'],['number','Number','ቁጥር'],['date','Date','ቀን'],['select','Dropdown','ተቆልቋይ'],['radio','Single choice','ነጠላ ምርጫ'],['checkbox','Multiple choice','ብዙ ምርጫ']];
  var REG_CHOICE=['select','radio','checkbox'];
  var REG_SUBST={new:['New','አዲስ'],seen:['Seen','የታየ'],contacted:['Contacted','የተገናኘ']};
  function regErr(msg){ v('regErrMsg').textContent=msg; v('regErrModal').style.display='flex'; }
  v('regErrOk').addEventListener('click',function(){ v('regErrModal').style.display='none'; });
  function regTypeLabel(t){ var m=REG_TYPES.find(function(x){return x[0]===t;}); return m?(curLang()==='am'?m[2]:m[1]):t; }
  function regFlabel(f){ return curLang()==='am'?(f.title_am||f.title_en):(f.title_en||f.title_am); }

  async function loadRegForms(id){
    try{ var r=await gs.api(REG_API); regForms=(r.data||[]).filter(function(f){ return f.department_id===id; }); renderRegForms(); }
    catch(e){ regForms=[]; renderRegForms(); }
  }

  function renderRegForms(){
    var w=v('regForms'); if(!w) return;
    v('regCount').textContent=regForms.length;
    if(!regForms.length){ w.innerHTML='<p class="text-sm text-ink-soft" data-en="No registration form for this department." data-am="ለዚህ ክፍል የምዝገባ ቅጽ የለም።">No registration form for this department.</p>'; return; }
    w.innerHTML=regForms.map(regFormCard).join('');
    regForms.forEach(function(f){ if(regSubs[f.id]) renderRegSubs(f.id); });
  }

  function regTypeOptions(sel){ return REG_TYPES.map(function(t){ return '<option value="'+t[0]+'" '+(t[0]===sel?'selected':'')+'>'+escHtml(curLang()==='am'?t[2]:t[1])+'</option>'; }).join(''); }

  function regFormCard(f){
    var st=['open','limited','closed'].map(function(s){ var lbl={open:['Open','ክፍት'],limited:['Limited','የተወሰነ'],closed:['Closed','ዝግ']}[s]; return '<option value="'+s+'" '+(f.status===s?'selected':'')+'>'+(curLang()==='am'?lbl[1]:lbl[0])+'</option>'; }).join('');
    var fields=(f.fields||[]).map(function(fld,i){
      var lbl=curLang()==='am'?(fld.label_am||fld.label_en):fld.label_en;
      var req=fld.is_required==1?'<span class="pill" style="background:rgba(186,26,26,0.1);color:#ba1a1a">'+(curLang()==='am'?'የግድ':'required')+'</span>':'';
      var opt=(fld.options&&fld.options.length)?'<span class="text-[11px] text-ink-soft">· '+fld.options.length+'</span>':'';
      return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30">'+
        '<div class="flex items-center gap-2 min-w-0"><span class="text-sm font-medium truncate '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(lbl)+'</span><span class="text-[11px] uppercase tracking-widestest text-outline">'+escHtml(regTypeLabel(fld.field_type))+'</span>'+req+opt+'</div>'+
        '<div class="flex items-center gap-1 flex-shrink-0">'+
          '<button class="btn-icon" data-regfmove="up" data-regfid="'+fld.id+'" data-regform="'+f.id+'" '+(i===0?'style="opacity:.3" disabled':'')+' title="Up"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 15l-6-6-6 6"/></svg></button>'+
          '<button class="btn-icon" data-regfmove="down" data-regfid="'+fld.id+'" data-regform="'+f.id+'" '+(i===(f.fields.length-1)?'style="opacity:.3" disabled':'')+' title="Down"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></button>'+
          '<button class="btn-icon" data-regfedit="'+fld.id+'" data-regform="'+f.id+'" title="Edit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>'+
          '<button class="btn-icon danger" data-regfdel="'+fld.id+'" data-regform="'+f.id+'" title="Remove"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>'+
        '</div></div>';
    }).join('') || '<p class="text-sm text-ink-soft" data-en="No fields yet." data-am="ገና ጥያቄ የለም።">No fields yet.</p>';

    return '<div class="rounded border border-outline-soft/40" data-regcard="'+f.id+'">'+
      '<div class="flex items-center justify-between gap-3 flex-wrap px-4 py-3 bg-surface-low rounded-t">'+
        '<span class="font-display text-base '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(regFlabel(f))+'</span>'+
        '<label class="flex items-center gap-2 text-xs text-ink-soft"><span data-en="Status" data-am="ሁኔታ">Status</span>'+
          '<select class="input-field" style="width:auto;padding:6px 10px" data-regstatus="'+f.id+'">'+st+'</select></label>'+
      '</div>'+
      '<div class="p-4 space-y-4">'+
        '<div><p class="lbl mb-2" data-en="Fields" data-am="ጥያቄዎች">Fields</p><div class="space-y-2">'+fields+'</div></div>'+
        regFieldFormHtml(f.id)+
        '<div><button class="btn-ghost" data-regsubtoggle="'+f.id+'">'+(curLang()==='am'?'ምዝገባዎች አሳይ':'Show submissions')+' ('+f.submission_count+')</button>'+
          '<div class="mt-3 '+(regSubs[f.id]?'':'hidden')+'" data-regsubsbox="'+f.id+'"></div></div>'+
      '</div></div>';
  }

  function regFieldFormHtml(fid){
    return '<form class="bg-surface-low rounded p-3 border border-outline-soft/30 space-y-2" data-regfieldform="'+fid+'">'+
      '<div class="flex items-center justify-between"><p class="lbl rff-title" data-en="Add a field" data-am="ጥያቄ ጨምር">Add a field</p><button type="button" class="text-xs text-ink-soft hover:underline rff-cancel hidden" data-en="Cancel" data-am="ሰርዝ">Cancel</button></div>'+
      '<input type="hidden" class="rff-id" value="" />'+
      '<div class="grid sm:grid-cols-2 gap-2">'+
        '<input class="input-field rff-len" placeholder="'+(curLang()==='am'?'መለያ (እንግሊዝኛ)':'Label (English)')+'" />'+
        '<input class="input-field rff-lam ethiopic" placeholder="'+(curLang()==='am'?'መለያ (አማርኛ)':'Label (Amharic)')+'" />'+
        '<select class="input-field rff-type">'+regTypeOptions('text')+'</select>'+
        '<label class="flex items-center gap-2 text-sm text-ink-soft"><input type="checkbox" class="rff-req w-4 h-4" /><span data-en="Required" data-am="የግድ">Required</span></label>'+
        '<input class="input-field rff-phen" placeholder="'+(curLang()==='am'?'ማሳያ (እንግሊዝኛ)':'Placeholder (EN)')+'" />'+
        '<input class="input-field rff-pham ethiopic" placeholder="'+(curLang()==='am'?'ማሳያ (አማርኛ)':'Placeholder (AM)')+'" />'+
      '</div>'+
      '<div class="rff-optswrap hidden"><label class="lbl" data-en="Options: one per line as value|English|Amharic" data-am="አማራጮች፦ በእያንዳንዱ መስመር value|English|Amharic">Options — one per line: value|English|Amharic</label>'+
        '<textarea class="input-field rff-opts" rows="3" placeholder="male|Male|ወንድ"></textarea></div>'+
      '<button type="submit" class="btn-primary rff-submit" data-en="Add field" data-am="ጥያቄ ጨምር">Add field</button>'+
    '</form>';
  }

  function regCard(fid){ return document.querySelector('[data-regcard="'+fid+'"]'); }
  function regToggleOpts(form){ var t=form.querySelector('.rff-type').value; form.querySelector('.rff-optswrap').classList.toggle('hidden', REG_CHOICE.indexOf(t)<0); }
  function regParseOpts(txt){ return txt.split('\n').map(function(l){ return l.trim(); }).filter(Boolean).map(function(l){ var p=l.split('|'); return {value:(p[0]||'').trim(), label_en:(p[1]||p[0]||'').trim(), label_am:(p[2]||'').trim()}; }).filter(function(o){ return o.value; }); }

  async function loadRegSubs(fid, page){
    page=page||1; regSubPage[fid]=page;
    try{ var d=await gs.api(REG_API+'?resource=submissions&form_id='+fid+'&page='+page); regSubs[fid]=d; renderRegSubs(fid); }
    catch(e){ regErr(e.message); }
  }
  function renderRegSubs(fid){
    var box=document.querySelector('[data-regsubsbox="'+fid+'"]'); if(!box) return;
    box.classList.remove('hidden');
    var d=regSubs[fid]; if(!d){ box.innerHTML='<p class="text-sm text-ink-soft py-2">…</p>'; return; }
    var rows=d.data||[];
    var head='<div style="overflow-x:auto"><table class="data"><thead><tr>'+
      '<th data-en="Name" data-am="ስም">Name</th><th data-en="Phone" data-am="ስልክ">Phone</th><th data-en="Date" data-am="ቀን">Date</th><th data-en="Status" data-am="ሁኔታ">Status</th><th></th></tr></thead><tbody>';
    var body = rows.length ? rows.map(function(s){
      var opts=['new','seen','contacted'].map(function(k){ return '<option value="'+k+'" '+(s.status===k?'selected':'')+'>'+(curLang()==='am'?REG_SUBST[k][1]:REG_SUBST[k][0])+'</option>'; }).join('');
      var det=s.items.map(function(i){ return '<div class="py-1"><span class="text-[11px] uppercase tracking-widestest text-outline '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(curLang()==='am'?(i.label_am||i.label_en):i.label_en)+'</span><div class="text-sm">'+escHtml(i.value||'—')+'</div></div>'; }).join('');
      return '<tr class="cursor-pointer" data-regsubrow="'+s.id+'"><td class="font-medium">'+escHtml(s.applicant_name||'—')+'</td><td class="text-ink-soft">'+escHtml(s.applicant_phone||'—')+'</td>'+
        '<td class="text-ink-soft text-sm">'+escHtml(s.created_at||'')+'</td>'+
        '<td><select class="input-field" style="padding:5px 8px" data-regsubstatus="'+s.id+'" data-regform="'+fid+'">'+opts+'</select></td>'+
        '<td class="text-right"><button class="btn-icon danger" data-regsubdel="'+s.id+'" data-regform="'+fid+'" title="Archive"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td></tr>'+
        '<tr class="hidden" data-regsubdetail="'+s.id+'"><td colspan="5" style="background:#eef2fa"><div class="grid sm:grid-cols-2 gap-x-8">'+det+'</div></td></tr>';
    }).join('') : '<tr><td colspan="5" class="text-center text-ink-soft py-6" data-en="No submissions." data-am="ምዝገባ የለም።">No submissions.</td></tr>';
    var pages=Math.max(1,Math.ceil(d.total/d.per_page));
    var nav='<div class="flex items-center justify-between mt-2"><button class="btn-ghost" data-regsubprev="'+fid+'" '+(d.page<=1?'disabled style="opacity:.4"':'')+' data-en="Previous" data-am="ቀዳሚ">Previous</button><span class="text-sm text-ink-soft">'+(curLang()==='am'?'ገጽ ':'Page ')+d.page+' / '+pages+'</span><button class="btn-ghost" data-regsubnext="'+fid+'" '+(d.page>=pages?'disabled style="opacity:.4"':'')+' data-en="Next" data-am="ቀጣይ">Next</button></div>';
    box.innerHTML=head+body+'</tbody></table></div>'+nav;
  }

  // Reg event delegation
  document.addEventListener('submit', async function(e){
    var ff=e.target.closest('[data-regfieldform]'); if(!ff) return;
    e.preventDefault();
    var fid=parseInt(ff.getAttribute('data-regfieldform'),10);
    var labelEn=ff.querySelector('.rff-len').value.trim();
    if(!labelEn){ regErr(curLang()==='am'?'የእንግሊዝኛ መለያ ያስፈልጋል':'English label is required'); return; }
    var type=ff.querySelector('.rff-type').value;
    var body={form_id:fid,label_en:labelEn,label_am:ff.querySelector('.rff-lam').value.trim(),field_type:type,
      is_required:ff.querySelector('.rff-req').checked?1:0,placeholder_en:ff.querySelector('.rff-phen').value.trim(),placeholder_am:ff.querySelector('.rff-pham').value.trim()};
    if(REG_CHOICE.indexOf(type)>=0){ body.options=regParseOpts(ff.querySelector('.rff-opts').value); if(!body.options.length){ regErr(curLang()==='am'?'ቢያንስ አንድ አማራጭ ያስፈልጋል':'Add at least one option'); return; } }
    var editId=ff.querySelector('.rff-id').value;
    body.action=editId?'field.update':'field.create'; if(editId) body.id=parseInt(editId,10);
    try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify(body)}); await loadRegForms(current.id); gs.toast(curLang()==='am'?'ተቀምጧል':'Saved','success'); }
    catch(err){ regErr(err.message); }
  });

  document.addEventListener('change', async function(e){
    var st=e.target.closest('[data-regstatus]'); if(st){ try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify({action:'form.update',id:parseInt(st.dataset.regstatus,10),status:st.value})}); var f=regForms.find(function(x){return x.id===parseInt(st.dataset.regstatus,10);}); if(f)f.status=st.value; gs.toast(curLang()==='am'?'ተዘምኗል':'Updated','success'); }catch(err){ regErr(err.message); loadRegForms(current.id); } return; }
    var ss=e.target.closest('[data-regsubstatus]'); if(ss){ try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify({action:'submission.status',id:parseInt(ss.dataset.regsubstatus,10),status:ss.value})}); gs.toast(curLang()==='am'?'ተዘምኗል':'Updated','success'); }catch(err){ regErr(err.message); } return; }
    var ty=e.target.closest('.rff-type'); if(ty){ var form=ty.closest('[data-regfieldform]'); if(form) regToggleOpts(form); }
  });

  document.addEventListener('click', async function(e){
    var tg=e.target.closest('[data-regsubtoggle]'); if(tg){ var fid=parseInt(tg.dataset.regsubtoggle,10); var box=document.querySelector('[data-regsubsbox="'+fid+'"]'); if(regSubs[fid]){ regSubs[fid]=null; box.classList.add('hidden'); box.innerHTML=''; } else { loadRegSubs(fid,1); } return; }
    var ed=e.target.closest('[data-regfedit]'); if(ed){ var fid2=parseInt(ed.dataset.regform,10); var fld=(regForms.find(function(x){return x.id===fid2;})||{fields:[]}).fields.find(function(x){return x.id===parseInt(ed.dataset.regfedit,10);}); if(!fld) return; var form=regCard(fid2).querySelector('[data-regfieldform]'); form.querySelector('.rff-id').value=fld.id; form.querySelector('.rff-len').value=fld.label_en||''; form.querySelector('.rff-lam').value=fld.label_am||''; form.querySelector('.rff-type').value=fld.field_type; form.querySelector('.rff-req').checked=fld.is_required==1; form.querySelector('.rff-phen').value=fld.placeholder_en||''; form.querySelector('.rff-pham').value=fld.placeholder_am||''; form.querySelector('.rff-opts').value=(fld.options||[]).map(function(o){return o.value+'|'+(o.label_en||'')+'|'+(o.label_am||'');}).join('\n'); regToggleOpts(form); form.querySelector('.rff-submit').textContent=curLang()==='am'?'አዘምን':'Update field'; form.querySelector('.rff-title').textContent=curLang()==='am'?'ጥያቄ አስተካክል':'Edit field'; form.querySelector('.rff-cancel').classList.remove('hidden'); form.scrollIntoView({behavior:'smooth',block:'center'}); return; }
    var cn=e.target.closest('.rff-cancel'); if(cn){ var form2=cn.closest('[data-regfieldform]'); form2.reset(); form2.querySelector('.rff-id').value=''; regToggleOpts(form2); form2.querySelector('.rff-submit').textContent=curLang()==='am'?'ጥያቄ ጨምር':'Add field'; form2.querySelector('.rff-title').textContent=curLang()==='am'?'ጥያቄ ጨምር':'Add a field'; cn.classList.add('hidden'); return; }
    var dl=e.target.closest('[data-regfdel]'); if(dl){ if(!await gs.confirm(curLang()==='am'?'ይህን ጥያቄ ያስወግዱ?':'Remove this field?'))return; try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify({action:'field.archive',id:parseInt(dl.dataset.regfdel,10)})}); await loadRegForms(current.id); }catch(err){ regErr(err.message); } return; }
    var mv=e.target.closest('[data-regfmove]'); if(mv){ var fid3=parseInt(mv.dataset.regform,10); var f3=regForms.find(function(x){return x.id===fid3;}); if(!f3)return; var arr=f3.fields.slice(); var idx=arr.findIndex(function(x){return x.id===parseInt(mv.dataset.regfid,10);}); var sw=mv.dataset.regfmove==='up'?idx-1:idx+1; if(sw<0||sw>=arr.length)return; var t=arr[idx];arr[idx]=arr[sw];arr[sw]=t; try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify({action:'field.reorder',form_id:fid3,order:arr.map(function(x){return x.id;})})}); await loadRegForms(current.id); }catch(err){ regErr(err.message); } return; }
    var sd=e.target.closest('[data-regsubdel]'); if(sd){ if(!await gs.confirm(curLang()==='am'?'ይህን ምዝገባ ያስቀምጡ?':'Archive this submission?'))return; try{ await gs.api(REG_API,{method:'POST',body:JSON.stringify({action:'submission.archive',id:parseInt(sd.dataset.regsubdel,10)})}); loadRegSubs(parseInt(sd.dataset.regform,10),regSubPage[sd.dataset.regform]||1); loadRegForms(current.id); }catch(err){ regErr(err.message); } return; }
    var sp=e.target.closest('[data-regsubprev]'); if(sp){ var pf=parseInt(sp.dataset.regsubprev,10); loadRegSubs(pf,Math.max(1,(regSubPage[pf]||1)-1)); return; }
    var sn=e.target.closest('[data-regsubnext]'); if(sn){ var nf=parseInt(sn.dataset.regsubnext,10); loadRegSubs(nf,(regSubPage[nf]||1)+1); return; }
    var row=e.target.closest('[data-regsubrow]'); if(row && !e.target.closest('select') && !e.target.closest('button')){ var dr=document.querySelector('[data-regsubdetail="'+row.dataset.regsubrow+'"]'); if(dr) dr.classList.toggle('hidden'); return; }
  });

  v('logoutBtn').addEventListener('click', async function(){ try{ await gs.api('/api/auth/logout.php',{method:'POST'}); }catch(e){} window.location.href='/'; });

  // ---------- notifications ----------
  var NOTIFS=[];
  function escN(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  async function loadNotifs(){
    try{ var d=await gs.api('/api/staff/notifications.php'); NOTIFS=d.data||[]; }catch(e){ NOTIFS=[]; }
    var unread=NOTIFS.filter(function(n){return !n.is_read;}).length;
    var badge=v('notifBadge'); badge.textContent=unread; badge.classList.toggle('hidden',unread===0);
    renderNotifs();
  }
  function renderNotifs(){
    var am=curLang()==='am';
    var ul=v('notifList');
    if(!NOTIFS.length){ ul.innerHTML='<li class="py-6 text-center text-ink-soft text-sm">'+(am?'ማሳወቂያ የለም።':'No notifications.')+'</li>'; return; }
    ul.innerHTML=NOTIFS.map(function(n){
      return '<li class="py-3 flex items-start justify-between gap-4'+(n.is_read?' opacity-60':'')+'">'+
        '<div><p class="font-medium text-sm">'+escN(n.title)+'</p>'+
        '<p class="text-sm text-ink-soft mt-0.5">'+escN(n.message)+'</p>'+
        '<p class="text-[11px] text-outline mt-1">'+escN(n.created_at||'')+'</p></div>'+
        (n.is_read?'':'<button class="js-notif-read shrink-0 text-xs font-semibold text-primary hover:underline" data-id="'+n.id+'">'+(am?'እንደተነበበ ምልክት':'Mark read')+'</button>')+
      '</li>';
    }).join('');
  }
  v('notifBell').addEventListener('click', function(){ v('notifPanel').classList.toggle('hidden'); });
  v('notifClose').addEventListener('click', function(){ v('notifPanel').classList.add('hidden'); });
  v('notifList').addEventListener('click', async function(e){
    var b=e.target.closest('.js-notif-read'); if(!b) return;
    try{ await gs.api('/api/staff/notifications.php',{method:'POST',body:JSON.stringify({id:parseInt(b.dataset.id,10)})}); loadNotifs(); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  (async function(){
    try{
      var d=await gs.api('/api/staff/departments.php');
      depts=d.data||[]; renderDeptList();
    }catch(e){ gs.toast(e.message,'error'); }
    loadNotifs();
  })();
  applyLang('en');
</script>
</body>
</html>
