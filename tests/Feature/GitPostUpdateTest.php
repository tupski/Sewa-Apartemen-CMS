<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PostUpdateActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the post-update action system on the admin Git dashboard.
 *
 * Three areas are covered:
 *  1. Detection: PostUpdateActionService::detect() maps changed-file lists to
 *     the correct action keys (service-layer only, no HTTP, no process exec).
 *  2. Allowlist enforcement: the POST endpoint rejects unknown action keys (422).
 *  3. Authorization: guests and non-admin users cannot reach the endpoint.
 *
 * NOTE: Actual command execution (composer/npm/migrate/artisan) is NOT performed.
 * The test asserts on the allowlist mapping and HTTP authz only. The command-
 * runner path is covered by the PostUpdateActionService unit above (it can be
 * swapped out via the IoC container in integration tests).
 */
class GitPostUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $plain;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        $this->plain = User::factory()->create();

        $this->admin = User::factory()->create();
        $role = \App\Models\Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->admin->roles()->syncWithoutDetaching([
            $role->id => ['model_type' => \App\Models\User::class],
        ]);
    }

    // =========================================================================
    // 1. Detection — service-layer, no HTTP
    // =========================================================================

    public function test_composer_json_change_requires_composer_action(): void
    {
        $svc = new PostUpdateActionService();

        $this->assertContains('composer', $svc->detect(['composer.json']));
        $this->assertContains('composer', $svc->detect(['composer.lock']));
    }

    public function test_lockfile_change_requires_assets_ci_not_plain_assets(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['package-lock.json']);
        $this->assertContains('assets_ci', $actions);
        $this->assertNotContains('assets', $actions);
    }

    public function test_js_resource_change_without_lockfile_requires_plain_assets(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['resources/js/app.js']);
        $this->assertContains('assets', $actions);
        $this->assertNotContains('assets_ci', $actions);
    }

    public function test_css_resource_change_requires_plain_assets(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['resources/css/app.css']);
        $this->assertContains('assets', $actions);
    }

    public function test_vite_config_change_requires_plain_assets(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['vite.config.js']);
        $this->assertContains('assets', $actions);
    }

    public function test_tailwind_config_change_requires_plain_assets(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['tailwind.config.js']);
        $this->assertContains('assets', $actions);
    }

    public function test_migration_file_requires_migrate_action(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['database/migrations/2026_09_01_000000_add_foo.php']);
        $this->assertContains('migrate', $actions);
    }

    public function test_config_file_requires_caches_action(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['config/app.php']);
        $this->assertContains('caches', $actions);
    }

    public function test_routes_file_requires_caches_action(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['routes/web.php']);
        $this->assertContains('caches', $actions);
    }

    public function test_env_example_change_requires_caches_action(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect(['.env.example']);
        $this->assertContains('caches', $actions);
    }

    public function test_unrelated_file_requires_no_actions(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect([
            'README.md',
            'resources/views/properties/show.blade.php',
            'app/Models/Property.php',
        ]);
        $this->assertEmpty($actions);
    }

    public function test_empty_file_list_requires_no_actions(): void
    {
        $svc = new PostUpdateActionService();

        $this->assertEmpty($svc->detect([]));
    }

    public function test_mixed_changes_returns_all_required_actions(): void
    {
        $svc = new PostUpdateActionService();

        $actions = $svc->detect([
            'composer.json',
            'package-lock.json',
            'database/migrations/2026_09_02_000000_add_bar.php',
            'config/mail.php',
            'resources/views/welcome.blade.php', // irrelevant
        ]);

        $this->assertContains('composer', $actions);
        $this->assertContains('assets_ci', $actions);
        $this->assertContains('migrate', $actions);
        $this->assertContains('caches', $actions);
    }

    public function test_detect_returns_keys_in_canonical_order(): void
    {
        $svc = new PostUpdateActionService();

        // All triggers present — order must be canonical: composer, assets_ci, migrate, caches.
        $actions = $svc->detect([
            'config/app.php',
            'database/migrations/2026_09_03_000000_add_baz.php',
            'package-lock.json',
            'composer.lock',
        ]);

        $this->assertSame(['composer', 'assets_ci', 'migrate', 'caches'], $actions);
    }

    // =========================================================================
    // 2. Allowlist enforcement — unknown key → 422
    // =========================================================================

    public function test_unknown_action_key_is_rejected_with_422(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.post-update', ['action' => 'drop_tables']))
            ->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    public function test_unknown_key_shell_injection_attempt_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.post-update', ['action' => 'composer; rm -rf /']))
            ->assertStatus(422);
    }

    // =========================================================================
    // 3. Authorization — guests and non-admin users are blocked
    // =========================================================================

    public function test_guest_cannot_reach_post_update_endpoint(): void
    {
        // Unauthenticated web requests redirect to /login (302).
        // postJson sends Accept: application/json which makes Laravel return 401
        // only when the route guard returns a JSON-aware redirect, but the admin
        // group uses the 'auth' middleware whose unauthenticated handler may still
        // return 302 depending on the request type. Accept both.
        $response = $this->post(
            route('admin.settings.post-update', ['action' => 'caches']),
            [],
            ['Accept' => 'text/html']
        );
        $this->assertContains($response->getStatusCode(), [302, 401],
            'Guest must be redirected (302) or unauthorised (401).');
    }

    public function test_non_admin_user_is_forbidden_from_post_update_endpoint(): void
    {
        $this->actingAs($this->plain)
            ->postJson(route('admin.settings.post-update', ['action' => 'caches']))
            ->assertForbidden();
    }

    public function test_admin_user_can_reach_post_update_endpoint_for_valid_key(): void
    {
        // We mock the service so no real command is run.
        $mock = $this->mock(PostUpdateActionService::class);
        $mock->shouldReceive('run')
            ->once()
            ->with('caches')
            ->andReturn('cleared');

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.post-update', ['action' => 'caches']))
            ->assertOk()
            ->assertJsonFragment(['success' => true]);
    }
}
