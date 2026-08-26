<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    // Cache 6 jam = fetch maks 4x/hari
    const CACHE_TTL_HOURS = 6;
    const CACHE_KEY       = 'currency_rates_all';

    /**
     * Ambil semua rates (dari cache atau DB).
     * Format return: ['USD' => 15500.0, 'SGD' => 11500.0, ...]  (IDR sebagai base)
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(self::CACHE_TTL_HOURS), function () {
            return CurrencyRate::pluck('rate', 'to_currency')->toArray();
        });
    }

    /**
     * Convert amount dari IDR ke currency tujuan.
     */
    public static function convert(float $amountIdr, string $toCurrency): ?float
    {
        if (strtoupper($toCurrency) === 'IDR') return $amountIdr;
        $rates = self::all();
        return isset($rates[$toCurrency]) ? round($amountIdr / $rates[$toCurrency], 2) : null;
    }

    /**
     * Fetch rates dari API dan simpan ke DB + bust cache.
     * Dipanggil oleh scheduled command (4x/hari via cron).
     *
     * Provider: exchangerate-api.com (free tier, no key needed for IDR base)
     * Fallback: frankfurter.app (free, no key)
     */
    public static function fetchAndStore(?array $currencies = null): bool
    {
        $apiKey    = SettingsService::get('currency_api_key', '');
        $apiUrl    = SettingsService::get('currency_api_url', '');
        $currencies = $currencies ?? self::targetCurrencies();

        try {
            $rates = self::fetchFromApi($apiKey, $apiUrl, $currencies);

            if (empty($rates)) return false;

            foreach ($rates as $code => $rate) {
                CurrencyRate::updateOrCreate(
                    ['from_currency' => 'IDR', 'to_currency' => $code],
                    ['rate' => $rate, 'source' => 'api', 'fetched_at' => now()]
                );
            }

            Cache::forget(self::CACHE_KEY);
            Log::info('CurrencyRateService: rates updated', ['currencies' => array_keys($rates)]);
            return true;

        } catch (\Throwable $e) {
            Log::error('CurrencyRateService: fetch failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Simpan rate manual (dari admin UI).
     */
    public static function setManual(string $toCurrency, float $rate): void
    {
        CurrencyRate::updateOrCreate(
            ['from_currency' => 'IDR', 'to_currency' => strtoupper($toCurrency)],
            ['rate' => $rate, 'source' => 'manual', 'fetched_at' => now()]
        );
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Hapus rate.
     */
    public static function delete(string $toCurrency): void
    {
        CurrencyRate::where('to_currency', strtoupper($toCurrency))->delete();
        Cache::forget(self::CACHE_KEY);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private static function fetchFromApi(string $apiKey, string $apiUrl, array $currencies): array
    {
        $symbols = implode(',', $currencies);

        // 1. Custom API URL dari settings (misal: fixer.io, openexchangerates.org)
        if ($apiUrl) {
            $url = rtrim($apiUrl, '/') . '?base=IDR&symbols=' . $symbols;
            if ($apiKey) $url .= '&access_key=' . $apiKey;
            $res = Http::timeout(10)->get($url)->json();
            if (!empty($res['rates'])) {
                // Rates API mengembalikan "berapa 1 IDR dalam mata uang X" → kita simpan "berapa IDR per 1 X"
                return array_map(fn($r) => $r > 0 ? round(1 / $r, 4) : 0, $res['rates']);
            }
        }

        // 2. Frankfurter (gratis, tanpa API key, base IDR tidak didukung → gunakan USD lalu convert)
        $res = Http::timeout(10)
            ->get("https://api.frankfurter.app/latest?from=USD&to=IDR," . $symbols)
            ->json();

        if (empty($res['rates']['IDR'])) {
            throw new \RuntimeException('Frankfurter: IDR rate not found');
        }

        $idrPerUsd = (float) $res['rates']['IDR'];
        $result    = [];
        foreach ($currencies as $code) {
            if ($code === 'USD') {
                $result['USD'] = $idrPerUsd;
            } elseif (isset($res['rates'][$code])) {
                // convert: berapa IDR per 1 unit $code = idrPerUsd / usdPer$code
                $usdPerCode    = (float) $res['rates'][$code];
                $result[$code] = $usdPerCode > 0 ? round($idrPerUsd / $usdPerCode, 4) : 0;
            }
        }
        return $result;
    }

    public static function targetCurrencies(): array
    {
        $saved = SettingsService::get('currency_target_list', '');
        if ($saved) {
            return array_filter(array_map('trim', explode(',', strtoupper($saved))));
        }
        return ['USD', 'SGD', 'MYR', 'EUR', 'AUD', 'GBP', 'JPY'];
    }
}
