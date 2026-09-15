<?php

namespace App\Services;

use App\Models\PlaceCategory;
use Database\Seeders\PlaceCategorySeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * GeoapifyService — Geoapify Places + Route Matrix client.
 *
 * Responsibilities:
 *   1. searchCategory() — find candidate POIs for ONE Artivo place category
 *                          (Places API), keyed by the category's Geoapify slug.
 *   2. matrixTimes()    — measure the ACTUAL routed travel time/distance to
 *                          each candidate (Route Matrix API) for a mode.
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
     * Reachability budgets per travel mode: max routed duration (seconds) and
     * max routed distance (metres). A POI qualifies only when BOTH the routed
     * time and the routed distance are within the budget. These are real
     * travel-time budgets, NOT straight-line radii.
     *
     * mode => [max_seconds, max_metres]
     *
     * @var array<string, array{0: int, 1: int}>
     */
    public const MODE_BUDGETS = [
        'walk' => [900, 2000],   // 15 minutes / 2000 m
        'drive' => [600, 5000],  // 10 minutes / 5000 m
        'motorcycle' => [600, 3000], // 10 minutes / 3000 m
    ];

    /**
     * Route Matrix mode names accepted by Geoapify.
     *
     * @var array<int, string>
     */
    public const MODES = ['walk', 'drive', 'motorcycle'];

    /**
     * Legacy 3-group constants kept for backwards compatibility with the
     * admin UI labels and existing tests. They now act as parent-category
     * aliases into the DB-managed catalogue.
     */
    public const GROUP_SHOPPING = 'Mall/Shopping';

    public const GROUP_HOSPITAL = 'Hospital/Health';

    public const GROUP_TRANSPORT = 'Transportation';

    /**
     * Legacy walking budget (10 min in seconds). The per-mode budgets live in
     * MODE_BUDGETS; this constant survives because older views/tests reference it.
     */
    public const WALK_MAX_SECONDS = 600;

    /**
     * @deprecated Use activeCategorySlugs() / searchCategory(). Kept as a thin
     * wrapper mapping the legacy group label to its representative category
     * slugs so existing callers keep working.
     *
     * @var array<string, array<int, string>>
     */
    public const POI_GROUPS = [
        self::GROUP_SHOPPING => ['commercial.shopping_mall', 'commercial.department_store', 'commercial.marketplace'],
        self::GROUP_HOSPITAL => ['healthcare.hospital'],
        self::GROUP_TRANSPORT => ['public_transport.train', 'public_transport.subway', 'public_transport.light_rail', 'public_transport.monorail', 'public_transport.tram', 'public_transport.bus', 'public_transport.ferry'],
    ];

    /**
     * @var array<int, string>
     */
    public const GROUP_ORDER = [
        self::GROUP_SHOPPING,
        self::GROUP_HOSPITAL,
        self::GROUP_TRANSPORT,
    ];

    /**
     * Fetch candidate POIs for ONE legacy group (wrapper over searchCategory
     * with the representative category slugs merged).
     *
     * @param  string  $group  A key of self::POI_GROUPS
     * @param  float  $lat  Property latitude
     * @param  float  $lng  Property longitude
     * @param  int|null  $radiusMetres  Candidate search radius (defaults to config)
     * @param  int|null  $limit  Max candidates for this group (defaults to config)
     * @return array<int, array<string, mixed>>
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

        $results = [];

        foreach (self::POI_GROUPS[$group] as $slug) {
            foreach ($this->searchCategory($slug, $lat, $lng, $radiusMetres, $limit) as $poi) {
                // Report the legacy group label as the category so callers that
                // persist into `places.category` keep their behaviour.
                $poi['category'] = $group;
                $results[$poi['geoapify_place_id']] = $poi;
            }
        }

        // Legacy tests seed/expect the *representative* child slugs too; the
        // transport group previously enumerated every child key, so replicate
        // that breadth via child-prefix expansion of `public_transport`.
        return array_values($results);
    }

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
     * Geoapify category slugs to synchronize, from the DB-managed catalogue.
     *
     * Falls back to the shipped defaults when the place_categories table does
     * not exist yet (installer / fresh boot / pre-migration).
     *
     * @return array<int, string>
     */
    public static function activeCategorySlugs(): array
    {
        try {
            $slugs = PlaceCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('slug')
                ->all();
        } catch (Throwable) {
            $slugs = [];
        }

        if ($slugs !== []) {
            return $slugs;
        }

        // Pre-migration fallback: the shipped seeder defaults.
        return array_column(PlaceCategorySeeder::defaults(), 'slug');
    }

    /**
     * Fetch candidate POIs for ONE category from the Places API.
     *
     * @param  string  $categorySlug  A place_categories.slug (raw Geoapify key)
     * @param  float  $lat  Property latitude
     * @param  float  $lng  Property longitude
     * @param  int|null  $radiusMetres  Candidate search radius (defaults to config)
     * @param  int|null  $limit  Max candidates for this category (defaults to config)
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
     * @throws RuntimeException on an API key error, persistent failure, or an
     *                          unparseable response
     */
    public function searchCategory(
        string $categorySlug,
        float $lat,
        float $lng,
        ?int $radiusMetres = null,
        ?int $limit = null
    ): array {
        $radius = $radiusMetres ?? $this->defaultRadius;
        $max = $limit ?? $this->defaultLimit;

        $response = $this->request('GET', self::PLACES_URL, [
            'categories' => $categorySlug,
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

            // SEC-007: sanitize the raw category BEFORE matching, so a hostile
            // payload cannot smuggle markup into `raw_category`.
            $rawCategory = $this->sanitize($props['categories'][0] ?? '', 255);
            $category = $this->matchCategory($rawCategory, $categorySlug);

            // Skip POIs whose category does not belong to the requested key.
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
     * Measure ACTUAL routed travel time/distance from the property to every
     * candidate POI for one mode.
     *
     * Uses the Route Matrix API, batched at 1000 target combinations per
     * request. Straight-line distance is NEVER used as a proxy: only routed
     * values from the provider are returned.
     *
     * @param  string  $mode  One of self::MODES
     * @param  float  $lat  Property latitude (the single matrix source)
     * @param  float  $lng  Property longitude
     * @param  array<int, array{geoapify_place_id: string, lat: float, lng: float}>  $targets
     * @return array<string, array{seconds: int|null, metres: int|null}>
     *                                                                   Keyed by geoapify_place_id. A target the router cannot reach maps to
     *                                                                   nulls and is excluded by the caller.
     *
     * @throws RuntimeException when the route calculation fails or the mode is unknown
     */
    public function matrixTimes(string $mode, float $lat, float $lng, array $targets): array
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new RuntimeException("Unknown travel mode: {$mode}");
        }

        $results = [];

        foreach (array_chunk($targets, self::MATRIX_BATCH_SIZE) as $chunk) {
            $payload = [
                'mode' => $mode,
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
     * Backwards-compatible alias for the walking matrix used by legacy tests.
     *
     * @param  array<int, array{geoapify_place_id: string, lat: float, lng: float}>  $targets
     * @return array<string, array{seconds: int|null, metres: int|null}>
     */
    public function walkingTimes(float $lat, float $lng, array $targets): array
    {
        return $this->matrixTimes('walk', $lat, $lng, $targets);
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
     * Match a raw Geoapify category string against the requested category slug.
     *
     * Matching is exact or by child prefix: `healthcare.hospital` matches itself,
     * while `public_transport.train.station` matches the `public_transport`
     * prefix (and the exact `public_transport` slug matches too). Unrelated
     * categories return null and are dropped.
     */
    private function matchCategory(string $rawCategory, string $requestedSlug): ?string
    {
        if ($rawCategory === '') {
            return null;
        }

        if ($rawCategory === $requestedSlug) {
            return $rawCategory;
        }

        if (str_starts_with($rawCategory, $requestedSlug.'.')) {
            return $rawCategory;
        }

        // The requested slug may itself be a parent (e.g. `public_transport`):
        // match the raw string's own top-level key against it.
        if (strtok($rawCategory, '.') === $requestedSlug) {
            return $rawCategory;
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
