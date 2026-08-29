<?php

namespace Tests\Feature;

use App\Console\Commands\CheckForGitUpdates;
use App\Models\Role;
use App\Models\User;
use App\Services\GitService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * Feature tests for the scheduled update checker and admin header update badge.
 *
 * Coverage:
 *  1. The command is registered and scheduled daily at 01:00 Asia/Jakarta.
 *  2. Admin header renders the update badge when state says update is available.
 *  3. Admin header renders nothing when no update is available.
 *  4. Admin page render does NOT touch git or the network (cache-read only).
 *  5. Graceful handling: detached HEAD and git-unavailable do not report a false
 *     update and do not throw from the command.
 *  6. On-demand check route rejects unauthenticated and non-admin users.
 *  7. Remote URL credentials are never written to the persisted state.
 *
 * Strategy for command tests: call CheckForGitUpdates::handle() directly after
 * binding a Mockery mock so we don't rely on $this->artisan() to resolve the
 * container (which can inherit stale mock state from other test classes).
 */
class GitUpdateCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $plain;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        // Seed settings and roles so admin pages render without DB errors
        // (matches DashboardTest convention).
        $this->seed(SettingSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->plain = User::factory()->create(['email_verified_at' => now()]);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $role = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->admin->roles()->syncWithoutDetaching([
            $role->id => ['model_type' => User::class],
        ]);

        // Ensure a clean cache state for each test.
        Cache::forget(CheckForGitUpdates::CACHE_KEY);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a default "up to date" result array.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'available' => false,
            'commits_behind' => 0,
            'commits_ahead' => 0,
            'remote_sha' => '',
            'remote_subject' => '',
            'remote_author' => '',
            'remote_date' => '',
            'checked_at' => now()->toIso8601String(),
            'error' => '',
            'skipped_reason' => '',
        ], $overrides);
    }

    /**
     * Run the command by calling handle() directly with an injected GitService
     * mock — bypasses $this->artisan() container re-resolution so mock bindings
     * from sibling test files cannot interfere.
     */
    private function runCommand(GitService $gitService, bool $force = true): int
    {
        $command = new CheckForGitUpdates($gitService);
        $command->setLaravel(app());

        // Create an Artisan Input/Output pair.
        $input = new ArrayInput($force ? ['--force' => true] : []);
        $output = new NullOutput;

        return $command->run($input, $output);
    }

    // =========================================================================
    // 1. Schedule registration
    // =========================================================================

    public function test_command_is_scheduled_daily_at_0100_asia_jakarta(): void
    {
        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);

        $events = collect($schedule->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'git:check-updates')
                || str_contains($e->description ?? '', 'git:check-updates'));

        $this->assertNotEmpty($events, 'git:check-updates is not registered in the scheduler.');

        $event = $events->first();

        // Verify timezone is explicitly set to Asia/Jakarta.
        $this->assertSame(
            'Asia/Jakarta',
            $event->timezone,
            'Schedule timezone must be explicitly Asia/Jakarta, not the app default.'
        );

        // Verify cron expression is daily at 01:00 (cron: "0 1 * * *").
        $this->assertSame(
            '0 1 * * *',
            $event->expression,
            'git:check-updates must run at 01:00 daily.'
        );
    }

    // =========================================================================
    // 2 + 3. Admin header badge visibility
    // =========================================================================

    public function test_header_shows_update_badge_when_update_is_available(): void
    {
        // Pre-load cached state as if the scheduler already ran.
        Cache::forever(CheckForGitUpdates::CACHE_KEY, $this->makeResult([
            'available' => true,
            'commits_behind' => 3,
            'remote_sha' => 'abc1234',
        ]));

        // The dashboard uses admin.blade.php (confirmed by the admin header in the
        // response) and does NOT invoke GitService — ideal for asserting the badge.
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Use the language-independent data-testid to avoid locale-sensitive text matching.
        $response->assertSee('data-testid="git-update-badge"', false);
        // Link navigates to version_control settings group.
        $response->assertSee(route('admin.settings.index', ['group' => 'version_control']), false);
    }

    public function test_header_hides_update_badge_when_no_update_available(): void
    {
        Cache::forever(CheckForGitUpdates::CACHE_KEY, $this->makeResult());

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Badge must be absent — not in the DOM when no update is available.
        $response->assertDontSee('data-testid="git-update-badge"', false);
    }

    public function test_header_hides_update_badge_when_cache_is_empty(): void
    {
        // No cache entry at all — first boot or cache cleared.
        Cache::forget(CheckForGitUpdates::CACHE_KEY);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-testid="git-update-badge"', false);
    }

    // =========================================================================
    // 4. Zero git/network calls on admin page render
    // =========================================================================

    public function test_admin_page_render_does_not_call_gitservice(): void
    {
        // Pre-populate cache so the header read is just a Cache::get().
        Cache::forever(CheckForGitUpdates::CACHE_KEY, $this->makeResult([
            'available' => true,
            'commits_behind' => 1,
        ]));

        // The dashboard uses admin.blade.php (which contains the badge) but does NOT
        // invoke GitService in its controller action — so a strict mock proves that the
        // header badge read path (Cache::get) never touches git.
        $this->mock(GitService::class, function ($mock): void {
            $mock->shouldNotReceive('runGit');
            $mock->shouldNotReceive('checkForUpdates');
            $mock->shouldNotReceive('getRemoteInfo');
            $mock->shouldNotReceive('getCommitHistory');
        });

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Badge is present via the language-independent data-testid.
        $response->assertSee('data-testid="git-update-badge"', false);
        // addToAssertionCount registers the Mockery shouldNotReceive expectations.
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // 5. Graceful failure: detached HEAD and git unavailable
    // =========================================================================

    public function test_command_does_not_report_update_on_detached_head(): void
    {
        $mock = \Mockery::mock(GitService::class);
        $mock->shouldReceive('checkForUpdates')
            ->once()
            ->andReturn($this->makeResult(['skipped_reason' => 'detached_head']));
        $mock->shouldNotReceive('runGit');

        $exitCode = $this->runCommand($mock);

        $this->assertSame(0, $exitCode);

        $cached = Cache::get(CheckForGitUpdates::CACHE_KEY);

        $this->assertNotNull($cached);
        $this->assertFalse($cached['available']);
        $this->assertSame('detached_head', $cached['skipped_reason']);
    }

    public function test_command_does_not_throw_when_git_unavailable(): void
    {
        $mock = \Mockery::mock(GitService::class);
        $mock->shouldReceive('checkForUpdates')
            ->once()
            ->andThrow(new \RuntimeException('git: command not found'));
        $mock->shouldReceive('redactRemoteUrl')
            ->once()
            ->andReturn('git: command not found');

        Log::shouldReceive('warning')->once();

        $exitCode = $this->runCommand($mock);

        $this->assertSame(1, $exitCode);

        $cached = Cache::get(CheckForGitUpdates::CACHE_KEY);

        $this->assertNotNull($cached, 'A failure state must still be persisted.');
        $this->assertFalse($cached['available'], 'Failed check must NOT report update available.');
        $this->assertSame('exception', $cached['skipped_reason']);
    }

    public function test_command_does_not_report_update_when_not_a_git_repo(): void
    {
        $mock = \Mockery::mock(GitService::class);
        $mock->shouldReceive('checkForUpdates')
            ->once()
            ->andReturn($this->makeResult([
                'error' => 'Not a git repository.',
                'skipped_reason' => 'not_a_repo',
            ]));
        $mock->shouldNotReceive('runGit');

        $exitCode = $this->runCommand($mock);

        $this->assertSame(0, $exitCode);

        $cached = Cache::get(CheckForGitUpdates::CACHE_KEY);

        $this->assertFalse($cached['available']);
        $this->assertSame('not_a_repo', $cached['skipped_reason']);
    }

    // =========================================================================
    // 6. On-demand check route authorization
    // =========================================================================

    public function test_on_demand_check_route_rejects_unauthenticated(): void
    {
        // Laravel's session-auth middleware redirects unauthenticated web requests
        // rather than returning 401 (matching the project's existing test pattern in
        // GitRollbackFeatureTest).
        $response = $this->postJson(route('admin.settings.git-check-updates'));

        $response->assertStatus(302);
    }

    public function test_on_demand_check_route_rejects_non_admin_user(): void
    {
        $response = $this->actingAs($this->plain)
            ->postJson(route('admin.settings.git-check-updates'));

        $response->assertStatus(403);
    }

    public function test_on_demand_check_route_accepts_admin_user(): void
    {
        // Bind mock via $this->mock() — the controller resolves GitService through
        // the container which respects this binding.
        $this->mock(GitService::class, function ($mock): void {
            $mock->shouldReceive('checkForUpdates')
                ->once()
                ->andReturn($this->makeResult());
        });

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.git-check-updates'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'result', 'message']);
    }

    // =========================================================================
    // 7. Credentials are NOT written to the persisted cache state
    // =========================================================================

    public function test_remote_url_credentials_are_not_in_persisted_state(): void
    {
        // Drive the check via the on-demand route (controller calls $this->gitService
        // directly — mock binding via $this->mock() is reliably resolved).
        $this->mock(GitService::class, function ($mock): void {
            $mock->shouldReceive('checkForUpdates')
                ->once()
                ->andReturn($this->makeResult([
                    // Error message with already-redacted credential.
                    'error' => 'git fetch failed: https://***@github.com/org/repo.git',
                    'skipped_reason' => 'fetch_failed',
                ]));
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.git-check-updates'));

        $cached = Cache::get(CheckForGitUpdates::CACHE_KEY);
        $this->assertNotNull($cached, 'Cache must be populated after on-demand check.');

        // Serialise to string so we can scan for raw credential patterns.
        $serialized = json_encode($cached);

        // Should never contain an un-redacted user:token@host pattern.
        $this->assertDoesNotMatchRegularExpression(
            '#://[^*/@]+:[^@]+@#',
            $serialized,
            'Raw credentials (user:token@host) must not appear in persisted cache state.'
        );

        // The redacted placeholder should be present instead.
        $this->assertStringContainsString('***', $serialized);
    }

    // =========================================================================
    // 8. GitService::checkForUpdates() unit-level — not-a-repo guard
    // =========================================================================

    public function test_check_for_updates_returns_not_available_when_not_a_repo(): void
    {
        $service = new GitService;

        // Use a directory that provably has no .git subdirectory.
        $noRepoDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'no_git_'.uniqid();
        mkdir($noRepoDir, 0755, true);

        try {
            $result = $service->checkForUpdates($noRepoDir);

            $this->assertFalse($result['available']);
            $this->assertSame('not_a_repo', $result['skipped_reason']);
        } finally {
            @rmdir($noRepoDir);
        }
    }

    public function test_check_for_updates_returns_skipped_on_detached_head_via_mock(): void
    {
        $mock = \Mockery::mock(GitService::class)->makePartial();

        // Stub runGit for the HEAD check — but the .git guard must also pass.
        // We create a real temp dir with a fake .git and let makePartial() handle it.
        $cwd = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fake_git_'.uniqid();
        $fakeGitDir = $cwd.DIRECTORY_SEPARATOR.'.git';
        mkdir($fakeGitDir, 0755, true);

        // Stub ONLY the rev-parse abbrev-ref call.
        $mock->shouldReceive('runGit')
            ->with(['rev-parse', '--abbrev-ref', 'HEAD'], $cwd)
            ->andReturn('HEAD');

        try {
            $result = $mock->checkForUpdates($cwd);

            $this->assertFalse($result['available']);
            $this->assertSame('detached_head', $result['skipped_reason']);
        } finally {
            @rmdir($fakeGitDir);
            @rmdir($cwd);
        }
    }
}
