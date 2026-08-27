---
name: deployment
description: >-
  Use when deploying the app, running production/build commands, using the web
  installer, or managing the admin Git update dashboard. Trigger phrases:
  "deploy", "build assets", "run migrations", "storage link", "route:cache",
  "config:cache", "installer", "git dashboard", "schedule/cron", "queue worker".
  Grounds work in this repo's manual, no-CI deployment reality.
---

# Purpose
Describe how this app is actually deployed: manually, with no CI/CD pipeline,
bootstrapped through a web installer, and updatable via an admin Git dashboard.
This skill stops agents from assuming automation that does not exist or running
destructive commands against real data.

# When to Use
- Preparing or documenting a deploy / update.
- Running build, migration, cache, or storage-link commands.
- Working on the installer flow or the admin Git dashboard.

# Rules
- There is NO CI/CD pipeline (no `.github/`). Deployment is MANUAL.
- The app bootstraps via a web installer:
  [`routes/install.php`](routes/install.php), [`config/installer.php`](config/installer.php),
  and [`resources/views/install/*`](resources/views/install). Installer access is
  restricted by the `protect.installer` middleware (localhost/whitelist/token).
  Do not bypass the installer silently.
- Standard deploy/update commands: `composer install`,
  `npm install && npm run build` (Vite), `php artisan migrate` (ADDITIVE only),
  `php artisan storage:link` (required for the `public` disk),
  `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
- Queue is `sync` (config default `database`); no worker is strictly required
  today and no custom jobs exist. If real async work is added, provision
  `php artisan queue:work` deliberately (confirm the driver first).
- Scheduler runs via cron calling `php artisan schedule:run` (see
  [`routes/console.php`](routes/console.php)).
- The admin Git dashboard ([`resources/views/admin/settings/partials/_git.blade.php`](resources/views/admin/settings/partials/_git.blade.php))
  can pull updates via Symfony Process — commands are ARRAY args, never shell
  strings.
- DESTRUCTIVE commands require explicit user approval: `migrate:fresh`,
  `db:wipe`, `DROP`, `rm -rf`, and force push. Prefer additive migrations.

# Workflow
1. Pull code (or use the admin Git dashboard).
2. `composer install` and `npm install && npm run build`.
3. `php artisan migrate` (additive) and `php artisan storage:link`.
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. In dev after changes: `php artisan optimize:clear`.

# Common Mistakes
- Assuming a CI/CD pipeline or automated deploy exists.
- Running `migrate:fresh`/`db:wipe` against real data without approval.
- Forgetting `storage:link`, breaking the `public` media disk.
- Relying on a queue worker (queue is `sync`, no jobs exist).
- Building Git-dashboard commands as shell strings.

# Validation
- App boots; `/sitemap.xml` and public pages render.
- `php artisan test` green after migrations.
- Confirm caches rebuilt (`config:cache`/`route:cache`) and `storage` symlink present.

# Related Files
- [`routes/install.php`](routes/install.php), [`config/installer.php`](config/installer.php), [`resources/views/install`](resources/views/install)
- [`resources/views/admin/settings/partials/_git.blade.php`](resources/views/admin/settings/partials/_git.blade.php)
- [`config/queue.php`](config/queue.php), [`config/session.php`](config/session.php), [`routes/console.php`](routes/console.php)
- [`README.md`](README.md)
