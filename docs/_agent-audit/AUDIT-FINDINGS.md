# AUDIT FINDINGS — Sewa Apartemen CMS ("Lya Rooms")

Read-only repository audit. Source of truth for later AGENTS.md / skills / docs work.
Every claim references the concrete file / class / table it is based on.
Marketing/app name in [`.env`](.env:1) is `APP_NAME="Lya Rooms"`; the docs also refer to "Lya Rooms" / "Kakarama Room".

---

## 1. STACK

| Area | Fact | Source |
|------|------|--------|
| Framework | Laravel `^13.8` | [`composer.json`](composer.json:10) |
| PHP | `^8.3` | [`composer.json`](composer.json:9) |
| Tinker | `laravel/tinker ^3.0` | [`composer.json`](composer.json:11) |
| Auth scaffolding | Laravel Breeze `^2.4` (dev) | [`composer.json`](composer.json:16) |
| Testing | PHPUnit `^12.5.12` (NOT Pest, though pest-plugin is allow-listed) | [`composer.json`](composer.json:22), [`phpunit.xml`](phpunit.xml:1) |
| Dev tooling | Pint `^1.27`, Pail `^1.2.5`, Debugbar `^4.4`, Collision, Mockery, `laravel/pao ^1.0.6` | [`composer.json`](composer.json:14) |
| Frontend build | Vite `^8.0.0` + `laravel-vite-plugin ^3.1` (NOT Mix) | [`package.json`](package.json:15) |
| CSS | Tailwind `^3.1.0` + `@tailwindcss/forms`; note `@tailwindcss/vite ^4.0.0` also present (mixed v3/v4 deps) | [`package.json`](package.json:10), [`tailwind.config.js`](tailwind.config.js:1) |
| JS | Alpine.js `^3.4.2` + Hotwired Turbo `^8.0.23` (Turbo Drive). NO Livewire/Vue/React/Inertia. | [`package.json`](package.json:12), [`resources/js/app.js`](resources/js/app.js:1) |
| DB driver | Default config `sqlite`; `.env` uses `mysql` (`sa_cms`); tests use sqlite `:memory:` | [`config/database.php`](config/database.php:20), [`.env`](.env:29), [`phpunit.xml`](phpunit.xml:26) |
| Queue | `.env` `QUEUE_CONNECTION=sync`; config default `database` | [`.env`](.env:50), [`config/queue.php`](config/queue.php:16) |
| Cache | `.env` `CACHE_STORE=file`; config default `database` | [`.env`](.env:53), [`config/cache.php`](config/cache.php:18) |
| Session | `.env` `SESSION_DRIVER=file`; config default `database` | [`.env`](.env:37), [`config/session.php`](config/session.php:21) |
| Filesystem | `.env` `FILESYSTEM_DISK=local`; disks: `local` (private, `/private`), `public` (`/storage`), `s3` | [`.env`](.env:47), [`config/filesystems.php`](config/filesystems.php:31) |
| Mail | `.env` `MAIL_MAILER=log`; runtime overridable via [`MailSettingsServiceProvider`](app/Providers/MailSettingsServiceProvider.php:1) | [`.env`](.env:57), [`bootstrap/providers.php`](bootstrap/providers.php:5) |
| Broadcasting | `log` | [`.env`](.env:44) |
| CMS type | Custom Blade admin (NOT Filament/Nova). No filament/livewire/inertia/spatie in composer. | [`composer.json`](composer.json:8), admin views under [`resources/views/admin`](resources/views/admin) |
| Installer | Custom multi-step installer | [`config/installer.php`](config/installer.php:1), [`routes/install.php`](routes/install.php:1), [`app/Http/Controllers/InstallerController.php`](app/Http/Controllers/InstallerController.php:1) |

Bootstrap/middleware wiring lives in [`bootstrap/app.php`](bootstrap/app.php:9) (Laravel 11+ style, no HTTP Kernel class). Registered service providers: [`AppServiceProvider`](app/Providers/AppServiceProvider.php:1) (empty) and [`MailSettingsServiceProvider`](app/Providers/MailSettingsServiceProvider.php:1) — [`bootstrap/providers.php`](bootstrap/providers.php:1).

Global middleware appended (order matters): [`CheckInstalled`](app/Http/Middleware/CheckInstalled.php:1) → [`ForceHttps`](app/Http/Middleware/ForceHttps.php:1) → [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php:1) → [`LocaleMiddleware`](app/Http/Middleware/LocaleMiddleware.php:1); web group also appends [`SecurityHeaders`](app/Http/Middleware/SecurityHeaders.php:1). Aliases: `admin` → [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php:1), `protect.installer` → [`ProtectInstaller`](app/Http/Middleware/ProtectInstaller.php:1), `captcha` → [`VerifyCaptcha`](app/Http/Middleware/VerifyCaptcha.php:1). Trusts proxies for Cloudflare Tunnel. See [`bootstrap/app.php`](bootstrap/app.php:25).

Helper files autoloaded globally: [`app/Helpers/activity.php`](app/Helpers/activity.php:1) (`log_activity()`), [`app/Helpers/slug.php`](app/Helpers/slug.php:1) (`slug()`, `slug_url()`), [`app/Helpers/upload.php`](app/Helpers/upload.php:1) (`upload_file()`) — [`composer.json`](composer.json:30).

---

## 2. DOMAIN — Confirmed Eloquent Models

All live in `app/Models`. Entities that EXIST:

| Model | File | Notes |
|-------|------|-------|
| Property | [`Property.php`](app/Models/Property.php:10) | Core. SoftDeletes. JSON `unit_types`, `prices`, `weekend_days`, `photo_categories`, `nearby_places`, `required_documents`. |
| PropertyPhoto | [`PropertyPhoto.php`](app/Models/PropertyPhoto.php:7) | Gallery link (property_id + media_id + category). |
| Amenity | [`Amenity.php`](app/Models/Amenity.php:8) | belongsToMany Property via `amenity_property`. |
| Booking | [`Booking.php`](app/Models/Booking.php:9) | Core. SoftDeletes. |
| Voucher | [`Voucher.php`](app/Models/Voucher.php:10) | SoftDeletes. Percent/fixed discount. |
| PromoRate | [`PromoRate.php`](app/Models/PromoRate.php:8) | Per-property promo pricing. |
| Media | [`Media.php`](app/Models/Media.php:8) | URL/thumbnail accessors. |
| Page | [`Page.php`](app/Models/Page.php:8) | CMS page, JSON `blocks`, `is_homepage`. |
| Block | [`Block.php`](app/Models/Block.php:1) | Reusable content block. |
| Navigation | [`Navigation.php`](app/Models/Navigation.php:11) | Menu tree (parent/children, menu_location). |
| Post | [`Post.php`](app/Models/Post.php:9) | Blog post. morphOne SeoMetadata. |
| Category | [`Category.php`](app/Models/Category.php:1) | Blog category. |
| Tag | [`Tag.php`](app/Models/Tag.php:1) | Blog tags via `post_tag`. |
| User | [`User.php`](app/Models/User.php:18) | Auth. roles() belongsToMany. |
| Role | [`Role.php`](app/Models/Role.php:7) | via `model_has_roles`. |
| SeoMetadata | [`SeoMetadata.php`](app/Models/SeoMetadata.php:8) | Polymorphic `seoable`. |
| Redirect | [`Redirect.php`](app/Models/Redirect.php:9) | from_url/to_url/status_code. Cache-busting. |
| Setting | [`Setting.php`](app/Models/Setting.php:7) | key/value/type/group. |
| Language | [`Language.php`](app/Models/Language.php:7) | i18n language admin. |
| CurrencyRate | [`CurrencyRate.php`](app/Models/CurrencyRate.php:1) | FX rates. |
| ActivityLog | [`ActivityLog.php`](app/Models/ActivityLog.php:1) | user activity audit (`user_activity_logs`). |

