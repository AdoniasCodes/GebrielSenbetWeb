<?php
// public/blog.php — public-facing blog page (no auth)

require_once __DIR__ . '/../bootstrap.php';
$year = date('Y');

$social    = app_config()['social'] ?? [];
$ytUrl     = $social['youtube_url'] ?? '';
$tiktokUrl = $social['tiktok_url']  ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog · Mekane Selam Senbet School</title>
  <meta name="description" content="Posts and announcements from Mekane Selam Senbet School." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
  <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png">
  <link rel="apple-touch-icon" href="/images/logo-mekane-selam-192.png">

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          surface:'#f4f7fc','surface-low':'#eef2fa','surface-mid':'#e5ecf7',
          ink:'#141824','ink-soft':'#3f4658',outline:'#6b7690','outline-soft':'#c4d0e4',
          primary:'#16357e','primary-soft':'#2f52a6',
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
    .seg-active { background:#fed175; color:#16357e; }
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
        <img src="/images/logo-mekane-selam.webp" alt="Mekane Selam Sunday School" class="h-8 w-8 rounded-full object-cover">
        <span class="font-display text-xl font-semibold tracking-tight text-primary leading-none" data-en="Mekane Selam Senbet School" data-am="መካነ ሰላም ሰንበት ት/ቤት">Mekane Selam Senbet School</span>
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
      <p class="uppercase tracking-widestest" data-en="© <?= $year ?> Mekane Selam Senbet School · Made with reverence in Addis Ababa" data-am="© <?= $year ?> መካነ ሰላም ሰንበት ት/ቤት · በአዲስ አበባ በክብር የተሠራ">© <?= $year ?> Mekane Selam Senbet School · Made with reverence in Addis Ababa</p>
      <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars($ytUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" aria-label="Mekane Selam on YouTube" title="YouTube" class="grid place-items-center w-9 h-9 rounded-full border border-outline-soft/60 hover:text-primary hover:border-primary transition-colors">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
        </a>
        <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" aria-label="Mekane Selam on TikTok" title="TikTok" class="grid place-items-center w-9 h-9 rounded-full border border-outline-soft/60 hover:text-primary hover:border-primary transition-colors">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
        </a>
        <a href="/" class="hover:text-primary ml-2" data-en="← Back to home" data-am="← ወደ መነሻ ተመለስ">← Back to home</a>
      </div>
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
