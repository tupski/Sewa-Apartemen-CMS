# Architecture Overview

## System Purpose

The Generic Apartment Rental CMS is a **single-installation, configuration-driven content management system** built for apartment rental businesses. Each installation serves **one client** with their own database, domain, and branding. The same codebase can be installed multiple times for different clients without code modifications.

## Core Principles

### 1. Single-Site Architecture
- **One installation = One client** - No multi-tenancy, no tenant_id fields
- Each deployment has its own database, domain, and complete isolation
- Zero shared resources between installations

### 2. Configuration Over Code
- All client-specific data stored in database [`settings`](docs/DATABASE.md) table
- No hardcoded domains, phone numbers, or branding
- Theme customization via database-driven CSS variables
- Dynamic content through [`pages`](docs/DATABASE.md) and [`blocks`](docs/DATABASE.md) system

### 3. cPanel-First Design
- **No runtime dependencies**: No Redis, Docker, Supervisor, or Node.js in production
- File-based caching using Laravel's native cache system
- Database queue driver (sync or database, no workers required)
- Works on shared hosting with PHP 8.3+ and MySQL 8+

### 4. Progressive Enhancement
- Works without JavaScript (forms, navigation, SEO content)
- Enhanced with Alpine.js for interactivity (image galleries, mobile menus, form validation)
- Mobile-first responsive design with Tailwind CSS

### 5. SEO as a Core Feature
- Every entity (property, unit, page, post) has SEO metadata
- Auto-generated [`sitemap.xml`](docs/SEO.md) and [`robots.txt`](docs/SEO.md)
- JSON-LD structured data for rich snippets
- Clean URLs with slugs, no query strings

## Technology Stack

### Backend
- **Laravel 12** - PHP framework with Blade templating
- **PHP 8.3+** - Required extensions: PDO, MySQL, Mbstring, OpenSSL, Tokenizer, XML, Ctype, JSON, Fileinfo, GD
- **MySQL 8+** or MariaDB - Relational database with InnoDB engine

### Frontend
- **Blade Templates** - Server-side rendering for fast initial load
- **Tailwind CSS** - Utility-first CSS framework (compiled during build)
- **Alpine.js** - Minimal JavaScript framework (~15KB) for interactivity
- **Responsive Images** - `srcset` and `sizes` attributes with lazy loading

### Storage
- **Local Filesystem** - Default storage driver
- Organized structure: `storage/app/public/{media,logos,thumbnails}`
- Symlinked to `public/storage` for web access
- S3-compatible abstraction for future cloud storage

### Caching
- **File-based cache** - No Redis dependency
- Cache driver: `file` (default) or `database`
- Cached data: settings, routes, views, config
- Clear cache via artisan commands

### Queue System
- **Sync or Database driver** - No background worker required
- Queue jobs: email notifications, sitemap generation
- Can upgrade to Redis queue later if needed

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Web Browser                              │
│              (HTML + Tailwind CSS + Alpine.js)                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Apache/LiteSpeed                            │
│                     (with mod_rewrite)                           │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Laravel 12 Router                           │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Public     │  │    Admin     │  │  Installer   │          │
│  │   Routes     │  │   Routes     │  │   Routes     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                  │
│         ↓                  ↓                  ↓                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  Property    │  │   Admin      │  │  Installer   │          │
│  │ Controllers  │  │ Controllers  │  │ Controller   │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                  │
│         └──────────┬───────┴──────────────────┘                 │
│                    │                                             │
│                    ↓                                             │
│         ┌─────────────────────┐                                 │
│         │   Business Logic    │                                 │
│         │                     │                                 │
│         │  ┌──────────────┐  │                                 │
│         │  │   Services   │  │                                 │
│         │  │              │  │                                 │
│         │  │ - Settings   │  │                                 │
│         │  │ - SEO        │  │                                 │
│         │  │ - WhatsApp   │  │                                 │
│         │  │ - Media      │  │                                 │
│         │  └──────────────┘  │                                 │
│         │                     │                                 │
│         │  ┌──────────────┐  │                                 │
│         │  │   Models     │  │                                 │
│         │  │  (Eloquent)  │  │                                 │
│         │  └──────────────┘  │                                 │
│         └──────────┬──────────┘                                 │
│                    │                                             │
└────────────────────┼─────────────────────────────────────────────┘
                     │
                     ↓
          ┌──────────────────────┐
          │   MySQL Database     │
          │                      │
          │ - Properties         │
          │ - Units              │
          │ - Bookings           │
          │ - Pages/Blocks       │
          │ - Settings           │
          │ - Media Library      │
          └──────────────────────┘