Concepts that DO NOT exist as models: Apartment, Building, Room/Unit (Unit was **removed** — see [`refactor_units_to_property_types`](database/migrations/2026_08_12_000000_refactor_units_to_property_types.php:1); note `SchemaService` still references a non-existent `App\Models\Unit`), RoomType (represented as `unit_types` JSON on Property), Availability (no table; server-side conflict check exists in code via `BookingService::validateAvailability()` — see row below), Guest/Customer (denormalized onto Booking as `customer_*`), NearbyPlace (stored as JSON `nearby_places` on Property — NO `places`/`property_places` tables), Review, Payment (no model; only `deposit_amount`/`total_price` fields), Location, Currency (only CurrencyRate).

Factories present: [`PropertyFactory`](database/factories/PropertyFactory.php:1), [`BookingFactory`](database/factories/BookingFactory.php:1), [`PostFactory`](database/factories/PostFactory.php:1), [`UserFactory`](database/factories/UserFactory.php:1), and a stale [`UnitFactory`](database/factories/UnitFactory.php:1) (Unit no longer exists — likely dead).

---

## 3. ARCHITECTURE MAP

### Directories present (authoritative)
`app/` contains ONLY: `Console/Commands/`, `Helpers/`, `Http/Controllers/`, `Http/Middleware/`, `Http/Requests/`, `Models/`, `Providers/`, `Services/`, `View/Components/`. There are **NO** `Jobs/`, `Events/`, `Listeners/`, `Policies/`, `Observers/`, `Actions/`, `Repositories/`, `Notifications/` directories. (Confirmed via recursive listing.)

### Route files
- [`routes/web.php`](routes/web.php:1) — everything except installer. Notable:
  - Home: `GET /` → [`HomeController@index`](app/Http/Controllers/HomeController.php:1).
  - Public property listing/detail use **configurable slugs** via `slug('slug_apartments','apartments')` and route-model binding `{property:slug}` — [`routes/web.php`](routes/web.php:31).
  - Blog: index/show/category/tag with configurable base slug — [`routes/web.php`](routes/web.php:35).
  - Search suggest JSON endpoint (`/search/suggest`, throttled `30,1`) — [`routes/web.php`](routes/web.php:41).
  - Sitemap + robots — [`routes/web.php`](routes/web.php:46).
  - Contact page — [`routes/web.php`](routes/web.php:50).
  - Locale/currency switchers are POST closures with `Language::where('code')->where('is_active')` validation — [`routes/web.php`](routes/web.php:184).
  - **Catch-all `GET /{page:slug}` → [`PageController@publicShow`](app/Http/Controllers/PageController.php:1) registered LAST** — [`routes/web.php`](routes/web.php:204).
- [`routes/auth.php`](routes/auth.php:1) — Breeze auth routes (login/register/forgot/reset/verify/confirm/profile).
- [`routes/install.php`](routes/install.php:1) — installer under `/install` prefix, wrapped with `web` + `protect.installer` middleware via [`bootstrap/app.php`](bootstrap/app.php:15).
- [`routes/console.php`](routes/console.php:1) — `inspire` + scheduled `currency:fetch` every 6 hours.

### Controllers (root namespace, public + shared admin)
[`AmenityController`](app/Http/Controllers/AmenityController.php:1), [`BlockController`](app/Http/Controllers/BlockController.php:1), [`BlogController`](app/Http/Controllers/BlogController.php:1), [`BookingController`](app/Http/Controllers/BookingController.php:1), [`CategoryController`](app/Http/Controllers/CategoryController.php:1), [`ContactController`](app/Http/Controllers/ContactController.php:1), [`HomeController`](app/Http/Controllers/HomeController.php:1), [`MediaController`](app/Http/Controllers/MediaController.php:1), [`NavigationController`](app/Http/Controllers/NavigationController.php:1), [`PageController`](app/Http/Controllers/PageController.php:1), [`PostController`](app/Http/Controllers/PostController.php:1), [`ProfileController`](app/Http/Controllers/ProfileController.php:1), [`PromoRateController`](app/Http/Controllers/PromoRateController.php:1), [`PromotionController`](app/Http/Controllers/PromotionController.php:1), [`PropertyController`](app/Http/Controllers/PropertyController.php:1), [`InstallerController`](app/Http/Controllers/InstallerController.php:1), plus [`SeoController`](app/Http/Controllers/SeoController.php:1), [`RedirectController`](app/Http/Controllers/RedirectController.php:1), [`SearchController`](app/Http/Controllers/SearchController.php:1), [`SettingsController`](app/Http/Controllers/SettingsController.php:1), [`TagController`](app/Http/Controllers/TagController.php:1), [`VoucherController`](app/Http/Controllers/VoucherController.php:1) (referenced in [`routes/web.php`](routes/web.php:14)). The `PromotionController` appears to be a legacy duplicate of the PromoRate feature — needs human verification.

### Admin controllers
Under [`app/Http/Controllers/Admin`](app/Http/Controllers/Admin): [`BackupController`](app/Http/Controllers/Admin/BackupController.php:1), [`CurrencyRateController`](app/Http/Controllers/Admin/CurrencyRateController.php:1), [`DashboardController`](app/Http/Controllers/Admin/DashboardController.php:1), [`LanguageController`](app/Http/Controllers/Admin/LanguageController.php:1), [`SlugSettingsController`](app/Http/Controllers/Admin/SlugSettingsController.php:1), [`UserController`](app/Http/Controllers/Admin/UserController.php:1).

### Auth controllers (Breeze)
Standard 10 under [`app/Http/Controllers/Auth`](app/Http/Controllers/Auth): AuthenticatedSession, ConfirmablePassword, EmailVerificationNotification/Prompt, NewPassword, Password, PasswordResetLink, RegisteredUser, VerifyEmail.

### Middleware (11)
[`CheckInstalled`](app/Http/Middleware/CheckInstalled.php:1), [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php:1), [`ForceHttps`](app/Http/Middleware/ForceHttps.php:1), [`LocaleMiddleware`](app/Http/Middleware/LocaleMiddleware.php:1), [`PreventAccessWhenInstalled`](app/Http/Middleware/PreventAccessWhenInstalled.php:1), [`ProtectInstaller`](app/Http/Middleware/ProtectInstaller.php:1), [`RedirectIfNotInstalled`](app/Http/Middleware/RedirectIfNotInstalled.php:1), [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php:1), [`SecurityHeaders`](app/Http/Middleware/SecurityHeaders.php:1), [`VerifyCaptcha`](app/Http/Middleware/VerifyCaptcha.php:1), plus `VerifyCsrfToken` implicitly (framework). Installer-protection middleware set: `CheckInstalled`, `PreventAccessWhenInstalled`, `RedirectIfNotInstalled`, `ProtectInstaller`.

### FormRequests (9 + 2 auth)
[`AmenityRequest`](app/Http/Requests/AmenityRequest.php:1), [`BlockRequest`](app/Http/Requests/BlockRequest.php:1), [`BookingRequest`](app/Http/Requests/BookingRequest.php:1), [`MediaRequest`](app/Http/Requests/MediaRequest.php:1), [`NavigationRequest`](app/Http/Requests/NavigationRequest.php:1), [`PageRequest`](app/Http/Requests/PageRequest.php:1), [`ProfileUpdateRequest`](app/Http/Requests/ProfileUpdateRequest.php:1), [`PropertyRequest`](app/Http/Requests/PropertyRequest.php:1) (strips thousands separators in `prepareForValidation`), and [`Auth/LoginRequest`](app/Http/Requests/Auth/LoginRequest.php:1).

