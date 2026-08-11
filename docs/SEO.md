# SEO Architecture

## Overview

SEO is a core feature of the Apartment Rental CMS. Every public-facing page has comprehensive SEO metadata that can be customized per page, property, unit, blog post, and location. The system generates sitemaps, robots.txt, and JSON-LD structured data automatically.

## SEO Metadata Structure

### SEO Metadata Model

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `model_type` | string | Model class (Property, Unit, Post, Page, Location) |
| `model_id` | bigint | Model ID |
| `seo_title` | string | SEO title (60-70 chars recommended) |
| `seo_description` | string | Meta description (150-160 chars recommended) |
| `canonical_url` | string | Canonical URL |
| `og_title` | string | Open Graph title |
| `og_description` | string | Open Graph description |
| `og_image` | string | Open Graph image URL |
| `twitter_title` | string | Twitter/X title |
| `twitter_description` | string | Twitter/X description |
| `twitter_image` | string | Twitter/X image URL |
| `robots` | string | Robots directive |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

## SEO Metadata Fields

### 1. Title Tag

**Field:** `seo_title`

**Best Practices:**
- 50-70 characters
- Include primary keyword
- Include location
- Brand name at end

**Examples:**
```
Studio Apartment in BSD City | Luxury Living
1 Bedroom Apartment in Bekasi | Affordable Rates
Grand Kamala Lagoon Apartments
```

**Auto-generation Logic:**
```php
// If seo_title is empty
$seoTitle = $model->name . ' - ' . config('site_name');

// For property pages
$seoTitle = $model->name . ' Apartments in ' . $model->city . ' | ' . config('site_name');

// For unit pages
$seoTitle = $model->name . ' at ' . $model->property->name . ' | ' . config('site_name');
```

### 2. Meta Description

**Field:** `seo_description`

**Best Practices:**
- 150-160 characters
- Include key features
- Include call-to-action
- Natural language

**Examples:**
```
Luxury studio apartment in BSD City with modern amenities. Perfect for professionals. Book now!
Affordable 1 bedroom apartments in Bekasi. Includes parking, AC, and WiFi. Contact us today.
```

**Auto-generation Logic:**
```php
// If seo_description is empty
$seoDescription = Str::limit(strip_tags($model->description), 160);
```

### 3. Canonical URL

**Field:** `canonical_url`

**Purpose:** Prevents duplicate content issues

**Auto-generation Logic:**
```php
// Default canonical URL
$canonicalUrl = url()->current();

// For models with routes
$canonicalUrl = route($model->routeName(), $model);
```

### 4. Open Graph (OG) Tags

**Fields:** `og_title`, `og_description`, `og_image`

**Purpose:** Social media sharing (Facebook, LinkedIn, WhatsApp)

**Required OG Tags:**
```html
<meta property="og:title" content="Studio Apartment in BSD City">
<meta property="og:description" content="Luxury studio apartment with modern amenities">
<meta property="og:image" content="https://example.com/storage/og-image.jpg">
<meta property="og:url" content="https://example.com/apartments/property/unit">
<meta property="og:type" content="product">
<meta property="og:site_name" content="Website Name">
```

### 5. Twitter/X Cards

**Fields:** `twitter_title`, `twitter_description`, `twitter_image`

