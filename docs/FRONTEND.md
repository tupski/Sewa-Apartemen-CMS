# Frontend

_Frontend Architecture_

Dokumen ini menjelaskan **Blade, Alpine.js 3, Hotwired Turbo (Turbo Drive), Tailwind CSS v3, dan Vite 8** — stack frontend yang **tetap** dan tidak boleh diubah ke Livewire/Vue/React/Inertia.

_This document describes **Blade, Alpine.js 3, Hotwired Turbo (Turbo Drive), Tailwind CSS v3, and Vite 8** — the **fixed** frontend stack that must not be changed to Livewire/Vue/React/Inertia._

---

## Layout / _Layouts_

| Layout | File | Digunakan untuk |
|--------|------|-----------------|
| Frontend publik | [`resources/views/layouts/frontend.blade.php`](../resources/views/layouts/frontend.blade.php) | Semua halaman publik: properti, blog, halaman CMS, kontak |
| Admin | [`resources/views/layouts/admin.blade.php`](../resources/views/layouts/admin.blade.php) | Semua halaman admin (termasuk sidebar, dark mode, header) |
| App | [`resources/views/layouts/app.blade.php`](../resources/views/layouts/app.blade.php) | Halaman auth (login, register, dll.) |
| Guest | [`resources/views/layouts/guest.blade.php`](../resources/views/layouts/guest.blade.php) | Halaman guest (minimal) |

## Komponen Blade / _Blade Components_

Semua komponen bersama di [`resources/views/components/`](../resources/views/components):

| Komponen | Deskripsi |
|----------|-----------|
| [`seo.blade.php`](../resources/views/components/seo.blade.php) | Render meta tag SEO — **gunakan ini**, jangan tulis `<head>` meta manual |
| [`analytics.blade.php`](../resources/views/components/analytics.blade.php) | Google Analytics, GTM, Meta Pixel, Clarity, Search Console |
| [`analytics-body.blade.php`](../resources/views/components/analytics-body.blade.php) | Bagian `<body>` dari analytics (GTM noscript) |
| [`captcha.blade.php`](../resources/views/components/captcha.blade.php) | Captcha (reCAPTCHA / hCaptcha / Turnstile) |
| [`money-input.blade.php`](../resources/views/components/money-input.blade.php) | Input harga dengan format mata uang |
| [`text-input.blade.php`](../resources/views/components/text-input.blade.php) | Input teks standar |
| [`password-input.blade.php`](../resources/views/components/password-input.blade.php) | Input password dengan toggle |
| [`search-input.blade.php`](../resources/views/components/search-input.blade.php) | Input pencarian dengan autocomplete |
| [`modal.blade.php`](../resources/views/components/modal.blade.php) | Modal dialog |
| [`dropdown.blade.php`](../resources/views/components/dropdown.blade.php) | Dropdown menu |
| [`share-modal.blade.php`](../resources/views/components/share-modal.blade.php) | Modal bagikan (sosial media) |
| [`powered-by.blade.php`](../resources/views/components/powered-by.blade.php) | Kredit "Powered by Artivo CMS" |
| [`input-label.blade.php`](../resources/views/components/input-label.blade.php) | Label input |
| [`input-error.blade.php`](../resources/views/components/input-error.blade.php) | Pesan error input |
| [`nav-link.blade.php`](../resources/views/components/nav-link.blade.php) | Link navigasi |
| [`responsive-nav-link.blade.php`](../resources/views/components/responsive-nav-link.blade.php) | Link navigasi responsif |
| [`primary-button.blade.php`](../resources/views/components/primary-button.blade.php) | Tombol utama |
| [`secondary-button.blade.php`](../resources/views/components/secondary-button.blade.php) | Tombol sekunder |
| [`danger-button.blade.php`](../resources/views/components/danger-button.blade.php) | Tombol bahaya |
| [`auth-session-status.blade.php`](../resources/views/components/auth-session-status.blade.php) | Status sesi auth |
| [`application-logo.blade.php`](../resources/views/components/application-logo.blade.php) | Logo aplikasi |