### Services (15)
> **CORRECTION (verified):** header previously read "(16)"; the directory contains **15** service classes (enumerated below). Corrected to 15.
[`AnalyticsService`](app/Services/AnalyticsService.php:1), [`BackupService`](app/Services/BackupService.php:1), [`BookingNotificationService`](app/Services/BookingNotificationService.php:1), [`BookingPricingService`](app/Services/BookingPricingService.php:1), [`BookingService`](app/Services/BookingService.php:1), [`CaptchaService`](app/Services/CaptchaService.php:1), [`CurrencyRateService`](app/Services/CurrencyRateService.php:1), [`GeoLocaleService`](app/Services/GeoLocaleService.php:1), [`MapEmbedService`](app/Services/MapEmbedService.php:1), [`RobotsService`](app/Services/RobotsService.php:1), [`SafeHtmlService`](app/Services/SafeHtmlService.php:1), [`SchemaService`](app/Services/SchemaService.php:1), [`SeoService`](app/Services/SeoService.php:1), [`SettingsService`](app/Services/SettingsService.php:1), [`SitemapService`](app/Services/SitemapService.php:1).

### Artisan commands
Only [`FetchCurrencyRates`](app/Console/Commands/FetchCurrencyRates.php:1) (`currency:fetch`). Scheduled in [`routes/console.php`](routes/console.php:12).

### View components
Blade layout components only: [`AppLayout`](app/View/Components/AppLayout.php:1), [`GuestLayout`](app/View/Components/GuestLayout.php:1). All other UI "components" are Blade partials/components under `resources/views/components/`.

### View components/partials (Blade) — [`resources/views/components`](resources/views/components)
`seo.blade.php`, `analytics.blade.php`, `analytics-body.blade.php`, `application-logo`, `auth-session-status`, `captcha`, `danger-button`, `dropdown`, `dropdown-link`, `input-error`, `input-label`, `modal`, `money-input`, `nav-link`, `password-input`, `primary-button`, `responsive-nav-link`, `search-input`, `secondary-button`, `share-modal`, `text-input`.

### Critical vs legacy/unused
- Critical: [`BookingService`](app/Services/BookingService.php:1), [`BookingPricingService`](app/Services/BookingPricingService.php:1), [`PropertyController`](app/Http/Controllers/PropertyController.php:1), [`MediaController`](app/Http/Controllers/MediaController.php:1), [`SettingsController`](app/Http/Controllers/SettingsController.php:1), installer/middleware set, [`SeoService`](app/Services/SeoService.php:1).
- Likely legacy/dead: [`PromotionController`](app/Http/Controllers/PromotionController.php:1) (vs [`PromoRateController`](app/Http/Controllers/PromoRateController.php:1)); stale [`UnitFactory`](database/factories/UnitFactory.php:1); any `App\Models\Unit` reference in `SchemaService`. **Unconfirmed, needs verification.**

---

## 4. DATABASE

Naming conventions: plural snake_case table names; PK always `id`; FKs named `{singular}_id`; pivot `amenity_property`, `post_tag`, `model_has_roles`; JSON columns for flexible domain data; soft deletes via `deleted_at` where used.

### Confirmed tables and key columns (from migrations)

| Table | Migration | Key columns / constraints |
|-------|-----------|---------------------------|
| `users` | [`0001_01_01_000000_create_users_table.php`](database/migrations/0001_01_01_000000_create_users_table.php:1) | id, name, email unique, email_verified_at, password, remember_token; later `phone`, `avatar` added ([`2026_08_11_141657`](database/migrations/2026_08_11_141657_add_phone_and_avatar_to_users_table.php:1)) |
| `cache` / `cache_locks` | [`0001_01_01_000001_create_cache_table.php`](database/migrations/0001_01_01_000001_create_cache_table.php:1) | framework cache |
| `jobs` | [`0001_01_01_000002_create_jobs_table.php`](database/migrations/0001_01_01_000002_create_jobs_table.php:1) | framework queue |
| `settings` | [`2026_08_11_142519`](database/migrations/2026_08_11_142519_create_settings_table.php:1) | key (unique), value NOT NULL, type, group (added later) |
| `roles` | [`2026_08_11_143717`](database/migrations/2026_08_11_143717_create_roles_table.php:1) | id, name, slug unique, description |
| `model_has_roles` | [`2026_08_11_143718`](database/migrations/2026_08_11_143718_create_model_has_roles_table.php:1) | model_type, model_id, role_id; polymorphic, used by `User::roles()` with `wherePivot('model_type', self::class)` — [`User.php`](app/Models/User.php:36) |
| `media` | [`2026_08_11_144256`](database/migrations/2026_08_11_144256_create_media_table.php:1) | user_id nullable FK set null, disk (default 'local'), directory, filename, original_filename, mime_type, extension, size, width, height, type ('image' default), alt/title/caption/description, metadata json |
| `pages` | [`2026_08_11_144632`](database/migrations/2026_08_11_144632_create_pages_table.php:1) | user_id, title, slug, excerpt, content, status, is_homepage (unique-ish enforced by model boot), layout, blocks json |
| `blocks` | [`2026_08_11_144745`](database/migrations/2026_08_11_144745_create_blocks_table.php:1) | block content entity |
| `navigations` | [`2026_08_11_150120`](database/migrations/2026_08_11_150120_create_navigations_table.php:1) | parent_id self-FK, title, url, page_id, type, target, icon, menu_location, order, status, css_class |
| `properties` | [`2026_08_11_151813`](database/migrations/2026_08_11_151813_create_properties_table.php:1) + [`2026_08_12_000000`](database/migrations/2026_08_12_000000_refactor_units_to_property_types.php:1) + [`2026_08_18_000000`](database/migrations/2026_08_18_000000_add_property_detail_fields.php:1) + [`2026_08_21_000000`](database/migrations/2026_08_21_000000_add_max_guests_to_properties_table.php:1) | id, name, slug unique, description, address, city, province, postal_code, latitude decimal(10,8), longitude decimal(11,8), featured_image_id FK->media set null, status default 'draft', meta_title, meta_description, is_featured bool, order, **json**: unit_types, weekend_days, prices, photo_categories, required_documents, nearby_places; max_days, checkin_time, checkout_time, checkin_method, max_guests; softDeletes |
| `units` | [`2026_08_11_151815`](database/migrations/2026_08_11_151815_create_units_table.php:1) | **DROPPED** by refactor migration ([`2026_08_12_000000`](database/migrations/2026_08_12_000000_refactor_units_to_property_types.php:16)); also dropped pivot `amenity_unit` |
| `amenities` | [`2026_08_11_151816`](database/migrations/2026_08_11_151816_create_amenities_table.php:1) | name, slug, icon, category, description, is_active; pivot `amenity_property` |
| `bookings` | [`2026_08_11_162521`](database/migrations/2026_08_11_162521_create_bookings_table.php:1) + [`2026_08_11_173000`](database/migrations/2026_08_11_173000_add_notes_to_bookings_table.php:1) + [`2026_08_20_000002`](database/migrations/2026_08_20_000002_add_voucher_to_bookings_table.php:1) + [`2026_08_24_000000`](database/migrations/2026_08_24_000000_add_access_token_to_bookings_table.php:1) | id, property_id FK cascade, **unit_id dropped**, booking_type default 'daily' (daily/transit/weekly/monthly), unit_type nullable, duration_hours nullable, customer_name/email/phone/whatsapp, check_in/check_out datetime nullable, guests int, code unique (BK-YYYYMMDD-XXXX), access_token varchar(64) **unique** (FIND-001 backfill), message, notes, status enum('pending','confirmed','cancelled','completed') default pending, whatsapp_status default 'pending', whatsapp_sent_at, total_price decimal(15,2), deposit_amount decimal(15,2), price_breakdown json, metadata json, voucher_id FK nullable, voucher_discount int; softDeletes |
| `seo_metadata` | [`2026_08_11_170000`](database/migrations/2026_08_11_170000_create_seo_metadata_table.php:1) | seoable_id/seoable_type polymorphic, meta_title, meta_description, open_graph json, twitter json, canonical_url, index_status bool |
| `redirects` | [`2026_08_11_170001`](database/migrations/2026_08_11_170001_create_redirects_table.php:1) | from_url (unique), to_url, status_code int |
| `categories` | [`2026_08_11_171000`](database/migrations/2026_08_11_171000_create_categories_table.php:1) | blog categories (name, slug) |
| `tags` | [`2026_08_11_171001`](database/migrations/2026_08_11_171001_create_tags_table.php:1) | blog tags |
| `posts` | [`2026_08_11_171002`](database/migrations/2026_08_11_171002_create_posts_table.php:1) | title, slug, content, excerpt, status, published_at, user_id, category_id, featured_image; morphOne seo |
| `post_tag` | [`2026_08_11_171003`](database/migrations/2026_08_11_171003_create_post_tag_table.php:1) | pivot post_id/tag_id |
| `user_activity_logs` | [`2026_08_11_173100`](database/migrations/2026_08_11_173100_create_user_activity_logs_table.php:1) | activity audit for `log_activity()` helper |
| `property_photos` | [`2026_08_12_010000`](database/migrations/2026_08_12_010000_create_property_photos_table.php:1) | property_id FK cascade, media_id FK cascade, category, sort_order; index (`property_id`, `category`) |
| `promo_rates` | [`2026_08_20_000000`](database/migrations/2026_08_20_000000_create_promo_rates_table.php:1) | property_id FK, name, applies_to (weekday/weekend/custom/all), active_days json, start_time/end_time, price int, booking_type, duration_hours, is_active |
| `vouchers` | [`2026_08_20_000001`](database/migrations/2026_08_20_000001_create_vouchers_table.php:1) | code unique, name, discount_type ('percent'/'fixed'), discount_value decimal, min_booking_amount, max_discount_amount, usage_limit, used_count default 0, valid_from/valid_until date, is_active bool; softDeletes |
| `languages` | [`2026_08_26_000001`](database/migrations/2026_08_26_000001_create_languages_table.php:1) | code, name, native_name, flag_emoji, flag_code, is_active, is_default, sort_order |
| `currency_rates` | [`2026_08_26_000002`](database/migrations/2026_08_26_000002_create_currency_rates_table.php:1) | from_currency default 'IDR', to_currency, rate decimal(18,6), source, fetched_at; unique(from_currency, to_currency) |

