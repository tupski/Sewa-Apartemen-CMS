<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Services\GeoLocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Set the application locale per request.
     *
     * Resolution order:
     *  1. Session locale (set via language switcher in admin/frontend).
     *  2. Default language from DB languages table.
     *  3. Geo fallback: ID → 'id', everyone else → 'en'.
     *  4. App default locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale')) {
            $locale = session('locale');
        } else {
            // Try DB-driven default language
            try {
                $default = Language::where('is_default', true)->where('is_active', true)->value('code');
                if ($default) {
                    $locale = $default;
                } else {
                    // Geo fallback
                    $country = GeoLocaleService::countryFor($request);
                    $locale  = $country === null
                        ? config('app.locale', 'id')
                        : ($country === 'ID' ? 'id' : 'en');
                }
            } catch (\Throwable $e) {
                // DB not ready (e.g. fresh install)
                $country = GeoLocaleService::countryFor($request);
                $locale  = $country === null
                    ? config('app.locale', 'id')
                    : ($country === 'ID' ? 'id' : 'en');
            }
        }

        // Validate against active languages — prevent setting an unknown locale
        $locale = $this->validateLocale($locale);

        app()->setLocale($locale);
        return $next($request);
    }

    private function validateLocale(string $locale): string
    {
        try {
            $codes = Language::where('is_active', true)->pluck('code')->toArray();
            return in_array($locale, $codes) ? $locale : (Language::where('is_default', true)->value('code') ?? config('app.locale', 'id'));
        } catch (\Throwable $e) {
            return $locale;
        }
    }
}
