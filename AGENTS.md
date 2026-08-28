# AGENTS.md — Sewa Apartemen CMS ("Lya Rooms")

Master rule file for AI coding agents working on this repository. This file is the entry point and constitution for all agent work. Read it fully before making any change. Ground truth lives in the codebase; this file only summarizes verified facts and rules.

Every rule here is grounded in the repository audit at [`docs/_agent-audit/AUDIT-FINDINGS.md`](docs/_agent-audit/AUDIT-FINDINGS.md) (the read-only source of truth) and in the actual code. If this file conflicts with real code, the code wins — report the discrepancy instead of guessing.

---

## 1. Project Overview

- **Name**: Sewa Apartemen CMS (marketing/`APP_NAME` is "Lya Rooms")
- **Type**: Apartment Rental CMS — multi-language property rental website with CMS, blog, and booking system
- **Stack**:
  - Laravel `^13.8`, PHP `^8.3` ([`composer.json`](composer.json))
  - Blade + Alpine.js 3 + Hotwired Turbo (Turbo Drive) + Tailwind CSS v3, built with Vite 8 ([`resources/js/app.js`](resources/js/app.js))
  - MySQL for production, SQLite in-memory for tests ([`.env`](.env), [`phpunit.xml`](phpunit.xml))
  - Laravel Breeze session-based auth with a custom `admin` role middleware (supports `super-admin` and `admin` slugs)
  - **Custom Blade admin** — no Livewire, Vue, React, Inertia, or admin packages
- **Purpose**: Multi-language property rental website with CMS pages/blocks/navigation, blog, and a booking system
- **Audience for this file**: any AI coding agent working on the repo (vendor-neutral)

---

## 2. Core Principles