### Model relationships (authoritative)
- `Property`: `belongsTo(Media,'featured_image_id')`, `belongsToMany(Amenity,'amenity_property')`, `hasMany(PropertyPhoto)`, `hasMany(PromoRate)`, `hasMany(Booking)`, `hasBookingType()`, `getBookingMethods()`, `faqs()` — [`Property.php`](app/Models/Property.php:221).
- `Booking`: `belongsTo(Property)`, `belongsTo(Voucher)`, scopes `pending/confirmed/cancelled/completed`, `isActive()`, `isPastDue()` (BUG-017 null guard) — [`Booking.php`](app/Models/Booking.php:65).
- `User`: `belongsToMany(Role,'model_has_roles','model_id','role_id')->wherePivot('model_type',self::class)`, `hasRole()`, `isAdmin()` (BUG-025: super-admin + admin aliases) — [`User.php`](app/Models/User.php:36).
- `Media`: `belongsTo(User)`, `getUrlAttribute()`, `getThumbnailUrlAttribute()`, `deleteFile()` — [`Media.php`](app/Models/Media.php:49).
- `Post`: `belongsTo(User,'user_id') author`, `belongsTo(Category)`, `belongsToMany(Tag,'post_tag')`, `morphOne(SeoMetadata)` — [`Post.php`](app/Models/Post.php:29).
- `Redirect`: booted `saved` → `Cache::forget('redirects')` — [`Redirect.php`](app/Models/Redirect.php:36).
- `Page`: booted `creating` enforces single `is_homepage` — [`Page.php`](app/Models/Page.php:42).
- `Voucher`: `setCodeAttribute` uppercases; `isValid()`; `calculateDiscount()` respects max cap; `hasMany(Booking)` — [`Voucher.php`](app/Models/Voucher.php:42).
- `PromoRate`: `belongsTo(Property)`, `matchesCheckin(dayOfWeek, time, bookingType, durationHours)` — [`PromoRate.php`](app/Models/PromoRate.php:44).

### Indexes/unique constraints of note
`properties.slug` unique; `bookings.code` unique; `bookings.access_token` unique; `media` has no unique constraints; `property_photos` index (property_id, category); `currency_rates` unique (from,to); `settings.key` unique; `roles.slug` unique; `redirects.from_url` unique; `users.email` unique.

---

## 5. BUSINESS LOGIC — CANONICAL LOCATIONS

| Concern | Canonical location | Method | Notes |
|---------|--------------------|--------|-------|
| Booking price total (all types) | [`BookingPricingService`](app/Services/BookingPricingService.php:30) | `calculate()` | Only place that computes totals. Transit uses check-in day for weekday/weekend; daily per-night; weekly=7 nights, monthly=30 nights. Weekend days resolved from property `weekend_days` else global settings (`resolveWeekendDays`). |
| Transit hour buckets | [`BookingPricingService`](app/Services/BookingPricingService.php:13) | `TRANSIT_BUCKETS = [3,6,9,12,24]`, `normalizeTransitHours()` | Rounds up; throws InvalidArgumentException on null/0 (BUG-008). |
| Promo rate application | [`BookingPricingService`](app/Services/BookingPricingService.php:54) | inside `calculate()` | Validates promo belongs to property + `is_active`. |
| Voucher discount | [`Voucher::calculateDiscount()`](app/Models/Voucher.php:77) | method on model | Percent vs fixed, capped by max_discount_amount and by booking amount. Voucher validity via `Voucher::isValid()`. |
| Voucher resolution + lock | [`BookingService::create()`](app/Services/BookingService.php:51) | static create | FIND-003: resolves voucher by **code** with `lockForUpdate()` inside a DB transaction; rejects numeric voucher_id alone; increments used_count in same transaction. |
| Booking code generation | [`BookingService::generateCode()`](app/Services/BookingService.php:22) | static | BK-YYYYMMDD-XXXX, transactional with lockForUpdate (BUG-006). |
| Booking creation | [`BookingService::create()`](app/Services/BookingService.php:51) | static | Full flow incl. pricing snapshot, notifications (created). |
| Booking status changes | [`BookingService`](app/Services/BookingService.php:237) | `confirm()`, `cancel()`, `complete()` | Each updates status and fires notification event via [`BookingNotificationService`](app/Services/BookingNotificationService.php:30) (`booking.confirmed/cancelled/completed/created`). |
| Booking status enum | `bookings.status` | enum('pending','confirmed','cancelled','completed') | Migration [`2026_08_11_162521`](database/migrations/2026_08_11_162521_create_bookings_table.php:37). |
| Payment / deposit | No payment gateway. `total_price`, `deposit_amount` stored on booking. Payment status = booking status. | — | No Payment model, no payment provider config in [`config/services.php`](config/services.php:1) (only postmark/resend/ses/slack). |
| Notifications (webhook) | [`BookingNotificationService`](app/Services/BookingNotificationService.php:20) | `send()` | Settings `notification_webhook`, `notification_webhook_secret`; fire-and-forget, logs always. |
| Availability | No availability table/model. Server-side conflict check **exists** via [`BookingService::validateAvailability()`](app/Services/BookingService.php:204) (overlap query on `bookings` with `lockForUpdate()`; rejects overlapping windows for the same `property_id` + `unit_type` with `status != 'cancelled'`). | — | Pinned by `SecurityTest::test_overlapping_booking_is_rejected` ([`tests/Feature/SecurityTest.php:188`](tests/Feature/SecurityTest.php:188)). |
| Cancellation | [`BookingService::cancel()`](app/Services/BookingService.php:248) | static | sets status cancelled + notifies. |
| Currency conversion (display) | [`CurrencyRateService`](app/Services/CurrencyRateService.php:1) + [`FetchCurrencyRates`](app/Console/Commands/FetchCurrencyRates.php:1) | fetchAndStore | Scheduler every 6h. Session `display_currency`. |

