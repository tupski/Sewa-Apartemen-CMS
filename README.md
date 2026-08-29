# Artivo CMS

**Sistem Manajemen Sewa Apartemen** — dibangun khusus untuk **PT KAKARAMA Samudera Group**.
**_Apartment Rental Management System_** — _built specifically for **PT KAKARAMA Samudera Group**._

Deploy sebagai **Lya Rooms** — `https://artivo.artupski.com`

Versi: **`1.0.0`** — baca kapan saja via `config('artivo.version')` dari [`config/artivo.php`](config/artivo.php:21).
_Version: **`1.0.0`** — read anytime via `config('artivo.version')` from [`config/artivo.php`](config/artivo.php:21)._

---

## Fitur Utama / _Key Features_

- **Manajemen Properti**: CRUD properti dengan foto, galeri per kategori, tipe unit (Studio/1BR—4BR/Penthouse), harga JSON per tipe & periode
- **Sistem Booking**: Form booking publik, 4 status (pending/confirmed/cancelled/completed), konflik overlap otomatis, kode unik `BK-YYYYMMDD-XXXX`, look-up tamu via token acak, export CSV
- **Harga Flexible**: Transit (3/6/9/12/24 jam), harian (weekday/weekend), mingguan, bulanan — semua via kalkulator canonical
- **Kupon & Promo**: Voucher diskon (`percent`/`fixed`) + Promo Rate per properti
- **Blog**: Post, kategori, tag, editor WYSIWYG (Quill 2), featured image
- **SEO Engine**: `SeoMetadata` polimorfik, sitemap.xml, robots.txt, redirect manager, JSON-LD structured data
- **Analytics**: GA4 (G-), GTM (GTM-), Meta Pixel, Microsoft Clarity, Google Search Console
- **Halaman & Blok CMS**: Halaman statis + blok konten (hero, rich text, image, gallery)
- **Navigasi**: Builder menu multi-lokasi (main, footer, sidebar)
- **Pustaka Media**: Upload file + import URL (dengan proteksi SSRF)
- **Multi-Bahasa**: id/en + deteksi geo + switcher
- **Mata Uang**: Multi-currency display + kurs otomatis setiap 6 jam
- **Panel Admin Blade**: Hand-built, bukan Filament/Nova/Voyager — dark mode, sidebar collapsible, Font Awesome + Lucide icons
- **Web Installer**: 6-langkah (requirements → app → database → admin → website → finish), dilindungi `protect.installer`
- **Git Dashboard**: Version Control dari admin — update checker terjadwal, riwayat commit, rollback via `git checkout` (⚠️ peringatan detached HEAD + skema DB)
- **Backup & Restore**: Database dump SQL via panel admin

## Tumpukan Teknologi / _Tech Stack_

| Layer | Teknologi | Sumber |
|-------|-----------|--------|
| Bahasa | PHP `^8.3` | [`composer.json`](composer.json:9) |
| Framework | Laravel `^13.8` | [`composer.json`](composer.json:10) |
| Template | Blade | — |
| Interaktivitas | Alpine.js `^3.4.2` | [`package.json`](package.json:12) |
| Navigasi | Hotwired Turbo `^8.0.23` | [`package.json`](package.json:21) |
| CSS | Tailwind CSS v3 | [`package.json`](package.json:17) |
| Build | Vite 8 | [`package.json`](package.json:18) |
| Database (prod) | MySQL 8+ / MariaDB 10.6+ | — |
| Database (test) | SQLite in-memory | [`phpunit.xml`](phpunit.xml) |
| Auth | Laravel Breeze (session) + `admin` role middleware | [`bootstrap/app.php`](bootstrap/app.php:44) |
| Admin panel | **Custom Blade** — bukan Filament/Nova/Voyager | — |
| Pengujian | PHPUnit `^12.5.12` (bukan Pest) | [`composer.json`](composer.json:23) |

## Mulai Cepat / _Quick Start_

```bash
# Opsi A: Web installer
# Buka http://localhost:8000/install setelah menjalankan server

# Opsi B: Manual
git clone <repo-url>
cd sewa-apartemen-cms
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link

# Jalankan pengembangan / dev
composer dev
```

