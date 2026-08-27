<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * SEC-09 regression coverage.
 *
 * `BackupService::restore()` truncates target tables (including `users` and
 * `roles`) and then inserts rows taken verbatim from an admin-supplied JSON
 * file. Without per-column filtering, a crafted file could introduce arbitrary
 * columns — a privilege-escalation path. Every row must now be reduced to the
 * columns that genuinely exist on its table, with unknown keys dropped and
 * logged rather than aborting an otherwise legitimate restore.
 */
class BackupRestoreValidationTest extends TestCase
{
    use RefreshDatabase;

    private BackupServiceProbe $probe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->probe = new BackupServiceProbe();
    }

    public function test_unknown_column_keys_are_dropped(): void
    {
        $rows = [[
            'id'                 => 1,
            'name'               => 'Legit User',
            'email'              => 'legit@example.com',
            'password'           => 'hash',
            // Injected keys that do not exist on `users`.
            'is_admin'           => 1,
            'is_super_admin'     => true,
            'role'               => 'super-admin',
            'permissions'        => 'all',
            '__proto__'          => 'x',
        ]];

        $clean = $this->probe->probeSanitize('users', $rows);

        $this->assertCount(1, $clean);
        $this->assertArrayHasKey('email', $clean[0]);
        $this->assertArrayNotHasKey('is_admin', $clean[0]);
        $this->assertArrayNotHasKey('is_super_admin', $clean[0]);
        $this->assertArrayNotHasKey('role', $clean[0]);
        $this->assertArrayNotHasKey('permissions', $clean[0]);
        $this->assertArrayNotHasKey('__proto__', $clean[0]);
    }

    public function test_every_surviving_key_is_a_real_column(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');

        $clean = $this->probe->probeSanitize('users', [[
            'name'     => 'A',
            'email'    => 'a@example.com',
            'bogus_1'  => 'x',
            'bogus_2'  => 'y',
        ]]);

        foreach (array_keys($clean[0]) as $key) {
            $this->assertContains($key, $columns);
        }
    }

    public function test_nested_structures_are_dropped(): void
    {
        $clean = $this->probe->probeSanitize('users', [[
            'name'  => ['array' => 'payload'],
            'email' => 'nested@example.com',
        ]]);

        $this->assertCount(1, $clean);
        $this->assertArrayNotHasKey('name', $clean[0]);
        $this->assertSame('nested@example.com', $clean[0]['email']);
    }

    public function test_rows_with_no_valid_columns_are_skipped(): void
    {
        $clean = $this->probe->probeSanitize('users', [
            ['is_admin' => 1],
            'not-an-array',
            [],
            ['email' => 'keeper@example.com'],
        ]);

        $this->assertCount(1, $clean);
        $this->assertSame('keeper@example.com', $clean[0]['email']);
    }

    public function test_numeric_keys_are_dropped(): void
    {
        $clean = $this->probe->probeSanitize('users', [[
            0       => 'positional',
            'email' => 'numeric@example.com',
        ]]);

        $this->assertSame(['email' => 'numeric@example.com'], $clean[0]);
    }

    public function test_legitimate_rows_pass_through_unchanged(): void
    {
        $row = [
            'id'    => 7,
            'name'  => 'Untouched',
            'email' => 'untouched@example.com',
        ];

        $clean = $this->probe->probeSanitize('users', [$row]);

        $this->assertSame($row, $clean[0]);
    }

    public function test_dropped_columns_are_logged(): void
    {
        Log::shouldReceive('warning')
            ->atLeast()
            ->once()
            ->with(
                'Backup restore: dropped unknown/invalid columns.',
                \Mockery::on(function (array $context): bool {
                    return ($context['table'] ?? null) === 'users'
                        && in_array('is_admin', $context['columns'] ?? [], true);
                })
            );

        $this->probe->probeSanitize('users', [[
            'email'    => 'logged@example.com',
            'is_admin' => 1,
        ]]);
    }

    public function test_legitimate_backup_round_trips(): void
    {
        $service = new BackupService();

        DB::table('settings')->insert([
            ['key' => 'site_name', 'value' => 'Round Trip'],
            ['key' => 'contact_email', 'value' => 'rt@example.com'],
        ]);

        $backup = $service->export(['settings']);

        $this->assertNotEmpty($backup['groups']['settings']['settings']);

        // Simulate the real path: JSON encode/decode as the uploaded file would.
        $decoded = json_decode(json_encode($backup), true);

        $service->restore($decoded);

        $this->assertSame(
            'Round Trip',
            DB::table('settings')->where('key', 'site_name')->value('value')
        );
        $this->assertSame(
            'rt@example.com',
            DB::table('settings')->where('key', 'contact_email')->value('value')
        );
        $this->assertSame(2, DB::table('settings')->count());
    }

    public function test_restore_drops_injected_columns_end_to_end(): void
    {
        $service = new BackupService();

        $service->restore([
            'groups' => [
                'settings' => [
                    'settings' => [
                        [
                            'key'      => 'injected',
                            'value'    => 'ok',
                            'is_admin' => 1,
                            'evil'     => 'payload',
                        ],
                    ],
                ],
            ],
        ]);

        $row = (array) DB::table('settings')->where('key', 'injected')->first();

        $this->assertSame('ok', $row['value']);
        $this->assertArrayNotHasKey('is_admin', $row);
        $this->assertArrayNotHasKey('evil', $row);
    }

    public function test_restore_ignores_tables_outside_the_group_map(): void
    {
        $service = new BackupService();

        $service->restore([
            'groups' => [
                'settings' => [
                    // `users` is not part of the `settings` group.
                    'users' => [
                        ['name' => 'Smuggled', 'email' => 'smuggled@example.com', 'password' => 'x'],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'smuggled@example.com']);
    }
}

/**
 * Exposes BackupService's protected row filter for assertion.
 */
class BackupServiceProbe extends BackupService
{
    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public function probeSanitize(string $table, array $rows): array
    {
        return $this->sanitizeRows($table, $rows);
    }
}
