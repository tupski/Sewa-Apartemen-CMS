<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * GeoapifyService — fetches Points of Interest from the Geoapify Places API.
 *
 * IMPORTANT: This service is designed to be called ONLY from queue jobs.
 * Never call it from a view, controller, or any code that runs on a page render.
 *
 * Category mapping targets Property::NEARBY_CATEGORIES keys (display labels),
 * which are the canonical category identifiers used by the places system.
 */
class GeoapifyService
{
    /**
     * Geoapify Places API base URL.
     */
    private const API_URL = 'https://api.geoapify.com/v2/places';

    /**
     * Geoapify category strings to request, covering POI types relevant to apartment guests.
     */
    private const GEOAPIFY_CATEGORIES = [
        'catering.restaurant',
        'catering.fast_food',
        'catering.cafe',
        'commercial.supermarket',
        'commercial.shopping_mall',
        'commercial.convenience',
        'healthcare.hospital',
        'healthcare.pharmacy',
        'healthcare.clinic',
        'education.school',
        'education.university',
        'public_transport.bus',
        'public_transport.train',
        'public_transport.subway',
        'transport.airport',
        'leisure.park',
        'leisure.sports',
        'tourism.attraction',
        'tourism.museum',
    ];

    private string $apiKey;

    private int $defaultRadius;

    private int $defaultLimit;

    /**
     * @throws \RuntimeException if GEOAPIFY_API_KEY is not configured
     */
    public function __construct()
    {
        $config = config('services.geoapify');

        if (empty($config['key'])) {
            throw new \RuntimeException(
                'Geoapify API key is not configured. Set GEOAPIFY_API_KEY in .env'
            );
        }

        $this->apiKey = $config['key'];
        $this->defaultRadius = (int) ($config['radius'] ?? 2000);
        $this->defaultLimit = (int) ($config['max_results'] ?? 20);
    }

    /**
     * Fetch nearby Points of Interest from Geoapify Places API.
     *
     * @param  float  $lat  Latitude of the center point
     * @param  float  $lng  Longitude of the center point
     * @param  int|null  $radiusMetres  Search radius in metres (defaults to config value)
     * @param  int|null  $limit  Max results to return (defaults to config value)
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
     * @throws \RuntimeException on API key error, persistent failure, or JSON parse error
     */
    public function fetchNearbyPlaces(
        float $lat,
        float $lng,
        ?int $radiusMetres = null,
        ?int $limit = null
    ): array {
        $radius = $radiusMetres ?? $this->defaultRadius;
        $max = $limit ?? $this->defaultLimit;

        $categories = implode(',', self::GEOAPIFY_CATEGORIES);
        $filter = "circle:{$lng},{$lat},{$radius}";

        // SEC-004: a ConnectionException carries a Guzzle message that embeds the
        // full request URL — including the apiKey query parameter. Convert it into
        // a generic RuntimeException with NO original message and NO $previous
        // chaining, so the key can never reach a log line or a debug trace.
        try {
            $response = Http::timeout(10)
                ->connectTimeout(10)
                ->retry(2, 1000, function (\Throwable $e) {
                    // The retry callback's 2nd argument is the PendingRequest, not the
                    // Response — the failed response is reachable via the exception.
                    $failed = $e instanceof RequestException ? $e->response : null;

                    // Handle 429 rate limiting: wait Retry-After seconds then retry once.
                    // Checked before the generic 4xx short-circuit because 429 IS a 4xx.
                    if ($failed && $failed->status() === 429) {
                        $retryAfter = (int) ($failed->header('Retry-After') ?: 60);
                        sleep($retryAfter);

                        return true;
                    }

                    // Do not retry on 4xx responses (including 401/403 — API key errors)
                    if ($failed && $failed->status() >= 400 && $failed->status() < 500) {
                        return false;
                    }

                    // Retry on timeout or 5xx
                    return true;
                }, throw: false)
                ->get(self::API_URL, [
                    'categories' => $categories,
                    'filter' => $filter,
                    'limit' => $max,
                    'apiKey' => $this->apiKey,
                ]);
        } catch (ConnectionException) {
            throw new \RuntimeException('Geoapify API request failed: connection error');
        }

        // Handle auth failures immediately — no further retries
        if ($response->status() === 401 || $response->status() === 403) {
            throw new \RuntimeException('Geoapify API key invalid or quota exceeded');
        }

        // Persistent failure after retries
        if ($response->failed()) {
            throw new \RuntimeException(
                'Geoapify API request failed: '.$response->status()
            );
        }

        // Parse JSON
        $data = $response->json();

        if ($data === null) {
            throw new \RuntimeException('Geoapify returned invalid JSON');
        }

        // Graceful degradation — Geoapify may return 0 results with no features key
        if (! isset($data['features']) || ! is_array($data['features'])) {
            return [];
        }

        $results = [];

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            $geom = $feature['geometry'] ?? [];

            // Skip any POI without a name
            $name = $props['name'] ?? null;
            if (empty($name)) {
                continue;
            }

            // SEC-007: sanitize the raw category BEFORE the allowlist mapping, so a
            // hostile payload cannot smuggle markup into `raw_category`, while the
            // mapped label itself remains an exact NEARBY_CATEGORIES key.
            $rawCategory = $this->sanitize($props['categories'][0] ?? '', 255);
            $category = $this->mapCategory($rawCategory);

            // Skip POIs whose category cannot be mapped to a known category
            if ($category === null) {
                continue;
            }

            // GeoJSON coordinates are [longitude, latitude]
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
     * Map a raw Geoapify category string to a Property::NEARBY_CATEGORIES key.
     *
     * Uses first-match-wins logic with str_contains checks.
     * Returns null if no mapping exists (caller should skip such POIs).
     *
     * Mapped against the actual Property::NEARBY_CATEGORIES keys:
     *   'Mall/Shopping', 'Restaurant/Food', 'Transport', 'Education',
     *   'Hospital/Health', 'Recreation', 'Hotel', 'Nearby Places',
     *   'Transportation', 'Entertainment/Attraction', 'Others'
     */
    private function mapCategory(string $rawCategory): ?string
    {
        // Ordered mapping — first match wins
        $map = [
            'catering.restaurant' => 'Restaurant/Food',
            'catering.fast_food' => 'Restaurant/Food',
            'catering.cafe' => 'Restaurant/Food',
            'commercial.supermarket' => 'Nearby Places',
            'commercial.shopping_mall' => 'Mall/Shopping',
            'commercial.convenience' => 'Nearby Places',
            'healthcare.hospital' => 'Hospital/Health',
            'healthcare.pharmacy' => 'Hospital/Health',
            'healthcare.clinic' => 'Hospital/Health',
            'education' => 'Education',
            'public_transport.bus' => 'Transportation',
            'public_transport.train' => 'Transportation',
            'public_transport.subway' => 'Transportation',
            'transport.airport' => 'Transport',
            'leisure.park' => 'Recreation',
            'leisure.sports' => 'Recreation',
            'tourism' => 'Entertainment/Attraction',
        ];

        // Validate that all mapped values exist in Property::NEARBY_CATEGORIES
        $validCategories = array_keys(Property::NEARBY_CATEGORIES);

        foreach ($map as $geoapifyPrefix => $targetCategory) {
            if (str_contains($rawCategory, $geoapifyPrefix)) {
                // Guard: only return if the target is a known category key
                if (in_array($targetCategory, $validCategories, true)) {
                    return $targetCategory;
                }
            }
        }

        return null;
    }
}