1. **Inspect Before Modify** — Read the relevant files, migrations, models, and tests before editing. Never assume how a feature works.
2. **Never Guess Existing Architecture** — If you are unsure how something is structured, search the codebase or ask. Do not invent a parallel structure.
3. **Minimal Change** — Make the smallest change that satisfies the requirement. Do not refactor unrelated code.
4. **Preserve Existing Business Logic** — Pricing and booking calculations are canonical (see [Backend Rules](#7-backend-rules)). Do not reimplement them.
5. **Don't Fix What Isn't Broken** — If code is working and has no defect or test failure, leave it alone. Cosmetic "improvements" that change behavior are out of scope unless requested.
6. **No Fake Implementation** — Never leave stubs, TODOs that pretend to work, mock data masquerading as real, or features that "appear" implemented but aren't. If something is not implemented, say so.
7. **No Secrets** — Never commit, print, or log credentials, keys, or `.env` values.
8. **No Unnecessary Dependencies** — Do not add packages, assets, or files unless the task genuinely requires them.

---

## 3. Architecture Map

### Routing
- [`routes/web.php`](routes/web.php) — main web routes (frontend + admin)
- [`routes/auth.php`](routes/auth.php) — Breeze auth routes
- [`routes/install.php`](routes/install.php) — installer routes
- [`routes/console.php`](routes/console.php) — console/scheduled commands

### Layouts & Frontend Entry Points
- [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php) — public pages layout
- [`resources/views/layouts/admin.blade.php`](resources/views/layouts/admin.blade.php) — admin pages layout
- [`resources/views/layouts/app.blade.php`](resources/views/layouts/app.blade.php), [`resources/views/layouts/guest.blade.php`](resources/views/layouts/guest.blade.php) — auth-scaffold layouts
- Shared Blade components in [`resources/views/components/*`](resources/views/components) (e.g. [`seo.blade.php`](resources/views/components/seo.blade.php))

### Services (business logic lives here)
- [`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php) — canonical pricing calculator
- [`app/Services/BookingService.php`](app/Services/BookingService.php) — canonical booking creator
- [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php) — sanitized contact map embeds (never raw iframes)
- [`app/Services/GeoapifyService.php`](app/Services/GeoapifyService.php) — Geoapify Places API client; normalizes POIs and maps provider categories to `Property::NEARBY_CATEGORIES` labels. Called ONLY from `FetchNearbyPlacesJob` (see [Performance Rules](#14-performance-rules))

### Jobs
- [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php) — the sole caller of `GeoapifyService`; upserts `places` + `property_places`, 24h-cached per property, dispatched only by the admin POI resync action

### Key Providers
- [`bootstrap/providers.php`](bootstrap/providers.php)
- [`app/Providers/MailSettingsServiceProvider.php`](app/Providers/MailSettingsServiceProvider.php) — runtime mail overrides

### Controllers
- Frontend: [`app/Http/Controllers/`](app/Http/Controllers) (e.g. `PropertyController`, `SearchController`, `PageController`, `PostController`)
- Admin: [`app/Http/Controllers/Admin/`](app/Http/Controllers/Admin) — see [CMS Rules](#9-cms-rules) for the full list

---

## 4. Domain Model

### Confirmed Models (23)
All live in [`app/Models/`](app/Models):

`Property`, `PropertyPhoto`, `Amenity`, `Booking`, `Voucher`, `PromoRate`, `Media`, `Page`, `Block`, `Navigation`, `Post`, `Category`, `Tag`, `User`, `Role`, `SeoMetadata`, `Redirect`, `Setting`, `Language`, `CurrencyRate`, `ActivityLog`, `Place`, `PropertyPlace`.

Key relationships/fields to know:
- `Property` stores prices as a JSON `prices` column and nearby places as JSON `nearby_places` (see [Pricing Rules](#11-pricing-rules))
- `PropertyPhoto` has a `property_id` FK to `Property`
- `Booking` has `status`, `access_token` (guest lookup), `voucher_id` (added later), `notes`
- `SeoMetadata` is polymorphic via a `seoable` morph relationship
- `Media` uses the `public` disk (symlinked to `storage/app/public`)
- `Voucher` and `PromoRate` back the pricing/discount logic
- `Role` uses the `model_has_roles` pivot (Spatie-style, custom role middleware)
- `ActivityLog` backs user activity logging (`user_activity_logs` table)
- `Place` ([`app/Models/Place.php`](app/Models/Place.php)) is a persisted Geoapify POI on the `places` table — deduped on the nullable-unique `geoapify_place_id`, with `name`, `category` (a `Property::NEARBY_CATEGORIES` display label), `lat`/`lng` (cast to float), `address`, `website`, `phone`, `raw_category`, and `fetched_at`. Migration: [`2026_08_28_000001_create_places_table.php`](database/migrations/2026_08_28_000001_create_places_table.php)
- `PropertyPlace` ([`app/Models/PropertyPlace.php`](app/Models/PropertyPlace.php)) is the `property_places` pivot: `property_id` + `place_id` (both FK cascade, unique composite), a `source` enum `manual|geoapify` (default `geoapify`), `distance_m`, and `sort_order`. `getDistanceFormattedAttribute()` renders `"850m"` / `"1.2km"` / `null`. Migration: [`2026_08_28_000002_create_property_places_table.php`](database/migrations/2026_08_28_000002_create_property_places_table.php)
- `Property` exposes `propertyPlaces()` (hasMany) and `places()` (hasManyThrough). Because `Property` uses `SoftDeletes`, the `property_places` FK cascade only fires on `forceDelete()`

### Notable Absences (do NOT assume these exist)
- **No `Room` / `Unit` model** — Units were refactored out into property types (see [`2026_08_12_000000_refactor_units_to_property_types.php`](database/migrations/2026_08_12_000000_refactor_units_to_property_types.php)). Dead `Unit`/`UnitFactory` references may linger — ignore them.
- **No `Availability` table** — there is no server-side availability/conflict-checking system.
- **No `Payment` model** — no payment system.
- **`Place` / `PropertyPlace` DO exist** — they back the implemented Geoapify POI pipeline (`places` + `property_places`). Nearby places may ALSO be entered manually as JSON on `properties.nearby_places`, which remains the fallback on the property page. There is no `NearbyPlace` model and no `NearbyPlacesService` — the service is [`GeoapifyService`](app/Services/GeoapifyService.php).
- No `Policies/` directory — use `authorize()` in controllers or admin middleware.
- **One custom queued job exists** — [`app/Jobs/FetchNearbyPlacesJob.php`](app/Jobs/FetchNearbyPlacesJob.php). There are still no events/listeners/observers/actions.

---

## 5. Coding Conventions

- **PSR-12** for all PHP.
- **Laravel conventions**: snake_case tables, camelCase methods, PascalCase classes, singular model names.
- **Migrations**: explicit foreign keys with `->constrained()` / `->foreign()`; indexes declared intentionally. Do not rely on implicit naming.
- **`$fillable` on models, never `$guarded`**.
- **JSON columns** for `Property->prices` and `Property->nearby_places`. Cast them in the model.
- **No business logic in Blade views** — views render; services compute.
- **No heavy logic in controllers** — controllers handle HTTP; services own business logic (Controller-Service pattern).
- **Translation keys** in [`lang/en.json`](lang/en.json) and [`lang/id.json`](lang/id.json). Use `__('...')` with these keys; do not hardcode UI strings in Indonesian/English that belong in lang files.
- Money values: use the existing `money-input` component / decimal storage conventions. Never float for money if avoidable.

---

## 6. Database Rules

- **Migration first** — schema changes go through migrations in `database/migrations/`. Never edit schema directly.
- **Foreign keys explicit** — always declare FK constraints in migrations.
- **Indexes intentional** — add indexes for columns used in `WHERE`/`JOIN`/`ORDER BY` (e.g. `properties.slug`, `posts.slug`, `pages.slug`, booking lookups). Do not index blindly.
- **Avoid destructive migrations** — prefer additive changes. Do not drop or rewrite tables without approval.
- **Never modify production data casually** — no bulk `UPDATE`/`DELETE` on production data as a side effect of a code change; use seeders for test/dev data.
- **Avoid N+1** — use eager loading (`with()`, `load()`).
- **Use transactions for critical operations** — booking creation must be transactional (`DB::transaction` in [`BookingService::create()`](app/Services/BookingService.php)).
- **Use `lockForUpdate` for voucher code reservation** — when applying a voucher, lock the row to prevent double-redemption races.

---

## 7. Backend Rules

- **Controller-Service pattern**: controllers handle HTTP (validation, auth, responses); services own business logic. Follow this pattern; do not inline business logic into controllers.
- **`BookingPricingService::calculate()`** ([`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php)) is the **SOLE canonical pricing calculator**. All pricing (transit/daily/weekly/monthly, promo rates, voucher discounts) flows through it.
- **`BookingService::create()`** ([`app/Services/BookingService.php`](app/Services/BookingService.php)) is the **SOLE canonical booking creator**. It is transactional and applies vouchers via `Voucher::calculateDiscount()`.
- **`Voucher::calculateDiscount()`** ([`app/Models/Voucher.php`](app/Models/Voucher.php)) is the **SOLE canonical voucher calculator**.
- **Do NOT duplicate pricing/booking/voucher logic anywhere else** — not in controllers, views, or JS. Client-side pricing display is for preview only; the server is authoritative.
- **Do NOT put business logic in Blade** — at most, display logic (formatting, iteration).
- **FormRequest validation for all admin input** — create FormRequest classes; do not validate ad-hoc in controllers.
- **`authorize()` or admin middleware** for admin access — there is no `Policies/` directory; use `authorize()` in controllers or the `admin` role middleware.

---

## 8. Frontend Rules

- **Stack is fixed**: Blade + Alpine.js 3 + Hotwired Turbo (Turbo Drive) + Tailwind CSS v3 + Vite. **No Livewire, no Vue, no React, no Inertia.**
- **Custom Blade components** in `resources/views/components/*` — reuse existing ones (e.g. `text-input`, `money-input`, `modal`, `seo`, `captcha`, `analytics`) rather than re-implementing.
- **Public pages** use [`resources/views/layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php).
- **Admin pages** use [`resources/views/layouts/admin.blade.php`](resources/views/layouts/admin.blade.php).
- **Mobile-first, responsive** via Tailwind utilities. Do not add a CSS framework or component library.
- **Hotwired Turbo** handles page navigation — do not add full-page-reload navigation patterns or disable Turbo unnecessarily.
- **Alpine.js** for interactive widgets: booking form, gallery, modals, dropdowns. Prefer Alpine `x-data`/`x-show`/`x-transition` over jQuery or vanilla DOM scripts.
- Keep JS in [`resources/js/app.js`](resources/js/app.js) or per-page Alpine directives; avoid inline `<script>` where it can live in app.js.

---

## 9. CMS Rules

- **Custom Blade admin** — this is hand-built, not Filament/Nova/Voyager or any admin package. Work within the existing admin views/controllers.
- **Admin routes prefix**: `/admin` (configurable via the `admin_prefix` setting through the `slug()` helper — never hardcode `/admin`).
- **Admin middleware**: `['auth', 'verified', 'admin']`. The `admin` alias maps to [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php) (registered in [`bootstrap/app.php`](bootstrap/app.php)), which calls `User::isAdmin()` and accepts the `super-admin` and `admin` role slugs.
- **Admin controllers are split** — do NOT assume all admin resources live under `app/Http/Controllers/Admin/`. Only **6** controllers are in that namespace:
  - [`app/Http/Controllers/Admin/BackupController.php`](app/Http/Controllers/Admin/BackupController.php)
  - [`app/Http/Controllers/Admin/CurrencyRateController.php`](app/Http/Controllers/Admin/CurrencyRateController.php)
  - [`app/Http/Controllers/Admin/DashboardController.php`](app/Http/Controllers/Admin/DashboardController.php)
  - [`app/Http/Controllers/Admin/LanguageController.php`](app/Http/Controllers/Admin/LanguageController.php)
  - [`app/Http/Controllers/Admin/SlugSettingsController.php`](app/Http/Controllers/Admin/SlugSettingsController.php)
  - [`app/Http/Controllers/Admin/UserController.php`](app/Http/Controllers/Admin/UserController.php)

  All OTHER admin resources are served by **root-namespace** controllers in [`app/Http/Controllers/`](app/Http/Controllers) that handle BOTH admin and public actions, routed under the `/admin` group in [`routes/web.php`](routes/web.php): [`PropertyController`](app/Http/Controllers/PropertyController.php), [`BookingController`](app/Http/Controllers/BookingController.php), [`MediaController`](app/Http/Controllers/MediaController.php), [`PageController`](app/Http/Controllers/PageController.php), [`BlockController`](app/Http/Controllers/BlockController.php), [`NavigationController`](app/Http/Controllers/NavigationController.php), [`PostController`](app/Http/Controllers/PostController.php), [`CategoryController`](app/Http/Controllers/CategoryController.php), [`TagController`](app/Http/Controllers/TagController.php), [`VoucherController`](app/Http/Controllers/VoucherController.php), [`PromoRateController`](app/Http/Controllers/PromoRateController.php), [`RedirectController`](app/Http/Controllers/RedirectController.php), [`AmenityController`](app/Http/Controllers/AmenityController.php), and [`SettingsController`](app/Http/Controllers/SettingsController.php). Read [`routes/web.php`](routes/web.php) to confirm which action is admin vs public before editing.
- **Listing views**: `index.blade.php` for each admin resource (table of records).
- **Form views**: `_form.blade.php` partials reused by `create.blade.php` / `edit.blade.php` (see e.g. [`resources/views/admin/categories/_form.blade.php`](resources/views/admin/categories/_form.blade.php)).
- **Property admin uses partials** `_photos.blade.php`, `_pricing.blade.php`, `_policy.blade.php`, `_nearby.blade.php` (the Geoapify POI table + resync button) ([`resources/views/admin/properties/`](resources/views/admin/properties)).
- **Preserve existing admin workflows** — do not restructure admin UX or routing without explicit instruction.

---

## 10. Booking Rules

- **Booking flow**: Property page → Booking form ([`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php)) → `BookingService::create()` → success page ([`resources/views/bookings/success.blade.php`](resources/views/bookings/success.blade.php)) / status page ([`resources/views/bookings/status.blade.php`](resources/views/bookings/status.blade.php)).
- **Booking statuses** (from the `bookings.status` enum in [`2026_08_11_162521_create_bookings_table.php`](database/migrations/2026_08_11_162521_create_bookings_table.php)): `pending`, `confirmed`, `cancelled`, `completed` (default `pending`).
- **Pricing flows through `BookingPricingService::calculate()` ONLY** — never compute booking totals in the controller or view.
- **Voucher is applied inside `BookingService::create()`** via `Voucher::calculateDiscount()`; reserve voucher codes with `lockForUpdate`.
- **Guest status lookup uses `bookings.access_token`** (see [`2026_08_24_000000_add_access_token_to_bookings_table.php`](database/migrations/2026_08_24_000000_add_access_token_to_bookings_table.php)).
- **Do NOT implement a duplicate pricing calculation.**
- **Do NOT implement a server-side availability/conflict check** — none currently exists; adding one is a business-rule change that requires an audit and user confirmation first.

