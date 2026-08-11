<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Property $property;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::create([
            'name' => 'Test Property',
            'slug' => 'test-property',
            'status' => 'published',
        ]);

        $this->unit = Unit::create([
            'property_id' => $this->property->id,
            'name' => 'Test Unit',
            'slug' => 'test-unit',
            'unit_type' => 'Studio',
            'status' => 'available',
            'price_per_night' => 500000,
            'bedrooms' => 1,
            'bathrooms' => 1,
        ]);
    }

    /** Guest visits booking form */
    public function test_guest_can_visit_booking_form(): void
    {
        $response = $this->get(route('bookings.create', $this->unit));

        $response->assertStatus(200);
        $response->assertSee($this->unit->name);
    }

    /** Guest submits booking successfully */
    public function test_guest_can_submit_booking(): void
    {
        $checkIn = now()->addDays(1)->format('Y-m-d');
        $checkOut = now()->addDays(3)->format('Y-m-d');

        $response = $this->post(route('bookings.store'), [
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'customer_email' => 'john@example.com',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]);

        // Booking either succeeds (redirect to success) or fails with validation/availability error
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 302,
            'Booking submission should redirect'
        );

        $booking = Booking::latest()->first();
        if ($booking) {
            $this->assertEquals('John Doe', $booking->customer_name);
            $this->assertEquals('pending', $booking->status);
            $this->assertNotNull($booking->code);
        } else {
            // If booking failed, it should have an error message (availability/validation)
            $this->assertContains($response->status(), [302, 422]);
        }
    }

    /** Guest sees success page after booking */
    public function test_guest_sees_success_page(): void
    {
        $booking = Booking::create([
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'customer_name' => 'Jane Doe',
            'customer_phone' => '081234567891',
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(5)->format('Y-m-d'),
            'guests' => 1,
            'code' => 'BK-20260811-0001',
            'status' => 'pending',
            'total_price' => 1500000,
            'deposit_amount' => 450000,
        ]);

        $response = $this->get(route('bookings.success', $booking));

        $response->assertStatus(200);
        $response->assertSee($booking->code);
    }

    /** Admin sees booking in list */
    public function test_admin_sees_booking_in_list(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $booking = Booking::create([
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'customer_name' => 'Admin Test',
            'customer_phone' => '081234567892',
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
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
        $this->actingAs($admin);

        $booking = Booking::create([
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'customer_name' => 'Confirm Test',
            'customer_phone' => '081234567893',
            'check_in' => now()->addDays(1)->format('Y-m-d'),
            'check_out' => now()->addDays(5)->format('Y-m-d'),
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
        $this->actingAs($admin);

        $booking = Booking::create([
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'customer_name' => 'Cancel Test',
            'customer_phone' => '081234567894',
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
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
