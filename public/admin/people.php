<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'People';
$page_title_am = 'አባላት';
$page_eyebrow    = 'Community';
$page_eyebrow_am = 'ማህበረሰብ';
$active_nav = 'people';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl" data-en="Every member of the school — students, servants, volunteers, staff. One person can belong to many departments. Department membership is managed on the Departments page." data-am="የትምህርት ቤቱ እያንዳንዱ አባል — ተማሪዎች፣ አገልጋዮች፣ በጎ ፈቃደኞች፣ ሰራተኞች። አንድ ሰው በብዙ ክፍሎች ሊያገለግል ይችላል። የክፍል አባልነት በክፍሎች ገጽ ይተዳደራል።">Every member of the school — students, servants, volunteers, staff. One person can belong to many departments. Department membership is managed on the Departments page.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span data-en="New person" data-am="አዲስ ሰው">New person</span>
  </button>
</div>

<!-- Filters -->
<section class="panel p-4 flex flex-wrap items-end gap-3">
  <div class="flex-1 min-w-[200px]">
    <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Search" data-am="ፈልግ">Search</label>
    <input id="fltQ" type="text" class="input-field" placeholder="Name, baptismal name, phone…" />
  </div>
  <div>
    <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Church" data-am="ቤተክርስቲያን">Church</label>
    <select id="fltChurch" class="input-field"><option value="">All</option></select>
  </div>
  <div>
    <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Department" data-am="ክፍል">Department</label>
    <select id="fltDept" class="input-field"><option value="">All</option></select>
  </div>
  <div>
    <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Status" data-am="ሁኔታ">Status</label>
    <select id="fltStatus" class="input-field">
      <option value="">All</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
      <option value="alumni">Alumni</option>
      <option value="prospective">Prospective</option>
    </select>
  </div>
</section>

<!-- Form -->
<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New person</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div><label class="lbl" data-en="First name" data-am="የመጀመሪያ ስም">First name</label><input id="f_first" type="text" class="input-field" required /></div>
    <div><label class="lbl" data-en="Last name" data-am="የአባት ስም">Last name</label><input id="f_last" type="text" class="input-field" required /></div>
    <div><label class="lbl" data-en="Baptismal name" data-am="የክርስትና ስም">Baptismal name</label><input id="f_baptismal" type="text" class="input-field ethiopic" /></div>
    <div><label class="lbl" data-en="Phone" data-am="ስልክ">Phone</label><input id="f_phone" type="text" class="input-field" /></div>
    <div><label class="lbl" data-en="Date of birth" data-am="የልደት ቀን">Date of birth</label><input id="f_dob" type="date" class="input-field" /></div>
    <div>
      <label class="lbl" data-en="Gender" data-am="ጾታ">Gender</label>
      <select id="f_gender" class="input-field"><option value="">—</option><option value="male" data-en="Male" data-am="ወንድ">Male</option><option value="female" data-en="Female" data-am="ሴት">Female</option></select>
    </div>
    <div>
      <label class="lbl" data-en="Primary church" data-am="ዋና ቤተክርስቲያን">Primary church</label>
      <select id="f_church" class="input-field"><option value="">—</option></select>
    </div>
    <div>
      <label class="lbl" data-en="Member status" data-am="የአባልነት ሁኔታ">Member status</label>
      <select id="f_status" class="input-field">
        <option value="active" data-en="Active" data-am="ንቁ">Active</option>
        <option value="inactive" data-en="Inactive" data-am="ቦዝ">Inactive</option>
        <option value="alumni" data-en="Alumni" data-am="ምሩቅ">Alumni</option>
        <option value="prospective" data-en="Prospective" data-am="አዲስ">Prospective</option>
      </select>
    </div>
    <div><label class="lbl" data-en="Joined on" data-am="የተቀላቀለበት">Joined on</label><input id="f_joined" type="date" class="input-field" /></div>
    <div><label class="lbl" data-en="Last Holy Communion" data-am="የመጨረሻ ቅዱስ ቁርባን">Last Holy Communion</label><input id="f_communion" type="date" class="input-field" /></div>
    <div class="md:col-span-2"><label class="lbl" data-en="Address" data-am="አድራሻ">Address</label><input id="f_address" type="text" class="input-field" /></div>
    <div class="md:col-span-2"><label class="lbl" data-en="Notes" data-am="ማስታወሻ">Notes</label><textarea id="f_notes" rows="2" class="input-field"></textarea></div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary" data-en="Save" data-am="አስቀምጥ">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
      <p id="formMsg" class="text-sm hidden"></p>
    </div>
  </form>
</section>

