# Security Architecture

## Overview

Security is a top priority for the Apartment Rental CMS. This document outlines the security measures implemented throughout the application, from authentication to data protection.

## Security Principles

### 1. Defense in Depth

Multiple layers of security:
- Input validation
- Output escaping
- Database protection
- File upload restrictions
- Rate limiting
- Secure session management

### 2. Least Privilege

- Users have minimum necessary permissions
- Admins have role-based access control
- File storage isolated from public access

### 3. Secure by Default

- All forms use CSRF protection
- Passwords are hashed
- SQL queries use parameter binding
- XSS is prevented by default

## Authentication & Authorization

### User Authentication

```php
// Login using Laravel's built-in authentication
Auth::attempt([
    'email' => $request->email,
    'password' => $request->password,
], $request->remember);

// Password verification
Hash::check($plainPassword, $hashedPassword);
```

### Password Requirements

- Minimum 8 characters
- Stored using bcrypt (Laravel default)
- Password hashing cost: 12

```php
// Password hashing
'password' => Hash::make($request->password, [
    'rounds' => 12,
]);
```

### Login Throttling

```php
// Throttle login attempts
'max_attempts' => 5,
'milliseconds' => 300000, // 5 minutes

// Implement in LoginRequest
public function rules()
{
    return [
        'email' => 'required|email',
        'password' => 'required|string',
    ];
}

public function messages()
{
    return [
        'email.exists' => 'This account has been locked due to too many failed attempts.',
    ];
}
```

### Session Security

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### Logout on Browser Close

```php
// Session expires when browser closes
SESSION_LIFETIME=120
SESSION_DRIVER=file
```

### Multi-factor Authentication (Future)

```php
// Plan for future MFA implementation
class User extends Authenticatable
{
    public function isMfaEnabled()
    {
        return !empty($this->mfa_secret);
    }
    
    public function verifyMfaCode($code)
    {
        $manager = new \Google2FA();
        return $manager->verifyKey($this->mfa_secret, $code);
    }
}
```

### Role-Based Access Control

#### Roles

| Role | Permissions |
|------|-------------|
| Super Admin | All permissions |
| Editor | Manage content, edit bookings |
| Booking Staff | View/edit bookings only |
| SEO Manager | Manage SEO, integrations |

#### Permissions

```php
// Permission structure
[
    'properties' => ['view', 'create', 'update', 'delete'],
    'units' => ['view', 'create', 'update', 'delete'],
    'bookings' => ['view', 'update'],
    'seo' => ['edit'],
    'settings' => ['edit'],
    'media' => ['upload', 'delete'],
    'users' => ['manage'],
    'roles' => ['manage'],
]
```

#### Policy Implementation

```php
class PropertyPolicy
{
    public function view(User $user, Property $property)
    {
        return $user->hasPermission('properties.view');
    }
    
    public function create(User $user)
    {
        return $user->hasPermission('properties.create');
    }
    
    public function update(User $user, Property $property)
    {
        return $user->hasPermission('properties.update');
    }
    
    public function delete(User $user, Property $property)
    {
        return $user->hasPermission('properties.delete') && 
               !$property->bookings()->where('status', '!=', 'cancelled')->exists();
    }
}
```

## Input Validation & Sanitization

### Request Validation

```php
class StorePropertyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:properties,slug',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'whatsapp_number' => 'nullable|string|regex:/^[0-9+]{8,20}$/',
            'phone' => 'nullable|string|regex:/^[0-9+]{8,20}$/',
            'email' => 'nullable|email',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
            'featured' => 'nullable|boolean',
            'status' => 'required|in:draft,published,archived',
        ];
    }
}
```

### XSS Prevention

```php
// Blade automatically escapes output
{{ $variable }}  // Escaped
{!! $variable !!} // Not escaped (use sparingly)

// Use Laravel's e() helper
e($variable)

// Use HTML Purifier for user input
$cleanHtml = clean($userInput);
```

### SQL Injection Prevention

```php
// Use Eloquent ORM (parameterized queries)
Property::where('id', $id)->first();
Property::where('slug', $slug)->first();

// Or use query builder
DB::table('properties')->where('id', $id)->first();

// NEVER use string concatenation
DB::table("properties WHERE id = $id") // BAD!
```

### Mass Assignment Protection

```php
// In model
class Property extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
    ];
    
    // OR use $guarded
    protected $guarded = ['id', 'created_at', 'updated_at'];
}

// In controller
Property::create($request->validated()); // Only fillable fields
Property::create($request->all()); // DANGEROUS!
```

