---
name: cms
description: >-
  Use when working on the admin CMS: admin resource screens (properties,
  amenities, bookings, media, pages, blocks, navigation, blog posts, categories,
  tags, vouchers, promo rates, users, redirects, languages, currency, settings),
  admin routing, FormRequest validation, or activity logging. Trigger phrases:
  "add an admin page", "admin CRUD", "admin list/index", "create/edit form",
  "admin route", "admin middleware", "log admin activity". Grounds work in the
  hand-built Blade admin — NOT Filament/Nova/Voyager.
---

# Purpose
The admin is a custom, hand-built Blade CMS — there is no admin package
(Filament/Nova/Voyager) and no Livewire/Vue/React/Inertia. This skill keeps
agents inside the existing admin conventions instead of inventing a parallel
admin structure or reaching for a package.

# When to Use
- Adding or editing an admin resource screen or its controller/routes.
- Adding validation to admin input (FormRequest).
- Wiring activity logging for an admin action.
- Any change under the `/admin` prefix.

# Rules
- Admin routes live in one group in [`routes/web.php`](routes/web.php):
  `Route::middleware(['auth','verified','admin'])->prefix(slug('admin_prefix','admin'))->name('admin.')`.
  The `admin` middleware is [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php)
  and accepts the `super-admin` and `admin` role slugs. The admin path prefix is
  configurable (the `admin_prefix` setting via `slug()`) — never hardcode `/admin`.
- Admin controllers are split: a few live in
  [`app/Http/Controllers/Admin/`](app/Http/Controllers/Admin) (`DashboardController`,
  `UserController`, `LanguageController`, `CurrencyRateController`, `BackupController`,
  `SlugSettingsController`), but most resource controllers are top-level in
  [`app/Http/Controllers/`](app/Http/Controllers) (e.g. `PropertyController`,
  `MediaController`, `PageController`, `PostController`) and serve BOTH admin and
  public actions. Read [`routes/web.php`](routes/web.php) to see which action is
  admin vs public before editing — do not assume a controller is admin-only.
- Confirmed admin resources (from [`routes/web.php`](routes/web.php)): media, pages,
  blocks, navigations, settings, properties, amenities, bookings (index/show/destroy
  only), users, redirects, posts, categories, tags, vouchers, languages,
  currency-rates, backup, slug-settings, plus property-nested promo rates.
- View convention: each resource has `index.blade.php` (listing table) and a shared
  `_form.blade.php` partial reused by `create.blade.php` and `edit.blade.php`.
  Property admin additionally uses `_photos`, `_pricing`, `_policy` partials.
- Admin pages extend [`resources/views/layouts/admin.blade.php`](resources/views/layouts/admin.blade.php).
- Validate all admin input via a FormRequest in
  [`app/Http/Requests/`](app/Http/Requests) — existing ones: `PropertyRequest`,
  `AmenityRequest`, `BlockRequest`, `BookingRequest`, `MediaRequest`,
  `NavigationRequest`, `PageRequest`. Do not validate ad-hoc inside controllers.
- Authorize via the `admin` middleware (there is NO `Policies/` directory). Scope
  queries to prevent IDOR; never trust user-supplied IDs blindly.
- Log admin actions with the `log_activity($action, $description)` helper
  ([`app/Helpers/activity.php`](app/Helpers/activity.php)), backed by the
  `ActivityLog` model / `user_activity_logs` table. It guards null users and never
  throws — do not replace it with raw inserts.
- Business logic belongs in services, not controllers or Blade (Controller-Service
  pattern). Custom (non-resource) routes must be declared BEFORE their
  `Route::resource(...)` so words like `export`/`upload`/`from-url` are not matched
  as `{id}` wildcards (see the existing NOTE comments in `routes/web.php`).

# Workflow
1. Read [`routes/web.php`](routes/web.php) to locate the resource's routes and
   confirm which controller/actions are admin vs public.
2. Follow the `index` + `_form` (+ `create`/`edit`) view convention already used by
   sibling resources; reuse existing Blade components.
3. Add/extend a FormRequest for input; keep business logic in a service.
4. Call `log_activity()` for meaningful admin mutations if siblings do.
5. Register any custom route BEFORE the matching `Route::resource`.

# Common Mistakes
- Introducing Filament/Nova/Livewire or a new admin scaffold.
- Assuming every resource controller is under `Admin/` (many are top-level).
- Hardcoding `/admin` instead of the configurable `admin_prefix` slug.
- Validating inline in a controller instead of a FormRequest.
- Declaring a custom admin route after `Route::resource` (wildcard shadowing).

# Validation
- `php artisan route:list` shows the new route under the `admin.` name + prefix.
- `php artisan test --filter=CrudTest` (and `DashboardTest`) still pass.
- Confirm the FormRequest is applied and `log_activity()` is called where siblings do.

# Related Files
- [`routes/web.php`](routes/web.php), [`app/Http/Middleware/EnsureUserIsAdmin.php`](app/Http/Middleware/EnsureUserIsAdmin.php)
- [`resources/views/layouts/admin.blade.php`](resources/views/layouts/admin.blade.php)
- [`app/Http/Controllers/Admin/`](app/Http/Controllers/Admin), [`app/Http/Controllers/`](app/Http/Controllers)
- [`app/Http/Requests/`](app/Http/Requests), [`app/Helpers/activity.php`](app/Helpers/activity.php), [`app/Models/ActivityLog.php`](app/Models/ActivityLog.php)
- [`tests/Feature/CrudTest.php`](tests/Feature/CrudTest.php), [`tests/Feature/DashboardTest.php`](tests/Feature/DashboardTest.php)
