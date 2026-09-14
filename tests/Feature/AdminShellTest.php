<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ],
            ['model_type' => User::class]
        );

        return $user;
    }

    public function test_shell_demo_requires_authentication(): void
    {
        $this->get(route('admin.shell.demo'))->assertRedirect(route('login'));
    }

    public function test_shell_demo_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.shell.demo'))->assertForbidden();
    }

    public function test_shell_renders_navigation_for_admin(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.shell.demo'));

        $response->assertOk();

        // Every real admin destination is reachable from the shell
        foreach (['admin.pages.index', 'admin.blocks.index', 'admin.media.index', 'admin.properties.index', 'admin.posts.index', 'admin.bookings.index', 'admin.vouchers.index', 'admin.settings.index'] as $route) {
            $response->assertSee(route($route), false);
        }

        // Shell furniture
        $response->assertSee('data-flux-sidebar', false);
        $response->assertSee('data-testid="profile-menu-trigger"', false);
        $response->assertSee(route('logout'), false);
        $response->assertSee('assets/admin.css', false);
    }

    public function test_shell_marks_active_navigation(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.shell.demo'));

        $response->assertOk();

        // Livewire renders `current` on the item whose route matches the request
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/data-current|current="true"|aria-current="page"/', $html);
    }

    public function test_shell_renders_breadcrumbs(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.shell.demo'));

        $response->assertOk();
        $response->assertSee('data-flux-breadcrumbs', false);
        $response->assertSee(__('admin.dashboard'), false);
        $response->assertSee(__('admin.shell_demo'), false);
    }

    public function test_shell_renders_flash_messages(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Saved successfully'])
            ->get(route('admin.shell.demo'));

        $response->assertOk();
        $response->assertSee('Saved successfully', false);
        $response->assertSee('data-flux-callout', false);
    }

    public function test_shell_respects_admin_dark_mode_setting(): void
    {
        $user = $this->createAdminUser();

        // Via service (bukan Eloquent langsung) agar static cache ikut tervalidasi
        SettingsService::set('enable_dark_mode', true);

        $response = $this->actingAs($user)->get(route('admin.shell.demo'));
        $response->assertOk();
        $response->assertSee('var enableDark = true', false);
    }

    public function test_public_homepage_receives_no_livewire_payload(): void
    {
        Livewire::flushState();

        $response = $this->get('/');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('build/assets/app-', $html);
        $this->assertStringNotContainsString('livewire.js', $html);
        $this->assertStringNotContainsString('Livewire Styles', $html);
    }
}
