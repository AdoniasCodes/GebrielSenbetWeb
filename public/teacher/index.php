<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'teacher') {
    header('Location: /'); exit;
}
$email = $_SESSION['user_email'] ?? '';
?><!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Teacher Portal · Mekane Selam Senbet School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
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
    .seg-active{background:#fed175;color:#16357e;}
    .input-sm{border:1px solid #c4d0e4;border-radius:6px;padding:6px 10px;font-size:14px;background:#fff;}
    .input-sm:focus{outline:2px solid #c9a14a;outline-offset:1px;}
    .stat-btn{padding:5px 10px;font-size:12px;font-weight:600;border-radius:5px;color:#3f4658;}
    .dept-chip{border:1px solid #c4d0e4;background:#fff;border-radius:999px;padding:8px 18px;font-size:13px;font-weight:600;color:#3f4658;transition:all .15s;}
    .dept-chip:hover{border-color:#2f52a6;color:#16357e;}
    .dept-chip.chip-active{background:#16357e;border-color:#16357e;color:#fff;}
    .btn-primary{background:#16357e;color:#f4f7fc;padding:10px 20px;border-radius:6px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;}
    .btn-primary:hover{background:#2f52a6;}
  </style>
</head>
<body>
  <header class="sticky top-0 z-40 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1100px] mx-auto px-6 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/><circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/></svg>
        </span>
        <span class="font-display text-lg font-semibold text-primary" data-en="Mekane Selam Senbet School · Teacher" data-am="መካነ ሰላም · መምህር">Mekane Selam Senbet School · Teacher</span>
      </a>
      <div class="flex items-center gap-3">
        <button id="notifBell" class="relative p-2 text-ink-soft hover:text-primary" aria-label="Notifications">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
          <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center">0</span>
        </button>
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

    <!-- Notifications panel -->
    <section id="notifPanel" class="panel hidden">
      <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
        <h2 class="font-display text-lg text-ink" data-en="Notifications" data-am="ማሳወቂያዎች">Notifications</h2>
        <button id="notifClose" class="text-ink-soft hover:text-primary p-1" aria-label="Close"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
      </header>
      <ul id="notifList" class="divide-y divide-outline-soft/20 px-6">
        <li class="py-6 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
      </ul>
    </section>

    <!-- Department selector -->
    <section>
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-2" data-en="Welcome" data-am="እንኳን ደህና መጡ">Welcome</p>
      <h1 class="font-display text-3xl text-primary" data-en="My departments" data-am="የእኔ ክፍሎች (ዲፓርትመንት)">My departments</h1>
      <p class="text-sm text-ink-soft mt-2" data-en="Pick a department to work with its students, or open your general classes." data-am="ከተማሪዎቹ ጋር ለመስራት ዲፓርትመንት ይምረጡ፣ ወይም አጠቃላይ ክፍሎችዎን ይክፈቱ።">Pick a department to work with its students, or open your general classes.</p>
      <div id="deptChips" class="flex flex-wrap gap-2 mt-5">
        <p class="text-sm text-ink-soft" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
      </div>
    </section>

    <!-- Workspace -->
    <section id="deptWs" class="panel hidden">
      <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h2 id="deptWsTitle" class="font-display text-lg text-ink">—</h2>
          <p id="deptWsSub" class="text-xs text-outline mt-0.5"></p>
        </div>
        <div class="flex bg-surface-mid rounded-md p-0.5 border border-outline-soft/50 flex-wrap" id="deptTabs">
          <button data-tab="grades" class="stat-btn seg-active" data-en="Grades" data-am="ውጤቶች">Grades</button>
          <button data-tab="att" class="stat-btn" data-en="Attendance" data-am="መገኘት">Attendance</button>
          <button data-tab="tasks" class="stat-btn" data-en="Tasks" data-am="ስራዎች">Tasks</button>
          <button data-tab="files" class="stat-btn" data-en="Files" data-am="ፋይሎች">Files</button>
          <button data-tab="ann" class="stat-btn" data-en="Announcements" data-am="ማስታወቂያዎች">Announcements</button>
          <button data-tab="events" class="stat-btn" data-en="Events" data-am="ዝግጅቶች">Events</button>
        </div>
      </header>

      <!-- Grades tab: class cards inside the dept (or all classes in General), then roster -->
      <div id="dTabGrades" class="p-6">
        <div id="deptClassCards" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6"></div>
        <div id="gradeWork" class="hidden border-t border-outline-soft/30 pt-5">
          <div class="flex items-center gap-3 mb-4 flex-wrap">
            <h3 id="gradeWorkTitle" class="font-display text-base text-ink">—</h3>
            <label class="text-xs font-semibold uppercase tracking-widestest text-outline ml-auto" data-en="Term" data-am="ወቅት">Term</label>
            <select id="termSel" class="input-sm"></select>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] uppercase tracking-widestest text-outline border-b border-outline-soft/30">
                  <th class="py-2 pr-4" data-en="Student" data-am="ተማሪ">Student</th>
                  <th class="py-2 pr-4 w-28" data-en="Score" data-am="ውጤት">Score</th>
                  <th class="py-2" data-en="Remarks" data-am="አስተያየት">Remarks</th>
                </tr>
              </thead>
              <tbody id="gradesBody"></tbody>
            </table>
          </div>
          <div class="mt-5 flex items-center gap-3">
            <button id="saveGrades" class="btn-primary" data-en="Save grades" data-am="ውጤቶችን አስቀምጥ">Save grades</button>
            <span id="gradesMsg" class="text-sm"></span>
          </div>
        </div>
      </div>

      <!-- Attendance tab: dept roll-call in a department, class roll-call in General -->
      <div id="dTabAtt" class="p-6 hidden">
        <div id="attClassCards" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 hidden"></div>
        <div id="attWork" class="hidden">
          <div class="flex items-center gap-3 mb-4 flex-wrap">
            <h3 id="attWorkTitle" class="font-display text-base text-ink hidden"></h3>
            <label class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Date" data-am="ቀን">Date</label>
            <input id="attDate" type="date" class="input-sm" />
            <button id="allPresent" class="text-xs font-semibold text-olive border border-olive-soft/60 px-3 py-1.5 rounded hover:bg-olive/5" data-en="Mark all present" data-am="ሁሉንም እንደተገኙ">Mark all present</button>
          </div>
          <ul id="attBody" class="divide-y divide-outline-soft/20"></ul>
          <div class="mt-5 flex items-center gap-3">
            <button id="saveAtt" class="btn-primary" data-en="Save attendance" data-am="መገኘት አስቀምጥ">Save attendance</button>
            <span id="attMsg" class="text-sm"></span>
          </div>
          <div id="attPast" class="mt-6 hidden">
            <p class="text-xs font-semibold uppercase tracking-widestest text-outline mb-2" data-en="Past sessions" data-am="ያለፉ ክፍለ ጊዜያት">Past sessions</p>
            <ul id="attPastList" class="text-sm divide-y divide-outline-soft/15"></ul>
          </div>
        </div>
      </div>

      <!-- Tasks tab -->
      <div id="dTabTasks" class="p-6 hidden">
        <ul id="taskList" class="divide-y divide-outline-soft/20 mb-6"></ul>
        <form id="taskForm" class="border-t border-outline-soft/30 pt-5 grid sm:grid-cols-2 gap-3">
          <p class="sm:col-span-2 text-xs font-semibold uppercase tracking-widestest text-outline"><span id="taskFormHead" data-en="New task" data-am="አዲስ ስራ">New task</span></p>
          <input type="hidden" id="taskEditId" value="" />
          <input id="taskTitle" type="text" class="input-sm sm:col-span-2" placeholder="Title" required />
          <textarea id="taskDesc" class="input-sm sm:col-span-2" rows="2" placeholder="Description (optional)"></textarea>
          <select id="taskScope" class="input-sm"></select>
          <input id="taskDue" type="date" class="input-sm" />
          <div class="sm:col-span-2 flex items-center gap-3">
            <button type="submit" class="btn-primary"><span id="taskSubmitLbl" data-en="Add task" data-am="ስራ ጨምር">Add task</span></button>
            <button type="button" id="taskCancelEdit" class="hidden text-xs font-semibold text-ink-soft hover:text-primary" data-en="Cancel edit" data-am="ማስተካከያ ሰርዝ">Cancel edit</button>
            <span id="taskMsg" class="text-sm"></span>
          </div>
        </form>
      </div>

      <!-- Files (resources) tab -->
      <div id="dTabFiles" class="p-6 hidden">
        <div class="flex items-center gap-3 mb-4 flex-wrap hidden" id="resGradeRow">
          <label class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Grade" data-am="ክፍል">Grade</label>
          <select id="resGradeSel" class="input-sm"></select>
        </div>
        <ul id="resList" class="divide-y divide-outline-soft/20 mb-6">
          <li class="py-8 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
        </ul>
        <div class="grid sm:grid-cols-2 gap-6 border-t border-outline-soft/30 pt-5">
          <form id="resUploadForm" class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Upload a file" data-am="ፋይል ስቀል">Upload a file</p>
            <input id="resFileTitle" type="text" class="input-sm w-full" placeholder="Title (optional)" />
            <input id="resFile" type="file" class="input-sm w-full" required />
            <button type="submit" class="btn-primary" data-en="Upload" data-am="ስቀል">Upload</button>
            <span id="resUploadMsg" class="text-sm block"></span>
          </form>
          <form id="resLinkForm" class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Add a link" data-am="አገናኝ ጨምር">Add a link</p>
            <input id="resLinkTitle" type="text" class="input-sm w-full" placeholder="Title" required />
            <input id="resLinkUrl" type="url" class="input-sm w-full" placeholder="https://…" required />
            <button type="submit" class="btn-primary" data-en="Add link" data-am="አገናኝ ጨምር">Add link</button>
            <span id="resLinkMsg" class="text-sm block"></span>
          </form>
        </div>
      </div>

      <!-- Announcements tab -->
      <div id="dTabAnn" class="p-6 hidden">
        <form id="annForm" class="grid gap-3 mb-8">
          <p class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Post an announcement" data-am="ማስታወቂያ ልጥፍ">Post an announcement</p>
          <input id="annTitle" type="text" class="input-sm" placeholder="Title" required />
          <textarea id="annMessage" class="input-sm" rows="3" placeholder="Message" required></textarea>
          <div class="flex items-center gap-3 flex-wrap">
            <label class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Audience" data-am="ተደራሲ">Audience</label>
            <select id="annClassSel" class="input-sm"></select>
            <button type="submit" class="btn-primary" data-en="Post" data-am="ልጥፍ">Post</button>
            <span id="annMsg" class="text-sm"></span>
          </div>
        </form>
        <p class="text-xs font-semibold uppercase tracking-widestest text-outline mb-2" data-en="My announcements" data-am="የእኔ ማስታወቂያዎች">My announcements</p>
        <ul id="annList" class="divide-y divide-outline-soft/20"></ul>
      </div>

      <!-- Events tab -->
      <div id="dTabEvents" class="p-6 hidden">
        <form id="eventForm" class="grid gap-3 mb-8">
          <p class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Propose an event" data-am="ዝግጅት ሐሳብ አቅርብ">Propose an event</p>
          <input id="evtTitle" type="text" class="input-sm" placeholder="Title" required />
          <textarea id="evtDesc" class="input-sm" rows="2" placeholder="Description (optional)"></textarea>
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-semibold uppercase tracking-widestest text-outline block mb-1" data-en="Start" data-am="ጀምር">Start</label>
              <input id="evtStart" type="datetime-local" class="input-sm w-full" required />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase tracking-widestest text-outline block mb-1" data-en="End (optional)" data-am="ጨርስ (አማራጭ)">End (optional)</label>
              <input id="evtEnd" type="datetime-local" class="input-sm w-full" />
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary" data-en="Propose" data-am="ሐሳብ አቅርብ">Propose</button>
            <span id="evtMsg" class="text-sm"></span>
          </div>
          <p class="text-[11px] text-outline" data-en="Your department head must approve this before it appears publicly." data-am="ይህ ከመታተሙ በፊት በክፍሉ ሃላፊ መጽደቅ አለበት።">Your department head must approve this before it appears publicly.</p>
        </form>
        <p class="text-xs font-semibold uppercase tracking-widestest text-outline mb-2" data-en="My proposals" data-am="የእኔ ሐሳቦች">My proposals</p>
        <ul id="eventList" class="divide-y divide-outline-soft/20"></ul>
      </div>
    </section>
  </main>

<script>
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function isAm(){ return document.documentElement.getAttribute('data-lang')==='am'; }
  function t(en,am){ return isAm()?am:en; }
  async function ensureCsrf(){
    var tok = sessionStorage.getItem('csrf_token');
    if(!tok){ var r=await fetch('/api/auth/csrf.php'); var d=await r.json(); tok=d.csrf_token; sessionStorage.setItem('csrf_token',tok); }
    return tok;
  }
  async function api(url, opts){
    opts=opts||{}; opts.headers=opts.headers||{};
    if(opts.body && !opts.headers['Content-Type']) opts.headers['Content-Type']='application/json';
    if(['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase())>=0) opts.headers['X-CSRF-Token']=await ensureCsrf();
    var res=await fetch(url,opts); var data; try{data=await res.json();}catch(e){data={};}
    if(!res.ok) throw new Error(data.error||('HTTP '+res.status));
    return data;
  }
  function name(r){ return ((r.first_name||'')+' '+(r.last_name||'')).trim(); }
  function todayStr(){ var d=new Date(); return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }

  // ---------- state ----------
  var DEPTS=[], LEGACY_CLASSES=[], TERMS=[], NOTIFS=[];
  var SEL=null;            // {type:'dept', dept:{...}} | {type:'general'}
  var TAB='grades';
  var CURRENT=null;        // selected class {class_id,subject_id,label} for grades/attendance
  var ROSTER=[], ATT=[], TASKS=[];
  var RES_GRADES=[], RES_DEPTS=[], RES_DATA=[];

  function deptLabel(d){ return isAm()&&d.name_am?d.name_am:d.name; }
  function classCardLabel(c){
    var lvl=isAm()&&c.level_name_am?c.level_name_am:c.level_name;
    var subj=isAm()&&c.subject_name_am?c.subject_name_am:c.subject_name;
    return {lvl:lvl,subj:subj};
  }

  // ---------- boot ----------
  async function boot(){
    try{
      var results=await Promise.all([
        api('/api/teacher/departments.php'),
        api('/api/teacher/classes/index.php'),
      ]);
      DEPTS=results[0].data||[];
      LEGACY_CLASSES=results[1].data||[];
      TERMS=results[1].terms||[];
      renderChips();
      if(DEPTS.length) selectDept(DEPTS[0].id);
      else if(LEGACY_CLASSES.length) selectGeneral();
    }catch(e){
      document.getElementById('deptChips').innerHTML='<p class="text-sm text-error">'+escHtml(e.message)+'</p>';
    }
    loadNotifs();
  }

  // ---------- notifications ----------
  async function loadNotifs(){
    try{
      var d=await api('/api/teacher/notifications.php');
      NOTIFS=d.data||[];
    }catch(e){ NOTIFS=[]; }
    var unread=NOTIFS.filter(function(n){return !n.is_read;}).length;
    var badge=document.getElementById('notifBadge');
    badge.textContent=unread;
    badge.classList.toggle('hidden',unread===0);
    renderNotifs();
  }
  function renderNotifs(){
    var ul=document.getElementById('notifList');
    if(!NOTIFS.length){ ul.innerHTML='<li class="py-6 text-center text-ink-soft text-sm">'+t('No notifications.','ማሳወቂያ የለም።')+'</li>'; return; }
    ul.innerHTML=NOTIFS.map(function(n){
      return '<li class="py-3 flex items-start justify-between gap-4'+(n.is_read?' opacity-60':'')+'">'+
        '<div><p class="font-medium text-sm">'+escHtml(n.title)+'</p>'+
        '<p class="text-sm text-ink-soft mt-0.5">'+escHtml(n.message)+'</p>'+
        '<p class="text-[11px] text-outline mt-1">'+escHtml(n.created_at||'')+'</p></div>'+
        (n.is_read?'':'<button class="js-notif-read shrink-0 text-xs font-semibold text-primary hover:underline" data-id="'+n.id+'">'+t('Mark read','እንደተነበበ ምልክት')+'</button>')+
      '</li>';
    }).join('');
  }
  document.getElementById('notifBell').addEventListener('click', function(){
    document.getElementById('notifPanel').classList.toggle('hidden');
  });
  document.getElementById('notifClose').addEventListener('click', function(){
    document.getElementById('notifPanel').classList.add('hidden');
  });
  document.getElementById('notifList').addEventListener('click', async function(e){
    var b=e.target.closest('.js-notif-read'); if(!b) return;
    try{ await api('/api/teacher/notifications.php',{method:'POST',body:JSON.stringify({id:parseInt(b.dataset.id,10)})}); loadNotifs(); }
    catch(err){ alert(err.message); }
  });

  // ---------- department chips ----------
  function renderChips(){
    var wrap=document.getElementById('deptChips');
    var html='';
    DEPTS.forEach(function(d){
      var on=SEL&&SEL.type==='dept'&&SEL.dept.id===d.id;
      html+='<button class="dept-chip'+(on?' chip-active':'')+'" data-dept="'+d.id+'">'+escHtml(deptLabel(d))+
        (Number(d.is_head)===1?' <span class="text-[10px] uppercase tracking-widestest">'+t('· head','· ሓላፊ')+'</span>':'')+'</button>';
    });
    if(LEGACY_CLASSES.length){
      var gOn=SEL&&SEL.type==='general';
      html+='<button class="dept-chip'+(gOn?' chip-active':'')+'" data-general="1">'+t('General (my classes)','አጠቃላይ (የእኔ ክፍሎች)')+'</button>';
    }
    if(!html){
      html='<div class="panel px-6 py-8 w-full text-center">'+
        '<p class="text-sm text-ink-soft">'+t('You have not been assigned to a department yet. Please contact the school administration.','ገና ወደ ዲፓርትመንት አልተመደቡም። እባክዎ የትምህርት ቤቱን አስተዳደር ያነጋግሩ።')+'</p></div>';
    }
    wrap.innerHTML=html;
  }
  document.getElementById('deptChips').addEventListener('click', function(e){
    var b=e.target.closest('[data-dept]');
    if(b){ selectDept(parseInt(b.dataset.dept,10)); return; }
    var g=e.target.closest('[data-general]');
    if(g){ selectGeneral(); }
  });

  function selectDept(id){
    var d=DEPTS.find(function(x){return Number(x.id)===id;}); if(!d) return;
    SEL={type:'dept',dept:d}; CURRENT=null;
    renderChips(); openWorkspace();
  }
  function selectGeneral(){
    SEL={type:'general'}; CURRENT=null;
    renderChips(); openWorkspace();
  }

  function openWorkspace(){
    document.getElementById('deptWs').classList.remove('hidden');
    if(SEL.type==='dept'){
      document.getElementById('deptWsTitle').textContent=deptLabel(SEL.dept);
      document.getElementById('deptWsSub').textContent=t('Department workflows — students, tasks, files and announcements.','የክፍሉ ስራዎች — ተማሪዎች፣ ስራዎች፣ ፋይሎች እና ማስታወቂያዎች።');
    }else{
      document.getElementById('deptWsTitle').textContent=t('General (my classes)','አጠቃላይ (የእኔ ክፍሎች)');
      document.getElementById('deptWsSub').textContent=t('Your class-based grades and attendance.','በክፍል ላይ የተመሰረቱ ውጤቶችና መገኘት።');
    }
    // Tasks, announcements & events are dept-scoped: hide them in General mode.
    var isDept=SEL.type==='dept';
    document.querySelector('#deptTabs [data-tab="tasks"]').classList.toggle('hidden',!isDept);
    document.querySelector('#deptTabs [data-tab="ann"]').classList.toggle('hidden',!isDept);
    document.querySelector('#deptTabs [data-tab="events"]').classList.toggle('hidden',!isDept);
    if(!isDept&&(TAB==='tasks'||TAB==='ann'||TAB==='events')) TAB='grades';
    showTab(TAB||'grades');
  }

  // ---------- tabs ----------
  function showTab(which){
    TAB=which;
    document.querySelectorAll('#deptTabs [data-tab]').forEach(function(b){ b.classList.toggle('seg-active',b.dataset.tab===which); });
    document.getElementById('dTabGrades').classList.toggle('hidden',which!=='grades');
    document.getElementById('dTabAtt').classList.toggle('hidden',which!=='att');
    document.getElementById('dTabTasks').classList.toggle('hidden',which!=='tasks');
    document.getElementById('dTabFiles').classList.toggle('hidden',which!=='files');
    document.getElementById('dTabAnn').classList.toggle('hidden',which!=='ann');
    document.getElementById('dTabEvents').classList.toggle('hidden',which!=='events');
    if(which==='grades') renderGradesTab();
    if(which==='att') renderAttTab();
    if(which==='tasks') loadTasks();
    if(which==='files') loadResources();
    if(which==='ann'){ renderAnnClassOptions(); loadAnnouncements(); }
    if(which==='events') loadEvents();
  }
  document.getElementById('deptTabs').addEventListener('click', function(e){
    var b=e.target.closest('[data-tab]'); if(!b) return; showTab(b.dataset.tab);
  });

  function currentClasses(){
    return SEL.type==='dept' ? (SEL.dept.classes||[]) : LEGACY_CLASSES;
  }

  // ---------- Grades tab ----------
  function renderGradesTab(){
    var cards=document.getElementById('deptClassCards');
    var work=document.getElementById('gradeWork');
    var rows=currentClasses();
    if(!rows.length){
      cards.innerHTML='<p class="text-sm text-ink-soft col-span-full text-center py-8">'+
        (SEL.type==='dept'
          ? t('None of your classes belong to this department yet. Your class grades are under "General (my classes)".','ከክፍሎችዎ አንዳቸውም ገና የዚህ ዲፓርትመንት አይደሉም። የክፍል ውጤቶችዎ በ«አጠቃላይ (የእኔ ክፍሎች)» ስር ይገኛሉ።')
          : t('No classes assigned to you yet. Contact the admin.','ገና ምንም ክፍል አልተመደበልዎትም። እባክዎ አስተዳዳሪውን ያነጋግሩ።'))+'</p>';
      work.classList.add('hidden'); CURRENT=null; return;
    }
    cards.innerHTML=rows.map(function(c){
      var l=classCardLabel(c);
      var on=CURRENT&&CURRENT.class_id===Number(c.class_id)&&CURRENT.subject_id===Number(c.subject_id);
      return '<button class="panel p-5 text-left hover:shadow-md transition-shadow'+(on?' ring-2 ring-primary':'')+'" data-class="'+c.class_id+'" data-subject="'+c.subject_id+'">'+
        '<p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1.5">'+escHtml(l.subj||'')+'</p>'+
        '<h3 class="font-display text-lg text-primary mb-1">'+escHtml(c.class_name)+'</h3>'+
        '<p class="text-xs text-outline">'+escHtml([l.lvl,c.academic_year].filter(Boolean).join(' · '))+'</p>'+
      '</button>';
    }).join('');
    if(CURRENT) loadRoster(); else work.classList.add('hidden');
  }
  document.getElementById('deptClassCards').addEventListener('click', function(e){
    var b=e.target.closest('[data-class]'); if(!b) return;
    var cid=parseInt(b.dataset.class,10), sid=parseInt(b.dataset.subject,10);
    var c=currentClasses().find(function(x){return Number(x.class_id)===cid&&Number(x.subject_id)===sid;}); if(!c) return;
    var l=classCardLabel(c);
    CURRENT={class_id:cid,subject_id:sid,label:c.class_name+' · '+(l.subj||'')};
    var ts=document.getElementById('termSel');
    ts.innerHTML=TERMS.map(function(tm){ return '<option value="'+tm.id+'"'+(Number(tm.is_current)===1?' selected':'')+'>'+escHtml(tm.name+' · '+tm.academic_year)+'</option>'; }).join('');
    renderGradesTab();
  });
  document.getElementById('termSel').addEventListener('change', loadRoster);

  async function loadRoster(){
    if(!CURRENT) return;
    var work=document.getElementById('gradeWork');
    work.classList.remove('hidden');
    document.getElementById('gradeWorkTitle').textContent=CURRENT.label;
    var tb=document.getElementById('gradesBody');
    document.getElementById('gradesMsg').textContent='';
    tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-ink-soft">'+t('Loading…','በመጫን ላይ…')+'</td></tr>';
    var term=document.getElementById('termSel').value;
    try{
      var d=await api('/api/teacher/roster/index.php?class_id='+CURRENT.class_id+'&subject_id='+CURRENT.subject_id+'&term_id='+term);
      ROSTER=d.data||[];
      if(!ROSTER.length){ tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-ink-soft">'+t('No students in this class.','በዚህ ክፍል ተማሪ የለም።')+'</td></tr>'; return; }
      tb.innerHTML=ROSTER.map(function(r){
        return '<tr class="border-b border-outline-soft/15" data-sid="'+r.student_id+'" data-gid="'+(r.grade_id||'')+'">'+
          '<td class="py-2 pr-4 font-medium">'+escHtml(name(r))+'</td>'+
          '<td class="py-2 pr-4"><input type="number" min="0" max="100" step="0.01" class="input-sm w-24 js-score" value="'+(r.score!=null?escHtml(r.score):'')+'" /></td>'+
          '<td class="py-2"><input type="text" class="input-sm w-full js-remarks" value="'+escHtml(r.remarks||'')+'" /></td>'+
        '</tr>';
      }).join('');
    }catch(e){ tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-error">'+escHtml(e.message)+'</td></tr>'; }
  }
  document.getElementById('saveGrades').addEventListener('click', async function(){
    if(!CURRENT) return;
    var term=parseInt(document.getElementById('termSel').value,10);
    var msg=document.getElementById('gradesMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=t('Saving…','በማስቀመጥ ላይ…');
    var rows=Array.prototype.slice.call(document.querySelectorAll('#gradesBody tr[data-sid]'));
    var ok=0, fail=0;
    for(var i=0;i<rows.length;i++){
      var tr=rows[i]; var scoreEl=tr.querySelector('.js-score'); var remEl=tr.querySelector('.js-remarks');
      var score=scoreEl.value.trim(); var gid=tr.dataset.gid;
      if(score===''){ continue; } // skip blanks (don't create empty grades)
      try{
        if(gid){ await api('/api/teacher/grades/index.php',{method:'PUT',body:JSON.stringify({id:parseInt(gid,10),score:parseFloat(score),remarks:remEl.value})}); }
        else {
          var r=await api('/api/teacher/grades/index.php',{method:'POST',body:JSON.stringify({student_id:parseInt(tr.dataset.sid,10),subject_id:CURRENT.subject_id,class_id:CURRENT.class_id,term_id:term,score:parseFloat(score),remarks:remEl.value})});
          if(r.id) tr.dataset.gid=r.id;
        }
        ok++;
      }catch(e){ fail++; }
    }
    msg.className='text-sm '+(fail?'text-error':'text-olive');
    msg.textContent=(isAm()?('ተቀምጧል: '+ok):(ok+' saved'))+(fail?(' · '+fail+(isAm()?' አልተሳካም':' failed')):'');
  });

  // ---------- Attendance tab ----------
  var ATT_STATUSES=[['present','Present','የተገኘ','#384700'],['late','Late','የዘገየ','#795901'],['absent','Absent','ቀሪ','#9b1c1c'],['excused','Excused','በፍቃድ','#3f4658']];

  function renderAttTab(){
    var cards=document.getElementById('attClassCards');
    var work=document.getElementById('attWork');
    if(SEL.type==='dept'){
      // department roll-call — the roster is the dept's students, no class picker
      cards.classList.add('hidden');
      work.classList.remove('hidden');
      document.getElementById('attWorkTitle').classList.add('hidden');
      loadDeptAttendance();
    }else{
      cards.classList.remove('hidden');
      var rows=LEGACY_CLASSES;
      if(!rows.length){ cards.innerHTML='<p class="text-sm text-ink-soft col-span-full text-center py-8">'+t('No classes assigned to you yet.','ገና ምንም ክፍል አልተመደበልዎትም።')+'</p>'; work.classList.add('hidden'); return; }
      cards.innerHTML=rows.map(function(c){
        var l=classCardLabel(c);
        var on=CURRENT&&CURRENT.class_id===Number(c.class_id);
        return '<button class="panel p-5 text-left hover:shadow-md transition-shadow'+(on?' ring-2 ring-primary':'')+'" data-att-class="'+c.class_id+'">'+
          '<h3 class="font-display text-lg text-primary mb-1">'+escHtml(c.class_name)+'</h3>'+
          '<p class="text-xs text-outline">'+escHtml([l.lvl,c.academic_year].filter(Boolean).join(' · '))+'</p>'+
        '</button>';
      }).join('');
      if(CURRENT&&CURRENT.class_id){ work.classList.remove('hidden'); loadClassAttendance(); }
      else work.classList.add('hidden');
    }
  }
  document.getElementById('attClassCards').addEventListener('click', function(e){
    var b=e.target.closest('[data-att-class]'); if(!b) return;
    var cid=parseInt(b.dataset.attClass,10);
    var c=LEGACY_CLASSES.find(function(x){return Number(x.class_id)===cid;}); if(!c) return;
    CURRENT={class_id:cid,subject_id:Number(c.subject_id),label:c.class_name};
    renderAttTab();
  });

  async function loadDeptAttendance(){
    var ul=document.getElementById('attBody'); document.getElementById('attMsg').textContent='';
    var dt=document.getElementById('attDate');
    if(!dt.value) dt.value=todayStr();
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    try{
      var d=await api('/api/teacher/dept-attendance.php?department_id='+SEL.dept.id+'&date='+dt.value);
      ATT=d.roster||[];
      var past=d.sessions||[];
      var pastWrap=document.getElementById('attPast');
      pastWrap.classList.toggle('hidden',!past.length);
      document.getElementById('attPastList').innerHTML=past.map(function(s){
        return '<li class="py-1.5 flex items-center gap-3"><span class="text-outline">'+escHtml(s.session_date)+'</span><span>'+escHtml(s.title||t('Roll-call','መገኘት'))+'</span></li>';
      }).join('');
      if(!ATT.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('No students in this department yet.','በዚህ ዲፓርትመንት ገና ተማሪ የለም።')+'</li>'; return; }
      renderAtt();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  async function loadClassAttendance(){
    var ul=document.getElementById('attBody'); document.getElementById('attMsg').textContent='';
    document.getElementById('attPast').classList.add('hidden');
    var title=document.getElementById('attWorkTitle');
    title.classList.remove('hidden'); title.textContent=CURRENT.label;
    var dt=document.getElementById('attDate');
    if(!dt.value) dt.value=todayStr();
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    try{
      var d=await api('/api/teacher/attendance/index.php?class_id='+CURRENT.class_id+'&date='+dt.value);
      ATT=d.roster||[];
      if(!ATT.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('No students in this class.','በዚህ ክፍል ተማሪ የለም።')+'</li>'; return; }
      renderAtt();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  function renderAtt(){
    var ul=document.getElementById('attBody');
    ul.innerHTML=ATT.map(function(r,idx){
      var noProfile = r.person_id==null;
      var segs=ATT_STATUSES.map(function(s){
        var on=r.status===s[0];
        return '<button data-idx="'+idx+'" data-st="'+s[0]+'" class="att-seg px-2.5 py-1 text-xs font-semibold rounded '+(on?'text-surface':'text-ink-soft')+'" style="'+(on?('background:'+s[3]+';'):'')+'" '+(noProfile?'disabled':'')+'>'+(isAm()?s[2]:s[1])+'</button>';
      }).join('');
      return '<li class="py-3 flex items-center justify-between gap-4">'+
        '<span class="font-medium">'+escHtml(name(r))+(noProfile?' <span class="text-[10px] text-outline">('+t('no profile','መገለጫ የለም')+')</span>':'')+'</span>'+
        '<div class="flex gap-1 flex-wrap '+(noProfile?'opacity-40':'')+'">'+segs+'</div>'+
      '</li>';
    }).join('');
  }
  document.getElementById('attBody').addEventListener('click', function(e){
    var b=e.target.closest('.att-seg'); if(!b||b.disabled) return;
    ATT[parseInt(b.dataset.idx,10)].status=b.dataset.st; renderAtt();
  });
  document.getElementById('attDate').addEventListener('change', function(){
    if(SEL&&SEL.type==='dept') loadDeptAttendance(); else if(CURRENT) loadClassAttendance();
  });
  document.getElementById('allPresent').addEventListener('click', function(){ ATT.forEach(function(r){ if(r.person_id!=null) r.status='present'; }); renderAtt(); });

  document.getElementById('saveAtt').addEventListener('click', async function(){
    var msg=document.getElementById('attMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=t('Saving…','በማስቀመጥ ላይ…');
    var records=ATT.filter(function(r){ return r.person_id!=null; }).map(function(r){ return {person_id:r.person_id,status:r.status}; });
    if(!records.length){ msg.className='text-sm text-error'; msg.textContent=t('Nothing to save (no student profiles)','ምንም የሚቀመጥ የለም'); return; }
    try{
      var r;
      if(SEL.type==='dept'){
        r=await api('/api/teacher/dept-attendance.php',{method:'POST',body:JSON.stringify({department_id:SEL.dept.id,session_date:document.getElementById('attDate').value,records:records})});
      }else{
        if(!CURRENT){ msg.textContent=''; return; }
        r=await api('/api/teacher/attendance/index.php',{method:'POST',body:JSON.stringify({class_id:CURRENT.class_id,date:document.getElementById('attDate').value,records:records})});
      }
      msg.className='text-sm text-olive'; msg.textContent=(isAm()?'ተቀምጧል: ':'Saved ')+(r.saved||0);
      if(SEL.type==='dept') loadDeptAttendance();
    }catch(e){ msg.className='text-sm text-error'; msg.textContent=e.message; }
  });

  // ---------- Tasks tab ----------
  async function loadTasks(){
    if(!SEL||SEL.type!=='dept') return;
    var ul=document.getElementById('taskList');
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    renderTaskScopeOptions();
    taskFormReset();
    try{
      var d=await api('/api/teacher/tasks.php?department_id='+SEL.dept.id);
      TASKS=d.data||[];
      renderTasks();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  function taskScopeLabel(tk){
    if(tk.scope_type==='department') return t('Whole department','መላው ዲፓርትመንት');
    var c=(SEL.dept.classes||[]).find(function(x){return Number(x.class_id)===Number(tk.scope_id);});
    return c?c.class_name:(t('Class','ክፍል')+' #'+tk.scope_id);
  }
  function renderTasks(){
    var ul=document.getElementById('taskList');
    if(!TASKS.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('No tasks yet.','ገና ስራ የለም።')+'</li>'; return; }
    ul.innerHTML=TASKS.map(function(tk){
      var mine=Number(tk.is_mine)===1;
      return '<li class="py-3 flex items-start justify-between gap-4" data-id="'+tk.id+'">'+
        '<div><p class="font-medium">'+escHtml(tk.title)+'</p>'+
        (tk.description?'<p class="text-sm text-ink-soft mt-0.5">'+escHtml(tk.description)+'</p>':'')+
        '<p class="text-[11px] text-outline mt-1">'+escHtml(taskScopeLabel(tk))+(tk.due_date?' · '+t('due','እስከ')+' '+escHtml(tk.due_date):'')+'</p></div>'+
        (mine?('<div class="flex gap-3 shrink-0">'+
          '<button class="js-task-edit text-xs font-semibold text-primary hover:underline" data-id="'+tk.id+'">'+t('Edit','አስተካክል')+'</button>'+
          '<button class="js-task-del text-xs font-semibold text-error hover:underline" data-id="'+tk.id+'">'+t('Archive','አርካይቭ')+'</button>'+
        '</div>'):'')+
      '</li>';
    }).join('');
  }
  function renderTaskScopeOptions(){
    var sel=document.getElementById('taskScope');
    var opts='<option value="department:'+SEL.dept.id+'">'+t('Whole department','መላው ዲፓርትመንት')+'</option>';
    (SEL.dept.classes||[]).forEach(function(c){
      opts+='<option value="class:'+c.class_id+'">'+escHtml(c.class_name)+'</option>';
    });
    sel.innerHTML=opts;
  }
  function taskFormReset(){
    document.getElementById('taskEditId').value='';
    document.getElementById('taskTitle').value='';
    document.getElementById('taskDesc').value='';
    document.getElementById('taskDue').value='';
    document.getElementById('taskScope').disabled=false;
    document.getElementById('taskFormHead').textContent=t('New task','አዲስ ስራ');
    document.getElementById('taskSubmitLbl').textContent=t('Add task','ስራ ጨምር');
    document.getElementById('taskCancelEdit').classList.add('hidden');
    document.getElementById('taskMsg').textContent='';
  }
  document.getElementById('taskList').addEventListener('click', async function(e){
    var del=e.target.closest('.js-task-del');
    if(del){
      if(!confirm(t('Archive this task?','ይህን ስራ ወደ አርካይቭ ማዛወር ይፈልጋሉ?'))) return;
      try{ await api('/api/teacher/tasks.php',{method:'DELETE',body:JSON.stringify({id:parseInt(del.dataset.id,10)})}); loadTasks(); }
      catch(err){ alert(err.message); }
      return;
    }
    var ed=e.target.closest('.js-task-edit');
    if(ed){
      var tk=TASKS.find(function(x){return Number(x.id)===parseInt(ed.dataset.id,10);}); if(!tk) return;
      document.getElementById('taskEditId').value=tk.id;
      document.getElementById('taskTitle').value=tk.title||'';
      document.getElementById('taskDesc').value=tk.description||'';
      document.getElementById('taskDue').value=tk.due_date||'';
      var sel=document.getElementById('taskScope');
      sel.value=tk.scope_type+':'+tk.scope_id;
      sel.disabled=true; // scope is fixed once created
      document.getElementById('taskFormHead').textContent=t('Edit task','ስራ አስተካክል');
      document.getElementById('taskSubmitLbl').textContent=t('Save changes','ለውጦችን አስቀምጥ');
      document.getElementById('taskCancelEdit').classList.remove('hidden');
      document.getElementById('taskTitle').focus();
    }
  });
  document.getElementById('taskCancelEdit').addEventListener('click', taskFormReset);
  document.getElementById('taskForm').addEventListener('submit', async function(e){
    e.preventDefault();
    if(!SEL||SEL.type!=='dept') return;
    var msg=document.getElementById('taskMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=t('Saving…','በማስቀመጥ ላይ…');
    var editId=document.getElementById('taskEditId').value;
    var title=document.getElementById('taskTitle').value.trim();
    var desc=document.getElementById('taskDesc').value.trim();
    var due=document.getElementById('taskDue').value;
    try{
      if(editId){
        await api('/api/teacher/tasks.php',{method:'PUT',body:JSON.stringify({id:parseInt(editId,10),title:title,description:desc||null,due_date:due||null})});
      }else{
        var sc=document.getElementById('taskScope').value.split(':');
        var body={scope_type:sc[0],scope_id:parseInt(sc[1],10),title:title};
        if(desc) body.description=desc;
        if(due) body.due_date=due;
        await api('/api/teacher/tasks.php',{method:'POST',body:JSON.stringify(body)});
      }
      loadTasks();
    }catch(err){ msg.className='text-sm text-error'; msg.textContent=err.message; }
  });

  // ---------- Files (resources) tab ----------
  function resKind(k){ return k==='file'?t('file','ፋይል'):t('link','አገናኝ'); }
  async function loadResources(){
    var ul=document.getElementById('resList');
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    try{
      var d=await api('/api/teacher/resources.php');
      RES_GRADES=d.grades||[]; RES_DEPTS=d.departments||[]; RES_DATA=d.data||[];
      renderResGradeOptions();
      renderResources();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  function renderResGradeOptions(){
    // In General mode the resources are grade-scoped → show the grade select.
    var row=document.getElementById('resGradeRow');
    if(SEL&&SEL.type==='dept'){ row.classList.add('hidden'); return; }
    row.classList.remove('hidden');
    var sel=document.getElementById('resGradeSel');
    var prev=sel.value;
    sel.innerHTML=RES_GRADES.map(function(g){
      var label=isAm()&&g.name_am?g.name_am:g.name;
      return '<option value="'+g.id+'">'+escHtml(label)+'</option>';
    }).join('');
    if(prev && RES_GRADES.some(function(g){return String(g.id)===prev;})) sel.value=prev;
  }
  function resScope(){
    if(SEL&&SEL.type==='dept') return {type:'department', id:Number(SEL.dept.id)};
    var gid=parseInt(document.getElementById('resGradeSel').value,10);
    return {type:'grade', id:gid};
  }
  function renderResources(){
    var ul=document.getElementById('resList');
    if(SEL&&SEL.type!=='dept' && !RES_GRADES.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('No grades assigned to you yet.','ገና ምንም ክፍል አልተመደበልዎትም።')+'</li>'; return; }
    var sc=resScope();
    var rows=RES_DATA.filter(function(r){ return r.scope_type===sc.type && parseInt(r.scope_id,10)===sc.id; });
    if(!rows.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('No resources yet.','ምንም ግብዓት የለም።')+'</li>'; return; }
    ul.innerHTML=rows.map(function(r){
      return '<li class="py-3 flex items-center justify-between gap-4" data-id="'+r.id+'">'+
        '<a href="'+escHtml(r.url)+'" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">'+escHtml(r.title)+'</a>'+
        '<div class="flex items-center gap-3">'+
          '<span class="text-[10px] uppercase tracking-widestest text-outline">'+resKind(r.kind)+'</span>'+
          '<button class="js-res-remove text-xs font-semibold text-error hover:underline" data-id="'+r.id+'">'+t('Remove','አስወግድ')+'</button>'+
        '</div>'+
      '</li>';
    }).join('');
  }
  document.getElementById('resGradeSel').addEventListener('change', renderResources);
  document.getElementById('resList').addEventListener('click', async function(e){
    var b=e.target.closest('.js-res-remove'); if(!b) return;
    if(!confirm(t('Remove this resource?','ይህን ግብዓት ማስወገድ ይፈልጋሉ?'))) return;
    try{
      await api('/api/teacher/resources.php',{method:'DELETE',body:JSON.stringify({id:parseInt(b.dataset.id,10)})});
      loadResources();
    }catch(err){ alert(err.message); }
  });
  document.getElementById('resUploadForm').addEventListener('submit', async function(e){
    e.preventDefault();
    var msg=document.getElementById('resUploadMsg'); msg.className='text-sm block text-ink-soft'; msg.textContent=t('Uploading…','በመስቀል ላይ…');
    var fileEl=document.getElementById('resFile');
    var sc=resScope();
    if(!fileEl.files.length || !sc.id){ msg.className='text-sm block text-error'; msg.textContent=t('A file and a scope are required','ፋይል እና ክፍል ያስፈልጋል'); return; }
    var fd=new FormData();
    fd.append('file', fileEl.files[0]);
    fd.append('scope_type', sc.type);
    fd.append('scope_id', sc.id);
    var title=document.getElementById('resFileTitle').value.trim();
    if(title) fd.append('title', title);
    try{
      var token=await ensureCsrf();
      var res=await fetch('/api/teacher/resources.php',{method:'POST',headers:{'X-CSRF-Token':token},body:fd});
      var data; try{ data=await res.json(); }catch(_){ data={}; }
      if(!res.ok) throw new Error(data.error||('HTTP '+res.status));
      msg.className='text-sm block text-olive'; msg.textContent=t('Uploaded','ተስቀሏል');
      document.getElementById('resUploadForm').reset();
      loadResources();
    }catch(err){ msg.className='text-sm block text-error'; msg.textContent=err.message; }
  });
  document.getElementById('resLinkForm').addEventListener('submit', async function(e){
    e.preventDefault();
    var msg=document.getElementById('resLinkMsg'); msg.className='text-sm block text-ink-soft'; msg.textContent=t('Adding…','በመጨመር ላይ…');
    var sc=resScope();
    var title=document.getElementById('resLinkTitle').value.trim();
    var url=document.getElementById('resLinkUrl').value.trim();
    if(!sc.id || !title || !url){ msg.className='text-sm block text-error'; msg.textContent=t('Title, link and a scope are required','ርዕስ፣ አገናኝ እና ክፍል ያስፈልጋል'); return; }
    try{
      await api('/api/teacher/resources.php',{method:'POST',body:JSON.stringify({scope_type:sc.type,scope_id:sc.id,title:title,url:url})});
      msg.className='text-sm block text-olive'; msg.textContent=t('Added','ተጨምሯል');
      document.getElementById('resLinkForm').reset();
      loadResources();
    }catch(err){ msg.className='text-sm block text-error'; msg.textContent=err.message; }
  });

  // ---------- Announcements tab ----------
  function renderAnnClassOptions(){
    if(!SEL||SEL.type!=='dept') return;
    var sel=document.getElementById('annClassSel');
    var opts='<option value="">'+t('Whole department','መላው ዲፓርትመንት')+'</option>';
    (SEL.dept.classes||[]).forEach(function(c){
      opts+='<option value="'+c.class_id+'">'+escHtml(c.class_name)+'</option>';
    });
    sel.innerHTML=opts;
  }
  async function loadAnnouncements(){
    if(!SEL||SEL.type!=='dept') return;
    var ul=document.getElementById('annList');
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    try{
      var d=await api('/api/teacher/announcements.php?department_id='+SEL.dept.id);
      var rows=d.data||[];
      if(!rows.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('You have not posted any announcements in this department yet.','በዚህ ዲፓርትመንት ገና ማስታወቂያ አልለጠፉም።')+'</li>'; return; }
      ul.innerHTML=rows.map(function(a){
        var aud=a.target_type==='class'?(a.class_name||(t('Class','ክፍል')+' #'+a.class_id)):t('Whole department','መላው ዲፓርትመንት');
        return '<li class="py-3">'+
          '<p class="font-medium">'+escHtml(a.title)+'</p>'+
          '<p class="text-sm text-ink-soft mt-0.5">'+escHtml(a.message)+'</p>'+
          '<p class="text-[11px] text-outline mt-1">'+escHtml(aud)+' · '+escHtml(a.created_at||'')+'</p>'+
        '</li>';
      }).join('');
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  document.getElementById('annForm').addEventListener('submit', async function(e){
    e.preventDefault();
    if(!SEL||SEL.type!=='dept') return;
    var msg=document.getElementById('annMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=t('Posting…','በመለጠፍ ላይ…');
    var title=document.getElementById('annTitle').value.trim();
    var message=document.getElementById('annMessage').value.trim();
    var classId=document.getElementById('annClassSel').value;
    var body={department_id:Number(SEL.dept.id),title:title,message:message};
    if(classId) body.class_id=parseInt(classId,10);
    try{
      await api('/api/teacher/announcements.php',{method:'POST',body:JSON.stringify(body)});
      msg.className='text-sm text-olive'; msg.textContent=t('Posted','ተለጥፏል');
      document.getElementById('annForm').reset();
      loadAnnouncements();
    }catch(err){ msg.className='text-sm text-error'; msg.textContent=err.message; }
  });

  // ---------- Events tab ----------
  var EVENTS=[];
  var EVT_STATUS_CHIP={
    pending:{bg:'#fed175',fg:'#795901',en:'Pending',am:'በመጠባበቅ ላይ'},
    approved:{bg:'#a2b665',fg:'#384700',en:'Approved',am:'ጸድቋል'},
    rejected:{bg:'#f3caca',fg:'#9b1c1c',en:'Rejected',am:'ውድቅ ተደርጓል'}
  };
  function evtStatusChip(status){
    var c=EVT_STATUS_CHIP[status]||EVT_STATUS_CHIP.pending;
    return '<span class="text-[11px] font-semibold uppercase tracking-widestest px-2 py-0.5 rounded-full" style="background:'+c.bg+';color:'+c.fg+'">'+(isAm()?c.am:c.en)+'</span>';
  }
  async function loadEvents(){
    if(!SEL||SEL.type!=='dept') return;
    var ul=document.getElementById('eventList');
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('Loading…','በመጫን ላይ…')+'</li>';
    try{
      var d=await api('/api/teacher/events.php?department_id='+SEL.dept.id);
      EVENTS=d.data||[];
      renderEvents();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  function renderEvents(){
    var ul=document.getElementById('eventList');
    if(!EVENTS.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+t('You have not proposed any events in this department yet.','በዚህ ዲፓርትመንት ገና ዝግጅት አላቀረቡም።')+'</li>'; return; }
    ul.innerHTML=EVENTS.map(function(ev){
      return '<li class="py-3 flex items-start justify-between gap-4" data-id="'+ev.id+'">'+
        '<div><div class="flex items-center gap-2 flex-wrap"><p class="font-medium">'+escHtml(ev.title)+'</p>'+evtStatusChip(ev.status)+'</div>'+
        (ev.description?'<p class="text-sm text-ink-soft mt-0.5">'+escHtml(ev.description)+'</p>':'')+
        '<p class="text-[11px] text-outline mt-1">'+escHtml(ev.start_datetime||'')+(ev.end_datetime?(' – '+escHtml(ev.end_datetime)):'')+'</p></div>'+
        (ev.status==='pending'?('<button class="js-evt-withdraw shrink-0 text-xs font-semibold text-error hover:underline" data-id="'+ev.id+'">'+t('Withdraw','ስረዝ')+'</button>'):'')+
      '</li>';
    }).join('');
  }
  document.getElementById('eventList').addEventListener('click', async function(e){
    var b=e.target.closest('.js-evt-withdraw'); if(!b) return;
    if(!confirm(t('Withdraw this proposal?','ይህን ሐሳብ መስረዝ ይፈልጋሉ?'))) return;
    try{ await api('/api/teacher/events.php',{method:'DELETE',body:JSON.stringify({id:parseInt(b.dataset.id,10)})}); loadEvents(); }
    catch(err){ alert(err.message); }
  });
  document.getElementById('eventForm').addEventListener('submit', async function(e){
    e.preventDefault();
    if(!SEL||SEL.type!=='dept') return;
    var msg=document.getElementById('evtMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=t('Sending…','በመላክ ላይ…');
    var title=document.getElementById('evtTitle').value.trim();
    var desc=document.getElementById('evtDesc').value.trim();
    var start=document.getElementById('evtStart').value;
    var end=document.getElementById('evtEnd').value;
    var body={department_id:Number(SEL.dept.id),title:title,start_datetime:start};
    if(desc) body.description=desc;
    if(end) body.end_datetime=end;
    try{
      await api('/api/teacher/events.php',{method:'POST',body:JSON.stringify(body)});
      msg.className='text-sm text-olive'; msg.textContent=t('Proposed','ሐሳብ ቀርቧል');
      document.getElementById('eventForm').reset();
      loadEvents();
    }catch(err){ msg.className='text-sm text-error'; msg.textContent=err.message; }
  });

  // ---------- lang toggle ----------
  (function(){
    function applyLang(lang){
      if(lang!=='en'&&lang!=='am') lang='en';
      document.documentElement.setAttribute('data-lang',lang);
      document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var v=el.getAttribute('data-'+lang); if(v!==null) el.innerHTML=v; });
      document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.classList.toggle('seg-active',btn.dataset.lang===lang); btn.classList.toggle('text-ink-soft',btn.dataset.lang!==lang); });
      try{ localStorage.setItem('gs_lang',lang); }catch(e){}
      // re-render dynamic bits in the new language
      renderChips(); renderNotifs();
      if(SEL){ openWorkspace(); }
    }
    document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.addEventListener('click',function(){ applyLang(btn.dataset.lang); }); });
    var saved='en'; try{ saved=localStorage.getItem('gs_lang')||'en'; }catch(e){}
    document.documentElement.setAttribute('data-lang',saved);
    document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var v=el.getAttribute('data-'+saved); if(v!==null) el.innerHTML=v; });
    document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.classList.toggle('seg-active',btn.dataset.lang===saved); btn.classList.toggle('text-ink-soft',btn.dataset.lang!==saved); });
  })();

  document.getElementById('logoutBtn').addEventListener('click', async function(){
    try{ await api('/api/auth/logout.php',{method:'POST'}); window.location.href='/'; }catch(e){ alert(e.message); }
  });

  boot();
</script>
</body>
</html>
