# Analytics Integration

## Overview

The Analytics Integration Hub provides seamless integration with popular analytics services including Google Analytics 4, Google Tag Manager, Google Search Console, Meta Pixel, and Microsoft Clarity. All integrations are optional and can be enabled/disabled independently.

## Integration Architecture

### Integration Hub

```
Integration Hub
├── Google Analytics 4
├── Google Tag Manager
├── Google Search Console
├── Meta Pixel
├── Microsoft Clarity
└── Custom Scripts
```

### Integration Storage

```php
Schema::create('integrations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->json('config'); // Integration-specific settings
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
```

### Integration Examples

```php
// Google Analytics 4
Integration::create([
    'name' => 'Google Analytics 4',
    'slug' => 'google_analytics_4',
    'config' => [
        'measurement_id' => 'G-XXXXXXXXXX',
    ],
    'is_active' => true,
]);

// Meta Pixel
Integration::create([
    'name' => 'Meta Pixel',
    'slug' => 'meta_pixel',
    'config' => [
        'pixel_id' => 'XXXXXXXXXXXXXXX',
    ],
    'is_active' => true,
]);

// Google Tag Manager
Integration::create([
    'name' => 'Google Tag Manager',
    'slug' => 'google_tag_manager',
    'config' => [
        'container_id' => 'GTM-XXXXXXX',
    ],
    'is_active' => true,
]);
```

## Google Analytics 4

### Setup

1. Create GA4 property at https://analytics.google.com/
2. Copy Measurement ID (format: `G-XXXXXXXXXX`)
3. Go to Admin → Integrations → Google Analytics 4
4. Paste Measurement ID
5. Save

### GA4 Configuration

```php
// Integration settings
'slug' => 'google_analytics_4',
'config' => [
    'measurement_id' => 'G-XXXXXXXXXX', // Your GA4 Measurement ID
],
```

### GA4 Tracking Implementation

```html
<!-- GA4 Tracking Code -->
@if(settings('google_analytics_4.is_active'))
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ settings('google_analytics_4.config.measurement_id') }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    
    gtag('config', '{{ settings('google_analytics_4.config.measurement_id') }}', {
        page_title: '{{ $page_title ?? '' }}',
        page_location: '{{ url()->current() }}',
        page_path: '{{ request()->path() }}',
    });
</script>
@endif
```

### GA4 Events

```javascript
// Page views (automatic with gtag)
gtag('event', 'page_view', {
    page_title: document.title,
    page_location: window.location.href,
    page_path: window.location.pathname,
});

// Property views
gtag('event', 'view_property', {
    property_id: '{{ $property->id }}',
    property_name: '{{ $property->name }}',
    property_location: '{{ $property->city }}',
});

// Unit views
gtag('event', 'view_unit', {
    unit_id: '{{ $unit->id }}',
    unit_name: '{{ $unit->name }}',
    property_id: '{{ $unit->property->id }}',
    property_name: '{{ $unit->property->name }}',
    price: '{{ $unit->price }}',
    price_type: '{{ $unit->price_type }}',
});

// Booking events
gtag('event', 'click_tanya', {
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id }}',
});

gtag('event', 'click_booking', {
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id }}',
});

gtag('event', 'booking_form_open', {
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id }}',
});

gtag('event', 'booking_submit', {
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id }}',
    booking_code: '{{ $booking->booking_code ?? '' }}',
});

gtag('event', 'whatsapp_redirect', {
    property_id: '{{ $property->id }}',
    unit_id: '{{ $unit->id }}',
});
```

## Google Tag Manager

### Setup

1. Create GTM container at https://tagmanager.google.com/
2. Copy Container ID (format: `GTM-XXXXXXX`)
3. Go to Admin → Integrations → Google Tag Manager
4. Paste Container ID
5. Save

### GTM Configuration

```php
// Integration settings
'slug' => 'google_tag_manager',
'config' => [
    'container_id' => 'GTM-XXXXXXXX', // Your GTM Container ID
],
```

### GTM Implementation

```html
<!-- Google Tag Manager -->
@if(settings('google_tag_manager.is_active'))
<!-- GTM Head -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'../../../www.googletagmanager.com/gtm.php?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ settings('google_tag_manager.config.container_id') }}');</script>
<!-- End GTM Head -->

<!-- GTM Body -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ settings('google_tag_manager.config.container_id') }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End GTM Body -->
@endif
```

