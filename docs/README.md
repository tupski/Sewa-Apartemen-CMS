# Dokumentasi Artivo CMS

_Artivo CMS Documentation_

Daftar lengkap dokumen proyek untuk **Artivo CMS** — sistem manajemen sewa apartemen yang dibangun untuk **PT KAKARAMA Samudera Group** (deploy sebagai **Lya Rooms**).

_Complete index of project documentation for **Artivo CMS** — an apartment rental management system built for **PT KAKARAMA Samudera Group** (deployed as **Lya Rooms**)._

---

**Strategi bahasa:** setiap dokumen memimpin dengan Bahasa Indonesia, lalu terjemahan Inggris dalam baris _italic_. Ini konsisten dengan [`CHANGELOG.md`](../CHANGELOG.md) dan dipakai di seluruh set dokumentasi.

_Language strategy: each document leads with Indonesian, followed by the English translation in italic lines beneath. This is consistent with [`CHANGELOG.md`](../CHANGELOG.md) and applied across the whole documentation set._

---

## Dokumen Utama / _Core Documents_

| Dokumen | Deskripsi | _Description_ |
|---------|-----------|---------------|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Ikhtisar sistem: stack, direktori, pola Controller-Service, siklus request, auth | _System overview: stack, directory layout, Controller-Service pattern, request lifecycle, auth model_ |
| [`DATABASE.md`](DATABASE.md) | 23 model, relasi kunci, JSON columns, polymorphic morph, tabel pivot, absensi penting | _23 models, key relationships, JSON columns, polymorphic morph, pivot tables, notable absences_ |
| [`FRONTEND.md`](FRONTEND.md) | Blade + Alpine.js 3 + Turbo Drive + Tailwind v3 + Vite 8, i18n, komponen, mata uang | _Frontend stack: Blade, Alpine.js 3, Turbo Drive, Tailwind v3, Vite 8, i18n, components, currency_ |
| [`ADMIN.md`](ADMIN.md) | Panduan panel admin: setiap menu, fungsi, dan alur kerja (untuk non-developer) | _Admin panel guide: every menu, function, and workflow (for non-developer admins)_ |
| [`BOOKING.md`](BOOKING.md) | Alur pemesanan, status, layanan canonical, pengecekan konflik, kupon | _Booking flow, statuses, canonical services, conflict checking, vouchers_ |
| [`SEO.md`](SEO.md) | `SeoMetadata` morph, komponen `seo`, sitemap, robots.txt, redirect, analytics | _SEO architecture: SeoMetadata morph, seo component, sitemap, robots.txt, redirects, analytics_ |
| [`NEARBY-PLACES.md`](NEARBY-PLACES.md) | Dua jalur tempat sekitar: JSON manual + pipeline Geoapify (`places`/`property_places`) | _Two coexisting nearby-places paths: manual JSON + Geoapify pipeline (`places`/`property_places`)_ |
| [`DEPLOYMENT.md`](DEPLOYMENT.md) | Deployment manual, web installer, scheduler, cron, env vars, queue | _Manual deployment, web installer, scheduler, cron, env vars, queue_ |
| [`VERSION-CONTROL.md`](VERSION-CONTROL.md) | Git dashboard, rollback, detached HEAD, peringatan skema DB | _Git dashboard, rollback, detached HEAD, DB schema warning_ |
| [`SECURITY.md`](SECURITY.md) | Praktik keamanan: FormRequest, middleware, Git dashboard, XSS, SSRF | _Security practices: FormRequest, middleware, Git dashboard, XSS, SSRF_ |
| [`TESTING.md`](TESTING.md) | PHPUnit, SQLite in-memory, cara menjalankan, suite yang ada | _PHPUnit, SQLite in-memory, how to run, existing test suites_ |
| [`VERSIONING.md`](VERSIONING.md) | SemVer, sumber versi, kebijakan MAJOR/MINOR/PATCH, changelog | _SemVer, version source, MAJOR/MINOR/PATCH policy, changelog_ |

## Dokumen yang Sudah Ada (rujuk, jangan duplikasi) / _Existing Docs (reference, do not duplicate)_

| Dokumen | Deskripsi |
|---------|-----------|
| [`geoapify-setup.md`](geoapify-setup.md) | Setup Geoapify API key dan konfigurasi |
| [`GEOAPIFY-Nearby-Places-Integration.md`](GEOAPIFY-Nearby-Places-Integration.md) | Spesifikasi desain integrasi Geoapify (sudah diimplementasi — kode adalah otoritatif) |
| [`security-audit-2026-08-27.md`](security-audit-2026-08-27.md) | Laporan audit keamanan |
| [`security-audit-report.md`](security-audit-report.md) | Laporan audit keamanan (dokumen lama) |
| [`PROJECT-OVERVIEW-ARTIVO-CMS_28082026-1815.md`](PROJECT-OVERVIEW-ARTIVO-CMS_28082026-1815.md) | Ikhtisar proyek |
| [`bug-audit-report.md`](bug-audit-report.md), [`qa-ux-report.md`](qa-ux-report.md) | Laporan audit bug & QA/UX |
| [`INSTALLER.md`](INSTALLER.md), [`FAQ.md`](FAQ.md), [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md) | Panduan installer, FAQ, troubleshooting |
| [`USER_GUIDE.md`](USER_GUIDE.md), [`DEVELOPER_GUIDE.md`](DEVELOPER_GUIDE.md), [`THEMING.md`](THEMING.md), [`ROADMAP.md`](ROADMAP.md), [`ANALYTICS.md`](ANALYTICS.md) | Panduan pengguna, developer, theming, roadmap, analytics |
| [`_agent-audit/AUDIT-FINDINGS.md`](_agent-audit/AUDIT-FINDINGS.md) | Temuan audit (read-only source of truth) |
| [`decisions/`](decisions/) | Architecture Decision Records (ADR) |
| [`architecture/`](architecture/) | Dokumen arsitektur (verifikasi dengan kode) |
| [`domain/`](domain/) | Dokumen domain (pricing, booking, property) |

> Catatan: dokumen utama `ARCHITECTURE.md`, `DATABASE.md`, `BOOKING.md`, `SEO.md`, `SECURITY.md`, `TESTING.md`, `DEPLOYMENT.md` sebelumnya berisi spesifikasi generik lama (Laravel 12, "Units", `model_has_permissions`, "tanpa reservation engine"). Semua telah **ditulis ulang** agar sesuai kode saat ini. Dokumen `ADMIN_GUIDE.md` lama (menjelaskan layar "Units") **tidak** dipakai lagi — gunakan [`ADMIN.md`](ADMIN.md).

---

**Versi:** `1.0.0` — baca dari [`config/artivo.php`](../config/artivo.php:21) via `config('artivo.version')`.

_Version: `1.0.0` — read from [`config/artivo.php`](../config/artivo.php:21) via `config('artivo.version')`._
