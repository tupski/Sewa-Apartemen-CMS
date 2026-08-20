<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PromoRate;
use App\Models\Voucher;
use Carbon\Carbon;

class BookingPricingService
{
    /** Transit duration buckets in hours. */
    public const TRANSIT_BUCKETS = [3, 6, 9, 12, 24];

    /** Night count for bulk rates. */
    public const WEEKLY_NIGHTS = 7;
    public const MONTHLY_NIGHTS = 30;

    /**
     * Calculate the total price for a booking.
     *
     * Weekday/weekend is decided per night (daily) or per check-in day (transit)
     * using the property's own weekend_days configuration, falling back to global
     * settings when the property has no custom weekend_days set.
     *
     * Optionally applies a PromoRate and/or Voucher discount.
     *
     * @return array{total: float, nights: int, hours: ?int, is_weekend: ?bool, days: array<string,float>, rate: ?float, promo: ?array, voucher: ?array, discount: int}
     */
    public function calculate(
        Property $property,
        string $unitType,
        string $bookingType,
        Carbon $checkIn,
        ?Carbon $checkOut = null,
        ?int $hours = null,
        ?int $promoRateId = null,
        ?int $voucherId = null
    ): array {
        $prices      = $property->prices[$unitType] ?? [];
        $weekendDays = $this->resolveWeekendDays($property);

        $promoInfo   = null;
        $voucherInfo = null;

        // Transit: hourly rate, decided by the check-in day.
        if ($bookingType === 'transit') {
            $hours     = $this->normalizeTransitHours($hours);
            $isWeekend = in_array($checkIn->dayOfWeek, $weekendDays, true);
            $key       = ($isWeekend ? 't' . $hours . '_we' : 't' . $hours . '_wd');
            $rate      = (float) ($prices[$key] ?? 0);

            // Apply promo if provided
            if ($promoRateId) {
                $promo = PromoRate::find($promoRateId);
                if ($promo && $promo->property_id === $property->id && $promo->is_active) {
                    $rate      = (float) $promo->price;
                    $promoInfo = ['id' => $promo->id, 'name' => $promo->name, 'price' => $promo->price];
                }
            }

            $base   = $rate;
            $result = [
                'total'      => $base,
                'nights'     => 1,
                'hours'      => $hours,
                'is_weekend' => $isWeekend,
                'days'       => [$checkIn->format('Y-m-d') => $rate],
                'rate'       => $rate,
                'promo'      => $promoInfo,
                'voucher'    => null,
                'discount'   => 0,
            ];

            return $this->applyVoucher($result, $voucherId);
        }

        // Bulk rates: weekly / monthly are flat prices
        if ($bookingType === 'weekly') {
            $rate   = (float) ($prices['weekly'] ?? 0);
            $nights = self::WEEKLY_NIGHTS;

            if ($promoRateId) {
                $promo = PromoRate::find($promoRateId);
                if ($promo && $promo->property_id === $property->id && $promo->is_active) {
                    $rate      = (float) $promo->price;
                    $promoInfo = ['id' => $promo->id, 'name' => $promo->name, 'price' => $promo->price];
                }
            }

            $result = [
                'total'      => $rate,
                'nights'     => $nights,
                'hours'      => null,
                'is_weekend' => null,
                'days'       => [],
                'rate'       => $rate,
                'promo'      => $promoInfo,
                'voucher'    => null,
                'discount'   => 0,
            ];

            return $this->applyVoucher($result, $voucherId);
        }

        if ($bookingType === 'monthly') {
            $rate   = (float) ($prices['monthly'] ?? 0);
            $nights = self::MONTHLY_NIGHTS;

            if ($promoRateId) {
                $promo = PromoRate::find($promoRateId);
                if ($promo && $promo->property_id === $property->id && $promo->is_active) {
                    $rate      = (float) $promo->price;
                    $promoInfo = ['id' => $promo->id, 'name' => $promo->name, 'price' => $promo->price];
                }
            }

            $result = [
                'total'      => $rate,
                'nights'     => $nights,
                'hours'      => null,
                'is_weekend' => null,
                'days'       => [],
                'rate'       => $rate,
                'promo'      => $promoInfo,
                'voucher'    => null,
                'discount'   => 0,
            ];

            return $this->applyVoucher($result, $voucherId);
        }

        // Daily rate: sum up each night
        $nights = max(1, $checkOut ? $checkIn->diffInDays($checkOut) : 1);
        $total  = 0.0;
        $days   = [];

        for ($i = 0; $i < $nights; $i++) {
            $day       = $checkIn->copy()->addDays($i);
            $isWeekend = in_array($day->dayOfWeek, $weekendDays, true);
            $rate      = (float) ($prices[$isWeekend ? 'night_we' : 'night_wd'] ?? 0);
            $total     += $rate;
            $days[$day->format('Y-m-d')] = $rate;
        }

        // Promo can override the per-night rate for daily bookings too
        if ($promoRateId) {
            $promo = PromoRate::find($promoRateId);
            if ($promo && $promo->property_id === $property->id && $promo->is_active) {
                $total     = (float) $promo->price;
                $promoInfo = ['id' => $promo->id, 'name' => $promo->name, 'price' => $promo->price];
            }
        }

        $result = [
            'total'      => round($total, 2),
            'nights'     => $nights,
            'hours'      => null,
            'is_weekend' => null,
            'days'       => $days,
            'rate'       => null,
            'promo'      => $promoInfo,
            'voucher'    => null,
            'discount'   => 0,
        ];

        return $this->applyVoucher($result, $voucherId);
    }

