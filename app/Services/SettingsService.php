<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected static ?array $cache = null;

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::loadCache();
        }

        return self::$cache[$key] ?? $default;
    }

    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $type = self::determineType($value);

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? (int) $value : $value,
                'type' => $type,
            ]
        );

        self::clearCache();
    }

    /**
     * Get all settings as an array.
     *
     * @return array
     */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::loadCache();
        }

        return self::$cache;
    }

    /**
     * Load settings into cache.
     *
     * @return void
     */
    protected static function loadCache(): void
    {
        self::$cache = Cache::rememberForever('settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear settings cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = null;
        Cache::forget('settings');
    }

    /**
     * Determine the type of a value.
     *
     * @param mixed $value
     * @return string
     */
    protected static function determineType($value): string
    {
        if (is_string($value)) {
            return 'string';
        } elseif (is_int($value) || is_float($value)) {
            return 'integer';
        } elseif (is_bool($value)) {
            return 'boolean';
        } elseif (is_array($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (self::$cache === null) {
            self::loadCache();
        }

        return array_key_exists($key, self::$cache);
    }
}