---

## 11. Pricing Rules

- **Property prices are stored as JSON** in the `prices` column on `properties`.
- Price keys are per booking type (see `Property::hasBookingType()` / `priceFor()` in [`app/Models/Property.php`](app/Models/Property.php)):
  - `daily` → `night_wd`, `night_we`
  - `transit` → `t3_wd`, `t3_we`, `t6_wd`, `t6_we`, `t9_wd`, `t9_we`, `t12_wd`, `t12_we`, `t24_wd`, `t24_we`
  - `weekly` → `weekly`
  - `monthly` → `monthly`
- **`BookingPricingService::calculate()`** ([`app/Services/BookingPricingService.php`](app/Services/BookingPricingService.php)) is canonical — transit/daily/weekly/monthly pricing, promo rates, and voucher discounts all route through it.
- **Promo rates** are managed via [`app/Models/PromoRate.php`](app/Models/PromoRate.php) (admin surface: root-namespace [`app/Http/Controllers/PromoRateController.php`](app/Http/Controllers/PromoRateController.php)).
- **Vouchers** are managed via [`app/Models/Voucher.php`](app/Models/Voucher.php) (admin surface: root-namespace [`app/Http/Controllers/VoucherController.php`](app/Http/Controllers/VoucherController.php)).
- **Global pricing settings** are managed in admin settings: root-namespace [`app/Http/Controllers/SettingsController.php`](app/Http/Controllers/SettingsController.php) + [`resources/views/admin/settings/partials/_pricing.blade.php`](resources/views/admin/settings/partials/_pricing.blade.php).
- **Never implement a second pricing calculation.** If a price path seems wrong, trace it back to the canonical service before changing anything.

