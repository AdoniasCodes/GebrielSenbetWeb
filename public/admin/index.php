<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Dashboard';
$page_title_am = 'ዋና ገጽ';
$page_eyebrow    = 'Overview';
$page_eyebrow_am = 'አጠቃላይ እይታ';
$active_nav = 'dashboard';
require __DIR__ . '/_partials/page-shell.php';
?>

<!-- Stat cards -->
<div id="statCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
  <div class="stat-card border-t-primary">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
      <span data-en="Total Students" data-am="ጠቅላላ ተማሪዎች">Total Students</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
    </p>
    <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none" data-stat="total_students">—</p>
    <p class="text-xs text-ink-soft" data-en="Active" data-am="ንቁ">Active</p>
  </div>

  <div class="stat-card border-t-gold-soft">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
      <span data-en="Active Classes" data-am="ንቁ ክፍሎች">Active Classes</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    </p>
    <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none" data-stat="active_classes">—</p>
    <p class="text-xs text-ink-soft" data-en="Across all tracks" data-am="በሁሉም ኮርሶች">Across all tracks</p>
  </div>

  <div class="stat-card" style="border-top-color:#ba1a1a;">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
      <span data-en="Unpaid This Term" data-am="ያልተከፈለ በዚህ ኮርስ">Unpaid This Term</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
    </p>
    <p class="font-display text-5xl text-error mt-3 mb-3 leading-none" data-stat="unpaid_this_term">—</p>
    <p class="text-xs text-error" data-stat-trend="outstanding"><span data-en="No outstanding amount" data-am="ቀሪ ሂሳብ የለም">No outstanding amount</span></p>
  </div>

  <div class="stat-card border-t-olive">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
      <span data-en="Active Teachers" data-am="ንቁ መምህራን">Active Teachers</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M14 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
    </p>
    <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none" data-stat="active_teachers">—</p>
    <p class="text-xs text-ink-soft" data-en="With at least one assignment" data-am="ቢያንስ አንድ ምድብ ያላቸው">With at least one assignment</p>
  </div>
</div>

<!-- Two-column: tables left | rail right -->
<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">

  <div class="space-y-6 min-w-0">

    <!-- Recent Grade Entries -->
    <section class="panel">
      <header class="px-6 py-5 flex items-center justify-between border-b border-outline-soft/40">
        <h2 class="font-display text-lg text-ink" data-en="Recent Grade Entries" data-am="የቅርብ ጊዜ ውጤቶች">Recent Grade Entries</h2>
        <a href="/admin/grades.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="View all" data-am="ሁሉንም ይመልከቱ">View all</a>
      </header>
      <div id="recentGradesWrap" class="table-wrap">
        <p class="px-6 py-12 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
      </div>
    </section>

    <!-- Recent Enrollments -->
    <section class="panel">
      <header class="px-6 py-5 flex items-center justify-between border-b border-outline-soft/40">
        <h2 class="font-display text-lg text-ink" data-en="Recent Enrollments" data-am="የቅርብ ጊዜ ምዝገባዎች">Recent Enrollments</h2>
        <a href="/admin/students.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="Manage" data-am="ያስተዳድሩ">Manage</a>
      </header>
      <ul id="recentEnrollmentsWrap" class="divide-y divide-outline-soft/30">
        <li class="px-6 py-12 text-center text-ink-soft text-sm" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</li>
      </ul>
    </section>
  </div>

  <!-- Right rail -->
  <div class="space-y-6">
    <section class="panel p-6">
      <header class="flex items-center justify-between mb-5">
        <h2 class="font-display text-lg text-ink" data-en="This Week" data-am="በዚህ ሳምንት">This Week</h2>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#795901" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      </header>
      <p class="text-sm text-ink-soft" data-en="No events scheduled. Add events from the Resources menu." data-am="ምንም ዝግጅት የለም። ከመርጃዎች ዝርዝር ይጨምሩ።">No events scheduled. Add events from the Resources menu.</p>
      <a href="/admin/events.php" class="mt-6 block w-full text-center bg-surface-mid border border-outline-soft/50 text-xs font-semibold uppercase tracking-widestest text-ink py-2.5 rounded hover:bg-surface-high transition-colors" data-en="Open Calendar" data-am="የቀን መቁጠሪያ ክፈት">Open Calendar</a>
    </section>

    <section class="quick-actions rounded-lg p-6 relative overflow-hidden">
      <svg class="absolute -right-6 -top-6 opacity-15" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="0.5"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/></svg>
      <h2 class="font-display text-lg mb-4" data-en="Quick Actions" data-am="ፈጣን እርምጃዎች">Quick Actions</h2>
      <div class="grid grid-cols-2 gap-3">
        <a href="/admin/students.php?new=1" class="bg-primary/40 hover:bg-primary/60 transition-colors rounded p-4 flex flex-col items-center gap-2 text-center border border-gold-soft/30">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6M23 11h-6"/></svg>
          <span class="text-xs font-semibold uppercase tracking-widestest" data-en="New Student" data-am="አዲስ ተማሪ">New Student</span>
        </a>
        <a href="/admin/teachers.php?new=1" class="bg-primary/40 hover:bg-primary/60 transition-colors rounded p-4 flex flex-col items-center gap-2 text-center border border-gold-soft/30">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6M23 11h-6"/></svg>
          <span class="text-xs font-semibold uppercase tracking-widestest" data-en="New Teacher" data-am="አዲስ መምህር">New Teacher</span>
        </a>
      </div>
    </section>
  </div>