**Required Twitter Tags:**
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Studio Apartment in BSD City">
<meta name="twitter:description" content="Luxury studio apartment with modern amenities">
<meta name="twitter:image" content="https://example.com/storage/twitter-image.jpg">
<meta name="twitter:creator" content="@website">
```

### 6. Robots Directive

**Field:** `robots`

**Options:**
- `index, follow` (default)
- `noindex, follow`
- `index, nofollow`
- `noindex, nofollow`

**Use Cases:**
- `noindex, follow` - Admin pages, search results
- `noindex, nofollow` - Login, installer, private content

## SEO Implementation by Page Type

### Homepage SEO

```php
// SEO Metadata for Homepage
$seo = [
    'title' => 'Luxury Apartment Rentals in ' . $city . ' | ' . config('site_name'),
    'description' => 'Find your perfect apartment in ' . $city . '. Luxury units with modern amenities. Book today!',
    'canonical' => url('/'),
    'robots' => 'index, follow',
];
```

**Schema Markup:**
- Organization
- WebSite
- LocalBusiness

### Property Detail Page SEO

```php
// SEO Metadata for Property
$seo = [
    'title' => $property->name . ' Apartments in ' . $property->city . ' | ' . config('site_name'),
    'description' => Str::limit(strip_tags($property->description), 160),
    'canonical' => route('properties.show', $property),
    'og_image' => $property->featured_image_url,
    'robots' => 'index, follow',
];
```

**Schema Markup:**
- Organization
- RealEstateAgent
- RealEstateListing
- BreadcrumbList

### Unit Detail Page SEO

```php
// SEO Metadata for Unit
$seo = [
    'title' => $unit->name . ' at ' . $unit->property->name . ' | ' . config('site_name'),
    'description' => $unit->bedrooms . ' bedroom apartment starting at Rp ' . number_format($unit->price) . '. ' . Str::limit(strip_tags($unit->description), 160),
    'canonical' => route('units.show', [$unit->property, $unit]),
    'og_image' => $unit->featured_image_url,
    'robots' => 'index, follow',
];
```

**Schema Markup:**
- Organization
- RealEstateAgent
- RealEstateListing
- Offer
- BreadcrumbList

### Blog Post SEO

```php
// SEO Metadata for Blog Post
$seo = [
    'title' => $post->title . ' | ' . config('site_name'),
    'description' => Str::limit(strip_tags($post->excerpt), 160),
    'canonical' => route('blog.show', $post),
    'og_image' => $post->featured_image_url,
    'robots' => 'index, follow',
];
```

**Schema Markup:**
- Organization
- Article
- BreadcrumbList

### Location Page SEO

```php
// SEO Metadata for Location
$seo = [
    'title' => 'Apartments in ' . $location->name . ' | ' . config('site_name'),
    'description' => 'Find apartments in ' . $location->city . '. ' . Str::limit(strip_tags($location->description), 160),
    'canonical' => route('locations.show', $location),
    'robots' => 'index, follow',
];
```

**Schema Markup:**
- Organization
- Place
- LocalBusiness

## Sitemap Generation

### Sitemap.xml

**Endpoint:** `/sitemap.xml`

**Generated Automatically:**
- Homepage
- Properties
- Units
- Blog posts
- Pages
- Locations

**Excluded:**
- Admin routes
- Login routes
- Installer routes
- Private content
- Noindex pages

### Sitemap Format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <lastmod>2026-08-11T10:30:00+07:00</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://example.com/apartments/property-slug</loc>
        <lastmod>2026-08-10T08:15:00+07:00</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://example.com/apartments/property-slug/unit-slug</loc>
        <lastmod>2026-08-09T14:20:00+07:00</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
</urlset>
```

### Sitemap Cache

```php
// Cache sitemap for 24 hours
$cacheKey = 'sitemap.' . $cacheVersion;

if (Cache::has($cacheKey)) {
    return Cache::get($cacheKey);
}

$content = $this->generateSitemap();
Cache::put($cacheKey, $content, now()->addDay());

return $content;
```

### Sitemap Trigger

```php
// Auto-update sitemap on content change
Property::saved(function($property) {
    Cache::delete('sitemap');
});

Unit::saved(function($unit) {
    Cache::delete('sitemap');
});

Post::saved(function($post) {
    Cache::delete('sitemap');
});
```

## robots.txt

### robots.txt Content

```
User-agent: *
Allow: /

# Disallow admin areas
Disallow: /admin
Disallow: /login
Disallow: /install
Disallow: /reset-password
Disallow: /register

# Disallow private content
Disallow: /bookings
Disallow: /profile

# Disallow system files
Disallow: /.env
Disallow: /composer.json
Disallow: /composer.lock

# Sitemap location
Sitemap: https://example.com/sitemap.xml

# Crawl delay (optional)
Crawl-delay: 1
```

### robots.txt Implementation

