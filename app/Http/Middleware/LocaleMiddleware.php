<?php

namespace App\Http\Middleware;

use App\Services\GeoLocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Set the application locale per request.
     *
     * Rules:
     *  - Session locale (if set) wins — reserved for a future language switcher.
     *  - Otherwise: visitors from Indonesia get 'id', everyone else gets 'en'.
     *  - Unknown origin (private IP, API failure) falls back to the app default ('id').
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale')) {
            $locale = session('locale');
        } else {
            $country = GeoLocaleService::countryFor($request);

            if ($country === null) {
                $locale = config('app.locale', 'id');
            } else {
                $locale = $country === 'ID' ? 'id' : 'en';
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
