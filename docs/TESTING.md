# Testing Strategy

## Overview

This document outlines the testing strategy for the Apartment Rental CMS. Testing is critical to ensure the system works correctly and remains reliable as new features are added.

## Testing Pyramid

```
                    ┌─────────────────────────────┐
                    │     Integration Tests       │
                    │   (Feature Tests)           │
                    └─────────────┬───────────────┘
                                  │
                    ┌─────────────┴───────────────┐
                    │      Unit Tests             │
                    │   (Model, Service, Helper)  │
                    └─────────────┬───────────────┘
                                  │
                    ┌─────────────┴───────────────┐
                    │  Browser/Endpoint Tests     │
                    │   (Laravel Dusk)            │
                    └─────────────────────────────┘
```

## Test Categories

### 1. Installer Tests

#### Requirements Check Tests

```php
/** @test */
public function it_checks_php_version_requirements()
{
    $response = $this->get('/install');
    
    $response->assertSee('PHP 8.3+');
    $response->assertSee('8.3.10'); // Current PHP version
}

/** @test */
public function it_checks_required_extensions()
{
    $response = $this->get('/install');
    
    $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd'];
    
    foreach ($extensions as $extension) {
        $response->assertSee($extension, false);
    }
}

/** @test */
public function it_checks_storage_permissions()
{
    $response = $this->get('/install');
    
    $response->assertSee('storage/'); // Check permissions
}

/** @test */
public function it_redirects_when_already_installed()
{
    // Create lock file
    touch(storage_path('installed.lock'));
    
    $response = $this->get('/install');
    
    $response->assertStatus(403);
    $response->assertSee('Installation already completed');
}
```

#### Application Configuration Tests

```php
/** @test */
public function it_validates_application_name()
{
    $response = $this->post('/install/step/2', [
        'app_name' => '',
    ]);
    
    $response->assertSessionHasErrors('app_name');
}

/** @test */
public function it_validates_application_url()
{
    $response = $this->post('/install/step/2', [
        'app_name' => 'Test App',
        'app_url' => 'invalid-url',
    ]);
    
    $response->assertSessionHasErrors('app_url');
}

/** @test */
public function it_saves_application_settings()
{
    $response = $this->from('/install/step/2')
        ->post('/install/step/2', [
            'app_name' => 'Test Apartment',
            'app_url' => 'https://test.com',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'currency' => 'IDR',
        ]);
    
    $response->assertSessionHasNoErrors();
    
    $this->assertEquals('Test Apartment', config('app.name'));
    $this->assertEquals('Asia/Jakarta', config('app.timezone'));
}
```

#### Database Configuration Tests

```php
/** @test */
public function it_tests_database_connection()
{
    $response = $this->post('/install/step/3/test-connection', [
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_database' => 'test_database',
        'db_username' => 'test_user',
        'db_password' => 'test_password',
    ]);
    
    $response->assertJson([
        'success' => false, // Expected to fail in test
        'message' => 'Connection failed',
    ]);
}

/** @test */
public function it_runs_migrations()
{
    $response = $this->from('/install/step/3')
        ->post('/install/step/3', [
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_database' => 'test_database',
            'db_username' => 'test_user',
            'db_password' => 'test_password',
        ]);
    
    $response->assertSessionHasNoErrors();
    
    // Verify migrations ran
    $this->assertTrue(Schema::hasTable('users'));
    $this->assertTrue(Schema::hasTable('properties'));
    $this->assertTrue(Schema::hasTable('units'));
}
```

#### Admin Creation Tests

```php
/** @test */
public function it_validates_admin_email_uniqueness()
{
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $response = $this->post('/install/step/4', [
        'name' => 'Admin',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertSessionHasErrors('email');
}

/** @test */
public function it_creates_admin_user()
{
    $response = $this->from('/install/step/4')
        ->post('/install/step/4', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    
    $response->assertSessionHasNoErrors();
    
    $this->assertDatabaseHas('users', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
    
    $user = User::where('email', 'admin@example.com')->first();
    $this->assertTrue(Hash::check('password123', $user->password));
}
```

#### Website Configuration Tests

```php
/** @test */
public function it_saves_website_settings()
{
    $response = $this->post('/install/step/5', [
        'site_name' => 'My Apartment',
        'site_tagline' => 'Quality Living',
        'email' => 'info@example.com',
        'phone' => '081234567890',
        'whatsapp' => '6281234567890',
        'address' => 'Jalan Test No 123',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#10B981',
        'accent_color' => '#F59E0B',
    ]);
    
    $response->assertSessionHasNoErrors();
    
    $this->assertEquals('My Apartment', settings('site_name'));
    $this->assertEquals('#3B82F6', settings('primary_color'));
}

/** @test */
public function it_creates_lock_file()
{
    $this->assertFalse(file_exists(storage_path('installed.lock')));
    
    $response = $this->from('/install/step/5')
        ->post('/install/step/5', [
            'site_name' => 'My Apartment',
            // ... other fields
        ]);
    
    $response->assertSessionHasNoErrors();
    
    $this->assertTrue(file_exists(storage_path('installed.lock')));
}
```

