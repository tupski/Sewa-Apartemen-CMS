# cPanel Deployment Guide

## Overview

This guide covers deploying the Apartment Rental CMS to cPanel shared hosting. The deployment is designed to work on standard shared hosting without requiring special server configurations.

## Hosting Requirements

### Minimum Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP Version | 8.3 | 8.3+ |
| MySQL Version | 5.7 | 8.0+ |
| Disk Space | 500MB | 2GB+ |
| RAM | 512MB | 1GB+ |
| PHP Memory Limit | 128MB | 256MB+ |
| PHP Max Execution Time | 30s | 120s+ |

### Required PHP Extensions

| Extension | Required | Purpose |
|-----------|----------|---------|
| PHP | 8.3+ | Core |
| PDO | Yes | Database connection |
| PDO MySQL | Yes | MySQL driver |
| Mbstring | Yes | String handling |
| OpenSSL | Yes | Encryption |
| Tokenizer | Yes | Token parsing |
| XML | Yes | XML parsing |
| Ctype | Yes | Character type checking |
| JSON | Yes | JSON handling |
| Fileinfo | Yes | File type detection |
| GD | Yes | Image processing |

### Recommended PHP Settings

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 120
max_input_time = 120
max_input_vars = 1000
session.gc_maxlifetime = 1440
```

## Deployment Options

### Option 1: Document Root in `/public`

**Recommended for new installations**

```
/home/username/
├── public_html/              # Apache document root
│   ├── index.php            # Symlink to /app/public/index.php
│   ├── .htaccess            # Symlink to /app/public/.htaccess
│   ├── favicon.ico
│   ├── robots.txt (symlink)
│   └── storage -> ../apartment-cms/storage/app/public
│
├── apartment-cms/           # Application code
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/              # Symlinked to public_html
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   │   ├── app/
│   │   │   └── public/     # Uploaded media
│   │   ├── framework/
│   │   └── logs/
│   ├── .env
│   ├── artisan
│   └── ...
```

### Option 2: Document Root at Project Root

**Required when you can't change document root**

```
/home/username/
├── public_html/             # Apache document root (project root)
│   ├── .env                 # Move to parent directory
│   ├── .htaccess            # Modified for security
│   ├── index.php            # Modified entry point
│   ├── favicon.ico
│   ├── robots.txt (symlink)
│   └── storage -> ../apartment-cms/storage/app/public
│
├── apartment-cms/           # Application code (outside public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── .env
│   └── ...
```

## Step-by-Step Deployment

### Step 1: Prepare Local Environment

```bash
# Build assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate production key
php artisan key:generate --show
```

### Step 2: Upload Files

#### Method A: Using FTP/SFTP

1. Connect to your cPanel server via FTP/SFTP
2. Navigate to your domain's root directory
3. Upload all files (except `.env`)
4. Upload `.env.example` as `.env`

#### Method B: Using Git

```bash
# On local machine
git add .
git commit -m "Ready for deployment"
git push origin main

# On server
cd /home/username
git clone https://github.com/your-repo/apartment-cms.git apartment-cms
cd apartment-cms
```

#### Method C: Using File Manager

1. Login to cPanel
2. Open File Manager
3. Navigate to domain root
4. Upload ZIP file of project
5. Extract ZIP file

### Step 3: Configure Database

```sql
-- Create database
CREATE DATABASE `apartment_cms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'username_apartment'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant permissions
GRANT ALL PRIVILEGES ON `apartment_cms`.* TO 'username_apartment'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4: Configure `.env` File

```env
# .env file (update with your values)
APP_NAME="My Apartment"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=apartment_cms
DB_USERNAME=username_apartment
DB_PASSWORD=strong_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Analytics (optional)
GOOGLE_ANALYTICS_4_MEASUREMENT_ID=G-XXXXXXXXXX
META_PIXEL_ID=XXXXXXXXXXXXXXX
```

### Step 5: Set Permissions

```bash
# Set proper permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Create symbolic link (if using Option 1)
cd public_html
ln -s ../apartment-cms/storage/app/public storage