```

## Application Layers

### 1. Presentation Layer (Blade + Tailwind + Alpine.js)

**Public Views**
- Homepage: Hero, featured properties, search, testimonials
- Property listing: Grid/list view with filters
- Property detail: Gallery, description, units list, amenities
- Unit detail: Booking form, pricing, availability info
- Location pages: City/province listings with SEO content
- Blog: Post listing, categories, tags, single post
- Custom pages: Dynamic content with blocks

**Admin Views**
- Dashboard: Stats overview, recent bookings, quick actions
- CRUD interfaces: Properties, units, pages, posts, amenities, locations
- Media library: Upload, organize, edit metadata
- Settings: Site config, branding, integrations, SEO defaults
- User management: Admin users (simple RBAC)

**Blade Components**
```php
// Example: Property card component
<x-property-card :property="$property" />

// Example: Booking form component
<x-booking-form :unit="$unit" />

// Example: SEO meta tags component
<x-seo-meta :model="$property" />
```

### 2. Application Layer (Controllers + Services)

**Controllers**
- `HomeController` - Homepage rendering
- `PropertyController` - Property and unit listing/detail
- `BookingController` - Handle booking submissions
- `PageController` - Dynamic page rendering
- `BlogController` - Blog listing and posts
- `LocationController` - Location pages
- Admin controllers for CRUD operations

**Services** (Business Logic)
```php
// SettingsService - Centralized settings access
SettingsService::get('site_name');
SettingsService::get('whatsapp_default');
SettingsService::set('primary_color', '#3B82F6');

// SEOService - Generate meta tags, structured data
SEOService::generateMetaTags($model);
SEOService::generateStructuredData($property);

// WhatsAppService - Generate WhatsApp URLs
WhatsAppService::generateUrl($booking, $unit);

// MediaService - Handle uploads, thumbnails, optimization
MediaService::upload($file, $folder);
MediaService::generateResponsiveImages($media);

// BookingService - Create bookings, generate codes
BookingService::create($data);
BookingService::generateCode(); // BK-20260811-0001

// SitemapService - Generate sitemap.xml
SitemapService::generate();
```

### 3. Domain Layer (Eloquent Models)

**Core Models**
- `Property` - Apartment buildings/complexes
- `Unit` - Individual rentable units
- `Amenity` - Reusable amenities (WiFi, Pool, AC)
- `Booking` - Lead capture with customer info
- `Page` - Dynamic pages with SEO
- `Block` - Page content blocks
- `Post` - Blog posts
- `Category` - Post categories
- `Tag` - Post tags
- `Location` - City/province for local SEO
- `Media` - Media library items
- `Setting` - Key-value configuration
- `User` - Admin users
- `Redirect` - 301/302 URL redirects
- `AuditLog` - Security audit trail

**Relationships**
```php
// Property has many Units
Property::hasMany(Unit::class);

// Property belongsToMany Amenities
Property::belongsToMany(Amenity::class);

// Unit belongsToMany Amenities
Unit::belongsToMany(Amenity::class);

// Page hasMany Blocks
Page::hasMany(Block::class);

// Post belongsToMany Tags
Post::belongsToMany(Tag::class);