### 2. Authentication Tests

```php
/** @test */
public function it_shows_login_form()
{
    $response = $this->get('/login');
    
    $response->assertStatus(200);
    $response->assertViewIs('auth.login');
}

/** @test */
public function it_authenticates_user()
{
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
}

/** @test */
public function it_throttles_login_attempts()
{
    // Try to login 5 times with wrong credentials
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }
    
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);
    
    $response->assertSessionHasErrors('email');
    $this->assertStringContainsString('locked', $response->getSession()->get('auth.throttle'));
}

/** @test */
public function it_logs_out_user()
{
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $this->actingAs($user);
    
    $response = $this->post('/logout');
    
    $response->assertRedirect('/');
    $this->assertGuest();
}
```

### 3. Property Tests

```php
/** @test */
public function it_lists_properties()
{
    $response = $this->get('/apartments');
    
    $response->assertStatus(200);
    $response->assertViewIs('properties.index');
}

/** @test */
public function it_shows_property_detail()
{
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
        'description' => 'Test description',
        'status' => 'published',
    ]);
    
    $response = $this->get('/apartments/' . $property->slug);
    
    $response->assertStatus(200);
    $response->assertViewIs('properties.show');
    $response->assertSee('Test Property');
}

/** @test */
public function it_shows_404_for_non_existent_property()
{
    $response = $this->get('/apartments/non-existent');
    
    $response->assertStatus(404);
}

/** @test */
public function it_filters_properties_by_status()
{
    Property::create(['name' => 'Published', 'status' => 'published']);
    Property::create(['name' => 'Draft', 'status' => 'draft']);
    Property::create(['name' => 'Archived', 'status' => 'archived']);
    
    $response = $this->get('/apartments?status=published');
    
    $response->assertStatus(200);
    $response->assertSee('Published');
    $response->assertDontSee('Draft');
    $response->assertDontSee('Archived');
}
```

### 4. Unit Tests

```php
/** @test */
public function it_lists_units()
{
    $response = $this->get('/apartments/test-property');
    
    $response->assertStatus(200);
}

/** @test */
public function it_shows_unit_detail()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
        'description' => 'Luxury studio',
        'price' => 2500000,
        'price_type' => 'nightly',
        'status' => 'available',
    ]);
    
    $response = $this->get('/apartments/test-property/studio-deluxe');
    
    $response->assertStatus(200);
    $response->assertSee('Studio Deluxe');
    $response->assertSee('Rp 2.500.000');
}

/** @test */
public function it_shows_booking_form()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
        'max_guests' => 2,
    ]);
    
    $response = $this->get('/apartments/test-property/studio-deluxe');
    
    $response->assertSee('Book Studio Deluxe');
    $response->assertSee('Name');
    $response->assertSee('WhatsApp Number');
    $response->assertSee('Check-in');
    $response->assertSee('Check-out');
    $response->assertSee('Number of Guests');
}
```

### 5. Booking Tests

```php
/** @test */
public function it_validates_booking_form()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => '',
        'phone' => '',
        'check_in' => '',
        'check_out' => '',
        'guests' => '',
    ]);
    
    $response->assertSessionHasErrors(['name', 'phone', 'check_in', 'check_out', 'guests']);
}

/** @test */
public function it_creates_booking()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'email' => 'john@example.com',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'guests' => 2,
        'notes' => 'Early check-in requested',
    ]);
    
    $response->assertJsonStructure([
        'success',
        'booking_code',
        'whatsapp_url',
    ]);
    
    $this->assertDatabaseHas('bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'status' => 'new',
    ]);
}

/** @test */
public function it_generates_booking_code()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'guests' => 2,
    ]);
    
    $booking = Booking::where('name', 'John Doe')->first();
    $this->assertStringStartsWith('BK-', $booking->booking_code);
    $this->assertStringContainsString('202609', $booking->booking_code);
}

/** @test */
public function it_generates_whatsapp_message()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
        'whatsapp_number' => '6281234567890',
    ]);
    
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567891',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'guests' => 2,
    ]);
    
    $booking = Booking::where('name', 'John Doe')->first();
    
    $this->assertStringContainsString('Halo', $booking->whatsapp_url);
    $this->assertStringContainsString('Test Property', $booking->whatsapp_url);
    $this->assertStringContainsString('Studio Deluxe', $booking->whatsapp_url);
}

/** @test */
public function it_validates_dates()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    // Check-out before check-in
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'check_in' => '2026-09-05',
        'check_out' => '2026-09-01',
        'guests' => 2,
    ]);
    
    $response->assertSessionHasErrors('check_out');
    
    // Check-in in the past
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'check_in' => '2020-01-01',
        'check_out' => '2020-01-05',
        'guests' => 2,
    ]);
    
    $response->assertSessionHasErrors('check_in');
}

/** @test */
public function it_validates_guests()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
        'max_guests' => 2,
    ]);
    
    // More guests than allowed
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'guests' => 5,
    ]);
    
    $response->assertSessionHasErrors('guests');
}
```

