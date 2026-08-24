# Security Audit Report — Sewa Apartemen CMS (Laravel)

**Tanggal audit:** 2026-08-24
**Mode:** Static code review (read-only). Tidak ada file aplikasi yang diubah.
**Auditor:** Security Reviewer (automated + manual tracing)
**Laporan:** Satu-satunya artefak yang dibuat — file Markdown ini.

---

## 1. Executive Summary

Audit menyeluruh repository Laravel "Sewa Apartemen CMS" menemukan **1 temuan Critical, 5 High, 4 Medium** dengan confidence tinggi, plus **9 item needs-verification**.

Temuan paling serius adalah **kebocoran data booking publik (PII + finansial)** melalui rute tanpa autentikasi dan tanpa kontrol kepemilikan ([`routes/web.php:60-61`](routes/web.php:60)), diperparah oleh **format kode booking yang dapat diprediksi** (`BK-YYYYMMDD-0001`). Kedua, **kegagalan integritas voucher/promo** — diskon tidak pernah diterapkan ke harga final padahal voucher sudah dikonsumsi, dan `voucher_id`/`promo_rate_id` dapat dipilih attacker tanpa kode. Ketiga, **race condition double-booking** (TOCTOU) karena pengecekan ketersediaan tanpa lock dan tanpa constraint database. Keempat, **keluarga XSS** melalui konten admin (HTML mentah) dan breakout JSON-LD.

Kontrol positif yang sudah ada: ORM parameterized (tidak ada SQL injection ditemukan), CSRF aktif default Laravel, escaping Blade default untuk output `{{ }}`, proteksi installer berlapis (IP whitelist + token + lock file), sanitasi path upload di [`MediaController.php:73-89`](app/Http/Controllers/MediaController.php:73), validasi URL redirect, security headers dasar, password hashing bcrypt 12 rounds.

Prioritas remediasi: (1) rotasi `APP_KEY` + kredensial DB (rahasia ada di disk workspace), (2) token acak per booking + hapus endpoint numerik, (3) perbaiki pipeline voucher/promo + transaksi tunggal, (4) lock/constraint ketersediaan, (5) sanitasi HTML konten admin + JSON-LD encoding.

> Catatan: File test (`tests/`) dan dokumen bukan bukti vulnerability kecuali eksplisit disebutkan. Temuan dilaporkan hanya bila input attacker-controlled terkonfirmasi dan proteksi framework tidak menutupnya.

---

## 2. Scope dan Limitations