// Booking belongsTo Unit
Booking::belongsTo(Unit::class);
```

### 4. Data Layer (Database)

See [`DATABASE.md`](docs/DATABASE.md) for complete schema with ERD diagram.

**Key Design Decisions**
- InnoDB engine for ACID compliance and foreign key support
- Soft deletes on main entities (properties, units, pages, posts)
- Indexes on foreign keys, slugs, status fields, published_at dates
- JSON columns for flexible metadata (amenities, settings)
- Timestamps (`created_at`, `updated_at`) on all tables
- `published_at` for scheduling content

## Request Lifecycle

### Public Request Flow
```
1. User visits /apartments/luxury-apartment/unit-a1
2. Apache/LiteSpeed rewrites to /index.php
3. Laravel router matches route to UnitController@show
4. Middleware: web, CheckInstalled
5. Controller loads Unit with Property, Amenities (eager loading)
6. SEOService generates meta tags and structured data
7. SettingsService loads site configuration
8. Blade renders unit detail view with data
9. Response sent to browser with HTML + CSS + minimal JS
```

### Booking Submission Flow
```
1. User fills booking form, submits to /bookings
2. Laravel validates input (BookingRequest)
3. BookingController creates Booking record
4. BookingService generates booking code (BK-20260811-0001)
5. WhatsAppService determines phone number (Unit → Property → Default)
6. WhatsAppService generates prefilled message with booking details
7. Controller redirects to WhatsApp URL
8. User continues conversation on WhatsApp
```

### Admin Request Flow
```
1. Admin visits /admin/properties
2. Middleware: web, auth, CheckInstalled
3. PropertyController@index loads paginated properties
4. Blade renders admin list view with DataTables/pagination
5. Admin clicks "Edit" on property
6. PropertyController@edit loads property with relations
7. Admin updates form, submits to /admin/properties/{id}
8. Validation, update database, flash message
9. Redirect to /admin/properties with success message
```

### Installer Request Flow
```
1. Fresh installation, user visits /install
2. InstallerController checks if already installed (lock file)
3. If locked, abort(403, "Already installed")
4. If unlocked, show step 1: Requirements check
5. User proceeds through steps 1-5 (see INSTALLER.md)
6. Step 5: Create .installed lock file
7. Redirect to /admin with success message
```

## Routing Strategy

### Public Routes (`routes/web.php`)
```php
// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Properties
Route::get('/apartments', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/apartments/{property:slug}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/apartments/{property:slug}/{unit:slug}', [UnitController::class, 'show'])->name('units.show');

// Bookings
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');

// Locations
Route::get('/locations/{location:slug}', [LocationController::class, 'show'])->name('locations.show');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Pages
Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
```

### Admin Routes (`routes/admin.php`)
```php
Route::prefix('admin')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Resource routes
    Route::resource('properties', PropertyController::class);
    Route::resource('units', UnitController::class);
    Route::resource('amenities', AmenityController::class);
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
    Route::resource('pages', PageController::class);
    Route::resource('posts', PostController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('tags', TagController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('media', MediaController::class);
    Route::resource('redirects', RedirectController::class);
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update');
});
```

### Installer Routes (`routes/install.php`)
```php
Route::prefix('install')->middleware('web')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('install');
    Route::get('/step/{step}', [InstallerController::class, 'step'])->name('install.step');
    Route::post('/step/{step}', [InstallerController::class, 'processStep'])->name('install.process');
});
```

## Middleware Stack

### Global Middleware
- `TrustProxies` - Handle load balancers
- `ValidatePostSize` - Prevent oversized requests
- `TrimStrings` - Trim input strings
- `ConvertEmptyStringsToNull` - Normalize empty inputs

### Route Middleware
- `web` - Session, cookies, CSRF, encryption
- `auth` - Require authentication
- `CheckInstalled` - Redirect to installer if not installed
- `PreventAccessWhenInstalled` - Block installer after installation
- `ThrottleRequests` - Rate limiting (60 requests/minute for web, 5 for bookings)

## Service Layer Design

Services encapsulate business logic and provide reusable functionality.

### SettingsService
```php
class SettingsService
{
    protected static $cache = null;
    
    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::$cache = Setting::pluck('value', 'key')->toArray();
        }
        
        return self::$cache[$key] ?? $default;
    }
    
    public static function set(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        self::clearCache();
    }
    
    public static function clearCache(): void
    {
        self::$cache = null;
        Cache::forget('settings');
    }
}
```

### SEOService
```php
class SEOService
{
    public function generateMetaTags($model): array
    {
        return [
            'title' => $model->seo_title ?? $model->title,
            'description' => $model->seo_description ?? Str::limit($model->description, 160),
            'canonical' => $model->canonical_url ?? route($model->routeName(), $model),
            'og_image' => $model->featured_image_url ?? SettingsService::get('default_og_image'),
            // ... more meta tags
        ];
    }
    
