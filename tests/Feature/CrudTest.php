<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function authenticate(): void
    {
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => \App\Models\User::class]]);

        $this->actingAs($this->user);
    }

    public function test_non_admin_user_cannot_access_admin(): void
    {
        // Regular user WITHOUT the super-admin role
        $this->actingAs($this->user);
        $this->get(route('admin.properties.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_admin_properties_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.properties.index'))->assertStatus(200);
    }

    public function test_property_gallery_media_linking_works(): void
    {
        $this->authenticate();

        $media = \App\Models\Media::create([
            'user_id' => $this->user->id,
            'disk' => 'public',
            'directory' => 'properties/1/lobby',
            'filename' => 'lobby-1.jpg',
            'original_filename' => 'lobby-1.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'type' => 'image',
        ]);

        $response = $this->post(route('admin.properties.store'), [
            'name' => 'Gallery Property',
            'slug' => 'gallery-property',
            'status' => 'published',
            'photo_categories' => json_encode(['Lobby', 'Bedroom']),
            'gallery_media' => [0 => [$media->id]],
        ]);

        $response->assertRedirect(route('admin.properties.index'));

        $property = \App\Models\Property::where('slug', 'gallery-property')->firstOrFail();
        $this->assertEquals(['Lobby', 'Bedroom'], $property->photoCategories());

        $photo = $property->photos()->first();
        $this->assertNotNull($photo);
        $this->assertEquals('Lobby', $photo->category);
        $this->assertEquals($media->id, $photo->media_id);
    }

    public function test_admin_amenities_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.amenities.index'))->assertStatus(200);
    }

    public function test_admin_blocks_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.blocks.index'))->assertStatus(200);
    }

    public function test_admin_pages_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.pages.index'))->assertStatus(200);
    }

    public function test_admin_navigations_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.navigations.index'))->assertStatus(200);
    }

    public function test_admin_media_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.media.index'))->assertStatus(200);
    }

    public function test_admin_settings_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.settings.index'))->assertStatus(200);
    }

    public function test_admin_redirects_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.redirects.index'))->assertStatus(200);
    }

    public function test_admin_bookings_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.bookings.index'))->assertStatus(200);
    }

    public function test_admin_users_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.users.index'))->assertStatus(200);
    }

    public function test_admin_posts_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.posts.index'))->assertStatus(200);
    }

    public function test_admin_categories_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.categories.index'))->assertStatus(200);
    }

    public function test_admin_tags_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.tags.index'))->assertStatus(200);
    }

    // Guest access denied tests

    public function test_guest_cannot_access_properties_index(): void
    {
        $this->get(route('admin.properties.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_amenities_index(): void
    {
        $this->get(route('admin.amenities.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_blocks_index(): void
    {
        $this->get(route('admin.blocks.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_pages_index(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_navigations_index(): void
    {
        $this->get(route('admin.navigations.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_media_index(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_settings_index(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_redirects_index(): void
    {
        $this->get(route('admin.redirects.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_bookings_index(): void
    {
        $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_users_index(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_posts_index(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_categories_index(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_tags_index(): void
    {
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
    }
}
