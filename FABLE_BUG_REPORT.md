# FABLE_BUG_REPORT.md — security & correctness audit

**Method:** 5 parallel dimension reviewers (auth/CSRF · admin SQL · domain engines + migrate
runner · public-page XSS · scoped/setup IDOR), each candidate finding handed to an **independent
adversarial verifier** instructed to refute it. **11 raw findings → 11 confirmed, 0 refuted.**
Run: 2026-07-03, Fable 5 multi-agent workflow (16 agents, ~632k tokens).

Severity legend: **Critical** = remote/unauth compromise or guaranteed prod breakage · **High** =
cross-user compromise or serious integrity/availability · **Medium** = needs a condition/insider ·
**Low** = hardening / info-leak.

---

## APPLIED in this session (9 of 11) — committed, verified

| # | Sev | File | Fix |
|---|-----|------|-----|
| 3 | High | `public/student/grades.php:66` | **Stored/DOM XSS** — teacher-controlled `remarks`/`subject_name` were `innerHTML`-injected unescaped; a malicious remark ran JS in the student's session. Added `escHtml()` + wrapped every interpolated field. |
| 4 | High | `public/teacher/grades.php:115` | **DOM XSS** — student names + remarks injected into `innerHTML` and `value="..."` attributes unescaped (attribute-breakout). Added `escHtml()` (encodes `"`), escaped all fields. |
| 2 | High | `api/admin/deploy/migrate.php:37` | **Migration runner wedge** — migrations 011–017 had no artifact probe, so a recovery/partial deploy (empty tracker + schema present) re-runs them; 011's non-idempotent DDL (`ADD COLUMN person_id`) hard-errors and the loop `break`s → deploy can never converge. Added probes 011–017; **verified all 7 return TRUE** against the migrated DB. |
| 7 | Med | `api/admin/deploy/migrate.php:73` | Prune self-heal couldn't drop wrongly-applied 011–017 rows (same missing-probe root). Fixed by the same probes. |
| 9 | Med | `api/teacher/grades/index.php:68` | **Integrity/IDOR** — teacher could POST a grade for *any* student id into a class they teach (only the FK constrained it). Added an enrollment check (`student_class_assignments`) → 422 if not enrolled. Tested: non-enrolled student now rejected. |
| 8 | Med | `api/setup/create_admin.php:37` | **No first-run lockout** — endpoint minted unlimited admins forever if the setup token leaked. Added: once any active admin exists → 403 "Setup already completed". |
| 6 | Med | `bootstrap.php:34` + `src/Utils/Csrf.php:12` | Session cookie lacked **Secure** on an HTTPS site (session hijack over plain-HTTP). Added `cookie_secure` gated on `$_SERVER['HTTPS']` (local HTTP dev unaffected). |
| 10 | Low | `api/auth/login.php:39` | **Login timing side-channel** leaked which emails exist (bcrypt only ran when the row existed). Now always runs `password_verify` against a dummy hash → constant work. |
| 11 | Low | `public/test/index.php` | Unauth deploy-marker page in the doc root leaked server time/build string. **Deleted.** |

All 9 lint-clean; the teacher-portal regression suite still passes 20/20 after the changes.

---

## PROPOSED — needs your decision (2 of 11), NOT yet applied

Findings **#1 & #5 are the same root issue** and are the single most serious result, but the fix
changes how your production demo works — so I'm leaving it to you.

### 🔴 demo_logins.php seeds a permanent admin login with a public password
`api/setup/demo_logins.php:22,42` — the endpoint upserts an **admin-role** account
`demo@mekaneselamss.com` with the password **`demo1234`**, which is committed in the repo
(`DEMO_LOGINS.md`, `demo_seed.php`, and the endpoint itself). Every run also **resets the password
and un-archives** the row. Login needs no token/CSRF. So once this endpoint is run on prod (its
whole purpose), **anyone can log in as a full admin with a known password** — a complete admin
backdoor. The verifier confirmed the end-to-end path and could only *not* confirm the live DB's
current state.

**Two things need to happen — one operational, one code:**
1. **Operational (do regardless):** on the live host, check whether `demo@mekaneselamss.com` exists
   and is active; if so, archive it or rotate its password. I can't see prod DB state from here.
2. **Code hardening — your call on the tradeoff** (see the question I'll ask): the demo endpoint is
   genuinely useful for showing off the 5 role portals, but shipping a world-known admin login is
   the risk. Options range from "drop only the admin demo account" to "randomize its password each
   run" to "gate the whole endpoint to non-production."

I have **not** touched `demo_logins.php`'s account list pending your choice, because narrowing it
would change the demo you rely on.

---

## Refuted / false positives
None — all 11 candidates survived adversarial verification. (The verifiers did downgrade-check each;
none warranted downgrade below the reviewer's severity.)
