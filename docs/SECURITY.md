# Keamanan

_Security_

Praktik keamanan yang **benar-benar berlaku** di kode Artivo CMS, plus risiko yang diketahui. Untuk analisis mendalam, rujuk laporan audit yang ada — dokumen ini tidak mengulang semuanya.

_Security practices **actually in force** in the Artivo CMS codebase, plus known risks. For in-depth analysis, refer to the existing audit reports — this document does not restate everything._

---

## Praktik yang Berlaku / _Practices in Force_

### 1. Validasi Input via FormRequest

Semua input admin melewati **FormRequest** di [`app/Http/Requests/`](../app/Http/Requests): `PropertyRequest`, `BookingRequest`, `MediaRequest`, `AmenityRequest`, `BlockRequest`, `NavigationRequest`, `PageRequest`, dst. Jangan menerima data request yang tidak tervalidasi ke model.

- Pengaturan SEO divalidasi **allowlist ketat** (regex) karena di-interpolasi ke `<script>` inline — lihat [`docs/SEO.md`](SEO.md).

### 2. Otorisasi Admin

- Middleware `admin` → [`EnsureUserIsAdmin`](../app/Http/Middleware/EnsureUserIsAdmin.php) (`User::isAdmin()`, menerima `super-admin` + `admin`).
- Aksi destruktif (rollback git, settings) hanya untuk super-admin — contoh `authorize()`/`abort(403)` di [`gitRollback`](../app/Http/Controllers/SettingsController.php:860).
- **Tidak ada `Policies/`** — gunakan `authorize()` di controller atau middleware `admin`.
- **Cegah IDOR** — jangan pernah percaya ID yang dikontrol user untuk fetch resource admin; scoping ke otorisasi admin.

### 3. Git Dashboard — Symfony Process Argument Arrays

Semua operasi git di [`GitService::runGit()`](../app/Services/GitService.php:64) memakai `Symfony\Component\Process\Process` dengan **argumen array** — tidak pernah shell string. SHA rollback divalidasi regex `^[0-9a-f]{7,40}$` **dan** di-resolve ke objek `commit` nyata sebelum checkout. Tidak ada input user yang mencapai shell interpreter.

### 4. Embed Peta Kontak — [`MapEmbedService`](../app/Services/MapEmbedService.php)

Embed peta kontak **wajib** lewat `MapEmbedService` (sanitasi URL) — **tidak pernah** render iframe mentah dari URL settings. Test: [`tests/Feature/ContactMapEmbedTest.php`](../tests/Feature/ContactMapEmbedTest.php).

### 5. SSRF pada Import Media dari URL

Media admin mendukung import dari URL — dilindungi anti-SSRF. Test: [`tests/Feature/MediaUrlImportSsrfTest.php`](../tests/Feature/MediaUrlImportSsrfTest.php).

### 6. Output Escaping

- Blade `{{ }}` auto-escape. `{!! !!}` hanya untuk HTML yang disengaja di-sanitasi (whitelist block content via [`SafeHtmlService`](../app/Services/SafeHtmlService.php)).
- JS: autocomplete search meng-escape judul & query sebelum `x-html` (lihat [`resources/js/app.js`](../resources/js/app.js:42)).

### 7. Header Keamanan

Middleware [`SecurityHeaders`](../app/Http/Middleware/SecurityHeaders.php) (X-Frame-Options, X-Content-Type-Options, dll.) dipasang di group `web`.

### 8. Lainnya

- CSRF protection di semua form.
- Password di-hash (Laravel default `hashed` cast).
- Rate limiting: booking store `throttle:10,1`, status `throttle:30,1`, validate-voucher `throttle:20,1`, contact store `throttle:5,1` + captcha, search suggest `throttle:30,1`.
- Captcha (reCAPTCHA v2/v3, hCaptcha, Turnstile) via [`CaptchaService`](../app/Services/CaptchaService.php) + middleware `captcha`.
- Force HTTPS via [`ForceHttps`](../app/Http/Middleware/ForceHttps.php).
- Installer dilindungi [`ProtectInstaller`](../app/Http/Middleware/ProtectInstaller.php) (localhost/whitelist/token).
- Jangan pernah mengekspos kredensial, kunci, atau nilai `.env` di kode/commit/log/response.

## Risiko yang Diketahui / _Known Risks_

| Risiko | Status | Rekomendasi |
|--------|--------|-------------|
| **SVG upload diizinkan** | ⚠️ Stored-XSS risk | Sanitasi atau batasi ke role tepercaya; jangan perluas penerimaan SVG tanpa mitigasi |
| **`APP_DEBUG=true` di produksi** | ⚠️ Temuan audit (SEC-critical) | Set `APP_DEBUG=false` di produksi. Jangan membuat lebih buruk (jangan log stack trace/payload) |
| **`GEOAPIFY_MAP_KEY` = `GEOAPIFY_API_KEY`** | ⚠️ Kunci Places terkirim ke browser | Gunakan kunci browser terpisah (lihat [`docs/NEARBY-PLACES.md`](NEARBY-PLACES.md)) |
| **`cyberstrike.json` di working tree** | ✅ Di-[`.gitignore`](../.gitignore) | Pastikan tidak ter-commit dengan sekret; jangan tambahkan sekret ke file tracked |
| **Queue `sync`** | ℹ️ Bukan risiko keamanan | Job Geoapify berjalan inline saat request admin; pertimbangkan driver nyata |

## Audit Referensi / _Reference Audits_

- [`docs/security-audit-2026-08-27.md`](security-audit-2026-08-27.md) — audit keamanan (grounding: [`AGENTS.md §15`](../AGENTS.md))
- [`docs/security-audit-report.md`](security-audit-report.md) — laporan audit (dokumen lama)
- `GEOAPIFY Integration Security Audit Report.md` (root) — audit spesifik Geoapify
- [`docs/_agent-audit/AUDIT-FINDINGS.md`](_agent-audit/AUDIT-FINDINGS.md) — temuan audit (read-only source of truth)
- [`docs/bug-audit-report.md`](bug-audit-report.md) — laporan audit bug

## Lihat Juga / _See Also_

- [`docs/SEO.md`](SEO.md) — validasi ketat field analytics
- [`docs/VERSION-CONTROL.md`](VERSION-CONTROL.md) — keamanan git dashboard
- [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) — env vars & produksi
