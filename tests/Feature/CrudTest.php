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
        $this->actingAs($this->user);
    }

    public function test_admin_properties_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.properties.index'))->assertStatus(200);
    }

    public function test_admin_units_index_returns_200(): void
    {
        $this->authenticate();
        $this->get(route('admin.units.index'))->assertStatus(200);
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

    public function test_guest_cannot_access_units_index(): void
    {
        $this->get(route('admin.units.index'))->assertRedirect(route('login'));
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
