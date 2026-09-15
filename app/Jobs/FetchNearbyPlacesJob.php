<?php

namespace App\Jobs;

use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Services\GeoapifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * FetchNearbyPlacesJob
 *
 * The single owner of the Geoapify POI pipeline for one property:
 *
 *   coordinates
 *     -> per-category Places search (all active place_categories slugs)
 *     -> deduplicate candidates by Geoapify place id
 *     -> per-mode Route Matrix (walk / drive / motorcycle) with budget filters
 *     -> upsert `places` + `property_places`
 *     -> return a structured result for the admin UI
 *
 * IMPORTANT: this job is the ONLY caller of GeoapifyService, and it is only
 * dispatched from the admin POI sync action — never from a page render.
 *
 * Presentation overrides (custom_name / show_on_frontend) on existing pivot
 * rows are PRESERVED: the sync never overwrites or deletes an association just
 * because the provider stopped returning the POI (only genuinely stale rows
 * from successfully-searched categories are replaced).
 */
class FetchNearbyPlacesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Earth radius in metres, used for the Haversine straight-line distance.
     */
    private const EARTH_RADIUS_METRES = 6371000;

    /**
     * How long a successful sync result is cached, in seconds (24 hours).
     */
    private const CACHE_TTL_SECONDS = 86400;

    /**
     * Cache-key prefix for the structured result of the last sync run.
     *
     * The result is cached (short TTL) because Laravel's synchronous dispatch
     * discards the job's return value, so the admin endpoint reads the outcome
     * back from the cache instead.
     */
    private const RESULT_CACHE_PREFIX = 'geoapify_sync_result_';

    /**
     * How long a sync result stays readable by the calling request, in seconds.
     */
    private const RESULT_TTL_SECONDS = 300;

    /**
     * Maximum number of attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Backoff intervals (in seconds) between retries.
     * Retries at 30s, 2 min, 5 min.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * The number of seconds the job may run before it is killed.
     */
    public int $timeout = 300;

    /**
     * @param  Property  $property  The property to fetch nearby places for.
     *                              SerializesModels handles lazy serialization.
     * @param  bool  $measureMotorcycle  Geoapify Route Matrix has no dedicated
     *                                   motorcycle profile — it is approximated
     *                                   with driving routes. When false, the
     *                                   motorcycle mode is skipped entirely and
     *                                   its columns stay NULL (no fabricated data).
     */
    public function __construct(
        protected Property $property,
        protected bool $measureMotorcycle = true,
    ) {}

    /**
     * Execute the job.
     *
     * @return array{
     *     success: bool,
     *     reason: string|null,
     *     categories: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     new: int,
     *     budgets: array<string, array{seconds: int, metres: int}>
     * }
     */
    public function handle(): array
    {
        $result = $this->run();

        // Laravel's synchronous dispatch throws the return value away, so the
        // result is also cached for the calling admin request to read back.
        Cache::put(self::resultCacheKey($this->property->id), $result, self::RESULT_TTL_SECONDS);

        return $result;
    }

    /**
     * Cache key holding the structured result of the last sync for a property.
     */
    public static function resultCacheKey(int $propertyId): string
    {
        return self::RESULT_CACHE_PREFIX.$propertyId;
    }

    /**
     * Run the sync and return its structured result.
     *
     * @return array{
     *     success: bool,
     *     reason: string|null,
     *     categories: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     new: int,
     *     budgets: array<string, array{seconds: int, metres: int}>
     * }
     */
    private function run(): array
    {
        $property = $this->property;

        Log::info("FetchNearbyPlacesJob: starting for property {$property->id} ({$property->name})");

        // --- Precondition: coordinates must be present ---
        if ($property->latitude === null || $property->longitude === null) {
            Log::warning(
                "FetchNearbyPlacesJob: skipping property {$property->id} — latitude/longitude not set"
            );

            return $this->failedResult('missing_coordinates');
        }

        // --- Precondition: a Geoapify API key must be configured ---
        if (! GeoapifyService::isConfigured()) {
            Log::warning(
                "FetchNearbyPlacesJob: skipping property {$property->id} — no Geoapify API key configured"
            );

            return $this->failedResult('missing_api_key');
        }

        // --- SEC-002: atomic in-flight lock, one sync per property at a time ---
        // Both the configured `file` store and the test-suite `array` store
        // implement LockProvider, so Cache::lock() is a real atomic lock here.
        // A second concurrent sync for the same property early-returns instead of
        // making a duplicate paid upstream call.
        $lock = Cache::lock("geoapify_sync_{$property->id}", 300);

        if (! $lock->get()) {
            Log::info(
                "FetchNearbyPlacesJob: skipping property {$property->id} — a sync is already in progress"
            );

            return $this->failedResult('busy');
        }

        try {
            return $this->syncPlaces($property);
        } finally {
            $lock->release();
        }
    }

    /**
     * Fetch, filter and persist the POIs for a property.
     *
     * Always invoked while the per-property sync lock is held (see handle()).
     *
     * @return array{
     *     success: bool,
     *     reason: string|null,
     *     categories: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     new: int,
     *     budgets: array<string, array{seconds: int, metres: int}>
     * }
     */
    private function syncPlaces(Property $property): array
    {
        $lat = (float) $property->latitude;
        $lng = (float) $property->longitude;

        $cacheKey = "geoapify_places_{$property->id}";

        // --- Cache check (24-hour TTL) ---
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['pois'], $cached['categories'], $cached['found'])) {
            /** @var array<int, array<string, mixed>> $pois */
            $pois = $cached['pois'];
            /** @var array<string, array{status: string, found: int, kept: int, error: string|null}> $categoryStatus */
            $categoryStatus = $cached['categories'];
            $found = (int) $cached['found'];
        } else {
            $search = $this->searchCandidates($property, $lat, $lng);

            // A walk-route failure means no candidate can be validated against the
            // walking budget, so nothing is persisted and existing rows are left
            // untouched.
            if ($search['reason'] === 'route_failed') {
                return $this->failedResult('route_failed', $search['categories'], $search['found']);
            }

            $pois = $search['pois'];
            $categoryStatus = $search['categories'];
            $found = $search['found'];

            // Cache only a complete, budget-filtered result. Failures are never cached.
            if ($search['failed_categories'] === []) {
                Cache::put($cacheKey, [
                    'pois' => $pois,
                    'categories' => $categoryStatus,
                    'found' => $found,
                ], self::CACHE_TTL_SECONDS);
            }
        }

        $persist = $this->persist($property, $pois, $categoryStatus, $lat, $lng);

        $failedCategories = array_keys(array_filter(
            $categoryStatus,
            fn (array $status): bool => $status['status'] !== 'ok'
        ));

        Log::info(
            "FetchNearbyPlacesJob: synced {$persist['synced']} of {$found} candidates for property {$property->id}"
            .($failedCategories === [] ? '' : ' (failed categories: '.implode(', ', $failedCategories).')')
        );

        return [
            'success' => $failedCategories === [],
            'reason' => $failedCategories === [] ? null : 'partial',
            'categories' => $categoryStatus,
            'found' => $found,
            'synced' => $persist['synced'],
            'new' => $persist['new'],
            'budgets' => $this->budgetPayload(),
        ];
    }

    /**
     * The reachability budgets used by this sync, for the admin UI.
     *
     * @return array<string, array{seconds: int, metres: int}>
     */
    private function budgetPayload(): array
    {
        $budgets = [];

        foreach (GeoapifyService::MODES as $mode) {
            [$seconds, $metres] = GeoapifyService::MODE_BUDGETS[$mode];

            if ($mode === 'motorcycle' && ! $this->measureMotorcycle) {
                continue;
            }

            $budgets[$mode] = ['seconds' => $seconds, 'metres' => $metres];
        }

        return $budgets;
    }

    /**
     * Search POI categories, deduplicate candidates, then apply the
     * per-mode routed-travel budgets (walk / drive / motorcycle).
     *
     * A failure in one category never discards the other categories' results.
     *
     * @param  array<int, string>|null  $categorySlugs  Explicit category slugs to
     *                                                  search; defaults to all
     *                                                  active DB categories.
     * @return array{pois: array<int, array<string, mixed>>, categories: array<string, array{status: string, found: int, kept: int, error: string|null}>, found: int, failed_categories: array<int, string>, reason: string|null}
     */
    private function searchCandidates(Property $property, float $lat, float $lng, ?array $categorySlugs = null): array
    {
        $service = new GeoapifyService;

        $categoryStatus = [];
        $candidates = [];
        $found = 0;
        $failedCategories = [];

        $slugs = $categorySlugs ?? GeoapifyService::activeCategorySlugs();

        foreach ($slugs as $slug) {
            try {
                $categoryPois = $service->searchCategory($slug, $lat, $lng);
            } catch (RuntimeException $e) {
                // SEC-005: the service already strips URLs/keys from its messages.
                Log::error(
                    "FetchNearbyPlacesJob: category '{$slug}' failed for property {$property->id} — {$e->getMessage()}"
                );

                $categoryStatus[$slug] = ['status' => 'failed', 'found' => 0, 'kept' => 0, 'error' => $e->getMessage()];
                $failedCategories[] = $slug;

                continue;
            }

            $categoryStatus[$slug] = ['status' => 'ok', 'found' => count($categoryPois), 'kept' => 0, 'error' => null];
            $found += count($categoryPois);

            foreach ($categoryPois as $poi) {
                if (empty($poi['geoapify_place_id'])) {
                    continue;
                }

                // Deduplicate: the same venue can be returned by more than one
                // category (e.g. a museum that is also a tourism POI).
                $candidates[$poi['geoapify_place_id']] = $poi;
            }
        }

        if ($candidates === []) {
            return [
                'pois' => [],
                'categories' => $categoryStatus,
                'found' => $found,
                'failed_categories' => $failedCategories,
                'reason' => null,
            ];
        }

        // --- Per-mode routed-travel filtering (ACTUAL routed values, not distance) ---
        $targets = array_values($candidates);
        $measured = [];

        foreach (GeoapifyService::MODES as $mode) {
            // Provider limitation: Geoapify Route Matrix has no dedicated motorcycle
            // profile. When disabled, the columns stay NULL instead of being filled
            // with a fabricated driving approximation.
            if ($mode === 'motorcycle' && ! $this->measureMotorcycle) {
                continue;
            }

            try {
                $measured[$mode] = $service->matrixTimes($mode, $lat, $lng, $targets);
            } catch (RuntimeException $e) {
                Log::error(
                    "FetchNearbyPlacesJob: {$mode} route matrix failed for property {$property->id} — {$e->getMessage()}"
                );

                // Walk is the primary filter: without it no candidate qualifies.
                if ($mode === 'walk') {
                    return [
                        'pois' => [],
                        'categories' => $categoryStatus,
                        'found' => $found,
                        'failed_categories' => $failedCategories,
                        'reason' => 'route_failed',
                    ];
                }

                // Non-walk failure degrades gracefully: those columns stay NULL.
                continue;
            }
        }

        $kept = [];

        [$walkMaxSeconds, $walkMaxMetres] = GeoapifyService::MODE_BUDGETS['walk'];
        [$driveMaxSeconds, $driveMaxMetres] = GeoapifyService::MODE_BUDGETS['drive'];
        [$motoMaxSeconds, $motoMaxMetres] = GeoapifyService::MODE_BUDGETS['motorcycle'];

        foreach ($candidates as $placeId => $poi) {
            $walk = $measured['walk'][$placeId] ?? null;

            // Unreachable on foot, or outside the walking budget.
            if ($walk === null || $walk['seconds'] === null
                || $walk['seconds'] > $walkMaxSeconds
                || ($walk['metres'] !== null && $walk['metres'] > $walkMaxMetres)) {
                continue;
            }

            $poi['walking_duration_s'] = $walk['seconds'];
            $poi['walking_distance_m'] = $walk['metres'];

            $drive = $measured['drive'][$placeId] ?? null;
            if ($drive !== null && $drive['seconds'] !== null
                && $drive['seconds'] <= $driveMaxSeconds
                && ($drive['metres'] === null || $drive['metres'] <= $driveMaxMetres)) {
                $poi['driving_duration_s'] = $drive['seconds'];
                $poi['driving_distance_m'] = $drive['metres'];
            } else {
                $poi['driving_duration_s'] = null;
                $poi['driving_distance_m'] = null;
            }

            $moto = $measured['motorcycle'][$placeId] ?? null;
            if ($moto !== null && $moto['seconds'] !== null
                && $moto['seconds'] <= $motoMaxSeconds
                && ($moto['metres'] === null || $moto['metres'] <= $motoMaxMetres)) {
                $poi['motorcycle_duration_s'] = $moto['seconds'];
                $poi['motorcycle_distance_m'] = $moto['metres'];
            } else {
                $poi['motorcycle_duration_s'] = null;
                $poi['motorcycle_distance_m'] = null;
            }

            $kept[$placeId] = $poi;

            $categoryKey = $poi['category'];
            if (isset($categoryStatus[$categoryKey])) {
                $categoryStatus[$categoryKey]['kept']++;
            }
        }

        return [
            'pois' => array_values($kept),
            'categories' => $categoryStatus,
            'found' => $found,
            'failed_categories' => $failedCategories,
            'reason' => null,
        ];
    }

    /**
     * Upsert the surviving POIs and remove stale Geoapify rows.
     *
     * Presentation overrides (`custom_name`, `show_on_frontend`) on existing
     * pivot rows are never overwritten — only the measured travel data and the
     * raw place record are refreshed.
     *
     * @param  array<int, array<string, mixed>>  $pois
     * @param  array<string, array{status: string, found: int, kept: int, error: string|null}>  $categoryStatus
     * @return array{synced: int, new: int}
     */
    private function persist(Property $property, array $pois, array $categoryStatus, float $lat, float $lng): array
    {
        // Categories that could not be searched keep their existing rows: a partial
        // failure must not destroy data that was collected successfully before.
        $protectedCategories = array_keys(array_filter(
            $categoryStatus,
            fn (array $status): bool => $status['status'] !== 'ok'
        ));

        $syncedPlaceIds = [];
        $newCount = 0;

        DB::transaction(function () use ($property, $pois, $lat, $lng, &$syncedPlaceIds, &$newCount): void {
            foreach ($pois as $poi) {
                // Guard: skip POIs without a Geoapify place ID.
                if (empty($poi['geoapify_place_id'])) {
                    continue;
                }

                $place = Place::updateOrCreate(
                    ['geoapify_place_id' => $poi['geoapify_place_id']],
                    [
                        'name' => $poi['name'],
                        'category' => $poi['category'],
                        'lat' => $poi['lat'],
                        'lng' => $poi['lng'],
                        'address' => $poi['address'],
                        'website' => $poi['website'],
                        'phone' => $poi['phone'],
                        'raw_category' => $poi['raw_category'],
                        'fetched_at' => now(),
                    ]
                );

                $existing = PropertyPlace::query()
                    ->where('property_id', $property->id)
                    ->where('place_id', $place->id)
                    ->first();

                $isNew = $existing === null;

                // updateOrCreate with an override-safe payload: the presentation
                // columns are deliberately ABSENT so a re-sync can never clobber
                // the admin's custom name or show/hide choice.
                $pivot = PropertyPlace::updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'place_id' => $place->id,
                    ],
                    [
                        'source' => 'geoapify',
                        'distance_m' => $this->calculateDistanceMetres(
                            $lat,
                            $lng,
                            (float) $place->lat,
                            (float) $place->lng
                        ),
                        'walking_distance_m' => $poi['walking_distance_m'] ?? null,
                        'walking_duration_s' => $poi['walking_duration_s'] ?? null,
                        'driving_distance_m' => $poi['driving_distance_m'] ?? null,
                        'driving_duration_s' => $poi['driving_duration_s'] ?? null,
                        'motorcycle_distance_m' => $poi['motorcycle_distance_m'] ?? null,
                        'motorcycle_duration_s' => $poi['motorcycle_duration_s'] ?? null,
                    ]
                );

                $syncedPlaceIds[] = $place->id;

                if ($isNew) {
                    $newCount++;
                }
            }
        });

        // Replacement synchronization, scoped to the categories that actually ran.
        // Manual rows (source = 'manual') are never touched.
        if ($protectedCategories !== array_keys($categoryStatus)) {
            $stale = PropertyPlace::where('property_id', $property->id)
                ->where('source', 'geoapify')
                ->whereNotIn('place_id', $syncedPlaceIds === [] ? [0] : $syncedPlaceIds);

            if ($protectedCategories !== []) {
                $stale->whereHas('place', fn ($query) => $query->whereNotIn('category', $protectedCategories));
            }

            $stale->delete();
        }

        return ['synced' => count($syncedPlaceIds), 'new' => $newCount];
    }

    /**
     * A result array describing a sync that could not run at all.
     *
     * @param  array<string, array{status: string, found: int, kept: int, error: string|null}>  $categories
     * @return array{
     *     success: bool,
     *     reason: string,
     *     categories: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     new: int,
     *     budgets: array<string, array{seconds: int, metres: int}>
     * }
     */
    private function failedResult(string $reason, array $categories = [], int $found = 0): array
    {
        return [
            'success' => false,
            'reason' => $reason,
            'categories' => $categories,
            'found' => $found,
            'synced' => 0,
            'new' => 0,
            'budgets' => $this->budgetPayload(),
        ];
    }

    /**
     * Compute the Haversine great-circle distance between two WGS-84 coordinates.
     *
     * Kept as a straight-line reference value; the budget rule is applied to
     * the routed travel times instead (see matrixTimes()).
     *
     * @param  float  $lat1  Latitude of point 1 in decimal degrees.
     * @param  float  $lng1  Longitude of point 1 in decimal degrees.
     * @param  float  $lat2  Latitude of point 2 in decimal degrees.
     * @param  float  $lng2  Longitude of point 2 in decimal degrees.
     * @return int Distance in metres, rounded to nearest integer.
     */
    private function calculateDistanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round(self::EARTH_RADIUS_METRES * $c);
    }
}