---

## 12. Media Rules

- **Media model**: [`app/Models/Media.php`](app/Models/Media.php); admin surface: root-namespace [`app/Http/Controllers/MediaController.php`](app/Http/Controllers/MediaController.php).
- **Storage**: `public` disk (symlinked to `storage/app/public` via `php artisan storage:link`). Do not break the symlink convention or switch disks without instruction.
- **Property photos**: [`app/Models/PropertyPhoto.php`](app/Models/PropertyPhoto.php) with a `property_id` FK to `Property`. Keep this relationship intact.
- **Upload validation**: validate file type and size on every upload path (FormRequest or controller validation). Never trust a filename or content type from the client.
- **Preserve existing filesystem conventions** — follow how current uploads are stored/named/retrieved. Do not invent a parallel storage layout.
- **Do not break featured-image relationships** — check which media/photo is used as the primary/thumbnail before reordering or deleting.
- **SVG upload is currently allowed** — this is a known stored-XSS risk. Treat SVGs with caution: sanitize, restrict to trusted roles, or deny by default. Do not silently expand SVG acceptance.
- **URL import**: the media admin supports importing from a URL — keep SSRF protections intact (see [Security Rules](#15-security-rules)).

---

## 13. SEO Rules

- **`SeoMetadata` model** ([`app/Models/SeoMetadata.php`](app/Models/SeoMetadata.php)) is polymorphic via a `seoable` morph relationship — pages/posts/properties attach SEO metadata through it. Do not flatten or break the morph.
- **SEO component**: [`resources/views/components/seo.blade.php`](resources/views/components/seo.blade.php) renders meta tags — reuse it instead of hand-writing `<head>` meta in views.
- **SEO settings**: [`resources/views/admin/settings/partials/_seo.blade.php`](resources/views/admin/settings/partials/_seo.blade.php) (managed by root-namespace [`SettingsController`](app/Http/Controllers/SettingsController.php)).
- **Redirects**: [`app/Models/Redirect.php`](app/Models/Redirect.php) + admin surface (root-namespace [`app/Http/Controllers/RedirectController.php`](app/Http/Controllers/RedirectController.php)) — preserve redirect rules so old URLs keep working.
- **Slugs**: `properties.slug`, `pages.slug`, `posts.slug` are indexed lookup columns. Preserve slug stability; regenerate only on explicit request.
- **Sitemap EXISTS** — [`app/Services/SitemapService.php`](app/Services/SitemapService.php) generates `/sitemap.xml` (served via [`SeoController::sitemap`](app/Http/Controllers/SeoController.php)); `robots.txt` is served via [`SeoController::robots`](app/Http/Controllers/SeoController.php) backed by [`app/Services/RobotsService.php`](app/Services/RobotsService.php). Reuse these; do not add a second sitemap generator.
- **Preserve** canonical tags, existing metadata, semantic headings, and slugs when editing views or content.
- **Do not hide SEO content unnecessarily** — keep titles/descriptions/headings present and meaningful.
- **Do not create duplicate URLs** — always route through existing slug/redirect logic; never add parallel URL patterns that can collide.

---

## 14. Performance Rules

- **Avoid N+1 queries** — eager load with `with()` / `load()` for the common relationships: `property -> photos`, `property -> amenities`, `booking -> property`, `post -> category` / `post -> tags`.
- **One custom queued job exists** — [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php). Queue config defaults to `database` but `.env` overrides to `sync`, so that job currently runs **inline during the admin resync request**; its `$tries`/`$backoff`/`$timeout` only take effect with a real driver plus `php artisan queue:work`. If a task needs background processing, introduce it deliberately (and confirm the queue driver choice) rather than blocking page render.
- **Cache**: `file` driver by default (config default `database`, `.env` override `file`) — cache aggressively only where it is clearly safe (read-only data). Invalidate on writes.
- **Lazy-load below-the-fold media** (images, galleries) so page render is not blocked.
- **No blocking external API calls on page render** — the Geoapify nearby-places integration IS implemented, but Geoapify is called ONLY from [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php) (queued, 24h-cached per property under `geoapify_places_{id}`, triggered only by the admin resync action). The property page reads persisted `places`/`property_places` rows. Never call `GeoapifyService` — or any external API — from a controller, view, or other render path; a feature test pins that the public property page issues zero outbound HTTP requests.
- **Production caching**: `php artisan route:cache` and `php artisan config:cache` (see [Deployment Rules](#18-deployment-rules)). Remember `php artisan optimize:clear` after changes during development.

---

## 15. Security Rules

- **`APP_DEBUG=true` is set in production** (per audit, SEC-critical). **Awareness rule: flag this to the user; do not make it worse** (e.g. never log stack traces, dumps, or request payloads that expose internals). Recommend `APP_DEBUG=false` in production.
- **`cyberstrike.json` (security-scanner config that may contain credentials) is present in the repo working tree and IS listed in [`.gitignore`](.gitignore) (line 51)** — verify it is not committed with secrets. Do not add secrets, tokens, or credentials to it or any tracked file.
- **SVG upload is allowed** — sanitize or restrict; treat uploaded SVGs as untrusted HTML (stored-XSS risk).
- **Admin Git dashboard uses Symfony Process** — pass commands as argument arrays, never shell strings (prevents shell injection).
- **Contact map embeds** must go through [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php) — never render raw iframe URLs from settings.
- **Validate all input via FormRequest** — do not accept unvalidated request data into models.
- **Authorize all admin actions** — `authorize()` in controllers or the `admin` role middleware. There is no `Policies/` directory.
- **Prevent IDOR** — never trust user-controlled IDs to fetch admin resources; scope queries to the authenticated admin's permissions.
- **Never expose secrets** — no credentials, keys, or `.env` values in code, commits, logs, or responses.
- **Escape output** — Blade `{{ }}` auto-escapes; use `{!! !!}` only for intentionally-sanitized HTML (e.g. whitelisted block content). Never echo raw user input.
- **Do not trust client-side calculations** — pricing is computed server-side via `BookingPricingService::calculate()`; JS previews are not authoritative.

---

## 16. Testing Rules

- **PHPUnit** feature tests live in [`tests/Feature/`](tests/Feature). PHPUnit `^12.5` is used (not Pest).
- **Test database**: SQLite in-memory ([`phpunit.xml`](phpunit.xml)) — tests must not depend on MySQL-specific behavior.
- **Existing test areas**: `BookingFlowTest`, `CrudTest`, `BlogTest`, `AnalyticsTest`, `AccessibilityTest`, `BackupRestoreValidationTest`, `ContactMapEmbedTest`, `DashboardTest`, `ForceHttpsTest`, `InstallerTest`, `MediaUrlImportSsrfTest`, `PropertyNearbyPlacesTest` (manual `nearby_places` JSON), `GeoapifyNearbyPlacesTest` (persistent POI pipeline — uses `Http::preventStrayRequests()` + `Http::fake()`, and pins that the public property page makes zero outbound HTTP requests), and Breeze auth tests.
- **Bug fixes should include a regression test when practical** — write the failing test first, then fix.
- **Business-critical changes require tests** — pricing, booking creation, voucher application, and auth are business-critical. Never change canonical services without covering tests.
- **Do not delete or weaken tests to make CI green** — if a test fails, fix the code or the test's intent, not by removing coverage.

---

## 17. Git Rules

- **Small, focused commits** — one logical change per commit; keep diffs reviewable.
- **Do not commit secrets** — `cyberstrike.json` (security-scanner config) IS listed in [`.gitignore`](.gitignore) but may have been historically tracked; do not add secrets to it or any tracked file. Add new secret-like files to `.gitignore`.
- **Do not commit generated artifacts** — build output, caches, logs, and local environment files stay untracked.
- **Do not rewrite git history without explicit instruction.**
- **Do not force push without explicit instruction.**
- **Commit messages**: follow the existing convention if discernible from `git log` (audit notes BUG-/FIND-/SEC- prefixes may be in use); otherwise use Conventional Commits (e.g. `fix:`, `feat:`, `chore:`, `test:`).

---

## 18. Deployment Rules

- **No CI/CD pipeline exists** (no `.github/` found) — deployments are manual.
- **Installer**: [`routes/install.php`](routes/install.php) + [`resources/views/install/*`](resources/views/install) + [`config/installer.php`](config/installer.php) — the app bootstraps setup through it. Do not bypass it silently.
- **Queue worker**: `php artisan queue:work` (queue is `sync` in `.env`, `database` config default — confirm before relying on background jobs). With `sync`, [`FetchNearbyPlacesJob`](app/Jobs/FetchNearbyPlacesJob.php) runs inline in the admin resync request with no retries; set a real driver (e.g. `database`) and run a worker for it to execute asynchronously with its configured retry/backoff.
- **Geoapify env vars**: `GEOAPIFY_API_KEY` must be set in `.env` for POI syncing to work at all (it is currently blank — the job early-returns and the map falls back to OSM tiles). Optional: `GEOAPIFY_MAP_KEY` (browser-exposed map tiles; falls back to `GEOAPIFY_API_KEY`), `GEOAPIFY_RADIUS` (default 2000), `GEOAPIFY_MAX_RESULTS` (default 20). See [`docs/geoapify-setup.md`](docs/geoapify-setup.md).
- **Scheduler**: `php artisan schedule:run` via cron (see [`routes/console.php`](routes/console.php)).
- **Production caching**: `php artisan route:cache` and `php artisan config:cache`.
- **Storage link**: `php artisan storage:link` (required for the `public` disk used by `Media`).
- **Do not run destructive commands without explicit approval**: `migrate:fresh`, `db:wipe`, `rm -rf`, force-push. Prefer additive migrations.

---

## 19. AI Agent Behavior

This is the behavioral contract for every change:

1. **Inspect Before Modify** — Read the relevant files, migrations, models, and tests before editing. Never assume how a feature works.
2. **Never Guess Existing Architecture** — If you are unsure how something is structured, search the codebase or ask. Do not invent a parallel structure.
3. **Minimal Change** — Make the smallest change that satisfies the requirement. Do not refactor unrelated code.
4. **Preserve Existing Business Logic** — Pricing and booking calculations are canonical (see [Backend Rules](#7-backend-rules)). Do not reimplement them.
5. **Don't Fix What Isn't Broken** — If code is working and has no defect or test failure, leave it alone. Cosmetic "improvements" that change behavior are out of scope unless requested.
6. **No Fake Implementation** — Never leave stubs, TODOs that pretend to work, mock data masquerading as real, or features that "appear" implemented but aren't. If something is not implemented, say so.
7. **No Secrets** — Never commit, print, or log credentials, keys, or `.env` values.
8. **No Unnecessary Dependencies** — Do not add packages, assets, or files unless the task genuinely requires them.

---

## 20. Decision Making — When to Ask the User

Ask before proceeding when any of the following applies:

- **Requirement is ambiguous** — the desired behavior cannot be determined from code, tests, or docs.
- **Business rule change** — e.g. modifying pricing formulas, booking status flow, voucher semantics, or adding availability/conflict checking (none exists today).
- **Destructive migration** — dropping/rewriting tables or columns, `migrate:fresh` against real data.
- **Architecture conflict** — the change contradicts an established pattern (Controller-Service, canonical services, admin conventions).
- **Provider / infrastructure change** — new external service (e.g. Geoapify), queue driver, cache driver, mailer, or storage disk.
- **Auth change** — modifying auth scaffolding, roles, middleware, or session behavior.
- **Payment / booking calculation change** — anything that touches totals, discounts, or booking lifecycle.
- **Data deletion** — bulk `DELETE`/`UPDATE` or user-data removal beyond the requested scope.
- **Risky production migration** — any schema change applied against production data.

---

## 21. Decision Making — When NOT to Ask

Do not ask when the answer is already clear from existing evidence:

- The answer is determined by **existing code** (patterns, canonical services, components).
- The answer follows from **database structure** (migrations, FKs, indexes, JSON columns).
- The answer is covered by **tests** (behavior is pinned by a test suite).
- The answer is defined by **project conventions** (naming, structure, lang-file usage).
- The change is purely additive and reversible (new migration, new component, new test).

In these cases, implement the smallest correct change and report it.

---

## 22. Change Impact Analysis

Before any large change, run through this checklist and state the impacts in your plan:

- **Files affected** — which controllers, services, models, views, routes, configs will change?
- **Database affected** — new migrations? column/index/FK changes? seeders?
- **API affected** — any public endpoints, forms, or redirects whose contract changes?
- **Booking affected** — does pricing, voucher, status, or the booking flow change? (Must route through canonical services.)
- **SEO affected** — slugs, canonical tags, metadata, redirects, or heading structure?
- **Performance affected** — new N+1 risks, blocking calls, cache invalidation needs?
- **Security affected** — new upload/input paths, authz gaps, SSRF/XSS surface, secret exposure?
- **Tests required** — which existing tests cover the area, and what new tests are needed?

---

## 23. Source of Truth Hierarchy

When rules conflict, resolve in this order:

1. **Actual production-safe code and database structure** (migrations, models, services, routes) — the code wins.
2. **Tests** — they pin the intended behavior.
3. **Existing project documentation** — e.g. `docs/` (note: [`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md) is the original design spec and **has now been implemented** — see its Implementation Status section for the shipped file list and the deliberate divergences; where the spec and the code differ, the code is authoritative).
4. **AGENTS.md** — this file.
5. **Skill files** — if loaded.
6. **AI assumptions** — last resort; if you must rely on an assumption, say so explicitly and verify.

If AGENTS.md conflicts with real code, report the discrepancy rather than silently following the file.

---

_End of AGENTS.md. Grounded in [`docs/_agent-audit/AUDIT-FINDINGS.md`](docs/_agent-audit/AUDIT-FINDINGS.md). If a new audit changes verified facts, update this file accordingly._

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
