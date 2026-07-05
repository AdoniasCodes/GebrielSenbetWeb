# GebrielSenbetWeb — project rules

- Single church (Gebriel); Mariam is a serving location only. Departments are self-running sub-orgs. Domain model memory `project_domain_model` is authoritative.
- Prod host: mekaneselamss.com (cPanel user `mekanefh`). Deploys are MANUAL: push to GitHub, then Eyoel clicks cPanel → Git Version Control → Update from Remote + Deploy HEAD Commit. After deploy with new migrations, hit the migrate endpoint (see instructions.md / memory).
- Never run the admin Reset tool (`load_demo` / `wipe_clean`) against prod without explicit approval — it wipes operational data.
- Demo/test accounts: recreate with `scripts/seed_demo_users.php` (non-destructive) rather than the Reset tool when only logins need fixing.
- Bilingual EN/አማርኛ labels on all user-facing pages; design tokens per `project_design_system` (primary #16357e).
- Vanilla PHP + vanilla JS + fetch; no frameworks. Public endpoints in `public/api/` are thin delegates into repo-root `api/`.
