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
</script>
</body>
</html>
