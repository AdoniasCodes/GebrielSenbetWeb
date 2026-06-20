<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Grade Levels';
$page_title_am = 'የክፍል ደረጃዎች';
$page_eyebrow    = 'Classroom';
$page_eyebrow_am = 'ክፍል';
$active_nav = 'levels';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl" data-en="Grades 1–11 of the diocese curriculum. Grades 7–11 also carry a Ge'ez name (ቀዳማይ … ሃምሳይ). Use “Subjects” to set which curriculum subjects each grade studies." data-am="የሀገረ ስብከቱ ሥርዓተ ትምህርት ከ1ኛ–11ኛ ክፍል። ከ7ኛ–11ኛ ክፍል የግዕዝ ስም አላቸው (ቀዳማይ … ሃምሳይ)። እያንዳንዱ ክፍል የሚማራቸውን ትምህርቶች ለማስተካከል “ትምህርቶች” ይጠቀሙ።">Grades 1–11 of the diocese curriculum. Grades 7–11 also carry a Ge'ez name. Use "Subjects" to set which curriculum subjects each grade studies.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span data-en="New level" data-am="አዲስ ደረጃ">New level</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New level</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <input type="hidden" id="f_id" />
    <div>
      <label class="lbl" data-en="Track" data-am="ኮርስ">Track</label>
      <select id="f_track" class="input-field" required></select>
    </div>
    <div>
      <label class="lbl" data-en="Name (English)" data-am="ስም (እንግሊዝኛ)">Name (English)</label>
      <input id="f_name" class="input-field" required placeholder="e.g. Grade 1" />
    </div>
    <div>
      <label class="lbl" data-en="Name (Amharic)" data-am="ስም (አማርኛ)">Name (Amharic)</label>
      <input id="f_name_am" class="input-field ethiopic" placeholder="ለምሳሌ 1ኛ ክፍል" />
    </div>
    <div>
      <label class="lbl" data-en="Ge'ez alias (Grades 7–11)" data-am="የግዕዝ ስም (7–11)">Ge'ez alias (Grades 7–11)</label>
      <input id="f_alias" class="input-field ethiopic" placeholder="ቀዳማይ … ሃምሳይ" />
    </div>
    <div>
      <label class="lbl" data-en="Sort order" data-am="ቅደም ተከተል">Sort order</label>
      <input id="f_sort" type="number" class="input-field" value="0" />
    </div>
    <div class="md:col-span-3 flex items-center gap-3">
      <button type="submit" class="btn-primary" data-en="Save" data-am="አስቀምጥ">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<!-- Curriculum subjects assignment for a grade -->
<section id="subjPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="Curriculum subjects" data-am="የሥርዓተ ትምህርት ትምህርቶች">Curriculum subjects</span> · <span id="subjFor" class="text-gold"></span></h2>
    <button type="button" id="subjClose" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <div class="p-6">
    <p class="text-xs text-ink-soft mb-3" data-en="Check the subjects this grade studies. Saving replaces the grade's subject set." data-am="ይህ ክፍል የሚማራቸውን ትምህርቶች ምልክት ያድርጉ። ማስቀመጥ የክፍሉን ትምህርቶች ይተካል።">Check the subjects this grade studies. Saving replaces the grade's subject set.</p>
    <div id="subjChecks" class="grid grid-cols-1 md:grid-cols-2 gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 max-h-80 overflow-y-auto"></div>
    <div class="flex items-center gap-3 mt-4">
      <button type="button" id="subjSave" class="btn-primary" data-en="Save subjects" data-am="ትምህርቶች አስቀምጥ">Save subjects</button>
      <button type="button" id="subjCancel" class="btn-ghost" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
    </div>
  </div>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="All levels" data-am="ሁሉም ደረጃዎች">All levels</span> · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th data-en="Name" data-am="ስም">Name</th>
        <th data-en="Amharic" data-am="አማርኛ">Amharic</th>
        <th data-en="Ge'ez" data-am="ግዕዝ">Ge'ez</th>
        <th data-en="Subjects" data-am="ትምህርቶች">Subjects</th>
        <th data-en="Sort" data-am="ቅደም">Sort</th>
        <th class="text-right">&nbsp;</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="6" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#564242;margin-bottom:8px;}</style>

