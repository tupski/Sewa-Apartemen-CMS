# Arsitektur Sistem

_System Architecture_

Ikhtisar arsitektur **Artivo CMS** — sistem manajemen sewa apartemen berbasis Laravel untuk **PT KAKARAMA Samudera Group** (deploy sebagai **Lya Rooms**). Dokumen ini menggambarkan **apa yang sebenarnya ada di kode**.

_Overview of the **Artivo CMS** architecture — a Laravel-based apartment rental CMS for **PT KAKARAMA Samudera Group** (deployed as **Lya Rooms**). This document describes **what actually exists in the codebase**._

---

## Ringkasan / _Summary_

- **Tipe**: CMS rental apartemen multi-bahasa — situs web properti dengan sistem booking, blog, dan CMS halaman.
- **Model deploy**: satu instalasi = satu klien; tidak ada multi-tenancy, tidak ada field `tenant_id`.
- **Versi**: `1.0.0`, dibaca via `config('artivo.version')` dari [`config/artivo.php`](../config/artivo.php:21).

## Tumpukan Teknologi / _Technology Stack_

| Lapisan | Teknologi | Sumber |
|---------|-----------|--------|
| Bahasa | PHP `^8.3` | [`composer.json`](../composer.json:9) |
| Framework | Laravel `^13.8` | [`composer.json`](../composer.json:10) |
| Template | Blade | — |
| Interaktivitas | Alpine.js `^3.4.2` | [`package.json`](../package.json:12) |
| Navigasi | Hotwired Turbo `^8.0.23` (Turbo Drive) | [`package.json`](../package.json:21) |
| CSS | Tailwind CSS `^3.1` (v3) | [`package.json`](../package.json:17) |
| Build | Vite `^8.0.0` + `laravel-vite-plugin` `^3.1` | [`package.json`](../package.json:15,18), [`vite.config.js`](../vite.config.js) |
| Database produksi | MySQL | [`config/database.php`](../config/database.php) |
| Database test | SQLite in-memory | [`phpunit.xml`](../phpunit.xml) |
| Auth | Laravel Breeze (session) + middleware role `admin` | [`bootstrap/app.php`](../bootstrap/app.php:44) |
| Admin panel | **Blade admin buatan tangan** — bukan Filament/Nova/Voyager | [`resources/views/layouts/admin.blade.php`](../resources/views/layouts/admin.blade.php) |
| Pengujian | PHPUnit `^12.5.12` (bukan Pest) | [`composer.json`](../composer.json:23) |

> Catatan: versi Laravel yang tertera di beberapa dokumen lama (`docs/ARCHITECTURE.md`) adalah **Laravel 12** — itu sudah usang. Kode saat ini menggunakan **Laravel `^13.8`** (lihat [`composer.json`](../composer.json:10)).

## Peta Direktori / _Directory Layout_

```
app/
├── Console/Commands/          # Artisan commands (git:check-updates, currency:fetch)
├── Helpers/                    # slug.php, upload.php, activity.php (autoload via composer.json)
├── Http/
│   ├── Controllers/            # Frontend + sebagian besar admin (root namespace)
│   │   └── Admin/              # HANYA 6 controller admin (lihat di bawah)
│   ├── Controllers/Auth/       # Breeze auth controllers
│   ├── Middleware/             # admin, protect.installer, captcha, dll.
│   └── Requests/               # FormRequest (validation semua input admin)
├── Jobs/                       # FetchNearbyPlacesJob (satu-satunya job custom)
├── Models/                     # 23 model terkonfirmasi
├── Providers/                  # AppServiceProvider, MailSettingsServiceProvider
├── Services/                   # Business logic (pola Controller-Service)
└── View/Components/            # AppLayout, GuestLayout
bootstrap/app.php               # Konfigurasi middleware, routing, exception
config/                         # Konfigurasi (artivo.php, installer.php, dll.)
database/
├── factories/                  # BookingFactory, PostFactory, PropertyFactory, UserFactory, (UnitFactory mati)
├── migrations/                 # 35 migrasi (perubahan skema harus lewat sini)
└── seeders/                    # RoleSeeder, SettingSeeder, PropertySeeder, dll.
lang/                           # en.json, id.json (terjemahan UI)
public/                         # index.php, favicon, storage symlink
resources/
├── css/app.css, js/app.js      # Entry Vite
└── views/                      # Blade views: layouts/, admin/, components/, dll.
routes/
├── web.php                     # Route utama (frontend + admin)
├── auth.php                    # Route auth Breeze
├── install.php                 # Route installer (prefix /install, protected)
└── console.php                 # Artisan command + jadwal scheduler
tests/Feature/                  # PHPUnit feature tests
```