    public function generateStructuredData($model): array
    {
        // Generate JSON-LD based on model type
        if ($model instanceof Property) {
            return $this->accommodationSchema($model);
        } elseif ($model instanceof Post) {
            return $this->articleSchema($model);
        }
        // ... more schema types
    }
}
```

### WhatsAppService
```php
class WhatsAppService
{
    public function generateUrl(Booking $booking, Unit $unit): string
    {
        $phone = $this->getPhoneNumber($unit);
        $message = $this->generateMessage($booking, $unit);
        
        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }
    
    protected function getPhoneNumber(Unit $unit): string
    {
        // Fallback hierarchy: Unit → Property → Default
        return $unit->whatsapp_number
            ?? $unit->property->whatsapp_number
            ?? SettingsService::get('whatsapp_default');
    }
    
    protected function generateMessage(Booking $booking, Unit $unit): string
    {
        return "Hi! I'd like to book {$unit->name} at {$unit->property->name}.\n\n"
            . "Booking Code: {$booking->code}\n"
            . "Name: {$booking->customer_name}\n"
            . "Check-in: {$booking->check_in}\n"
            . "Check-out: {$booking->check_out}\n"
            . "Guests: {$booking->guests}";
    }
}
```

## Security Architecture

See [`SECURITY.md`](docs/SECURITY.md) for complete details.

**Key Security Measures**
- CSRF protection on all forms
- XSS prevention (Blade auto-escaping)
- SQL injection prevention (Eloquent ORM, parameter binding)
- File upload validation (MIME type, extension whitelist, size limit)
- Rate limiting on forms (login, booking, contact)
- Password hashing (bcrypt)
- Audit logging for sensitive actions
- Secure session configuration
- HTTPS enforcement in production

## Performance Optimization

### Database Optimization
- Eager loading to prevent N+1 queries
- Indexes on foreign keys, slugs, status fields
- Query result caching for heavy queries
- Pagination for all listings (20-50 per page)

### Frontend Optimization
- Minified CSS and JS (via Vite/Laravel Mix)
- Responsive images with `srcset`
- Lazy loading images below fold
- Critical CSS inlined in `<head>`
- Defer non-critical JavaScript
- CDN for static assets (optional)

### Caching Strategy
```php
// Settings cached indefinitely
Cache::rememberForever('settings', fn() => Setting::pluck('value', 'key'));

// Properties cached for 1 hour
Cache::remember('properties.featured', 3600, fn() => Property::featured()->get());

// Sitemap cached for 24 hours
Cache::remember('sitemap', 86400, fn() => SitemapService::generate());
```

### Query Optimization Examples
```php
// BAD: N+1 queries
$properties = Property::all();
foreach ($properties as $property) {
    echo $property->units->count(); // Query per property!
}

