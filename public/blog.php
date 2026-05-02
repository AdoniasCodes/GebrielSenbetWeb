<?php
// public/blog.php — public-facing blog page (no auth)

require_once __DIR__ . '/../bootstrap.php';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en" data-lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog · Gebriel Senbet</title>
  <meta name="description" content="Posts and announcements from Saint Gabriel Sabbath School." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          surface:'#fcf9f2','surface-low':'#f6f3ec','surface-mid':'#f0eee7',
          ink:'#1c1c18','ink-soft':'#564242',outline:'#897172','outline-soft':'#dcc0c0',
          primary:'#5b0617','primary-soft':'#7a1f2b',
          gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175',
          olive:'#384700','olive-soft':'#a2b665',
        },
        fontFamily: {
          display: ['Newsreader','"Noto Serif Ethiopic"','serif'],
          body: ['"Plus Jakarta Sans"','"Noto Sans Ethiopic"','system-ui','sans-serif'],
          ethiopic: ['"Noto Sans Ethiopic"','serif'],
        },
        letterSpacing: { widestest: '0.18em' },
      }}
    };
  </script>

  <style>
    .font-display { font-family: 'Newsreader','Noto Serif Ethiopic',serif; }
    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; }
    .seg-active { background:#fed175; color:#5b0617; }
    .rule-gold { height:1px; background: linear-gradient(to right, transparent, #c9a14a 20%, #c9a14a 80%, transparent); }
    .rule-gold-tiny { display:inline-block; width:12px; height:1px; background:#c9a14a; vertical-align:middle; }
    .eyebrow { font-family:'Plus Jakarta Sans',sans-serif; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#795901; display:inline-flex; align-items:center; gap:12px; }
    :where(a, button, input, select):focus-visible { outline:2px solid #c9a14a; outline-offset:2px; border-radius:2px; }
  </style>
</head>
<body class="bg-surface text-ink antialiased" style="font-family: 'Plus Jakarta Sans','Noto Sans Ethiopic',system-ui,sans-serif;">

  <!-- Top nav -->
  <header class="sticky top-0 z-50 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1024px] mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
            <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
            <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
          </svg>
        </span>
        <span class="font-display text-xl font-semibold tracking-tight text-primary leading-none" data-en="Gebriel Senbet" data-am="ገብርኤል ሰንበት">Gebriel Senbet</span>
      </a>
      <nav class="hidden md:flex items-center gap-8 text-[15px] text-ink-soft">
        <a class="hover:text-primary" href="/#about" data-en="About" data-am="ስለ እኛ">About</a>
        <a class="hover:text-primary" href="/#programs" data-en="Programs" data-am="ፕሮግራሞች">Programs</a>
        <a class="hover:text-primary" href="/#calendar" data-en="Calendar" data-am="የቀን መቁጠሪያ">Calendar</a>
        <a class="text-primary font-semibold" href="/blog.php" data-en="Blog" data-am="ብሎግ">Blog</a>
      </nav>
      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>
        <a href="/login.html" class="inline-flex items-center gap-2 bg-primary text-surface px-4 py-2 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
          <span data-en="Sign in" data-am="ግባ">Sign in</span>
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-[1024px] mx-auto px-6 lg:px-8 py-16">
    <div class="text-center mb-14">
      <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Posts" data-am="ጽሑፎች">Posts</span><span class="rule-gold-tiny"></span></p>
      <h1 class="font-display text-4xl lg:text-5xl text-primary mt-4 leading-tight" data-en="From the Sabbath school." data-am="ከሰንበት ት/ቤታችን።">From the Sabbath school.</h1>
      <p class="ethiopic text-lg text-ink-soft mt-4">ዜናዎች፣ ትምህርቶች፣ እና ማኅበረሰባዊ መልዕክቶች።</p>
    </div>
    <div class="rule-gold mb-12 max-w-[120px] mx-auto opacity-60"></div>

    <div id="postsWrap" class="space-y-12">
      <p class="text-center text-ink-soft" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
    </div>
  </main>

  <footer class="bg-surface-mid border-t border-outline-soft/40 mt-16">
    <div class="max-w-[1024px] mx-auto px-6 lg:px-8 py-10 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-outline">
      <p class="uppercase tracking-widestest" data-en="© <?= $year ?> Gebriel Senbet · Made with reverence in Addis Ababa" data-am="© <?= $year ?> ገብርኤል ሰንበት · በአዲስ አበባ በክብር የተሠራ">© <?= $year ?> Gebriel Senbet · Made with reverence in Addis Ababa</p>
      <a href="/" class="hover:text-primary" data-en="← Back to home" data-am="← ወደ መነሻ ተመለስ">← Back to home</a>
    </div>
  </footer>

  <script>
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
      }
      document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
        btn.addEventListener('click', function () { applyLang(btn.dataset.lang); });
      });
      var saved = 'en';
      try { saved = localStorage.getItem('gs_lang') || 'en'; } catch (e) {}
      applyLang(saved);
    })();

    function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
    function fmtDate(s) { return s && window.EC ? EC.fmtDate(s, 'long') : (s || ''); }

    (async function () {
      try {
        var res = await fetch('/api/posts/index.php');
        var data = await res.json();
        var rows = data.data || [];
        var wrap = document.getElementById('postsWrap');
        if (!rows.length) {
          wrap.innerHTML = '<p class="text-center text-ink-soft py-16">No posts yet. Check back soon.</p>';
          return;
        }
        wrap.innerHTML = rows.map(function (p) {
          var paragraphs = (p.content || '').split(/\n{2,}/).map(function (para) {
            return '<p>' + escHtml(para).replace(/\n/g, '<br/>') + '</p>';
          }).join('');
          return '<article class="border-b border-outline-soft/40 pb-10">' +
            '<p class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-3">'+escHtml(fmtDate(p.created_at))+'</p>' +
            '<h2 class="font-display text-3xl text-ink mb-6 leading-tight">'+escHtml(p.title)+'</h2>' +
            '<div class="prose space-y-4 text-ink-soft leading-relaxed">'+paragraphs+'</div>' +
          '</article>';
        }).join('');
      } catch (e) {
        document.getElementById('postsWrap').innerHTML = '<p class="text-center text-error">Could not load posts.</p>';
      }
    })();
  </script>
</body>
</html>