<!-- Login grant panel -->
<section id="loginPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="Login for" data-am="መግቢያ ለ">Login for</span> <span id="loginFor" class="text-gold"></span></h2>
    <button type="button" id="loginClose" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <div class="p-6">
    <div id="loginExisting" class="hidden mb-4 p-3 bg-surface-low rounded border border-outline-soft/30 text-sm">
      <p><span data-en="Email" data-am="ኢሜይል">Email</span>: <span id="loginEmail" class="font-medium"></span> · <span data-en="Role" data-am="ሚና">Role</span>: <span id="loginRole" class="font-medium"></span></p>
    </div>
    <form id="loginForm" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div><label class="lbl" data-en="Email" data-am="ኢሜይል">Email</label><input id="lg_email" type="email" class="input-field" /></div>
      <div>
        <label class="lbl" data-en="Role" data-am="ሚና">Role</label>
        <select id="lg_role" class="input-field">
          <option value="staff" data-en="Staff (dept head)" data-am="ሰራተኛ (ክፍል ሃላፊ)">Staff (dept head)</option>
          <option value="teacher" data-en="Teacher" data-am="መምህር">Teacher</option>
          <option value="parent" data-en="Parent" data-am="ወላጅ">Parent</option>
          <option value="student" data-en="Student" data-am="ተማሪ">Student</option>
          <option value="admin" data-en="Admin" data-am="አስተዳዳሪ">Admin</option>
        </select>
      </div>
      <div><label class="lbl" data-en="Password (blank = auto)" data-am="የይለፍ ቃል (ባዶ = በራስ)">Password (blank = auto)</label><input id="lg_password" type="text" class="input-field" placeholder="auto-generate" /></div>
      <div class="md:col-span-3 flex items-center gap-3">
        <button type="submit" id="lg_submit" class="btn-primary" data-en="Grant login" data-am="መግቢያ ስጥ">Grant login</button>
        <button type="button" id="lg_revoke" class="btn-ghost hidden" data-en="Revoke login" data-am="መግቢያ ሰርዝ">Revoke login</button>
        <p id="loginMsg" class="text-sm hidden"></p>
      </div>
    </form>
  </div>
</section>

<!-- Table -->
<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="People" data-am="አባላት">People</span> · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th data-en="Name" data-am="ስም">Name</th>
        <th data-en="Church" data-am="ቤተክርስቲያን">Church</th>
        <th data-en="Departments" data-am="ክፍሎች">Departments</th>
        <th data-en="Status" data-am="ሁኔታ">Status</th>
        <th data-en="Last Communion" data-am="የመጨረሻ ቁርባን">Last Communion</th>
        <th class="text-right">&nbsp;</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="6" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#564242;margin-bottom:8px;}</style>

