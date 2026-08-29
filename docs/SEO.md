# SEO

_SEO Architecture_

Arsitektur SEO **Artivo CMS**: metadata polimorfik, komponen `seo`, sitemap, robots.txt, redirect, dan pengaturan analytics dengan format yang divalidasi ketat.

_SEO architecture for **Artivo CMS**: polymorphic metadata, the `seo` component, sitemap, robots.txt, redirects, and tightly-validated analytics settings._

---

## Metadata SEO Polimorfik / _Polymorphic SEO Metadata_

- Model: [`SeoMetadata`](../app/Models/SeoMetadata.php) — polimorfik via relasi `seoable()` (`morphTo`). Tabel `seo_metadata` punya `seoable_type` + `seoable_id`.
- Pages, posts, dan properties melampirkan metadata SEO melalui morph ini. **Jangan ratakan atau rusak morph.**
- Field: `meta_title`, `meta_description`, `open_graph` (array), `twitter` (array), `canonical_url`, `index_status` (boolean).
- Komponen render: [`resources/views/components/seo.blade.php`](../resources/views/components/seo.blade.php) — **gunakan komponen ini**, jangan menulis meta `<head>` manual.

## Sitemap / _Sitemap_

- Generator: [`SitemapService`](../app/Services/SitemapService.php).
- Route: `GET /sitemap.xml` → [`SeoController::sitemap`](../app/Http/Controllers/SeoController.php) ([`routes/web.php`](../routes/web.php:53)).
- Memuat homepage, properti published, halaman published, dan entri blog.
- **Jangan tambahkan generator sitemap kedua** — reuse `SitemapService`.

## robots.txt / _robots.txt_

- Service: [`RobotsService`](../app/Services/RobotsService.php).
- Route: `GET /robots.txt` → [`SeoController::robots`](../app/Http/Controllers/SeoController.php) ([`routes/web.php`](../routes/web.php:54)).
- Halaman admin memiliki `<meta name="robots" content="noindex, nofollow">` di layout admin.

## Redirect / _Redirects_

- Model: [`Redirect`](../app/Models/Redirect.php).
- Middleware: [`RedirectMiddleware`](../app/Http/Middleware/RedirectMiddleware.php) — dipasang global di [`bootstrap/app.php`](../bootstrap/app.php:36), memproses setiap request agar URL lama tetap bekerja.
- Admin: resource `RedirectController` (lihat [`docs/ADMIN.md`](ADMIN.md)).

## Slug / _Slugs_

- `properties.slug`, `pages.slug`, `posts.slug` adalah kolom lookup yang di-index.
- **Jaga stabilitas slug**; regenerate hanya atas permintaan eksplisit.
- Slug/path publik dapat dikonfigurasi via **Settings → Slug & Path** (helper [`slug()`](../app/Helpers/slug.php:16)): `slug_apartments`, `slug_blog`, `slug_booking`, `slug_booking_success`, `slug_booking_status`, `admin_prefix`.

## Structured Data / _Structured Data_

- Service: [`SchemaService`](../app/Services/SchemaService.php) — JSON-LD.
- Integrasi via [`SeoService`](../app/Services/SeoService.php).

## Analytics & Verifikasi (Settings → SEO) / _Analytics & Verification (Settings → SEO)_

Field di tab **SEO** ([`resources/views/admin/settings/partials/_seo.blade.php`](../resources/views/admin/settings/partials/_seo.blade.php)) di-render oleh [`AnalyticsService`](../app/Services/AnalyticsService.php) ke dalam `<script>`/`<iframe>` inline. Karena nilai di-interpolasi langsung ke output, setiap aturan di bawah adalah **allowlist keras** (tanpa tanda kutip, `<`, `>`, atau spasi) — didefinisikan di [`SettingsController::$groupRules['seo']`](../app/Http/Controllers/SettingsController.php:134).

| Field | Format yang diterima (regex) | Contoh |
|-------|------------------------------|--------|
| **Google Analytics ID** (`google_analytics_id`) | `G-`, `GT-`, atau `AW-` + 4–15 karakter alfanumerik | `G-ABC1234567` |
| **Google Tag Manager** (`google_tag_manager_id`) | `GTM-` + 4–12 alfanumerik | `GTM-ABC1234` |
| **Meta Pixel** (`meta_pixel_id`) | 10–16 digit angka | `1234567890` |
| **Microsoft Clarity** (`microsoft_clarity_id`) | 4–20 alfanumerik (lowercase base36-ish) | `a1b2c3d4e5f6` |
| **Search Console Token** (`search_console_token`) | 10–100 karakter alfanumerik/`_-` (URL-safe base64) | `AbCdEfGhIjKlMnOpQrSt` |
| **Google Maps API key** (`google_maps_api_key`) | `AIza` + 35 karakter URL-safe | `AIzaSy...` |

Catatan penting (dari komentar kode):

- `google_analytics_id` memakan **semua keluarga ID gtag.js** (`G-` GA4, `GT-` Google tag, `AW-` Google Ads). UA- lama (legacy) masuk ke field terpisah **"Google Analytics (Legacy)"** (`google_analytics`) yang **tidak** di-render ke `<script>` oleh `AnalyticsService` — hanya batas panjang (`max:255`).
- `google_tag_manager_id` **hanya** menerima `GTM-` (gtm.js hanya resolve container GTM). Meletakkan `G-`/`GT-`/`AW-` di sini diam-diam tidak memuat apa pun.
- Field legacy `facebook_pixel` dan `google_analytics` dipertahankan untuk kompatibilitas tapi tidak di-render.

> Pilihan: Pengaturan SEO global ada di **Settings → SEO**; metadata per-halaman/properti/post di form resource masing-masing (via morph `SeoMetadata`).

## Lihat Juga / _See Also_

- [`docs/ADMIN.md`](ADMIN.md) — tab Settings → SEO
- [`docs/ARCHITECTURE.md`](ARCHITECTURE.md) — middleware & siklus request
- [`docs/security-audit-2026-08-27.md`](security-audit-2026-08-27.md) — audit keamanan
- Test: [`tests/Feature/SeoTest.php`](../tests/Feature/SeoTest.php), [`tests/Feature/SitemapTest.php`](../tests/Feature/SitemapTest.php), [`tests/Feature/SeoSettingsValidationTest.php`](../tests/Feature/SeoSettingsValidationTest.php)