## File Upload Security

### Upload Validation

```php
public function rules()
{
    return [
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'favicon' => 'nullable|image|mimes:ico,png,webp|max:256',
        'media' => 'nullable|array',
        'media.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,pdf|max:10240',
    ];
}
```

### MIME Type Validation

```php
// Validate MIME type (don't trust extension)
$mimeTypes = [
    'image/jpeg' => 'jpg,jpeg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'application/pdf' => 'pdf',
];

$mimeType = $file->getMimeType();
$extension = $file->getClientOriginalExtension();

if (!in_array($mimeType, array_keys($mimeTypes))) {
    throw new ValidationException('Invalid file type');
}
```

### Safe Filename Generation

```php
// Generate safe filename
public function generateSafeFilename($originalFilename)
{
    $extension = $file->getClientOriginalExtension();
    $safeName = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME));
    
    // Add timestamp and random string
    $safeName = $safeName . '-' . time() . '-' . Str::random(10);
    
    return $safeName . '.' . $extension;
}

// Example output
// apartment-photo-1691740200-abc123def4.jpg
```

### File Upload Storage

```php
// Store outside public directory
// Or use symbolic link to storage/app/public

// .env
FILESYSTEM_DISK=local

// Or use public disk
FILESYSTEM_DISK=public

// Configuration
'storage' => [
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],
    ],
],
```

### SVG Security

```php
// SVG files can contain XSS - validate carefully
public function validateSvg($file)
{
    $content = file_get_contents($file->getRealPath());
    
    // Check for dangerous tags
    $dangerousPatterns = [
        '/<script/i',
        '/on\w+\s*=/i',
        '/data:/i',
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            throw new ValidationException('Invalid SVG file');
        }
    }
    
    return true;
}
```

### File Upload Size Limits

```php
// In .env
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M

// In controller
'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // 2MB
'media' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240', // 10MB
```

## Rate Limiting

### Global Rate Limit

```php
// In RouteServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### Login Rate Limit

```php
'max_attempts' => 5,
'milliseconds' => 300000, // 5 minutes
```

### Booking Form Rate Limit

```php
// Limit booking submissions
'max_attempts' => 10,
'milliseconds' => 60000, // 1 minute
```

### API Rate Limit

```php
// Protect API endpoints
Route::middleware('auth:api', 'throttle:60,1')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/units', [UnitController::class, 'index']);
});
```

## SQL Injection Prevention

### Always Use Parameter Binding

```php
// GOOD
Property::where('id', $id)->first();
Property::where('slug', $slug)->first();

// GOOD
DB::table('properties')->where('id', $id)->first();

// BAD
DB::table("properties WHERE id = $id")->first();
DB::table("properties WHERE slug = '$slug'")->first();
```

### Use Eloquent ORM

```php
// Automatically uses parameter binding
$property = Property::find($id);
$properties = Property::where('status', 'published')->get();

// Use eager loading to prevent N+1
$properties = Property::with('units', 'amenities')->get();
```

### Raw Queries (Use Sparingly)

```php
// If you must use raw queries
DB::select('SELECT * FROM properties WHERE id = ?', [$id]);
DB::select('SELECT * FROM properties WHERE status = ?', [$status]);

// NEVER concatenate
DB::select("SELECT * FROM properties WHERE id = $id");
```

## Cross-Site Request Forgery (CSRF)

### CSRF Protection

```php
// All forms include CSRF token
<form method="POST" action="/admin/properties">
    @csrf
    <!-- form fields -->
</form>

// Or use Blade directive
@method('PUT')
@csrf

// AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

### CSRF Token Validation

```php
// Laravel validates CSRF automatically
// Can disable for specific routes
protected $except = [
    'webhook/*',
];
```

## Cross-Site Scripting (XSS) Prevention

### Blade Auto-escaping

```php
// Automatically escapes output
{{ $userInput }}  // Safe
{!! $userInput !!} // Not escaped (use with caution)

// Use Laravel's e() helper
{{ e($userInput) }}
```

### HTML Purifier (When Needed)

```php
// For rich text fields
$cleanHtml = clean($userInput);

// Or use HTML Purifier package
use HTMLPurifier;
$purifier = new HTMLPurifier();
$cleanHtml = $purifier->purify($userInput);
```

### Content Security Policy (CSP)

```php
// Add CSP header
Route::middleware('csp', function ($request, $next) {
    return $next($request)
        ->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';");
});
```

## Password Security

### Password Requirements

