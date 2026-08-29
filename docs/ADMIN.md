# Panduan Panel Admin

_Admin Panel Guide_

Panduan untuk administrator **non-developer** di PT KAKARAMA. Dokumen ini menjelaskan **setiap menu yang benar-benar ada** di panel admin, fungsi setiap layar, dan alur kerjanya.

_Guide for **non-developer** administrators at PT KAKARAMA. This document describes **every menu that actually exists** in the admin panel, what each screen does, and the workflow._

---

## Cara Masuk / _How to Log In_

1. Buka `https://<domain-anda>/login` (atau `<domain>/<admin_prefix>/login`).
2. Masuk dengan email & password akun admin.
3. Setelah login, panel admin berada di `/<admin_prefix>` — **default `/admin`**, tapi prefix ini **dapat diubah** di menu **Settings → Slug & Path** (pengaturan `admin_prefix`). Gunakan route name `admin.` di kode, jangan hardcode `/admin`.

Semua halaman admin membutuhkan login + role **`super-admin`** atau **`admin`** (middleware `admin`).

## Struktur Menu Sisi Kiri / _Left Sidebar Menu_

Urutan menu di sidebar admin ([`resources/views/layouts/admin.blade.php`](../resources/views/layouts/admin.blade.php:96)):

1. **Dashboard**
2. **Content**: Pages, Blocks, Media, Navigation
3. **Properties**: Properties, Amenities
4. **Blog**: Posts, Categories, Tags
5. **Booking**: Bookings, Vouchers, Promo Rates
6. **System**: Currency, Languages, Users, Slug & Path, Backup & Restore, Settings

---

## Dashboard

Layar pertama setelah login. Menampilkan statistik ringkas (jumlah properti, booking, dll.) dan aktivitas terbaru.

- Controller: [`Admin\DashboardController`](../app/Http/Controllers/Admin/DashboardController.php)
- Test terkait: [`tests/Feature/DashboardTest.php`](../tests/Feature/DashboardTest.php)

## Properties

**Menu → Properties** — kelola properti/apartemen yang ditampilkan ke publik.

### Daftar Properti (index)

- Tabel semua properti dengan pencarian & filter status.
- Tombol untuk membuat properti baru.

### Form Properti (create/edit)

Form dibagi menjadi beberapa bagian:

**Bagian Utama** (di [`resources/views/admin/properties/create.blade.php`](../resources/views/admin/properties/create.blade.php) / [`edit.blade.php`](../resources/views/admin/properties/edit.blade.php)):

| Field | Keterangan |
|-------|-----------|
| Nama | Nama properti |
| Slug | URL properti (di-index; jangan ubah tanpa perlu) |
| Deskripsi | Deskripsi panjang |
| Status | Published / Draft (sitemap hanya memuat yang published) |
| Address / City / Province / Postal Code | Lokasi |
| Latitude / Longitude | Koordinat — **diperlukan** untuk fitur Nearby Places (Geoapify) |
| Featured Image | Foto utama |
| Is Featured | Sorotan di beranda |
| Order | Urutan tampilan |
| Max Guests / Max Days | Batas tamu & lama sewa |
| Check-in / Check-out Time | Jam check-in/out |
| Check-in Method | Metode check-in |
| Required Documents | Dokumen yang diminta (JSON, mis. KTP, deposit) |

**Partial [`_photos.blade.php`](../resources/views/admin/properties/_photos.blade.php)** — Galeri foto properti. Foto diunggah per kategori (Lobby, Lift, Bedroom, Toilet, Swimming Pool, Playground, View — lihat [`Property::DEFAULT_PHOTO_CATEGORIES`](../app/Models/Property.php:80)). Gunakan partial ini untuk mengelola foto & kategori.

**Partial [`_pricing.blade.php`](../resources/views/admin/properties/_pricing.blade.php)** — **Tipe Kamar & Harga** (lihat [`docs/BOOKING.md`](BOOKING.md) untuk detail kunci harga):