<script>
  var all = [], tracks = [], allSubjects = [], subjLevelId = null;
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function v(id){return document.getElementById(id);}

  function render() {
    v('rowCount').textContent = all.length + (curLang()==='am'?' ደረጃዎች':' total');
    var tbody = v('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-soft py-16">No levels yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function (l) {
      return '<tr>' +
        '<td class="font-medium">'+escHtml(l.name)+'</td>' +
        '<td class="text-ink-soft ethiopic">'+escHtml(l.name_am||'—')+'</td>' +
        '<td class="text-ink-soft ethiopic">'+escHtml(l.alias||'—')+'</td>' +
        '<td><button class="pill pill-active" data-subjects="'+l.id+'" title="Edit curriculum subjects">'+(l.subject_count||0)+' '+(curLang()==='am'?'ትምህርቶች':'subjects')+'</button></td>' +
        '<td class="text-ink-soft">'+escHtml(l.sort_order)+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+l.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          '<button class="btn-icon danger" title="Archive" data-archive="'+l.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  function fillTrackSelect() {
    v('f_track').innerHTML = tracks.filter(function(t){return t.is_archived==0;}).map(function(t){ return '<option value="'+t.id+'">'+escHtml(t.name)+'</option>'; }).join('');
  }

  async function load() {
    try {
      var [t, d, s] = await Promise.all([
        gs.api('/api/admin/tracks/index.php'),
        gs.api('/api/admin/levels/index.php'),
        gs.api('/api/admin/subjects/index.php'),
      ]);
      tracks = t.data || []; fillTrackSelect();
      allSubjects = s.data || [];
      all = d.data || []; render();
    } catch (e) { gs.toast(e.message,'error'); }
  }

  // ---- level form ----
  var formPanel = v('formPanel'), formTitle = v('formTitle'), msg = v('formMsg');
  function showForm(item) {
    formPanel.classList.remove('hidden'); v('subjPanel').classList.add('hidden'); msg.classList.add('hidden');
    v('f_id').value = item?item.id:''; v('f_name').value = item?(item.name||''):''; v('f_name_am').value = item?(item.name_am||''):'';
    v('f_alias').value = item?(item.alias||''):''; v('f_sort').value = item?item.sort_order:'0'; v('f_track').value = item?item.track_id:(tracks[0]?tracks[0].id:'');
    formTitle.textContent = item?(curLang()==='am'?'ደረጃ አርትዕ':'Edit level'):(curLang()==='am'?'አዲስ ደረጃ':'New level');
    formPanel.scrollIntoView({behavior:'smooth',block:'center'});
  }
  function hideForm(){ formPanel.classList.add('hidden'); }
  v('newBtn').addEventListener('click', function(){ showForm(null); });
  v('cancelBtn').addEventListener('click', hideForm);
  v('cancelBtn2').addEventListener('click', hideForm);

  v('entityForm').addEventListener('submit', async function(e){
    e.preventDefault(); msg.classList.add('hidden');
    var id = v('f_id').value;
    var body = { track_id: parseInt(v('f_track').value,10), name: v('f_name').value.trim(), name_am: v('f_name_am').value.trim(), alias: v('f_alias').value.trim(), sort_order: parseInt(v('f_sort').value||'0',10) };
    if (!body.track_id || !body.name) return;
    try {
      if (id) { body.id=parseInt(id,10); await gs.api('/api/admin/levels/index.php',{method:'PUT',body:JSON.stringify(body)}); }
      else await gs.api('/api/admin/levels/index.php',{method:'POST',body:JSON.stringify(body)});
      gs.toast(id?'Updated':'Created','success'); hideForm(); load();
    } catch(err){ msg.textContent=err.message; msg.classList.remove('hidden'); }
  });

  // ---- curriculum subjects panel ----
  async function openSubjects(levelId) {
    subjLevelId = levelId;
    var lvl = all.find(function(x){return x.id===levelId;});
    v('subjFor').textContent = lvl ? (curLang()==='am' ? (lvl.name_am||lvl.name) : lvl.name) + (lvl.alias?(' / '+lvl.alias):'') : '';
    formPanel.classList.add('hidden');
    v('subjPanel').classList.remove('hidden');
    v('subjChecks').innerHTML = '<p class="text-sm text-ink-soft">Loading…</p>';
    v('subjPanel').scrollIntoView({behavior:'smooth',block:'center'});
    try {
      var cur = await gs.api('/api/admin/grade-subjects/index.php?level_id='+levelId);
      var assigned = (cur.data||[]).map(function(r){return r.subject_id;});
      v('subjChecks').innerHTML = allSubjects.filter(function(s){return s.is_archived==0;}).map(function(s){
        var ck = assigned.indexOf(s.id)>=0 ? 'checked' : '';
        var label = escHtml(s.name) + (s.name_am ? ' <span class="text-ink-soft ethiopic">('+escHtml(s.name_am)+')</span>' : '');
        return '<label class="inline-flex items-center gap-2 text-sm py-1"><input type="checkbox" class="w-4 h-4 subj-check" value="'+s.id+'" '+ck+' /><span>'+label+'</span></label>';
      }).join('') || '<p class="text-sm text-ink-soft">No subjects in the catalog yet — add some on the Subjects page.</p>';
    } catch(e){ gs.toast(e.message,'error'); }
  }
  function hideSubjects(){ v('subjPanel').classList.add('hidden'); subjLevelId=null; }
  v('subjClose').addEventListener('click', hideSubjects);
  v('subjCancel').addEventListener('click', hideSubjects);
  v('subjSave').addEventListener('click', async function(){
    if (!subjLevelId) return;
    var ids = Array.prototype.slice.call(document.querySelectorAll('.subj-check:checked')).map(function(c){return parseInt(c.value,10);});
    try { await gs.api('/api/admin/grade-subjects/index.php',{method:'PUT',body:JSON.stringify({level_id:subjLevelId, subject_ids:ids})}); gs.toast(curLang()==='am'?'ተቀምጧል':'Saved','success'); hideSubjects(); load(); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive], [data-subjects]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive || t.dataset.subjects, 10);
    if (t.dataset.subjects) { openSubjects(id); }
    else if (t.dataset.edit) { var item = all.find(function(x){return x.id===id;}); if (item) showForm(item); }
    else {
      if (!await gs.confirm(curLang()==='am'?'ይህን ደረጃ ማህደር ውስጥ ያስገቡ?':'Archive this level?')) return;
      try { await gs.api('/api/admin/levels/index.php',{method:'DELETE',body:JSON.stringify({id:id})}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  document.addEventListener('gs:lang-change', render);
  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
