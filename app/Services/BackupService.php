<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackupService
{
    /**
     * Maps group keys to their corresponding database table names.
     * Tables that don't exist are silently skipped during export/restore.
     */
    protected const TABLE_MAP = [
        'settings'   => ['settings', 'languages', 'currency_rates'],
        'blog'       => ['posts', 'categories', 'tags', 'post_tag'],
        'properties' => ['properties', 'amenities', 'property_photos', 'promo_rates', 'units'],
        'bookings'   => ['bookings'],
        'vouchers'   => ['vouchers'],
        'pages'      => ['pages', 'blocks', 'navigations'],
        'users'      => ['users', 'roles', 'model_has_roles'],
        'media'      => ['media'],
        'seo'        => ['seo_metadata', 'redirects'],
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
            'version'    => '1.0',
            'created_at' => now()->toIso8601String(),
            'app_name'   => config('app.name', 'Laravel'),
            'groups'     => $exported,
        ];
    }

    /**
     * Check whether any of the tables referenced in the backup data
     * currently contain rows.  Used to decide whether a confirmation
     * dialog is needed before overwriting.
     *
     * @param  array{groups: array<string, array<string, list<array<string, mixed>>>>}  $data
     * @return bool  true if at least one target table has at least one row
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
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=' . ($enabled ? '1' : '0')),
            'sqlite' => DB::statement('PRAGMA foreign_keys=' . ($enabled ? 'ON' : 'OFF')),
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
                'table'   => $table,
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
}