### Controller Admin — Split / _Admin controllers are split_

Hanya **6 controller** yang berada di `app/Http/Controllers/Admin/`:

- [`BackupController`](../app/Http/Controllers/Admin/BackupController.php)
- [`CurrencyRateController`](../app/Http/Controllers/Admin/CurrencyRateController.php)
- [`DashboardController`](../app/Http/Controllers/Admin/DashboardController.php)
- [`LanguageController`](../app/Http/Controllers/Admin/LanguageController.php)
- [`SlugSettingsController`](../app/Http/Controllers/Admin/SlugSettingsController.php)
- [`UserController`](../app/Http/Controllers/Admin/UserController.php)

**Semua** resource admin lainnya dilayani controller di root namespace `app/Http/Controllers/` yang menangani **aksi admin DAN publik** (dibedakan oleh rute di [`routes/web.php`](../routes/web.php)): `PropertyController`, `BookingController`, `MediaController`, `PageController`, `BlockController`, `NavigationController`, `PostController`, `CategoryController`, `TagController`, `VoucherController`, `PromoRateController`, `RedirectController`, `AmenityController`, `SettingsController`, `SeoController`, `BlogController`, `HomeController`, `SearchController`, `ContactController`, `PromotionController`, `InstallerController`, `ProfileController`.

## Pola Controller-Service / _Controller-Service Pattern_

Business logic berada di `app/Services/`, bukan di controller atau view:

| Layanan | Tanggung jawab |
|---------|----------------|
| [`BookingPricingService`](../app/Services/BookingPricingService.php) | Kalkulator harga **canonical** (satu-satunya) |
| [`BookingService`](../app/Services/BookingService.php) | Pembuat booking **canonical** + pengecekan konflik + status |
| [`Voucher::calculateDiscount()`](../app/Models/Voucher.php:77) | Kalkulator diskon kupon **canonical** |
| [`GitService`](../app/Services/GitService.php) | Operasi git untuk dashboard Version Control |
| [`BackupService`](../app/Services/BackupService.php) | Backup & restore database |
| [`SettingsService`](../app/Services/SettingsService.php) | Akses pengaturan DB |
| [`SitemapService`](../app/Services/SitemapService.php) | Generator `/sitemap.xml` |
| [`RobotsService`](../app/Services/RobotsService.php) | Generator `/robots.txt` |
| [`MapEmbedService`](../app/Services/MapEmbedService.php) | Sanitasi embed peta kontak (tidak pernah iframe mentah) |
| [`GeoapifyService`](../app/Services/GeoapifyService.php) | Klien Geoapify Places API — **hanya** dipanggil dari `FetchNearbyPlacesJob` |
| [`SeoService`](../app/Services/SeoService.php), [`SchemaService`](../app/Services/SchemaService.php) | SEO / structured data |
| [`CurrencyRateService`](../app/Services/CurrencyRateService.php) | Kurs mata uang |
| [`CaptchaService`](../app/Services/CaptchaService.php) | Verifikasi captcha |
| [`BookingNotificationService`](../app/Services/BookingNotificationService.php) | Notifikasi booking (email/WhatsApp) |
| [`PostUpdateActionService`](../app/Services/PostUpdateActionService.php) | Aksi pasca-update git (jalankan migrasi/cache) |
| [`GeoLocaleService`](../app/Services/GeoLocaleService.php) | Deteksi negara untuk fallback bahasa |
| [`SafeHtmlService`](../app/Services/SafeHtmlService.php) | Sanitasi HTML whitelist |

