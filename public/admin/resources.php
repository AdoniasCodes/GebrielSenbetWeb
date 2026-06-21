<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Resources';
$page_title_am = 'መርጃዎች';
$page_eyebrow    = 'Library';
$page_eyebrow_am = 'ቤተ መጻሕፍት';
$active_nav = 'resources';
require __DIR__ . '/_partials/page-shell.php';
?>

<p class="text-sm text-ink-soft max-w-2xl" data-en="Attach and share files or links with a specific grade or department. Members of that grade or department can open them from their area." data-am="ፋይሎችን ወይም አገናኞችን ለአንድ የተወሰነ ክፍል ወይም መምሪያ ያያይዙ እና ያጋሩ። የዚያ ክፍል ወይም መምሪያ አባላት ከቦታቸው ሊከፍቷቸው ይችላሉ።">Attach and share files or links with a specific grade or department.</p>

<section class="panel mt-6 p-6">
  <div class="flex flex-wrap items-end gap-5">
    <div>
      <label class="lbl" data-en="Scope" data-am="ወሰን">Scope</label>
      <div id="scopeToggle" class="inline-flex bg-surface-mid rounded-md p-0.5 border border-outline-soft/50">
        <button type="button" data-scope="grade" class="scope-btn px-4 py-1.5 text-sm font-semibold rounded">Grades</button>
        <button type="button" data-scope="department" class="scope-btn px-4 py-1.5 text-sm font-semibold rounded text-ink-soft" data-en="Departments" data-am="መምሪያዎች">Departments</button>
      </div>
    </div>
    <div class="min-w-[240px] grow max-w-sm">
      <label class="lbl" id="scopeLabel" data-en="Grade" data-am="ክፍል">Grade</label>
      <select id="scopeSelect" class="input-field"><option value="">…</option></select>
    </div>
  </div>
</section>

<section id="panelMain" class="grid lg:grid-cols-3 gap-6 mt-6 hidden">
  <div class="lg:col-span-2 panel self-start">
    <header class="px-6 py-4 border-b border-outline-soft/40">
      <h2 id="listTitle" class="font-display text-lg text-ink" data-en="Resources" data-am="መርጃዎች">Resources</h2>
    </header>
    <ul id="resList" class="divide-y divide-outline-soft/20"></ul>
  </div>

  <div class="space-y-6">
    <div class="panel p-5">
      <h3 class="font-display text-base text-ink mb-3" data-en="Upload a file" data-am="ፋይል ይጫኑ">Upload a file</h3>
      <form id="fileForm" class="space-y-3">
        <input id="fileTitle" class="input-field" data-en-ph="Title (optional)" placeholder="Title (optional)" />
        <input id="fileInput" type="file" class="block w-full text-sm text-ink-soft file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-wide file:bg-surface-mid file:text-primary hover:file:bg-outline-soft/30" />
        <p class="text-[11px] text-outline" data-en="PDF, Office, images, audio, zip — up to 25 MB." data-am="PDF፣ Office፣ ምስሎች፣ ድምፅ፣ zip — እስከ 25 ሜባ።">PDF, Office, images, audio, zip — up to 25 MB.</p>
        <button class="btn-primary w-full justify-center" data-en="Upload" data-am="ይጫኑ">Upload</button>
      </form>
    </div>
    <div class="panel p-5">
      <h3 class="font-display text-base text-ink mb-3" data-en="Add a link" data-am="አገናኝ ይጨምሩ">Add a link</h3>
      <form id="linkForm" class="space-y-3">
        <input id="linkTitle" class="input-field" placeholder="Title" data-en-ph="Title" />
        <input id="linkUrl" class="input-field" placeholder="https://…" />
        <button class="btn-primary w-full justify-center" data-en="Add link" data-am="አገናኝ ጨምር">Add link</button>
      </form>
    </div>
  </div>
</section>

