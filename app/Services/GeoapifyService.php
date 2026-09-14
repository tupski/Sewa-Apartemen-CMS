<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * GeoapifyService — Geoapify Places + Route Matrix client.
 *
 * Two responsibilities:
 *   1. searchGroup()  — find candidate POIs for one POI group (Places API).
 *   2. walkingTimes() — measure the ACTUAL walking route to each candidate
 *                       (Route Matrix API, mode=walk).
 *
 * IMPORTANT: this service performs outbound HTTP calls, so it must ONLY be
 * called from FetchNearbyPlacesJob — never from a controller, view, or any
 * other code that runs during a page render.
 *
 * The API key is resolved from Settings (Settings → Integrations) first and
 * falls back to GEOAPIFY_API_KEY in .env. It is only ever sent server-side.
 */
class GeoapifyService
{
    /**
     * Geoapify Places API endpoint.
     */
    private const PLACES_URL = 'https://api.geoapify.com/v2/places';

    /**
     * Geoapify Route Matrix API endpoint.
     */
    private const ROUTE_MATRIX_URL = 'https://api.geoapify.com/v1/routematrix';

    /**
     * Route Matrix accepts at most 1000 source-target combinations per call.
     */
    private const MATRIX_BATCH_SIZE = 1000;

    /**
     * Maximum accepted walking duration, in seconds (10 minutes).
     *
     * This is a travel-time budget, NOT a distance: a POI qualifies only when
     * the routed walking time is <= this value.
     */
    public const WALK_MAX_SECONDS = 600;

    /**
     * Display label for the shopping/mall group (a Property::NEARBY_CATEGORIES key).
     */
    public const GROUP_SHOPPING = 'Mall/Shopping';

    /**
     * Display label for the healthcare group (a Property::NEARBY_CATEGORIES key).
     */
    public const GROUP_HOSPITAL = 'Hospital/Health';

    /**
     * Display label for the public-transport group (a Property::NEARBY_CATEGORIES key).
     */
    public const GROUP_TRANSPORT = 'Transportation';

    /**
     * POI groups to synchronize, mapped to their Geoapify category identifiers.
     *
     * Every identifier below is verified against Geoapify's supported place
     * categories (Geoapify `list_place_categories` / Places API docs):
     *   commercial.shopping_mall, commercial.department_store, commercial.marketplace
     *   healthcare.hospital
     *   public_transport.{train,subway,light_rail,monorail,tram,bus,ferry}
     *
     * Deliberately excluded: `public_transport.platform` (platform nodes are not
     * guest-facing destinations) and `public_transport.aerialway` / `ferry`
     * variants that describe infrastructure rather than a usable station.
     *
     * @var array<string, array<int, string>>
     */
    public const POI_GROUPS = [
        self::GROUP_SHOPPING => [
            'commercial.shopping_mall',
            'commercial.department_store',
            'commercial.marketplace',
        ],
        self::GROUP_HOSPITAL => [
            'healthcare.hospital',
        ],
        self::GROUP_TRANSPORT => [
            'public_transport.train',
            'public_transport.subway',
            'public_transport.light_rail',
            'public_transport.monorail',
            'public_transport.tram',
            'public_transport.bus',
            'public_transport.ferry',
        ],
    ];

    /**
     * Canonical group order used by the admin UI.
     *
     * @var array<int, string>
     */
    public const GROUP_ORDER = [
        self::GROUP_SHOPPING,
        self::GROUP_HOSPITAL,
        self::GROUP_TRANSPORT,
    ];

    private string $apiKey;

    private int $defaultRadius;

    private int $defaultLimit;

    /**
     * @throws RuntimeException if no Geoapify API key is configured
     */
    public function __construct()
    {
        $key = self::apiKey();

        if ($key === null) {
            throw new RuntimeException(
                'Geoapify API key is not configured. Add it in Settings → Integrations.'
            );
        }

        $config = config('services.geoapify');

        $this->apiKey = $key;
        $this->defaultRadius = (int) ($config['radius'] ?? 2000);
        $this->defaultLimit = (int) ($config['max_results'] ?? 20);
    }

    /**
     * Resolve the server-side Geoapify API key.
     *
     * Precedence: the `geoapify_api_key` setting (Settings → Integrations) first,
     * then GEOAPIFY_API_KEY from .env. Returns null when neither is configured.
     *
     * Never render this value: it is server-side only.
     */
    public static function apiKey(): ?string
    {
        return self::firstFilled([
            self::setting('geoapify_api_key'),
            config('services.geoapify.key'),
        ]);
    }

    /**
     * Resolve the browser-facing map-tile key.
     *
     * Precedence: the `geoapify_map_key` setting, then GEOAPIFY_MAP_KEY, then the
     * server Places key. This value IS exposed to the browser in the map payload —
     * operators should set a separate, referrer-restricted key.
     */
    public static function mapKey(): ?string
    {
        return self::firstFilled([
            self::setting('geoapify_map_key'),
            config('services.geoapify.map_key'),
            self::apiKey(),
        ]);
    }