### GTM Data Layer

```javascript
// Push events to dataLayer
window.dataLayer = window.dataLayer || [];

// Page view
window.dataLayer.push({
    'event': 'page_view',
    'page_type': '{{ $page_type ?? "default" }}',
    'property_id': '{{ $property->id ?? null }}',
    'unit_id': '{{ $unit->id ?? null }}',
});

// Property view
window.dataLayer.push({
    'event': 'view_property',
    'property_id': '{{ $property->id }}',
    'property_name': '{{ $property->name }}',
    'property_location': '{{ $property->city }}',
});

// Unit view
window.dataLayer.push({
    'event': 'view_unit',
    'unit_id': '{{ $unit->id }}',
    'unit_name': '{{ $unit->name }}',
    'price': '{{ $unit->price }}',
});

// Booking events
window.dataLayer.push({
    'event': 'click_booking',
    'property_id': '{{ $property->id }}',
    'unit_id': '{{ $unit->id }}',
});

window.dataLayer.push({
    'event': 'booking_submit',
    'property_id': '{{ $property->id }}',
    'unit_id': '{{ $unit->id }}',
});
```

## Google Search Console

### Setup

1. Go to https://search.google.com/search-console/
2. Add your property
3. Choose verification method: HTML tag
4. Copy the verification token
5. Go to Admin → Integrations → Google Search Console
6. Paste verification token
7. Save

### Search Console Configuration

```php
// Integration settings
'slug' => 'search_console',
'config' => [
    'verification_token' => 'google1234567890abcdef.html',
],
```

### Verification Meta Tag

```html
<!-- Google Search Console Verification -->
@if(settings('search_console.is_active'))
<meta name="google-site-verification" content="{{ settings('search_console.config.verification_token') }}" />
@endif
```

## Meta Pixel

### Setup

1. Go to https://business.facebook.com/events_manager/
2. Create a new Pixel
3. Copy Pixel ID
4. Go to Admin → Integrations → Meta Pixel
5. Paste Pixel ID
6. Save

### Meta Pixel Configuration

```php
// Integration settings
'slug' => 'meta_pixel',
'config' => [
    'pixel_id' => 'XXXXXXXXXXXXXXX', // Your Meta Pixel ID
],
```

### Meta Pixel Implementation

```html
<!-- Meta Pixel Code -->
@if(settings('meta_pixel.is_active'))
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    
    fbq('init', '{{ settings('meta_pixel.config.pixel_id') }}');
    fbq('track', 'PageView');
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id={{ settings('meta_pixel.config.pixel_id') }}&ev=PageView&noscript=1"/>
</noscript>
@endif
```

### Meta Pixel Events

```javascript
// View content (property view)
fbq('track', 'ViewContent', {
    content_name: '{{ $property->name }}',
    content_ids: ['{{ $property->id }}'],
    content_type: 'product',
    value: '{{ $unit->price }}',
    currency: '{{ settings('currency', 'IDR') }}',
});

// Lead generation
fbq('track', 'Lead', {
    content_name: '{{ $property->name }} - {{ $unit->name }}',
    content_ids: ['{{ $unit->id }}'],
    content_type: 'product',
    value: '{{ $unit->price }}',
    currency: '{{ settings('currency', 'IDR') }}',
});

// Purchase (if booking confirmed)
fbq('track', 'Purchase', {
    content_name: '{{ $property->name }} - {{ $unit->name }}',
    content_ids: ['{{ $unit->id }}'],
    content_type: 'product',
    value: '{{ $unit->price }}',
    currency: '{{ settings('currency', 'IDR') }}',
});
```

## Microsoft Clarity

### Setup

1. Go to https://clarity.microsoft.com/
2. Create a new project
3. Copy the Project ID
4. Go to Admin → Integrations → Microsoft Clarity
5. Paste Project ID
6. Save

### Clarity Configuration

```php
// Integration settings
'slug' => 'microsoft_clarity',
'config' => [
    'project_id' => 'xxxxxxxxxx', // Your Clarity Project ID
],
```

### Clarity Implementation

```html
<!-- Microsoft Clarity -->
@if(settings('microsoft_clarity.is_active'))
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "{{ settings('microsoft_clarity.config.project_id') }}");
</script>
@endif
```

## Custom Scripts

### Custom HTML/JavaScript

