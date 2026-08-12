<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::create([
            'name' => 'Test Property',
            'slug' => 'test-property',
            'status' => 'published',
            'unit_types' => ['studio', '1br'],
            'weekend_days' => [6, 0],
            'prices' => [
                'studio' => ['night_wd' => 500000, 'night_we' => 600000, 't3_wd' => 150000, 't3_we' => 180000, 'weekly' => 3000000, 'monthly' => 9000000],
                '1br' => ['night_wd' => 700000, 'night_we' => 800000, 't3_wd' => 200000, 't3_we' => 240000, 't6_wd' => 300000, 't6_we' => 360000, 'weekly' => 4200000, 'monthly' => 12600000],
            ],
        ]);
    }

    /** Guest sees the booking widget on the property page */
    public function test_guest_sees_booking_widget_on_property_page(): void
    {
        $response = $this->get(route('properties.public.show', $this->property->slug));

        $response->assertStatus(200);
        $response->assertSee($this->property->name);
        $response->assertSee('studio', false);
    }

    /** Guest submits a daily booking via JSON */
    public function test_guest_can_submit_daily_booking(): void
    {
        $checkIn = now()->addDays(1)->format('Y-m-d');
        $checkOut = now()->addDays(3)->format('Y-m-d');

        $response = $this->postJson(route('bookings.store'), [
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'customer_email' => 'john@example.com',
            'guests' => 2,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $booking = Booking::latest()->first();
        $this->assertEquals('John Doe', $booking->customer_name);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals('studio', $booking->unit_type);
        $this->assertEquals('daily', $booking->booking_type);
        $this->assertEquals(2, $booking->metadata['nights']);
        $this->assertNotNull($booking->code);
    }

    /** Guest submits a transit booking */
    public function test_guest_can_submit_transit_booking(): void
    {
        $response = $this->postJson(route('bookings.store'), [
            'property_id' => $this->property->id,
            'booking_type' => 'transit',
            'unit_type' => '1br',
            'duration_hours' => 6,
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'customer_name' => 'Jane Doe',
            'customer_phone' => '081234567891',
            'guests' => 1,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $booking = Booking::latest()->first();
        $this->assertEquals('transit', $booking->booking_type);
        $this->assertEquals(6, $booking->duration_hours);
    }

    /** Invalid room type is rejected */
    public function test_invalid_room_type_is_rejected(): void
    {
        $response = $this->postJson(route('bookings.store'), [
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'penthouse',
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(2)->format('Y-m-d'),
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'guests' => 1,
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    /** Guest sees success page after booking */
    public function test_guest_sees_success_page(): void
    {
        $booking = Booking::create([
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'customer_name' => 'Jane Doe',
            'customer_phone' => '081234567891',
            'check_in' => now()->addDays(2),
            'check_out' => now()->addDays(5),
            'guests' => 1,
            'code' => 'BK-20260811-0001',
            'status' => 'pending',
            'total_price' => 1500000,
            'deposit_amount' => 450000,
            'metadata' => ['nights' => 3],
        ]);

        $response = $this->get(route('bookings.success', $booking));

        $response->assertStatus(200);
        $response->assertSee($booking->code);
    }

    /** Admin sees booking in list */
    public function test_admin_sees_booking_in_list(): void
    {
        $admin = User::factory()->create();
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($admin);

        $booking = Booking::create([
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'customer_name' => 'Admin Test',
            'customer_phone' => '081234567892',
            'check_in' => now()->addDays(1),
            'check_out' => now()->addDays(4),
            'guests' => 2,
            'code' => 'BK-20260811-0002',
            'status' => 'pending',
            'total_price' => 1500000,
            'deposit_amount' => 450000,
        ]);

        $response = $this->get(route('admin.bookings.index'));

        $response->assertStatus(200);
        $response->assertSee($booking->code);
        $response->assertSee($booking->customer_name);
    }

    /** Admin confirms booking */
    public function test_admin_can_confirm_booking(): void
    {
        $admin = User::factory()->create();
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($admin);

        $booking = Booking::create([
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'customer_name' => 'Confirm Test',
            'customer_phone' => '081234567893',
            'check_in' => now()->addDays(1),
            'check_out' => now()->addDays(5),
            'guests' => 3,
            'code' => 'BK-20260811-0003',
            'status' => 'pending',
            'total_price' => 2000000,
            'deposit_amount' => 600000,
        ]);

        $response = $this->patch(route('admin.bookings.confirm', $booking));

        $response->assertRedirect();
        $this->assertEquals('confirmed', $booking->fresh()->status);
    }

    /** Admin cancels booking */
    public function test_admin_can_cancel_booking(): void
    {
        $admin = User::factory()->create();
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($admin);

        $booking = Booking::create([
            'property_id' => $this->property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'customer_name' => 'Cancel Test',
            'customer_phone' => '081234567894',
            'check_in' => now()->addDays(2),
            'check_out' => now()->addDays(4),
            'guests' => 1,
            'code' => 'BK-20260811-0004',
            'status' => 'pending',
            'total_price' => 1000000,
            'deposit_amount' => 300000,
        ]);

        $response = $this->patch(route('admin.bookings.cancel', $booking));

        $response->assertRedirect();
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    /** Guest cannot access admin booking list */
    public function test_guest_cannot_access_admin_booking_list(): void
    {
        $response = $this->get(route('admin.bookings.index'));

        $response->assertRedirect(route('login'));
    }
}