```php
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

class RobotsController extends Controller
{
    public function index()
    {
        $content = $this->generateRobots();
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
    
    protected function generateRobots()
    {
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n\n";
        
        // Disallow admin areas
        $robots .= "# Disallow admin areas\n";
        $robots .= "Disallow: /admin\n";
        $robots .= "Disallow: /login\n";
        $robots .= "Disallow: /install\n\n";
        
        // Sitemap
        $robots .= "# Sitemap location\n";
        $robots .= "Sitemap: " . url('/sitemap.xml') . "\n";
        
        return $robots;
    }
}
```

## JSON-LD Structured Data

### Organization Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Website Name",
  "url": "https://example.com",
  "logo": "https://example.com/storage/logo.png",
  "sameAs": [
    "https://instagram.com/website",
    "https://facebook.com/website",
    "https://twitter.com/website"
  ]
}
```

### WebSite Schema

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Website Name",
  "url": "https://example.com",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://example.com/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
```

### RealEstateListing Schema

```json
{
  "@context": "https://schema.org",
  "@type": "RealEstateListing",
  "name": "Grand Kamala Lagoon",
  "description": "Luxury apartment complex in BSD City",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Jalan Grand Kamala",
    "addressLocality": "BSD City",
    "addressRegion": "Banten",
    "postalCode": "15332",
    "addressCountry": "ID"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": -6.2389,
    "longitude": 106.6629
  },
  "telephone": "+6281234567890",
  "email": "info@example.com"
}
```

### Offer Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Offer",
  "name": "Studio Deluxe",
  "description": "Luxury studio apartment",
  "price": "2500000",
  "priceCurrency": "IDR",
  "availability": "https://schema.org/InStock",
  "url": "https://example.com/apartments/property/unit"
}
```

### Article Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "How to Choose the Right Apartment",
  "description": "Tips for finding your perfect apartment",
  "datePublished": "2026-08-10",
  "dateModified": "2026-08-11",
  "author": {
    "@type": "Person",
    "name": "Admin"
  }
}
```

### BreadcrumbList Schema

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://example.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Properties",
      "item": "https://example.com/apartments"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Grand Kamala Lagoon",
      "item": "https://example.com/apartments/grand-kamala-lagoon"
    }
  ]
}
```

### Implementation in Blade

```php
<!-- resources/views/components/seo.blade.php -->
@php
    $seo = $seo ?? SEOService::generateMetaTags($model ?? null);
    $schema = $schema ?? SEOService::generateStructuredData($model ?? null);
@endphp

<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

<!-- Open Graph -->
<meta property="og:title" content="{{ $seo['og_title'] ?? $seo['title'] }}">
<meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] }}">
<meta property="og:image" content="{{ $seo['og_image'] ?? asset('images/og-default.jpg') }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">

<!-- Twitter/X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['twitter_title'] ?? $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['twitter_description'] ?? $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['twitter_image'] ?? $seo['og_image'] ?? asset('images/og-default.jpg') }}">

<!-- Robots -->
<meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">

