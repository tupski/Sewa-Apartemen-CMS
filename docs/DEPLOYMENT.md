# Deployment Guide — Sewa Apartemen CMS

## Server Requirements

| Component | Minimum |
|-----------|---------|
| PHP | 8.3+ |
| MySQL | 8.0+ / MariaDB 10.6+ |
| Web Server | Nginx 1.20+ or Apache 2.4+ |
| Composer | 2.x |
| Node.js | 18+ (for Vite build) |
| PHP Extensions | BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, MySQL, Tokenizer, XML, GD/Imagick |

## Deployment Steps

### 1. Clone Repository

```bash
git clone <repo-url> /path/to/project
cd /path/to/project
```

### 2. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Install & Build Frontend Assets

```bash
npm install
npm run build
```

### 4. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:
- `APP_URL=https://yourdomain.com`
- `APP_ENV=production`
- `APP_DEBUG=false`
- Database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

### 5. Database Setup

```bash
php artisan migrate --seed
```

This creates all tables and seeds initial data (roles, default settings).

### 6. Storage Link

```bash
php artisan storage:link
```

Creates `public/storage` → `storage/app/public` symlink for uploaded files.

### 7. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs storage/framework
```

Web server user (e.g., `www-data`, `nginx`) needs write access to `storage/`.

### 8. Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache (.htaccess)

Laravel includes a default `.htaccess` in `public/`. Ensure `mod_rewrite` is enabled.

### 9. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 10. Run the Web Installer

Navigate to `https://yourdomain.com/install` and follow the 5-step wizard.

---

## Cron Job for Scheduled Tasks

Add to crontab:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Backup Strategy

### Database Backups

```bash
# Manual backup
mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql

# Using Laravel (if spatie/laravel-backup is installed)
php artisan backup:run
```

### File Backups

Back up these directories:
- `storage/app/public/` — uploaded media
- `.env` — environment configuration
- `storage/logs/` — optional, for debugging

### Backup Schedule (Recommended)

- **Database**: Daily automated backup, retain last 30 days
- **Files**: Weekly backup, retain last 4 weeks
- **Off-site**: Sync backups to cloud storage (S3, Dropbox, etc.)

## Post-Deployment Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] SSL certificate installed (HTTPS enforced)
- [ ] `APP_URL` matches actual domain with `https://`
- [ ] Storage symlink working (uploaded images display correctly)
- [ ] Database migrations run successfully
- [ ] Cron job configured
- [ ] Backups configured and tested
- [ ] Web installer completed (or `.installed` file exists)
- [ ] Admin account created and logged in successfully
- [ ] All tests pass: `php artisan test`

## Rollback

If deployment fails:

```bash
# Rollback migrations
php artisan migrate:rollback

# Restore from backup
mysql -u user -p database_name < backup.sql

# Revert code
git checkout <previous-commit-hash>
```
