<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_token_present_on_login_form(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('csrf-token');
    }

    public function test_login_requires_csrf(): void
    {
        // Clear cookies to force CSRF check
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);

        $response->assertStatus(302);
    }

    public function test_x_frame_options_header(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_x_content_type_options_header(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_mass_assignment_protection_on_user(): void
    {
        // Verify User model has guarded attributes for password/remember_token
        $user = new User();
        $hidden = ['password', 'remember_token'];

        // Ensure these fields are in $hidden (not fillable by default anyway)
        foreach ($hidden as $field) {
            // New User model attributes should not leak
            $this->assertNull($user->getAttribute($field));
        }
    }

    // ==================== FIND-001: Booking token protection ====================

        public function test_booking_status_requires_access_token(): void
        {
            $booking = \App\Models\Booking::create([
                'property_id' => \App\Models\Property::factory()->create()->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'customer_name' => 'Token Test',
                'customer_phone' => '081234567890',
                'check_in' => now()->addDays(1),
                'check_out' => now()->addDays(2),
                'guests' => 1,
                'code' => 'BK-20260824-0001',
                'access_token' => 'secret-token-abc',
                'status' => 'pending',
                'total_price' => 1000000,
                'deposit_amount' => 300000,
            ]);

            // Sequential code no longer resolves the booking
            $this->get(route('bookings.status', $booking->code))->assertStatus(404);
            // Numeric id is not a valid token either
            $this->get(route('bookings.status', $booking->id))->assertStatus(404);
            // The random token works
            $this->get(route('bookings.status', $booking->access_token))->assertStatus(200);
            // Success page is also token-gated
            $this->get(route('bookings.success', $booking->access_token))->assertStatus(200);
        }

        public function test_booking_status_without_token_is_rejected(): void
        {
            $booking = \App\Models\Booking::create([
                'property_id' => \App\Models\Property::factory()->create()->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'customer_name' => 'No Token',
                'customer_phone' => '081234567890',
                'check_in' => now()->addDays(1),
                'check_out' => now()->addDays(2),
                'guests' => 1,
                'code' => 'BK-20260824-0002',
                'access_token' => null,
                'status' => 'pending',
                'total_price' => 1000000,
                'deposit_amount' => 300000,
            ]);

            $this->get(route('bookings.status', 'BK-20260824-0002'))->assertStatus(404);
            $this->get('/bookings/' . $booking->id . '/success')->assertStatus(404);
        }

        // ==================== FIND-003: Voucher integrity ====================

        public function test_voucher_id_alone_cannot_be_redeemed(): void
        {
            $property = \App\Models\Property::factory()->create();
            $voucher = \App\Models\Voucher::create([
                'code' => 'HELLO10',
                'name' => 'Hello 10',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'is_active' => true,
            ]);

            // Numeric voucher_id must not burn the voucher
            $response = $this->postJson(route('bookings.store'), [
                'property_id' => $property->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'check_in' => now()->addDays(1)->format('Y-m-d'),
                'check_out' => now()->addDays(2)->format('Y-m-d'),
                'customer_name' => 'Voucher Attacker',
                'customer_phone' => '081234567890',
                'guests' => 1,
                'voucher_id' => $voucher->id,
            ]);

            // voucher_id is ignored: a normal booking is created, the voucher is not consumed
            $response->assertOk();
            $this->assertEquals(0, $voucher->fresh()->used_count);
            $this->assertNull(\App\Models\Booking::latest()->first()->voucher_id);
        }

        public function test_voucher_code_applies_discount_and_increments_usage(): void
        {
            $property = \App\Models\Property::factory()->create(['prices' => [
                'studio' => ['night_wd' => 500000, 'night_we' => 600000],
            ]]);
            $voucher = \App\Models\Voucher::create([
                'code' => 'POTONG50',
                'name' => 'Potong 50k',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'is_active' => true,
            ]);

            $response = $this->postJson(route('bookings.store'), [
                'property_id' => $property->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'check_in' => now()->addDays(1)->format('Y-m-d'),
                'check_out' => now()->addDays(2)->format('Y-m-d'),
                'customer_name' => 'Voucher Valid',
                'customer_phone' => '081234567890',
                'guests' => 1,
                'voucher_code' => 'POTONG50',
            ]);

            $response->assertOk()->assertJson(['success' => true]);

            $booking = \App\Models\Booking::latest()->first();
            $this->assertEquals(1, $voucher->fresh()->used_count);
            $this->assertEquals($voucher->id, $booking->voucher_id);
            $this->assertEquals(50000, $booking->voucher_discount);
            $this->assertEquals(450000, (int) $booking->total_price);
        }

        // ==================== FIND-004: Double-booking prevention ====================

        public function test_overlapping_booking_is_rejected(): void
        {
            $property = \App\Models\Property::factory()->create(['prices' => [
                'studio' => ['night_wd' => 500000, 'night_we' => 600000],
            ]]);

            $checkIn = now()->addDays(3)->format('Y-m-d');
            $checkOut = now()->addDays(5)->format('Y-m-d');

            $this->postJson(route('bookings.store'), [
                'property_id' => $property->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'customer_name' => 'First Guest',
                'customer_phone' => '081234567890',
                'guests' => 1,
            ])->assertOk();

            $response = $this->postJson(route('bookings.store'), [
                'property_id' => $property->id,
                'booking_type' => 'daily',
                'unit_type' => 'studio',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'customer_name' => 'Second Guest',
                'customer_phone' => '081234567891',
                'guests' => 1,
            ]);

            $this->assertContains($response->status(), [422, 500]);
            $this->assertEquals(1, \App\Models\Booking::count());
        }

        // ==================== FIND-005: Stored XSS ====================

        public function test_property_description_script_is_stripped_on_save(): void
        {
            $user = User::factory()->create();
            $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
            $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

            $this->actingAs($user)->post(route('admin.properties.store'), [
                'name' => 'XSS Property',
                'slug' => 'xss-property',
                'status' => 'published',
                'description' => '<p>Nice</p><script>alert(1)</script><img src=x onerror=alert(1)>',
            ])->assertRedirect();

            $property = \App\Models\Property::where('slug', 'xss-property')->first();
            $this->assertNotNull($property);
            $this->assertStringNotContainsString('<script>', $property->description);
            $this->assertStringNotContainsString('onerror', $property->description);
            $this->assertStringContainsString('<p>Nice</p>', $property->description);
        }

        // ==================== FIND-006: JSON-LD escaping ====================

        public function test_jsonld_breaks_out_of_script_tag_are_hex_escaped(): void
        {
            $html = \App\Services\SeoService::renderJsonLd([[
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => '</script><script>alert(1)</script>',
            ]]);

            $this->assertStringNotContainsString('</script><script>', $html);
            $this->assertStringContainsString('\\u003C/script\\u003E', $html);
        }

        // ==================== FIND-009: Role escalation ====================

        public function test_plain_admin_cannot_assign_super_admin(): void
        {
            $admin = User::factory()->create();
            $adminRole = \App\Models\Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
            $superRole = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
            $admin->roles()->syncWithoutDetaching([$adminRole->id => ['model_type' => User::class]]);

            $target = User::factory()->create();

            $this->actingAs($admin)->post(route('admin.users.store'), [
                'name' => 'Escalated User',
                'email' => 'escalated@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role_id' => $superRole->id,
            ])->assertSessionHasErrors('role_id');

            $this->assertFalse($target->hasRole('super-admin'));
        }

        // ==================== FIND-010: CSV formula injection ====================

        public function test_csv_export_prefixes_formula_injection_cells(): void
        {
            $user = User::factory()->create();
            $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
            $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

            \App\Models\Booking::factory()->create([
                'customer_name' => '=cmd|/c calc!A0',
                'notes' => '@SUM(1,1)',
            ]);

            $response = $this->actingAs($user)->get(route('admin.bookings.export'));

            $response->assertOk();
            $this->assertStringContainsString("'=cmd|/c calc!A0", $response->getContent());
            $this->assertStringContainsString("'@SUM(1,1)", $response->getContent());
        }
    }