### 6. Admin Panel Tests

```php
/** @test */
public function it_requires_authentication()
{
    $response = $this->get('/admin');
    
    $response->assertRedirect('/login');
}

/** @test */
public function admin_can_view_dashboard()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $response = $this->actingAs($admin)->get('/admin');
    
    $response->assertStatus(200);
    $response->assertViewIs('admin.dashboard');
}

/** @test */
public function admin_can_manage_properties()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
        'description' => 'Test description',
    ]);
    
    $response = $this->actingAs($admin)->get('/admin/properties');
    
    $response->assertStatus(200);
    $response->assertSee('Test Property');
}

/** @test */
public function admin_can_create_property()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $response = $this->actingAs($admin)->post('/admin/properties', [
        'name' => 'New Property',
        'slug' => 'new-property',
        'description' => 'New property description',
        'address' => 'Jalan Test No 1',
        'city' => 'Bekasi',
        'province' => 'Jawa Barat',
        'status' => 'published',
    ]);
    
    $response->assertRedirect('/admin/properties');
    $this->assertDatabaseHas('properties', [
        'name' => 'New Property',
        'slug' => 'new-property',
    ]);
}

/** @test */
public function admin_can_update_property()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $property = Property::create([
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);
    
    $response = $this->actingAs($admin)->put('/admin/properties/' . $property->id, [
        'name' => 'New Name',
        'slug' => 'new-name',
        'description' => 'Updated description',
    ]);
    
    $response->assertRedirect('/admin/properties');
    $this->assertEquals('New Name', $property->fresh()->name);
}

/** @test */
public function admin_can_delete_property()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $property = Property::create(['name' => 'To Delete']);
    
    $response = $this->actingAs($admin)->delete('/admin/properties/' . $property->id);
    
    $response->assertRedirect('/admin/properties');
    $this->assertSoftDeleted('properties', ['id' => $property->id]);
}
```

### 7. SEO Tests

```php
/** @test */
public function it_generates_meta_tags()
{
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
        'description' => 'Test description for SEO',
    ]);
    
    $response = $this->get('/apartments/test-property');
    
    $response->assertSee('<title>Test Property</title>');
    $response->assertSee('<meta name="description" content="Test description for SEO">');
    $response->assertSee('<link rel="canonical" href="https://example.com/apartments/test-property">');
}

/** @test */
public function it_generates_open_graph_tags()
{
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
    ]);
    
    $response = $this->get('/apartments/test-property');
    
    $response->assertSee('<meta property="og:title" content="Test Property">');
    $response->assertSee('<meta property="og:description" content="Test Property - Laravel Apartment CMS">');
    $response->assertSee('<meta property="og:type" content="website">');
}

/** @test */
public function it_generates_sitemap()
{
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
        'status' => 'published',
    ]);
    
    $response = $this->get('/sitemap.xml');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset');
    $response->assertSee('https://example.com/apartments/test-property');
}

/** @test */
public function it_generates_robots_txt()
{
    $response = $this->get('/robots.txt');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain');
    $response->assertSee('User-agent: *');
    $response->assertSee('Allow: /');
    $response->assertSee('Disallow: /admin');
    $response->assertSee('Sitemap: https://example.com/sitemap.xml');
}

/** @test */
public function it_generates_json_ld()
{
    $property = Property::create([
        'name' => 'Test Property',
        'slug' => 'test-property',
    ]);
    
    $response = $this->get('/apartments/test-property');
    
    $response->assertSee('<script type="application/ld+json">');
    $response->assertSee('"@context":"https://schema.org"');
    $response->assertSee('"@type":"RealEstateListing"');
}
```

### 8. Security Tests