<!-- JSON-LD -->
<script type="application/ld+json">
{{ json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
</script>
```

## SEO Service Implementation

### SEOService Class

```php
class SEOService
{
    public function generateMetaTags($model): array
    {
        if (!$model) {
            return $this->getDefaultSEO();
        }
        
        $seoTitle = $this->getSeoTitle($model);
        $seoDescription = $this->getSeoDescription($model);
        $canonical = $this->getCanonicalUrl($model);
        $ogImage = $this->getOgImage($model);
        
        return [
            'title' => $seoTitle,
            'description' => $seoDescription,
            'canonical' => $canonical,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => $ogImage,
            'twitter_title' => $seoTitle,
            'twitter_description' => $seoDescription,
            'twitter_image' => $ogImage,
            'robots' => 'index, follow',
        ];
    }
    
    public function generateStructuredData($model): array
    {
        if (!$model) {
            return $this->getOrganizationSchema();
        }
        
        if ($model instanceof Property) {
            return array_merge($this->getOrganizationSchema(), [
                $this->getRealEstateListingSchema($model),
                $this->getBreadcrumbSchema($model),
            ]);
        } elseif ($model instanceof Unit) {
            return array_merge($this->getOrganizationSchema(), [
                $this->getRealEstateListingSchema($model->property),
                $this->getOfferSchema($model),
                $this->getBreadcrumbSchema($model),
            ]);
        } elseif ($model instanceof Post) {
            return array_merge($this->getOrganizationSchema(), [
                $this->getArticleSchema($model),
                $this->getBreadcrumbSchema($model),
            ]);
        }
        
        return $this->getOrganizationSchema();
    }
    
    protected function getSeoTitle($model): string
    {
        if ($model->seo_title) {
            return $model->seo_title;
        }
        
        if ($model instanceof Property) {
            return $model->name . ' Apartments in ' . $model->city;
        }
        
        if ($model instanceof Unit) {
            return $model->name . ' at ' . $model->property->name;
        }
        
        if ($model instanceof Post) {
            return $model->title . ' | Blog';
        }
        
        return config('site_name');
    }
    
    protected function getSeoDescription($model): string
    {
        if ($model->seo_description) {
            return $model->seo_description;
        }
        
        return Str::limit(strip_tags($model->description ?? ''), 160);
    }
    
    protected function getCanonicalUrl($model): string
    {
        if ($model->canonical_url) {
            return $model->canonical_url;
        }
        
        if ($model instanceof Property) {
            return route('properties.show', $model);
        }
        
        if ($model instanceof Unit) {
            return route('units.show', [$model->property, $model]);
        }
        
        if ($model instanceof Post) {
            return route('blog.show', $model);
        }
        
        return url()->current();
    }
    
    protected function getOgImage($model): string
    {
        if ($model->og_image) {
            return $model->og_image;
        }
        
        // Use featured image or default
        return asset('images/og-default.jpg');
    }
}
```

## SEO Best Practices

### 1. Title Tag Optimization

- ✓ Include primary keyword
- ✓ Keep under 70 characters
- ✓ Use location for local SEO
- ✓ Brand name at end
- ✓ Unique per page

### 2. Meta Description Optimization

- ✓ Include keywords naturally
- ✓ Keep 150-160 characters
- ✓ Include call-to-action
- ✓ Unique per page
- ✓ Write for humans first

### 3. URL Structure

```
Good: /apartments/grand-kamala-lagoon/studio-deluxe
Bad: /apartments?id=123&unit=456
```

### 4. Heading Structure

```html
<!-- Good -->
<h1>Grand Kamala Lagoon</h1>
<h2>Studio Deluxe Apartment</h2>
<h3>Property Features</h3>
<h3>Booking Information</h3>

<!-- Bad -->
<h2>Grand Kamala Lagoon</h2>
<h1>Studio Deluxe Apartment</h1>
```

### 5. Image Optimization

- ✓ Descriptive filenames: `grand-kamala-lagoon-studio-deluxe.jpg`
- ✓ Alt text with keywords: `Studio Deluxe at Grand Kamala Lagoon`
- ✓ Compressed images
- ✓ Responsive images with `srcset`

### 6. Internal Linking

- ✓ Link to related properties
- ✓ Link to blog posts
- ✓ Link to location pages
- ✓ Use descriptive anchor text

## SEO Integration with Analytics

### Google Analytics 4 Events

```javascript
// SEO-related events
window.dataLayer.push({
    event: 'page_view',
    page_type: 'property_detail',
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id ?? null }}',
});

window.dataLayer.push({
    event: 'click_property',
    property_id: '{{ $property->id }}',
    property_name: '{{ $property->name }}',
});
```

### Schema Markup for SEO

- ✓ Organization
- ✓ WebSite
- ✓ LocalBusiness (if applicable)
- ✓ RealEstateListing
- ✓ Offer
- ✓ Article
- ✓ BreadcrumbList
- ✓ FAQPage (if FAQ section exists)

## Conclusion

The SEO architecture provides:

- ✓ Per-page SEO metadata customization
- ✓ Automatic sitemap generation
- ✓ robots.txt generation
- ✓ JSON-LD structured data
- ✓ Open Graph and Twitter/X cards
- ✓ Canonical URLs
- ✓ robots directive control
- ✓ Auto-generation fallbacks
- ✓ SEO service layer

Next: Continue to [SECURITY.md](SECURITY.md) for security architecture documentation.