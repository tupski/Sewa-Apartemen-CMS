# Architecture Overview

High-level architecture of the Sewa Apartemen CMS ("Lya Rooms"), an apartment rental CMS with a multi-language marketing website, blog, and booking system.

This document is grounded in the repository audit ([`docs/_agent-audit/AUDIT-FINDINGS.md`](../_agent-audit/AUDIT-FINDINGS.md)) and the actual code. For the behavioral rules that govern changes, see [`AGENTS.md`](../../AGENTS.md).

## Stack

| Area | Technology |
|------|------------|
| Framework | Laravel `^13.8` ([`composer.json`](../../composer.json)) |
| Language | PHP `^8.3` |
| Auth scaffolding | Laravel Breeze `^2.4` (session-based) |
| Templating | Blade |
| JS | Alpine.js `^3` + Hotwired Turbo `^8` (Turbo Drive) — **no** Livewire/Vue/React/Inertia |
| CSS | Tailwind CSS v3 (`@tailwindcss/forms`) |
| Build | Vite `^8` + `laravel-vite-plugin` (not Mix) |
| Database | MySQL in production (`sa_cms`); SQLite `:memory:` in tests |
| Queue | `.env` → `sync`; config default `database` (no custom queued jobs) |
| Cache | `.env` → `file`; config default `database` |
| Session | `.env` → `file` |
| Mail | `.env` → `log`; runtime-overridable via [`MailSettingsServiceProvider`](../../app/Providers/MailSettingsServiceProvider.php) |
| Testing | PHPUnit `^12.5` (not Pest) |

The entry point for the JS build is [`resources/js/app.js`](../../resources/js/app.js) (Turbo setup, Alpine registration, property-detail interactions).

## Request Lifecycle

The app uses the Laravel 11+ bootstrap style — middleware and providers are wired in [`bootstrap/app.php`](../../bootstrap/app.php), with no HTTP Kernel class.

```
Request
  → Global middleware (appended, order matters):
      CheckInstalled → ForceHttps → RedirectMiddleware → LocaleMiddleware
  → web group also appends: SecurityHeaders
  → Route (routes/web.php | routes/auth.php | routes/install.php)
  → Route middleware alias (e.g. admin, captcha, protect.installer)
  → Controller (HTTP concerns: validation via FormRequest, auth, response)
  → Service (business logic)
  → Blade view (rendering only)
Response
```

Middleware aliases:

| Alias | Class |
|-------|-------|
| `admin` | [`EnsureUserIsAdmin`](../../app/Http/Middleware/EnsureUserIsAdmin.php) |
| `protect.installer` | [`ProtectInstaller`](../../app/Http/Middleware/ProtectInstaller.php) |
| `captcha` | [`VerifyCaptcha`](../../app/Http/Middleware/VerifyCaptcha.php) |

Proxies are trusted for Cloudflare Tunnel. A notable routing detail: the catch-all `GET /{page:slug}` → `PageController@publicShow` is registered **last** in [`routes/web.php`](../../routes/web.php), so all other routes take precedence.

## Controller-Service Pattern

The project enforces a strict separation:

- **Controllers** handle HTTP: input validation (via FormRequest), authorization, and building responses. They do not own business logic.
- **Services** (`app/Services/`) own business logic — pricing, booking creation, SEO metadata, sitemaps, etc.
- **Blade views** render only. No business logic (at most display formatting/iteration).

Business-critical calculations (pricing, booking, vouchers) have a single canonical home and must never be duplicated. See [`docs/decisions/ADR-001`](../decisions/ADR-001-canonical-pricing-and-booking-services.md).

## Directory Layout

`app/` contains **only** these subdirectories:

```
app/
  Console/Commands/      only FetchCurrencyRates (currency:fetch)
  Helpers/               activity.php, slug.php, upload.php (globally autoloaded)
  Http/Controllers/      frontend + shared admin controllers
  Http/Controllers/Admin/  admin-only controllers
  Http/Middleware/       11 middleware
  Http/Requests/         FormRequest validation classes
  Models/                21 Eloquent models
  Providers/             AppServiceProvider, MailSettingsServiceProvider
  Services/              15 services (business logic)
  View/Components/        AppLayout, GuestLayout only
```

There are **no** `Jobs/`, `Events/`, `Listeners/`, `Policies/`, `Observers/`, `Actions/`, `Repositories/`, or `Notifications/` directories. Do not assume these patterns exist.

## Layouts

| Layout | Purpose |
|--------|---------|
| [`layouts/frontend.blade.php`](../../resources/views/layouts/frontend.blade.php) | Public marketing/website pages (settings-driven header/footer, SEO, analytics, navigation) |
| [`layouts/admin.blade.php`](../../resources/views/layouts/admin.blade.php) | Admin panel |
| [`layouts/app.blade.php`](../../resources/views/layouts/app.blade.php) | Breeze authenticated scaffold |
| [`layouts/guest.blade.php`](../../resources/views/layouts/guest.blade.php) | Breeze guest scaffold |

Shared Blade components live in [`resources/views/components/`](../../resources/views/components) (e.g. `seo`, `analytics`, `captcha`, `modal`, `money-input`, `text-input`). Reuse these rather than re-implementing.

## Key Services

All live in [`app/Services/`](../../app/Services) (15 total):

