# Deployment & Operasional

_Deployment & Operations_

**Tidak ada CI/CD** — deployment **manual**. Dokumen ini menjelaskan cara deploy ke produksi, web installer, scheduler/cron, realitas queue, dan variabel lingkungan yang dibutuhkan.

_There is **no CI/CD** — deployment is **manual**. This document explains how to deploy to production, the web installer, the scheduler/cron, queue reality, and required environment variables._

---

## Persyaratan Server / _Server Requirements_

- PHP `^8.3`
- MySQL 8+ / MariaDB 10.6+ (produksi); SQLite in-memory hanya untuk test
- Composer 2.x
- Node.js (untuk build Vite) — hanya saat deploy, tidak perlu di runtime

## Langkah Deploy Manual / _Manual Deploy Steps_

```bash
# 1. Ambil kode terbaru
git pull

# 2. Install dependency PHP (produksi: tanpa dev)
composer install --no-dev --optimize-autoloader

# 3. Siapkan .env (jika belum)
cp .env.example .env
php artisan key:generate   # sekali saja

# 4. Migrasi — HANYA aditif (jangan migrate:fresh pada produksi!)
php artisan migrate --force

# 5. Build aset frontend
npm install --ignore-scripts
npm run build

# 6. Symlink storage (dibutuhkan disk public untuk Media)
php artisan storage:link

# 7. Cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setelah perubahan selama pengembangan, ingat `php artisan optimize:clear`.

### Deploy tambahan (skrip yang tersedia)

- `composer setup` — install + .env + key + migrate + npm + build (untuk install baru).
- `composer dev` — menjalankan `php artisan serve` + `queue:listen --tries=1 --timeout=0` + `pail` + `vite dev` sekaligus (untuk pengembangan lokal).

## Web Installer / _Web Installer_

- Route installer: [`routes/install.php`](../routes/install.php), dipasang di `/install` di [`bootstrap/app.php`](../bootstrap/app.php:15).
- **Proteksi**: middleware [`ProtectInstaller`](../app/Http/Middleware/ProtectInstaller.php) (`protect.installer`) — hanya localhost / IP whitelist / token.
- Langkah: Requirements → Application → Database → Admin → Website → Finish (`InstallerController::step1..step6`).
- Ada endpoint `POST /install/fresh` untuk reset database.
- View: [`resources/views/install/`](../resources/views/install).
- Lihat juga [`docs/INSTALLER.md`](INSTALLER.md).

## Scheduler & Cron / _Scheduler & Cron_

Jadwal didefinisikan di [`routes/console.php`](../routes/console.php):

| Command | Jadwal | Catatan |
|---------|--------|---------|
| `currency:fetch` | Setiap 6 jam | `withoutOverlapping()` + `runInBackground()` |
| `git:check-updates` | Harian **01:00 Asia/Jakarta** | Timezone di-pin eksplisit karena `app.timezone` UTC; `withoutOverlapping()` + `runInBackground()` |

Tambahkan ke cron pengguna (biasanya `crontab -e`):

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Realitas Queue / _Queue Reality_

- Config default: `database` ([`config/queue.php`](../config/queue.php)); **`.env` saat ini `QUEUE_CONNECTION=sync`** (lihat `.env.example` line 38).
- Dengan `sync`, satu-satunya job custom — [`FetchNearbyPlacesJob`](../app/Jobs/FetchNearbyPlacesJob.php) — berjalan **inline** saat request admin resync, tanpa retry. `$tries`/`$backoff`/`$timeout` hanya efektif dengan driver asli + `php artisan queue:work`.
- Jika butuh job berjalan asinkron, set driver nyata (mis. `database`) dan jalankan worker: `php artisan queue:work`.
- `composer dev` menjalankan `queue:listen --tries=1 --timeout=0` untuk pengembangan.

## Variabel Lingkungan Wajib / _Required Environment Variables_

Dari [`.env.example`](../.env.example):

| Variabel | Keterangan |
|----------|-----------|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Konfigurasi dasar app |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | Bahasa default |
| `DB_*` | Koneksi database (produksi: MySQL) |
| `SESSION_DRIVER` | `database` (default) |
| `QUEUE_CONNECTION` | `database` config default, `.env` = `sync` |
| `CACHE_STORE` | `database` config default, `.env` = `file` |
| `MAIL_*` | Mail (juga bisa di-set dari Settings → Mail) |
| `GEOAPIFY_API_KEY` | **Wajib** untuk POI syncing — kosong di `.env` = job early-return, peta fallback ke OSM tiles |
| `GEOAPIFY_MAP_KEY` | Kunci browser untuk tile peta (optional; fallback `GEOAPIFY_API_KEY`) |
| `GEOAPIFY_RADIUS` | Radius POI, default 2000 |
| `GEOAPIFY_MAX_RESULTS` | Maksimal hasil, default 20 |

> ⚠️ `GEOAPIFY_API_KEY` saat ini **kosong** di `.env` — fitur Nearby Places tidak akan berjalan sampai diisi. Lihat [`docs/geoapify-setup.md`](geoapify-setup.md).

## Backup & Restore / _Backup & Restore_

- Admin: **System → Backup & Restore** → [`Admin\BackupController`](../app/Http/Controllers/Admin/BackupController.php).
- Service: [`BackupService`](../app/Services/BackupService.php).
- Alur: buat backup → download → restore (dengan konfirmasi).
- **Backup database WAJIB dilakukan sebelum operasi rollback git** — lihat [`docs/VERSION-CONTROL.md`](VERSION-CONTROL.md).

## Production Caching / _Production Caching_

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jangan lupa `php artisan optimize:clear` setelah deploy perubahan saat pengembangan.

## Catatan Penting / _Important Notes_

- **Jangan jalankan perintah destruktif** tanpa persetujuan eksplisit: `migrate:fresh`, `db:wipe`, `rm -rf`, force-push. Prefer migrasi aditif.
- `APP_DEBUG=true` ter-set di produksi (temuan audit) — sebaiknya **`false`** di produksi. Lihat [`docs/SECURITY.md`](SECURITY.md).
- Tidak ada `.github/` — tidak ada pipeline CI/CD.

## Lihat Juga / _See Also_

- [`docs/INSTALLER.md`](INSTALLER.md) — detail installer
- [`docs/VERSION-CONTROL.md`](VERSION-CONTROL.md) — update & rollback git dari admin
- [`docs/geoapify-setup.md`](geoapify-setup.md) — setup Geoapify
- [`docs/DEPLOY-CPANEL-ID.md`](DEPLOY-CPANEL-ID.md), [`docs/DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md) — panduan deploy cPanel (dokumen lama)
- [`docs/TROUBLESHOOTING.md`](TROUBLESHOOTING.md) — pemecahan masalah