```php
public function rules()
{
    return [
        'password' => 'required|string|min:8|confirmed',
        'password_confirmation' => 'required|string|min:8',
    ];
}

public function messages()
{
    return [
        'password.min' => 'Password must be at least 8 characters',
        'password.confirmed' => 'Passwords do not match',
    ];
}
```

### Password Hashing

```php
// Laravel uses bcrypt by default
'password' => Hash::make($request->password);

// Verify password
if (Hash::check($plainPassword, $hashedPassword)) {
    // Password matches
}
```

### Password Reset

```php
// Use Laravel's built-in password reset
// Email token with expiration
// Token single-use
// Secure token generation
```

## HTTPS Enforcement

### Force HTTPS in Production

```php
// In AppServiceProvider
public function boot()
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

### HSTS Header

```php
// Add HSTS header
Route::middleware('hsts', function ($request, $next) {
    return $next($request)
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
```

### Redirect HTTP to HTTPS

```apache
# In .htaccess
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Database Security

### Database Credentials

```env
# Store in .env (never commit to Git)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=apartment_cms
DB_USERNAME=username
DB_PASSWORD=strong-password-here
```

### Database User Permissions

```sql
-- Create limited database user
CREATE USER 'apartment_user'@'localhost' IDENTIFIED BY 'strong-password';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON apartment_cms.* TO 'apartment_user'@'localhost';

-- Revoke dangerous permissions
REVOKE DROP, ALTER, CREATE, INDEX ON apartment_cms.* FROM 'apartment_user'@'localhost';
```

### SQL Injection Prevention (Again)

```php
// Always use Eloquent or parameter binding
// Never concatenate user input into queries

// GOOD
Property::where('name', 'LIKE', "%$search%")->get();

// BAD
Property::where("name LIKE '%$search%'")->get();
```

## File System Security

### Storage Directory

```
# Storage directory structure
storage/
├── app/
│   ├── public/          # Public uploads (symlinked to public/storage)
│   ├── private/         # Private files
│   └── backups/         # Database backups
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
    └── laravel.log      # Log file

# Permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Log File Security

```php
// Store logs outside public directory
storage/logs/laravel.log

// Rotate logs
'log' => 'daily',  // In config/app.php

// Limit log size
'log_max_files' => 30,
```

### Environment File Security

```env
# Never commit .env to Git
# Add to .gitignore
.env
.env.local
.env.*.local

# Add to .gitignore
echo ".env" >> .gitignore
echo ".env.*" >> .gitignore
```

## Audit Logging

### Audit Log Implementation

```php
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];
}

// Usage
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'create_property',
    'model_type' => Property::class,
    'model_id' => $property->id,
    'old_values' => [],
    'new_values' => $property->getAttributes(),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

### Loggable Events

```php
// Log these actions
Property::saved(function($property) {
    if ($property->wasRecentlyCreated) {
        AuditLog::create([
            'action' => 'create_property',
            'model_type' => Property::class,
            'model_id' => $property->id,
            'new_values' => $property->getAttributes(),
        ]);
    } else {
        AuditLog::create([
            'action' => 'update_property',
            'model_type' => Property::class,
            'model_id' => $property->id,
            'old_values' => $property->getOriginal(),
            'new_values' => $property->getAttributes(),
        ]);
    }
});

Booking::created(function($booking) {
    AuditLog::create([
        'action' => 'create_booking',
        'model_type' => Booking::class,
        'model_id' => $booking->id,
        'new_values' => $booking->getAttributes(),
    ]);
});
```

## Error Handling

### Production Error Handling

```env
# In production
APP_DEBUG=false
APP_LOG_LEVEL=error
```

### Custom Error Pages

```php
// Create custom error pages
resources/views/errors/
├── 403.blade.php
├── 404.blade.php
├── 500.blade.php
└── 503.blade.php
```

### Log Errors

```php
// Laravel logs errors to storage/logs/laravel.log
Log::error('Error message', ['context' => $data]);

// Monitor logs for security issues
tail -f storage/logs/laravel.log
```

## Security Headers

### Add Security Headers

```php
// In AppServiceProvider
public function boot()
{
    // Add security headers
    Response::macro('securityHeaders', function ($response) {
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        return $response;
    });
    
    // Apply to all responses
    Response::macro('applySecurityHeaders', function ($response) {
        return $response->securityHeaders();
    });
}
```

## Security Best Practices

### 1. Keep Dependencies Updated

```bash
# Update Laravel
composer update

# Update npm packages
npm update

# Check for vulnerabilities
composer audit
npm audit
```