### Duplication risk (verified)
- Public booking form partial [`resources/views/properties/_booking-form.blade.php`](resources/views/properties/_booking-form.blade.php:1) and pricing table partial [`resources/views/properties/_pricing-table.blade.php`](resources/views/properties/_pricing-table.blade.php:1) display prices by reading `$property->prices` JSON and calling helper logic; the **authoritative calculation** remains `BookingPricingService::calculate()`. The admin pricing partials ([`resources/views/admin/properties/_pricing.blade.php`](resources/views/admin/properties/_pricing.blade.php:1), [`resources/views/admin/settings/partials/_pricing.blade.php`](resources/views/admin/settings/partials/_pricing.blade.php:1)) are input editors for the same `prices` JSON structure (keys `night_wd/night_we`, `t3_wd/t3_we`, `t6_*`, `t9_*`, `t12_*`, `t24_*`, `weekly`, `monthly`). Price keys enumerated in [`Property::hasBookingType()`](app/Models/Property.php:226).
- Booking creation goes through [`BookingController@store`](app/Http/Controllers/BookingController.php:21) → `BookingService::create()`. No other creation path confirmed.
- Note: [`config/services.php`](config/services.php:1) contains no pricing-related config; pricing settings live in `settings` table (e.g. deposit policy, weekend days) read via `SettingsService`.

---

## 6. CMS / ADMIN IMPLEMENTATION

Confirmed: **Custom Blade admin** (not Filament/Nova). No Filament, Livewire, or Inertia packages in composer.json.

### Admin-managed entities (from `routes/web.php` routes under `admin` prefix + middleware `['auth','admin']` group)
- Properties (CRUD via [`PropertyController`](app/Http/Controllers/PropertyController.php:1) — create/edit/index/delete; admin views at [`resources/views/admin/properties`](resources/views/admin/properties))
- Amenities ([`AmenityController`](app/Http/Controllers/AmenityController.php:1))
- Blocks ([`BlockController`](app/Http/Controllers/BlockController.php:1))
- Navigations ([`NavigationController`](app/Http/Controllers/NavigationController.php:1))
- Pages ([`PageController`](app/Http/Controllers/PageController.php:1))
- Posts (blog) ([`PostController`](app/Http/Controllers/PostController.php:1))
- Categories, Tags ([`CategoryController`](app/Http/Controllers/CategoryController.php:1), [`TagController`](app/Http/Controllers/TagController.php:1))
- Media ([`MediaController`](app/Http/Controllers/MediaController.php:1))
- Bookings (admin index/show) ([`BookingController`](app/Http/Controllers/BookingController.php:1))
- Promo Rates ([`PromoRateController`](app/Http/Controllers/PromoRateController.php:1))
- Vouchers ([`VoucherController`](app/Http/Controllers/VoucherController.php:1))
- Redirects ([`RedirectController`](app/Http/Controllers/RedirectController.php:1))
- Settings ([`SettingsController`](app/Http/Controllers/SettingsController.php:1)) — general, pricing, SEO, mail, captcha, theme, homepage, footer, integrations, currency API, git, email templates
- SEO settings ([`Admin\SlugSettingsController`](app/Http/Controllers/Admin/SlugSettingsController.php:1))
- Users ([`Admin\UserController`](app/Http/Controllers/Admin/UserController.php:1))
- Dashboard ([`Admin\DashboardController`](app/Http/Controllers/Admin/DashboardController.php:1))
- Backup ([`Admin\BackupController`](app/Http/Controllers/Admin/BackupController.php:1))
- Languages ([`Admin\LanguageController`](app/Http/Controllers/Admin/LanguageController.php:1))
- Currency rates ([`Admin\CurrencyRateController`](app/Http/Controllers/Admin/CurrencyRateController.php:1))

### Boundaries
- **Public website**: property listing/detail, blog, pages (catch-all), contact form, search suggest, sitemap/robots, locale/currency switchers.
- **Admin UI**: all routes in `admin` group with `auth` + `admin` middleware. Lists all above entities.
- **API**: NO dedicated API routes. Some controllers return JSON conditionally (`$request->wantsJson()`) — e.g. [`MediaController`](app/Http/Controllers/MediaController.php:69), [`BookingController`](app/Http/Controllers/BookingController.php:42), [`SearchController`](app/Http/Controllers/SearchController.php:1). The `bootstrap/app.php` configures `api/*` to render JSON on exceptions but no API routes file is registered.
- **Background jobs**: NO custom Jobs/queue classes. Only `currency:fetch` command. The `GEOAPIFY-Nearby-Places-Integration.md` proposes a job but it is **not implemented**.
- **Console**: only `currency:fetch` + `inspire`.

---

## 7. FRONTEND / UX

Confirmed: **Blade + Tailwind CSS v3 + Alpine.js v3 + Hotwired Turbo Drive** (Turbo handles form submissions and link clicks). NO Livewire, Vue, React, or Inertia.

### Layouts
- [`layouts/frontend.blade.php`](resources/views/layouts/frontend.blade.php:1) (532 lines) — main public layout. Pulls settings (site name, logo, favicon, social links, colors, dark mode, WhatsApp, etc.), loads `Navigation` for main menu, renders SEO component, analytics, footer. Inline SVG social icons. Responsive nav with hamburger toggle.
- [`layouts/admin.blade.php`](resources/views/layouts/admin.blade.php:1) — admin panel layout.
- [`layouts/app.blade.php`](resources/views/layouts/app.blade.php:1) — standard authenticated layout (Breeze).
- [`layouts/guest.blade.php`](resources/views/layouts/guest.blade.php:1) — guest layout (Breeze).
- [`layouts/navigation.blade.php`](resources/views/layouts/navigation.blade.php:1) — Breeze nav.

### Key JS
- [`resources/js/app.js`](resources/js/app.js:1) (1500 lines) — Turbo setup, Alpine, dynamic script loader (`loadScript`), escape HTML helper, auto-scroll, search anything, plus all the property detail page interactions (date picker, booking calculator, gallery lightbox, etc.). Turbo Drive progress bar enabled.

### Property detail page
- [`resources/views/properties/show.blade.php`](resources/views/properties/show.blade.php:1) (1737 lines) — largest view. Sections: hero gallery, property info, booking form, pricing table, amenities, nearby places with map, policies (check-in, check-out, required documents), FAQ, nearby properties. Uses `displayMode` setting (`both`, `pricing_only`, `form_only`).

### Responsive strategy
Tailwind responsive classes (sm/md/lg/xl). Mobile hamburger menu. Property grid adapts columns. No separate mobile view.

---

## 8. PERFORMANCE

### Caching
- [`SettingsService`](app/Services/SettingsService.php:10) — `static $cache` array, lazy-loaded once per request.
- [`Redirect`](app/Models/Redirect.php:37) — booted `saved` flushes `Cache::forget('redirects')`. [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php:30) reads cached redirects: `Cache::remember('redirects', 86400, ...)`.
- `Navigation` model has `Cache::forget('navigations')` on save in its boot method (confirmed via [`Navigation.php`](app/Models/Navigation.php:1)).
- Cache driver: file in dev, `database` default in config.

### Eager loading
- [`PropertyController::publicShow()`](app/Http/Controllers/PropertyController.php:150) — `$property->load(['featuredImage','amenities','photos.media','promoRates'=>fn=>where('is_active',true)])`.
- [`frontend.blade.php`](resources/views/layouts/frontend.blade.php:30) — `Navigation::with(['page','children'=>fn=>where('status','active'),'children.page'])`.
- [`MediaController::index()`](app/Http/Controllers/MediaController.php:45) — `Media::with('user')`.