# Or use artisan command
php artisan storage:link
```

### Step 6: Run Installer

1. Visit `https://yourdomain.com/install`
2. Follow the installer steps:
   - Step 1: Requirements check
   - Step 2: Application configuration
   - Step 3: Database configuration
   - Step 4: Create admin account
   - Step 5: Website configuration
   - Step 6: Finish

### Step 7: Configure `.htaccess`

```apache
# .htaccess in public_html

RewriteEngine On
RewriteBase /

# Redirect HTTP to HTTPS (production)
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Redirect to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<Files .env>
    Order allow,deny
    Deny from all
</Files>

<Files composer.json>
    Order allow,deny
    Deny from all
</Files>

<Files artisan>
    Order allow,deny
    Deny from all
</Files>

# PHP settings
<IfModule mod_php7.c>
    php_value memory_limit 256M
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 120
</IfModule>
```

## Post-Deployment Tasks

### 1. Verify Installation

```bash
# Test application
php artisan config:show

# Check cache
php artisan cache:status

# Verify routes
php artisan route:list
```

### 2. Clear Cache

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Test Admin Login

```
URL: https://yourdomain.com/admin
Email: (admin email from installer)
Password: (admin password from installer)
```

### 4. Test Frontend

```
Homepage: https://yourdomain.com
Properties: https://yourdomain.com/apartments
```

### 5. Verify Assets

```
CSS: https://yourdomain.com/css/app.css
JS: https://yourdomain.com/js/app.js
Images: https://yourdomain.com/storage/media/image.jpg
```

### 6. Verify SEO

```
Sitemap: https://yourdomain.com/sitemap.xml
Robots: https://yourdomain.com/robots.txt
```

## Troubleshooting

### 1. Permission Denied

```bash
# Fix permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# On cPanel, use File Manager to set permissions
```

### 2. White Screen of Death

```bash
# Enable debug temporarily
APP_DEBUG=true

# Check error logs
tail -f storage/logs/laravel.log
```

### 3. Database Connection Failed

```bash
# Verify database credentials in .env
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Test connection
php artisan tinker
>>> DB::connection()->getPDO();
```

### 4. 404 Errors on Routes

```apache
# Ensure mod_rewrite is enabled
# Check .htaccess is being read
```

### 5. File Upload Issues

```ini
# Increase upload limits
upload_max_filesize = 10M
post_max_size = 10M
```

### 6. CSS/JS Not Loading

```bash
# Verify assets built
npm run build

# Verify symlinks work
ls -la public_html/storage
```

## Performance Optimization

### OPcache

```ini
# Enable OPcache
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.validate_timestamps=0
```

### File-based Cache

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### Optimize Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Backup Strategy

### Database Backup

```bash
# Manual backup
mysqldump -u username -p apartment_cms > backup_$(date +%Y%m%d).sql

# Automated backup (cPanel)
# Set up Daily Backup in cPanel
```

### File Backup

```bash
# Backup storage
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public

# Backup .env
cp .env .env.backup.$(date +%Y%m%d)
```

## SSL/HTTPS Setup

### Let's Encrypt (cPanel)

1. Login to cPanel
2. Go to "SSL/TLS Status"
3. Enable autoSSL for your domain

### Force HTTPS

```apache
# In .htaccess
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `.env` file outside public_html (or protected by .htaccess)
- [ ] HTTPS enabled
- [ ] Database credentials secured
- [ ] Admin login working
- [ ] File permissions set correctly
- [ ] Cache working
- [ ] Backup configured
- [ ] Error logging enabled
- [ ] Security headers set

## Monitoring

### Error Logs

```bash
# Monitor error logs
tail -f storage/logs/laravel.log

# Watch for specific errors
grep -i "error" storage/logs/laravel.log
```

### Activity Logs

```bash
# Check recent activity
tail -f storage/logs/laravel.log | grep -i "info"
```

## Conclusion

The Apartment Rental CMS is designed for easy deployment to cPanel shared hosting. Follow the steps in this guide to get your website live quickly.

Key points:
- ✓ No special server requirements
- ✓ Works on standard shared hosting
- ✓ Web-based installer
- ✓ File-based caching
- ✓ No Redis required
- ✓ No Node.js runtime required
- ✓ AutoSSL support