    /**
     * Whether a server-side API key is configured (used by the admin UI status badge).
     */
    public static function isConfigured(): bool
    {
        return self::apiKey() !== null;
    }

    /**
     * Fetch candidate POIs for ONE group from the Places API.
     *
     * @param  string  $group  A key of self::POI_GROUPS
     * @param  float  $lat  Property latitude
     * @param  float  $lng  Property longitude
     * @param  int|null  $radiusMetres  Candidate search radius (defaults to config)
     * @param  int|null  $limit  Max candidates for this group (defaults to config)
     * @return array<int, array{
     *     geoapify_place_id: string,
     *     name: string,
     *     raw_category: string,
     *     category: string,
     *     lat: float,
     *     lng: float,
     *     address: string,
     *     website: string,
     *     phone: string,
     * }>
     *
     * @throws RuntimeException on an unknown group, API key error, persistent
     *                          failure, or an unparseable response
     */
    public function searchGroup(
        string $group,
        float $lat,
        float $lng,
        ?int $radiusMetres = null,
        ?int $limit = null
    ): array {
        if (! isset(self::POI_GROUPS[$group])) {
            throw new RuntimeException("Unknown POI group: {$group}");
        }

        $radius = $radiusMetres ?? $this->defaultRadius;
        $max = $limit ?? $this->defaultLimit;

        $response = $this->request('GET', self::PLACES_URL, [
            'categories' => implode(',', self::POI_GROUPS[$group]),
            'filter' => "circle:{$lng},{$lat},{$radius}",
            'limit' => $max,
            'apiKey' => $this->apiKey,
        ]);

        $data = $response->json();

        if ($data === null) {
            throw new RuntimeException('Geoapify returned invalid JSON');
        }

        // Graceful degradation — Geoapify may return 0 results with no features key.
        if (! isset($data['features']) || ! is_array($data['features'])) {
            return [];
        }

        $results = [];

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            $geom = $feature['geometry'] ?? [];

            // Skip any POI without a name.
            $name = $props['name'] ?? null;
            if (empty($name)) {
                continue;
            }

            // SEC-007: sanitize the raw category BEFORE the allowlist mapping, so a
            // hostile payload cannot smuggle markup into `raw_category`, while the
            // mapped label itself remains an exact NEARBY_CATEGORIES key.
            $rawCategory = $this->sanitize($props['categories'][0] ?? '', 255);
            $category = $this->mapCategory($rawCategory);

            // Skip POIs whose category cannot be mapped to a known group.
            if ($category === null) {
                continue;
            }

            // GeoJSON coordinates are [longitude, latitude].
            $coords = $geom['coordinates'] ?? [0, 0];

            // SEC-007: every Geoapify-supplied string is stripped of markup,
            // whitespace-normalized, and clamped to its DB column length before it
            // is persisted (see the create_places_table migration).
            $results[] = [
                'geoapify_place_id' => $this->sanitize($props['place_id'] ?? '', 128),
                'name' => $this->sanitize($name, 255),
                'raw_category' => $rawCategory,
                'category' => $category,
                'lat' => (float) ($coords[1] ?? 0),
                'lng' => (float) ($coords[0] ?? 0),
                'address' => $this->sanitize($props['formatted'] ?? '', 500),
                'website' => $this->sanitize($props['website'] ?? '', 500),
                'phone' => $this->sanitize($props['contact']['phone'] ?? '', 50),
            ];
        }

