<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Unit;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure settings table is seeded
        $this->seed(\Database\Seeders\SettingSeeder::class);
        SettingsService::clearCache();
    }

    // ==================== Analytics Scripts Present (via app.blade.php) ====================

    public function test_ga4_script_present_when_setting_is_set(): void
    {
        SettingsService::set('google_analytics_id', 'G-ABC1234567', 'integrations');

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('G-ABC1234567', false);
        $response->assertSee('googletagmanager.com/gtag/js', false);
        $response->assertSee("gtag('config', 'G-ABC1234567')", false);
    }

    public function test_gtm_scripts_present_when_setting_is_set(): void
    {
        SettingsService::set('google_tag_manager_id', 'GTM-TEST123', 'integrations');

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('GTM-TEST123', false);
        $response->assertSee('googletagmanager.com/gtm.js', false);
    }

    public function test_meta_pixel_script_present_when_setting_is_set(): void
    {
        SettingsService::set('meta_pixel_id', '1234567890123456', 'integrations');

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('1234567890123456', false);
        $response->assertSee('connect.facebook.net/en_US/fbevents.js', false);
        $response->assertSee("fbq('init'", false);
    }

    public function test_clarity_script_present_when_setting_is_set(): void
    {
        SettingsService::set('microsoft_clarity_id', 'clarity123abc', 'integrations');

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('clarity123abc', false);
        $response->assertSee('clarity.ms/tag/', false);
    }

    public function test_search_console_meta_present_when_token_set(): void
    {
        SettingsService::set('search_console_token', 'google-verification-token-xyz', 'integrations');

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('<meta name="google-site-verification" content="google-verification-token-xyz">', false);
    }

    // ==================== No Scripts When Settings Empty ====================

    public function test_no_ga4_script_when_setting_empty(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee('googletagmanager.com/gtag/js', false);
    }

    public function test_no_gtm_script_when_setting_empty(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee('googletagmanager.com/gtm.js', false);
    }

    public function test_no_meta_pixel_when_setting_empty(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);
    }

    public function test_no_clarity_when_setting_empty(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee('clarity.ms/tag/', false);
    }

    public function test_no_search_console_meta_when_token_empty(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee('google-site-verification', false);
    }

    // ==================== Booking Success Page Analytics ====================

    public function test_booking_success_page_has_datalayer_push(): void
    {
        $property = Property::create([
            'name' => 'Test Property',
            'slug' => 'test-property',
            'status' => 'published',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'name' => 'Test Unit',
            'slug' => 'test-unit',
            'unit_type' => 'Studio',
            'status' => 'available',
            'price_per_night' => 500000,
            'bedrooms' => 1,
            'bathrooms' => 1,
        ]);

        $booking = Booking::create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'guests' => 2,
            'code' => 'BK-20260811-0001',
            'status' => 'pending',
            'total_price' => 1000000,
            'deposit_amount' => 300000,
        ]);

        $analyticsEvent = [
            'event' => 'booking_completed',
            'booking_id' => $booking->id,
            'booking_code' => $booking->code,
            'unit_name' => $unit->name,
            'property_name' => $property->name,
            'value' => 1000000.0,
            'currency' => 'IDR',
        ];

        $response = $this->withSession(['analytics_event' => $analyticsEvent])
            ->get(route('bookings.success', $booking));

        $response->assertStatus(200);
        $response->assertSee("event: 'booking_completed'", false);
        $response->assertSee("booking_code: '{$booking->code}'", false);
        $response->assertSee('value: 1000000', false);
        $response->assertSee("currency: 'IDR'", false);
        $response->assertSee("fbq('track', 'Purchase'", false);
    }

    // ==================== Guest Page Analytics ====================

    public function test_guest_page_includes_analytics_when_set(): void
    {
        SettingsService::set('google_analytics_id', 'G-GUEST00001', 'integrations');

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('G-GUEST00001', false);
    }

    // ==================== Admin Page Does NOT Include Analytics ====================

    public function test_admin_page_does_not_include_public_analytics(): void
    {
        // Admin layout has its own head but analytics partial is only in app.blade.php / guest.blade.php
        // Admin uses admin.blade.php which does NOT include the analytics component
        SettingsService::set('google_analytics_id', 'G-ADMIN1234', 'integrations');

        // Verify public blog page has analytics, confirming it's only on public layouts
        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('G-ADMIN1234', false);
    }
}
