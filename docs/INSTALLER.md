# Web Installer

## Overview

The web installer provides a user-friendly installation process that guides administrators through setting up the Apartment Rental CMS. It handles requirements checking, database configuration, admin creation, and initial website setup - all through a web interface.

## Installation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    Web Browser                                  │
│                    (visit /install)                             │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ↓
         ┌──────────────────────────────┐
         │   Check if installed?        │
         │   (check .installed file)    │
         └────────────┬─────────────────┘
                      │
         ┌────────────┴────────────┐
         │                         │
    NOT INSTALLED             ALREADY INSTALLED
         │                         │
         ↓                         ↓
┌──────────────────┐      ┌──────────────────────┐
│   Step 1:        │      │   Show error:        │
│   Requirements   │      │   "Already installed"│
│   Check          │      │   (403 Forbidden)    │
└────────┬─────────┘      └──────────────────────┘
         │
         ↓
┌──────────────────┐
│   Step 2:        │
│   Application    │
│   Configuration  │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Step 3:        │
│   Database       │
│   Configuration  │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Step 4:        │
│   Create Admin   │
│   Account        │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Step 5:        │
│   Website        │
│   Configuration  │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Step 6:        │
│   Finish         │
│   Installation   │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Create         │
│   .installed     │
│   lock file      │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Redirect to    │
│   /admin         │
└──────────────────┘
```

## Step 1: Requirements Check

### PHP Requirements

| Extension | Required | Description |
|-----------|----------|-------------|
| PHP | 8.3+ | PHP version |
| PDO | Yes | Database connection |
| MySQL | Yes | MySQL driver |
| Mbstring | Yes | String handling |
| OpenSSL | Yes | Encryption |
| Tokenizer | Yes | Token parsing |
| XML | Yes | XML parsing |
| Ctype | Yes | Character type checking |
| JSON | Yes | JSON handling |
| Fileinfo | Yes | File type detection |
| GD | Yes | Image processing |

### Permissions Requirements

| Directory | Required | Description |
|-----------|----------|-------------|
| `storage/` | writable | Application storage |
| `storage/app/public/` | writable | Uploaded media |
| `storage/framework/` | writable | Cache and sessions |
| `storage/logs/` | writable | Log files |
| `bootstrap/cache/` | writable | Cached configurations |

### Requirements Check UI

```html
<div class="requirements-check">
  <h3>PHP Version</h3>
  <div class="status-item">
    <span>PHP Version</span>
    <span class="status success">8.3.10 ✓</span>
  </div>
  
  <h3>Extensions</h3>
  <div class="status-item">
    <span>PDO</span>
    <span class="status success">Enabled ✓</span>
  </div>
  <div class="status-item">
    <span>MySQL</span>
    <span class="status success">Enabled ✓</span>
  </div>
  <div class="status-item">
    <span>Mbstring</span>
    <span class="status success">Enabled ✓</span>
  </div>
  
  <h3>Permissions</h3>
  <div class="status-item">
    <span>storage/</span>
    <span class="status success">Writable ✓</span>
  </div>
  <div class="status-item">
    <span>bootstrap/cache/</span>
    <span class="status success">Writable ✓</span>
  </div>
</div>
```

## Step 2: Application Configuration

### Configuration Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| Application Name | text | Laravel | Website name |
| Application URL | text | auto-detect | Full website URL |
| Timezone | select | Asia/Jakarta | Timezone |
| Locale | select | id | Language code |
| Currency | select | IDR | Currency code |

### Validation Rules

```php
$request->validate([
    'app_name' => 'required|string|max:100',
    'app_url' => 'required|url',
    'timezone' => 'required|timezone',
    'locale' => 'required|in:en,id,ja,ko,zh',
    'currency' => 'required|currency_code',
]);
```

### Configuration Storage

```php
// Store in settings table
Settings::updateOrCreate(['key' => 'site_name'], ['value' => $request->app_name]);
Settings::updateOrCreate(['key' => 'site_url'], ['value' => $request->app_url]);
Settings::updateOrCreate(['key' => 'timezone'], ['value' => $request->timezone]);
Settings::updateOrCreate(['key' => 'locale'], ['value' => $request->locale]);
Settings::updateOrCreate(['key' => 'currency'], ['value' => $request->currency]);

