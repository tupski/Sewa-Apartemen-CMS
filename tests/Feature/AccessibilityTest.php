<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(User $user): User
    {
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        return $user;
    }

    public function test_admin_dashboard_has_skip_nav_link(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Skip to content', false);
    }

    public function test_admin_pages_have_main_landmark(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $routes = [
            'dashboard',
            'admin.pages.index',
            'admin.properties.index',
            'admin.bookings.index',
            'admin.users.index',
            'admin.posts.index',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('role="main"', false);
        }
    }

    public function test_admin_pages_have_h1(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('<h1', false);
    }

    public function test_admin_layout_has_lang_attribute(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('lang="', false);
    }

    public function test_sidebar_nav_has_role_navigation(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('role="navigation"', false);
    }
}