```php
/** @test */
public function it_prevents_xss()
{
    $response = $this->get('/apartments/<script>alert("xss")</script>');
    
    $response->assertStatus(404);
}

/** @test */
public function it_requires_csrf_token()
{
    $response = $this->post('/bookings', [
        '_token' => 'invalid-token',
    ]);
    
    $response->assertStatus(419);
}

/** @test */
public function it_validates_file_uploads()
{
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $admin->assignRole('super-admin');
    
    $response = $this->actingAs($admin)->post('/admin/settings/branding', [
        'logo' => UploadedFile::fake()->create('malicious.php', 100, 'application/php'),
    ]);
    
    $response->assertSessionHasErrors('logo');
}

/** @test */
public function it_enforces_password_complexity()
{
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ]);
    
    $response->assertSessionHasErrors('password');
}

/** @test */
public function it_throttles_rate_limiting()
{
    for ($i = 0; $i < 60; $i++) {
        $this->get('/');
    }
    
    $response = $this->get('/');
    
    $response->assertStatus(429);
}
```

### 9. Integration Tests

```php
/** @test */
public function it_tracks_booking_events_in_analytics()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    $response = $this->post('/bookings', [
        'property_id' => $property->id,
        'unit_id' => $unit->id,
        'name' => 'John Doe',
        'phone' => '6281234567890',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'guests' => 2,
    ]);
    
    // Verify booking was created
    $this->assertDatabaseHas('bookings', ['name' => 'John Doe']);
    
    // Verify WhatsApp URL was generated
    $booking = Booking::where('name', 'John Doe')->first();
    $this->assertStringStartsWith('https://wa.me/', $booking->whatsapp_url);
}

/** @test */
public function it_saves_utms_on_booking()
{
    $property = Property::create(['name' => 'Test Property', 'slug' => 'test-property']);
    $unit = Unit::create([
        'property_id' => $property->id,
        'name' => 'Studio Deluxe',
        'slug' => 'studio-deluxe',
    ]);
    
    $response = $this->from('/landing-page?utm_source=google&utm_medium=cpc&utm_campaign=summer')
        ->post('/bookings', [
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'name' => 'John Doe',
            'phone' => '6281234567890',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-05',
            'guests' => 2,
        ]);
    
    $booking = Booking::where('name' => 'John Doe')->first();
    
    $this->assertEquals('google', $booking->utm_source);
    $this->assertEquals('cpc', $booking->utm_medium);
    $this->assertEquals('summer', $booking->utm_campaign);
}
```

## Test Infrastructure

### Test Database

```php
// .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Test Database Setup

```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test data
        $this->seed(AmenitiesTableSeeder::class);
    }
}
```

### Test Data Factories

```php
// database/factories/PropertyFactory.php
$factory->define(Property::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
        'slug' => $faker->slug,
        'short_description' => $faker->sentence,
        'description' => $faker->paragraphs(3, true),
        'address' => $faker->address,
        'city' => 'Bekasi',
        'province' => 'Jawa Barat',
        'postal_code' => '17111',
        'latitude' => -6.2389,
        'longitude' => 106.6629,
        'whatsapp_number' => '6281234567890',
        'status' => 'published',
        'featured' => $faker->boolean,
    ];
});

// database/factories/UnitFactory.php
$factory->define(Unit::class, function (Faker $faker) {
    return [
        'property_id' => Property::factory(),
        'name' => $faker->sentence,
        'slug' => $faker->slug,
        'type' => 'Studio',
        'short_description' => $faker->sentence,
        'description' => $faker->paragraphs(2, true),
        'bedrooms' => 0,
        'bathrooms' => 1,
        'max_guests' => 2,
        'size' => $faker->numberBetween(25, 50),
        'price' => $faker->numberBetween(1500000, 3500000),
        'price_type' => 'nightly',
        'status' => 'available',
        'featured' => $faker->boolean,
    ];
});
```

### Test Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=BookingTest

# Run specific test
php artisan test --filter=testItCreatesBooking

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Continuous Integration

### GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, gd
          coverage: xdebug
      
      - name: Install Dependencies
        run: |
          composer install -q --no-ansi --no-interaction
          npm install
          npm run build
      
      - name: Create Database
        run: |
          mkdir -p storage
          touch storage/database.sqlite
      
      - name: Run Tests
        run: php artisan test --coverage
```

## Conclusion

The testing strategy provides:

- ✅ Comprehensive test coverage
- ✅ Installer tests
- ✅ Authentication tests
- ✅ Property/Unit tests
- ✅ Booking tests
- ✅ Admin panel tests
- ✅ SEO tests
- ✅ Security tests
- ✅ Integration tests
- ✅ CI/CD integration

Testing ensures the system works correctly and prevents regressions when adding new features.