// Store in config/cache
config(['app.name' => $request->app_name]);
config(['app.url' => $request->app_url]);
config(['app.timezone' => $request->timezone]);
config(['app.locale' => $request->locale]);
config(['app.currency' => $request->currency]);
```

## Step 3: Database Configuration

### Configuration Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| Database Host | text | localhost | MySQL hostname |
| Database Port | text | 3306 | MySQL port |
| Database Name | text | - | Database name |
| Username | text | - | Database username |
| Password | password | - | Database password |

### Connection Test

```php
try {
    DB::connection()->getPDO();
    DB::connection()->ping();
    
    return response()->json([
        'success' => true,
        'message' => 'Database connection successful'
    ]);
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
}
```

### Migrations Execution

```php
// Run migrations
Artisan::call('migrate', [
    '--force' => true,
    '--path' => 'database/migrations'
]);

// Run seeders
Artisan::call('db:seed', [
    '--class' => 'AmenitiesTableSeeder'
]);

// Clear cache
Artisan::call('config:clear');
Artisan::call('cache:clear');
```

### Database Setup

```sql
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `apartment_cms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions
GRANT ALL PRIVILEGES ON `apartment_cms`.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

## Step 4: Create Admin Account

### Form Fields

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| Name | text | required, min:3 | Admin name |
| Email | email | required, email, unique:users | Admin email |
| Password | password | required, min:8, confirmed | Admin password |
| Confirm Password | password | required | Password confirmation |

### Validation Rules

```php
$request->validate([
    'name' => 'required|string|min:3|max:100',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:8|confirmed',
]);
```

### Password Hashing

```php
// Use Laravel's password hashing
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
]);

// Assign Super Admin role
$superAdminRole = Role::where('slug', 'super-admin')->first();
$user->assignRole($superAdminRole);
```

### Login After Creation

```php
// Log in the user
Auth::login($user);

// Store installation flag
file_put_contents(storage_path('installed.lock'), 'installed');

// Clear installer middleware
```

## Step 5: Website Configuration

### Configuration Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| Website Name | text | - | Website name |
| Tagline | text | - | Short tagline |
| Email | email | - | Contact email |
| Phone | tel | - | Contact phone |
| WhatsApp | tel | - | WhatsApp number |
| Address | textarea | - | Physical address |
| Logo | file | - | Logo image |
| Favicon | file | - | Favicon image |
| Primary Color | color | #3B82F6 | Primary color |
| Secondary Color | color | #10B981 | Secondary color |
| Accent Color | color | #F59E0B | Accent color |

### Image Upload

```php
// Validate image upload
$request->validate([
    'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    'favicon' => 'nullable|image|mimes:ico,png,webp|max:256',
]);

// Upload logo
if ($request->hasFile('logo')) {
    $logo = $request->file('logo');
    $filename = 'logo-' . time() . '.' . $logo->getClientOriginalExtension();
    $logo->storeAs('logos', $filename, 'public');
    
    Settings::updateOrCreate(['key' => 'logo'], ['value' => $filename]);
}

// Upload favicon
if ($request->hasFile('favicon')) {
    $favicon = $request->file('favicon');
    $filename = 'favicon-' . time() . '.ico';
    $favicon->storeAs('favicons', $filename, 'public');
    
    Settings::updateOrCreate(['key' => 'favicon'], ['value' => $filename]);
}

// Store colors
Settings::updateOrCreate(['key' => 'primary_color'], ['value' => $request->primary_color]);
Settings::updateOrCreate(['key' => 'secondary_color'], ['value' => $request->secondary_color]);
Settings::updateOrCreate(['key' => 'accent_color'], ['value' => $request->accent_color]);
```

### Theme Configuration

```php
// Set default theme
Settings::updateOrCreate(['key' => 'active_theme'], ['value' => 'modern']);

// Create theme settings
Settings::updateOrCreate(['key' => 'theme_primary_color'], ['value' => $request->primary_color]);
Settings::updateOrCreate(['key' => 'theme_secondary_color'], ['value' => $request->secondary_color]);
Settings::updateOrCreate(['key' => 'theme_accent_color'], ['value' => $request->accent_color]);
```

## Step 6: Finish Installation

### Installation Summary

```html
<div class="installation-complete">
  <h2>Installation Complete!</h2>
  <p>Your Apartment Rental CMS has been successfully installed.</p>
  
  <div class="summary">
    <h3>Configuration</h3>
    <p><strong>Website:</strong> {{ config('app.name') }}</p>
    <p><strong>URL:</strong> {{ config('app.url') }}</p>
    <p><strong>Timezone:</strong> {{ config('app.timezone') }}</p>
    
    <h3>Admin Account</h3>
    <p><strong>Email:</strong> {{ $adminEmail }}</p>
    <p><strong>Password:</strong> [shown during installation]</p>
  </div>
  
  <div class="actions">
    <a href="{{ url('/') }}" class="btn btn-primary">Open Website</a>
    <a href="{{ url('/admin') }}" class="btn btn-secondary">Open Admin</a>
  </div>
