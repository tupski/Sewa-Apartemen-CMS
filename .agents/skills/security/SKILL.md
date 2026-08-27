---
name: security
description: >-
  Use when auditing or changing security-sensitive code: input validation,
  authorization, uploads, SSRF, XSS, secrets, security headers, or captcha.
  Trigger phrases: "security review", "validate input", "authorize admin",
  "prevent IDOR", "sanitize upload", "SSRF", "XSS", "security headers",
  "captcha". Grounded in docs/security-audit-2026-08-27.md.
---

# Purpose
Encode this repo's real security posture and known issues so agents fix rather
than worsen them. Ground every claim in
[`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md) and the
actual middleware/services listed below.

# When to Use
- Any change to input handling, admin authorization, uploads, or external fetches.
- Reviewing code for injection, XSS, SSRF, IDOR, or secret exposure.
- Touching security headers, captcha, map embeds, or the admin Git dashboard.

# Rules
- Validate ALL input via FormRequest classes; use `$fillable` on models, never
  `$guarded`. Never mass-assign unvalidated request data.
- Authorize admin actions with the `admin` role middleware (aliased in
  [`bootstrap/app.php`](bootstrap/app.php) to
  [`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php)) plus
  `auth`/`verified`, or `authorize()` in the controller. There is NO `Policies/`
  directory — do not invent one.
- Prevent IDOR — scope queries to the authenticated admin; never trust a
  user-controlled `id` to fetch an admin resource (guest bookings use
  `access_token`, not `id`).
- Uploads: enforce the type/size allowlist in the media FormRequest. SVG upload
  is allowed and is a stored-XSS risk — treat SVGs as untrusted HTML; restrict or
  sanitize, do not silently widen acceptance.
- Keep the SSRF guard on media URL import intact (covered by
  [`tests/Feature/MediaUrlImportSsrfTest.php`](tests/Feature/MediaUrlImportSsrfTest.php)).
- Contact map embeds MUST render through
  [`MapEmbedService::iframe()`](app/Services/MapEmbedService.php) — never echo a
  raw iframe/URL from settings (see the `maps` skill).
- Admin Git dashboard uses Symfony Process — pass commands as ARRAY args, never
  shell strings (prevents shell injection).
- Security headers are applied globally via
  [`SecurityHeaders`](app/Http/Middleware/SecurityHeaders.php); captcha via the
  `captcha` alias → [`VerifyCaptcha`](app/Http/Middleware/VerifyCaptcha.php) +
  [`CaptchaService`](app/Services/CaptchaService.php). Do not bypass or remove them.
- Escape output — Blade `{{ }}` auto-escapes; use `{!! !!}` ONLY for
  intentionally-sanitized HTML (via [`SafeHtmlService`](app/Services/SafeHtmlService.php)).
  Never echo raw user input. Never trust client-side pricing.
- Never expose secrets. `APP_DEBUG=true` in production is a known issue — recommend
  `false`, and never log stack traces/dumps/request payloads. `cyberstrike.json`
  is listed in `.gitignore` but was historically tracked — do not add secrets to
  any tracked file.

# Workflow
1. Read the relevant audit finding in
   [`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md).
2. Add/adjust a FormRequest for input; confirm authorization middleware is applied.
3. For fetch/upload paths, verify SSRF/type guards remain; add a regression test.
4. For output, confirm escaping; route HTML through `SafeHtmlService`, maps through
   `MapEmbedService`.

# Common Mistakes
- Validating ad-hoc in the controller instead of a FormRequest.
- Assuming a `Policies/` directory exists.
- Rendering a raw iframe/URL for the contact map instead of `MapEmbedService`.
- Building Symfony Process commands as shell strings.
- Widening SVG/upload acceptance or weakening the SSRF guard.
- Using `{!! !!}` on unsanitized user input.

# Validation
- `php artisan test --filter=SecurityTest` and
  `--filter=MediaUrlImportSsrfTest`, `--filter=ContactMapEmbedTest`,
  `--filter=ForceHttpsTest` pass.
- Confirm the touched path validates input, authorizes, and escapes output.

# Related Files
- [`docs/security-audit-2026-08-27.md`](docs/security-audit-2026-08-27.md)
- [`app/Http/Middleware/SecurityHeaders.php`](app/Http/Middleware/SecurityHeaders.php), [`app/Http/Middleware/VerifyCaptcha.php`](app/Http/Middleware/VerifyCaptcha.php), [`app/Http/Middleware/EnsureUserIsAdmin.php`](app/Http/Middleware/EnsureUserIsAdmin.php)
- [`app/Services/CaptchaService.php`](app/Services/CaptchaService.php), [`app/Services/MapEmbedService.php`](app/Services/MapEmbedService.php), [`app/Services/SafeHtmlService.php`](app/Services/SafeHtmlService.php), [`app/Services/BackupService.php`](app/Services/BackupService.php)
- [`tests/Feature/SecurityTest.php`](tests/Feature/SecurityTest.php), [`tests/Feature/MediaUrlImportSsrfTest.php`](tests/Feature/MediaUrlImportSsrfTest.php), [`tests/Feature/ContactMapEmbedTest.php`](tests/Feature/ContactMapEmbedTest.php), [`tests/Feature/ForceHttpsTest.php`](tests/Feature/ForceHttpsTest.php)