## Alpine.js

Alpine.js digunakan untuk interaktivitas klien. Tidak ada jQuery, tidak ada vanilla DOM scripts yang tidak perlu.

- **Inisialisasi**: [`resources/js/app.js`](../resources/js/app.js:5) — `Alpine.start()` setelah Turbo mutations.
- **Data komponen**: `Alpine.data()` untuk komponen yang dapat digunakan ulang (mis. `searchAutocomplete`).
- **Turbo compatibility**: Alpine `x-data` otomatis diinisialisasi untuk node baru hasil body-swap Turbo via MutationObserver bawaan Alpine.
- **Contoh penggunaan**: booking form, galeri properti, modal, dropdown, dark mode toggle, sidebar collapsible.

## Hotwired Turbo (Turbo Drive)

- **Semua link same-origin dan form submission** di-navigasi oleh Turbo Drive — tidak perlu reload penuh.
- Progress bar diaktifkan dengan `Turbo.config.drive.progressBarDelay = 0` ([`app.js`](../resources/js/app.js:10)).
- **Jangan nonaktifkan Turbo** tanpa alasan yang jelas — jangan tambah pola reload penuh.
- Form yang perlu dihandle oleh Turbo (mis. booking form) menggunakan `method="POST"` + `action` standar.

## Tailwind CSS

- **Versi 3** (Tailwind v3, lihat [`package.json`](../package.json:17)).
- Gapai mobile-first: semua layout dimulai dari mobile, diperluas dengan `sm:`, `md:`, `lg:`, `xl:`.
- Dark mode: diadmin via `class` strategy (`localStorage.getItem('admin.theme')` + class `dark` di `<html>`, [`admin.blade.php`](../resources/views/layouts/admin.blade.php:27)).
- CSS di [`resources/css/app.css`](../resources/css/app.css) — di-import oleh Vite.

## Vite

- **Entry**: `resources/css/app.css` + `resources/js/app.js` — [`vite.config.js`](../vite.config.js:7).
- Build: `npm run build` (production).
- Dev: `npm run dev` (atau via `composer dev` yang juga menjalankan server + queue + logs).
- Tidak ada CDN libs untuk JS framework — semua via Vite atau CDN async (Font Awesome, Lucide Icons, Quill 2).

## Multi-Bahasa / _i18n_

- File terjemahan: [`lang/en.json`](../lang/en.json) + [`lang/id.json`](../lang/id.json).
- Dipanggil dengan `__('...')` — **jangan hardcode string UI** dalam bahasa Indonesia/Inggris yang seharusnya ada di file lang.
- **Locale resolution** (lihat [`LocaleMiddleware`](../app/Http/Middleware/LocaleMiddleware.php:22)):
  1. Session locale (set via language switcher).
  2. Default language dari DB `languages` table.
  3. Geo fallback: IP Indonesia → `id`, lainnya → `en`.
  4. App default locale.
- Public `/set-locale` dan admin `/admin/set-locale` endpoint untuk mengganti bahasa.

## Mata Uang / _Currency_

- Default: `IDR`, dapat diubah di Settings → General.
- Tampilan: pengguna dapat memilih mata uang tampilan via `/set-currency` (admin session).
- Kurs: [`CurrencyRate`](../app/Models/CurrencyRate.php) + [`CurrencyRateService`](../app/Services/CurrencyRateService.php) + fetch otomatis via `currency:fetch` setiap 6 jam.
- Admin: [`CurrencyRateController`](../app/Http/Controllers/Admin/CurrencyRateController.php) + [`resources/views/admin/currency/`](../resources/views/admin/currency).

## Lihat Juga / _See Also_

- [`docs/ADMIN.md`](ADMIN.md) — panduan admin (termasuk fitur frontend admin)
- [`docs/ARCHITECTURE.md`](ARCHITECTURE.md) — arsitektur umum