</div>
```

### Lock Installer

```php
// Create lock file
file_put_contents(storage_path('installed.lock'), json_encode([
    'installed_at' => now()->toIso8601String(),
    'version' => '1.0.0',
    'locked' => true,
]));

// Disable installer routes
// Installer middleware checks this file
```

### Clear Cache

```php
// Clear all caches
Artisan::call('config:clear');
Artisan::call('cache:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');
```

## Installer Middleware

### CheckInstalled Middleware

```php
class CheckInstalled
{
    public function handle($request, Closure $next)
    {
        if (!file_exists(storage_path('installed.lock'))) {
            return redirect('/install');
        }
        
        return $next($request);
    }
}
```

### PreventAccessWhenInstalled Middleware

```php
class PreventAccessWhenInstalled
{
    public function handle($request, Closure $next)
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(403, 'Installation already completed.');
        }
        
        return $next($request);
    }
}
```

## Installation Configuration File

### `.installed.lock`

```json
{
  "installed_at": "2026-08-11T10:30:00+07:00",
  "version": "1.0.0",
  "locked": true,
  "completed_steps": [1, 2, 3, 4, 5, 6],
  "admin_email": "admin@example.com",
  "database_name": "apartment_cms"
}
```

## Post-Installation Tasks

### 1. Verify Installation

```bash
# Check if installed
php artisan config:show

# Verify database connection
php artisan db:show
```

### 2. Setup Storage Symlink

```bash
# Create symbolic link for public storage
php artisan storage:link
```

### 3. Verify Admin Login

```bash
# Test admin login
php artisan tinker

>>> Auth::login(User::where('email', 'admin@example.com')->first());
>>> exit
```

### 4. Run Tests

```bash
# Run installation tests
php artisan test --filter=InstallerTest
```

## Security Considerations

### 1. Remove Installer After Installation

The installer should be accessible only during initial installation. The lock file prevents re-installation.

### 2. Secure .env File

```bash
# .env should be outside public_html on shared hosting
# Or use .htaccess to deny access
<Files .env>
    order allow,deny
    deny from all
</Files>
```

### 3. Reset Password if Forgotten

```php
// Add password reset endpoint if admin forgotten
Route::post('/install/reset-password', [InstallerController::class, 'resetPassword']);
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed

**Error:** `SQLSTATE[HY000] [1045] Access denied`

**Solution:**
- Verify database credentials
- Check if database exists
- Verify user permissions

#### 2. Permission Denied

**Error:** `The file is not writable`

**Solution:**
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache

# Windows
Set-ACL -Path storage -AclObject (Get-ACL storage)
```

#### 3. Migration Failed

**Error:** `SQLSTATE[42000] Syntax error`

**Solution:**
- Check MySQL version (must be 5.7+ or MariaDB 10.2+)
- Check character set settings
- Run migrations manually with verbose output

#### 4. Already Installed

**Error:** `Installation already completed`

**Solution:**
- Delete `storage/installed.lock`
- Run `php artisan config:clear`
- Refresh the page

### Debug Mode

```env
# Temporarily enable debug for troubleshooting
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

**WARNING:** Disable debug mode after installation!

```env
APP_DEBUG=false
APP_LOG_LEVEL=error
```

## Automated Installation (Optional)

### Command Line Installation

```bash
# Create installation script
php artisan install:run \
  --name="My Apartment" \
  --email="admin@example.com" \
  --password="secure-password" \
  --db-host="localhost" \
  --db-name="apartment_cms" \
  --db-username="username" \
  --db-password="password"
```

### Deployment Script Example

```bash
#!/bin/bash

# Deployment script for cPanel
cd /home/username/apartment-cms

# Run migrations
php artisan migrate --force

# Create admin if not exists
php artisan tinker --execute="
\$admin = User::where('email', 'admin@example.com')->first();
if (!\$admin) {
    \$admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('secure-password')
    ]);
    \$role = Role::where('slug', 'super-admin')->first();
    \$admin->assignRole(\$role);
}
"

# Create lock file
touch storage/installed.lock

# Set permissions
chmod -R 755 storage bootstrap/cache
```

## Conclusion

The web installer provides a complete, secure installation process for the Apartment Rental CMS. It:

- ✓ Checks system requirements
- ✓ Configures application settings
- ✓ Sets up database connection
- ✓ Creates admin account
- ✓ Configures initial website settings
- ✓ Locks installer after completion
- ✓ Clears all caches
- ✓ Prepares for production use

Next: Continue to [SEO.md](SEO.md) for SEO architecture documentation.