| Service | Responsibility |
|---------|----------------|
| [`BookingPricingService`](../../app/Services/BookingPricingService.php) | **Canonical** price calculator (all booking types, promos, vouchers) |
| [`BookingService`](../../app/Services/BookingService.php) | **Canonical** transactional booking creator + status changes |
| [`BookingNotificationService`](../../app/Services/BookingNotificationService.php) | Fire-and-forget webhook notifications on booking lifecycle |
| [`AnalyticsService`](../../app/Services/AnalyticsService.php) | Analytics config/rendering |
| [`BackupService`](../../app/Services/BackupService.php) | Backup/restore support (admin) |
| [`CaptchaService`](../../app/Services/CaptchaService.php) | Captcha verification helper |
| [`CurrencyRateService`](../../app/Services/CurrencyRateService.php) | FX rates fetch/store (paired with `currency:fetch`) |
| [`GeoLocaleService`](../../app/Services/GeoLocaleService.php) | Geo/locale resolution |
| [`MapEmbedService`](../../app/Services/MapEmbedService.php) | Sanitizes contact map embeds (never raw iframe) |
| [`RobotsService`](../../app/Services/RobotsService.php) | Generates `robots.txt` |
| [`SafeHtmlService`](../../app/Services/SafeHtmlService.php) | HTML sanitization |
| [`SchemaService`](../../app/Services/SchemaService.php) | JSON-LD structured data |
| [`SeoService`](../../app/Services/SeoService.php) | Meta tags, canonical, OG/Twitter, title normalization |
| [`SettingsService`](../../app/Services/SettingsService.php) | Cached settings access |
| [`SitemapService`](../../app/Services/SitemapService.php) | Generates `/sitemap.xml` |

> **Note:** A sitemap **does exist** (`SitemapService` → `GET /sitemap.xml`, via `SeoController`), and `robots.txt` is served by `RobotsService`. The audit's earlier "no sitemap" note is corrected here.

## Auth & Roles

- Session-based auth via Laravel Breeze (`web` guard, `User` model).
- Admin authorization is enforced by [`EnsureUserIsAdmin`](../../app/Http/Middleware/EnsureUserIsAdmin.php), which calls `User::isAdmin()`. That checks for the `super-admin` or `admin` role slugs.
- Roles are attached through the `model_has_roles` pivot (Spatie-style shape, but a custom role system — no Spatie package).
- There is **no** `Policies/` directory. Authorization is done via `authorize()` in controllers or the `admin` middleware.

## Installer

The app bootstraps through a custom multi-step web installer:

- Routes: [`routes/install.php`](../../routes/install.php) (under `/install`, wrapped in `web` + `protect.installer`).
- Config: [`config/installer.php`](../../config/installer.php) (reads `env()` at config time so it survives `config:cache`; supports `INSTALLER_ALLOWED_IPS` / `INSTALLER_TOKEN`).
- Views: [`resources/views/install/`](../../resources/views/install) (requirements, database, application, website, admin, finish).
- The `CheckInstalled` middleware redirects to the installer when the app is not yet installed. Do not bypass the installer silently.

## Internationalization (i18n)

- Translation strings live in [`lang/en.json`](../../lang/en.json) and [`lang/id.json`](../../lang/id.json). Use `__('...')` with these keys; do not hardcode UI strings.
- The active locale is resolved from the session by [`LocaleMiddleware`](../../app/Http/Middleware/LocaleMiddleware.php).
- Locale and display-currency switchers are POST routes in [`routes/web.php`](../../routes/web.php); the locale switch validates against active `Language` records.
- Languages and currency rates are admin-managed (`Language`, `CurrencyRate` models). FX rates are refreshed by the scheduled `currency:fetch` command every 6 hours.

## What Is NOT Present

To prevent invented architecture, the following do **not** exist:

- No `Room` / `Unit` model — units were refactored into a `unit_types` JSON column on `properties` (see [`docs/decisions/ADR-003`](../decisions/ADR-003-units-refactored-to-property-types.md)).
- No `Availability` table — availability is **not** a stored table/model, but `BookingService::create()` **does** run a server-side conflict/double-booking check via [`BookingService::validateAvailability()`](../../app/Services/BookingService.php:204) (overlap query on `bookings` with `lockForUpdate()`; rejects overlapping windows for the same `property_id` + `unit_type` with `status != 'cancelled'`).
- No `Payment` model or payment gateway — only `total_price` / `deposit_amount` fields on bookings.
- No `NearbyPlace` model — nearby places are manually-entered JSON on `properties.nearby_places` (see [`docs/decisions/ADR-002`](../decisions/ADR-002-nearby-places-manual-json-not-geoapify.md)).
- No custom queued jobs, events, listeners, or observers.
- No `Policies/` directory.
- No dedicated API routes file (some controllers return JSON conditionally via `$request->wantsJson()`, but there is no public API).
- No CI/CD pipeline — deployments are manual (see [`AGENTS.md` §18](../../AGENTS.md)).

## Related Documentation

- [`AGENTS.md`](../../AGENTS.md) — master rule file / behavioral contract.
- [`docs/architecture/database.md`](database.md) — schema overview.
- [`docs/domain/property.md`](../domain/property.md), [`docs/domain/booking.md`](../domain/booking.md), [`docs/domain/pricing.md`](../domain/pricing.md) — domain guides.
- Skills under [`.agents/skills/`](../../.agents/skills) — task-scoped playbooks (`laravel`, `cms`, `booking`, `pricing`, `property`, `database`, etc.).