        return $results;
    }

    /**
     * Measure the ACTUAL walking route from the property to every candidate POI.
     *
     * Uses the Route Matrix API with mode=walk, batched at 1000 target
     * combinations per request. Straight-line distance is NOT used as a proxy:
     * the returned travel time is the value the 10-minute filter is applied to.
     *
     * @param  float  $lat  Property latitude (the single matrix source)
     * @param  float  $lng  Property longitude
     * @param  array<int, array{geoapify_place_id: string, lat: float, lng: float}>  $targets
     * @return array<string, array{seconds: int|null, metres: int|null}>
     *                                                                   Keyed by geoapify_place_id. A target the router cannot reach maps to
     *                                                                   nulls and is excluded by the caller.
     *
     * @throws RuntimeException when the route calculation fails
     */
    public function walkingTimes(float $lat, float $lng, array $targets): array
    {
        $results = [];

        foreach (array_chunk($targets, self::MATRIX_BATCH_SIZE) as $chunk) {
            $payload = [
                'mode' => 'walk',
                'type' => 'short',
                'units' => 'metric',
                'sources' => [['location' => [$lng, $lat]]],
                'targets' => array_map(
                    fn (array $target): array => ['location' => [(float) $target['lng'], (float) $target['lat']]],
                    $chunk
                ),
            ];

            $response = $this->request('POST', self::ROUTE_MATRIX_URL.'?apiKey='.$this->apiKey, $payload);

            $data = $response->json();

            if ($data === null) {
                throw new RuntimeException('Geoapify returned invalid JSON');
            }

            $matrix = $data['sources_to_targets'] ?? null;

            if (! is_array($matrix)) {
                throw new RuntimeException('Geoapify returned an unexpected route matrix payload');
            }

            // Single source => a single row of target entries, ordered by target index.
            $row = array_values(is_array($matrix[0] ?? null) ? $matrix[0] : []);

            foreach (array_values($chunk) as $index => $target) {
                $entry = $row[$index] ?? null;

                $results[$target['geoapify_place_id']] = [
                    'seconds' => is_array($entry) && isset($entry['time']) ? (int) $entry['time'] : null,
                    'metres' => is_array($entry) && isset($entry['distance']) ? (int) $entry['distance'] : null,
                ];
            }
        }

        return $results;
    }

    /**
     * Perform one Geoapify HTTP request with the shared timeout/retry policy.
     *
     * SEC-004: a ConnectionException carries a Guzzle message that embeds the
     * full request URL — including the apiKey query parameter. It is converted
     * into a generic RuntimeException with NO original message and NO $previous
     * chaining, so the key can never reach a log line or a debug trace.
     *
     * @param  array<string, mixed>|array<int, mixed>  $payload  Query params (GET) or JSON body (POST)
     *
     * @throws RuntimeException on key errors, rate limiting, or persistent failure
     */
    private function request(string $method, string $url, array $payload): Response
    {
        try {
            $pending = Http::timeout(15)
                ->connectTimeout(10)
                ->retry(2, 1000, function (Throwable $e) {
                    // The retry callback's 2nd argument is the PendingRequest, not the
                    // Response — the failed response is reachable via the exception.
                    $failed = $e instanceof RequestException ? $e->response : null;

                    // Handle 429 rate limiting: wait (bounded) then retry once. Checked
                    // before the generic 4xx short-circuit because 429 IS a 4xx.
                    if ($failed && $failed->status() === 429) {
                        $retryAfter = (int) ($failed->header('Retry-After') ?: 5);
                        sleep(max(1, min($retryAfter, 5)));

                        return true;
                    }

                    // Do not retry on 4xx responses (including 401/403 — API key errors).
                    if ($failed && $failed->status() >= 400 && $failed->status() < 500) {
                        return false;
                    }

                    // Retry on timeout or 5xx.
                    return true;
                }, throw: false);

            $response = $method === 'POST'
                ? $pending->post($url, $payload)
                : $pending->get($url, $payload);
        } catch (ConnectionException) {
            throw new RuntimeException('Geoapify API request failed: connection error');
        }

        // Handle auth failures immediately — no further retries.
        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('Geoapify API key invalid or quota exceeded');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Geoapify API rate limit reached');
        }

        if ($response->failed()) {
            throw new RuntimeException('Geoapify API request failed: '.$response->status());
        }

        return $response;
    }

    /**
     * Sanitize an untrusted Geoapify string for persistence (SEC-007).
     *
     * Strips HTML tags, collapses all whitespace runs to single spaces, trims, and
     * clamps to the destination column length. Defense-in-depth only: render paths
     * still escape. No new dependency is used.
     *
     * @param  mixed  $value  Raw value straight from the Geoapify payload.
     * @param  int  $maxLength  Destination column length in characters.
     */
    private function sanitize(mixed $value, int $maxLength): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $clean = strip_tags((string) $value);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
        $clean = trim($clean);

        return mb_substr($clean, 0, $maxLength);
    }

    /**
     * Map a raw Geoapify category string to one of the synchronized POI groups.
     *
     * Matching is exact or by child prefix: `commercial.shopping_mall` matches the
     * category itself, while `public_transport.train.station` matches the
     * `public_transport.train` prefix. Parent-only strings (e.g. plain
     * `public_transport`) return null and are dropped, because they are too vague
     * to be guest-facing.
     *
     * @return string|null A Property::NEARBY_CATEGORIES key, or null to skip the POI
     */
    private function mapCategory(string $rawCategory): ?string
    {
        if ($rawCategory === '') {
            return null;
        }

        foreach (self::POI_GROUPS as $group => $categories) {
            foreach ($categories as $category) {
                if ($rawCategory === $category || str_starts_with($rawCategory, $category.'.')) {
                    return $group;
                }
            }
        }

        return null;
    }

    /**
     * First non-empty string from a precedence list.
     *
     * @param  array<int, mixed>  $candidates
     */
    private static function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Read a setting without exploding when the settings table does not exist yet
     * (installer / fresh install / pre-migration boot).
     */
    private static function setting(string $key): ?string
    {
        try {
            $value = SettingsService::get($key);
        } catch (Throwable) {
            return null;
        }

        return is_string($value) ? $value : null;
    }
}
