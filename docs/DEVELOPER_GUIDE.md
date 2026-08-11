# Developer Guide — Sewa Apartemen CMS

## Tech Stack

- **Framework**: Laravel 13.8 (PHP 8.3+)
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Database**: MySQL 8 / MariaDB 10.6+
- **Build**: Vite

## Directory Layout

```
app/
  Http/
    Controllers/        # Route handlers
      Admin/             # Admin controllers (UserController, DashboardController)
    Middleware/          # CheckInstalled, RedirectMiddleware, SecurityHeaders
    Requests/            # Form request validation classes
  Models/               # Eloquent models
  Services/             # Business logic services
  Providers/            # AppServiceProvider, RouteServiceProvider
  View/Components/      # Blade components (AppLayout, GuestLayout)

routes/
  web.php               # Main web routes (public + admin)
  auth.php              # Auth routes (Breeze)
  install.php           # Web installer routes

database/
  migrations/           # Schema migrations
  factories/            # Model factories for testing
  seeders/              # DatabaseSeeder, RoleSeeder, SettingSeeder

resources/
  views/                # Blade templates
    admin/              # Admin panel views (properties, units, bookings, etc.)
    blog/               # Blog frontend (index, show, sidebar)
    bookings/           # Booking form + success page
    components/         # Shared Blade components (analytics, seo, buttons)
    layouts/            # Base layouts (admin, app, guest)
  css/app.css           # Tailwind entry
  js/app.js             # Alpine + Vite entry

tests/
  Unit/                 # Pure unit tests (ServicesTest, etc.)
  Feature/              # HTTP integration tests

docs/                   # Project documentation
```

## Key Services

All services under [`app/Services/`](app/Services):

| Service | Purpose |
|---------|---------|
| [`SettingsService`](app/Services/SettingsService.php) | Key-value settings with DB persistence + in-memory cache. `::get(key, default)`, `::set(key, value, group)`, `::all(?group)`, `::has(key)`, `::clearCache()` |
| [`SeoService`](app/Services/SeoService.php) | Meta tags generation. `::title()`, `::description()`, `::canonical()`, `::openGraphTags()`, `::twitterTags()`, `::metaTagsArray()`, `::renderMetaTags()`, `::renderJsonLd()` |
| [`SchemaService`](app/Services/SchemaService.php) | JSON-LD structured data. `::organization()`, `::website()`, `::realEstateListing()`, `::offer()`, `::breadcrumbList()`, `::article()` |
| [`SitemapService`](app/Services/SitemapService.php) | XML sitemap generation with 24h cache. `::generate()` |
| [`RobotsService`](app/Services/RobotsService.php) | robots.txt generation. `::generate()` — respects `robots_txt` setting override |
| [`AnalyticsService`](app/Services/AnalyticsService.php) | GA4, GTM, Meta Pixel, Clarity, Search Console script tags. `::ga4Script()`, `::gtmScript()`, `::metaPixelScript()`, `::clarityScript()`, `::searchConsoleMeta()`, `::hasAny()` |
| [`BookingService`](app/Services/BookingService.php) | Booking lifecycle. `::generateCode()`, `::create()`, `::confirm()`, `::cancel()`, `::complete()` |

## Adding a New Content Type with SEO

1. Create Model + Migration with `slug`, `name`, `description`, `status` fields.
2. Add `seo()` morph relation using the [`SeoMetadata`](app/Models/SeoMetadata.php) model (polymorphic: `seoable_type` / `seoable_id`).
3. Register the model in [`SeoService::buildJsonLdForModel()`](app/Services/SeoService.php) to get auto JSON-LD.
4. Register URLs in [`SitemapService::collectUrls()`](app/Services/SitemapService.php).
5. Create admin CRUD controller + views under `resources/views/admin/`.
6. Add routes in [`routes/web.php`](routes/web.php) inside the admin group.

## Caching Strategy

- **Settings**: Cached forever via `Cache::rememberForever('settings')`. Cleared on any `SettingsService::set()` call.
- **Sitemap**: Cached for 86400 seconds. Bust via `Cache::forget('sitemap.xml')` or `php artisan cache:clear`.
- **Blade views**: Compiled to `storage/framework/views/`. Clear with `php artisan view:clear`.
- **Route cache**: `php artisan route:cache` for production.

## Testing

```bash
# Run all tests
php artisan test

# Filter by test file
php artisan test --filter=ServicesTest

# Filter by test method
php artisan test --filter="settings set and get"
```

Tests use `RefreshDatabase` trait — each test gets a fresh DB migrated in a transaction. Model factories are in [`database/factories/`](database/factories). Extend [`tests/TestCase.php`](tests/TestCase.php) for shared setup.

Target: 90+ tests. Current: 174 tests, 414 assertions.

## Environment Variables

Key `.env` entries beyond standard Laravel:

```
APP_NAME="Sewa Apartemen"
APP_URL=http://localhost
INSTALLED=false           # Web installer lock
GOOGLE_ANALYTICS_ID=      # GA4 measurement ID
GOOGLE_TAG_MANAGER_ID=    # GTM container ID
META_PIXEL_ID=            # Facebook pixel
MICROSOFT_CLARITY_ID=     # Clarity project ID
SEARCH_CONSOLE_TOKEN=     # Google verification token
```
