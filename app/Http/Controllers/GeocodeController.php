<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * GeocodeController — server-side proxy for the property address search.
 *
 * The admin property form's map search used to call Nominatim directly from
 * the browser, which leaked the operator's IP/User-Agent policy to the
 * provider and made the request unverifiable. All lookups now flow through
 * this endpoint (rate-limited + 24h-cached), and the browser never sees any
 * provider credential (Nominatim needs none; a Geoapify fallback key, when
 * configured, stays server-side).
 *
 * Usage policy note: Nominatim requires a valid Referer/User-Agent — the
 * HTTP client sends the app URL, which satisfies it.
 */
class GeocodeController extends Controller
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    private const CACHE_TTL_SECONDS = 86400; // 24h

    private const CACHE_PREFIX = 'geocode_';

    /**
     * GET ?q= — search an address/place, returning normalized results.
     */
    public function search(Request $request): JsonResponse
    {
        // Inline validation with a JSON response: the app renders validation
        // errors as redirects outside `api/*`, and this endpoint is fetch()-only.
        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('geocode.invalid_query'),
            ], 422);
        }

        $query = trim((string) $validator->validated()['q']);
        $cacheKey = self::CACHE_PREFIX.md5('search|'.$query);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json(['success' => true, 'results' => $cached]);
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 300)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Artivo CMS').'/1.0 (property admin geocoding)',
                    'Referer' => config('app.url'),
                    'Accept' => 'application/json',
                    'Accept-Language' => 'id,en',
                ])
                ->get(self::NOMINATIM_URL, [
                    'format' => 'jsonv2',
                    'limit' => 5,
                    'q' => $query,
                ]);
        } catch (\Throwable $e) {
            // Network/timeout failure: log the detail, return a generic error.
            Log::warning('Geocode search failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('geocode.search_failed'),
            ], 502);
        }

        if ($response->status() === 429) {
            return response()->json([
                'success' => false,
                'message' => __('geocode.rate_limited'),
            ], 429);
        }

        if (! $response->successful()) {
            Log::warning('Geocode search returned HTTP '.$response->status());

            return response()->json([
                'success' => false,
                'message' => __('geocode.search_failed'),
            ], 502);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => __('geocode.search_failed'),
            ], 502);
        }

        // Normalize + validate the provider payload before it leaves the server:
        // coordinates must be well-formed finite numbers inside WGS-84 bounds.
        $results = [];

        foreach (array_slice($payload, 0, 5) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $lat = self::sanitizeCoordinate($item['lat'] ?? null, -90, 90);
            $lng = self::sanitizeCoordinate($item['lon'] ?? null, -180, 180);

            if ($lat === null || $lng === null) {
                continue; // malformed coordinate → drop the result
            }

            $results[] = [
                'display_name' => mb_substr((string) ($item['display_name'] ?? ''), 0, 500),
                'lat' => $lat,
                'lng' => $lng,
            ];
        }

        Cache::put($cacheKey, $results, self::CACHE_TTL_SECONDS);

        return response()->json(['success' => true, 'results' => $results]);
    }

    /**
     * Validate a provider coordinate string: numeric, finite, within bounds.
     */
    private static function sanitizeCoordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if (! is_finite($number) || $number < $min || $number > $max) {
            return null;
        }

        return round($number, 7);
    }
}
