<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
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
     * Restore data from a previously exported backup array.
     *
     * Each group's tables are truncated and re-populated inside a single
     * database transaction so the restore is atomic.
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
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach ($groups as $groupKey => $tables) {
                    if (! array_key_exists($groupKey, self::TABLE_MAP)) {
                        continue;
                    }

                    foreach ($tables as $table => $rows) {
                        if (! Schema::hasTable($table)) {
                            continue;
                        }

                        DB::table($table)->truncate();

                        if (! empty($rows)) {
                            // Insert in chunks to avoid hitting query-size limits
                            foreach (array_chunk($rows, 500) as $chunk) {
                                DB::table($table)->insert($chunk);
                            }
                        }
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });
    }
}