// GOOD: Eager loading
$properties = Property::withCount('units')->get();
foreach ($properties as $property) {
    echo $property->units_count; // No additional queries
}
```

## Deployment Architecture

### File Structure in Production
```
/home/username/
├── public_html/                 # Apache document root
│   ├── .htaccess               # Laravel rewrite rules
│   ├── index.php               # Entry point
│   ├── favicon.ico
│   ├── robots.txt (symlink)
│   └── storage -> ../apartment-cms/storage/app/public
│
├── apartment-cms/               # Application code (outside public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/                  # Symlinked to public_html
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   │   ├── app/
│   │   │   └── public/         # Uploaded media
│   │   ├── framework/
│   │   │   ├── cache/
│   │   │   ├── sessions/
│   │   │   └── views/
│   │   └── logs/
│   ├── .env                     # Environment config (secured)
│   ├── .installed              # Lock file
│   ├── artisan
│   ├── composer.json
│   └── composer.lock
```

### Environment Configuration
```ini
# .env file
APP_NAME="Your Apartment Name"
APP_ENV=production
APP_KEY=base64:generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
```

## Extensibility Points

### Custom Themes
Theme system allows visual customization without code changes:
- CSS variables stored in database
- Template overrides in `resources/views/themes/{theme-name}/`
- See [`THEMING.md`](docs/THEMING.md)

### Custom Blocks
New block types can be added by:
1. Creating block Blade component in `resources/views/components/blocks/`
2. Adding block type to `BlockType` enum
3. Creating admin form for block settings

### Third-Party Integrations
Integration hub for analytics and marketing:
- GA4, GTM, Meta Pixel, Microsoft Clarity
- Stored as settings, injected via Blade partials
- See [`ANALYTICS.md`](docs/ANALYTICS.md)

### Storage Drivers
Filesystem abstraction allows switching storage:
- Default: Local filesystem
- Optional: S3, DigitalOcean Spaces, Cloudinary
- Change via `FILESYSTEM_DISK` in `.env`

## Scalability Considerations

### Current Architecture (Shared Hosting)
- Single server (web + database)
- File-based cache and sessions
- Local filesystem storage
- Suitable for: 1-10K visitors/month, <1K properties

### Future Scaling Path
When traffic grows, can upgrade to:
1. **Separate database server** - Move MySQL to dedicated server
2. **Redis cache** - Switch from file to Redis cache driver
3. **Queue workers** - Switch from sync to Redis queue with Supervisor
4. **CDN** - CloudFlare or similar for static assets
5. **Object storage** - Move uploads to S3/Spaces
6. **Load balancer** - Multiple web servers behind load balancer
7. **Read replicas** - Database read replicas for heavy queries

## Development Workflow

### Local Development
```bash
# Clone repository
git clone https://github.com/your-repo/apartment-cms.git
cd apartment-cms

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run dev

# Start development server
php artisan serve
```

### Build Process
```bash
# Production asset build
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Testing
See [`TESTING.md`](docs/TESTING.md) for complete testing strategy.

## Maintenance

### Regular Tasks
- **Daily**: Monitor error logs, check booking submissions
- **Weekly**: Database backup, review security logs
- **Monthly**: Update dependencies (composer, npm), performance audit
- **Quarterly**: SEO audit, content review, user feedback analysis

### Backup Strategy
```bash
# Database backup (cPanel or command line)
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# File backup (via cPanel or rsync)
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

### Monitoring
- **Error logging**: Laravel logs to `storage/logs/laravel.log`
- **Audit logging**: Security events logged to `audit_logs` table
- **Performance**: Monitor slow queries, cache hit rates
- **Uptime**: Use external monitoring service (UptimeRobot, Pingdom)

## Conclusion

This architecture prioritizes:
- **Simplicity**: Single-site, no multi-tenancy complexity
- **Portability**: Works on any cPanel hosting
- **Performance**: Optimized for shared hosting constraints
- **Security**: Built-in protection against common vulnerabilities
- **SEO**: Technical SEO as a first-class feature
- **Maintainability**: Clear separation of concerns, service layer
- **Extensibility**: Plugin points for themes, blocks, integrations

Next steps:
1. Review [`DATABASE.md`](docs/DATABASE.md) for complete schema
2. Review [`INSTALLER.md`](docs/INSTALLER.md) for setup flow
3. Review [`SECURITY.md`](docs/SECURITY.md) for security details
4. Review [`ROADMAP.md`](docs/ROADMAP.md) for implementation phases
