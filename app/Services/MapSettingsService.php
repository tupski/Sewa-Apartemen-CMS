<?php

namespace App\Services;

/**
 * MapSettingsService — central configuration for the Leaflet/OSM map system.
 *
 * Styles are identified by STABLE KEYS (never display names); each key maps to
 * the tile URL template supported by the current map implementation (Geoapify
 * Raster Tiles v1, which hosts all six styles). Labels live in the lang files.
 *
 * Theme behavior: `follow` resolves the style from the site's light/dark mode
 * (dark styles for dark mode, the configured light style otherwise) — so a
 * theme switch never requires new code paths in JS, only a different tile URL.
 *
 * Custom colors: Geoapify RASTER tiles cannot be recolored client-side, so
 * per-layer color overrides (background/buildings/roads/water/labels) are NOT
 * technically supported and the service deliberately exposes no such option —
 * admin UI explains this instead of faking it. Theme support is achieved by
 * switching between the light and dark style keys.
 */
class MapSettingsService
{
    /**
     * Stable style key => [tile URL template, is_dark].
     *
     * All styles are served by Geoapify Raster Tiles v1 (the current map
     * implementation uses Geoapify tiles when a map key is configured, OSM
     * standard tiles otherwise). Style IDs verified against Geoapify's
     * published catalogue (apidocs.geoapify.com/docs/maps/map-tiles/):
     * osm-carto, osm-bright(+variants), klokantech-basic, positron(+variants),
     * dark-matter(+variants). The requested names "MapTiler Basic" and
     * "Fiord Color" are not Geoapify style IDs, so their stable internal keys
     * map to the verified closest equivalents (klokantech-basic IS the
     * Klokantech/MapTiler "Basic" style; dark-matter-dark-grey is the closest
     * verified dark style to Fiord). `osm-standard` is the keyless fallback.
     *
     * @var array<string, array{url: string, dark: bool}>
     */
    public const STYLES = [
        'osm-openmaptiles' => ['url' => 'https://maps.geoapify.com/v1/tile/osm-carto/{z}/{x}/{y}.png', 'dark' => false],
        'maptiler-basic' => ['url' => 'https://maps.geoapify.com/v1/tile/klokantech-basic/{z}/{x}/{y}.png', 'dark' => false],
        'osm-bright' => ['url' => 'https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png', 'dark' => false],
        'positron' => ['url' => 'https://maps.geoapify.com/v1/tile/positron/{z}/{x}/{y}.png', 'dark' => false],
        'dark-matter' => ['url' => 'https://maps.geoapify.com/v1/tile/dark-matter/{z}/{x}/{y}.png', 'dark' => true],
        'fiord-color' => ['url' => 'https://maps.geoapify.com/v1/tile/dark-matter-dark-grey/{z}/{x}/{y}.png', 'dark' => true],
        'osm-standard' => ['url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'dark' => false],
    ];

    /**
     * Theme modes for style resolution.
     *
     * @var array<int, string>
     */
    public const THEME_MODES = ['follow', 'light', 'dark'];

    /**
     * Default style when nothing is configured.
     */
    public const DEFAULT_STYLE = 'osm-bright';

    /**
     * The configured light style key.
     */
    public static function lightStyle(): string
    {
        $key = (string) SettingsService::get('map_style_light', self::DEFAULT_STYLE);

        return self::isValidStyle($key) && ! self::STYLES[$key]['dark'] ? $key : self::DEFAULT_STYLE;
    }

    /**
     * The configured dark style key (defaults to Dark Matter).
     */
    public static function darkStyle(): string
    {
        $key = (string) SettingsService::get('map_style_dark', 'dark-matter');

        return self::isValidStyle($key) && self::STYLES[$key]['dark'] ? $key : 'dark-matter';
    }

    /**
     * The configured theme mode: follow | light | dark.
     */
    public static function themeMode(): string
    {
        $mode = (string) SettingsService::get('map_theme_mode', 'follow');

        return in_array($mode, self::THEME_MODES, true) ? $mode : 'follow';
    }

    /**
     * Resolve the concrete style key for a light/dark site theme.
     */
    public static function resolveStyle(bool $isDark): string
    {
        return match (self::themeMode()) {
            'light' => self::lightStyle(),
            'dark' => self::darkStyle(),
            default => $isDark ? self::darkStyle() : self::lightStyle(),
        };
    }

    /**
     * Tile URL for a style key, with the (referrer-restricted) map key appended
     * when the style is hosted by Geoapify. The OSM standard fallback needs no key.
     */
    public static function styleUrl(string $styleKey): ?string
    {
        if (! self::isValidStyle($styleKey)) {
            return null;
        }

        $url = self::STYLES[$styleKey]['url'];

        if (! str_contains($url, 'maps.geoapify.com')) {
            return $url;
        }

        $mapKey = GeoapifyService::mapKey();

        if (empty($mapKey)) {
            // A keyless Geoapify style cannot resolve: the caller (JS) falls back
            // to OSM standard tiles instead of an invented/empty-key URL.
            return null;
        }

        return $url.'?apiKey='.urlencode((string) $mapKey);
    }

    /**
     * Whether the style key exists in the catalogue.
     */
    public static function isValidStyle(string $key): bool
    {
        return array_key_exists($key, self::STYLES);
    }
}