## Siklus Request / _Request Lifecycle_

1. Request masuk → `public/index.php` → bootstrap Laravel ([`bootstrap/app.php`](../bootstrap/app.php)).
2. **Global middleware** (append di [`bootstrap/app.php`](../bootstrap/app.php:33)):
   - [`CheckInstalled`](../app/Http/Middleware/CheckInstalled.php)
   - [`ForceHttps`](../app/Http/Middleware/ForceHttps.php)
   - [`RedirectMiddleware`](../app/Http/Middleware/RedirectMiddleware.php) (redirect lama → baru)
   - [`LocaleMiddleware`](../app/Http/Middleware/LocaleMiddleware.php) (bahasa per-request)
3. **Middleware web group** juga mendapat [`SecurityHeaders`](../app/Http/Middleware/SecurityHeaders.php) (X-Frame-Options, X-Content-Type-Options, dll.).
4. Route dipecahkan → controller memvalidasi (FormRequest) → memanggil service → service mengembalikan data → view Blade dirender.
5. Response dikirim. Turbo Drive menangani navigasi selanjutnya tanpa reload penuh.

## Provider Utama / _Key Providers_

- [`bootstrap/providers.php`](../bootstrap/providers.php)
- [`AppServiceProvider`](../app/Providers/AppServiceProvider.php)
- [`MailSettingsServiceProvider`](../app/Providers/MailSettingsServiceProvider.php) — override mail runtime dari settings.

## Model Auth / _Auth Model_

- **Laravel Breeze session-based auth** (login, register, password reset, email verification).
- Middleware `admin` → [`EnsureUserIsAdmin`](../app/Http/Middleware/EnsureUserIsAdmin.php), memanggil `User::isAdmin()` yang menerima role slug **`super-admin`** dan **`admin`** ([`app/Models/User.php`](../app/Models/User.php:60)).
- Role disimpan via pivot `model_has_roles` (gaya Spatie, tapi custom).
- Admin group di [`routes/web.php`](../routes/web.php:86) menggunakan middleware `['auth', 'verified', 'admin']` dengan prefix dari setting **`admin_prefix`** via helper [`slug()`](../app/Helpers/slug.php:16) — **jangan hardcode `/admin`**.

## Rute Publik Kunci / _Key Public Routes_

- `/` → HomeController
- `/apartments` (slug dapat diubah) → daftar & detail properti
- `/blog` (slug dapat diubah) → daftar & detail blog, kategori, tag
- `/search/suggest` → JSON untuk autocomplete (throttle)
- `/sitemap.xml`, `/robots.txt` → SeoController
- `/kontak` → ContactController (POST di-throttle + captcha)
- `/promosi` → PromotionController
- `/bookings` → BookingController (store, success, status, validate-voucher — semuanya throttle)
- Catch-all `/{page:slug}` → halaman CMS (harus terdaftar **terakhir**) — [`routes/web.php`](../routes/web.php:237)

## Instalasi / _Installer_

- Route installer di [`routes/install.php`](../routes/install.php) dipasang dengan prefix `/install` dan middleware `web` + **`protect.installer`** di [`bootstrap/app.php`](../bootstrap/app.php:15).
- [`ProtectInstaller`](../app/Http/Middleware/ProtectInstaller.php) membatasi akses ke localhost / IP whitelist / token.
- Lihat [`docs/INSTALLER.md`](INSTALLER.md) untuk detail langkah.

## Lihat Juga / _See Also_

- [`docs/DATABASE.md`](DATABASE.md) — model & relasi
- [`docs/FRONTEND.md`](FRONTEND.md) — frontend stack
- [`docs/BOOKING.md`](BOOKING.md) — alur booking & pricing
- [`docs/ADMIN.md`](ADMIN.md) — panduan panel admin
- [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) — deployment