### Queue
- `.env` uses `sync`; config default is `database`. No custom queued jobs. Scheduler runs `currency:fetch` every 6h.

### Database indexes
- `property_photos.index(property_id, category)` — [`migration`](database/migrations/2026_08_12_010000_create_property_photos_table.php:31).
- `currency_rates.unique(from_currency, to_currency)` — [`migration`](database/migrations/2026_08_26_000002_create_currency_rates_table.php:18).
- Unique indexes on `properties.slug`, `bookings.code`, `bookings.access_token`, `users.email`, `roles.slug`, `redirects.from_url`, `settings.key`.

### Image handling
- `Media::getThumbnailUrlAttribute()` looks for `directory/thumbnails/filename` — [`Media.php`](app/Models/Media.php:65). No confirmation of actual thumbnail generation pipeline (likely done on upload via `MediaController` or `upload_file` helper).
- `upload_file()` helper in [`app/Helpers/upload.php`](app/Helpers/upload.php:31) — MIME-based extension, standardized naming.

### Route/config caching
- [`config/installer.php`](config/installer.php:5) — explicitly notes config-cache awareness (SEC-11: reads `env()` at config time so it survives `config:cache`). No evidence of route caching being used.

---

## 9. SECURITY

### Auth
- Laravel Breeze (session-based). [`config/auth.php`](config/auth.php:1) — `web` guard, `User` model.
- Admin authorization: [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php:1) — calls `$request->user()->isAdmin()`. `User::isAdmin()` checks for `super-admin` or `admin` role slugs (BUG-025) — [`User.php`](app/Models/User.php:60).

### Mass assignment
- `$fillable` defined on all models. `User` uses PHP 8 `#[Fillable]` attribute. No `$guarded` found. Safe.

### FormRequest validation
- [`PropertyRequest`](app/Http/Requests/PropertyRequest.php:48) — validates name, slug (regex `^[a-z0-9]+(?:-[a-z0-9]+)*$`), prices, nearby_places, etc. Strips thousands separators from money inputs in `prepareForValidation()`.
- [`BookingRequest`](app/Http/Requests/BookingRequest.php:1) — booking validation.
- [`MediaRequest`](app/Http/Requests/MediaRequest.php:1) — upload validation.

### CSRF
- `csrf_token()` in meta tag and `@csrf` in forms. Laravel auto-applies VerifyCsrfToken.

### Middleware security chain
- [`CheckInstalled`](app/Http/Middleware/CheckInstalled.php:1) — redirects to install if not installed.
- [`ForceHttps`](app/Http/Middleware/ForceHttps.php:1) — force HTTPS (tested in [`tests/Feature/ForceHttpsTest.php`](tests/Feature/ForceHttpsTest.php:1)).
- [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php:1) — cached redirects, skips admin/redirects and AJAX.
- [`LocaleMiddleware`](app/Http/Middleware/LocaleMiddleware.php:1) — locale from session.
- [`SecurityHeaders`](app/Http/Middleware/SecurityHeaders.php:1) — X-Frame-Options SAMEORIGIN, X-Content-Type-Options nosniff, Referrer-Policy strict-origin-when-cross-origin. **No CSP header**.
- [`VerifyCaptcha`](app/Http/Middleware/VerifyCaptcha.php:1) — optional captcha on contact form.

### File uploads
- [`MediaController`](app/Http/Controllers/MediaController.php:18) — allowed MIMEs: `image/jpeg, image/png, image/webp, image/gif, image/svg+xml, application/pdf`. SVG allowed (flagged as XSS risk in security audit). 10MB URL import limit. Uploads use `upload_file()` helper with MIME-based extension mapping — [`app/Helpers/upload.php`](app/Helpers/upload.php:50).
- Thumbnails stored in `directory/thumbnails/`. Deletion via `Media::deleteFile()`.

### Signed URLs
- [`config/filesystems.php`](config/filesystems.php:39) — `local` disk has `'serve' => true` with `'url' => '/private'` (signed URLs). Public disk also `'serve' => true` at `/storage/{path}`.

### Known security concerns (from [`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md))
- **SEC-01**: `APP_DEBUG=true` with `APP_ENV=production` leaks stack traces. **CRITICAL**.
- **SEC-02**: `cyberstrike.json` (114KB) — original finding: "tracked in git, not in `.gitignore`". **CORRECTION (verified):** `cyberstrike.json` IS listed in [`.gitignore`](.gitignore:51). It may be present in the working tree and/or historically tracked — verify it is not committed with secrets. **CRITICAL**.
- **SEC-03**: Git dashboard (`SettingsController` gitPull/gitStatus) uses `exec()` via Symfony Process (mitigated by array args, not shell). **HIGH**.
- **SEC-04**: `contact_map_embed` rendered raw `{!! $mapEmbed !!}` — mitigated by [`MapEmbedService`](app/Services/MapEmbedService.php:1) (extracts URL, validates host/path, never echoes raw iframe). **HIGH** (fix applied in service).
- SVG upload → stored XSS risk. **HIGH**.
- Analysis: [`tests/Feature/MediaUrlImportSsrfTest.php`](tests/Feature/MediaUrlImportSsrfTest.php:1) tests URL import SSRF.

### API keys / env usage
- All secrets read from `.env` via `env()` in config files. [`config/services.php`](config/services.php:1) — postmark, resend, ses, slack. [`config/installer.php`](config/installer.php:1) — `INSTALLER_ALLOWED_IPS`, `INSTALLER_TOKEN`. No hardcoded credentials.

---

## 10. SEO

### SEO component
- [`resources/views/components/seo.blade.php`](resources/views/components/seo.blade.php:1) — calls `SeoService::metaTagsArray($model)` and `SeoService::renderMetaTags($seoData)`. Rendered in layout head.

### SeoService
- [`SeoService`](app/Services/SeoService.php:1) (412 lines) — `title()`, `metaTagsArray()`, `renderMetaTags()`, `structuredData()`. Handles: meta title/description, canonical URL, Open Graph, Twitter Card, structured data (JSON-LD). Title normalization strips duplicate site-name suffix.

### SchemaService
- [`SchemaService`](app/Services/SchemaService.php:1) — generates structured data (JSON-LD). Used by `SeoService`.

### SeoMetadata model
- Polymorphic `seoable` on posts, pages, and potentially other entities. Fields: `meta_title`, `meta_description`, `open_graph` (json), `twitter` (json), `canonical_url`, `index_status` (bool).

### Sitemap / Robots
- [`SeoController@sitemap`](app/Http/Controllers/SeoController.php:1) — `GET /sitemap.xml` → [`SitemapService`](app/Services/SitemapService.php:1).
- [`SeoController@robots`](app/Http/Controllers/SeoController.php:1) — `GET /robots.txt` → [`RobotsService`](app/Services/RobotsService.php:1). Disallows /admin, /install, /login, /logout, /register, /profile, /dashboard. Overridable via `robots_txt` setting.

### Redirects
- [`RedirectMiddleware`](app/Http/Middleware/RedirectMiddleware.php:1) — cached lookup of `from_url` → `to_url` with `status_code`. Skips admin/redirects paths and AJAX. Admin management at [`resources/views/admin/redirects`](resources/views/admin/redirects).
- [`Redirect`](app/Models/Redirect.php:1) — auto-flushes cache on save.

### Slugs
- Configurable slugs via [`Admin\SlugSettingsController`](app/Http/Controllers/Admin/SlugSettingsController.php:1) + [`resources/views/admin/slug-settings/index.blade.php`](resources/views/admin/slug-settings/index.blade.php). Uses `slug()` helper — [`app/Helpers/slug.php`](app/Helpers/slug.php:1).
- Property slug: `{property:slug}` route-model binding — [`routes/web.php`](routes/web.php:32).
- Blog slug: `slug('slug_blog', 'blog')` — [`routes/web.php`](routes/web.php:35).
- Properties slug pattern validated: `^[a-z0-9]+(?:-[a-z0-9]+)*$` — [`PropertyRequest.php`](app/Http/Requests/PropertyRequest.php:58).

