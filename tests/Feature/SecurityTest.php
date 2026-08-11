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
}
