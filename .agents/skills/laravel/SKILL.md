---
name: laravel
description: >-
  Use when working on any Laravel backend task in this repo — adding or editing
  controllers, routes, services, FormRequests, middleware, config, or Blade
  views. Trigger phrases: "add a controller", "new route", "admin page",
  "validate input", "create a service", "wire up middleware", "config value",
  "where does the business logic go". Establishes the framework version,
  directory layout, and the Controller-Service pattern this project enforces.
---

# Purpose
This project is a Laravel `^13.8` / PHP `^8.3` apartment rental CMS ("Lya Rooms")
using Laravel Breeze session auth, a custom Blade admin (no Filament/Nova), and a
strict Controller-Service architecture. This skill keeps changes aligned with the
existing structure so agents do not invent parallel patterns.

# When to Use
- Adding or editing a controller, route, service, or FormRequest.
- Building an admin screen under `/admin`.
- Adding validation, authorization, or config values.
- Deciding where a piece of logic belongs (controller vs service vs view).

# Rules
- Framework is fixed: Laravel `^13.8`, PHP `^8.3`, Breeze session auth. Do not add
  Livewire, Vue, React, Inertia, or any admin package.
- Controllers handle HTTP only (validation dispatch, auth, responses). Business
  logic lives in `app/Services/*`. Do not inline pricing/booking/voucher math in a
  controller or view.
- Validate all admin/user input with a FormRequest class in `app/Http/Requests/`.
  Do not validate ad-hoc with `$request->validate()` for admin input.
- There is NO `app/Policies/` directory. Authorize with the `admin` role middleware
  (`['auth','admin']`) or explicit checks in the controller.
- Admin routes use the `/admin` prefix and `['auth','admin']` middleware group. The
  `admin` middleware accepts `super-admin` and `admin` role slugs.
- Bootstrapping is Laravel 11+ style: middleware/aliases are wired in
  [`bootstrap/app.php`](bootstrap/app.php) and providers in
  [`bootstrap/providers.php`](bootstrap/providers.php). There is no HTTP Kernel class.
- UI strings go through `__('...')` with keys in [`lang/en.json`](lang/en.json) and
  [`lang/id.json`](lang/id.json). Primary locale is `id`. Do not hardcode UI copy.
- Config reads secrets via `env()` inside `config/*` files only; never call `env()`
  outside config. Third-party keys belong in [`config/services.php`](config/services.php)
  (e.g. the `services.geoapify` block: `key`, `map_key`, `radius`, `max_results`).
- `app/Jobs/` is a real directory containing exactly ONE custom job:
  [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php). There are still NO
  events, listeners, observers, or action classes. `.env` sets `QUEUE_CONNECTION=sync`,
  so that job currently runs inline on the dispatching request.
- [`GeoapifyService`](app/Services/GeoapifyService.php) is the reference example of the
  Controller→Service split for an external integration: the controller validates and
  dispatches, the JOB is the async entry point, and the service is the only class that
  performs HTTP. Never call an external API from a controller, view, or render path.

# Workflow
1. Identify the layer: is this HTTP wiring (controller/route), business logic
   (service), validation (FormRequest), or presentation (Blade)?
2. Read the nearest existing example of that layer before writing (e.g. an existing
   admin controller + its FormRequest).
3. For a new admin resource: add route to the `admin` group, create/extend a
   controller, add a FormRequest, reuse `index.blade.php` + `_form.blade.php` view
   conventions.
4. Put any calculation or multi-step domain operation in a service under
   `app/Services/`; call it from the controller.
5. Add translation keys to both `lang/en.json` and `lang/id.json`.
6. Run the relevant feature test (`php artisan test`) before finishing.

# Common Mistakes
- Writing business logic in a controller or Blade view instead of a service.
- Creating a Policy class — there is no `Policies/` directory here; use middleware.
- Validating admin input inline instead of via a FormRequest.
- Hardcoding Indonesian/English UI strings instead of using `__()` keys.
- Adding a frontend framework or admin package — the stack is fixed.
- Assuming events/listeners/observers/actions exist — they do not.
- Assuming `FetchNearbyPlacesJob` runs in the background — with `sync` it runs inline.

# Validation
- `php artisan test` (or `php artisan test --filter=<TestName>` for a focused run).
- Re-read [`bootstrap/app.php`](bootstrap/app.php) to confirm middleware aliases.
- Confirm new admin routes sit inside the `['auth','admin']` group in
  [`routes/web.php`](routes/web.php).
- Verify new UI strings exist in both `lang/en.json` and `lang/id.json`.

# Related Files
- [`routes/web.php`](routes/web.php), [`routes/auth.php`](routes/auth.php), [`routes/install.php`](routes/install.php), [`routes/console.php`](routes/console.php)
- [`bootstrap/app.php`](bootstrap/app.php), [`bootstrap/providers.php`](bootstrap/providers.php)
- [`app/Http/Controllers/`](app/Http/Controllers), [`app/Http/Controllers/Admin/`](app/Http/Controllers/Admin)
- [`app/Http/Requests/`](app/Http/Requests), [`app/Http/Middleware/EnsureUserIsAdmin.php`](app/Http/Middleware/EnsureUserIsAdmin.php)
- [`app/Services/`](app/Services), [`app/Jobs/`](app/Jobs), [`config/services.php`](config/services.php)
- [`lang/en.json`](lang/en.json), [`lang/id.json`](lang/id.json)