### Canonical / OG / structured data
- Handled by `SeoService::renderMetaTags()` and `SchemaService`. No direct `<link rel="canonical">` or `og:` tags found in Blade templates; they are generated by the service. Verified via search for `canonical|og:|twitter:card` in views — no results, confirming `SeoService` generates them programmatically.

---

## 11. MEDIA

### Model
- [`Media`](app/Models/Media.php:8) — URL generation via `Storage::disk($this->disk)->url($this->directory.'/'.$this->filename)`. Thumbnail accessor checks `directory/thumbnails/filename` first, else falls back to `url`. `deleteFile()` deletes both file and thumbnail. BelongsTo User.

### Controller
- [`MediaController`](app/Http/Controllers/MediaController.php:1) (786 lines) — upload (allowed MIME list), URL import (10MB max, SSRF-guarded), browse (folder/type/search filter, paginated 24, JSON for picker), edit (alt/title/caption), delete.

### Upload helper
- [`app/Helpers/upload.php`](app/Helpers/upload.php:31) — `upload_file()`: folder `{base_folder}/{sub_folders...}`, filename `{prefix}_{category}_{DDMMYYYY}_{random8}.{ext}`. Extension derived from MIME map (jpg/png/svg/pdf/doc/docx/mp4/avi). Uses `Str::slug` for folder names.

### Storage disks
- [`config/filesystems.php`](config/filesystems.php:31): `local` (private, root `storage/app/private`, url `/private`, `serve: true` for signed URLs), `public` (root `storage/app/public`, url `{APP_URL}/storage`, `serve: true` — Laravel serves `/storage/{path}` directly, avoiding the Windows junction 403 issue), `s3` (cloud, configured but not default).

### Admin views
- [`resources/views/admin/media`](resources/views/admin/media) — index (with JSON picker support), create (upload + URL import), edit, show.

### Property photos
- [`PropertyPhoto`](app/Models/PropertyPhoto.php:7) — `property_id` + `media_id` + `category` + `sort_order`; index (property_id, category). Gallery categories from `properties.photo_categories` JSON.
- Public display partial: [`resources/views/properties/_photos.blade.php`](resources/views/properties/_photos.blade.php:1); admin editor: [`resources/views/admin/properties/_photos.blade.php`](resources/views/admin/properties/_photos.blade.php:1).

### URL generation
- `$media->url` and `$media->thumbnail_url` accessors (as above). Public views use these accessors; lightbox builds array of `{url, category, name}` in [`show.blade.php`](resources/views/properties/show.blade.php:10).

---

## 12. MAPS / NEARBY PLACES (GEOAPIFY)

**CRITICAL FINDING**: The [`docs/GEOAPIFY-Nearby-Places-Integration.md`](docs/GEOAPIFY-Nearby-Places-Integration.md:1) document (1547 lines) is a **design proposal/specification**, NOT an implemented feature.

### What actually exists in code
- **No** `NearbyPlacesService` (does not exist in `app/Services`).
- **No** `Place` model, `places` table, `property_places` table, or `create_property_places_table` migration (migration list confirmed — no such files).
- **No** queued job (`ShouldQueue`) for fetching nearby places.
- **No** Geoapify API config in [`config/services.php`](config/services.php:1).
- Nearby places ARE stored as **manual JSON** `nearby_places` on the `properties` table (array of `{name, category, distance_km, lat?, lng?}`). Category enum validated in [`PropertyRequest`](app/Http/Requests/PropertyRequest.php:83): `Nearby Places, Transportation, Entertainment/Attraction, Others`.
- [`PropertyController::publicShow()`](app/Http/Controllers/PropertyController.php:162) computes **Haversine distances** (`haversineMeters`, `formatDistance`) for places that carry `lat`/`lng`, else falls back to stored `distance_km`. Enriches `distance_m`/`distance_formatted` and re-groups by category for the view.
- Related properties map: `nearbyProperties()` — 3 nearest by Haversine, else same-city, else latest — [`PropertyController`](app/Http/Controllers/PropertyController.php:197).
- Map embed: [`MapEmbedService`](app/Services/MapEmbedService.php:1) — validates Google Maps embed URLs against allowlist (`google.*` host + `/maps` path), renders escaped iframe server-side. Used for contact map (`contact_map_embed` setting). Tested in [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php:1).
- `GeoLocaleService` — geolocation/locale service (currency/locale detection; not nearby places).

### Pipeline as designed in the spec (NOT implemented)
Proposed: `NearbyPlacesService` → Geoapify Places API → queued job → `places`/`property_places` tables → cache → property detail page. **All of this is aspirational.** Later subtasks must NOT assume the pipeline exists.

---

## 13. DEPLOYMENT / INSTALLER / GIT

### Queue / scheduler
- [`routes/console.php`](routes/console.php:12) — `Schedule::command('currency:fetch')->everySixHours()->withoutOverlapping()->runInBackground()`.
- [`config/queue.php`](config/queue.php:16) — default `database`; `.env` overrides to `sync`.
- **No queue worker/supervisor config in repo** (no Procfile, no supervisor conf, no `.github/workflows`).

### Session
- [`config/session.php`](config/session.php:21) — default `database`; `.env` uses `file`. Lifetime 120 min.

### Git
- `.gitattributes` — present at root.
- `.gitignore` — covers `.env*`, `/vendor`, `composer.lock`, `/node_modules`, `/public/build`, storage keys, IDE files. **CORRECTION (verified):** `cyberstrike.json` **IS** ignored — it is listed in [`.gitignore`](.gitignore:51) (the original "NOT ignored" note under SEC-02 was inaccurate; verify the file is not committed with secrets).
- `.npmrc` — `ignore-scripts=true`, `audit=true`.

### CI
- **No `.github/` directory exists** — confirmed by failed listing. No CI workflows.

### Installer
- [`routes/install.php`](routes/install.php:1) — 6-step wizard: requirements, application, database (test-connection), admin creation, website config, finish; plus fresh-reset.
- [`config/installer.php`](config/installer.php:1) — `INSTALLER_ALLOWED_IPS`, `INSTALLER_TOKEN` (SEC-11 config-cache safe).
- [`ProtectInstaller`](app/Http/Middleware/ProtectInstaller.php:1) — blocks installer unless localhost/whitelisted/token.
- Views under `resources/views/install/` (referenced by [`InstallerController`](app/Http/Controllers/InstallerController.php:1)).
- Docs: [`docs/INSTALLER.md`](docs/INSTALLER.md), [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), [`docs/DEPLOYMENT-CPANEL.md`](docs/DEPLOYMENT-CPANEL.md), [`docs/DEPLOY-CPANEL-ID.md`](docs/DEPLOY-CPANEL-ID.md).

### Git settings partial
- [`resources/views/admin/settings/partials/_git.blade.php`](resources/views/admin/settings/partials/_git.blade.php) — admin Git dashboard (status, fetch, pull). Backed by [`SettingsController`](app/Http/Controllers/SettingsController.php:440) gitStatus/gitPull/gitFetch using Symfony Process (SEC-03).

---

## 14. EXISTING AGENT / DOCS CONVENTIONS

### Existing agent config
- **NO** `AGENTS.md`, **NO** `CLAUDE.md`, **NO** `.cursor/` (ignored in `.gitignore`), **NO** `.agents/`, **NO** `.github/`. This is greenfield for the AI Agent OS.

