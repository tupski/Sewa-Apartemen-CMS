<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Post;
use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $role = Role::where('slug', 'super-admin')->first();
        if ($role) {
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        }

        return $user;
    }

    // ==================== Dashboard ====================

    public function test_admin_can_visit_dashboard_and_see_stats(): void
    {
        $user = $this->createAdminUser();

        Post::factory()->published()->count(2)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalProperties');
        $response->assertViewHas('totalUnits');
        $response->assertViewHas('totalBookings');
        $response->assertViewHas('totalUsers');
        $response->assertViewHas('totalPosts');
        $response->assertViewHas('occupancyRate');
        $response->assertViewHas('recentBookings');
        $response->assertViewHas('recentProperties');
        $response->assertViewHas('bookingChartLabels');
        $response->assertViewHas('bookingChartValues');
    }

    // ==================== User Management ====================

    public function test_user_index_lists_users(): void
    {
        $user = $this->createAdminUser();
        User::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewHas('users');
    }

    public function test_user_crud_works(): void
    {
        $admin = $this->createAdminUser();

        // Create
        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        $createdUser = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($createdUser);

        // Edit - show update form
        $editResponse = $this->actingAs($admin)->get(route('admin.users.edit', $createdUser));
        $editResponse->assertOk();

        // Update
        $updateResponse = $this->actingAs($admin)->put(route('admin.users.update', $createdUser), [
            'name' => 'Updated User',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['name' => 'Updated User', 'email' => 'test@example.com']);

        // Delete
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $createdUser));
        $deleteResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    // ==================== Booking Management ====================

    public function test_booking_filter_works(): void
    {
        $user = $this->createAdminUser();

        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        Booking::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'status' => 'pending',
            'customer_name' => 'Jane Doe',
        ]);
        Booking::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'status' => 'confirmed',
            'customer_name' => 'John Smith',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.bookings.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertSee('Jane Doe');
        $response->assertDontSee('John Smith');
    }

    public function test_booking_status_update_works(): void
    {
        $user = $this->createAdminUser();

        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $booking = Booking::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'status' => 'pending',
        ]);

        // Confirm
        $response = $this->actingAs($user)
            ->patch(route('admin.bookings.confirm', $booking));

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);

        // Complete
        $response = $this->actingAs($user)
            ->patch(route('admin.bookings.complete', $booking));

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'completed']);
    }

    public function test_booking_csv_export_returns_csv_content(): void
    {
        $user = $this->createAdminUser();

        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        Booking::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'code' => 'BK-20260811-0001',
            'customer_name' => 'Export Test',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.bookings.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('Export Test', $response->getContent());
    }
}