```php
// Integration settings
'slug' => 'custom_scripts',
'config' => [
    'head_scripts' => '<!-- Custom head scripts -->',
    'body_scripts' => '<!-- Custom body scripts -->',
],
```

### Admin Form for Custom Scripts

```html
<div class="mb-6">
    <label class="block text-sm font-medium">Custom Head Scripts</label>
    <textarea name="head_scripts" rows="5" class="form-input mt-1">{{ settings('custom_scripts.config.head_scripts') }}</textarea>
    <p class="text-sm text-gray-500">Add custom scripts to insert in <head> (e.g., additional tracking, custom CSS)</p>
</div>

<div class="mb-6">
    <label class="block text-sm font-medium">Custom Body Scripts</label>
    <textarea name="body_scripts" rows="5" class="form-input mt-1">{{ settings('custom_scripts.config.body_scripts') }}</textarea>
    <p class="text-sm text-gray-500">Add custom scripts to insert before </body> tag</p>
</div>
```

## Analytics Dashboard

### Admin Analytics View

```html
<!-- resources/views/admin/analytics/dashboard.blade.php -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Analytics Overview</h2>
    </div>
    
    <div class="card-body">
        <!-- Integrations Status -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            @foreach($integrations as $integration)
                <div class="p-4 border rounded {{ $integration->is_active ? 'bg-green-50' : 'bg-gray-50' }}">
                    <h3 class="font-semibold">{{ $integration->name }}</h3>
                    <span class="text-sm {{ $integration->is_active ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $integration->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            @endforeach
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="p-4 border rounded">
                <h3 class="text-sm text-gray-500">Total Page Views</h3>
                <p class="text-2xl font-bold">{{ $stats['page_views'] ?? 0 }}</p>
            </div>
            
            <div class="p-4 border rounded">
                <h3 class="text-sm text-gray-500">Total Properties Viewed</h3>
                <p class="text-2xl font-bold">{{ $stats['property_views'] ?? 0 }}</p>
            </div>
            
            <div class="p-4 border rounded">
                <h3 class="text-sm text-gray-500">Total Units Viewed</h3>
                <p class="text-2xl font-bold">{{ $stats['unit_views'] ?? 0 }}</p>
            </div>
            
            <div class="p-4 border rounded">
                <h3 class="text-sm text-gray-500">Total Bookings</h3>
                <p class="text-2xl font-bold">{{ $stats['bookings'] ?? 0 }}</p>
            </div>
        </div>
        
        <!-- Top Pages -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold mb-4">Top Pages</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Views</th>
                        <th>Unique Visitors</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topPages as $page)
                        <tr>
                            <td>{{ $page['page'] }}</td>
                            <td>{{ $page['views'] }}</td>
                            <td>{{ $page['unique_visitors'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Top Properties -->
        <div>
            <h3 class="text-lg font-semibold mb-4">Top Properties</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Views</th>
                        <th>Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProperties as $property)
                        <tr>
                            <td>{{ $property->name }}</td>
                            <td>{{ $property->views_count }}</td>
                            <td>{{ $property->bookings_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
```

## Analytics Data Collection

### Page Views

```php
// Track page views
Event::listen('laravel.router.finished', function ($request, $response) {
    if ($request->method() === 'GET' && !$request->is('admin/*')) {
        // Log page view (optional - GA handles this)
        \Log::info('Page view', [
            'url' => $request->url(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
});
```

### Property Views

```php
// Track property views
class PropertyController extends Controller
{
    public function show(Property $property)
    {
        // Increment view count (optional)
        // $property->increment('views_count');
        
        return view('properties.show', compact('property'));
    }
}
```

### Booking Events

```php
// Track booking events
class BookingController extends Controller
{
    public function store(StoreBookingRequest $request)
    {
        $booking = BookingService::create($request->all());
        
        // Log booking event
        \Log::info('Booking created', [
            'booking_code' => $booking->booking_code,
            'unit_id' => $booking->unit_id,
            'property_id' => $booking->property_id,
            'customer' => $booking->name,
        ]);
        
        return response()->json([
            'success' => true,
            'booking_code' => $booking->booking_code,
            'whatsapp_url' => $booking->whatsapp_url,
        ]);
    }
}
```

## Analytics Privacy

### Cookie Consent (Optional)

