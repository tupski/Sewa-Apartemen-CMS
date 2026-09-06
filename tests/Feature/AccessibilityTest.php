<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(User $user): User
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
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

    public function test_validation_errors_are_described_by_text_inputs(): void
    {
        $response = $this->followingRedirects()->from(route('login'))->post(route('login'), [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response->assertStatus(200);
        $response->assertSee('id="email-error"', false);
        $response->assertSee('aria-describedby="email-error"', false);
    }

    public function test_property_photo_alt_text_preserves_media_alt_and_numbers_fallbacks(): void
    {
        $property = Property::factory()->create([
            'name' => 'Accessibility Residence',
            'slug' => 'accessibility-residence',
        ]);

        $describedMedia = Media::create([
            'disk' => 'public',
            'directory' => 'properties/accessibility',
            'filename' => 'living-room.jpg',
            'original_filename' => 'living-room.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'type' => 'image',
            'alt' => 'Living room with city view',
        ]);
        $fallbackMedia = Media::create([
            'disk' => 'public',
            'directory' => 'properties/accessibility',
            'filename' => 'bedroom.jpg',
            'original_filename' => 'bedroom.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'type' => 'image',
        ]);

        PropertyPhoto::create([
            'property_id' => $property->id,
            'media_id' => $describedMedia->id,
            'category' => 'Living Room',
            'sort_order' => 1,
        ]);
        PropertyPhoto::create([
            'property_id' => $property->id,
            'media_id' => $fallbackMedia->id,
            'category' => 'Bedroom',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('alt="Living room with city view"', false);
        $response->assertSee('alt="Accessibility Residence — foto 2"', false);
    }
}
