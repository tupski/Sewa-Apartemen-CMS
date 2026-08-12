<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoLocaleService
{
    /**
     * Determine the visitor's country code (ISO 3166-1 alpha-2).
     *
     * Resolution order:
     *  1. Cloudflare's CF-IPCountry header (free, when behind Cloudflare)
     *  2. ip-api.com free endpoint (no API key; HTTP only)
     *
     * Returns null when the country cannot be determined (private IP, API down, etc.).
     */
    public static function countryFor(Request $request): ?string
    {
        if ($header = $request->header('CF-IPCountry')) {
            return strtoupper($header);
        }

        $ip = $request->ip();

        if (self::isPrivate($ip)) {
            return null;
        }

        $cacheKey = 'geo.country.' . md5($ip);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($ip) {
            try {
                $response = Http::timeout(3)
                    ->get('http://ip-api.com/json/' . $ip)
                    ->json();

                return ($response['status'] ?? '') === 'success'
                    ? strtoupper($response['countryCode'])
                    : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    /**
     * True for loopback/private/reserved addresses (no geo lookup needed).
     */
    protected static function isPrivate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