```html
<!-- Cookie consent banner -->
@if(!session('cookie_consent'))
<div class="cookie-banner" x-show="!cookieConsent">
    <p>This website uses cookies to improve your experience.</p>
    <button @click="cookieConsent = true">Accept</button>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(!session('cookie_consent'))
        // Don't load analytics until consent
        @endif
    });
</script>
```

### Anonymize IP

```javascript
// Anonymize IP in GA4
gtag('config', 'G-XXXXXXXXXX', {
    anonymize_ip: true,
});
```

## Testing Integrations

### Verify Integration

```php
// Test if integration is working
public function testAnalytics()
{
    // Check if settings exist
    $gaActive = settings('google_analytics_4.is_active');
    $gtmActive = settings('google_tag_manager.is_active');
    
    return response()->json([
        'google_analytics_4' => $gaActive,
        'google_tag_manager' => $gtmActive,
    ]);
}
```

### Manual Test

```bash
# Check if GA4 is loaded
curl -s https://example.com | grep -o 'G-XXXXXXXXXX'

# Check if GTM is loaded
curl -s https://example.com | grep -o 'GTM-XXXXXXX'

# Check if Meta Pixel is loaded
curl -s https://example.com | grep -o 'XXXXXXXXXXXXXXX'
```

## Integration Management

### Admin Integration Settings

```php
class IntegrationController extends Controller
{
    public function index()
    {
        $integrations = Integration::all();
        return view('admin.integrations.index', compact('integrations'));
    }
    
    public function update(Request $request, $slug)
    {
        $integration = Integration::where('slug', $slug)->firstOrFail();
        
        $validated = $request->validate([
            'is_active' => 'boolean',
        ]);
        
        $integration->update($validated);
        
        return back()->with('success', 'Integration updated.');
    }
}
```

### Integration View

```html
<!-- resources/views/admin/integrations/index.blade.php -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Integrations</h2>
    </div>
    
    <div class="card-body">
        @foreach($integrations as $integration)
            <div class="integration-item">
                <div class="integration-header">
                    <h3>{{ $integration->name }}</h3>
                    <div class="toggle-switch">
                        <input type="checkbox" 
                               id="integration-{{ $integration->id }}"
                               {{ $integration->is_active ? 'checked' : '' }}
                               wire:change="toggleIntegration({{ $integration->id }})">
                        <label for="integration-{{ $integration->id }}"></label>
                    </div>
                </div>
                
                @if($integration->slug === 'google_analytics_4')
                    <div class="integration-config">
                        <label>Measurement ID</label>
                        <input type="text" 
                               value="{{ $integration->config['measurement_id'] ?? '' }}"
                               wire:change="updateConfig({{ $integration->id }}, 'measurement_id', $event.target.value)">
                    </div>
                @endif
                
                @if($integration->slug === 'meta_pixel')
                    <div class="integration-config">
                        <label>Pixel ID</label>
                        <input type="text" 
                               value="{{ $integration->config['pixel_id'] ?? '' }}"
                               wire:change="updateConfig({{ $integration->id }}, 'pixel_id', $event.target.value)">
                    </div>
                @endif
                
                @if($integration->slug === 'google_tag_manager')
                    <div class="integration-config">
                        <label>Container ID</label>
                        <input type="text" 
                               value="{{ $integration->config['container_id'] ?? '' }}"
                               wire:change="updateConfig({{ $integration->id }}, 'container_id', $event.target.value)">
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
```

## Performance Considerations

### Lazy Load Analytics

```javascript
// Lazy load analytics for better performance
function loadAnalytics() {
    if (settings('google_analytics_4.is_active')) {
        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX';
        document.head.appendChild(script);
    }
}

// Load after page load
window.addEventListener('load', loadAnalytics);
```

### Disable When Not Needed

```php
// Don't load analytics on admin pages
@if(!request()->is('admin/*'))
    @include('analytics.google-analytics-4')
    @include('analytics.meta-pixel')
@endif
```

## Conclusion

The Analytics Integration Hub provides:

- ✅ Google Analytics 4 support
- ✅ Google Tag Manager support
- ✅ Google Search Console verification
- ✅ Meta Pixel support
- ✅ Microsoft Clarity support
- ✅ Custom scripts support
- ✅ Optional integrations
- ✅ Admin management
- ✅ Performance-optimized

Integrations are completely optional and can be enabled/disabled independently without affecting website functionality.