### Default Admin (setelah seeding)

- Email: `admin@admin.com`
- Password: `password`

## Dokumentasi / _Documentation_

Indeks lengkap — lihat [`docs/README.md`](docs/README.md) untuk navigasi.

| Dokumen | Deskripsi | _Description_ |
|---------|-----------|---------------|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Stack, direktori, Controller-Service, siklus request, auth | _Stack, directory layout, Controller-Service pattern, request lifecycle, auth_ |
| [`docs/DATABASE.md`](docs/DATABASE.md) | 23 model, relasi, JSON columns, POI, absensi penting | _23 models, relationships, JSON columns, POI, notable absences_ |
| [`docs/FRONTEND.md`](docs/FRONTEND.md) | Blade + Alpine + Turbo + Tailwind + Vite + i18n | _Frontend stack: Blade, Alpine, Turbo, Tailwind, Vite, i18n_ |
| [`docs/ADMIN.md`](docs/ADMIN.md) | Panduan setiap menu admin (untuk non-developer) | _Admin panel guide for every menu (for non-developer admins)_ |
| [`docs/BOOKING.md`](docs/BOOKING.md) | Alur booking, status, canonical services, konflik, kupon | _Booking flow, statuses, canonical services, conflict check, vouchers_ |
| [`docs/SEO.md`](docs/SEO.md) | `SeoMetadata` morph, sitemap, robots, redirect, analytics | _SEO metadata, sitemap, robots.txt, redirects, analytics_ |
| [`docs/NEARBY-PLACES.md`](docs/NEARBY-PLACES.md) | Dua jalur tempat sekitar: manual JSON + pipeline Geoapify | _Two nearby-places paths: manual JSON + Geoapify pipeline_ |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Deploy manual, installer, cron, env vars, queue | _Manual deployment, web installer, cron, env vars, queue_ |
| [`docs/VERSION-CONTROL.md`](docs/VERSION-CONTROL.md) | Git dashboard, rollback, ⚠️ detached HEAD + skema DB | _Git dashboard, rollback, ⚠️ detached HEAD + DB schema warning_ |
| [`docs/SECURITY.md`](docs/SECURITY.md) | Praktik keamanan, risiko, audit referensi | _Security practices, known risks, reference audits_ |
| [`docs/TESTING.md`](docs/TESTING.md) | PHPUnit, SQLite in-memory, suite yang ada | _PHPUnit, SQLite in-memory, existing test suites_ |
| [`docs/VERSIONING.md`](docs/VERSIONING.md) | SemVer, sumber versi, kebijakan MAJOR/MINOR/PATCH | _SemVer, version source, MAJOR/MINOR/PATCH policy_ |

### Dokumen Pendukung / _Supporting Docs_

- [`CHANGELOG.md`](CHANGELOG.md) — catatan rilis lengkap (ID-first, EN italic)
- [`AGENTS.md`](AGENTS.md) — aturan untuk AI agent (teknis)
- [`docs/geoapify-setup.md`](docs/geoapify-setup.md) — setup Geoapify API key
- [`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md) — audit keamanan
- [`docs/decisions/`](docs/decisions/) — Architecture Decision Records (ADR)

## Pengujian / _Testing_

```bash
php artisan test
```

## Powered by Artivo CMS

Kredit "Powered by Artivo CMS" ([`resources/views/components/powered-by.blade.php`](resources/views/components/powered-by.blade.php)) dirender di footer publik dan admin, menampilkan versi dan tautan ke `https://artivo.artupski.com`.

## Kepemilikan / _Ownership_

Proyek ini adalah **perangkat lunak proprietary** milik **PT KAKARAMA Samudera Group**. Tidak ada lisensi open-source yang berlaku. Seluruh hak cipta dilindungi.

_This project is **proprietary software** owned by **PT KAKARAMA Samudera Group**. No open-source license applies. All rights reserved._

---

**Artivo CMS** — `https://artivo.artupski.com`