### 2. Use HTTPS

```env
# Always use HTTPS in production
APP_URL=https://yourdomain.com
```

### 3. Disable Debug Mode

```env
# In production
APP_DEBUG=false
```

### 4. Secure Session Cookie

```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### 5. Use Strong Passwords

- Minimum 12 characters
- Mix of uppercase, lowercase, numbers, symbols
- Use password manager
- Never reuse passwords

### 6. Regular Backups

```bash
# Daily database backup
mysqldump -u username -p database_name | gzip > backup_$(date +%Y%m%d).sql.gz

# Weekly file backup
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

### 7. Monitor Logs

```bash
# Monitor for suspicious activity
tail -f storage/logs/laravel.log

# Check for failed login attempts
grep "Login failed" storage/logs/laravel.log
```

### 8. Limit File Uploads

```php
// Limit upload size
'media' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240', // 10MB

// Validate MIME type
'media.*' => 'nullable|mimetypes:image/jpeg,image/png,image/webp',
```

### 9. Use rate Limiting

```php
// Rate limit login attempts
'max_attempts' => 5,
'milliseconds' => 300000, // 5 minutes
```

### 10. Implement HTTPS

```apache
# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Security Testing

### Automated Testing

```bash
# Run security tests
php artisan test --filter=SecurityTest

# Run all tests
php artisan test
```

### Manual Testing

#### Test Authentication

```bash
# Try to access admin without login
curl -I https://example.com/admin

# Should redirect to login
```

#### Test CSRF

```bash
# Try to submit form without CSRF token
curl -X POST https://example.com/admin/properties -d "name=test"

# Should fail with 419
```

#### Test Rate Limiting

```bash
# Try to login multiple times
for i in {1..10}; do
    curl -X POST https://example.com/login -d "email=test@test.com&password=test"
done
```

## Conclusion

The security architecture provides:

- ✓ Authentication & authorization
- ✓ Password security
- ✓ CSRF protection
- ✓ XSS prevention
- ✓ SQL injection prevention
- ✓ File upload security
- ✓ Rate limiting
- ✓ Session security
- ✓ Audit logging
- ✓ Error handling
- ✓ Security headers

Regular security audits and updates are essential to maintain security.

---

## 2026-08-24 Remediation (per `docs/security-audit-report.md`)

Applied fixes for the high-confidence findings; regression tests live in `tests/Feature/SecurityTest.php`.

| Finding | Mitigation |
|---------|------------|
| FIND-001 | Public booking pages now keyed by random `access_token` (migration `2026_08_24_000000_add_access_token_to_bookings_table`). Sequential booking code / numeric id no longer resolve a booking (404). |
| FIND-002 | `.env` not tracked in git; `.gitignore` covers `.env`; `.env.example` contains no real secrets. Rotation of `APP_KEY`/DB creds remains a deployment task. |
| FIND-003 | Voucher redeemable by code only; discount applied via `BookingPricingService::calculate(..., promoRateId, voucherId)`; voucher `used_count` incremented inside the same transaction as booking creation. |
| FIND-004 | Availability check now runs `lockForUpdate()` inside the create transaction and covers transit windows; `max_guests` is enforced server-side. |
| FIND-005 | Rich content (`property.description`, `page.content`, `post.content`, string `block.content`) sanitized on write by `App\Services\SafeHtmlService` (stdlib DOM allowlist). |
| FIND-006 | JSON-LD `json_encode` now uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`. |
| FIND-007 | Gallery upload extension derived from verified MIME map, never client filename. |
| FIND-008 | `svg` removed from `MediaRequest` allowed mimes. |
| FIND-009 | `admin` can no longer assign the `super-admin` role; a user cannot change their own role in one request. |
| FIND-010 | CSV export prefixes cells starting with `= + - @` (and tab/CR) with `'`. |
| VERIFY-001 | Installer DB identifiers validated against `[A-Za-z0-9_$]` (DSN + `USE`). |
| VERIFY-005 | Webhook/log payload no longer contains full customer PII (name initial + masked phone). |
| VERIFY-006 | `max_guests` enforced in `BookingService::create()`. |
| VERIFY-008 | Password reset always returns the same success message (no user enumeration). |
| VERIFY-009 | `throttle` added to register (5/min), login (10/min), forgot-password (5/min). |

Deployment notes: run `php artisan migrate` for the new `access_token` column; rotate `APP_KEY` and DB credentials; set `APP_DEBUG=false` / `SESSION_SECURE_COOKIE=true` in production.
