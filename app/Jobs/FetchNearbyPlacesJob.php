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
 *     -> per-group Places search (shopping / hospital / transportation)
 *     -> deduplicate candidates by Geoapify place id
 *     -> Route Matrix walking times (mode=walk)
 *     -> keep only POIs whose routed walking time is <= 10 minutes
 *     -> upsert `places` + `property_places`
 *     -> return a structured result for the admin UI
 *
 * IMPORTANT: this job is the ONLY caller of GeoapifyService, and it is only
 * dispatched from the admin POI sync action — never from a page render.
 *
 * handle() returns a result array (Laravel's synchronous dispatch returns it to
 * the caller), so the admin endpoint can report exact counts and per-group
 * failures without a second round trip.
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
    public int $timeout = 120;

    /**
     * @param  Property  $property  The property to fetch nearby places for.
     *                              SerializesModels handles lazy serialization.
     */
    public function __construct(
        protected Property $property,
    ) {}

    /**
     * Execute the job.
     *
     * @return array{
     *     success: bool,
     *     reason: string|null,
     *     groups: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     walk_max_seconds: int
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
     *     groups: array<string, array{status: string, found: int, kept: int, error: string|null}>,
     *     found: int,
     *     synced: int,
     *     walk_max_seconds: int
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
        $lock = Cache::lock("geoapify_sync_{$property->id}", 120);

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
     * Fetch, walk-filter and persist the POIs for a property.
     *
     * Always invoked while the per-property sync lock is held (see handle()).
     *
     * @return array{success: bool, reason: string|null, groups: array<string, array{status: string, found: int, kept: int, error: string|null}>, found: int, synced: int, walk_max_seconds: int}
     */
    private function syncPlaces(Property $property): array
    {
        $lat = (float) $property->latitude;
        $lng = (float) $property->longitude;

        $cacheKey = "geoapify_places_{$property->id}";

        // --- Cache check (24-hour TTL) ---
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['pois'], $cached['groups'], $cached['found'])) {
            /** @var array<int, array<string, mixed>> $pois */
            $pois = $cached['pois'];
            /** @var array<string, array{status: string, found: int, kept: int, error: string|null}> $groupStatus */
            $groupStatus = $cached['groups'];
            $found = (int) $cached['found'];
        } else {
            $search = $this->searchCandidates($property, $lat, $lng);

            // A route-calculation failure means no candidate can be validated
            // against the 10-minute walking rule, so nothing is persisted and the
            // existing rows are left untouched.
            if ($search['reason'] === 'route_failed') {
                return $this->failedResult('route_failed', $search['groups'], $search['found']);
            }

            $pois = $search['pois'];
            $groupStatus = $search['groups'];
            $found = $search['found'];

            // Cache only a complete, walk-filtered result. Failures are never cached.
            if ($search['failed_groups'] === []) {
                Cache::put($cacheKey, [
                    'pois' => $pois,
                    'groups' => $groupStatus,
                    'found' => $found,
                ], self::CACHE_TTL_SECONDS);
            }
        }

        $synced = $this->persist($property, $pois, $groupStatus, $lat, $lng);

        $failedGroups = array_keys(array_filter(
            $groupStatus,
            fn (array $status): bool => $status['status'] !== 'ok'
        ));

        Log::info(
            "FetchNearbyPlacesJob: synced {$synced} of {$found} candidates for property {$property->id}"
            .($failedGroups === [] ? '' : ' (failed groups: '.implode(', ', $failedGroups).')')
        );

        return [
            'success' => $failedGroups === [],
            'reason' => $failedGroups === [] ? null : 'partial',
            'groups' => $groupStatus,
            'found' => $found,
            'synced' => $synced,
            'walk_max_seconds' => GeoapifyService::WALK_MAX_SECONDS,
        ];
    }

    /**
     * Search every POI group, deduplicate the candidates, then apply the
     * 10-minute walking-time filter using real routed travel times.
     *
     * A failure in one group never discards the other groups' results.
     *
     * @return array{pois: array<int, array<string, mixed>>, groups: array<string, array{status: string, found: int, kept: int, error: string|null}>, found: int, failed_groups: array<int, string>, reason: string|null}
     */
    private function searchCandidates(Property $property, float $lat, float $lng): array
    {
        $service = new GeoapifyService;

        $groupStatus = [];
        $candidates = [];
        $found = 0;
        $failedGroups = [];

        foreach (array_keys(GeoapifyService::POI_GROUPS) as $group) {
            try {
                $groupPois = $service->searchGroup($group, $lat, $lng);
            } catch (RuntimeException $e) {
                // SEC-005: the service already strips URLs/keys from its messages.
                Log::error(
                    "FetchNearbyPlacesJob: group '{$group}' failed for property {$property->id} — {$e->getMessage()}"
                );

                $groupStatus[$group] = ['status' => 'failed', 'found' => 0, 'kept' => 0, 'error' => $e->getMessage()];
                $failedGroups[] = $group;

                continue;
            }

            $groupStatus[$group] = ['status' => 'ok', 'found' => count($groupPois), 'kept' => 0, 'error' => null];
            $found += count($groupPois);

            foreach ($groupPois as $poi) {
                if (empty($poi['geoapify_place_id'])) {
                    continue;
                }

                // Deduplicate: the same venue can be returned by more than one
                // category (e.g. a mall that is also a department store).
                $candidates[$poi['geoapify_place_id']] = $poi;
            }
        }

        if ($candidates === []) {
            return [
                'pois' => [],
                'groups' => $groupStatus,
                'found' => $found,
                'failed_groups' => $failedGroups,
                'reason' => null,
            ];
        }

        // --- Walking-time filtering (ACTUAL routed travel time, not distance) ---
        try {
            $walking = $service->walkingTimes($lat, $lng, array_values($candidates));
        } catch (RuntimeException $e) {
            Log::error(
                "FetchNearbyPlacesJob: route matrix failed for property {$property->id} — {$e->getMessage()}"
            );

            return [
                'pois' => [],
                'groups' => $groupStatus,
                'found' => $found,
                'failed_groups' => $failedGroups,
                'reason' => 'route_failed',
            ];
        }

        $kept = [];

        foreach ($candidates as $placeId => $poi) {
            $walk = $walking[$placeId] ?? null;

            // Unreachable on foot, or over the 10-minute walking budget.
            if ($walk === null || $walk['seconds'] === null || $walk['seconds'] > GeoapifyService::WALK_MAX_SECONDS) {
                continue;
            }

            $poi['walking_duration_s'] = $walk['seconds'];
            $poi['walking_distance_m'] = $walk['metres'];
            $kept[$placeId] = $poi;

            $groupStatus[$poi['category']]['kept']++;
        }

        return [
            'pois' => array_values($kept),
            'groups' => $groupStatus,
            'found' => $found,
            'failed_groups' => $failedGroups,
            'reason' => null,
        ];
    }

    /**
     * Upsert the surviving POIs and remove stale Geoapify rows.
     *
     * @param  array<int, array<string, mixed>>  $pois
     * @param  array<string, array{status: string, found: int, kept: int, error: string|null}>  $groupStatus
     * @return int number of persisted pivot rows
     */
    private function persist(Property $property, array $pois, array $groupStatus, float $lat, float $lng): int
    {
        // Groups that could not be searched keep their existing rows: a partial
        // failure must not destroy data that was collected successfully before.
        $protectedGroups = array_keys(array_filter(
            $groupStatus,
            fn (array $status): bool => $status['status'] !== 'ok'
        ));

        $syncedPlaceIds = [];

        DB::transaction(function () use ($property, $pois, $lat, $lng, &$syncedPlaceIds): void {
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

                $syncedPlaceIds[] = $place->id;

                PropertyPlace::updateOrCreate(
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
                    ]
                );
            }
        });

        // Replacement synchronization, scoped to the groups that actually ran.
        // Manual rows (source = 'manual') are never touched.
        if ($protectedGroups !== array_keys($groupStatus)) {
            $stale = PropertyPlace::where('property_id', $property->id)
                ->where('source', 'geoapify')
                ->whereNotIn('place_id', $syncedPlaceIds === [] ? [0] : $syncedPlaceIds);

            if ($protectedGroups !== []) {
                $stale->whereHas('place', fn ($query) => $query->whereNotIn('category', $protectedGroups));
            }

            $stale->delete();
        }

        return count($syncedPlaceIds);
    }

    /**
     * A result array describing a sync that could not run at all.
     *
     * @param  array<string, array{status: string, found: int, kept: int, error: string|null}>  $groups
     * @return array{success: bool, reason: string, groups: array<string, array{status: string, found: int, kept: int, error: string|null}>, found: int, synced: int, walk_max_seconds: int}
     */
    private function failedResult(string $reason, array $groups = [], int $found = 0): array
    {
        return [
            'success' => false,
            'reason' => $reason,
            'groups' => $groups,
            'found' => $found,
            'synced' => 0,
            'walk_max_seconds' => GeoapifyService::WALK_MAX_SECONDS,
        ];
    }

    /**
     * Compute the Haversine great-circle distance between two WGS-84 coordinates.
     *
     * Kept as a straight-line reference value; the 10-minute rule is applied to
     * the routed walking time instead (see walkingTimes()).
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