- **Tipe Kamar**: centang tipe yang tersedia (Studio, 1 BR, 2 BR, 3 BR, 4 BR, Penthouse). Harga yang dikosongkan tidak muncul sebagai opsi di frontend.
- **Hari Weekend (Override per Properti)**: pilih hari weekend properti ini; kosongkan untuk memakai konfigurasi global dari **Settings → Pricing**.
- **Harga Transit** (3/6/9/12/24 jam, split `_wd`/`_we`), **Harian** (`night_wd`/`night_we`), **Mingguan** (`weekly`), **Bulanan** (`monthly`).

**Partial [`_policy.blade.php`](../resources/views/admin/properties/_policy.blade.php)** — Kebijakan properti (check-in method, dokumen yang diminta, dll.).

**Partial [`_nearby.blade.php`](../resources/views/admin/properties/_nearby.blade.php)** — **Nearby Places (Geoapify)**:

- Menampilkan tabel POI persisten (`property_places` + `place`), terurut jarak terdekat.
- Tombol **Resync POI** memicu [`FetchNearbyPlacesJob`](../app/Jobs/FetchNearbyPlacesJob.php) — satu-satunya jalur pemanggilan Geoapify. Butuh koordinat + API key; tanpa keduanya tombol nonaktif dengan peringatan.
- ⚠️ Jika `GEOAPIFY_MAP_KEY` sama dengan server `GEOAPIFY_API_KEY`, sistem menampilkan peringatan (kunci Places terkirim ke browser).
- Lihat [`docs/NEARBY-PLACES.md`](NEARBY-PLACES.md) untuk detail.

> **Tidak ada menu "Units"** — unit direfaktor menjadi tipe unit (`unit_types`) di properti.

## Amenities

**Menu → Amenities** — kelola daftar fasilitas (WiFi, AC, Parkir, Kolam, dll.) yang dapat ditautkan ke properti.

- CRUD sederhana: nama, ikon/label opsional.
- Form: [`resources/views/admin/amenities/_form.blade.php`](../resources/views/admin/amenities/_form.blade.php)

## Blog (Posts, Categories, Tags)

### Posts

**Menu → Blog → Posts** — artikel blog.

- Editor WYSIWYG (Quill 2), featured image, kategori & tag, slug, SEO metadata.
- Form: [`resources/views/admin/posts/_form.blade.php`](../resources/views/admin/posts/_form.blade.php)

### Categories

**Menu → Blog → Categories** — kategori artikel.

### Tags

**Menu → Blog → Tags** — tag artikel (tabel pivot `post_tag`).

## Bookings

**Menu → Booking → Bookings** — kelola permintaan booking dari publik.

### Daftar Booking (index)

- **Export CSV** — tombol hijau kanan atas, mengekspor sesuai filter aktif.
- **Filter**: pencarian (kode, nama, tipe kamar), tipe sewa (Transit Jam / Harian / Mingguan / Bulanan), properti, rentang tanggal check-in.
- **Status pills**: Semua / Pending / Confirmed / Cancelled / Completed.
- File: [`resources/views/admin/bookings/index.blade.php`](../resources/views/admin/bookings/index.blade.php)

### Detail Booking (show)

- Informasi lengkap tamu, properti, tipe unit, tanggal, harga, breakdown harga, catatan.
- Aksi: **Confirm**, **Cancel**, **Complete**, tambah notes.
- Status flow: `pending` → `confirmed` → `completed`, atau `pending` → `cancelled` ([`BookingService::confirm/cancel/complete`](../app/Services/BookingService.php:237)).

> ⚠️ **Pengecekan konflik booking otomatis ADA** di `BookingService::validateAvailability()` — sistem menolak booking yang tumpang-tindih untuk properti + tipe unit yang sama (kecuali status `cancelled`).

## Vouchers

**Menu → Booking → Vouchers** — kupon diskon yang bisa dipakai tamu saat booking.