**Scope (diperiksa):**
- Routes: `routes/web.php`, `routes/auth.php`, `routes/install.php`
- Middleware: `CheckInstalled`, `EnsureUserIsAdmin`, `ProtectInstaller`, `SecurityHeaders`, `RedirectMiddleware`, `LocaleMiddleware`, `RedirectIfNotInstalled`, `PreventAccessWhenInstalled`
- Controllers (33): Booking, Property, Media, Page, Post, Redirect, Settings, SEO, Installer, Admin/*, Auth/*
- Services: `BookingService`, `BookingPricingService`, `BookingNotificationService`, `SeoService`, `SchemaService`, `AnalyticsService`, `SettingsService`, `GeoLocaleService`, `RobotsService`, `SitemapService`
- Models (18), Form Requests (10), migrations, config (`app`, `session`, `auth`, `filesystems`, `logging`, `debugbar`), `.env`, `.gitignore`, `public/.htaccess`, `composer.json`, dokumen deployment

**Yang TIDAK diperiksa (limitation):**
- Runtime behavior live (tidak ada server/DB yang dieksekusi) — semua berbasis static trace.
- `composer.lock`/`package-lock.json` hanya ditinjau secara sinyal (audit dependency aktual `composer audit` tidak dijalankan).
- Infrastruktur nyata production (nginx/apache config server, DNS, TLS termination) — hanya dari dokumen deployment.
- Nila-nilai environment production tidak diketahui (hanya `.env` local).
- File > tertentu dibaca secara sampling (mis. `resources/views/properties/show.blade.php` 1006 baris dibaca pada titik kritis).

---

## 3. Severity Methodology

| Level | Definisi |
|-------|----------|
| **Critical** | Eksploitasi jarak jauh tanpa auth, dampak luas (PII massal / eksekusi / financial loss), mudah dieksploitasi |
| **High** | Eksploitasi membutuhkan sedikit prasyarat; dampak signifikan (PII, integritas finansial, XSS tersimpan) |
| **Medium** | Dampak terbatas, biasanya butuh role admin atau kondisi khusus; defense-in-depth |
| **Low** | Best practice / hardening — tidak dilaporkan sebagai finding |

**Confidence:**
- **High** — pola rentan + input attacker-controlled terkonfirmasi + proteksi framework tidak menutup
- **Medium** — pola rentan, jalur input/konfigurasi belum sepenuhnya terkonfirmasi (ditaruh di Needs Verification)
- **Low** — teoritis — tidak dilaporkan

---

## 4. Findings (High Confidence)

### [FIND-001] Kebocoran PII + data finansial booking publik, enumerable (Critical)
- **Lokasi:** [`routes/web.php:60-61`](routes/web.php:60), [`app/Http/Controllers/BookingController.php:88-93`](app/Http/Controllers/BookingController.php:88), [`app/Http/Controllers/BookingController.php:142-149`](app/Http/Controllers/BookingController.php:142), [`resources/views/bookings/success.blade.php:90-107`](resources/views/bookings/success.blade.php:90), [`resources/views/bookings/status.blade.php:126-136`](resources/views/bookings/status.blade.php:126)
- **Severity:** Critical · **Confidence:** High
- **Issue:** Dua endpoint publik tanpa autentikasi:
  1. `GET /bookings/{booking}/success` — implicit binding **auto-increment ID** numerik.
  2. `GET /booking/status/{code}` — kode sequential `BK-YYYYMMDD-0001` (lihat [`app/Services/BookingService.php:21-37`](app/Services/BookingService.php:21)).
  Tidak ada ownership check, signed URL, maupun secret token.
- **Evidence:**
  ```php
  // routes/web.php:60
  Route::get('/bookings/{booking}/success', [BookingController::class, 'success'])->name('bookings.success');
  // BookingController.php:88 — tidak ada auth, tidak ada policy
  public function success(Booking $booking): View { ... }
  // BookingService.php:36 — kode dapat diprediksi
  return "BK-{$datePrefix}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
  ```
- **Precondition / Impact:** Attacker cukup memanggil `/bookings/1/success` … `/bookings/N/success` atau mengiterasi `BK-<date>-0001..9999`. Halaman merender nama, email, nomor HP, WhatsApp, tanggal, tipe kamar, **total harga, deposit, breakdown harga**, dan kode booking. Throttle `30,1` pada status hanya memperlambat, tidak mencegah enumerasi per-IP.
- **Remediation (jangan diterapkan sekarang):**
  1. Hapus endpoint `success` berbasis ID numerik dari akses publik.
  2. Ganti kode booking dengan token acak (mis. `Str::random(16)`) atau tambahkan `access_token` unik per booking yang dihasilkan server.
  3. Lookup hanya via token; jangan pernah render PII lengkap tanpa verifikasi tambahan (mis. OTP/email).
  4. Tambahkan rate limit ketat + monitoring anomaly pada endpoint status.

---

### [FIND-002] Rahasia ter-commit di workspace `.env` (High)
- **Lokasi:** [`.env:3`](.env:3) (`APP_KEY`), [`.env:34`](.env:34) (password DB plaintext), [`.env:4`](.env:4) (`APP_DEBUG=true`)
- **Severity:** High · **Confidence:** High (file ada di disk workspace; gitignore tidak menahan copy yang sudah bocor)
- **Issue:** `.env` local berisi `APP_KEY` dan password MySQL plaintext. `.gitignore:2` mengecualikan `.env` dari git, tetapi file tetap ada di workspace, backup, archive, atau screen-share. Jika bocor, attacker memegang kunci enkripsi cookie/session dan kredensial DB.
- **Evidence:** `.env:3` `APP_KEY=base64:…` (nilai di-redact), `.env:34` `DB_PASSWORD="…"` (redacted).
- **Impact:** Decrypt semua session/cookie bertanda tangan, akses DB penuh (baca semua booking/PII, modifikasi data), potensi RCE via SQL jika DB user punya FILE/privilege.
- **Remediation:**
  1. **Rotasi segera**: ganti `APP_KEY` (semua session invalid) dan password DB.
  2. Jangan pernah menyalin `.env` production ke workspace dev.
  3. Hapus history git bila pernah ter-commit (`git filter-repo`).
  4. Production: set `APP_DEBUG=false`, `APP_ENV=production`.

---

### [FIND-003] Voucher/promo integrity broken — diskon tidak diterapkan, voucher dibakar (High)
- **Lokasi:** [`app/Http/Controllers/BookingController.php:28-43`](app/Http/Controllers/BookingController.php:28), [`app/Services/BookingService.php:98-105`](app/Services/BookingService.php:98), [`app/Services/BookingPricingService.php:30-39`](app/Services/BookingPricingService.php:30), [`app/Http/Requests/BookingRequest.php:47-50`](app/Http/Requests/BookingRequest.php:47)
- **Severity:** High · **Confidence:** High
- **Issue:** Tiga cacat terkonfirmasi:
  1. **`voucher_id` diterima tanpa kode** — attacker dapat mengirim `voucher_id` numerik (enumerable, `exists:vouchers,id` saja) dan membakar `used_count` voucher tanpa tahu kodenya.
  2. **Transaksi terpisah** — increment `used_count` di-commit dalam transaksi pertama, lalu `BookingService::create()` berjalan di transaksi kedua. Jika pembuatan booking gagal, voucher tetap terpakai.
  3. **Diskon tidak pernah diterapkan** — `BookingService::create()` memanggil `calculate()` **tanpa** `$promoRateId`/`$voucherId`, padahal keduanya didukung di [`BookingPricingService.php:37-38`](app/Services/BookingPricingService.php:37) dan `applyVoucher()` ([`:221-250`](app/Services/BookingPricingService.php:221)). Customer dibayar penuh, voucher hangus.
- **Evidence:**
  ```php
  // BookingController.php:29-42 — transaksi #1 (increment) terpisah dari create()
  DB::transaction(function () use (&$data) {
      $voucher = !empty($data['voucher_id'])
          ? Voucher::where('id', $data['voucher_id'])->lockForUpdate()->first()
          : Voucher::where('code', $code)->lockForUpdate()->first();
      ...
      $voucher->increment('used_count');
  });
  // BookingService.php:98-105 — promoRateId/voucherId TIDAK diteruskan
  $pricing = app(BookingPricingService::class)->calculate($property, $data['unit_type'], ...);
  ```
- **Precondition / Impact:** Endpoint publik [`POST /bookings`](routes/web.php:59) (throttle 10/min). Attacker mengirim `voucher_id` = 1..N untuk menemukan voucher aktif; setiap request yang berhasil meng-increment `used_count` dan membuat booking **tanpa diskon** — pelanggan sah dirugikan (bayar penuh), voucher habis sia-sia.
- **Remediation:**
  1. Satu transaksi: lock voucher → validasi (wajib `code`, bukan `id`) → hitung harga **dengan** voucher/promo → buat booking → increment.
  2. Teruskan `$data['voucher_id']`/`$promo_rate_id` ke `calculate()`.
  3. Hapus jalur `voucher_id` dari request; hanya terima `voucher_code` (seperti `validateVoucher` di [`BookingController.php:100-104`](app/Http/Controllers/BookingController.php:100)).
  4. Validasi `promo_rate_id` milik `property_id` yang sama (cek kepemilikan, bukan `exists` saja).

---

### [FIND-004] Double-booking race condition (TOCTOU) (High)
- **Lokasi:** [`app/Services/BookingService.php:111`](app/Services/BookingService.php:111), [`app/Services/BookingService.php:162-184`](app/Services/BookingService.php:162), migration [`database/migrations/2026_08_11_162521_create_bookings_table.php:14-55`](database/migrations/2026_08_11_162521_create_bookings_table.php:14)
- **Severity:** High · **Confidence:** High
- **Issue:** `validateAvailability()` hanya `exists()` SELECT tanpa `lockForUpdate`; dua request konkuren melewati pengecekan lalu sama-sama insert. **Transit di-skip total** ([`:164-166`](app/Services/BookingService.php:164)). Tidak ada constraint overlap di tabel `bookings`.
- **Evidence:**
  ```php
  // BookingService.php:172-179
  $conflicting = Booking::where('property_id', $propertyId)
      ->where('unit_type', $unitType)
      ->where('status', '!=', 'cancelled')
      ->where(function ($q) { $q->where('check_in', '<', $checkOut)->where('check_out', '>', $checkIn); })
      ->exists();
  ```
- **Impact:** Overbooking unit yang sama pada rentang tanggal sama; kerugian operasional, konflik pelanggan, risiko reputasi. Transit (jam) paling rawan karena tanpa pengecekan sama sekali.
- **Remediation:**
  1. `lockForUpdate()` pada baris konflik kandidat di dalam satu transaksi yang sama dengan insert.
  2. Tambahkan DB constraint anti-overlap (exclusion constraint / trigger) sebagai lapisan terakhir.
  3. Wajibkan pengecekan transit juga (window check-in/check-out efektif).

---

### [FIND-005] Stored XSS via konten HTML admin (High)
- **Lokasi:** [`resources/views/properties/show.blade.php:92`](resources/views/properties/show.blade.php:92), [`resources/views/blog/show.blade.php:31`](resources/views/blog/show.blade.php:31), [`resources/views/pages/show.blade.php:20`](resources/views/pages/show.blade.php:20), [`resources/views/pages/show.blade.php:29`](resources/views/pages/show.blade.php:29); input: [`app/Http/Requests/PropertyRequest.php:36`](app/Http/Requests/PropertyRequest.php:36), [`app/Http/Requests/PageRequest.php:37`](app/Http/Requests/PageRequest.php:37)
- **Severity:** High · **Confidence:** High (input admin; dieksekusi terhadap semua visitor)
- **Issue:** `description`/`content` disimpan mentah (`nullable|string`, tanpa sanitizer) dan dirender `{!! !!}`. Role `admin` (bukan hanya `super-admin`) diizinkan oleh [`app/Models/User.php:60-63`](app/Models/User.php:60). Admin yang dikompromi atau akun admin kehilangan kredensial dapat menyuntik `<script>` yang berjalan di browser **semua pengunjung situs**.
- **Evidence:** `{!! $property->description !!}` (show.blade.php:92)
- **Impact:** Session hijack visitor, defacement, phishing, keylogging, eksfiltrasi token CSRF.
- **Remediation:**
  1. Sanitasi HTML pada write dengan allowlist (HTMLPurifier atau setara); jangan percaya `{!! !!}` pada input admin mentah.
  2. Alternatif: render escaped (`{{ }}`) dan sediakan editor yang menyimpan format terpisah.
  3. Pertimbangkan memisahkan role: hanya `super-admin` yang boleh menyisipkan HTML mentah (dengan review).

---

### [FIND-006] JSON-LD `</script>` breakout (High)
- **Lokasi:** [`app/Services/SeoService.php:247-249`](app/Services/SeoService.php:247), dirender di [`resources/views/components/seo.blade.php:4`](resources/views/components/seo.blade.php:4) dan [`resources/views/layouts/frontend.blade.php:53`](resources/views/layouts/frontend.blade.php:53)
- **Severity:** High · **Confidence:** High
- **Issue:** `json_encode` tanpa `JSON_HEX_TAG`. Nilai schema berasal dari setting dan field model (site_name, title, description — diisi admin). String berisi `</script><script>…` menembus keluar blok `<script type="application/ld+json">`.
- **Evidence:**
  ```php
  // SeoService.php:247-249
  $html .= '<script type="application/ld+json">' . "\n"
         . json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
         . '</script>' . "\n";
  ```
- **Impact:** Stored XSS terhadap semua visitor saat halaman memuat SEO schema (mengikuti jalur yang sama dengan FIND-005 tapi vektor berbeda; menyentuh nilai tanpa sanitasi string penuh).
- **Remediation:**
  ```php
  json_encode($clean, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
  ```

---

### [FIND-007] Ekstensi file gallery dari client tanpa validasi (Medium, admin-gated)
- **Lokasi:** [`app/Http/Controllers/PropertyController.php:384-386`](app/Http/Controllers/PropertyController.php:384), validasi [`app/Http/Requests/PropertyRequest.php:71`](app/Http/Requests/PropertyRequest.php:71)
- **Severity:** Medium · **Confidence:** High
- **Issue:** `$file->getClientOriginalExtension()` (dikontrol client) dipakai untuk nama file tersimpan, sedangkan validasi hanya cek content MIME. Admin mengunggah konten gambar bernama `shell.php` → tersimpan `properties/{id}/{cat}/shell.php` di disk `public`. Jika server mengeksekusi PHP di bawah `/storage` (misconfiguration), terjadi RCE.
- **Evidence:**
  ```php
  // PropertyController.php:384
  $filename = $safeName . '-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
  ```
- **Impact:** Potensi RCE (bergantung konfigurasi PHP handler); setidaknya penyimpanan file dengan nama mencurigakan. MediaController sudah benar ([`MediaController.php:87-89`](app/Http/Controllers/MediaController.php:87)) — pakai `extensionFromMime()`.
- **Remediation:** Gunakan peta MIME→extension seperti `MediaController`; pastikan PHP tidak dieksekusi di `/storage` (`php_admin_flag engine off` atau `.htaccess` deny).

---

### [FIND-008] SVG upload — stored XSS same-origin (Medium, admin-gated)
- **Lokasi:** [`app/Http/Requests/MediaRequest.php:29`](app/Http/Requests/MediaRequest.php:29), disajikan dari `storage/app/public` via `/storage/...`
- **Severity:** Medium · **Confidence:** High
- **Issue:** `mimes:...,svg,...` mengizinkan `image/svg+xml`; isi tidak diperiksa. SVG ber-skrip dieksekusi saat dibuka langsung (navigasi ke URL file).
- **Impact:** XSS same-origin (cookie/CSRF token dapat dicuri) ketika visitor membuka URL media; admin yang mengunggah SVG berbahaya menargetkan pengunjung/operator.
- **Remediation:** Hapus `svg` dari daftar mimes, atau layani dari origin terpisah / `Content-Disposition: attachment`, atau sanitasi konten SVG.

---

### [FIND-009] Privilege escalation role via UserController (Medium)
- **Lokasi:** [`app/Http/Controllers/Admin/UserController.php:53-61`](app/Http/Controllers/Admin/UserController.php:53), [`app/Http/Controllers/Admin/UserController.php:105-118`](app/Http/Controllers/Admin/UserController.php:105), middleware [`app/Http/Middleware/EnsureUserIsAdmin.php:16-23`](app/Http/Middleware/EnsureUserIsAdmin.php:16)
- **Severity:** Medium · **Confidence:** High
- **Issue:** Group admin menerima `super-admin` **dan** `admin` ([`app/Models/User.php:60-63`](app/Models/User.php:60)). `UserController::store/update` menerima `role_id` apa pun (`exists:roles,id`), jadi seorang `admin` dapat menaikkan dirinya (atau user lain) menjadi `super-admin`. Tidak ada check "hanya super-admin boleh assign super-admin".
- **Impact:** Eskalasi privilege penuh dari role admin standar.
- **Remediation:**
  1. Pisahkan izin: hanya `super-admin` yang boleh assign `super-admin`.
  2. Validasi `role_id` terhadap daftar role yang boleh diberikan oleh actor saat ini.
  3. Jangan izinkan actor mengubah role dirinya sendiri ke level lebih tinggi dalam satu request.

---

### [FIND-010] CSV export — formula injection (Medium, admin-gated)
- **Lokasi:** [`app/Http/Controllers/BookingController.php:287-302`](app/Http/Controllers/BookingController.php:287)
- **Severity:** Medium · **Confidence:** High
- **Issue:** `fputcsv` menulis `customer_name`/`notes` (attacker-controlled via booking form) tanpa prefix. Sel yang diawali `=`, `+`, `-`, `@` dieksekusi sebagai formula oleh Excel/Sheets saat admin membuka export.
- **Evidence:** [`BookingController.php:289-300`](app/Http/Controllers/BookingController.php:289) — `$b->customer_name`, `$b->notes` ditulis mentah.
- **Impact:** Formula injection terhadap komputer admin (exfiltration file lokal via DDE/Web, macro prompt). Butuh admin membuka CSV — itulah precondition.
- **Remediation:** Prefix sel teks dengan `'` atau tab untuk nilai yang diawali karakter formula (`= + - @ \t \r`).

---

## 5. Needs Verification

### [VERIFY-001] Installer `USE \`{$database}\`` — SQL injection / identifier injection
- **Lokasi:** [`app/Http/Controllers/InstallerController.php:310-336`](app/Http/Controllers/InstallerController.php:310), khususnya `:323` `$pdo->exec("USE \`{$database}\`")`; juga DSN dibangun dari input (`:319`).
- **Pertanyaan:** Apakah `INSTALLER_ALLOWED_IPS` / `INSTALLER_TOKEN` dikonfigurasi production, dan `storage/installed.lock` ada? Jika token kosong dan bukan localhost, route tertutup (`ProtectInstaller`). Jika token lemah/bocor, `db_database` dapat menyuntik backtick/query dan `db_host` dapat mengarahkan koneksi (SSRF-ish ke port internal).
- **Rekomendasi (verifikasi di deployment):** pastikan token kuat + IP whitelist production, nonaktifkan route setelah install, gunakan parameter binding untuk identifier (whitelist karakter ``[A-Za-z0-9_$]``).

### [VERIFY-002] Kredensial plaintext di `storage/app/install_state.json`
- **Lokasi:** [`app/Http/Controllers/InstallerController.php:42-58`](app/Http/Controllers/InstallerController.php:42), `:275-277`
- **Pertanyaan:** Apakah file state dihapus pada semua jalur selesai install (termasuk kegagalan/cancel)? Apakah direktori `storage/app` tidak tersaji publik? (default Laravel aman, tapi cPanel fallback bisa berbeda — lihat VERIFY-008).

### [VERIFY-003] `APP_DEBUG=true` + `LOG_LEVEL=debug` di `.env` local
- **Lokasi:** [`.env:4`](.env:4), [`.env:26`](.env:26), [`config/app.php:42`](config/app.php:42)
- **Pertanyaan:** Apakah production memaksa `APP_DEBUG=false`? Jika `true` di production, stack trace + query + env dibocorkan ke attacker.

### [VERIFY-004] `SESSION_SECURE_COOKIE` tidak disetel
- **Lokasi:** [`.env:36-41`](.env:36), [`config/session.php:172`](config/session.php:172)
- **Pertanyaan:** Production HTTPS menetapkan `SESSION_SECURE_COOKIE=true`? Tanpa flag `Secure`, session cookie dapat dikirim via HTTP (interception). `http_only` default `true` (baik).

### [VERIFY-005] PII lengkap di-log pada setiap event booking
- **Lokasi:** [`app/Services/BookingNotificationService.php:41`](app/Services/BookingNotificationService.php:41)
- **Pertanyaan:** Apakah retention log & akses log dikontrol (log berisi nama, email, HP, WhatsApp, alamat properti)? Untuk kepatuhan privasi (UU PDP), pertimbangkan redaksi/pseudonimisasi di log.

### [VERIFY-006] `max_guests` properti tidak ditegakkan
- **Lokasi:** [`app/Http/Requests/PropertyRequest.php:50`](app/Http/Requests/PropertyRequest.php:50), [`app/Services/BookingService.php:132`](app/Services/BookingService.php:132)
- **Pertanyaan:** `guests` dibatasi `max:20` global; apakah properti dengan `max_guests` lebih kecil perlu membatasi booking? Saat ini kapasitas per-properti tidak memengaruhi validasi.

### [VERIFY-007] Deployment cPanel: doc-root fallback + deny rules
- **Lokasi:** [`docs/DEPLOYMENT-CPANEL.md:81-105`](docs/DEPLOYMENT-CPANEL.md:81)
- **Pertanyaan:** Apakah `.env`, `storage/`, `bootstrap/`, `vendor/` diblokir akses web di konfigurasi Apache produksi? Apakah doc-root mengarah ke `public/`?

### [VERIFY-008] Enumerasi user via password reset
- **Lokasi:** [`app/Http/Controllers/Auth/PasswordResetLinkController.php:36-43`](app/Http/Controllers/Auth/PasswordResetLinkController.php:36)
- **Pertanyaan:** Apakah respons "user not found" dibedakan dari "link sent"? Jika ya, enumerasi email pengguna terdaftar.

### [VERIFY-009] Rate limit hanya pada sebagian endpoint
- **Lokasi:** [`routes/web.php:37-62`](routes/web.php:37)
- **Pertanyaan:** `POST /bookings` (10/min) dan status (30/min) ter-throttle; endpoint lain (auth login default Laravel) sudah default. Verifikasi `login`/`register` throttle dan apakah `install/*` (saat terbuka) butuh rate limit.

---

## 6. Positive Controls (yang sudah baik)

| Area | Kontrol | Lokasi |
|------|---------|--------|
| SQL injection | ORM parameterized di seluruh app; tidak ada `DB::raw`/interpolasi SQL pada input user | seluruh repo |
| CSRF | Middleware default Laravel `web` group aktif | `bootstrap/app.php` |
| XSS escaping | Blade `{{ }}` default escape | seluruh views |
| Installer | Lock file + IP whitelist + token (`hash_equals`) | [`app/Http/Middleware/ProtectInstaller.php:24-52`](app/Http/Middleware/ProtectInstaller.php:24) |
| Path traversal (media) | Sanitasi folder: strip `..`, whitelist chars | [`app/Http/Controllers/MediaController.php:73-78`](app/Http/Controllers/MediaController.php:73) |
| Extension upload (media) | MIME→extension server-side | [`app/Http/Controllers/MediaController.php:83-89`](app/Http/Controllers/MediaController.php:83) |
| Open redirect (CMS) | Validasi `to_url`: relatif atau `https?://`, blokir `javascript:`/`data:` | [`app/Http/Controllers/RedirectController.php:54-63`](app/Http/Controllers/RedirectController.php:54) |
| Security headers | X-Frame-Options, nosniff, Referrer-Policy | [`app/Http/Middleware/SecurityHeaders.php:15-17`](app/Http/Middleware/SecurityHeaders.php:15) |
| Password | bcrypt 12 rounds | `.env:20`, `config/hashing` |
| Session cookie | `http_only` default true, SameSite default lax | [`config/session.php:185-199`](config/session.php:185) |
| Sensitive attribute | `password`, `remember_token` hidden | [`app/Models/User.php:17`](app/Models/User.php:17) |
| Voucher concurrency (parsial) | `lockForUpdate` pada increment voucher | [`app/Http/Controllers/BookingController.php:32-33`](app/Http/Controllers/BookingController.php:32) |
| Kode booking unik | transaction + `lockForUpdate` + unique constraint | [`app/Services/BookingService.php:23-37`](app/Services/BookingService.php:23) |
| Upload logo/favicon | disimpan ke disk, path dari server | [`app/Http/Controllers/SettingsController.php:160-175`](app/Http/Controllers/SettingsController.php:160) |
| Webhook | timeout 5s, signature HMAC opsional | [`app/Services/BookingNotificationService.php:50-60`](app/Services/BookingNotificationService.php:50) |

---

## 7. Prioritized Remediation Plan

| # | Prioritas | Aksi | Terkait |
|---|-----------|------|---------|
| 1 | **Urgent** | Rotasi `APP_KEY` + password DB; set `APP_DEBUG=false` di production; pastikan `.env` tidak pernah masuk VCS/backup publik | FIND-002 |
| 2 | **Urgent** | Hapus endpoint booking numerik publik; tambah token acak per booking; batasi render PII | FIND-001 |
| 3 | **High** | Perbaiki pipeline voucher/promo: satu transaksi, terima `code` saja, teruskan discount ke `calculate()` | FIND-003 |
| 4 | **High** | Lock/constraint anti-overlap booking (termasuk transit) | FIND-004 |
| 5 | **High** | Sanitasi HTML konten admin + `JSON_HEX_TAG` pada JSON-LD | FIND-005, FIND-006 |
| 6 | **Medium** | MIME→extension di PropertyController; hapus/isolasi SVG | FIND-007, FIND-008 |
| 7 | **Medium** | Pisahkan izin assign role super-admin | FIND-009 |
| 8 | **Medium** | Prefix anti-formula pada export CSV | FIND-010 |
| 9 | **Verify** | Selesaikan VERIFY-001..009 sesuai hasil deployment check | Section 5 |

---

## 8. Validation Checklist

Gunakan setelah remediasi. Semua item harus berlalu sebelum deploy.

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` baru, kredensial DB baru — FIND-002
- [ ] `/bookings/{id}/success` tidak lagi dapat diakses publik; status booking butuh token — FIND-001
- [ ] Tidak ada request yang bisa membuat booking tanpa kode voucher valid; diskon terlihat di `total_price` — FIND-003
- [ ] Tes konkurensi (2 request paralel tanggal sama) hanya menghasilkan 1 booking — FIND-004
- [ ] Konten property/page/blog yang mengandung `<script>` ter-sanitasi/escape saat render — FIND-005
- [ ] Nilai SEO berisi `</script>` tidak menembus blok JSON-LD — FIND-006
- [ ] Upload gallery dengan nama `shell.php` tersimpan dengan extension sesuai MIME — FIND-007
- [ ] Upload SVG ditolak atau disajikan sebagai attachment — FIND-008
- [ ] Role `admin` tidak dapat assign `super-admin` — FIND-009
- [ ] CSV export: sel berawalan `=`/`+`/`-`/`@` di-prefix — FIND-010
- [ ] Instalasi ulang (hapus `installed.lock`) tidak dapat diakses publik tanpa token — VERIFY-001/002
- [ ] `SESSION_SECURE_COOKIE=true` pada HTTPS — VERIFY-004
- [ ] cPanel: doc-root `public/`, deny `.env`/`storage/`/`bootstrap/`/`vendor` — VERIFY-007
- [ ] Password reset tidak membedakan user terdaftar/tidak — VERIFY-008
- [ ] `composer audit` & `npm audit` bersih (belum dijalankan dalam audit ini) — supply-chain

---

## 9. Catatan Metodologis

- File `tests/` dan `docs/` tidak dianggap sebagai bukti vulnerability; hanya dipakai konteks.
- Semua path relatif terhadap root repo `d:/Projects/Sewa Apartemen CMS`.
- Nilai rahasia aktual (APP_KEY, DB_PASSWORD) di-redact dalam laporan ini.
- Tidak ada file selain `docs/security-audit-report.md` yang dibuat/diubah selama audit ini.
