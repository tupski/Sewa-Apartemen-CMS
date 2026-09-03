<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin header profile chip + dropdown, and the read-only /profile page.
 */
class AdminProfileHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createAdminUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        return $user;
    }

    public function test_header_shows_initials_when_no_avatar_is_uploaded(): void
    {
        $user = $this->createAdminUser(['name' => 'Lya Rooms', 'avatar' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="profile-menu-trigger"', false);
        $response->assertSee('>LR</span>', false);
    }

    public function test_header_shows_the_photo_when_an_avatar_exists(): void
    {
        $user = $this->createAdminUser(['name' => 'Lya Rooms', 'avatar' => 'avatars/lya.jpg']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('avatars/lya.jpg', false);
        $response->assertDontSee('>LR</span>', false);
    }

    public function test_dropdown_contains_name_role_profile_logout_and_utility_icons(): void
    {
        $user = $this->createAdminUser(['name' => 'Lya Rooms']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Lya Rooms');
        $response->assertSee('Super Admin');
        $response->assertSee(route('profile.edit'), false);
        $response->assertSee(route('logout'), false);
        $response->assertSee('data-testid="clear-cache-button"', false);
        $response->assertSee('data-testid="dark-mode-toggle"', false);
    }

    public function test_currency_switcher_is_gone_from_the_header(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('admin.set-currency'), false);
    }

    public function test_profile_page_shows_details_and_links_to_the_user_edit_screen(): void
    {
        $user = $this->createAdminUser(['name' => 'Lya Rooms', 'phone' => '08123456789']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Lya Rooms');
        $response->assertSee($user->email);
        $response->assertSee('08123456789');
        $response->assertSee('Super Admin');
        $response->assertSee(route('admin.users.edit', $user), false);
    }

    public function test_profile_page_no_longer_renders_the_inline_profile_information_form(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        // The read-only page must not embed the name/email edit form anymore.
        // NOTE: asserting on the action URL would be a false positive — the
        // delete-account form posts to the same `/profile` path.
        $response->assertDontSee('Profile Information');
        $response->assertDontSee("Update your account's profile information and email address.", false);
        $response->assertDontSee('@method(\'patch\')', false);
    }

    public function test_non_admin_user_sees_the_profile_page_without_an_edit_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertDontSee('data-testid="profile-edit-link"', false);
    }

    public function test_initials_helper_handles_single_word_and_empty_names(): void
    {
        $single = User::factory()->make(['name' => 'Lya']);
        $this->assertSame('L', $single->initials());

        $threeWords = User::factory()->make(['name' => 'Ayu Putri Wardani']);
        $this->assertSame('AW', $threeWords->initials());

        $blank = User::factory()->make(['name' => '', 'email' => 'zed@example.com']);
        $this->assertSame('Z', $blank->initials());
    }
}