| Field | Keterangan |
|-------|-----------|
| Code | Kode kupon (selalu disimpan UPPERCASE) |
| Name | Nama kupon |
| Discount Type | `percent` atau `fixed` |
| Discount Value | Nilai diskon |
| Min Booking Amount | Minimum total booking |
| Max Discount Amount | Maksimal diskon (untuk tipe persen) |
| Usage Limit | Batas pemakaian (`null` = tak terbatas) |
| Used Count | Sudah terpakai |
| Valid From / Valid Until | Masa berlaku |
| Is Active | Aktif/nonaktif |

- Kalkulator diskon **canonical**: [`Voucher::calculateDiscount()`](../app/Models/Voucher.php:77).
- Form: [`resources/views/admin/vouchers/_form.blade.php`](../resources/views/admin/vouchers/_form.blade.php)

## Promo Rates

**Menu → Booking → Promo Rates** — harga promo per properti (mis. harga transit khusus).

- Promo dibatasi ke properti tertentu (`property_id`), memiliki `price` dan status `is_active`.
- Diterapkan di [`BookingPricingService::calculate()`](../app/Services/BookingPricingService.php:54).

## Media

**Menu → Content → Media** — pustaka file (upload & URL import).

- Upload file ke disk `public` (symlink `storage/app/public`).
- **Import dari URL** didukung — dengan proteksi SSRF (lihat [`docs/SECURITY.md`](SECURITY.md)).
- Validasi tipe & ukuran file di [`MediaRequest`](../app/Http/Requests/MediaRequest.php).
- ⚠️ **SVG upload diizinkan** — risiko stored-XSS; batasi ke pengguna tepercaya.

## Pages

**Menu → Content → Pages** — halaman CMS statis.

- Setiap halaman punya slug unik (di-index); dirender publik lewat **catch-all** `/{page:slug}` di [`routes/web.php`](../routes/web.php:237).
- Dukungan SEO metadata (polymorphic `seoable`).
- Form: [`resources/views/admin/pages/_form.blade.php`](../resources/views/admin/pages/_form.blade.php)
- Ada juga rute lama `/pages/{slug}` yang dipertahankan untuk kompatibilitas.

## Blocks

**Menu → Content → Blocks** — blok konten yang dapat digunakan ulang (hero, rich text, image, gallery, dll.) di dalam halaman.

- CRUD + halaman `show` (preview).
- Form: [`resources/views/admin/blocks/_form.blade.php`](../resources/views/admin/blocks/_form.blade.php)

## Navigation

**Menu → Content → Navigation** — menu situs (main, footer, sidebar).

- Builder menu multi-lokasi dengan item bersarang & urutan.
- Partial: [`resources/views/admin/navigations/_menu-item.blade.php`](../resources/views/admin/navigations/_menu-item.blade.php)

## Currency

**Menu → System → Currency** — kurs mata uang.

- Kelola kurs manual, dan tombol **fetch** untuk menarik kurs otomatis (via [`CurrencyRateService`](../app/Services/CurrencyRateService.php) + `currency:fetch` terjadwal setiap 6 jam).
- Konfigurasi API: **Settings → Currency API**.

## Languages

**Menu → System → Languages** — bahasa situs (id/en).

- Kelola bahasa aktif & default.
- **Translations** — edit terjemahan kunci `lang/*.json` dari panel admin.
- File: [`resources/views/admin/languages/translations.blade.php`](../resources/views/admin/languages/translations.blade.php)

## Users

**Menu → System → Users** — akun pengguna.

- Buat/edit akun admin; atur role (super-admin / admin).
- Form: [`resources/views/admin/users/_form.blade.php`](../resources/views/admin/users/_form.blade.php)

## Redirects

**Menu → System → Redirects** *(jika tersedia di rute)* — kelola redirect lama → baru agar URL lama tetap berfungsi.

- Diproses oleh [`RedirectMiddleware`](../app/Http/Middleware/RedirectMiddleware.php) di setiap request.
- Form: [`resources/views/admin/redirects/_form.blade.php`](../resources/views/admin/redirects/_form.blade.php)

> Catatan: pastikan menu Redirects muncul di sidebar sesuai versi; rutenya ada di [`routes/web.php`](../routes/web.php).