</div>

<script>
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
  function relativeTime(iso) {
    if (!iso) return '';
    var d = new Date(iso.replace(' ','T') + 'Z');
    if (isNaN(d)) return '';
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    if (diff < 86400*7) return Math.floor(diff/86400) + 'd ago';
    return d.toLocaleDateString();
  }

  (async function () {
    try {
      var d = await gs.api('/api/admin/stats/index.php');
      // Stat cards
      var s = d.stats || {};
      document.querySelectorAll('[data-stat]').forEach(function (el) {
        var k = el.dataset.stat;
        if (s[k] != null) el.textContent = s[k];
      });
      var trend = document.querySelector('[data-stat-trend="outstanding"]');
      if (trend) {
        var amt = (s.outstanding_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        trend.innerHTML = '<span>ETB ' + amt + ' outstanding</span>';
      }

      // Recent grades
      var rg = d.recent_grades || [];
      var wrap = document.getElementById('recentGradesWrap');
      if (rg.length === 0) {
        wrap.innerHTML = '<p class="px-6 py-12 text-center text-ink-soft text-sm">No recent grade entries.</p>';
      } else {
        var rows = rg.map(function (g) {
          return '<tr class="hover:bg-surface-low/50 transition-colors">' +
            '<td>' + escHtml(g.class_name) + ' (' + escHtml(g.level_name) + ')</td>' +
            '<td class="text-ink-soft">' + escHtml((g.teacher_first||'') + ' ' + (g.teacher_last||'')).trim() + '</td>' +
            '<td class="text-ink-soft">' + escHtml(g.subject_name) + '</td>' +
            '<td><span class="font-display text-lg text-primary">' + escHtml(g.score) + '</span></td>' +
            '<td class="text-xs text-outline">' + escHtml(relativeTime(g.created_at)) + '</td>' +
          '</tr>';
        }).join('');
        wrap.innerHTML =
          '<table class="data"><thead><tr>' +
          '<th>Class</th><th>Teacher</th><th>Subject</th><th>Score</th><th>When</th>' +
          '</tr></thead><tbody>' + rows + '</tbody></table>';
      }

      // Recent enrollments
      var re = d.recent_enrollments || [];
      var ul = document.getElementById('recentEnrollmentsWrap');
      if (re.length === 0) {
        ul.innerHTML = '<li class="px-6 py-12 text-center text-ink-soft text-sm">No students enrolled yet.</li>';
      } else {
        ul.innerHTML = re.map(function (st) {
          var name = ((st.first_name||'') + ' ' + (st.last_name||'')).trim() || 'Unnamed';
          var initials = (name[0] || '?').toUpperCase() + ((name.split(' ')[1]||[])[0]||'').toUpperCase();
          var enrolled = st.class_name ? ('Enrolled in ' + st.class_name + (st.level_name?' ('+st.level_name+')':'')) : 'Not yet assigned to a class';
          return '<li class="px-6 py-4 flex items-center justify-between hover:bg-surface-low/50 transition-colors">' +
            '<div class="flex items-center gap-4">' +
              '<div class="w-10 h-10 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-bold text-sm">' + escHtml(initials) + '</div>' +
              '<div><p class="text-ink font-medium">' + escHtml(name) + '</p>' +
              '<p class="text-xs text-ink-soft">' + escHtml(enrolled) + '</p></div>' +
            '</div>' +
            '<a class="text-xs text-gold hover:text-primary uppercase tracking-widestest" href="/admin/student-detail.php?id=' + escHtml(st.id) + '">View</a>' +
          '</li>';
        }).join('');
      }
    } catch (e) {
      gs.toast(e.message, 'error');
    }
  })();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
