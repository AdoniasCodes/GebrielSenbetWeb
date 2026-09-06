// public/assets/js/ec-date.js
// Ethiopian Calendar (Amete Mihret) display helpers.
// Storage stays Gregorian; this module converts for display when lang === 'am'.
//
// Algorithm: convert via Julian Day Number using the standard Beyene–Kudlek
// method. Verified: 2026-05-02 Gregorian → Miyazya 24, 2018 EC.

(function (global) {
  var EPOCH = 1724221; // JDN of Mäskäräm 1, year 1 EC

  var MONTHS_AM = [
    'መስከረም','ጥቅምት','ኅዳር','ታኅሳስ','ጥር','የካቲት',
    'መጋቢት','ሚያዝያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'
  ];
  var MONTHS_EN = [
    'Mäskäräm','Tiqimt','Hidar','Tahsas','Tirr','Yäkatit',
    'Mägabit','Miyazya','Ginbot','Säne','Hamle','Nähase','Pagumē'
  ];
  var WEEKDAYS_AM = ['እሁድ','ሰኞ','ማክሰኞ','ረቡዕ','ሐሙስ','ዓርብ','ቅዳሜ'];
  var WEEKDAYS_EN = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }

  function gregToJDN(gy, gm, gd) {
    var a = Math.floor((14 - gm) / 12);
    var y = gy + 4800 - a;
    var m = gm + 12 * a - 3;
    return gd + Math.floor((153 * m + 2) / 5)
      + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400)
      - 32045;
  }

  function jdnToEthiopian(jdn) {
    var diff = jdn - EPOCH;
    // Guard against pre-epoch dates (shouldn't happen for school events)
    if (diff < 0) return null;
    var r = diff % 1461;
    var n = (r % 365) + 365 * Math.floor(r / 1460);
    var year = 4 * Math.floor(diff / 1461) + Math.floor(r / 365) - Math.floor(r / 1460) + 1;
    var month = Math.floor(n / 30) + 1;
    var day = (n % 30) + 1;
    return { year: year, month: month, day: day };
  }

  function toDate(input) {
    if (input == null || input === '') return null;
    if (input instanceof Date) return isNaN(input) ? null : input;
    var s = String(input).replace(' ', 'T');
    var d = new Date(s);
    return isNaN(d) ? null : d;
  }

  function gregorianToEC(input) {
    var d = toDate(input);
    if (!d) return null;
    var jdn = gregToJDN(d.getFullYear(), d.getMonth() + 1, d.getDate());
    var ec = jdnToEthiopian(jdn);
    if (!ec) return null;
    var weekdayIdx = d.getDay();
    return {
      year: ec.year,
      month: ec.month,
      day: ec.day,
      monthName_am: MONTHS_AM[ec.month - 1],
      monthName_en: MONTHS_EN[ec.month - 1],
      weekday_am: WEEKDAYS_AM[weekdayIdx],
      weekday_en: WEEKDAYS_EN[weekdayIdx],
      hours: d.getHours(),
      minutes: d.getMinutes(),
    };
  }

  // Ethiopian clock. The day is counted from dawn, not from midnight: 6:00 AM
  // is 12:00, 7:00 AM is 1:00, noon is 6:00, 6:00 PM is 12:00 again. So the
  // hour is the Western hour minus six, wrapped into 1..12.
  //
  // Which half of the cycle you are in is carried by the day-part word rather
  // than by AM/PM, and Amharic names four of them:
  //   ጠዋት   morning    06:00-11:59  (ET 12:00-5:59)
  //   ከሰዓት  afternoon  12:00-17:59  (ET  6:00-11:59)
  //   ማታ    evening    18:00-23:59  (ET 12:00-5:59)
  //   ሌሊት   late night 00:00-05:59  (ET  6:00-11:59)
  function toEthiopianHour(h) {
    var e = ((h - 6) % 12 + 12) % 12;
    return e === 0 ? 12 : e;
  }
  function ethiopianDayPart(h) {
    if (h >= 6 && h < 12) return 'ጠዋት';
    if (h >= 12 && h < 18) return 'ከሰዓት';
    if (h >= 18) return 'ማታ';
    return 'ሌሊት';
  }
  function fmt12hAm(h, m) {
    // Ethiopic wordspace is the conventional separator in written Amharic times.
    return toEthiopianHour(h) + '\u1361' + pad2(m) + ' ' + ethiopianDayPart(h);
  }
  function fmt12hEn(h, m) {
    var ap = h >= 12 ? 'PM' : 'AM';
    var hh = h % 12; if (hh === 0) hh = 12;
    return hh + ':' + pad2(m) + ' ' + ap;
  }

  // style: 'short' | 'long' | 'datetime'
  function formatEC(input, opts) {
    var ec = gregorianToEC(input);
    if (!ec) return '';
    var style = (opts && opts.style) || 'long';
    if (style === 'short') {
      // e.g. 24/08/2018 ዓ.ም
      return pad2(ec.day) + '/' + pad2(ec.month) + '/' + ec.year + ' ዓ.ም';
    }
    if (style === 'datetime') {
      return ec.monthName_am + ' ' + ec.day + ', ' + ec.year + ' · ' + fmt12hAm(ec.hours, ec.minutes);
    }
    // long (default)
    return ec.monthName_am + ' ' + ec.day + ', ' + ec.year;
  }

  // Gregorian formatter mirroring the EC style for symmetry.
  function formatGregorian(input, opts) {
    var d = toDate(input);
    if (!d) return '';
    var style = (opts && opts.style) || 'long';
    if (style === 'short') {
      return pad2(d.getDate()) + '/' + pad2(d.getMonth() + 1) + '/' + d.getFullYear();
    }
    if (style === 'datetime') {
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        + ' · ' + fmt12hEn(d.getHours(), d.getMinutes());
    }
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  }

  // Lang-aware façade. Reads document.documentElement.dataset.lang.
  // style defaults to 'datetime' (date + time) since that's what most lists want.
  function fmtDate(input, style) {
    var lang = (document.documentElement && document.documentElement.getAttribute('data-lang')) || 'en';
    var s = style || 'datetime';
    return lang === 'am' ? formatEC(input, { style: s }) : formatGregorian(input, { style: s });
  }

  // Re-render any element with [data-iso] when language changes.
  // Use: <span data-iso="2026-05-02 09:00:00" data-fmt-style="long"></span>
  function rerenderIsoNodes() {
    document.querySelectorAll('[data-iso]').forEach(function (el) {
      var iso = el.getAttribute('data-iso');
      var st = el.getAttribute('data-fmt-style') || 'datetime';
      el.textContent = fmtDate(iso, st);
    });
  }

  // Hook into the existing language toggle so dates flip live.
  function installLangHook() {
    document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        // Toggle handler runs first; we re-render on next tick.
        setTimeout(rerenderIsoNodes, 0);
      });
    });
  }

  global.EC = {
    gregorianToEC: gregorianToEC,
    formatEC: formatEC,
    formatGregorian: formatGregorian,
    fmtDate: fmtDate,
    toEthiopianHour: toEthiopianHour,
    ethiopianDayPart: ethiopianDayPart,
    fmtTimeAm: fmt12hAm,
    fmtTimeEn: fmt12hEn,
    rerenderIsoNodes: rerenderIsoNodes,
    installLangHook: installLangHook,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      rerenderIsoNodes();
      installLangHook();
    });
  } else {
    rerenderIsoNodes();
    installLangHook();
  }
})(window);
