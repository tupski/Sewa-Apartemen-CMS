# Pengujian

_Testing_

Strategi pengujian **Artivo CMS**: PHPUnit dengan SQLite in-memory. **Bukan Pest.**

_Testing strategy for **Artivo CMS**: PHPUnit with SQLite in-memory. **Not Pest.**_

---

## Cara Menjalankan / _How to Run_

```bash
# Jalankan semua test
php artisan test

# Jalankan file spesifik
php artisan test --filter=BookingFlowTest
vendor/bin/phpunit --filter=test_overlapping_booking_is_rejected

# Jalankan satu test dengan nama method
php artisan test --filter=test_name_here
```

Skrip `composer test` juga tersedia (config:clear + test).

## Konfigurasi / _Configuration_

- PHPUnit `^12.5.12` ([`composer.json`](../composer.json:23)).
- **Database test: SQLite in-memory** ([`phpunit.xml`](../phpunit.xml)) — test **tidak boleh** bergantung pada perilaku spesifik MySQL.
- Test ada di [`tests/Feature/`](../tests/Feature) (+ subdirektori `Auth/`).

## Suite yang Ada / _Existing Suites_

| Suite | Cakupan |
|-------|---------|
| [`Auth/AuthenticationTest.php`](../tests/Feature/Auth/AuthenticationTest.php), [`EmailVerificationTest.php`](../tests/Feature/Auth/EmailVerificationTest.php), [`PasswordConfirmationTest.php`](../tests/Feature/Auth/PasswordConfirmationTest.php), [`PasswordResetTest.php`](../tests/Feature/Auth/PasswordResetTest.php), [`PasswordUpdateTest.php`](../tests/Feature/Auth/PasswordUpdateTest.php), [`RegistrationTest.php`](../tests/Feature/Auth/RegistrationTest.php) | Auth Breeze: login, register, verifikasi email, reset password |
| [`BookingFlowTest.php`](../tests/Feature/BookingFlowTest.php) | Alur booking end-to-end |
| [`CrudTest.php`](../tests/Feature/CrudTest.php) | CRUD resource admin |
| [`BlogTest.php`](../tests/Feature/BlogTest.php) | Blog (posts, kategori, tag) |
| [`AnalyticsTest.php`](../tests/Feature/AnalyticsTest.php) | Rendering analytics (GA4, GTM, Pixel, Clarity, Search Console) |
| [`AccessibilityTest.php`](../tests/Feature/AccessibilityTest.php) | Aksesibilitas admin (skip nav, landmark main, h1, lang, role navigation) |
| [`BackupRestoreValidationTest.php`](../tests/Feature/BackupRestoreValidationTest.php) | Validasi backup & restore |
| [`ContactMapEmbedTest.php`](../tests/Feature/ContactMapEmbedTest.php) | Embed peta kontak via `MapEmbedService` (sanitasi, bukan iframe mentah) |
| [`DashboardTest.php`](../tests/Feature/DashboardTest.php) | Dashboard admin |
| [`ForceHttpsTest.php`](../tests/Feature/ForceHttpsTest.php) | Middleware ForceHttps |
| [`InstallerTest.php`](../tests/Feature/InstallerTest.php) | Web installer |
| [`MediaUrlImportSsrfTest.php`](../tests/Feature/MediaUrlImportSsrfTest.php) | Proteksi SSRF pada import media URL |
| [`PropertyNearbyPlacesTest.php`](../tests/Feature/PropertyNearbyPlacesTest.php) | Jalur manual `nearby_places` JSON |
| [`GeoapifyNearbyPlacesTest.php`](../tests/Feature/GeoapifyNearbyPlacesTest.php) | Pipeline Geoapify persisten — `Http::preventStrayRequests()` + `Http::fake()`; mem-pin bahwa halaman properti publik mengeluarkan **0 request HTTP keluar** |
| [`SecurityTest.php`](../tests/Feature/SecurityTest.php) | Termasuk `test_overlapping_booking_is_rejected` (konflik booking) |
| [`SitemapTest.php`](../tests/Feature/SitemapTest.php) | `/sitemap.xml` (200, XML, isi URL, well-formed) |
| [`SeoTest.php`](../tests/Feature/SeoTest.php) | Metadata SEO & analytics di halaman |
| [`SeoSettingsValidationTest.php`](../tests/Feature/SeoSettingsValidationTest.php) | Validasi allowlist field SEO settings |
| [`NotificationWebhookTest.php`](../tests/Feature/NotificationWebhookTest.php) | Webhook notifikasi |
| [`PerformanceTest.php`](../tests/Feature/PerformanceTest.php) | Pemeriksaan performa (mis. zero outbound HTTP) |
| [`ProfileTest.php`](../tests/Feature/ProfileTest.php) | Profil pengguna |
| [`VersionCreditTest.php`](../tests/Feature/VersionCreditTest.php) | Kredit "Powered by Artivo" + versi |
| [`WeekendPriceDisplayTest.php`](../tests/Feature/WeekendPriceDisplayTest.php) | Harga weekend di tampilan |
| [`GitRollbackFeatureTest.php`](../tests/Feature/GitRollbackFeatureTest.php), [`GitUpdateCheckTest.php`](../tests/Feature/GitUpdateCheckTest.php), [`GitDashboardErrorTest.php`](../tests/Feature/GitDashboardErrorTest.php), [`GitPostUpdateTest.php`](../tests/Feature/GitPostUpdateTest.php) | Dashboard git: rollback, update check, error handling, post-update |

## Konvensi / _Conventions_

- **Perubahan bisnis-kritis WAJIB punya test**: pricing, booking, voucher, auth. Jangan pernah mengubah layanan canonical tanpa test yang menutupinya.
- **Bug fix sertakan regression test** bila praktis — tulis test yang gagal dulu, lalu perbaiki.
- **Jangan hapus/lemahkan test** demi hijau — perbaiki kode atau niat test, bukan menghapus coverage.
- Gunakan factory (`PropertyFactory`, `BookingFactory`, `PostFactory`, `UserFactory`) — abaikan `UnitFactory` yang mati.
- Gunakan `Http::fake()` + `Http::preventStrayRequests()` untuk mem-pin bahwa tidak ada panggilan HTTP keluar saat render.

## Lihat Juga / _See Also_

- [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) — jalankan test sebelum deploy
- [`AGENTS.md §16`](../AGENTS.md) — testing rules
- `phpunit.xml` — konfigurasi test