### docs/ inventory (22 files)
`ADMIN_GUIDE.md`, `ANALYTICS.md`, `ARCHITECTURE.md`, `BOOKING.md`, `bug-audit-report.md`, `DATABASE.md`, `DEPLOY-CPANEL-ID.md`, `DEPLOYMENT-CPANEL.md`, `DEPLOYMENT.md`, `DEVELOPER_GUIDE.md`, `FAQ.md`, `GEOAPIFY-Nearby-Places-Integration.md`, `INSTALLER.md`, `qa-ux-report.md`, `ROADMAP.md`, `security-audit-2026-08-27.md`, `security-audit-report.md`, `SECURITY.md`, `SEO.md`, `TESTING.md`, `THEMING.md`, `TROUBLESHOOTING.md`, `USER_GUIDE.md`. Plus `IDEA.md` and `README.md` at root, and `cyberstrike.json` (tooling config, flagged SEC-02).

### Commit message convention
Not fully discernible from this read-only pass (git log not executed). Code comments use `BUG-XXX FIX:` / `FIND-XXX:` / `SEC-XX:` prefixes (e.g. BUG-002, BUG-006, BUG-008, BUG-017, BUG-025, FIND-001, FIND-003, SEC-04, SEC-11) — this implies issue-tracker-tagged commit messages referencing bug/finding IDs. **UNCONFIRMED** — verify from `git log` directly.

### Language conventions
- UI copy is Indonesian (id) primary; `lang/en.json` + `lang/id.json` JSON translation files. `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=id`.
- Code comments mix English and Indonesian (many bug-fix comments in Indonesian).
- Money format IDR; `display_currency` session switcher.

---

## CONFIRMED STACK SUMMARY

| Layer | Value | Source |
|-------|-------|--------|
| Laravel | 13.x (`^13.8`) | [`composer.json`](composer.json:10) |
| PHP | `^8.3` | [`composer.json`](composer.json:9) |
| DB | MySQL in `.env` (`sa_cms`); sqlite `:memory:` for tests; config default sqlite | [`.env`](.env:29), [`phpunit.xml`](phpunit.xml:26) |
| Frontend build | Vite 8 + laravel-vite-plugin (NOT Mix) | [`package.json`](package.json:15) |
| CSS | Tailwind v3 + forms plugin | [`tailwind.config.js`](tailwind.config.js:1) |
| JS | Alpine 3 + Hotwired Turbo 8 (Turbo Drive). No Vue/React/Livewire/Inertia | [`package.json`](package.json:12) |
| Queue | database (config) / sync (env) | [`config/queue.php`](config/queue.php:16), [`.env`](.env:50) |
| Cache | database (config) / file (env) | [`config/cache.php`](config/cache.php:18), [`.env`](.env:53) |
| Storage | local (private+public, both `serve:true`), s3 optional | [`config/filesystems.php`](config/filesystems.php:31) |
| Auth | Laravel Breeze (session) + custom role middleware `admin` | [`composer.json`](composer.json:16), [`bootstrap/app.php`](bootstrap/app.php:44) |
| Testing | PHPUnit 12 (Feature/Unit) | [`phpunit.xml`](phpunit.xml:7) |
| CMS | Custom Blade admin (no Filament/Nova/Livewire) | [`resources/views/admin`](resources/views/admin) |

## DOMAIN MODEL INVENTORY (existing only)

Property, PropertyPhoto, Amenity, Booking, Voucher, PromoRate, Media, Page, Block, Navigation, Post, Category, Tag, User, Role, SeoMetadata, Redirect, Setting, Language, CurrencyRate, ActivityLog — all in [`app/Models`](app/Models).

## CANONICAL BUSINESS LOGIC LOCATIONS

| Concern | File | Method |
|---------|------|--------|
| Booking total / pricing | [`BookingPricingService`](app/Services/BookingPricingService.php:30) | `calculate()` |
| Voucher discount | [`Voucher`](app/Models/Voucher.php:77) | `calculateDiscount()` |
| Booking creation + voucher apply | [`BookingService`](app/Services/BookingService.php:51) | `create()` |
| Booking code | [`BookingService`](app/Services/BookingService.php:22) | `generateCode()` |
| Status transitions | [`BookingService`](app/Services/BookingService.php:237) | `confirm/cancel/complete()` |
| Notifications | [`BookingNotificationService`](app/Services/BookingNotificationService.php:30) | `send()` |
| Availability | [`BookingService`](app/Services/BookingService.php:204) | `validateAvailability()` — overlap query on `bookings` with `lockForUpdate()`; rejects overlapping windows for the same `property_id` + `unit_type` with `status != 'cancelled'` (no `Availability` table/model). |

## SENSITIVE AREAS (do not casually change)

1. [`BookingService`](app/Services/BookingService.php:1) + [`BookingPricingService`](app/Services/BookingPricingService.php:1) — pricing correctness, voucher race, code generation.
2. [`Booking`](app/Models/Booking.php:1) / [`Voucher`](app/Models/Voucher.php:1) / [`Property`](app/Models/Property.php:1) — `$fillable`, casts, status enums, price JSON keys.
3. Installer + middleware chain ([`ProtectInstaller`](app/Http/Middleware/ProtectInstaller.php:1), [`CheckInstalled`](app/Http/Middleware/CheckInstalled.php:1), [`ForceHttps`](app/Http/Middleware/ForceHttps.php:1)) and [`config/installer.php`](config/installer.php:1).
4. [`MapEmbedService`](app/Services/MapEmbedService.php:1) + [`SafeHtmlService`](app/Services/SafeHtmlService.php:1) — XSS mitigations.
5. [`MediaController`](app/Http/Controllers/MediaController.php:1) upload/URL-import + [`upload_file()`](app/Helpers/upload.php:31) — MIME/SSRF/XSS (SVG).
6. [`SettingsController`](app/Http/Controllers/SettingsController.php:1) git endpoints — command execution surface (SEC-03).
7. `settings` table keys — any raw-HTML rendering paths.
8. [`SeoService`](app/Services/SeoService.php:1) / [`SchemaService`](app/Services/SchemaService.php:1) — meta/JSON-LD rendering.
9. `.env` / `cyberstrike.json` — secrets (SEC-01/02).
10. `resources/views/properties/show.blade.php` (1737 lines) + `_booking-form` + `_pricing-table` — must stay consistent with `BookingPricingService` price keys.

## UNCONFIRMED / NEEDS HUMAN VERIFICATION

1. ~~**Availability conflict-checking**: no server-side double-booking prevention was found. Is this intentional (single-operator CMS)?~~ **RESOLVED** — server-side conflict check exists in [`BookingService::validateAvailability()`](app/Services/BookingService.php:204) (overlap query with `lockForUpdate()`, rejects overlapping windows for same `property_id` + `unit_type` with `status != 'cancelled'`). Pinned by `SecurityTest::test_overlapping_booking_is_rejected`.
2. **Geoapify**: spec doc only. Is the automated nearby-places feature desired in this project at all, or is manual JSON the intended design?
3. **`PromotionController` vs `PromoRateController`**: which is canonical? Is `PromotionController` dead?
4. **`UnitFactory`** and any `App\Models\Unit` references — dead code after the units refactor.
5. **Commit message convention** — not verified from `git log` (read-only pass; run `git log --oneline` to confirm BUG-/FIND-/SEC- prefix usage).
6. **Thumbnail generation** — where are `directory/thumbnails/*` created? (search for `thumbnails` in `MediaController`/helpers).
7. **`cyberstrike.json`** — tool config; verify it should remain tracked or be gitignored (SEC-02).
8. **Search suggest** — check [`SearchController`](app/Http/Controllers/SearchController.php:1) implementation details.
9. **`DEBUGBAR_ENABLED=true`** in `.env` — Debugbar enabled in production; confirm intent.
10. **Language switch behavior** — `LocaleMiddleware` + `Language` model; confirm default-language seed.

---

_End of audit findings. Generated read-only; no application code modified. This file is the source of truth for subsequent AGENTS.md / skills / docs subtasks._


