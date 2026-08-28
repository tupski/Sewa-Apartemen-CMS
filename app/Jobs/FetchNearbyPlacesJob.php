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

/**
 * FetchNearbyPlacesJob
 *
 * Fetches Points of Interest from Geoapify for a given Property and persists
 * them in the `places` and `property_places` tables.
 *
 * IMPORTANT: This job must ONLY be dispatched explicitly from admin actions.
 * Never dispatch it from a controller, view, or any code that runs on a page render.
 */
class FetchNearbyPlacesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Earth radius in metres, used for the Haversine distance formula.
     */
    private const EARTH_RADIUS_METRES = 6371000;

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
    public int $timeout = 60;

    /**
     * @param  Property  $property  The property to fetch nearby places for.
     *                              SerializesModels handles lazy serialization.
     */
    public function __construct(
        protected Property $property,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $property = $this->property;

        Log::info("FetchNearbyPlacesJob: starting for property {$property->id} ({$property->name})");

        // --- Precondition: coordinates must be present ---
        if ($property->latitude === null || $property->longitude === null) {
            Log::warning(
                "FetchNearbyPlacesJob: skipping property {$property->id} — latitude/longitude not set"
            );

            return;
        }

        // --- Precondition: Geoapify API key must be configured ---
        if (empty(config('services.geoapify.key'))) {
            Log::warning(
                "FetchNearbyPlacesJob: skipping property {$property->id} — GEOAPIFY_API_KEY not configured"
            );

            return;
        }

        // --- SEC-002: atomic in-flight lock, one sync per property at a time ---
        // Both the configured `file` store and the test-suite `array` store
        // implement LockProvider, so Cache::lock() is a real atomic lock here.
        // A second concurrent resync for the same property early-returns instead of
        // making a duplicate paid upstream call.
        $lock = Cache::lock("geoapify_sync_{$property->id}", 120);

        if (! $lock->get()) {
            Log::info(
                "FetchNearbyPlacesJob: skipping property {$property->id} — a sync is already in progress"
            );

            return;
        }

        try {
            $this->syncPlaces($property);
        } finally {
            $lock->release();
        }
    }

    /**
     * Fetch the POIs for a property and persist them.
     *
     * Always invoked while the per-property sync lock is held (see handle()).
     */
    private function syncPlaces(Property $property): void
    {
        $lat = (float) $property->latitude;
        $lng = (float) $property->longitude;

        // --- Cache check (24-hour TTL) ---
        $cacheKey = "geoapify_places_{$property->id}";
        $ttl = 60 * 60 * 24; // 24 hours in seconds

        $pois = Cache::get($cacheKey);

        if ($pois === null) {
            // Cache miss — call the Geoapify API
            try {
                $service = new GeoapifyService;
                $pois = $service->fetchNearbyPlaces($lat, $lng);
            } catch (\RuntimeException $e) {
                Log::error(
                    "FetchNearbyPlacesJob: API error for property {$property->id} — {$e->getMessage()}"
                );

                return; // Do NOT cache a failure
            }

            // Store successful result in cache
            Cache::put($cacheKey, $pois, $ttl);
        }

        if (empty($pois)) {
            Log::info("FetchNearbyPlacesJob: synced 0 places for property {$property->id}");

            return;
        }

        // --- DB transaction: upsert places + pivot rows + stale cleanup ---
        $syncedPlaceIds = [];

        DB::transaction(function () use ($property, $pois, $lat, $lng, &$syncedPlaceIds): void {
            foreach ($pois as $poi) {
                // Guard: skip POIs without a Geoapify place ID
                if (empty($poi['geoapify_place_id'])) {
                    continue;
                }

                // Upsert the Place record (deduplicate on geoapify_place_id)
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

                // Upsert the PropertyPlace pivot row (deduplicate on property_id + place_id)
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
                    ]
                );
            }

            // Remove stale Geoapify rows that are no longer in the current result set.
            // Manual rows (source = 'manual') are never touched.
            if (! empty($syncedPlaceIds)) {
                PropertyPlace::where('property_id', $property->id)
                    ->where('source', 'geoapify')
                    ->whereNotIn('place_id', $syncedPlaceIds)
                    ->delete();
            }
        });

        $count = count($syncedPlaceIds);
        Log::info("FetchNearbyPlacesJob: synced {$count} places for property {$property->id}");
    }

    /**
     * Compute the Haversine great-circle distance between two WGS-84 coordinates.
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