<script>
  var all = [], churches = [], depts = [];
  var STATUS_LABEL = { active:{en:'Active',am:'ንቁ'}, inactive:{en:'Inactive',am:'ቦዝ'}, alumni:{en:'Alumni',am:'ምሩቅ'}, prospective:{en:'Prospective',am:'አዲስ'} };
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang') || 'en'; }

  function fillSelect(sel, items, valKey, labelFn, keepFirst) {
    var first = keepFirst ? sel.querySelector('option').outerHTML : '';
    sel.innerHTML = first + items.map(function(it){ return '<option value="'+it[valKey]+'">'+escHtml(labelFn(it))+'</option>'; }).join('');
  }

  function render() {
    var lang = curLang();
    document.getElementById('rowCount').textContent = all.length + (lang==='am' ? ' አባላት' : ' total');
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-soft py-16" data-en="No people yet." data-am="እስካሁን አባል የለም።">No people yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function(p){
      var st = STATUS_LABEL[p.member_status] || {en:p.member_status,am:p.member_status};
      var pill = p.member_status==='active' ? 'pill-active' : 'pill-archived';
      var name = escHtml(p.first_name+' '+p.last_name) + (p.baptismal_name ? ' <span class="text-ink-soft ethiopic">('+escHtml(p.baptismal_name)+')</span>' : '');
      return '<tr>' +
        '<td><p class="font-medium">'+name+'</p>'+(p.phone?'<p class="text-xs text-ink-soft">'+escHtml(p.phone)+'</p>':'')+'</td>' +
        '<td class="text-ink-soft text-sm">'+escHtml(p.church_name||'—')+'</td>' +
        '<td class="text-ink-soft text-sm ethiopic">'+escHtml(p.departments||'—')+'</td>' +
        '<td><span class="pill '+pill+'">'+escHtml(st[lang]||st.en)+'</span></td>' +
        '<td class="text-ink-soft text-sm">'+(p.last_communion_date?('<span data-iso="'+escHtml(p.last_communion_date)+'" data-fmt-style="long">'+escHtml(p.last_communion_date)+'</span>'):'<span class="text-error/70" title="No record">—</span>')+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Login / access" data-login="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></button>' +
          '<button class="btn-icon" title="Edit" data-edit="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          '<button class="btn-icon danger" title="Archive" data-archive="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
    if (window.EC) EC.rerenderIsoNodes();
  }

  function buildQuery() {
    var p = new URLSearchParams();
    var q = document.getElementById('fltQ').value.trim(); if (q) p.set('q', q);
    var c = document.getElementById('fltChurch').value; if (c) p.set('church_id', c);
    var d = document.getElementById('fltDept').value; if (d) p.set('department_id', d);
    var s = document.getElementById('fltStatus').value; if (s) p.set('status', s);
    return p.toString();
  }

  async function load() {
    try {
      var res = await gs.api('/api/admin/people/index.php' + (buildQuery() ? ('?'+buildQuery()) : ''));
      all = res.data || [];
      render();
    } catch(e) { gs.toast(e.message,'error'); }
  }

  async function loadLookups() {
    try {
      var [c, d] = await Promise.all([
        gs.api('/api/admin/churches/index.php'),
        gs.api('/api/admin/departments/index.php'),
      ]);
      churches = c.data || []; depts = d.data || [];
      fillSelect(document.getElementById('fltChurch'), churches, 'id', function(x){return x.short_name||x.name;}, true);
      fillSelect(document.getElementById('f_church'), churches, 'id', function(x){return x.short_name||x.name;}, true);
      fillSelect(document.getElementById('fltDept'), depts, 'id', function(x){return (x.parent_id?'— ':'')+(x.name_am||x.name);}, true);
    } catch(e) { gs.toast(e.message,'error'); }
  }

  // Form
  var formPanel = document.getElementById('formPanel'), formTitle = document.getElementById('formTitle'), msg = document.getElementById('formMsg');
  function v(id){return document.getElementById(id);}
  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.className = 'text-sm hidden';
    v('f_id').value = item ? item.id : '';
    v('f_first').value = item ? (item.first_name||'') : '';
    v('f_last').value = item ? (item.last_name||'') : '';
    v('f_baptismal').value = item ? (item.baptismal_name||'') : '';
    v('f_phone').value = item ? (item.phone||'') : '';
    v('f_dob').value = item ? (item.date_of_birth||'') : '';
    v('f_gender').value = item ? (item.gender||'') : '';
    v('f_church').value = item && item.primary_church_id ? item.primary_church_id : '';
    v('f_status').value = item ? (item.member_status||'active') : 'active';
    v('f_joined').value = item ? (item.joined_at||'') : '';
    v('f_communion').value = item ? (item.last_communion_date||'') : '';
    v('f_address').value = item ? (item.address||'') : '';
    v('f_notes').value = item ? (item.notes||'') : '';
    formTitle.textContent = item ? (curLang()==='am'?'ሰው አርትዕ':'Edit person') : (curLang()==='am'?'አዲስ ሰው':'New person');
    formPanel.scrollIntoView({behavior:'smooth', block:'center'});
  }
  function hideForm(){ formPanel.classList.add('hidden'); }
  v('newBtn').addEventListener('click', function(){ showForm(null); });
  v('cancelBtn').addEventListener('click', hideForm);
  v('cancelBtn2').addEventListener('click', hideForm);

  v('entityForm').addEventListener('submit', async function(e){
    e.preventDefault();
    msg.className = 'text-sm hidden';
    var id = v('f_id').value;
    var body = {
      first_name: v('f_first').value.trim(),
      last_name: v('f_last').value.trim(),
      baptismal_name: v('f_baptismal').value.trim(),
      phone: v('f_phone').value.trim(),
      date_of_birth: v('f_dob').value || null,
      gender: v('f_gender').value,
      primary_church_id: v('f_church').value ? parseInt(v('f_church').value,10) : null,
      member_status: v('f_status').value,
      joined_at: v('f_joined').value || null,
      last_communion_date: v('f_communion').value || null,
      address: v('f_address').value.trim(),
      notes: v('f_notes').value.trim(),
    };
    try {
      if (id) {
        body.id = parseInt(id,10);
        await gs.api('/api/admin/people/index.php', { method:'PUT', body: JSON.stringify(body) });
        gs.toast(curLang()==='am'?'ተዘምኗል':'Updated','success');
      } else {
        await gs.api('/api/admin/people/index.php', { method:'POST', body: JSON.stringify(body) });
        gs.toast(curLang()==='am'?'ተፈጥሯል':'Created','success');
      }
      hideForm(); load();
    } catch(err) { msg.className='text-sm text-error'; msg.textContent = err.message; }
  });

  // ---- Login grant/manage ----
  var loginPanel = v('loginPanel'), loginMsg = v('loginMsg'), loginPersonId = null, loginHas = false;
  async function openLogin(person) {
    loginPersonId = person.id; loginHas = false;
    loginPanel.classList.remove('hidden'); formPanel.classList.add('hidden'); loginMsg.className='text-sm hidden';
    v('loginFor').textContent = person.first_name + ' ' + person.last_name;
    v('loginExisting').classList.add('hidden');
    v('lg_email').value = ''; v('lg_email').disabled = false; v('lg_password').value = ''; v('lg_role').value = 'staff';
    v('lg_revoke').classList.add('hidden');
    v('lg_submit').setAttribute('data-en','Grant login'); v('lg_submit').setAttribute('data-am','መግቢያ ስጥ'); v('lg_submit').textContent = curLang()==='am'?'መግቢያ ስጥ':'Grant login';
    loginPanel.scrollIntoView({behavior:'smooth',block:'center'});
    try {
      var st = await gs.api('/api/admin/people/login.php?person_id='+person.id);
      if (st.has_login) {
        loginHas = true;
        v('loginExisting').classList.remove('hidden');
        v('loginEmail').textContent = st.email; v('loginRole').textContent = st.role;
        v('lg_email').value = st.email; v('lg_email').disabled = true; v('lg_role').value = st.role;
        v('lg_revoke').classList.remove('hidden');
        v('lg_submit').textContent = curLang()==='am'?'ቀይር / የይለፍ ቃል ዳግም አስጀምር':'Update / reset password';
      }
    } catch(e){ gs.toast(e.message,'error'); }
  }
  function hideLogin(){ loginPanel.classList.add('hidden'); loginPersonId=null; }
  v('loginClose').addEventListener('click', hideLogin);

  v('loginForm').addEventListener('submit', async function(e){
    e.preventDefault(); if(!loginPersonId) return; loginMsg.className='text-sm hidden';
    var role = v('lg_role').value, pw = v('lg_password').value.trim();
    try {
      if (loginHas) {
        var body = { person_id: loginPersonId, role: role };
        if (pw) body.password = pw;
        await gs.api('/api/admin/people/login.php', { method:'PUT', body: JSON.stringify(body) });
        gs.toast(curLang()==='am'?'ተዘምኗል':'Updated','success'); hideLogin(); load();
      } else {
        var email = v('lg_email').value.trim();
        if (!email) { loginMsg.className='text-sm text-error'; loginMsg.textContent='Email is required.'; return; }
        var b = { person_id: loginPersonId, email: email, role: role };
        if (pw) b.password = pw;
        var res = await gs.api('/api/admin/people/login.php', { method:'POST', body: JSON.stringify(b) });
        loginMsg.className='text-sm'; loginMsg.style.color='#384700';
        loginMsg.textContent = (curLang()==='am'?'ተፈጥሯል። የይለፍ ቃል፡ ':'Created. Password: ') + (res.generated_password || pw) + (curLang()==='am'?' — አሁን ያጋሩ።':' — share it now.');
        load();
      }
    } catch(err){ loginMsg.className='text-sm text-error'; loginMsg.textContent=err.message; }
  });

  v('lg_revoke').addEventListener('click', async function(){
    if(!loginPersonId) return;
    if(!await gs.confirm(curLang()==='am'?'መግቢያውን ይሰርዙ?':'Revoke this login? The person can no longer sign in.')) return;
    try { await gs.api('/api/admin/people/login.php', { method:'DELETE', body: JSON.stringify({person_id:loginPersonId}) }); gs.toast(curLang()==='am'?'ተሰርዟል':'Revoked','success'); hideLogin(); load(); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  document.addEventListener('click', async function(e){
    var t = e.target.closest('[data-edit], [data-archive], [data-login]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive || t.dataset.login, 10);
    if (t.dataset.login) { var pl = all.find(function(x){return x.id===id;}); if (pl) openLogin(pl); return; }
    if (t.dataset.edit) { var item = all.find(function(x){return x.id===id;}); if (item) showForm(item); }
    else {
      if (!await gs.confirm(curLang()==='am'?'ይህን ሰው ማህደር ውስጥ ያስገቡ?':'Archive this person? They will be removed from all department rosters.')) return;
      try { await gs.api('/api/admin/people/index.php', { method:'DELETE', body: JSON.stringify({id:id})}); gs.toast(curLang()==='am'?'ማህደር ተደርጓል':'Archived','success'); load(); }
      catch(err){ gs.toast(err.message,'error'); }
    }
  });

  // Filters (debounced search)
  var deb; v('fltQ').addEventListener('input', function(){ clearTimeout(deb); deb=setTimeout(load,250); });
  ['fltChurch','fltDept','fltStatus'].forEach(function(id){ v(id).addEventListener('change', load); });
  document.addEventListener('gs:lang-change', render);

  loadLookups().then(load);
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
