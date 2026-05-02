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

  // === CSRF + auth helpers (shared) ===
  window.gs = window.gs || {};

  // Lang-aware date formatter. Uses Ethiopian calendar in Amharic mode.
  // style: 'short' | 'long' | 'datetime' (default).
  gs.fmtDate = function (input, style) {
    if (window.EC && typeof EC.fmtDate === 'function') return EC.fmtDate(input, style);
    if (!input) return '—';
    var d = new Date(String(input).replace(' ', 'T'));
    return isNaN(d) ? String(input) : d.toLocaleString();
  };

  gs.ensureCsrf = async function () {
    var t = sessionStorage.getItem('csrf_token');
    if (!t) {
      var r = await fetch('/api/auth/csrf.php');
      var d = await r.json();
      t = d.csrf_token;
      sessionStorage.setItem('csrf_token', t);
    }
    return t;
  };

  gs.api = async function (url, opts) {
    opts = opts || {};
    opts.headers = opts.headers || {};
    if (opts.body && !opts.headers['Content-Type']) opts.headers['Content-Type'] = 'application/json';
    if (['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase()) >= 0) {
      opts.headers['X-CSRF-Token'] = await gs.ensureCsrf();
    }
    var res = await fetch(url, opts);
    var data;
    try { data = await res.json(); } catch (e) { data = {}; }
    if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
    return data;
  };

  gs.toast = function (msg, type) {
    type = type || 'info';
    var bg = { info: '#384700', error: '#ba1a1a', success: '#384700' }[type] || '#384700';
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:'+bg+';color:#fff;padding:14px 20px;border-radius:6px;z-index:9999;box-shadow:0 8px 24px -8px rgba(0,0,0,0.3);font-size:14px;max-width:340px;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity 300ms'; setTimeout(function () { t.remove(); }, 300); }, 3000);
  };

  gs.confirm = function (msg) {
    return Promise.resolve(window.confirm(msg));
  };

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