<script>
(function () {
  function $(id){ return document.getElementById(id); }
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  function curLang(){ return document.documentElement.getAttribute('data-lang') || 'en'; }
  var am = function(){ return curLang() === 'am'; };

  var scope = 'grade', scopeId = 0, cache = {};

  function setActiveToggle(btn){
    document.querySelectorAll('#scopeToggle .scope-btn').forEach(function(x){
      var on = x === btn;
      x.classList.toggle('bg-primary', on); x.classList.toggle('text-surface', on);
      x.classList.toggle('text-ink-soft', !on);
    });
  }

  async function loadOptions(){
    var sel = $('scopeSelect');
    sel.innerHTML = '<option value="">' + (am() ? 'ይምረጡ…' : 'Select…') + '</option>';
    var url = scope === 'grade' ? '/api/admin/levels/index.php' : '/api/admin/departments/index.php';
    try {
      if (!cache[scope]) cache[scope] = (await gs.api(url)).data || [];
      cache[scope].forEach(function(r){
        var name = (am() && r.name_am) ? r.name_am : r.name;
        var o = document.createElement('option'); o.value = r.id; o.textContent = name; sel.appendChild(o);
      });
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  async function loadList(){
    if (!scopeId){ $('panelMain').classList.add('hidden'); return; }
    $('panelMain').classList.remove('hidden');
    var ul = $('resList');
    ul.innerHTML = '<li class="px-6 py-8 text-center text-ink-soft text-sm">…</li>';
    try {
      var rows = (await gs.api('/api/admin/resources/index.php?scope_type=' + scope + '&scope_id=' + scopeId)).data || [];
      if (!rows.length){ ul.innerHTML = '<li class="px-6 py-10 text-center text-ink-soft text-sm">' + (am() ? 'ገና ምንም መርጃ የለም።' : 'No resources yet.') + '</li>'; return; }
      ul.innerHTML = rows.map(function(r){
        var icon = r.kind === 'link' ? '🔗' : '📄';
        var meta = (r.kind === 'file' && r.size_bytes) ? (Math.round(r.size_bytes/1024) + ' KB') : (r.kind === 'link' ? (am()?'አገናኝ':'Link') : '');
        return '<li class="px-6 py-4 flex items-center justify-between gap-4">' +
          '<a href="' + esc(r.url) + '" target="_blank" rel="noopener" class="min-w-0 flex items-center gap-3 group">' +
            '<span class="text-lg">' + icon + '</span>' +
            '<span class="min-w-0"><span class="block font-medium truncate group-hover:text-primary">' + esc(r.title) + '</span>' +
            '<span class="block text-xs text-outline">' + esc(meta) + '</span></span></a>' +
          '<button data-del="' + r.id + '" class="btn-icon shrink-0" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></button>' +
        '</li>';
      }).join('');
    } catch (e) { ul.innerHTML = '<li class="px-6 py-8 text-center text-error text-sm">' + esc(e.message) + '</li>'; }
  }

  document.querySelectorAll('#scopeToggle .scope-btn').forEach(function(b){
    b.addEventListener('click', function(){
      scope = b.dataset.scope; scopeId = 0;
      setActiveToggle(b);
      $('scopeLabel').textContent = scope === 'grade' ? (am()?'ክፍል':'Grade') : (am()?'መምሪያ':'Department');
      $('panelMain').classList.add('hidden');
      loadOptions();
    });
  });
  $('scopeSelect').addEventListener('change', function(){ scopeId = parseInt(this.value, 10) || 0; loadList(); });

  $('resList').addEventListener('click', async function(e){
    var d = e.target.closest('[data-del]'); if (!d) return;
    if (!await gs.confirm(am() ? 'ይህን መርጃ ያስወግዱ?' : 'Remove this resource?')) return;
    try { await gs.api('/api/admin/resources/index.php', { method:'DELETE', body: JSON.stringify({ id: parseInt(d.dataset.del,10) }) }); gs.toast(am()?'ተወግዷል':'Removed','success'); loadList(); }
    catch (err) { gs.toast(err.message, 'error'); }
  });

  $('fileForm').addEventListener('submit', async function(e){
    e.preventDefault();
    if (!scopeId){ gs.toast(am()?'መጀመሪያ ክፍል/መምሪያ ይምረጡ':'Pick a grade or department first','error'); return; }
    var fi = $('fileInput'); if (!fi.files.length){ gs.toast(am()?'ፋይል ይምረጡ':'Choose a file','error'); return; }
    var fd = new FormData();
    fd.append('scope_type', scope); fd.append('scope_id', scopeId);
    fd.append('title', $('fileTitle').value.trim()); fd.append('file', fi.files[0]);
    try {
      var token = await gs.ensureCsrf();
      var res = await fetch('/api/admin/resources/index.php', { method:'POST', headers: { 'X-CSRF-Token': token }, body: fd });
      var data; try { data = await res.json(); } catch (_) { data = {}; }
      if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
      $('fileForm').reset(); gs.toast(am()?'ተጭኗል':'Uploaded','success'); loadList();
    } catch (err) { gs.toast(err.message, 'error'); }
  });

  $('linkForm').addEventListener('submit', async function(e){
    e.preventDefault();
    if (!scopeId){ gs.toast(am()?'መጀመሪያ ክፍል/መምሪያ ይምረጡ':'Pick a grade or department first','error'); return; }
    try {
      await gs.api('/api/admin/resources/index.php', { method:'POST', body: JSON.stringify({ scope_type: scope, scope_id: scopeId, title: $('linkTitle').value.trim(), url: $('linkUrl').value.trim() }) });
      $('linkForm').reset(); gs.toast(am()?'ታክሏል':'Added','success'); loadList();
    } catch (err) { gs.toast(err.message, 'error'); }
  });

  // init
  setActiveToggle(document.querySelector('#scopeToggle [data-scope="grade"]'));
  loadOptions();
})();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
