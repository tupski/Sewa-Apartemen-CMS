<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * Maps group keys to their corresponding database table names.
     * Tables that don't exist are silently skipped during export/restore.
     */
    protected const TABLE_MAP = [
        'settings' => ['settings', 'languages', 'currency_rates'],
        'blog' => ['posts', 'categories', 'tags', 'post_tag'],
        'properties' => ['properties', 'amenities', 'property_photos', 'promo_rates', 'units'],
        'bookings' => ['bookings'],
        'vouchers' => ['vouchers'],
        'pages' => ['pages', 'blocks', 'navigations'],
        'users' => ['users', 'roles', 'model_has_roles'],
        'media' => ['media'],
        'seo' => ['seo_metadata', 'redirects'],
    ];

    /**
     * Export data for the given group keys.
     *
     * When 'full' is included in $groups, all groups are exported.
     *
     * @param  array<string>  $groups  Group keys selected by the user.
     * @return array{version: string, created_at: string, app_name: string, groups: array<string, list<array<string, mixed>>>}
     */
    public function export(array $groups): array
    {
        // 'full' is a shortcut — export everything
        if (in_array('full', $groups, true)) {
            $groups = array_keys(self::TABLE_MAP);
        }

        $exported = [];

        foreach ($groups as $group) {
            if (! array_key_exists($group, self::TABLE_MAP)) {
                continue;
            }

            $exported[$group] = [];

            foreach (self::TABLE_MAP[$group] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                $exported[$group][$table] = $rows;
            }
        }

        return [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'app_name' => config('app.name', 'Laravel'),
            'groups' => $exported,
        ];
    }

    /**
     * Check whether any of the tables referenced in the backup data
     * currently contain rows.  Used to decide whether a confirmation
     * dialog is needed before overwriting.
     *
     * @param  array{groups: array<string, array<string, list<array<string, mixed>>>>}  $data
     * @return bool true if at least one target table has at least one row
     */
    public function hasExistingData(array $data): bool
    {
        $groups = $data['groups'] ?? [];

        foreach ($groups as $groupKey => $tables) {
            if (! array_key_exists($groupKey, self::TABLE_MAP)) {
                continue;
            }

            foreach ($tables as $table => $rows) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                if (DB::table($table)->count() > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Restore data from a previously exported backup array.
     *
     * Each group's tables are truncated and re-populated inside a single
     * database transaction so the restore is atomic.
     *
     * SEC-09: every row is filtered against the live column list for its table
     * before insertion, so a crafted backup file cannot introduce arbitrary
     * columns (e.g. an invented `is_admin` on `users`). Unknown keys are dropped
     * and logged rather than aborting the whole restore — a backup taken from an
     * older/newer schema of the same app must still be restorable. Rows that are
     * not well-formed key/value maps are skipped entirely.
     *
     * @param  array{groups: array<string, array<string, list<array<string, mixed>>>>}  $data
     *
     * @throws \Throwable
     */
    public function restore(array $data): void
    {
        $groups = $data['groups'] ?? [];

        DB::transaction(function () use ($groups): void {
            // Disable FK checks for the duration of the restore so truncates
            // don't fail due to referential integrity constraints.
            $this->setForeignKeyChecks(false);

            try {
                foreach ($groups as $groupKey => $tables) {
                    if (! array_key_exists($groupKey, self::TABLE_MAP)) {
                        continue;
                    }

                    if (! is_array($tables)) {
                        continue;
                    }

                    foreach ($tables as $table => $rows) {
                        if (! Schema::hasTable($table)) {
                            continue;
                        }

                        // Only tables this service is responsible for.
                        if (! in_array($table, self::TABLE_MAP[$groupKey], true)) {
                            Log::warning('Backup restore: table not in group map, skipped.', [
                                'group' => $groupKey,
                                'table' => $table,
                            ]);

                            continue;
                        }

                        DB::table($table)->truncate();

                        if (! is_array($rows) || $rows === []) {
                            continue;
                        }

                        $sanitized = $this->sanitizeRows($table, $rows);

                        if ($sanitized === []) {
                            continue;
                        }

                        // Insert in chunks to avoid hitting query-size limits
                        foreach (array_chunk($sanitized, 500) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            } finally {
                $this->setForeignKeyChecks(true);
            }
        });
    }

    /**
     * Toggle referential-integrity enforcement for the restore.
     *
     * The mechanism is unchanged for MySQL/MariaDB (`SET FOREIGN_KEY_CHECKS`);
     * the statement is merely dialect-aware so the restore path also works on
     * the SQLite connection used by `.env.example` and the test suite, where
     * the MySQL syntax is a hard parse error.
     */
    protected function setForeignKeyChecks(bool $enabled): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS='.($enabled ? '1' : '0')),
            'sqlite' => DB::statement('PRAGMA foreign_keys='.($enabled ? 'ON' : 'OFF')),
            'pgsql' => null, // no session-level equivalent; deferred FKs handle this
            default => null,
        };
    }

    /**
     * Filter backup rows down to columns that actually exist on the table.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    protected function sanitizeRows(string $table, array $rows): array
    {
        $columns = Schema::getColumnListing($table);

        if ($columns === []) {
            Log::warning('Backup restore: no columns resolved for table, rows skipped.', [
                'table' => $table,
            ]);

            return [];
        }

        $allowed = array_flip($columns);
        $sanitized = [];
        $droppedKeys = [];
        $skippedRows = 0;

        foreach ($rows as $row) {
            if ($row instanceof \stdClass) {
                $row = (array) $row;
            }

            if (! is_array($row) || $row === []) {
                $skippedRows++;

                continue;
            }

            $clean = [];

            foreach ($row as $key => $value) {
                if (! is_string($key) || ! isset($allowed[$key])) {
                    $droppedKeys[is_scalar($key) ? (string) $key : '(non-scalar)'] = true;

                    continue;
                }

                // Nested structures are never valid column values here.
                if (is_array($value) || is_object($value)) {
                    $droppedKeys[$key] = true;

                    continue;
                }

                $clean[$key] = $value;
            }

            if ($clean === []) {
                $skippedRows++;

                continue;
            }

            $sanitized[] = $clean;
        }

        if ($droppedKeys !== []) {
            Log::warning('Backup restore: dropped unknown/invalid columns.', [
                'table' => $table,
                'columns' => array_keys($droppedKeys),
            ]);
        }

        if ($skippedRows > 0) {
            Log::warning('Backup restore: skipped malformed rows.', [
                'table' => $table,
                'count' => $skippedRows,
            ]);
        }

        return $sanitized;
    }

    /**
     * Produce a complete MySQL `.sql` dump via mysqldump.
     *
     * This is the database-backup path for the Git rollback flow: rolling back
     * code does NOT roll back the schema, so a full dump is strongly advised
     * before any rollback. It is a deliberately separate, minimal addition on
     * top of the existing JSON export — no competing backup implementation.
     *
     * SECURITY: mysqldump is invoked via Symfony Process with an argument
     * ARRAY (never a shell string); DB credentials are never echoed to output
     * or logs.
     *
     * @return array{path: string, filename: string}
     *
     * @throws \RuntimeException when the connection is not MySQL, mysqldump is
     *                           missing from PATH, or the dump fails.
     */
    public function dumpSql(): array
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException(__('git.backup_db_not_mysql'));
        }

        $host = config('database.connections.'.$driver.'.host', '127.0.0.1');
        $port = config('database.connections.'.$driver.'.port', '3306');
        $database = config('database.connections.'.$driver.'.database', '');
        $username = config('database.connections.'.$driver.'.username', '');
        $password = config('database.connections.'.$driver.'.password', '');
        $charset = config('database.connections.'.$driver.'.charset', 'utf8mb4');

        if ($database === '' || $username === '') {
            throw new \RuntimeException(__('git.backup_db_missing_config'));
        }

        // Fail fast with an actionable message when mysqldump is not on PATH.
        $probe = new Process(PHP_OS_FAMILY === 'Windows' ? ['where', 'mysqldump'] : ['which', 'mysqldump']);
        $probe->run();

        if (! $probe->isSuccessful()) {
            throw new \RuntimeException(__('git.backup_db_mysqldump_missing'));
        }

        $filename = 'rollback-backup-'.$database.'-'.now()->format('Ymd-His').'.sql';
        $dumpPath = storage_path('app/private/'.$filename);

        $argv = [
            'mysqldump',
            '--host='.$host,
            '--port='.(string) $port,
            '--user='.$username,
            '--password='.$password,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set='.$charset,
            $database,
        ];

        $process = new Process($argv, null, null, null, 900);
        $process->run();

        if (! $process->isSuccessful()) {
            // Never leak stderr (may embed DB errors/paths) to the caller —
            // keep the failure generic and log the real detail server-side.
            Log::error('mysqldump failed', ['exit' => $process->getExitCode(), 'stderr' => $process->getErrorOutput()]);

            throw new \RuntimeException(__('git.backup_db_dump_failed'));
        }

        $sql = $process->getOutput();

        if (trim($sql) === '') {
            throw new \RuntimeException(__('git.backup_db_dump_empty'));
        }

        // Store under app/private so the dump never lands on the public disk.
        if (! is_dir(dirname($dumpPath))) {
            mkdir(dirname($dumpPath), 0775, true);
        }

        file_put_contents($dumpPath, $sql);

        return [
            'path' => $dumpPath,
            'filename' => $filename,
        ];
    }
}
