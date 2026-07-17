    </main>
  </div>
</div>

<script>
  // === Bilingual swap (shared) ===
  (function () {
    function applyLang(lang) {
      if (lang !== 'en' && lang !== 'am') lang = 'en';
      document.documentElement.setAttribute('data-lang', lang);
      document.documentElement.lang = lang;
      document.querySelectorAll('[data-en], [data-am]').forEach(function (el) {
        var v = el.getAttribute('data-' + lang);
        if (v !== null) el.innerHTML = v;
      });
      document.querySelectorAll('[data-lang-toggle]').forEach(function (group) {
        group.querySelectorAll('button').forEach(function (btn) {
          btn.classList.toggle('seg-active', btn.dataset.lang === lang);
          btn.classList.toggle('text-ink-soft', btn.dataset.lang !== lang);
        });
      });
      try { localStorage.setItem('gs_lang', lang); } catch (e) {}
      // Re-render any date columns that were rendered with gs.fmtDate.
      document.querySelectorAll('[data-iso]').forEach(function (el) {
        var iso = el.getAttribute('data-iso');
        var st = el.getAttribute('data-fmt-style') || 'datetime';
        if (window.EC) el.textContent = EC.fmtDate(iso, st);
      });
      // Trigger a soft reload signal so list pages can refresh their tables.
      document.dispatchEvent(new CustomEvent('gs:lang-change', { detail: { lang: lang } }));
    }
    document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
      btn.addEventListener('click', function () { applyLang(btn.dataset.lang); });
    });
    var saved = 'en';
    try { saved = localStorage.getItem('gs_lang') || 'en'; } catch (e) {}
    applyLang(saved);
  })();

  // === CSRF + auth helpers (gs.api, gs.ensureCsrf, gs.fmtDate, gs.toast,
  //     gs.confirm) are now defined in <head> via page-shell.php so that page
  //     scripts can use them during initial load. ===

  // === Logout ===
  document.getElementById('logoutBtn').addEventListener('click', async function () {
    try {
      await gs.api('/api/auth/logout.php', { method: 'POST' });
      window.location.href = '/';
    } catch (e) { gs.toast(e.message, 'error'); }
  });

  // === Term pill — load current term name ===
  (async function () {
    try {
      var d = await gs.api('/api/admin/settings/current-term.php');
      if (d.term) {
        var label = document.getElementById('termPillLabel');
        if (label) {
          label.textContent = (d.term.academic_year || '') + ' · ' + (d.term.name || '');
        }
      }
    } catch (e) { /* fail silent */ }
  })();

  // === Admin inbox (role:admin + user-targeted notifications) ===
  (function () {
    var bell = document.getElementById('notifBell');
    var panel = document.getElementById('notifPanel');
    var list = document.getElementById('notifList');
    var badge = document.getElementById('notifBadge');
    if (!bell || !panel || !list) return;
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
    var NOTIFS = [];
    async function load() {
      try { var d = await gs.api('/api/admin/inbox/index.php'); NOTIFS = d.data || []; }
      catch (e) { NOTIFS = []; }
      var unread = NOTIFS.filter(function (n) { return !n.is_read; }).length;
      badge.textContent = unread; badge.classList.toggle('hidden', unread === 0);
      render();
    }
    function render() {
      var am = document.documentElement.getAttribute('data-lang') === 'am';
      if (!NOTIFS.length) { list.innerHTML = '<li class="py-6 text-center text-ink-soft text-sm">' + (am ? 'ማሳወቂያ የለም።' : 'No notifications.') + '</li>'; return; }
      list.innerHTML = NOTIFS.map(function (n) {
        return '<li class="py-3 flex items-start justify-between gap-3' + (n.is_read ? ' opacity-60' : '') + '">' +
          '<div><p class="font-medium text-xs">' + esc(n.title) + '</p>' +
          '<p class="text-xs text-ink-soft mt-0.5">' + esc(n.message) + '</p>' +
          '<p class="text-[10px] text-outline mt-1">' + esc(n.created_at || '') + '</p></div>' +
          (n.is_read ? '' : '<button class="js-notif-read shrink-0 text-[11px] font-semibold text-primary hover:underline" data-id="' + n.id + '">' + (am ? 'ተነበበ' : 'Read') + '</button>') +
        '</li>';
      }).join('');
    }
    bell.addEventListener('click', function (e) { e.stopPropagation(); panel.classList.toggle('hidden'); });
    document.addEventListener('click', function (e) { if (!panel.contains(e.target) && e.target !== bell) panel.classList.add('hidden'); });
    list.addEventListener('click', async function (e) {
      var b = e.target.closest('.js-notif-read'); if (!b) return;
      try { await gs.api('/api/admin/inbox/index.php', { method: 'POST', body: JSON.stringify({ id: parseInt(b.dataset.id, 10) }) }); load(); }
      catch (err) { gs.toast(err.message, 'error'); }
    });
    document.addEventListener('gs:lang-change', render);
    load();
  })();
</script>
</body>
</html>