## Slug & Path

**Menu → System → Slug & Path** — konfigurasi URL yang dapat diubah:

- `admin_prefix` — prefix panel admin (default `admin`).
- `slug_apartments` — URL daftar properti (default `apartments`).
- `slug_blog` — URL blog (default `blog`).
- `slug_booking`, `slug_booking_success`, `slug_booking_status` — URL booking.

Controller: [`Admin\SlugSettingsController`](../app/Http/Controllers/Admin/SlugSettingsController.php), helper [`slug()`](../app/Helpers/slug.php:16).

## Backup & Restore

**Menu → System → Backup & Restore** — backup database.

- Buat backup, download, restore (dengan konfirmasi).
- Lihat [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) untuk detail operasional.
- Controller: [`Admin\BackupController`](../app/Http/Controllers/Admin/BackupController.php), service: [`BackupService`](../app/Services/BackupService.php).

## Settings

**Menu → System → Settings** — satu halaman dengan tab grup (sidebar kiri dalam halaman). Semua grup di [`resources/views/admin/settings/partials/`](../resources/views/admin/settings/partials):

| Tab | Partial | Isi |
|-----|---------|-----|
| **General** | [`_general.blade.php`](../resources/views/admin/settings/partials/_general.blade.php) | Site name, description, logo, favicon, contact email/phone/address, WhatsApp default, timezone, locale, currency |
| **Homepage** | [`_homepage.blade.php`](../resources/views/admin/settings/partials/_homepage.blade.php) | Hero title/subtitle, CTA |
| **Footer** | [`_footer.blade.php`](../resources/views/admin/settings/partials/_footer.blade.php) | Footer about, copyright |
| **Appearance** | [`_theme.blade.php`](../resources/views/admin/settings/partials/_theme.blade.php) | Primary/secondary/accent color (regex `#RRGGBB`), header layout, dark mode |
| **SEO** | [`_seo.blade.php`](../resources/views/admin/settings/partials/_seo.blade.php) | Meta description/keywords + Analytics (lihat [`docs/SEO.md`](SEO.md)) |
| **Integrations** | [`_integrations.blade.php`](../resources/views/admin/settings/partials/_integrations.blade.php) | Notification webhook + secret |
| **Pricing** | [`_pricing.blade.php`](../resources/views/admin/settings/partials/_pricing.blade.php) | Weekend days mode, weekend start/end day, dst. |
| **Mail** | [`_mail.blade.php`](../resources/views/admin/settings/partials/_mail.blade.php) | Mail mailer, host, port, username, password, from |
| **Email Templates** | [`_email_templates.blade.php`](../resources/views/admin/settings/partials/_email_templates.blade.php) | Subject & body template email booking |
| **Captcha** | [`_captcha.blade.php`](../resources/views/admin/settings/partials/_captcha.blade.php) | Provider (none/reCAPTCHA/hCaptcha/Turnstile), site/secret key, min score |
| **Currency API** | [`_currency_api.blade.php`](../resources/views/admin/settings/partials/_currency_api.blade.php) | URL API, key, daftar target kurs |
| **Version Control** | [`_git.blade.php`](../resources/views/admin/settings/partials/_git.blade.php) | Git dashboard, commit history, rollback — lihat [`docs/VERSION-CONTROL.md`](VERSION-CONTROL.md) |

> Semua grup divalidasi oleh [`SettingsController::$groupRules`](../app/Http/Controllers/SettingsController.php:89). Field SEO analytics divalidasi dengan **allowlist ketat** — lihat [`docs/SEO.md`](SEO.md).

## Lihat Juga / _See Also_

- [`docs/BOOKING.md`](BOOKING.md) — alur booking & pricing
- [`docs/VERSION-CONTROL.md`](VERSION-CONTROL.md) — dashboard git & rollback
- [`docs/SEO.md`](SEO.md) — pengaturan SEO
- [`docs/NEARBY-PLACES.md`](NEARBY-PLACES.md) — Nearby Places
