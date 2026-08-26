<?php

use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

if (! function_exists('slug')) {
    /**
     * Get a configurable public URL slug/path segment from the settings,
     * falling back to the provided default if the setting is empty or the
     * settings table does not exist yet (e.g. during testing / first install).
     *
     * @param  string  $key     The settings key, e.g. 'slug_apartments'
     * @param  string  $default Fallback value when the setting is empty
     * @return string
     */
    function slug(string $key, string $default = ''): string
    {
        try {
            $value = trim((string) SettingsService::get($key, $default), '/');
        } catch (\Throwable $e) {
            // Settings table may not exist yet (migrations not run, testing, etc.)
            return trim($default, '/');
        }

        return $value !== '' ? $value : trim($default, '/');
    }
}

if (! function_exists('slug_url')) {
    /**
     * Build a fully-qualified URL for a configurable public slug segment.
     *
     * @param  string  $key     The settings key, e.g. 'slug_apartments'
     * @param  string  $default Fallback value when the setting is empty
     * @param  array<int, string>|null  $segments Extra path segments appended after the slug
     * @return string
     */
    function slug_url(string $key, string $default = '', ?array $segments = null): string
    {
        $path = slug($key, $default);

        if ($segments) {
            $path .= '/' . implode('/', array_map(fn ($s) => trim($s, '/'), $segments));
        }

        return url('/' . $path);
    }
}