    /**
     * Determine the weekend days array for a property.
     * Uses property-specific config if set, otherwise falls back to global settings.
     */
    public function resolveWeekendDays(Property $property): array
    {
        // If the property has an explicit custom config, use it
        if (!empty($property->weekend_days)) {
            return $property->weekend_days;
        }

        // Fall back to global settings
        $mode = SettingsService::get('weekend_days_mode', 'sat_sun');

        return match ($mode) {
            'fri_sun' => [5, 6, 0],  // Fri, Sat, Sun
            'sat_sun' => [6, 0],     // Sat, Sun
            'custom'  => $this->buildCustomWeekendDays(),
            default   => [6, 0],
        };
    }

    /**
     * Build weekend days array from the custom start/end settings.
     * Handles wrap-around (e.g. start=5 Fri, end=0 Sun → [5,6,0]).
     */
    protected function buildCustomWeekendDays(): array
    {
        $start = (int) SettingsService::get('weekend_start_day', 5);
        $end   = (int) SettingsService::get('weekend_end_day', 0);

        if ($start === $end) {
            return [$start];
        }

        $days = [];
        $day  = $start;

        while ($day !== $end) {
            $days[] = $day;
            $day    = ($day + 1) % 7;
        }

        $days[] = $end;

        return $days;
    }

    /**
     * Apply a voucher discount to a calculated price result array.
     */
    protected function applyVoucher(array $result, ?int $voucherId): array
    {
        if (!$voucherId) {
            return $result;
        }

        $voucher = Voucher::find($voucherId);

        if (!$voucher || !$voucher->isValid()) {
            return $result;
        }

        $amount   = (int) round($result['total']);
        $discount = $voucher->calculateDiscount($amount);

        if ($discount > 0) {
            $result['total']   = max(0, $result['total'] - $discount);
            $result['discount'] = $discount;
            $result['voucher']  = [
                'id'             => $voucher->id,
                'code'           => $voucher->code,
                'name'           => $voucher->name,
                'discount_type'  => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
                'discount_amount'=> $discount,
            ];
        }

        return $result;
    }

    /**
     * Round arbitrary transit hours UP to the nearest available bucket (max 24).
     *
     * BUG-008 FIX: Tambahkan null/zero guard — PHP null <= 3 adalah true (silent bug),
     * sehingga booking transit tanpa durasi sebelumnya dihitung 3 jam tanpa error.
     * Sekarang melempar InvalidArgumentException dengan pesan yang jelas.
     */
    protected function normalizeTransitHours(?int $hours): int
    {
        if ($hours === null || $hours <= 0) {
            throw new \InvalidArgumentException(
                'Durasi transit harus diisi dan bernilai lebih dari 0 jam.'
            );
        }

        foreach (self::TRANSIT_BUCKETS as $bucket) {
            if ($hours <= $bucket) {
                return $bucket;
            }
        }

        return self::TRANSIT_BUCKETS[array_key_last(self::TRANSIT_BUCKETS)];
    }
}
