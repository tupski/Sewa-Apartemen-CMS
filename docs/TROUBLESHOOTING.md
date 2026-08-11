# Troubleshooting — Sewa Apartemen CMS

## 500 Internal Server Error

1. Check the Laravel log: [`storage/logs/laravel.log`](storage/logs)
2. Enable debug mode temporarily: set `APP_DEBUG=true` in `.env`
3. Common causes:
   - Missing `.env` file — copy from `.env.example`
   - Missing `APP_KEY` — run `php artisan key:generate`
   - Database connection failed — verify `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - Missing Composer dependencies — run `composer install`
   - File permissions on `storage/` and `bootstrap/cache/`
4. After fixing, set `APP_DEBUG=false` and `APP_ENV=production`

## White Page / Blank Screen

1. Set `APP_DEBUG=true` in `.env` to see the actual error
2. Clear all caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```
3. Check PHP version: requires **PHP 8.3+** (`php -v`)
4. Verify all required PHP extensions are installed (see [DEPLOYMENT.md](DEPLOYMENT.md))

## Installer Not Working

1. Delete the `.installed` file in the project root if it exists
2. Ensure `INSTALLED=false` in `.env`
3. Check `storage/` and `bootstrap/cache/` are writable by the web server
4. Verify database credentials during step 2 of the installer
5. If the installer keeps redirecting to the homepage, check that [`routes/install.php`](routes/install.php) is being loaded

## Sitemap Not Updating

1. Clear the cache:
   ```bash
   php artisan cache:clear
   ```
2. The sitemap is cached for 24 hours. New content appears after cache expiry or manual clear.
3. Verify the content is published (status = `published` or `available`). Draft/unpublished items are excluded.

## Images Not Showing

1. Run the storage link command:
   ```bash
   php artisan storage:link
   ```
2. This creates a symlink from `public/storage` to `storage/app/public`
3. On shared hosting where symlinks are disabled, copy files manually or configure the filesystem disk
4. Verify the image path in the database points to the correct file

## "Route Not Found" Errors

1. Clear route cache: `php artisan route:clear`
2. In production, rebuild route cache: `php artisan route:cache`
3. Check that the route is defined in [`routes/web.php`](routes/web.php) or [`routes/auth.php`](routes/auth.php)

## "Specified key was too long" During Migration

1. Ensure MySQL version is 5.7.7+ or MariaDB 10.2.2+
2. In `config/database.php`, set `charset` to `utf8mb4` and `collation` to `utf8mb4_unicode_ci`
3. If using older MySQL, change to `utf8` and `utf8_unicode_ci`

## Database Connection Refused

1. Verify MySQL/MariaDB is running
2. Check `.env` credentials: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
3. Ensure the database user has full privileges on the database
4. On local dev, try `DB_HOST=127.0.0.1` instead of `localhost`

## CSRF Token Mismatch

1. Ensure `@csrf` is present in all forms
2. Clear browser cookies
3. Verify `SESSION_DOMAIN` in `.env` matches the actual domain
4. Check `config/session.php` — `same_site` should be `lax` for most setups

## Vite Assets Not Loading (dev)

1. Run `npm run dev` to start the Vite dev server
2. For production, run `npm run build` first
3. Check that `@vite(['resources/css/app.css', 'resources/js/app.js'])` is in the layout
