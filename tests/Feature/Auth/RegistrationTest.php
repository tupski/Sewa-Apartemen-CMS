<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Public registration is deliberately DISABLED.
 *
 * This is a single-tenant CMS: accounts exist only to grant staff access to
 * the admin panel, and they are created by an admin (admin.users.store, behind
 * the `admin` middleware). A public /register route would let anyone create an
 * account and reach the CMS, so these tests pin the absence.
 *
 * The old RegistrationTest asserted the OPPOSITE (that anyone could register
 * and land on the dashboard) — that is why the hole existed unnoticed.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_register_route_does_not_exist(): void
    {
        $this->assertFalse(
            Route::has('register'),
            'A public register route exists — anyone could create a CMS account.'
        );

        // A name-less route bound to the same path would slip past the name
        // check, so assert on the URI too.
        $boundToRegister = collect(Route::getRoutes())
            ->contains(fn ($route) => $route->uri() === 'register');

        $this->assertFalse($boundToRegister, 'A route is still bound to /register.');
    }

    public function test_the_register_page_is_not_reachable(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_posting_to_register_cannot_create_an_account(): void
    {
        // The catch-all GET /{slug} route owns this path, so a POST is refused
        // with 405 (method not allowed) rather than 404. Either way no account
        // may be created — that is the property under test.
        $response = $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertContains(
            $response->getStatusCode(),
            [404, 405],
            'POST /register was accepted — public registration may be reachable again.'
        );

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
        $this->assertGuest();
    }

    public function test_the_register_view_is_deleted(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('views/auth/register.blade.php'),
            'The registration view still exists.'
        );
    }

    /**
     * The admin path is the ONLY way to create a user — keep it working.
     */
    public function test_an_admin_can_still_create_a_user(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->syncWithoutDetaching([
            Role::updateOrCreate(
                ['slug' => 'super-admin'],
                ['name' => 'Super Admin']
            )->id => ['model_type' => User::class],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Staff',
                'email' => 'staff@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com']);
    }

    public function test_a_guest_cannot_create_a_user_through_the_admin_route(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Intruder',
            'email' => 'intruder2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'intruder2@example.com']);
    }
}
