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
- Queue is `sync` in `.env` (config default `database`). Exactly ONE custom job
  exists — [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php) — and under
  `sync` it runs INLINE during the admin POI resync request, with no retries. To get
  it running asynchronously with its configured `$tries`/`$backoff`/`$timeout`, set a
  real driver (e.g. `database`) and run `php artisan queue:work`. Confirm the driver
  change with the user first.
- Geoapify env vars ([`config/services.php`](config/services.php) `services.geoapify`):
  `GEOAPIFY_API_KEY` is REQUIRED for POI syncing to work at all — while it is blank
  the job early-returns with a log warning and the public property map falls back to
  OSM tiles. Optional: `GEOAPIFY_MAP_KEY` (browser-exposed tile key; falls back to
  `GEOAPIFY_API_KEY`), `GEOAPIFY_RADIUS` (default 2000), `GEOAPIFY_MAX_RESULTS`
  (default 20). Operator guide: [`docs/geoapify-setup.md`](docs/geoapify-setup.md).
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
- Relying on a queue worker for async execution (queue is `sync`, so
  `FetchNearbyPlacesJob` runs inline in the request).
- Expecting POI sync to work with a blank `GEOAPIFY_API_KEY`.
- Building Git-dashboard commands as shell strings.

# Validation
- App boots; `/sitemap.xml` and public pages render.
- `php artisan test` green after migrations.
- Confirm caches rebuilt (`config:cache`/`route:cache`) and `storage` symlink present.

# Related Files
- [`routes/install.php`](routes/install.php), [`config/installer.php`](config/installer.php), [`resources/views/install`](resources/views/install)
- [`resources/views/admin/settings/partials/_git.blade.php`](resources/views/admin/settings/partials/_git.blade.php)
- [`config/queue.php`](config/queue.php), [`config/session.php`](config/session.php), [`routes/console.php`](routes/console.php)
- [`config/services.php`](config/services.php), [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php), [`docs/geoapify-setup.md`](docs/geoapify-setup.md)
- [`README.md`](README.md)
